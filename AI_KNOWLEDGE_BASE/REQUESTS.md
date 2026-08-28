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
`UpdateProfileRequest` adopted the same rule on 2026-08-27 and **tightened the rest of the address**:
`address`, `city`, `zip_code` and `country_id` went from `nullable` to `required`. A profile-update
client that used to omit them now gets a 422 — the conditional `state_id` is what keeps that from
forcing a state on countries that have none.

**Email uniqueness now spans soft-deleted users** — changed 2026-08-27 (ws-352):
```php
Rule::unique('users', 'email')->ignore($id),      // was: ->whereNull('deleted_at')
```
The `whereNull('deleted_at')` clause was **removed**. It had let a soft-deleted account's email pass
validation and then hit the plain unique index on `users.email`, surfacing as a 500. Validation now
matches what the database will actually accept, so the collision is a 422 on the `email` field.

The trade-off is deliberate: a deleted account's address is **not** re-registrable. `UserController`
`store()`/`update()` additionally catch `UniqueConstraintViolationException` and translate it to the
same 422 — belt-and-braces for the race between validation and insert. (The comment on that catch still
describes the old rule; the rule itself is what changed.)

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
