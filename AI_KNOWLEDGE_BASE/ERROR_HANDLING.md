# Error Handling

> **The single most consequential file in the repo for debugging.**
> [`app/Exceptions/Handler.php`](../../TCV-Backend/app/Exceptions/Handler.php) is bound explicitly via
> `bootstrap/app.php` → `->withBindings([ExceptionHandler::class => Handler::class])`.

## What the handler actually does

```php
public function render($request, Throwable $exception)
{
    if ($exception instanceof AuthenticationException)  return json(['success'=>false,'message'=>'Unauthenticated.'], 401);
    if ($exception instanceof ValidationException)      return json([... 'errors'=>$exception->errors()], 422);

    if ($request->expectsJson()) {                       // ← EVERYTHING else lands here
        logger()->error($exception);
        if (app()->environment('production')) return json([... 'Something went wrong…'], 500);
        return json(['success'=>false,'message'=>$exception->getMessage(),
                     'trace'=>config('app.debug') ? $exception->getTrace() : []], 500);
    }

    return parent::render($request, $exception);
}
```

Two exception types are handled. **Everything else becomes a 500.**

### The status codes that get thrown away

| Thrown | Correct status | What the client actually receives |
|---|---|---|
| `ModelNotFoundException` (`findOrFail`, `firstOrFail`) | 404 | **500** |
| `AuthorizationException` (`$this->authorize()` fails) | 403 | **500** |
| `NotFoundHttpException` (unmatched route / failed model binding) | 404 | **500** |
| `MethodNotAllowedHttpException` | 405 | **500** |
| `ThrottleRequestsException` (`throttle:10,1`) | 429 | **500** |
| any `HttpException` a package throws | its own | **500** |

`findOrFail` and `firstOrFail` are used throughout — `TestExecutionService`, `CreditsController`,
`PatientController`, `TestInvitationController`. **A missing record presents as a server error.**

Likewise, all ~20 `$this->authorize()` call sites (`TestController`, `TestConditionController`,
`TestSectionController`, `OrganizationController`, `CreditsController`) return **500 on denial**, not
403. That is why the SPA's error handling cannot distinguish "you may not do this" from "the server
broke".

### Consequences you will actually hit

- **Debugging:** a 500 in the logs means nothing on its own. Read the logged exception class before
  assuming a crash — most 500s in this app are 404s and 403s in disguise.
- **The SPA cannot branch on status.** `src/services/errorHandler.js` shows a generic popup for 500s;
  there is no way for it to render "not found" correctly.
- **Rate limiting is invisible.** A throttled `POST api/contact` returns 500, so a client cannot back off.

### The information leak
Outside `production` the response carries `$exception->getMessage()`, plus the **entire stack trace**
when `app.debug` is on. Shared dev/QA environments therefore expose internals to anyone who can reach
them. [S-12](SECURITY.md#s-12--non-production-error-responses-leak-messages-and-stack-traces).

---

## The pattern controllers use instead

Because the handler is unhelpful, controllers catch their own exceptions and return an explicit status:

```php
try {
    $result = $this->executionService->submitAnswer(…);
    return ApiResponse::success(HttpStatus::OK, 'api.answer_submit_success', […]);
} catch (\Exception $e) {
    Log::error('Error submitting answer.', ['error' => $e->getMessage()]);
    return ApiResponse::error(HttpStatus::SERVER_ERROR, 'api.answer_submit_failed');
}
```

**Follow this.** An explicit `ApiResponse::error(HttpStatus::NOT_FOUND, …)` is the only reliable way to
return a 404 from this codebase today.

☠️ But note what the pattern costs: the `catch (\Exception)` also swallows genuine bugs into a generic
`api.*_failed` message. When a feature "just returns an error", the real cause is in the log line above
the response, not in the response.

---

## Response shapes

| Source | Shape |
|---|---|
| `ApiResponse::success()` | `{success, status_code, message, data?}` |
| `ApiResponse::error()` | `{success, status_code, message, errors?}` |
| `Handler` (auth) | `{success, message}` — **no `status_code`** |
| `Handler` (validation) | `{success, message, errors}` — **no `status_code`** |
| `AuthController::login()` | `{access_token, token_type, user}` or `{status, message, has_existing_token}` |
| `AuthController::isTokenValid()` | `{valid, …}` |
| `FlexibleAuthMiddleware` | `{success, message, error_type?}` |
| `LmsSessionStatusMiddleware` | `{success, message, code, current_status, allowed}` |
| `RestrictIpMiddleware` | `{success, error_code, message}` |

Eight shapes. A client cannot key on one field. The SPA copes by checking several
(`error.response?.data?.error_code`, `?.message`, `?.error_type`). **New code should use `ApiResponse`**
— do not add a ninth.

Message keys resolve through `__()` against `resources/lang/en/api.php` (78 keys). A missing key renders
as the raw key string, which is how `api.resticted` (sic) is visible in responses today.

---

## If you change the handler

The obvious improvement is to let `HttpExceptionInterface` carry its own status:

```php
if ($exception instanceof HttpExceptionInterface) {
    return response()->json([...], $exception->getStatusCode());
}
```

That is a **breaking change for the SPA**, which currently treats 403/404 paths as 500s and has
work-arounds built on that. Coordinate it with `src/services/errorHandler.js` and the slices that pass
`skipErrorPopup` ([FRONTEND.md](FRONTEND.md)), and check
[CHANGE_IMPACT_GUIDE.md](CHANGE_IMPACT_GUIDE.md) first.
