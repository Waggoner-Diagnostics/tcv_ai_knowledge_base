# Controllers

**34 classes under `app/Http/Controllers/`** (including the base `Controller` and the 6 unused
`Auth/` scaffolding classes). Full list with line numbers:
[INDEXES/CLASS_INDEX.md](INDEXES/CLASS_INDEX.md) (`CTRL-nnn`).

## Size — where the weight is

| Controller | Lines | What lives there |
|---|---|---|
| `OrganizationController` | 876 | CRUD, logo upload, **`verifySignature()`**, patient-form config, privileges |
| `AuthController` | 775 | login, register, verify ×2, password set/reset, impersonation, token validation |
| `TestController` | 679 | the whole test surface; mostly thin over the services |
| `TestInvitationController` | 559 | send / verify / resend / cancel |
| `PatientController` | 432 | CRUD + `getPatientTests` + `resendTestLink` |
| `StripePaymentController` | 410 | the **deprecated** payment surface |
| `UserController` | 361 | user management + credit aggregation |
| … 27 more | < 320 each | |

The three big ones are the ones to read via [INDEXES/METHOD_INDEX.md](INDEXES/METHOD_INDEX.md) rather
than opening.

## The house style

```php
public function performTest(PerformTestRequest $request)      // ← FormRequest, not inline validation
{
    $validated = $request->validated();
    \DB::beginTransaction();
    try {
        $result = $this->executionService->submitAnswer(...);  // ← delegate
        \DB::commit();
        return ApiResponse::success(HttpStatus::OK, 'api.answer_submit_success', [...]);
    } catch (\Exception $e) {
        \DB::rollBack();
        Log::error('Error submitting answer.', ['error' => $e->getMessage()]);
        return ApiResponse::error(HttpStatus::SERVER_ERROR, 'api.answer_submit_failed');
    }
}
```

Four elements, all worth copying: **FormRequest → transaction → service call → `ApiResponse`**.

## Where the style is not followed

| Deviation | Where |
|---|---|
| Inline `$request->validate([...])` instead of a FormRequest | `AuthController` (throughout), `TestInvitationController`, `TestResumeController`, `CreditsController::checkDiscountCodeValidity` |
| Hand-built response arrays instead of `ApiResponse` | `AuthController::login/isTokenValid/verifyEmail*`, `TestResumeController`, `PatientController`, `CreditsController::store/show/destroy` |
| Business logic in the controller | `AuthController::sendVerificationEmailForUser()` — 100 lines of template assembly and SMTP error handling |
| `$request->all()` instead of `$request->validated()` | `PatientController::update()` — [S-14](SECURITY.md#s-14--patientsid-showupdatedestroy-have-no-ownership-scoping) |
| A `public static` action | `AuthController::isTokenValid()` |

New code should follow the house style, not the deviations.

## Authorization inside controllers

~20 `$this->authorize(...)` call sites, in five controllers only:

| Controller | Ability checked |
|---|---|
| `TestController`, `TestConditionController`, `TestSectionController`, `TestSectionPlateController`, `TestAnswerController` | `viewTests` / `createTests` / `updateTests` / `deleteTests` / `cloneTests` |
| `OrganizationController` | `viewAny` / `view` / `create` / `update` / `delete` |
| `CreditsController` | `delete` only |

**Everything else has no authorization beyond the route's middleware** — including
`LmsAdminController`, `SuperAdminDashboardController`, `ReportController`, `UserController`,
`DiscountCodeController` and `PaymentController`. See [AUTHORIZATION.md](AUTHORIZATION.md).

☠️ A failed `authorize()` returns **500**, not 403 ([ERROR_HANDLING.md](ERROR_HANDLING.md)).

## Dead controllers

`app/Http/Controllers/Auth/` — `LoginController`, `RegisterController`, `ForgotPasswordController`,
`ResetPasswordController`, `ConfirmPasswordController`, `VerificationController`. These are
`laravel/ui` scaffolding using web-session traits (`AuthenticatesUsers`, `RegistersUsers`, …). **No
route references them.** Editing them changes nothing; deleting them is safe.

## Adding a controller

1. Create the FormRequest first — validation belongs there.
2. Delegate to a service; keep the action to validate → call → respond.
3. Return `ApiResponse::success/error` with an `HttpStatus` constant and a key from
   `resources/lang/en/api.php` (add the key).
4. Register the route in the right guard zone ([ROUTES.md](ROUTES.md)) and re-check
   [INDEXES/PUBLIC_ROUTE_AUDIT.md](INDEXES/PUBLIC_ROUTE_AUDIT.md).
5. If the resource is owner-scoped, **scope the query** — the middleware will not do it for you.
