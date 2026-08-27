# How to Debug

## The one thing to know first

**A 500 does not mean a crash.** `app/Exceptions/Handler.php` collapses everything except
`AuthenticationException` (401) and `ValidationException` (422) into a 500 — so `findOrFail` 404s,
`authorize()` 403s, unmatched routes, method-not-allowed and throttling **all arrive as 500**
([ERROR_HANDLING.md](../ERROR_HANDLING.md)).

And because controllers catch their own exceptions and return a generic `api.*_failed` key, **the
response body tells you almost nothing.**

## So: start in the log

```bash
tail -f storage/logs/laravel.log          # single channel, no rotation
docker logs backend-app-tcv               # boot problems only
docker logs backend-nginx-tcv             # JSON access log
```

The log line immediately before the failure carries the real exception. `Handler` also calls
`logger()->error($exception)` for anything reaching its catch-all, so class, file, line and trace are
there even when the response is one line.

☠️ There is **no request/correlation id**, so correlating a user report to a log line means searching by
timestamp and email.

---

## Symptom → first place to look

| Symptom | Look at |
|---|---|
| **500 on a normal request** | the log — it is usually a 404 or 403 in disguise |
| **401 with `error_code: IP_RESTRICTED`** | `restricted_ips` — the caller's IP is listed ([MIDDLEWARE.md](../MIDDLEWARE.md)) |
| **"Logged out after a few minutes"** | `config/sanctum.php` → `'expiration' => 900`. Working as configured; there is no refresh flow |
| **User cannot log in despite verifying email** | the two verification flags disagree — [S-08](../SECURITY.md#s-08--two-email-verification-systems-that-disagree). Check `users.email_verified` (the string), not `email_verified_at` |
| **Email links are broken / point at `/app/...` with no host** | `FRONTEND_URL` unset. `AppServiceProvider` logs a warning at boot; `entrypoint.sh` refuses to start |
| **Blank plates in the test player** | S3. Look for `Failed to generate secure plate URL` — bad credentials, wrong bucket, or the test is not `inprogress` |
| **Plate loads then fails on retry** | the 880 s cache expiring after the 900 s URL, or the plate already answered |
| **Org patient intake rejects everything** | `TURNSTILE_SECRET_KEY` unset — `TurnstileService` **fails closed** |
| **409 `SESSION_STATUS_MISMATCH`** | the LMS session's `status` vs the route's `lms.status:` list ([LMS_CONTEXT](../CONTEXT/LMS_CONTEXT.md)) |
| **`lms.status` gate "not working"** | it only applies when an `LmsSession` is attached — by design |
| **LMS completions never arrive** | `lms_delivery_queue` rows stuck at `pending` ⇒ **no queue worker** ([QUEUES.md](../QUEUES.md)). Check `GET api/admin/lms/delivery-status` |
| **"Insufficient credits" for an unlimited customer** | a caller missing the `!== 'Unlimited'` guard ([CREDITS_CONTEXT](../CONTEXT/CREDITS_CONTEXT.md)) |
| **Customer charged, no credits** | the browser died before `POST api/payment/confirm`; the webhook does not fulfil ([BILLING_CONTEXT](../CONTEXT/BILLING_CONTEXT.md)) |
| **Discount code rejected for a specific user** | the `discount_code_users` list is an **exclusion** list ([DISCOUNT_CONTEXT](../CONTEXT/DISCOUNT_CONTEXT.md)) |
| **An SPA page never renders, no error** | it is missing from `RouteConfig[role].parentRoutes` — check [FRONTEND_ROUTE_INDEX](../INDEXES/FRONTEND_ROUTE_INDEX.md) |
| **SPA call 404s** | check [CONTRACT_DRIFT.md](../INDEXES/CONTRACT_DRIFT.md) — two live calls already have no route |
| **A route change had no effect** | routes are cached at boot. Restart the container, or `php artisan route:clear` locally |
| **`env()` returns null in a service** | config is cached; read `config('…')` instead |
| **Works in dev, 404 in production (website)** | the `next.config.mjs` rewrites are **dev-only** ([WEBSITE.md](../WEBSITE.md)) |
| **A section reports the wrong eye** | `TestHelper::extractEyeFromSectionInstruction()` regexes `Eye: (OU\|OD\|OS)` out of the instruction text |
| **Impersonating admin gets denied everywhere** | `impersonation-token` has none of the nine abilities ([POLICIES.md](../POLICIES.md)) |
| **"Stop impersonating" doesn't revoke** | it deletes nothing — [S-09](../SECURITY.md#s-09--stopimpersonation-never-deletes-the-impersonation-token) |

---

## Useful probes

```bash
# Which routes actually exist (from the KB, no artisan needed)
grep 'api/some-path' AI_KNOWLEDGE_BASE/INDEXES/API_ENDPOINT_INDEX.md

# Is it public?
grep 'api/some-path' AI_KNOWLEDGE_BASE/INDEXES/PUBLIC_ROUTE_AUDIT.md

# Where does the SPA call it?
grep 'api/some-path' AI_KNOWLEDGE_BASE/INDEXES/FRONTEND_API_CALL_INDEX.md

# Exact line of a method
grep 'submitAnswer' AI_KNOWLEDGE_BASE/INDEXES/METHOD_INDEX.md
```

Inside the container, with dependencies installed:
```bash
php artisan route:list --path=api/tests
php artisan tinker
php artisan queue:failed
```

## Adding temporary logging

Follow the house style, and **never log a token, a password or `$request->all()`**
([LOGGING.md](../LOGGING.md)) — `sendVerificationEmailForUser()` already logs a live credential; don't
add more.

```php
Log::info('DEBUG doTheThing', ['user_id' => auth()->id(), 'step' => 'after-validation']);
```

Remove it before you ship.

## When you have fixed it

If the bug was one this KB documents, mark it fixed with a date in the relevant pack or in
[SECURITY.md](../SECURITY.md) — don't just delete the entry. If it was a bug this KB **missed**, add it:
that is the highest-value edit you can make here.
