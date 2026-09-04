# Middleware

Four classes exist in `app/Http/Middleware/`. **Two are global, two are aliased.**

| Class | ID | How it runs | Notes |
|---|---|---|---|
| `RestrictIpMiddleware` | `MW-004` | **appended globally** in `bootstrap/app.php` | Runs on every request |
| `AddRequestId` | `MW-001` | **prepended globally** in `bootstrap/app.php` (`tcv-backend-codefix`) | Stamps every request with a correlation id (`X-Request-Id`, honoured if the caller already set one) into Laravel's `Context`, so every log line for that request — and any queued job it dispatches — carries it. Pairs with the JSON log formatter, see [LOGGING.md](LOGGING.md) |
| `FlexibleAuthMiddleware` | `MW-002` | alias `FlexibleAuthMiddleware` | The four-tier session gate |
| `LmsSessionStatusMiddleware` | `MW-003` | alias `lms.status` | Parameterised; conditional |

`EnsureTokenIsValid` (previously `MW-001`, never aliased or routed) was deleted in `tcv-backend-codefix`
— it was confirmed dead first (see [ARCHITECTURE_REALITY.md](ARCHITECTURE_REALITY.md)), so nothing
references it. Do not resurrect it; use `auth:sanctum` or `FlexibleAuthMiddleware` instead.

Laravel's own `auth:sanctum`, `signed` and `throttle` are also used. `throttle` now backs six named
limiters — `login`, `register`, `password-reset`, `signature-verify`, `bulk-invitations` (all keyed on
`$request->ip()`) and `plate-url` (keyed on the caller's session/bearer token, falling back to ip) —
plus the original bare `throttle:10,1` on `POST api/contact`. Defined in
`AppServiceProvider::configureRateLimiting()`. ☠️ **The five ip-keyed limiters are currently one shared
global bucket for every client** — see [S-16](SECURITY.md#s-16--every-client-shares-one-ip-rate-limits-and-ip-restriction-are-both-inert).

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

## `FlexibleAuthMiddleware` — the four tiers

Tries in order and returns on the first hit. Full detail in
[CONTEXT/AUTH_CONTEXT.md](CONTEXT/AUTH_CONTEXT.md); the summary:

| Tier | Source | Lookup | Merges |
|---|---|---|---|
| 1 | `Auth::guard('sanctum')` | — | normal `$request->user()` |
| 2 | `test_sessions` | **SHA-256** of `session_token` (was plaintext; migrated `tcv-backend-codefix`) | `test_session_id`, `test_invitation_id`, `session_token` |
| 3 | `lms_sessions` | **SHA-256** of the token | `lms_session_id`, `org_session_id`, `org_id`, `patient_id`, `unique_test_id`, `$request->attributes['lmsSession']` |
| 4 | `organization_patient_sessions` | **SHA-256** of `token` (was plaintext; migrated `tcv-backend-codefix`) | `org_session_id`, `org_id`, `patient_id`, `test_id`, `org_session_token` |

The token is read from `Authorization: Bearer` **or** the `X-Session-Token` header.

**It also now publishes an unforgeable `auth_context` request attribute** (`tcv-backend-codefix`,
2026-09-02) — `FlexibleAuthMiddleware::context($request)` returns
`['tier', 'user_id', 'org_id', 'patient_id', 'test_invitation_id', 'test_session_id']`, set on
`$request->attributes` (never reachable by client input, unlike the *Merges* column above). Full detail:
[CONTEXT/AUTH_CONTEXT.md](CONTEXT/AUTH_CONTEXT.md#-auth_context--the-only-trustworthy-answer-to-who-is-calling-2026-09-02).

**Two things to know:**
- It restricts *which record* the caller may act on **only where a controller opts in** by calling
  `FlexibleAuthMiddleware::context()` — `PatientController`, `TestController` (`assignTest`,
  `getActiveTest`, certificate download) and `TestResumeController` now do this
  ([S-02](SECURITY.md#s-02--test-session-endpoints-never-check-that-the-caller-owns-the-test),
  [S-03](SECURITY.md#s-03--sendresumeemail-mails-a-resume-link-for-any-test-to-any-address),
  [S-14](SECURITY.md#s-14--patientsid-showupdatedestroy-have-no-ownership-scoping)). Five
  `unique_test_id`-keyed endpoints still don't and remain unscoped — see S-02's table.
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
   remember `RestrictIpMiddleware` already pays a DB round-trip there.
4. Re-run the generator: the middleware column in
   [INDEXES/API_ENDPOINT_INDEX.md](INDEXES/API_ENDPOINT_INDEX.md) is derived from the group stack, so it
   picks the change up automatically.
