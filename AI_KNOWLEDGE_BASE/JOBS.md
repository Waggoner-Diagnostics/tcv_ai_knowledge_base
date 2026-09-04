# Jobs & Console Commands

## Jobs — two

| Job | ID | Dispatched from |
|---|---|---|
| `ProcessLmsDeliveryJob` | `JOB-001` | `LmsDeliveryService` (3 sites) and itself (`self::dispatch()` on retry) |
| `SendTestInvitationEmailsJob` | `JOB-002` | `TestInvitationController::sendInvitations()` and `invitations:send-pending` (`ws-404`) |

Their retry models, dead-letter handling and the fact that **no worker is configured** are covered in
[QUEUES.md](QUEUES.md). Read that before touching either.

⭐ `SendTestInvitationEmailsJob` implements `ShouldQueue` but is **never queued**: it is dispatched with
`->afterResponse()` and runs in the web process. `$tries`, `$backoff` and `failed()` on it are inert
until a worker exists. See [QUEUES.md](QUEUES.md#after-response-dispatch-ws-404).

## Console commands — three

| Command | Class |
|---|---|
| `UploadTestPlates` | `app/Console/Commands/UploadTestPlates.php` |
| `invitations:send-pending` | `app/Console/Commands/SendPendingInvitations.php` (`ws-404`) |
| `templates:check-placeholders` | `app/Console/Commands/CheckEmailTemplatePlaceholders.php` (`ws-404`) |

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

☠️ **Neither command is scheduled.** `invitations:send-pending` is the only thing that recovers a
stranded send, and nothing runs it — recovery depends on someone noticing. See the section below.

Uploads test plate images to the S3 bucket. This is an **operator tool**, not part of any flow —
`SecureImageService::uploadPlateToS3()` exists for the same purpose and carries a comment saying it is
"currently not used, as upload image functionality is not required for current scope."

`routes/console.php` additionally defines Laravel's stock `inspire` closure. That is all.

## ☠️ Nothing is scheduled

`bootstrap/app.php` has no `->withSchedule(...)`, and `routes/console.php` registers no schedule. There
is therefore **no periodic cleanup of anything**:

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
([DEPLOYMENT.md](DEPLOYMENT.md)).

## Work that should be a job but isn't

Listed in [QUEUES.md](QUEUES.md#everything-else-is-synchronous). The two worth flagging:

- **Bulk invitation email** — up to 500 sends inside one HTTP request, preceded by
  `set_time_limit(0)`. The obvious first candidate for a queued job.
- **Stripe customer creation on every login** — an external API call in the authentication path.
