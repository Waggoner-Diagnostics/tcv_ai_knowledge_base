# How to Add a New Feature

Read this, plus **one** context pack for the area you're touching. Nothing else.

## 1. Decide the guard zone first

`routes/api.php` has three zones and the choice is the security decision:

| Zone | Use when |
|---|---|
| `auth:sanctum` (**default**) | an admin, customer or org user calls it |
| `FlexibleAuthMiddleware` | an unauthenticated **patient** must reach it with a session token |
| none | it genuinely precedes any credential (login, a token-exchange endpoint) |

If you pick zone 1 or 2, ask the second question immediately: **does the route need an ownership
check?** Middleware proves the caller has *a* credential, never *which record* they may touch. Scope the
query yourself — this is the single most common defect in the codebase
([SECURITY.md](../SECURITY.md)).

## 2. Write the FormRequest

```php
php artisan make:request DoTheThingRequest
```

- `authorize()` returns `true` **only** because the route's middleware already established who may call
  it. If the route is public, the rules themselves must not accept privileged values —
  `UserRequest` is the cautionary tale ([S-01](../SECURITY.md#s-01--public-registration-accepts-usertype--1)).
- Put domain constants and derived flags on the request class
  (`PerformTestRequest::isAutoSubmit()` is the model).

## 3. Put the logic in a Service

```php
// app/Services/DoTheThingService.php
class DoTheThingService
{
    public function __construct(private OtherService $other) {}

    public function handle(array $validated): array { … }   // throw on failure
}
```

Constructor-inject it into the controller. Bind it in a provider **only** if it holds state — and if you
create a provider, add it to `bootstrap/providers.php`, the file everyone forgets
([CONFIGURATION.md](../CONFIGURATION.md)).

## 4. Write the controller action

```php
public function doTheThing(DoTheThingRequest $request)
{
    try {
        $result = $this->service->handle($request->validated());
        return ApiResponse::success(HttpStatus::OK, 'api.do_the_thing_success', $result);
    } catch (\Exception $e) {
        Log::error('DoTheThing failed', ['error' => $e->getMessage()]);
        return ApiResponse::error(HttpStatus::SERVER_ERROR, 'api.do_the_thing_failed');
    }
}
```

Add both message keys to `resources/lang/en/api.php` — a missing key renders as the literal key string.

Return **explicit** statuses for expected failures (`HttpStatus::NOT_FOUND`, `::FORBIDDEN`). Do not rely
on an exception carrying its status: the handler turns everything into a 500
([ERROR_HANDLING.md](../ERROR_HANDLING.md)).

## 5. Register the route

```php
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/do-the-thing', [ThingController::class, 'doTheThing'])->name('thing.do');
});
```

- **Literal segments before parameterised ones** in the same prefix — `credits/{coupon-code}` is already
  dead because of this ([ROUTES.md](../ROUTES.md#ordering-traps)).
- **Throttle anything that sends mail or costs money.** Only one route in the whole app does today.
- Routes are cached at boot: `php artisan route:clear` locally.

## 6. Schema, if needed

```php
php artisan make:migration add_x_to_y_table
```

- Check [INDEXES/DATABASE_TABLE_INDEX.md](../INDEXES/DATABASE_TABLE_INDEX.md) first — it is a **union
  across migrations**, so confirm with `DESCRIBE` before assuming a column's current state.
- Match the neighbours on FKs: the LMS and discount tables declare them; the older test tables largely
  don't.
- `Schema::defaultStringLength(191)` is set globally — a `string` column is 191 chars.

## 7. The client side

If the SPA calls it ([FRONTEND.md](../FRONTEND.md)):
- add the thunk/slice (prefer `createPaginatedCrudSlice` for paginated lists);
- if it needs a **page**, that is **three** files: `protectedRoutes.js`, `routeConfig.js`, and
  `USER_PANEL_WITH_HEADER` in `Router.js`;
- if it is an unauthenticated patient path, also add the prefix to `isPublicRoute()` in
  `AxiosInstance.js` — otherwise the first 401 destroys the session.

## 8. Verify

```bash
php -l <changed files>
php artisan test                # real coverage exists ONLY for LMS + credit history
composer lint                   # pint --test
```

Be honest about coverage: if your change isn't LMS or credit history, the suite does not exercise it.
Say so rather than reporting "tests pass".

## 9. Update the KB

```bash
cd tcv-ai-knowledge-base
php tools/extract.php && php tools/extract-clients.php && php tools/render.php && php tools/verify.php
```

Then **diff** `PUBLIC_ROUTE_AUDIT.md`, `CONTRACT_DRIFT.md` and `FRONTEND_ROUTE_INDEX.md`, and
hand-update only the affected prose (the context pack, [FEATURE_INDEX.md](../FEATURE_INDEX.md), and
[CHANGE_IMPACT_GUIDE.md](../CHANGE_IMPACT_GUIDE.md) if you added a shared symbol).
**Never regenerate the whole KB.**

---

## Checklist

- [ ] Guard zone chosen deliberately
- [ ] Ownership check written (or genuinely not needed)
- [ ] FormRequest, consumed with `validated()`
- [ ] Logic in a Service
- [ ] `ApiResponse` + `HttpStatus` + lang keys added
- [ ] Route ordering safe; throttled if it mails or charges
- [ ] `PUBLIC_ROUTE_AUDIT.md` diff reviewed
- [ ] SPA side updated (3 files if it's a page)
- [ ] KB regenerated + prose updated
