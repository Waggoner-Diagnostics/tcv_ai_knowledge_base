# Middleware

Four classes exist in `app/Http/Middleware/`. **One is global, two are aliased, one is dead.**

| Class | ID | How it runs | Notes |
|---|---|---|---|
| `RestrictIpMiddleware` | `MW-004` | **appended globally** in `bootstrap/app.php` | Runs on every request |
| `FlexibleAuthMiddleware` | `MW-002` | alias `FlexibleAuthMiddleware` | The four-tier session gate |
| `LmsSessionStatusMiddleware` | `MW-003` | alias `lms.status` | Parameterised; conditional |
| `EnsureTokenIsValid` | `MW-001` | ❌ **never aliased, never routed** | Dead code — safe to delete |

Laravel's own `auth:sanctum`, `signed` and `throttle` are also used. `throttle` appears exactly once, on
`POST api/contact` (`throttle:10,1`).

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
| 2 | `test_sessions` | **plaintext** `session_token` | `test_session_id`, `test_invitation_id`, `session_token` |
| 3 | `lms_sessions` | **SHA-256** of the token | `lms_session_id`, `org_session_id`, `org_id`, `patient_id`, `unique_test_id`, `$request->attributes['lmsSession']` |
| 4 | `organization_patient_sessions` | plaintext `token` | `org_session_id`, `org_id`, `patient_id`, `test_id`, `org_session_token` |

The token is read from `Authorization: Bearer` **or** the `X-Session-Token` header.

**Two things it does not do:**
- It never restricts *which* record the caller may act on. Merging `patient_id` and `unique_test_id`
  into the request is informational; controllers read the URL instead
  ([S-02](SECURITY.md#s-02--test-session-endpoints-never-check-that-the-caller-owns-the-test)).
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
