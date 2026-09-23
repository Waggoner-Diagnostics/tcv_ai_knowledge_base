# Queues

`QUEUE_CONNECTION=database`. Tables: `jobs`, `job_batches`, `failed_jobs`.

## ☠️ By default, nothing consumes the queue

`ws-404` (merged 2026-09-15) added a worker service to **both** compose files — but behind the
`workers` profile, so **`docker compose up -d` does not start it**. `entrypoint.sh` still ends with
`exec php-fpm` for the web container. **Unless an environment runs with `COMPOSE_PROFILES=workers` (or a
worker runs somewhere outside these files), dispatched jobs sit in the `jobs` table forever.**

Practical effect: `ProcessLmsDeliveryJob` is the job that *is* always queued, so **LMS completion
reporting silently does not happen** in an environment without the profile. (`SendTestInvitationEmailsJob`
sidesteps this by default — see [After-response dispatch](#after-response-dispatch-ws-404).) The
`lms_delivery_queue` row is created (that part is synchronous), so `GET api/admin/lms/delivery-status`
shows entries stuck at `pending` — that is the diagnostic.

### The worker and scheduler services (`workers` profile)

| Service | Command | Exists for |
|---|---|---|
| `backend-queue` | `queue:work --queue=lms,default --tries=1 --timeout=300 --max-time=3600 --memory=384` | `ProcessLmsDeliveryJob` (the LMS backlog above) and invitation batches once `MAIL_INVITATION_DISPATCH=queue` |
| `backend-scheduler` | `schedule:work` | The one scheduled task, `invitations:send-pending` — the foreground equivalent of a crontab entry, so the image still needs no cron daemon. 🚧 Branch `ws-460` adds a second, `lms:deliver-pending` every five minutes — the **only** LMS retry path in `after_response` mode ([below](#the-lms-job)) |

☠️ **Enabling the profile drains the backlog immediately** — every LMS delivery queued while no worker
existed goes out, and the first scheduled `invitations:send-pending` resends or refunds every stranded
invitation. Run the queries in [DEPLOYMENT.md](DEPLOYMENT.md)'s first-boot runbook before turning it on.

Both merge the web service's `environment:` block through a YAML anchor (`&backend_env` / `<<: *backend_env`)
so a worker cannot drift onto a different database or mail host than the web process, override only
`RUN_INIT: "false"` (so they never rebuild caches or migrate), and both drop to `www-data` — a root-owned
file in the shared `storage/logs` volume would stop php-fpm (uid 33) appending to the day's log and break
logging for the whole app.

⚠️ **`--queue=lms,default` is priority-ordered, and `--tries=1` is a floor, not a rule.** A job's own
`$tries` overrides the flag, so `SendTestInvitationEmailsJob` still gets its 3 attempts while
`ProcessLmsDeliveryJob` keeps the single attempt its manual retry state machine expects. Same for
`--timeout`. Change the flags and you change neither job's behaviour — change the job.

☠️ **`retry_after` must stay longer than `--timeout`.** `config/queue.php`'s `database.retry_after` is
**360** (it was 90 until `9c7d0f1e`). It is when the queue decides a *reserved* job has died and hands it
to another worker — not a backoff. At 90 against `--timeout=300`, any invitation batch running past 90s
was re-reserved while still sending, and two workers mailed the same patients. Raise the worker's
`--timeout` and you must raise this with it.

## After-response dispatch (`ws-404`)

By default bulk invitation email does **not** use the queue. It uses Laravel's `->afterResponse()`,
which registers a terminating callback instead of enqueuing:

```php
SendTestInvitationEmailsJob::dispatch($invitationIds, $userId, $deadline)->afterResponse();
```

PHP-FPM flushes the response (`fastcgi_finish_request`), the browser disconnects with its `202`, and
then the batches run in the same process. This is why the endpoint returns in well under a second where
it used to 504.

What that buys and what it costs:

| | |
|---|---|
| ✅ No worker, no cron, no compose profile needed | |
| ☠️ Holds a php-fpm child for the whole send | ~3–5 min for 500 addresses, against the base image's default `pm.max_children = 5`; capped by `mail.invitation_send_budget` (240s) for the whole send |
| ☠️ No automatic retry | a restart mid-send strands rows at `email_status = 'pending'` |
| ☠️ Runs even on a 500 | terminating callbacks fire regardless of response status — see below |

⭐ **Terminating callbacks fire whatever the response status.** A throw *after* dispatch returns a
500 to the caller while every email still goes out, and a retry then double-sends and double-charges.

📌 **Corrected 2026-09-14 — this used to say `sendInvitations()` "does the dispatch as its last
statement, after every fallible step. Keep it there." That is not true of the code.** On `develop`
roughly 40 lines run *after* the `dispatchEmailBatch()` loop and inside the same `try`: the
`TestInvitation::…->min('expires_at')` query, the `AuditEventCatalog::invitationSentTitle()` lookup, and
the `auditService->log()` call. Any of them throwing returns a 500 to a caller whose invitations have
already been dispatched and charged for.

☠️ **So the trap is live, not guarded against** — re-verified 2026-09-17. Treat it as an open issue:

- A client retrying that 500 double-sends and double-charges. Nothing is idempotent at the request
  level — the row-level claim in `SendTestInvitationEmailsJob` prevents one *batch* mailing an address
  twice, but a second request creates a second set of invitation rows.
- ✅ **Narrowed on 2026-09-15 (`8833f697`):** each `dispatchEmailBatch()` call is now wrapped in its own
  `try/catch (\Throwable)` that logs `Failed to dispatch invitation email batch; rows left pending for
  recovery` and continues. A failing *dispatch* (e.g. the `jobs` INSERT in queue mode) no longer reaches
  the outer catch. What follows the loop still can.
- The narrow fix for the rest is to move the audit block before the dispatch loop, or to dispatch outside
  the `try`. Neither has been done.

The loop is followed by `SweepPendingInvitationsJob::dispatch()->afterResponse()`, which does **not** make
this worse — the sweep is idempotent and only delivers rows that were already charged for.

Anything left pending is recovered by three routes ([JOBS.md](JOBS.md)): `SweepPendingInvitationsJob`,
riding on the send and list endpoints and throttled to one run per interval (works on every deployment);
the scheduled `invitations:send-pending` (only where the `workers` profile runs `backend-scheduler`); and
running that command by hand.

### ⭐ The dispatch mode is configurable

`dispatchEmailBatch()` forks on `config('mail.invitation_dispatch')`:

```php
if (config('mail.invitation_dispatch') === self::DISPATCH_QUEUE) {
    SendTestInvitationEmailsJob::dispatch(
        $invitationIds, $userId, null,
        (float) config('mail.invitation_queue_batch_budget', 60),   // a DURATION, resolved in handle()
    );
    return;
}
SendTestInvitationEmailsJob::dispatch($invitationIds, $userId, $deadline)->afterResponse();
```

| Mode | Default? | What changes |
|---|---|---|
| `after_response` | ✅ `MAIL_INVITATION_DISPATCH` unset ⇒ this | Behaviour described above. `$tries`/`$backoff`/`failed()` stay inert. One absolute `$deadline` for the whole send |
| `queue` | | Runs on `backend-queue`. Real retries, a `failed_jobs` dead-letter and no competition with web traffic for the FPM pool. Each batch gets **its own 60s budget**, converted to a deadline when the worker starts it |

📌 **The queue path no longer passes a null deadline** (it did before `8833f697`). With none, a fully
unreachable host ran every address through three ~30s connect attempts until the batch hit the job's
`$timeout = 180`, got killed, and was retried up to `$tries` times with backoff — one dead host tied up
the single worker for hours across a large send. ☠️ And the budget must be a **duration**, not a
timestamp computed at dispatch: all 20 batches of a 500-address send dispatch together but run one after
another, so a shared timestamp is spent by the first few and the rest return immediately, leaving hundreds
of charged invitations `pending` with nothing logged.

☠️ **Setting `queue` without a running worker is worse than leaving it alone** — batches accept into
`jobs` and nothing sends them, with no error anywhere, the same silent failure `ProcessLmsDeliveryJob`
already suffers. Since the worker is now **off by default**, this is the likely state of any environment
that sets the variable without also setting `COMPOSE_PROFILES=workers`. The sweep is what keeps that
recoverable rather than permanent: rows stay `pending` until something delivers them, so a dead worker
degrades to *late* mail, not *lost* mail.

⭐ **The sweep stays on `->afterResponse()` even in queue mode, deliberately.** Its job is to catch what
nothing else delivered, and the case most needing catching is a dead worker — exactly when dispatching
the sweep itself to the queue would achieve nothing.

## The LMS job

`app/Jobs/ProcessLmsDeliveryJob.php`

```php
public int $tries   = 1;      // framework retries disabled…
public int $timeout = 60;
private array $backoffSchedule = [30, 120, 600, 3600, 21600];   // …retry is fully manual
```

It re-implements retry itself so the schedule and the dead-letter state live in
`lms_delivery_queue`, not in the framework's `attempts()`:

```
pending → in_flight → delivered
                    ↘ re-dispatch ->delay(backoff)  ×5  → dead_letter
```

(The class comment still says "via `release()`"; the code re-dispatches so the queue row, not the
`jobs` row, is authoritative.)

### 🚧 `ws-460`: LMS delivery gets the invitations' dispatch switch (branch, not on `develop`)

`config/lms.php` → `delivery_dispatch` (`LMS_DELIVERY_DISPATCH`), defaulting to **`after_response`**
because no environment consumes the `lms` queue. `LmsDeliveryService::dispatchDelivery()` is the single
fork for completion, section progress and dead-letter replay.

| | `after_response` (default) | `queue` |
|---|---|---|
| First attempt | web process, after the response | `backend-queue`, `afterCommit()` |
| Retry | ☠️ **none from the job** — row stays `pending` + `next_retry_at` | delayed re-dispatch |
| Who retries | `lms:deliver-pending` (scheduled 5 min, `withoutOverlapping(10)`; or by hand, `--limit=50`, `--dry-run`) | the job; the command is then a safety net |

☠️ **Same shape as invitations, same catch: the scheduler is behind the `workers` profile too.** Without
it the first attempt happens and retries don't — rows sit `pending` with `next_retry_at` in the past.
That, not "stuck at `pending` with a null `next_retry_at`", is the new signature of a missing scheduler.
The command also picks up rows whose after-response run never happened (FPM recycled, deploy mid-request).

⚠️ `LMS_DELIVERY_DISPATCH` is passed into the backend `environment:` block by `ws-460` in both compose
files; before that it never reached the container. `phpunit.xml` pins it to `queue` (see
[CONTEXT/LMS_CONTEXT.md](CONTEXT/LMS_CONTEXT.md)).

`handle()` opens with `LmsDeliveryQueue::lockForUpdate()->find(...)` inside a transaction, and returns
early for `delivered` (idempotent re-dispatch guard) and `dead_letter` (only an explicit admin replay
may revive it).

Dead letters are managed through `api/admin/lms/dead-letters` — list, `replay`, `dismiss`
([CONTEXT/LMS_CONTEXT.md](CONTEXT/LMS_CONTEXT.md)).

## Everything else is synchronous

Notable things that are **not** queued and therefore run inside the request:

| Work | Where | Cost |
|---|---|---|
| ~~Sending up to **500** invitation emails~~ | `TestInvitationController::sendInvitations()` | **no longer synchronous** — batched after the response or on the queue (`ws-404`, above). The request now only inserts rows and charges credits |
| Verification / reset / setup emails | `AuthController`, notifications | per-request SMTP round-trip |
| Test-resume email | `TestResumeController` | same |
| Stripe customer creation | on **every** login | an API call in the login path |
| PDF generation | `TestController::*PDF` | dompdf render in-request |
| Excel export | `ReportController` | re-runs the report query |

☠️ `SendAfterPasswordReset` imports `ShouldQueue` but **does not implement it** — it runs synchronously
inside the password-set request.

## Adding a job

1. `implements ShouldQueue` + `use Dispatchable, InteractsWithQueue, Queueable, SerializesModels`.
2. Decide retries deliberately: the framework default (`$tries` unset ⇒ retry forever) or the manual
   pattern `ProcessLmsDeliveryJob` uses. Do not mix them.
3. **Confirm a worker exists in the target environment** — i.e. that it runs with the `workers` profile —
   or the feature will look broken with no error.
4. Keep the job's `$timeout` under the worker's `--timeout=300`, and both under `retry_after` (360).
5. Never name a method `release()`, `attempts()`, `delete()` or `fail()` ([JOBS.md](JOBS.md)).
6. Failures land in `failed_jobs`; `php artisan queue:failed` lists them.

## What is missing

**One** scheduled task (`invitations:send-pending`, every ten minutes), registered in
`bootstrap/app.php` since 2026-09-15 and run only by `backend-scheduler` under the `workers` profile.
(Two on branch `ws-460`, which adds `lms:deliver-pending` every five minutes.)
`routes/console.php` defines only the stock `inspire` command.

There is still **no** cleanup of expired sessions, invitations, resume tokens, or stale
`personal_access_tokens`. Those tables grow without bound; the only expiry is checked at read time.
(`invitations:send-pending` now *refunds* undelivered expired invitations, but deletes nothing.)
