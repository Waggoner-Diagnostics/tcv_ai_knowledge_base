# Policies

**3 policies**, registered in `AuthServiceProvider::$policies`. The mechanics and the trap are in
[AUTHORIZATION.md](AUTHORIZATION.md); this page is the quick reference.

| Model | Policy | Methods |
|---|---|---|
| `Test` | `TestPolicy` | `viewTests` · `createTests` · `updateTests` · `deleteTests` · `cloneTests` |
| `Organization` | `OrgPolicy` | `viewAny` · `view` · `create` · `update` · `delete` |
| `Credits` | `CreditsPolicy` | the 7 standard methods |

## The shape

`TestPolicy` and `OrgPolicy` are uniform:

```php
return $user->usertype === User::SUPER_ADMIN && $user->tokenCan('view-tests');
```

Two conditions. **The `usertype` half is what actually enforces anything** — a `CUSTOMER`'s `api-token`
carries `['*']`, so `tokenCan()` returns `true` for any string.

`CreditsPolicy` denies everything except:

```php
public function delete(User $user, Credits $credits): bool
{
    return $credits->source === Credits::SOURCE_MANUAL;
}
```

Note it ignores `$user` entirely — **any** authenticated user may delete **any** manually-granted credit
row. Purchased (`1`) and revoked (`2`) grants are undeletable by anyone.

## ☠️ Three traps

1. **The nine abilities are a closed list.** `AuthController::login()` grants a super admin exactly:
   `view-tests`, `create-test`, `update-test`, `delete-test`, `clone-test`, `view-organizations`,
   `create-organization`, `update-organization`, `delete-organization`. A policy calling
   `tokenCan('anything-else')` **fails for super admins and passes for customers**. Add an ability to
   the policy and to `login()` in the same commit.

2. **Impersonation tokens fail every policy.** `impersonation-token` carries only
   `['impersonated-by:{id}']` — no `*`, none of the nine. An impersonating super admin cannot reach the
   tests or organizations admin surfaces at all.

3. **A denial returns 500, not 403.** `AuthorizationException` falls through to the catch-all branch of
   the exception handler ([ERROR_HANDLING.md](ERROR_HANDLING.md)). Clients cannot distinguish "forbidden"
   from "crashed".

## Where policies are actually consulted

~20 `$this->authorize(...)` call sites, in **five** controllers only: `TestController`,
`TestConditionController`, `TestSectionController`, `TestSectionPlateController`,
`TestAnswerController` (tests), `OrganizationController` (orgs), `CreditsController::destroy()` (credits).

Everything else — `LmsAdminController`, `SuperAdminDashboardController`, `ReportController`,
`UserController`, `DiscountCodeController`, `PaymentController`, `PatientController` — has **no policy
check at all**. See [AUTHORIZATION.md](AUTHORIZATION.md#where-there-is-no-authorization-at-all).

## Adding a policy

1. Write it, map it in `AuthServiceProvider::$policies`.
2. If it uses `tokenCan()`, add the ability to `AuthController::login()`'s super-admin array.
3. Call `$this->authorize(...)` in the controller — **and** return an explicit
   `ApiResponse::error(HttpStatus::FORBIDDEN, …)` where the status matters to the client, because
   `authorize()`'s exception will surface as a 500.
4. For **owner-scoped** data, prefer scoping the query (`->where('user_id', auth()->id())`) over a
   policy: policies need a `User`, and three of the four token tiers don't have one
   ([MIDDLEWARE.md](MIDDLEWARE.md)).
