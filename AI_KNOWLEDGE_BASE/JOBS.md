# Jobs & Console Commands

## Jobs — one

| Job | ID | Dispatched from |
|---|---|---|
| `ProcessLmsDeliveryJob` | `JOB-001` | `LmsDeliveryService` (3 sites) and itself (`self::dispatch()` on retry) |

Its retry model, dead-letter handling and the fact that **no worker is configured** are covered in
[QUEUES.md](QUEUES.md). Read that before touching it.

## Console commands — one

| Command | Class |
|---|---|
| `UploadTestPlates` | `app/Console/Commands/UploadTestPlates.php` |

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
