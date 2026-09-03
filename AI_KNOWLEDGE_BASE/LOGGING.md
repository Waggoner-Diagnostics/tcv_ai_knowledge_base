# Logging

`LOG_CHANNEL=stack` → `LOG_STACK=single` → **`storage/logs/laravel.log`**, one file, no rotation
configured (`daily` exists as a channel but is not selected).

The host bind-mounts `/var/www/html/storage/logs`, so logs survive container replacement
([SYSTEM_ARCHITECTURE.md](SYSTEM_ARCHITECTURE.md)).

nginx logs separately in a **JSON** format (`json_combined`), suppressing `/health` and `/api/health`.

## The log *is* the error channel

Because `App\Exceptions\Handler` collapses every non-auth, non-validation exception into a generic 500,
and because controllers catch their own exceptions and return a generic `api.*_failed` key, **the
response tells you almost nothing**. The real cause is the log line immediately before it:

```php
} catch (\Exception $e) {
    Log::error('Error submitting answer.', ['error' => $e->getMessage()]);
    return ApiResponse::error(HttpStatus::SERVER_ERROR, 'api.answer_submit_failed');
}
```

`Handler` also calls `logger()->error($exception)` for anything reaching its catch-all branch, so the
full exception (class, file, line, trace) is there even when the response is a one-liner.

**Debugging starts in `storage/logs/laravel.log`, not in the API response.**
See [GUIDES/HOW_TO_DEBUG.md](GUIDES/HOW_TO_DEBUG.md).

## Where logging is dense

| Area | What it records |
|---|---|
| `AuthController` | login gates, verification-token issue/use, password changes — with `user_id`, `email`, `ip` |
| `TestInvitationController` | credits consumed, invitations sent |
| `TestExecutionService` | invitation marked used, and failures |
| `SecureImageService` | `Unauthorized plate access attempt` (warning) and signing failures |
| `LmsLaunchService` / `ProcessLmsDeliveryJob` | session created, delivery attempts, dead letters |
| `AppServiceProvider` | the `FRONTEND_URL` warning at boot |

## ☠️ Secrets in the logs

✅ **The email-verification token is no longer logged in full** (`ws-417`, 2026-09-03). All four sites —
both branches of `AuthController::sendVerificationEmailForUser()` and both `verifyEmailByToken()` lines
— now record a prefix under a `token_prefix` key:

```php
\Log::info('Generated new verification token', [
    'user_id' => …, 'email' => …, 'token_prefix' => substr($verificationToken, 0, 8) . '...', …
]);
```

That token is a **credential** that verifies an account for 24 hours, and it was previously written at
`INFO` on every send, so anyone with log-file or aggregator access could complete a stranger's
verification. `SecureImageService` had the shape right all along (`substr($token, 0, 10) . '...'`);
this matches it. Enough to correlate a user report with a send, useless to a reader.

Still unredacted in logs: patient records (`CreditsController` logs whole model instances), full request
bodies (`Log::info('Performing test.', ['request' => $request->all()])`,
`Log::info('Attempting to create a new credits.', ['request' => $request->all()])`).

**Rule for new code: never log a token, a password, or a whole `$request->all()`.**

## Levels in use

`Log::info` dominates — including for routine successes, which makes the file noisy at
`LOG_LEVEL=debug`. `Log::error` for failures, `Log::warning` only in `SecureImageService` and
`ProcessLmsDeliveryJob`.

Both `\Log::` (root-namespaced) and the imported `Log::` facade appear, sometimes in the same file.
Import the facade in new code.

## What is missing

- **No request id / correlation id.** Nothing ties a log line to a specific HTTP request or a client
  report. With `single` channel and no rotation, correlating a user report to a log line means searching
  by timestamp and email.
- **No structured context convention** beyond ad-hoc arrays.
- **No audit trail.** `AuditLogger` + `pricing_audit_logs` cover **pricing changes only**
  ([MODULES.md](MODULES.md)). Credit grants, impersonation, patient deletion and test abandonment are
  **not** recorded anywhere but this log file.
