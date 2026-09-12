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

⭐ `SendTestInvitationEmailsJob` implements `ShouldQueue` but is **never queued**: it is dispatched with
`->afterResponse()` and runs in the web process. `$tries`, `$backoff` and `failed()` on it are inert
until a worker exists. See [QUEUES.md](QUEUES.md#after-response-dispatch-ws-404).

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
| `mail:preflight` | `MailPreflight.php` | ⚠️ **`ws-404` only** — `--from=`, checks sender identity verification |

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

⚠️ **It scans per `type`, and it only recognises `{{…}}`.** Two consequences worth knowing before you
trust a clean run: a legacy `[bracket]` placeholder is invisible to it entirely (that is what `ws-401`'s
repair migration exists for, and why that migration logs what it could not convert), and a row holding a
token valid for the *other* template type is reported as unrecognised — so a `FAILURE` here can mean a
row was written by something that ignored the type scoping, not that a human mistyped it.

☠️ **On `develop`, neither command is scheduled** — `invitations:send-pending` is the only thing that
recovers a stranded send, and nothing runs it, so recovery depends on someone noticing.

⚠️ Unmerged `ws-404` changes that only on paper: it registers `invitations:send-pending` in
`->withSchedule(...)` (every ten minutes), but **nothing executes the schedule** — no cron, no
`schedule:work` container — so the registration is inert there too. What actually recovers a stranded
send on that branch is `SweepPendingInvitationsJob` riding on web traffic (above).
`templates:check-placeholders` is not scheduled on either branch. See the section below.

Uploads test plate images to the S3 bucket. This is an **operator tool**, not part of any flow —
`SecureImageService::uploadPlateToS3()` exists for the same purpose and carries a comment saying it is
"currently not used, as upload image functionality is not required for current scope."

`routes/console.php` additionally defines Laravel's stock `inspire` closure. That is all.

## ☠️ Nothing is scheduled

`bootstrap/app.php` on `develop` has no `->withSchedule(...)`, and `routes/console.php` registers no
schedule.

⚠️ Unmerged `ws-404` adds a `->withSchedule(...)` with one task, but **registered is not running**: the
deployment is php-fpm and nginx only — no cron entry, no `schedule:work` container
([DEPLOYMENT.md](DEPLOYMENT.md)) — so `schedule:run` is never invoked and the task never fires. Treat
that block as documentation of intent until a scheduler process exists.

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

If you add a scheduled task, you also need to add a **scheduler process** to the deployment — there is
no `php artisan schedule:work` container today, only php-fpm and nginx
([DEPLOYMENT.md](DEPLOYMENT.md)). Note that once `ws-404` merges there is already one task registered,
so wiring a scheduler switches **both** on at once — check what is in the block before you add to it.

## Work that should be a job but isn't

Listed in [QUEUES.md](QUEUES.md#everything-else-is-synchronous). The two worth flagging:

- **Bulk invitation email** — up to 500 sends inside one HTTP request, preceded by
  `set_time_limit(0)`. The obvious first candidate for a queued job.
- **Stripe customer creation on every login** — an external API call in the authentication path.
