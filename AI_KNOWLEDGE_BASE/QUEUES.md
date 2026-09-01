# Queues

`QUEUE_CONNECTION=database`. Tables: `jobs`, `job_batches`, `failed_jobs`.

## ☠️ Nothing consumes the queue

Neither `docker-compose.yml` nor `docker-compose-dev.yml` defines a worker service, and
`entrypoint.sh` ends with `exec php-fpm`. **Unless a worker runs somewhere outside these files,
dispatched jobs sit in the `jobs` table forever.**

Practical effect: `ProcessLmsDeliveryJob` is the only job that *is* queued, so **LMS completion
reporting silently does not happen** in an environment without a worker. (`SendTestInvitationEmailsJob`
sidesteps this entirely — see [After-response dispatch](#after-response-dispatch-ws-404).) The `lms_delivery_queue` row is created (that part is
synchronous), so `GET api/admin/lms/delivery-status` shows entries stuck at `pending` — that is the
diagnostic.

To run one: `php artisan queue:work` in a container from the same image with the same environment.

## After-response dispatch (`ws-404`)

Because no worker exists, bulk invitation email does **not** use the queue. It uses Laravel's
`->afterResponse()`, which registers a terminating callback instead of enqueuing:

```php
SendTestInvitationEmailsJob::dispatch($invitationIds, $userId)->afterResponse();
```

PHP-FPM flushes the response (`fastcgi_finish_request`), the browser disconnects with its `202`, and
then the batches run in the same process. This is why the endpoint returns in well under a second where
it used to 504.

What that buys and what it costs:

| | |
|---|---|
| ✅ No worker, no cron, no compose change needed | |
| ☠️ Holds a php-fpm child for the whole send | ~3–5 min for 500 addresses, against the base image's default `pm.max_children = 5` |
| ☠️ No automatic retry | a restart mid-send strands rows at `email_status = 'pending'` |
| ☠️ Runs even on a 500 | terminating callbacks fire regardless of response status — see below |

⭐ **Terminating callbacks fire whatever the response status.** A throw *after* dispatch would return a
500 to the caller while every email still went out, and a retry would double-send and double-charge.
`sendInvitations()` therefore does the dispatch as its **last statement**, after every fallible step.
Keep it there.

Anything left pending is recovered with `php artisan invitations:send-pending` ([JOBS.md](JOBS.md)).
Nothing runs that automatically.

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
                    ↘ release(backoff)  ×5  → dead_letter
```

`handle()` opens with `LmsDeliveryQueue::lockForUpdate()->find(...)` inside a transaction, and returns
early for `delivered` (idempotent re-dispatch guard) and `dead_letter` (only an explicit admin replay
may revive it).

Dead letters are managed through `api/admin/lms/dead-letters` — list, `replay`, `dismiss`
([CONTEXT/LMS_CONTEXT.md](CONTEXT/LMS_CONTEXT.md)).

## Everything else is synchronous

Notable things that are **not** queued and therefore run inside the request:

| Work | Where | Cost |
|---|---|---|
| ~~Sending up to **500** invitation emails~~ | `TestInvitationController::sendInvitations()` | **no longer synchronous** — batched after the response (`ws-404`, above). The request now only inserts rows and charges credits |
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
3. **Confirm a worker exists in the target environment**, or the feature will look broken with no error.
4. Failures land in `failed_jobs`; `php artisan queue:failed` lists them.

## What is missing

No `Schedule` — `routes/console.php` defines only the stock `inspire` command and `bootstrap/app.php`
has no `->withSchedule(...)`. So there is **no** cleanup of expired sessions, invitations, resume
tokens, or stale `personal_access_tokens`. Those tables grow without bound; the only expiry is checked
at read time.
