# Middleware

Four classes exist in `app/Http/Middleware/` on `develop` (verified 2026-09-17). **Two are global, two
are aliased.** Branch `fix/test-email-and-account-restriction` (2026-11-03, unmerged) adds a fifth,
`RestrictAccountMiddleware`, making **three global**. Branch `fix/account-deleted-popup` (2026-11-04, off `develop`, backend + frontend,
unmerged) extends that one to **deleted** accounts and to letting an in-clinic test finish.

| Class | ID | How it runs | Notes |
|---|---|---|---|
| `AddRequestId` | `MW-001` | **prepended globally** in `bootstrap/app.php` | Stamps an `X-Request-Id` correlation id into Laravel's `Context`, so every log line for a request — and any job it dispatches — carries it, paired with a JSON log formatter ([LOGGING.md](LOGGING.md)) |
| `FlexibleAuthMiddleware` | `MW-002` | alias `FlexibleAuthMiddleware` | The four-tier session gate |
| `LmsSessionStatusMiddleware` | `MW-003` | alias `lms.status` | Parameterised; conditional |
| `RestrictIpMiddleware` | `MW-004` | **appended globally** in `bootstrap/app.php` | Runs on every request. Since `ws-449` also the whole of `GET api/access-check` |
| `RestrictAccountMiddleware` | `MW-005` | **appended globally**, after `RestrictIpMiddleware` (branch only, see below) | Refuses every bearer token of a restricted account with `ACCOUNT_RESTRICTED`, and (branch `fix/account-deleted-popup`) of a deleted one with `ACCOUNT_DELETED` — except six test-taking routes, for the account's own unfinished test |

📌 `EnsureTokenIsValid` (dead, never aliased) was **deleted** when `tcv-backend-codefix` merged; its old
`MW-001` slot is now `AddRequestId`. Earlier text here saying it was "still present on `develop`" was stale.

Laravel's own `auth:sanctum`, `signed` and `throttle` are also used. **Nine named limiters** live in
`AppServiceProvider::configureRateLimiting()`, plus the bare `throttle:10,1` on `api/contact` and
`api/distributor-enquiry`: `login`, `register`, `password-reset`, `signature-verify`, `bulk-invitations`
(keyed `identifier|ip` through `callerKey()`), `plate-url`, `send-resume-email` (keyed on the session
or bearer token), `free-order` (5/min per account, `ws-451`) and `patient-export` (10/min per user id, PR #291,
2026-09-29). ⭐ Two limiters have a custom response callback that writes an audit row: `login` writes
`auth.account_locked` ([CONFIGURATION.md](CONFIGURATION.md)) and `patient-export` writes a failed
`patient.exported` ([PATIENT_CONTEXT](CONTEXT/PATIENT_CONTEXT.md#patient-csv-export--on-develop-since-2026-09-29-pr-291--420)). ☠️ The `|ip` half is only as trustworthy as `$request->ip()`, which
since `ws-449` is forgeable by the traced chain
([S-16](SECURITY.md#status-2026-09-17--both-backend-halves-shipped-the-frontend-nginx-precondition-did-not)).

---

## `RestrictIpMiddleware` — global

```php
if ($request->is('api/logout')) return $next($request);
if (!RestrictedIp::where('ip_address', $request->ip())->exists()) return $next($request);
return $request->bearerToken()
    ? response()->json([... 'error_code' => 'IP_RESTRICTED'], 401)     // logged-in → 401, forces logout
    : response()->json([... 'error_code' => 'IP_RESTRICTED'], 403);    // anonymous → 403
```

- **A DB query on every single request**, uncached, including unauthenticated ones and requests to
  routes that don't exist. It is the app's performance floor and a hard DB dependency ahead of routing.
- `api/logout` is deliberately exempt, so a newly-restricted user can still clean up their token.
- The SPA keys on `error_code: 'IP_RESTRICTED'` to show the right message — see
  `src/apis/AxiosInstance.js`. Do not rename the code without changing the client.

If you add caching here, remember the cache store is `database` by default
([CACHE.md](CACHE.md)) — caching a DB lookup in the DB buys less than it looks.

---

## `RestrictAccountMiddleware` — global (2026-11-03, branch `fix/test-email-and-account-restriction`, unmerged)

```php
if (!$request->bearerToken() || $request->is('api/logout')) return $next($request);
$user = Auth::guard('sanctum')->user();            // cached on the guard: auth:sanctum and
if (!$user instanceof User) return handleMissingUser(); // FlexibleAuth tier 1 reuse it, no 2nd lookup
if ($impersonatorId = /* 'impersonated-by:{id}' ability on the token */)
    return User::find($impersonatorId)?->isActiveAccount() ? $next($request) : blocked(RESTRICTED, false);
if ($user->isActiveAccount()) return $next($request);
return blockedOrFinishTest($request, $next, $user, RESTRICTED);
// blocked(): 401 {success:false, status_code:401, message, error_code:'ACCOUNT_RESTRICTED'|'ACCOUNT_DELETED',
//                 test_may_finish: bool}   (message: api.resticted / api.account_deleted)
```

- **Why it exists:** before it, restricting an account only blocked the *next login*; a token issued
  earlier kept working until logout ([AUTH_CONTEXT](CONTEXT/AUTH_CONTEXT.md#account-restriction-ends-the-session-2026-11-03-branch-fixtest-email-and-account-restriction-unmerged)).
- ☠️ **It depends on the token still existing.** `User::booted()` deliberately does **not** delete a
  restricted account's tokens: a deleted token reaches `auth:sanctum` as a bare 401 "Unauthenticated.",
  which the SPA shows as "Your session has expired" and the user never learns why. Tokens are deleted on
  **re-activation** instead. Do not "tidy up" by revoking on restriction.
- `api/logout` is exempt; **`api/login` is not** — `AuthController::login()` hands any still-valid bearer
  token back as "Already logged in" (200), so exempting it let a restricted user straight back in.
- **"Login as" (impersonation) tokens** stand or fall with the Super Admin who minted them, not with the
  target: allowed on a restricted target (support needs it), refused once that admin is restricted.
  `User::booted()` also deletes them when the admin is restricted.
- Cost: none for anonymous requests; for a Sanctum token, the `sanctum` guard lookup it triggers is the one
  `auth:sanctum` would make anyway. Impersonation tokens add one `users` lookup. ⚠️ Since branch `fix/account-deleted-popup` a
  bearer the guard **cannot** resolve — a test-session / LMS / org-patient token, or an unknown one — costs one
  extra indexed `personal_access_tokens` lookup (`handleMissingUser()`), on a global middleware; a second `users`
  query only when the token is a Sanctum one with a missing owner. Open as [R-13](OPEN_RISKS_PROTECTED_ROUTES.md).
- The SPA keys on `error_code: 'ACCOUNT_RESTRICTED'` / `'ACCOUNT_DELETED'` in `src/services/errorHandler.js`
  (`errorCodeMap`) and `POPUP_MESSAGES.*` — see [FRONTEND.md](FRONTEND.md). Do not rename a code
  without changing the client.

### Deleted accounts (2026-11-04, branch `fix/account-deleted-popup`, unmerged)

- **Why a separate path:** for a soft-deleted owner (or a force-deleted one) the Sanctum guard returns "no
  user" — the same as for an unknown token — so the request used to reach `auth:sanctum` as a bare
  401 "Unauthenticated." with no `error_code`, and the SPA jumped to `/login` with no popup.
  `handleMissingUser()` therefore looks the token up itself: `PersonalAccessToken::findToken()`; ignore
  non-`User` owners and **expired** tokens (Sanctum's own rule: `sanctum.expiration` minutes from
  `created_at`, or `expires_at`); then `User::withTrashed()->find(tokenable_id)`. Trashed → `ACCOUNT_DELETED`;
  row missing (a force-deleted Integrated Partner) → `ACCOUNT_DELETED` with `test_may_finish: false`.
- **Tokens are kept on delete and deleted on restore** (`User::booted()`, `static::restored`: the user's own
  tokens plus any `impersonated-by:{id}` tokens). Same reasoning as restriction: a deleted token would be a
  bare 401. Force-deleting a partner (`OrganizationController::destroy`) leaves its token rows orphaned;
  nothing cleans them up.
- A deleted **impersonator** is reported as `ACCOUNT_RESTRICTED` (`User::find()` skips trashed rows) — the
  user is blocked either way; only the code differs.
- `api/logout` passes this middleware, but `auth:sanctum` still 401s for a deleted user (there is no user to
  log out); the SPA clears the browser itself.

### Letting an in-clinic test finish (2026-11-04, branch `fix/account-deleted-popup`, unmerged)

An in-clinic test (`/user-panel/start-test/*`) runs on the **clinic's own login token**. Once that account is
deleted or restricted the next answer would 401 and the patient would lose the test, so for a blocked
account these routes — and only these — stay open (`FINISH_TEST_ROUTES`, keyed `METHOD uri`):

`GET api/test-session/{unique_test_id}` · `…/section/{section_id}/plates` · `…/plate/{test_answer_id}/url` ·
`POST api/tests/perform` · `GET api/test-result/{unique_test_id}` · `…/download-pdf`

- **Which test:** matched with `app('router')->getRoutes()->match($request)`, because a global middleware runs
  *before* routing and `$request->route()` is null. `perform` names its test through `test_answer_id` →
  `testanswers.unique_test_id`; a plate-URL `test_answer_id` must belong to the same `unique_test_id`.
- **Ownership (the only place the test routes check it — compare [S-02](SECURITY.md#s-02--test-session-endpoints-never-check-that-the-caller-owns-the-test)):**
  the `patient_tests` row must belong to a patient whose `user_id` is the blocked account.
- **Open tests:** `pending` or `inprogress`, and not an emailed invitation (`is_email_invite`; those use their
  own `TestSession`). **Completed tests:** readable only for `RESULT_GRACE_MINUTES = 30` after
  `patient_tests.updated_at`, so the result page can load. Any later write to the row extends that window.
- **Passing through:** the middleware calls `setUser($account)` on **both** the `sanctum` guard
  (FlexibleAuth tier 1 reads it) and the default `api` guard (`auth()->user()` in
  `callerOwnsPatientTest()`), because a soft-deleted owner was never resolved. `lms.status` only acts when an
  `LmsSession` is attached, so it is inert here.
- **Everything else:** 401 + the error code + `test_may_finish` = the account has at least one unfinished
  in-clinic test (same `pending`/`inprogress` rule). The SPA keeps the session on a test page when it is true.
- ☠️ **`FINISH_TEST_ROUTES` fails closed:** rename or re-version one of those routes and finishing quietly stops
  working. No test checks the list against the route table.
- Restricted accounts get exactly the same exception. A force-deleted partner cannot finish (`patients.user_id`
  cascades, the test is gone). The product choices behind all of this are [R-13](OPEN_RISKS_PROTECTED_ROUTES.md).
- Tests: `tests/Feature/Auth/DeletedAccountSessionTest.php` (14).

---

## `FlexibleAuthMiddleware` — the four tiers

Tries in order and returns on the first hit. Full detail in
[CONTEXT/AUTH_CONTEXT.md](CONTEXT/AUTH_CONTEXT.md); the summary:

| Tier | Source | Lookup | Merges |
|---|---|---|---|
| 1 | `Auth::guard('sanctum')` | — | normal `$request->user()` |
| 2 | `test_sessions` | **SHA-256 hashed** `session_token` — the presented token is hashed before lookup | `test_session_id`, `test_invitation_id`, `session_token`, `patient_id` |
| 3 | `lms_sessions` | **SHA-256** of the token | `lms_session_id`, `org_session_id`, `org_id`, `patient_id`, `unique_test_id`, `$request->attributes['lmsSession']` |
| 4 | `organization_patient_sessions` | **SHA-256 hashed** `token` — hashed before lookup | `org_session_id`, `org_id`, `patient_id`, `test_id`, `org_session_token` |

The token is read from `Authorization: Bearer` **or** the `X-Session-Token` header.

✅ **`auth_context` is now on `develop`** (merged from `tcv-backend-codefix`). The
middleware also publishes an unforgeable `auth_context` request attribute — `FlexibleAuthMiddleware::context($request)` returns
`['tier', 'user_id', 'org_id', 'patient_id', 'test_invitation_id', 'test_session_id']`, set on
`$request->attributes` (never reachable by client input, unlike the *Merges* column above). Full detail:
[CONTEXT/AUTH_CONTEXT.md](CONTEXT/AUTH_CONTEXT.md#-auth_context--the-only-trustworthy-answer-to-who-is-calling-2026-09-02).

**Two things to know:**
- Even on that branch it restricts *which record* the caller may act on **only where a controller opts
  in** by calling `FlexibleAuthMiddleware::context()` — there `PatientController`, `TestController`
  (`assignTest`, `getActiveTest`, certificate download) and `TestResumeController` do so, and five
  `unique_test_id`-keyed endpoints still do not. ☠️ **On `develop` no controller does**, because the
  method does not exist: every one of those ownership findings is open
  ([S-02](SECURITY.md#s-02--test-session-endpoints-never-check-that-the-caller-owns-the-test),
  [S-03](SECURITY.md#s-03--sendresumeemail-mails-a-resume-link-for-any-test-to-any-address),
  [S-14](SECURITY.md#s-14--patientsid-showupdatedestroy-have-no-ownership-scoping),
  [S-18](SECURITY.md#s-18--assigntest--getactivetest-let-a-session-act-on-another-organizations-patient)).
- It sets `$request->attributes['lmsSession']` **only** in tier 3 — which is what makes `lms.status:`
  inert everywhere else.

Tier 4 is annotated *"fallback for pre-migration sessions — remove after Phase 3 cutover."* Do not build
on it.

---

## `LmsSessionStatusMiddleware` — `lms.status:a,b,c`

```php
public function handle(Request $request, Closure $next, string ...$allowedStatuses): Response
{
    $lmsSession = $request->attributes->get('lmsSession');

    if ($lmsSession && !in_array($lmsSession->status, $allowedStatuses)) {
        return response()->json([... 'code' => 'SESSION_STATUS_MISMATCH'], 409);
    }
    return $next($request);
}
```

☠️ **The `$lmsSession &&` is the whole story.** With no LMS session attached the middleware is a
pass-through. On `POST api/tests/perform` — decorated `lms.status:test_assigned` — an invitation session
or a resume session sails past the gate.

Read every `lms.status:` in `routes/api.php` as a **conditional** precondition. If you need a gate that
applies to all tiers, this class is not it.

Statuses are **bare strings** in the route file (`lms.status:form_submitted,test_assigned`), not
constants. A typo produces a filter that matches nothing — which, given the early return, still lets
non-LMS sessions through and blocks *only* LMS ones with a 409. See
[CONTEXT/LMS_CONTEXT.md](CONTEXT/LMS_CONTEXT.md) for the status list.

---

## Adding middleware

1. Register the alias in `bootstrap/app.php` → `$middleware->alias([...])`. Laravel 12 has no
   `Kernel.php`; there is nowhere else to put it.
2. Apply it per route or per group in `routes/api.php`.
3. If it should run on everything, use `$middleware->append(...)` — but weigh the per-request cost, and
   remember `RestrictIpMiddleware` already pays a DB round-trip there (and, on the branch above,
   `RestrictAccountMiddleware` resolves the Sanctum user for every bearer request).
4. Re-run the generator: the middleware column in
   [INDEXES/API_ENDPOINT_INDEX.md](INDEXES/API_ENDPOINT_INDEX.md) is derived from the group stack, so it
   picks the change up automatically.
