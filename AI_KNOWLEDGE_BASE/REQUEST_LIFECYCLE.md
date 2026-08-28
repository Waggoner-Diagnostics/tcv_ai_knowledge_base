# Request Lifecycle

What happens between nginx and a JSON response. Knowing the order is what lets you predict *which* layer
produced an error.

```
1.  nginx (backend-nginx-tcv)            → PHP-FPM at backend-tcv:9000
2.  public/index.php  →  bootstrap/app.php
3.  Global middleware:  HandleCors  →  RestrictIpMiddleware      ← DB query, EVERY request
4.  Route matching against the CACHED route table (route:cache runs at boot)
5.  Group middleware:  'api'  +  auth:sanctum | FlexibleAuthMiddleware | (none)
6.  Route middleware:  lms.status:… · signed · throttle:…
7.  FormRequest      → authorize() then rules()      (24 of them; throws ValidationException)
8.  Controller       → Service → Eloquent → MySQL
9.  Response         → ApiResponse::success/error, or a hand-built array
10. Exceptions       → App\Exceptions\Handler
```

## Step 3 — the global middleware pays a DB round-trip

`RestrictIpMiddleware` is appended in `bootstrap/app.php` and runs **before routing**. It queries
`restricted_ips` on every request — including requests to routes that do not exist. Only `api/logout` is
exempt. This is the app's latency floor and a hard DB dependency ahead of everything else
([MIDDLEWARE.md](MIDDLEWARE.md)).

## Step 4 — routes are cached at boot

`entrypoint.sh` runs `php artisan route:cache`. **A route change needs a container restart**, not just a
file change. Locally, `php artisan route:clear` after editing `routes/api.php`.

## Step 5 — which guard, and what it proves

| Group | Proves | Does **not** prove |
|---|---|---|
| none (21 endpoints) | nothing | — |
| `auth:sanctum` (133) | a valid, unexpired 15-minute token | any role or ownership |
| `FlexibleAuthMiddleware` (23) | one of four session kinds is valid | **which** patient/test/org it is for |

`FlexibleAuthMiddleware` merges identifiers into the request (`test_session_id`, `org_id`, `patient_id`,
`unique_test_id`, …) and sets `$request->attributes['lmsSession']` for tier 3 only. Controllers largely
ignore those merged values and read the URL instead — the root of
[S-02](SECURITY.md#s-02--test-session-endpoints-never-check-that-the-caller-owns-the-test) and
[S-14](SECURITY.md#s-14--patientsid-showupdatedestroy-have-no-ownership-scoping).

## Step 6 — conditional gates

`lms.status:a,b` returns **409 `SESSION_STATUS_MISMATCH`** only when an `LmsSession` is attached. On any
other tier it is a pass-through.

`throttle` appears exactly once (`POST api/contact`, `throttle:10,1`). A throttled request raises
`ThrottleRequestsException`, which the handler turns into a **500** — so clients cannot back off
correctly.

## Step 7 — validation

Two styles coexist:

| Style | Where | On failure |
|---|---|---|
| FormRequest (24 classes) | most controllers | `ValidationException` → **422** with `errors` |
| Inline `$request->validate([...])` | `AuthController`, `TestInvitationController`, `TestResumeController`, `CreditsController` | same 422 |

Both end at the same place. Validation is the **only** non-auth error type the handler treats properly.

☠️ Every FormRequest's `authorize()` returns `true`. They validate shape, never permission —
`UserRequest` is the case that matters ([S-01](SECURITY.md#s-01--public-registration-accepts-usertype--1)).

## Step 8 — controller → service

The dominant shape:

```php
try {
    $result = $this->someService->doTheThing($validated);
    return ApiResponse::success(HttpStatus::OK, 'api.some_key', $result);
} catch (\Exception $e) {
    Log::error('…', ['error' => $e->getMessage()]);
    return ApiResponse::error(HttpStatus::SERVER_ERROR, 'api.some_failure_key');
}
```

Consequence: **the useful error is in the log line, not the response.** When something "just fails",
read `storage/logs/laravel.log` around the request, not the JSON.

## Step 9 — the response

`ApiResponse::success/error` produce `{success, status_code, message, data?|errors?}`, with `message`
resolved through `__()` against `resources/lang/en/api.php` (78 keys). A missing key renders as the raw
key — which is how `api.resticted` (sic) reaches clients today.

Eight different response shapes exist across the app; the full table is in
[ERROR_HANDLING.md](ERROR_HANDLING.md#response-shapes).

## Step 10 — the handler collapses statuses

`AuthenticationException` → 401, `ValidationException` → 422, **everything else → 500**. `findOrFail`
404s and `authorize()` 403s all arrive as 500. This is the single most important thing to know when
reading a failing request ([ERROR_HANDLING.md](ERROR_HANDLING.md)).

---

## Side effects worth knowing about

| Trigger | Side effect |
|---|---|
| **every** successful `POST api/login` | `StripeService::createOrGetCustomer()` — a Stripe API call, failures swallowed into a log |
| `POST api/login` with an unverified email | sends (or re-sends) a verification email, then returns 401 |
| `setOrResetPassword(type=setup)` | fires `UserPasswordSet` → may mail an org Test URL |
| final answer of a test | `TestCompleted` → LMS delivery enqueued **after** the transaction commits |
| any request from a listed IP | 401/403 before routing |

## Tracing a request end to end

1. [INDEXES/API_ENDPOINT_INDEX.md](INDEXES/API_ENDPOINT_INDEX.md) — the ID, guard and controller action.
2. [INDEXES/PUBLIC_ROUTE_AUDIT.md](INDEXES/PUBLIC_ROUTE_AUDIT.md) — is it reachable with no token?
3. [INDEXES/METHOD_INDEX.md](INDEXES/METHOD_INDEX.md) — the exact `file:line` of the action.
4. The matching context pack — not the module.

Worked example: [GUIDES/HOW_TO_TRACE_API.md](GUIDES/HOW_TO_TRACE_API.md).
