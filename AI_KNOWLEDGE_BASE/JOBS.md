# Jobs & Console Commands

## Jobs — three on `develop`

| Job | ID | Dispatched from |
|---|---|---|
| `ProcessLmsDeliveryJob` | `JOB-001` | `LmsDeliveryService` (3 sites) and itself (`self::dispatch()` on retry) |
| `SendTestInvitationEmailsJob` | `JOB-002` | `TestInvitationController::sendInvitations()`, `invitations:send-pending`, and `SweepPendingInvitationsJob` |
| `SweepPendingInvitationsJob` | `JOB-003` | `sendInvitations()` **and** `getUnregisteredInvitations()`, both `->afterResponse()` |

All three are on `develop` and in the generated indexes since `ws-404` merged (PR #240 on 2026-09-15,
follow-up PR #251 on 2026-09-16).

Their retry models, dead-letter handling and the worker situation are covered in
[QUEUES.md](QUEUES.md). Read that before touching any of them. The short version: a worker
(`backend-queue`) and a scheduler (`backend-scheduler`) now exist in compose, but ☠️ **both sit behind the
`workers` profile and do not start by default** ([DEPLOYMENT.md](DEPLOYMENT.md)).

⭐ `SendTestInvitationEmailsJob` implements `ShouldQueue` but by default is **never queued**: it is
dispatched with `->afterResponse()` and runs in the web process. `$tries` (3), `$backoff` (`[60, 300]`)
and `failed()` on it are inert in that mode. `mail.invitation_dispatch=queue` puts it on `backend-queue`
and makes all three live — a **config choice** per environment, and only safe where the `workers` profile
is on. See [QUEUES.md](QUEUES.md#after-response-dispatch-ws-404).

### ⭐ Connection failure is not address rejection

The single most consequential behaviour in the invitation path, because it decides whether a patient's
invitation is revoked and the credit refunded. `sendOne()` returns one of three results:

| Result | Meaning | Row ends at |
|---|---|---|
| `sent` | The server accepted the message | `sent` |
| `failed` | The server **rejected this recipient** — a verdict on the address | `failed`, revoked, credit refunded |
| `deferred` | Nothing here is a verdict on the address | back to `pending`, `deferred_count` + 1 |

Once `MAX_ATTEMPTS_PER_EMAIL = 3` in-call attempts are spent, **`deferralReason()`** decides defer vs
fail. It returns a log phrase (defer) or `null` (fail), and it defers exactly three families:

| Family | Method | What matches | Why it is safe to defer |
|---|---|---|---|
| Never connected | `isConnectFailure()` | SMTP `Connection could not be established`, `Unable to connect with STARTTLS`; AWS `CredentialsException`; SES pre-request cURL **6 / 7 / 35**; SES **429 / 5xx** or a throttling / unavailable / paused / expired-or-rejected-credentials error code; failover `No transports found.` | No socket opened, or SES answered about itself — the message cannot have been accepted |
| Host said "not now" | `isTransientReply()` | a numbered SMTP **4xx** (`TransportException` code 400–499) | RFC 5321: a failure reply to end-of-DATA means the message was not queued. The number *is* the proof |
| Sender over its own quota | `isSenderQuotaRejection()` | **5xx** whose text is sender-scoped: `exceeded the max emails per`, `exceeded the max defers and failures`, `sending quota exceeded`, `sending limit exceeded`, `message rate exceeded`, `rate limit exceeded` | The sentence is about the hour, not the mailbox. ☠️ This is the **QA host's `550 … has exceeded the max emails per hour (200/200)`** — before `8ee517aa` (2026-09-16) it wrote off 286 rows of one 500-address QA send |

☠️ **What is deliberately *not* deferred — do not "fix" these into the list:**

- **`timed out`, `has been closed unexpectedly`, the read/write failures (code 0), SES cURL 28 / 52.**
  All can land *after* the server accepted DATA. Deferring them resends an address that may already be
  delivered — up to three copies per sweep. They still **retry** inside the call (paced by
  `looksLikeConnectionTrouble()`, which is broader on purpose); they just `fail` once attempts run out.
  An earlier `ws-404` version deferred them; `8833f697` split the check.
- **Failover `All transports failed.`** — `RoundRobinTransport` collapses every leg's error into that
  string, so it cannot be told apart from a recipient both hosts rejected.
- **A bare `quota exceeded` / `over quota`.** That is how hosts report a *recipient's* full mailbox — a
  real verdict on the address.
- **Any other 5xx** — `MessageRejected`, invalid address, suppression-list hit.

**The deferral cap.** A deferred row increments `test_invitations.deferred_count` (migration
`2026_09_14_000001`). When the count **exceeds** `mail.invitation_max_deferrals` (default **36**, `>` not
`>=` — 36 deferrals are allowed, the 37th writes off), the row is `markFailed()` with
`"Deferred N times without reaching the mail host"` and refunded. Without it an unreachable row stayed
`pending` forever as the oldest id, tripping the batch breaker ahead of everything behind it. ☠️ **The
value is a downtime tolerance** — roughly one deferral per 10-minute scheduled sweep, so 36 ≈ 6 hours;
the earlier 5 (≈40 min) permanently revoked the oldest invitations of every in-flight send during an
ordinary SES incident. Count and write-off happen **while the `sending` claim is still held**; releasing
first let the sweep claim and deliver a row that `markFailed()` then revoked and refunded.

Two circuit breakers sit on top:

- **Batch breaker** — `MAX_CONSECUTIVE_CONNECTION_FAILURES = 3` consecutive **deferred** results stop the
  batch and log one `Invitation email batch stopped: mail host is not accepting connections` line naming
  `mail.default`'s mailer, host and port. A `sent` **or** a `failed` resets the counter. Since 4xx now
  defers, a throttling host trips the breaker too (before, each 4xx failure reset it and the batch ground
  through the whole list writing addresses off).
- **Process stand-down** — `HOST_STANDDOWN_SECONDS = 60`, held in a **static** so the 20 batches of a
  500-address send share one view of the outage instead of each rediscovering it. It carries across
  requests in the same FPM child, which is why it expires on a timestamp rather than being a flag, and
  why `resetHostStandDown()` exists.

⚠️ **`markFailed()` is claim-scoped.** It updates only `WHERE email_status = 'sending' AND is_revoked =
false`. Losing that race means the row was settled elsewhere — delivered by another sweep, or cancelled
and refunded by `cancelUnregisteredInvitation` — so it logs, **issues no refund**, and releases the claim.
An unconditional update here revoked delivered invitations and double-refunded cancelled ones. Its refund
passes `creditedBy: null` ([CREDITS_CONTEXT](CONTEXT/CREDITS_CONTEXT.md)).

⚠️ **`failed()` does not write the batch off.** It only releases `sending` claims back to `pending`. Rows
already at `sent` or `failed` are left alone — reopening a `sent` row would mail that patient twice. This
matters because `queue` mode makes the method reachable at all.

⚠️ **Queue-mode time budget.** In `after_response` mode the controller passes one absolute `$deadline`
for the whole send (`mail.invitation_send_budget`, 240s). In `queue` mode it passes
`$budgetSeconds` (`mail.invitation_queue_batch_budget`, 60s) instead, and `handle()` turns that into
`$runDeadline` **when the worker starts the batch**. ☠️ Do not compute it at dispatch: all 20 batches
dispatch within milliseconds but run serially on one worker, so a shared timestamp expires for all but
the first few and leaves hundreds of charged invitations `pending` with nothing in the log.

☠️ **Never name a method on a queued job `release()`.** `SendTestInvitationEmailsJob` had a private
`release(TestInvitation $invitation)` that gave a claimed row back. The class also uses
`InteractsWithQueue`, whose own **`release($delay = 0)` puts the job back on the queue** — so the
class method silently shadowed the trait's, with no error, because a class method always wins over a
trait's.

It was harmless only while nothing called the trait version. Queue middleware do: `RateLimited`,
`WithoutOverlapping` and `ThrottlesExceptions` all call `$job->release($seconds)` on the handler, and
this job runs on a real worker whenever `mail.invitation_dispatch=queue`. Adding a rate limiter to
a bulk mail job — which [the review checklist actively asks for](REVIEW/REVIEW_CHECKLIST.md) — would
have called a *private* method with an `int` where a `TestInvitation` was expected.

Renamed to **`releaseClaim()`** (`3f3aeb58`, merged with `ws-404`). The same trap applies to any other
`InteractsWithQueue` method name — `attempts()`, `delete()`, `fail()`. (Counting a deferral was later
split out of it into `countDeferral()`, for the claim-ordering reason above.)

### `SweepPendingInvitationsJob` — recovery that rides on web traffic

`SendTestInvitationEmailsJob` leaves a row at `email_status='pending'` when it defers, assuming something
comes back for it. Two things do on `develop`: the scheduled `invitations:send-pending` — **only where the
`workers` profile runs `backend-scheduler`** — and this job, which uses **ordinary requests as the clock**
and so works on any deployment of the image.

Dispatched `->afterResponse()` from the two endpoints where a stranded invitation is most likely to
exist and to matter: sending invitations, and opening the list that displays delivery status. Three
properties keep that from being reckless:

| Property | Mechanism |
|---|---|
| Throttled | Atomic cache lock `invitations:sweep`, **taken and never released**, so it expires on its own — at most one sweep per `mail.invitation_sweep_interval` (default 600s, floor 60s) across all replicas |
| Bounded | One batch (`SendTestInvitationEmailsJob::BATCH_SIZE` = 25), own deadline `mail.invitation_sweep_budget` (default 60s) — it can never hold an FPM child the way a 500-address send can |
| Cheap when broken | If the mail host is refusing connections, the mailer's stand-down makes it return almost immediately rather than retry into a dead server on every request |

It only considers rows older than `mail.invitation_sweep_age_minutes` (default 15), so it cannot race a
send still working through its batches in another web process. `whereNotNull('user_id')` is applied **in
the query, before `limit()`** — filtering afterwards let a page of orphaned rows (deleted accounts) starve
owned rows queued right behind them.

☠️ **It needs traffic — an idle deployment sweeps nothing**, and without the `workers` profile nothing else
runs either. Registering both paths is safe: they select the same rows via
`TestInvitation::awaitingDelivery()` and the batch job claims each row atomically, so whichever reaches an
address first sends it exactly once.

## Console commands — six on `develop`

| Command | Class | Notes |
|---|---|---|
| `upload:test-plates` | `UploadTestPlates.php` | Operator tool, see below |
| `invitations:send-pending` | `SendPendingInvitations.php` | `--minutes=15` · `--limit=500`. **Scheduled** every 10 min (needs `backend-scheduler`) |
| `templates:check-placeholders` | `CheckEmailTemplatePlaceholders.php` | `--show-body` |
| `stripe:backfill-source-app` | `BackfillStripeSourceApp.php` | Dry run unless `--apply` |
| `credits:settle-negative-balances` | `SettleNegativeCreditBalances.php` | One-time repair, dry run unless `--apply`; see [CONTEXT/CREDITS_CONTEXT.md](CONTEXT/CREDITS_CONTEXT.md) |
| `mail:preflight` | `MailPreflight.php` | `--from=`, checks sender identity verification. Read-only, sends no mail |

⚠️ **Not every command is read-only any more.** `stripe:backfill-source-app` and
`credits:settle-negative-balances` dry-run unless given `--apply`, but **`invitations:send-pending` acts
on its first run with no flag**: it reclaims abandoned `sending` claims, **refunds and revokes expired
rows**, and mails the rest. Run it with `--limit=1` first on an environment with a backlog
([DEPLOYMENT.md](DEPLOYMENT.md)).

`invitations:send-pending`, in order:

1. `resetHostStandDown()` — an operator running this by hand has usually just fixed the mail host and
   should not have the run skipped by a window an earlier batch set.
2. **Reclaim** rows stuck at `sending` longer than `--minutes` back to `pending`.
3. ⭐ **`expireStaleInvitations($limit)`** (`8833f697`) — rows still `pending`, not revoked, and past
   `expires_at` are set `failed` + `is_revoked` with `"Invitation expired before it could be delivered"`
   and refunded 1 credit (`SOURCE_REVOKED`, `creditedBy: null`). Without this, a row whose 7-day window
   closed while undelivered fell out of `awaitingDelivery()` (which requires `expires_at > now()`) and was
   charged for forever. The write re-checks `email_status = 'pending' AND is_revoked = false`, so it
   neither double-refunds a cancelled row nor steals a live batch's claim. Bounded by `--limit` so a
   post-outage backlog cannot eat the whole 20-minute overlap lock.
4. Select owned rows via `awaitingDelivery($minutes)->whereNotNull('user_id')` (in the query) and send
   them; count orphans separately and warn.
5. Exit **non-zero** if anything failed, remains pending, or was orphaned — the summary line
   (`Sent: x, failed: y, still pending: z`) is the only place a stalled recovery shows, which is why the
   schedule deliberately does not `runInBackground()`.

`templates:check-placeholders` scans `user_email_templates` and `test_email_templates` for placeholders
that will not render. It strips HTML before scanning, so it finds breakage a plain SQL `LIKE` cannot —
see [CONTEXT/INVITATION_CONTEXT.md](CONTEXT/INVITATION_CONTEXT.md).

⚠️ **It scans per `type`, and it only recognises `{{…}}`.** Two consequences worth knowing before you
trust a clean run: a legacy `[bracket]` placeholder is invisible to it entirely (that is what `ws-401`'s
repair migration exists for, and why that migration logs what it could not convert), and a row holding a
token valid for the *other* template type is reported as unrecognised — so a `FAILURE` here can mean a
row was written by something that ignored the type scoping, not that a human mistyped it.

⭐ **`mail:preflight` answers "will invitation email actually work in this environment?" before a send
finds out.** Every mail failure this codebase has worked around was silent at the point of configuration
and loud only once patients were involved, so the command exercises the credentials rather than reading
the config:

| Check | Catches |
|---|---|
| Transport is not `log` or `array` | ☠️ The two settings that accept every message and deliver nothing — and `mail.default` falls back to `log` when `MAIL_MAILER` is unset |
| SES account state + sender identity verified (v2 API) | A correct config whose `from` address SES will not send for |
| SMTP reachability | The refused-connection case the batch job defers on |
| Queue backlog | `MAIL_INVITATION_DISPATCH=queue` with no worker draining `jobs` |
| Stuck invitations | Rows sitting at `email_status='pending'` that nothing has come back for |

It exits non-zero on any failure, so it works as a deploy gate. Run it after any mail or queue
configuration change. `tests/Feature/TestInvitations/MailPreflightTest.php` covers it.

`templates:check-placeholders` is not scheduled.

`upload:test-plates` uploads test plate images to the S3 bucket. This is an **operator tool**, not part
of any flow — `SecureImageService::uploadPlateToS3()` exists for the same purpose and carries a comment
saying it is "currently not used, as upload image functionality is not required for current scope."

`routes/console.php` additionally defines Laravel's stock `inspire` closure. That is all.

## ☠️ One task is scheduled — and by default nothing runs it

`bootstrap/app.php` on `develop` registers exactly one task (since 2026-09-15):
`invitations:send-pending`, `everyTenMinutes()`, `withoutOverlapping(20)`. `routes/console.php` registers
no schedule.

**Registered is not running.** The task fires only under a `schedule:work` process, and the
`backend-scheduler` service that provides one is behind the compose `workers` profile, **off by default**.
📌 The KB's 2026-09-14 correction ("on `ws-404` the task does fire") described `07a1c9b2`, before
`9c7d0f1e` added the profile gate; it is not true of a plain `docker compose up` on `develop`.

Either way there is **no periodic cleanup of anything**:

| Table | Grows unbounded | Expiry is checked… |
|---|---|---|
| `personal_access_tokens` | ✅ | never — expired tokens are just rejected at auth time |
| `test_sessions` | ✅ | at read time (`expires_at` compared) |
| `test_invitations` | ✅ | at read time — plus `expireStaleInvitations()` refunds undelivered expired rows when the command runs (it does not delete them) |
| `test_resume_tokens` | ✅ | at read time (`isExpired()`) |
| `lms_sessions` | ✅ | at read time |
| `jobs` | ✅ unless the `workers` profile is on | — |
| `sessions`, `cache` | ✅ | Laravel prunes `cache` lazily; `sessions` needs `session:gc` |

If you add a scheduled task, remember it inherits the same condition: it runs only where an environment
has opted into `COMPOSE_PROFILES=workers`. Check what is already in the block before adding to it, and
read the first-boot runbook in [DEPLOYMENT.md](DEPLOYMENT.md) — turning the profile on also starts
`backend-queue`, which drains whatever LMS backlog has been accumulating.

## Work that should be a job but isn't

Listed in [QUEUES.md](QUEUES.md#everything-else-is-synchronous). The one worth flagging:

- **Stripe customer creation on every login** — an external API call in the authentication path.

(Bulk invitation email used to top this list — up to 500 sends inside one HTTP request. Since `ws-404`
the request only inserts rows and charges credits; delivery runs in 25-row batches after the response or
on the queue.)
