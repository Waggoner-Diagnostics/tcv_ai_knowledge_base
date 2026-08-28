# FormRequests

**24 classes in `app/Http/Requests/`.** This is the one convention the codebase applies consistently —
follow it. Full list with line numbers: [INDEXES/CLASS_INDEX.md](INDEXES/CLASS_INDEX.md) (`REQ-nnn`).

| Domain | Classes |
|---|---|
| Users / auth | `UserRequest` · `UpdateUserEmailTemplateRequest` · `ChangePasswordRequest` · `UpdateProfileRequest` |
| Patients | `PatientAddRequest` · `PatientUpdateRequest` |
| Tests | `CreateTestRequest` · `TestRequest` · `PerformTestRequest` · `TestAnswerRequest` · `TestConditionRequest` · `TestSectionRequest` · `TestSectionPlateRequest` · `GenerateTestReportRequest` |
| Credits & money | `CreditsAddRequest` · `CreatePaymentRequest` · `PartialPaymentRequest` · `RefundPaymentRequest` |
| Discounts | `StoreDiscountCodeRequest` · `UpdateDiscountCodeRequest` · `ValidateDiscountCodeRequest` |
| Organisations | `OrganizationRequest` · `UpdateSettingsRequest` |
| Misc | `ContactFormRequest` |

## ☠️ Every `authorize()` returns `true`

Without exception. FormRequests here validate **shape only** — they never decide permission.

That is normally a stylistic choice; in one case it is a critical bug. `UserRequest` is shared between
the **public** `POST api/register` and the authenticated `POST api/users`, and it happily validates
`usertype: 1` (SUPER_ADMIN) with `account_status: 'active'`:

```php
'usertype'       => 'required|integer|in:' . implode(',', [User::SUPER_ADMIN, User::CUSTOMER, User::ORGANIZATION]),
'account_status' => 'required|in:active,inactive,suspended',
```

See [S-01](SECURITY.md#s-01--public-registration-accepts-usertype--1). Splitting that request, or gating
the rules on `$this->user()`, is the fix.

## Useful patterns already in use

**Conditional rules** — `UserRequest` makes `state_id` required only when the chosen country has states:
```php
'state_id' => Rule::when(
    fn() => State::where('country_id', (int) $countryId)->exists(),
    ['required', 'integer', 'exists:states,id'],
    ['nullable', 'integer', 'exists:states,id']
),
```

**Unique-ignoring-soft-deletes** — the same class:
```php
Rule::unique('users', 'email')->ignore($id)->whereNull('deleted_at'),
```
This matters because `User` soft-deletes; without `whereNull('deleted_at')` a deleted account would
block re-registration forever.

**Domain helpers on the request** — `PerformTestRequest::isAutoSubmit()` and
`CreateTestRequest::EYE_TESTED_BOTH` put small pieces of domain vocabulary where the controller can use
them. Copy this rather than re-deriving in the controller.

## Where FormRequests are *not* used

Inline `$request->validate([...])` appears in `AuthController` (throughout),
`TestInvitationController::sendInvitations()`, `TestResumeController`, and
`CreditsController::checkDiscountCodeValidity()`. Both styles produce the same 422; the FormRequest is
preferred for anything new.

## ☠️ `$request->all()` defeats the FormRequest

`PatientController::update()` type-hints `PatientUpdateRequest` and then calls `$request->all()`, so the
validated subset is discarded and every `$fillable` column — including `user_id` — becomes writable.
[S-14](SECURITY.md#s-14--patientsid-showupdatedestroy-have-no-ownership-scoping).

**Always use `$request->validated()`.** Grep for `$request->all()` before shipping.

## Adding one

1. `php artisan make:request …`, keep `authorize()` returning `true` **only** if the route's middleware
   already establishes who may call it.
2. Put domain constants and derived flags on the request class, not the controller.
3. Consume it with `$request->validated()`.
4. Validation failures return 422 with `errors` — that and 401 are the only statuses the exception
   handler preserves ([ERROR_HANDLING.md](ERROR_HANDLING.md)).
