# Jobs & Console Commands

## Jobs — two on `develop`, three on `ws-404`

| Job | ID | Dispatched from |
|---|---|---|
| `ProcessLmsDeliveryJob` | `JOB-001` | `LmsDeliveryService` (3 sites) and itself (`self::dispatch()` on retry) |
| `SendTestInvitationEmailsJob` | `JOB-002` | `TestInvitationController::sendInvitations()` and `invitations:send-pending` (`ws-404`) |
| `SweepPendingInvitationsJob` | `JOB-003` | ⚠️ **`ws-404` only, not on `develop`** — `sendInvitations()` **and** `getUnregisteredInvitations()`, both `->afterResponse()` |

The indexes are generated from `develop` and therefore list **two**. `JOB-003` is prose-only until
`ws-404` merges; see the README's branch rule.

Their retry models, dead-letter handling and the fact that **no worker is configured** are covered in
[QUEUES.md](QUEUES.md). Read that before touching either.

⭐ `SendTestInvitationEmailsJob` implements `ShouldQueue` but on `develop` is **never queued**: it is
dispatched with `->afterResponse()` and runs in the web process. `$tries`, `$backoff` and `failed()` on
it are inert until a worker exists. See [QUEUES.md](QUEUES.md#after-response-dispatch-ws-404).
⚠️ On `ws-404` that is a **config choice**, not a fact: `mail.invitation_dispatch=queue` puts it on
`backend-queue` and makes all three live. The default is still `after_response`.

### ⭐ Connection failure is not address rejection (`ws-404`, **unmerged**)

The single most consequential behaviour change on the branch, because it decides whether a patient's
credit is refunded. `sendOne()` stops returning `bool` and returns one of three results:

| Result | Meaning | Row ends at |
|---|---|---|
| `sent` | The server accepted the message | `sent` |
| `failed` | The server **rejected this recipient** — a verdict on the address | `failed`, revoked, credit refunded |
| `deferred` | We never reached the server, **or it declined to take the message on its own account** | released back to `pending` |

📌 **Amended 2026-09-16 (QA re-report on `ws-404`).** `deferralReason()` now owns this decision and
returns non-null for **three** cases, not one. The `deferred` arm originally covered connect failures
only; the two additions are:

- **An SMTP 4xx that outlasts the three in-call attempts** (`isTransientReply()`). Those attempts span
  about three seconds, and nothing producing a 4xx clears that fast. Deliberately narrower than
  `isTransient()`, which also matches code **0** — the socket bucket holding the post-DATA cases
  (`timed out`, `has been closed unexpectedly`, read/write) that must keep failing, because deferring
  those risks a duplicate email. A 4xx *reply* proves the server did not queue the message
  (RFC 5321 §4.2.5); silence proves nothing.
- ☠️ **A 5xx rejecting the *sender's* quota rather than the recipient** (`isSenderQuotaRejection()`).
  This is what QA actually hit, and it is the counter-example to "believe a 5xx". The host answered 286
  rows of one 500-address send with `550-Domain devwaggonerllc.space has exceeded the max emails per
  hour (200/200 (100%)) allowed. Message discarded.` — the same 550 a dead mailbox returns, so the
  message text is the only discriminator. It names the sending domain and an hour; it stops being true
  at the top of the next one. The needles are sender-scoped on purpose, and a bare `quota exceeded` /
  `over quota` is deliberately **absent** — that is how a host reports the *recipient's* mailbox being
  full, which is a real verdict and must keep failing.

⚠️ QA's host caps the domain at **200 emails/hour**, so a 500-address send cannot complete inside one
hour by construction. That is an environment limit, not a code defect — the fix makes the overflow
*late* rather than *lost*.

`isConnectionFailure()` makes the call, matching the literal Symfony message formats (`Connection could
not be established`, `Unable to connect with STARTTLS`, `timed out`, `has been closed unexpectedly`,
`Unable to read from connection`, `Unable to write bytes on the wire`) because Symfony gives all of them
exception code 0 and the message is the only discriminator.

☠️ **This is the bug the branch exists for.** Collapsing the two made a few minutes of mail-host downtime
look like a scattering of undeliverable patients, each one revoked and refunded and needing to be sent
by hand again. A deferred row keeps its live token and its charge and waits for a sweep.

Two circuit breakers sit on top:

- **Batch breaker** — `MAX_CONSECUTIVE_CONNECTION_FAILURES = 3` consecutive deferrals stops the batch.
  A `sent` **or** a `failed` resets the counter: a rejection aimed at one address says nothing about the
  host and must not trip a connection breaker.
  ⭐ Since the 4xx amendment above, a throttling host reaches this breaker at all. Previously each 4xx
  write-off reset the counter, so a host answering "not now" was never recognised as one condition —
  the batch ground on and kept converting the same throttle into fresh undeliverable addresses.
- **Process stand-down** — `HOST_STANDDOWN_SECONDS = 60`, held in a **static** so the 20 batches of a
  500-address send share one view of the outage instead of each rediscovering it. It carries across
  requests in the same FPM child, which is why it expires on a timestamp rather than being a flag, and
  why `resetHostStandDown()` exists.

⚠️ **`failed()` no longer writes the batch off.** It used to mark every row failed, revoked and refunded;
it now only releases `sending` claims back to `pending`. Rows already at `sent` or `failed` are left
alone — reopening a `sent` row would mail that patient twice. This matters now that `queue` mode makes
the method reachable at all.

☠️ **Never name a method on a queued job `release()`.** `SendTestInvitationEmailsJob` had a private
`release(TestInvitation $invitation)` that gave a claimed row back. The class also uses
`InteractsWithQueue`, whose own **`release($delay = 0)` puts the job back on the queue** — so the
class method silently shadowed the trait's, with no error, because a class method always wins over a
trait's.

It was harmless only while nothing called the trait version. Queue middleware do: `RateLimited`,
`WithoutOverlapping` and `ThrottlesExceptions` all call `$job->release($seconds)` on the handler, and
this job now runs on a real worker whenever `mail.invitation_dispatch=queue`. Adding a rate limiter to
a bulk mail job — which [the review checklist actively asks for](REVIEW/REVIEW_CHECKLIST.md) — would
have called a *private* method with an `int` where a `TestInvitation` was expected.

Renamed to **`releaseClaim()`** on `ws-404` (`3f3aeb58`) before that could land. The same trap applies
to any other `InteractsWithQueue` method name — `attempts()`, `delete()`, `fail()`.

### `SweepPendingInvitationsJob` — recovery that rides on web traffic (`ws-404`, **unmerged**)

`SendTestInvitationEmailsJob` leaves a row at `email_status='pending'` when it cannot reach the SMTP
host, assuming something comes back for it. `invitations:send-pending` is that something, but it needs a
scheduler this deployment does not have ([DEPLOYMENT.md](DEPLOYMENT.md)). The sweep job closes the gap
by using **ordinary requests as the clock** instead.

Dispatched `->afterResponse()` from the two endpoints where a stranded invitation is most likely to
exist and to matter: sending invitations, and opening the list that displays delivery status. Three
properties keep that from being reckless:

| Property | Mechanism |
|---|---|
| Throttled | Atomic cache lock `invitations:sweep`, **taken and never released**, so it expires on its own — at most one sweep per `mail.invitation_sweep_interval` (default 600s, floor 60s) across all replicas |
| Bounded | One batch (`SendTestInvitationEmailsJob::BATCH_SIZE`), own deadline `mail.invitation_sweep_budget` (default 60s) — it can never hold an FPM child the way a 500-address send can |
| Cheap when broken | If the mail host is refusing connections, the mailer's stand-down makes it return almost immediately rather than retry into a dead server on every request |

It only considers rows older than `mail.invitation_sweep_age_minutes` (default 15), so it cannot race a
send still working through its batches in another web process.

☠️ **It needs traffic — an idle deployment sweeps nothing.** That is the deliberate trade against a
cron entry, and it is why the scheduled `invitations:send-pending` is registered *as well*. Registering
both is safe: they select the same rows via `TestInvitation::awaitingDelivery()` and the batch job
claims each row atomically, so whichever reaches an address first sends it exactly once.

## Console commands — five on `develop`, six on `ws-404`

| Command | Class | Notes |
|---|---|---|
| `upload:test-plates` | `UploadTestPlates.php` | Operator tool, see below |
| `invitations:send-pending` | `SendPendingInvitations.php` | `--minutes=15` · `--limit=500` |
| `templates:check-placeholders` | `CheckEmailTemplatePlaceholders.php` | `--show-body` |
| `stripe:backfill-source-app` | `BackfillStripeSourceApp.php` | Dry run unless `--apply` |
| `credits:settle-negative-balances` | `SettleNegativeCreditBalances.php` | One-time repair, dry run unless `--apply`; see [CONTEXT/CREDITS_CONTEXT.md](CONTEXT/CREDITS_CONTEXT.md) |
| `mail:preflight` | `MailPreflight.php` | ⚠️ **`ws-404` only** — `--from=`, checks sender identity verification. Read-only, sends no mail |

⭐ **None of them is destructive by default.** The two repair commands (`stripe:backfill-source-app`,
`credits:settle-negative-balances`) dry-run unless given `--apply`, so reading their output first costs
nothing.

`invitations:send-pending` mails invitations left at `email_status = 'pending'` — the rows a container
restart stranded mid-send. It skips anything newer than `--minutes=15` so it cannot race a send still
running in a web process, skips revoked/expired rows, skips rows whose `user_id` is NULL (deleted
account), and returns a **non-zero exit code** if anything failed or remains pending.

`templates:check-placeholders` scans `user_email_templates` and `test_email_templates` for placeholders
that will not render. It strips HTML before scanning, so it finds breakage a plain SQL `LIKE` cannot —
see [CONTEXT/INVITATION_CONTEXT.md](CONTEXT/INVITATION_CONTEXT.md).

⭐ **`mail:preflight` (`ws-404`) answers "will invitation email actually work in this environment?"
before a send finds out.** Every mail failure this codebase has worked around was silent at the point of
configuration and loud only once patients were involved, so the command exercises the credentials rather
than reading the config:

| Check | Catches |
|---|---|
| Transport is not `log` or `array` | ☠️ The two settings that accept every message and deliver nothing — and `mail.default` falls back to `log` when `MAIL_MAILER` is unset |
| SES account state + sender identity verified (v2 API) | A correct config whose `from` address SES will not send for |
| SMTP reachability | The refused-connection case the batch job defers on |
| Queue backlog | `MAIL_INVITATION_DISPATCH=queue` with no worker draining `jobs` |
| Stuck invitations | Rows sitting at `email_status='pending'` that nothing has come back for |

It exits non-zero on any failure, so it works as a deploy gate. Run it after any mail or queue
configuration change.

⚠️ **It scans per `type`, and it only recognises `{{…}}`.** Two consequences worth knowing before you
trust a clean run: a legacy `[bracket]` placeholder is invisible to it entirely (that is what `ws-401`'s
repair migration exists for, and why that migration logs what it could not convert), and a row holding a
token valid for the *other* template type is reported as unrecognised — so a `FAILURE` here can mean a
row was written by something that ignored the type scoping, not that a human mistyped it.

☠️ **On `develop`, neither command is scheduled** — `invitations:send-pending` is the only thing that
recovers a stranded send, and nothing runs it, so recovery depends on someone noticing.

⭐ Unmerged `ws-404` changes that for real. It registers `invitations:send-pending` in
`->withSchedule(...)` (every ten minutes, `withoutOverlapping(20)`) **and** adds the
`backend-scheduler` service that executes it, so on that branch recovery no longer depends on someone
noticing. Two independent paths run there — the scheduled command and `SweepPendingInvitationsJob`
riding on web traffic (above) — and having both is safe, because they select the same rows through
`TestInvitation::awaitingDelivery()` and the batch job claims each row atomically.

⭐ The command also calls `SendTestInvitationEmailsJob::resetHostStandDown()` before it starts. The
stand-down is per-process and an operator running this by hand has usually just fixed the mail host;
they should not have the run skipped by a window an earlier batch set.

`templates:check-placeholders` is not scheduled on either branch. See the section below.

Uploads test plate images to the S3 bucket. This is an **operator tool**, not part of any flow —
`SecureImageService::uploadPlateToS3()` exists for the same purpose and carries a comment saying it is
"currently not used, as upload image functionality is not required for current scope."

`routes/console.php` additionally defines Laravel's stock `inspire` closure. That is all.

## ☠️ Nothing is scheduled

`bootstrap/app.php` on `develop` has no `->withSchedule(...)`, and `routes/console.php` registers no
schedule.

📌 **Corrected 2026-09-14.** This previously said `ws-404` registers a task that cannot run. That was
true of `b69a2c37`, whose own comment calls the block "documentation of the intended shape". `07a1c9b2`
then added the `backend-scheduler` service (`schedule:work`) to both compose files, so **on `ws-404` the
task does fire**. The "registered is not running" trap still holds for `develop`, which has neither the
block nor the service ([DEPLOYMENT.md](DEPLOYMENT.md)).

Either way there is **no periodic cleanup of anything**:

| Table | Grows unbounded | Expiry is checked… |
|---|---|---|
| `personal_access_tokens` | ✅ | never — expired tokens are just rejected at auth time |
| `test_sessions` | ✅ | at read time (`expires_at` compared) |
| `test_invitations` | ✅ | at read time |
| `test_resume_tokens` | ✅ | at read time (`isExpired()`) |
| `lms_sessions` | ✅ | at read time |
| `jobs` | ✅ (no worker) | — |
| `sessions`, `cache` | ✅ | Laravel prunes `cache` lazily; `sessions` needs `session:gc` |

If you add a scheduled task, you also need a **scheduler process** in the deployment. On `develop` there
is none — only php-fpm and nginx ([DEPLOYMENT.md](DEPLOYMENT.md)). `ws-404` brings both halves at once:
the `->withSchedule(...)` block *and* the `backend-scheduler` service, so merging it switches scheduling
on for the first time. Check what is in the block before adding to it, and remember that the same merge
also starts consuming the `database` queue via `backend-queue` — the LMS backlog that has been
accumulating will begin to drain on first boot.

## Work that should be a job but isn't

Listed in [QUEUES.md](QUEUES.md#everything-else-is-synchronous). The two worth flagging:

- **Bulk invitation email** — up to 500 sends inside one HTTP request, preceded by
  `set_time_limit(0)`. The obvious first candidate for a queued job.
- **Stripe customer creation on every login** — an external API call in the authentication path.
