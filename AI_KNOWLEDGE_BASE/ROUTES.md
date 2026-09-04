# Routes

`routes/api.php` — **158 endpoints** as of `tcv-backend-codefix` (2026-09-04; was 176 before the
`Route::resource` → `apiResource` cleanup below removed unreachable `create`/`edit` form routes).
Authorisation here is expressed by a route's **physical position** inside one
of two group blocks. Use [INDEXES/API_ENDPOINT_INDEX.md](INDEXES/API_ENDPOINT_INDEX.md) to answer
"is this guarded?", not the route's own line.

## The three zones

```php
// ── Zone 1: top of file, NO middleware ───────────────── 15 endpoints, fully public
Route::post('/login', …);  Route::post('/register', …);  Route::post('/password/forgot', …);  …

// ── Zone 2: session-token routes ─────────────────────── ~22 endpoints
Route::middleware('FlexibleAuthMiddleware')->group(function () { … });

// ── Zone 3: Sanctum-only routes ──────────────────────── ~121 endpoints
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

`auth:sanctum`, `signed` and `throttle` are Laravel's own. `EnsureTokenIsValid` — never aliased, never
routed — was confirmed dead and **deleted** in `tcv-backend-codefix` ([ARCHITECTURE_REALITY.md](ARCHITECTURE_REALITY.md)).

---

## Zone 1 — what is public

15 `api/*` endpoints plus both web routes. The full list is in
[INDEXES/PUBLIC_ROUTE_AUDIT.md](INDEXES/PUBLIC_ROUTE_AUDIT.md). Group them by *why*:

| Why it's public | Endpoints |
|---|---|
| Precedes a token, legitimately | `login`, `register`, `password/forgot`, `password/reset`, `password/verify-setup-token`, `verify-email-token`, `resend-verification-by-token`, `resend_email_verification_link`, `validate-token`, `countries-with-states` |
| Authenticates by its own emailed/embedded token | `test-invitation/verify-code`, `test-invitation/check-validity`, `test/resume`, `organization/verify-signature` |
| Leftover | `reset-password/{token}` (a closure echoing the token back) |

✅ **The five `stripe/*` routes are no longer public.** `tcv-backend-codefix` moved them into the
`auth:sanctum` group (they were previously registered at the top of the file, alongside genuinely public
routes like `/login`, despite every handler calling `Auth::user()` with no null check — an
unauthenticated caller got a raw 500 instead of a 401). See [BILLING_CONTEXT](CONTEXT/BILLING_CONTEXT.md).

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

Laravel matches the **first** registered route. Two places used to get this wrong; both were fixed in
`tcv-backend-codefix` (2026-09-04) — read as history, and as the shape to follow for new routes.

### 1. `credits/{coupon-code}` was unreachable — ✅ fixed

```php
// before
Route::resource('credits', CreditsController::class);                     // → GET credits/{credit}
Route::get('credits/{coupon-code}', [CreditsController::class, 'checkDiscountCodeValidity']);
```
Two independent bugs stacked here. `GET api/credits/anything` always matched `CreditsController@show`
first, since `credits/{coupon-code}` was registered second at the exact same shape. Separately,
`{coupon-code}` is not a valid Symfony route parameter name (hyphens aren't allowed in a `{param}`), so
it compiled as a *literal* path segment — the route only ever matched the literal string
`GET credits/coupon-code`, not any real coupon. The fix moved the endpoint under its own prefix, renamed
the parameter, and switched the resource to `apiResource`:
```php
Route::apiResource('credits', CreditsController::class);
Route::get('credits/coupon/{coupon_code}', [CreditsController::class, 'checkDiscountCodeValidity'])
    ->name('credits.checkDiscountCodeValidity');
```
`CreditsController::checkDiscountCodeValidity()` also had to change signature — it used to call
`$request->validate(['coupon_code' => …])`, but `Request::validate()` only sees query + body, never
route parameters, so every call 422'd before the handler ran. It now validates the route parameter
directly. See [CONTEXT/DISCOUNT_CONTEXT.md](CONTEXT/DISCOUNT_CONTEXT.md).

### 2. `restricted-ips` was registered twice — ✅ fixed

```php
// before, both present
Route::prefix('restricted-ips')->group(function () {
    Route::apiResource('/', RestrictedIpController::class)->except(['show']);   // malformed
});
…
Route::apiResource('restricted-ips', RestrictedIpController::class)->except(['show']);
```
The first form passed `'/'` as the resource name, producing a degenerate parameter name. Both mapped to
the same controller, so behaviour was unaffected — but the duplicate made `route:list` confusing and the
two registrations could drift. The `prefix` block was deleted; only the second registration remains.

### 3. Ordering that is correct — do not "tidy" it
```php
Route::put('users/change-password', …);                       // before the resource — deliberate
Route::apiResource('users', UserController::class)->except(['show']);
Route::get('users/{id}', [UserController::class, 'edit'])->name('users.show');   // show is excluded above
Route::get('discount-codes/stats', …);  Route::get('discount-codes/form-options', …);
Route::post('discount-codes/validate', …);  Route::patch('discount-codes/{discount_code}/toggle', …);
Route::apiResource('discount-codes', DiscountCodeController::class);   // literals first — correct
```

### `Route::resource` → `Route::apiResource`, throughout

Every `Route::resource(...)` call in `routes/api.php` (`patients`, `users`, `tests`, `conditions`,
`answers`, `sections`, `section/plates`, `credits`) became `Route::apiResource(...)` in the same pass.
This is a **pure JSON API** — none of these controllers implement `create()`/`edit()` (except
`UserController`, kept and re-exposed as `GET users/{id}` above, per the block that must not be
"tidied"). `resource()` additionally registers `GET .../create` and `GET .../{id}/edit`, neither backed
by a real form page, so both would 500 if ever hit. This is also *why* the total endpoint count dropped
from 176 to 158: those unreachable form routes are gone.

---

## Conventions for a new route

1. **Pick the zone first.** Default to Zone 3 (`auth:sanctum`). Only use Zone 2 if an unauthenticated
   patient must reach it with a session token. Only use Zone 1 if it genuinely precedes any credential.
2. **Literal segments before parameterised ones** within the same prefix.
3. **Name the route** if the SPA or a notification links to it (`->name('…')`).
4. **Throttle anything that sends mail or costs money.** `POST api/contact` (`throttle:10,1`) and, since
   `tcv-backend-codefix`, six more: `login`, `register`, `password/forgot`/`reset`/`verify-setup-token`
   (`throttle:password-reset`), `organization/verify-signature` (`throttle:signature-verify`),
   `test-invitations/send` (`throttle:bulk-invitations`) and the plate-url endpoint
   (`throttle:plate-url`, keyed on the session token, not IP). ☠️ The five ip-keyed ones currently share
   **one global bucket** across every client — see
   [S-16](SECURITY.md#s-16--every-client-shares-one-ip-rate-limits-and-ip-restriction-are-both-inert)
   before treating this as fully solved. The resend endpoints (`resend_email_verification_link`,
   `resend-verification-by-token`) still have none.
5. **Re-run the generator** and check the diff of `PUBLIC_ROUTE_AUDIT.md`
   ([GUIDES/HOW_TO_REGENERATE.md](GUIDES/HOW_TO_REGENERATE.md)).

## How the index is built

TCV-Backend does not vendor its dependencies, so `php artisan route:list` cannot boot from a clean
clone. `tools/extract.php` walks `routes/api.php` and `routes/web.php` with the AST instead, tracking
the group stack (prefix + middleware) and expanding `Route::resource` / `apiResource` including
`->only()` / `->except()`. The method actually used is recorded in `facts.json` → `routes_source` and
printed at the top of the index. If you run `composer install` inside TCV-Backend, the extractor
switches to `artisan route:list --json` automatically — prefer that when available.
