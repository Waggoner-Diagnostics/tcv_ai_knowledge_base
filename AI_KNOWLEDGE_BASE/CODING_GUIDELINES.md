# Coding Guidelines

The conventions this codebase actually follows, the places it doesn't, and the traps that have real
consequences. Match the surrounding code; introduce no new layers.

## The house style (backend)

```php
public function performTest(PerformTestRequest $request)          // 1. FormRequest
{
    $validated = $request->validated();                            // 2. validated(), never all()
    \DB::beginTransaction();
    try {
        $result = $this->executionService->submitAnswer(...);      // 3. delegate to a Service
        \DB::commit();
        return ApiResponse::success(HttpStatus::OK, 'api.answer_submit_success', [...]);  // 4. envelope
    } catch (\Exception $e) {
        \DB::rollBack();
        Log::error('Error submitting answer.', ['error' => $e->getMessage()]);
        return ApiResponse::error(HttpStatus::SERVER_ERROR, 'api.answer_submit_failed');
    }
}
```

1. **Validate in a FormRequest** — 24 exist ([REQUESTS.md](REQUESTS.md)).
2. **`$request->validated()`, never `$request->all()`.** `PatientController::update()` shows the
   consequence ([S-14](SECURITY.md#s-14--patientsid-showupdatedestroy-have-no-ownership-scoping)).
3. **Business logic in a Service** ([SERVICES.md](SERVICES.md)). Constructor-inject it.
4. **`ApiResponse` + `HttpStatus` + a key from `resources/lang/en/api.php`.** Add the key.

## Do

- Scope owner-bound queries yourself: `->where('user_id', auth()->id())`. Nothing does it for you.
- Read config through `config('...')`, never `env()` outside `config/` — config is cached at boot.
- Use `Str::uuid()` / `Str::random(n)` / `random_bytes()` for tokens. Never `uniqid()` or `rand()`.
- Hash anything you store as a credential (`hash('sha256', $raw)`) — the LMS session token is the
  example done right.
- Lock rows you are about to mutate under contention: `->lockForUpdate()` inside `DB::transaction`.
- Eager-load: `->with(['patient', 'test'])`. The plate loop is N+1-prone without it.
- Reuse the `Searchable` trait for `?search=`.
- Truncate secrets in logs: `substr($token, 0, 10) . '...'`.

## Don't

- **Don't add a global helper function.** There are none, and there is no `autoload.files` entry
  ([HELPERS.md](HELPERS.md)).
- **Don't add a repository.** There is one, and it is not a pattern ([REPOSITORIES.md](REPOSITORIES.md)).
- **Don't register listeners in `EventServiceProvider`.** It is not loaded ([EVENTS.md](EVENTS.md)).
- **Don't add a fifth mail mechanism** or a ninth response shape
  ([ARCHITECTURE_REALITY.md](ARCHITECTURE_REALITY.md)).
- **Don't rely on `authorize()` producing a 403** — return the status explicitly.
- **Don't log tokens, passwords, or `$request->all()`** ([LOGGING.md](LOGGING.md)).
- **Don't trust an id from the request** when the session already implies it
  ([SECURITY.md](SECURITY.md)).
- **Don't run `vendor/bin/pint` unflagged** — use `pint --dirty`.

## ☠️ Traps that have real consequences

**1. `Credits::getAvailableCredits()` returns `int|string`.**
```php
$credit = Credits::getAvailableCredits($userId);
if ($credit !== 'Unlimited' && $credit < 1) { /* insufficient */ }   // ← the guard is mandatory
```
Without the `!==` check, `'Unlimited'` coerces to `0` and an unlimited customer is blocked.

**2. `usertype` skips 3.** `1`, `2`, `4`. Never `range()`, never `< 5`.

**3. `account_status` and `email_verified` are strings.** `'active'`, `'yes'` — not booleans, not ints.

**4. `lms.status:` is conditional.** It gates only sessions that carry an `LmsSession`
([MIDDLEWARE.md](MIDDLEWARE.md)).

**5. Nested transactions are savepoints.** `performTest()` wraps `finalizeTestIfCompleted()`'s own
transaction; an outer rollback discards the inner commit **after** `TestCompleted` has fired
([EVENTS.md](EVENTS.md)).

**6. Static state survives the request in a long-lived runtime.** `PaymentManager::$initialized`
latches ([SERVICES.md](SERVICES.md)). Avoid static mutable state.

**7. Table names don't follow the model.** `testanswers`, `credit_consume`, `email_template`,
`lms_delivery_queue` ([DATABASE.md](DATABASE.md)).

**8. Free text is load-bearing.** `TestHelper::extractEyeFromSectionInstruction()` regexes
`Eye: (OU|OD|OS)` out of an authored section instruction.

**9. `'N/A'` is a sentinel.** `PatientController::index()` computes `is_prolific` from
`empty($p->first_name) || $p->first_name === 'N/A'`.

## Naming

| Thing | Convention | Exceptions |
|---|---|---|
| Controllers | `XController`, one per resource | `AuthController` covers five concerns |
| Services | `XService`, or a role name (`PaymentManager`, `LmsProviderRegistry`) | — |
| FormRequests | `XRequest` / `StoreXRequest` / `UpdateXRequest` | inconsistent between the two forms |
| Models | singular | **`Credits`** (plural, and a duplicate `Credit` exists) |
| Constants | `SCREAMING_SNAKE` on the model / a `Support` class | LMS statuses are bare strings in `routes/api.php` |
| Message keys | `api.snake_case` | `api.resticted` is typo'd and shipped |

## Frontend

- Adding a page = **3 files** (`protectedRoutes.js`, `routeConfig.js`, `USER_PANEL_WITH_HEADER`)
  ([FRONTEND.md](FRONTEND.md)).
- Lazy imports via `lazyWithRetry`.
- Prefer `createPaginatedCrudSlice` over `createCrudSlice`.
- Import shared UI from `src/components/index.js`.
- `eslint src --max-warnings 0` — one new warning fails the lint.

## Website

- `/app` = Server Components. `/views` = `'use client'`. Never the reverse.
- Import via `@/`, never `../..`.
- Theme colours are **inline styles**; Tailwind cannot compile a runtime value.
- Use **yarn**.
