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

> ☠️ **`ws-402` (unmerged, credit revocation) both widens and finally gates this.** `delete()` also
> returns `true` for a `SOURCE_REVOKED` row whose new `original_source` column is `SOURCE_MANUAL` — a
> refund is deletable when the money it's returning traces back to a manual grant, never when it traces
> back to a purchase.
>
> **`$user` is no longer ignored.** `delete()` now returns `false` unless `$user->isSuperAdmin()`,
> checked *before* the source rules — who first, then what. That closes
> [S-19](SECURITY.md#s-19): the route carries only `auth:sanctum`, so while the policy read nothing but
> `source`, any authenticated customer could delete a stranger's grant by id — and since `destroy()`
> stopped being a row delete, that meant *mutating* their ledger (counter-entry, possibly a
> `SOURCE_ADJUSTMENT` row, possibly `settleNegativeBalance()`). `/add-credits` is a Super Admin page in
> the SPA, so this was never intended access.
>
> ⚠️ **`index()` is still ungated** — it reads `user_id` from the request and never scopes it to the
> caller, now leaking four per-grant usage fields as well.
>
> A denial returns **403**, not 500 — see the `ws-402` note in
> [ERROR_HANDLING.md](ERROR_HANDLING.md). Full detail: [CONTEXT/CREDITS_CONTEXT.md](CONTEXT/CREDITS_CONTEXT.md).
>
> **Both comparisons are `===`, so both operands must be integers.** `Credits::$casts` covers `source`
> and `original_source` for exactly this reason — MySQL's PDO can return an integer column as a string,
> and `"0" === 0` is false, which would 403 a refund that is genuinely deletable. A SQLite test suite
> will not reproduce that; it returns native integers. If you add another `===` comparison here, cast the
> column it reads.
>
> `original_source` is **nullable and null on every `SOURCE_REVOKED` row written before the column
> existed**, so those are all undeletable (`null === 0` is false). Any UI that mirrors this policy must
> reproduce the null case rather than coercing — see the `addCreditsColumns.js` note in
> [FRONTEND.md](FRONTEND.md#redux).

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
