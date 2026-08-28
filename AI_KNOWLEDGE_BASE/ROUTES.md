# Routes

`routes/api.php` is **15 KB, 264 lines** — small enough to read, which is exactly why people read it and
draw the wrong conclusion. Authorisation here is expressed by a route's **physical position** inside one
of two group blocks. Use [INDEXES/API_ENDPOINT_INDEX.md](INDEXES/API_ENDPOINT_INDEX.md) to answer
"is this guarded?", not the route's own line.

## The three zones

```php
// ── Zone 1: top of file, NO middleware ───────────────── 20 endpoints, fully public
Route::post('/login', …);  Route::post('/register', …);  Route::post('/password/forgot', …);  …

// ── Zone 2: session-token routes ─────────────────────── 23 endpoints
Route::middleware('FlexibleAuthMiddleware')->group(function () { … });

// ── Zone 3: Sanctum-only routes ──────────────────────── 132 endpoints
Route::middleware('auth:sanctum')->group(function () { … });
```

Plus `routes/web.php`: `GET /` (a view) and `GET /payment/callback` → `StripePaymentController@paymentCallback`.

**Every route additionally passes `RestrictIpMiddleware`**, appended globally in `bootstrap/app.php`
(`api/logout` is the one exemption). It is not shown per-route in the index.

### Middleware aliases (`bootstrap/app.php`)
| Alias | Class |
|---|---|
| `FlexibleAuthMiddleware` | `App\Http\Middleware\FlexibleAuthMiddleware` |
| `lms.status` | `App\Http\Middleware\LmsSessionStatusMiddleware` |

`auth:sanctum`, `signed` and `throttle` are Laravel's own. `EnsureTokenIsValid` is **not aliased and
never used** ([ARCHITECTURE_REALITY.md](ARCHITECTURE_REALITY.md)).

---

## Zone 1 — what is public

21 `api/*` endpoints plus both web routes. The full list is in
[INDEXES/PUBLIC_ROUTE_AUDIT.md](INDEXES/PUBLIC_ROUTE_AUDIT.md). Group them by *why*:

| Why it's public | Endpoints |
|---|---|
| Precedes a token, legitimately | `login`, `register`, `password/forgot`, `password/reset`, `password/verify-setup-token`, `verify-email-token`, `resend-verification-by-token`, `resend_email_verification_link`, `validate-token`, `countries-with-states` |
| Authenticates by its own emailed/embedded token | `test-invitation/verify-code`, `test-invitation/check-validity`, `test/resume`, `organization/verify-signature` |
| ⚠️ Public but broken — every handler needs `Auth::user()` | all five `stripe/*` routes ([BILLING_CONTEXT](CONTEXT/BILLING_CONTEXT.md)) |
| Leftover | `reset-password/{token}` (a closure echoing the token back) |

**Re-read the public audit after every route change.** A route added at the top of the file, or after the
closing `});` of a group, is public with no warning.

---

## Zone 2 — `FlexibleAuthMiddleware`

Accepts **four** token kinds ([AUTH_CONTEXT](CONTEXT/AUTH_CONTEXT.md)). Anything in this group is
reachable by a Sanctum user *and* by an invitation session *and* by an LMS session *and* by a legacy org
session. That breadth is the point — and the reason `patients` being in this group is a problem
([S-14](SECURITY.md#s-14--patientsid-showupdatedestroy-have-no-ownership-scoping)).

Several routes here add `->middleware('lms.status:…')`. Read that as *"if this is an LMS session it must
be in state X"* — it does nothing for the other three tiers ([MIDDLEWARE.md](MIDDLEWARE.md)).

---

## Ordering traps

Laravel matches the **first** registered route. Two places get this wrong:

### 1. `credits/{coupon-code}` is unreachable
```php
Route::resource('credits', CreditsController::class);                     // line 175 → GET credits/{credit}
Route::get('credits/{coupon-code}', [CreditsController::class, 'checkDiscountCodeValidity']);  // line 176
```
`GET api/credits/anything` always hits `CreditsController@show`. `checkDiscountCodeValidity()` is dead
code. Fix by moving the literal route **above** the resource, the way the `discount-codes` block
correctly does.

### 2. `restricted-ips` is registered twice
```php
Route::prefix('restricted-ips')->group(function () {
    Route::apiResource('/', RestrictedIpController::class)->except(['show']);   // line 180 — malformed
});
…
Route::apiResource('restricted-ips', RestrictedIpController::class)->except(['show']);  // line 206
```
The first form passes `'/'` as the resource name, which produces a degenerate parameter name. Both map
to the same controller, so behaviour is unaffected — but the duplicate makes `route:list` confusing and
the two registrations can drift. Delete the `prefix` block; keep line 206.

### 3. Ordering that is correct — do not "tidy" it
```php
Route::put('users/change-password', …);                       // before the resource — deliberate
Route::resource('users', UserController::class)->except(['show']);
Route::get('users/{id}', [UserController::class, 'edit'])->name('users.show');   // show is excluded above
Route::get('discount-codes/stats', …);  Route::get('discount-codes/form-options', …);
Route::post('discount-codes/validate', …);  Route::patch('discount-codes/{discount_code}/toggle', …);
Route::apiResource('discount-codes', DiscountCodeController::class);   // literals first — correct
```

---

## Conventions for a new route

1. **Pick the zone first.** Default to Zone 3 (`auth:sanctum`). Only use Zone 2 if an unauthenticated
   patient must reach it with a session token. Only use Zone 1 if it genuinely precedes any credential.
2. **Literal segments before parameterised ones** within the same prefix.
3. **Name the route** if the SPA or a notification links to it (`->name('…')`).
4. **Throttle anything that sends mail or costs money.** Today only `POST api/contact` has
   `throttle:10,1` — `test-invitations/send` (≤500 emails, now `auth:sanctum` but still unthrottled),
   `password/forgot` and the resend endpoints have none.
5. **Re-run the generator** and check the diff of `PUBLIC_ROUTE_AUDIT.md`
   ([GUIDES/HOW_TO_REGENERATE.md](GUIDES/HOW_TO_REGENERATE.md)).

## How the index is built

TCV-Backend does not vendor its dependencies, so `php artisan route:list` cannot boot from a clean
clone. `tools/extract.php` walks `routes/api.php` and `routes/web.php` with the AST instead, tracking
the group stack (prefix + middleware) and expanding `Route::resource` / `apiResource` including
`->only()` / `->except()`. The method actually used is recorded in `facts.json` → `routes_source` and
printed at the top of the index. If you run `composer install` inside TCV-Backend, the extractor
switches to `artisan route:list --json` automatically — prefer that when available.
