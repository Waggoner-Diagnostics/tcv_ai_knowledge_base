# Authorization

**Four different mechanisms coexist, and one of them is "none".** Knowing which applies to the endpoint
you're touching is most of the work.

| Mechanism | Where | Coverage |
|---|---|---|
| Route middleware | `routes/api.php` | Decides *authenticated vs. not*, not *who* |
| Policies via `$this->authorize()` | ~20 call sites in 5 controllers | Tests, Organizations, Credits(delete) |
| Inline `usertype` checks | scattered | ad hoc |
| **Nothing** | most of the rest | ← the default |

## Roles

```
User::SUPER_ADMIN = 1
User::CUSTOMER    = 2
User::ORGANIZATION = 4        ← there is no 3
account_status ∈ {'active', 'inactive', 'suspended'}
```

`User` helpers: `isSuperAdmin()`, `canImpersonate()`, `canBeImpersonated()`, `canImpersonateUser($target)`.
Note `canImpersonate()` returns true for **all three** roles, but `canImpersonateUser()` then requires the
actor to be `SUPER_ADMIN` — the first method is a misleading name, not a second permission level.

## Policies

Registered in `AuthServiceProvider::$policies`:

| Model | Policy | ID |
|---|---|---|
| `Test` | `TestPolicy` | `POL-…` |
| `Organization` | `OrgPolicy` | `POL-…` |
| `Credits` | `CreditsPolicy` | `POL-…` |

### `TestPolicy` / `OrgPolicy` — role **AND** token ability
```php
public function viewTests(User $user)
{
    return $user->usertype === User::SUPER_ADMIN && $user->tokenCan('view-tests');
}
```
Every method in both policies has this shape. Abilities used:
`view-tests`, `create-test`, `update-test`, `delete-test`, `clone-test`,
`view-organizations`, `create-organization`, `update-organization`, `delete-organization`.

☠️ **Those nine strings are also, exactly, the ability list `login()` grants a super admin.** The two
lists must stay in lockstep:

- Add a policy method calling `tokenCan('archive-test')` and **it fails for super admins**, because their
  token carries an explicit list that does not include it — while a `CUSTOMER`'s `api-token` has `['*']`
  and passes `tokenCan()` unconditionally. Only the `usertype ===` half stops them. **The privilege
  check is inverted relative to intuition; the role check is what actually holds the line.**
- An **impersonation token** carries only `['impersonated-by:{id}']`, so an impersonating super admin
  fails every policy. Impersonation cannot reach the tests or organizations admin surfaces at all.

If you add an ability, add it to `AuthController::login()`'s array in the same commit.

### `CreditsPolicy` — deny by default
`viewAny`, `view`, `create`, `update`, `restore`, `forceDelete` all return `false` unconditionally. Only
`delete()` returns anything else:
```php
return $credits->source === Credits::SOURCE_MANUAL;
```
So purchased (`1`) and revoked (`2`) grants can never be deleted, by anyone. And because only
`CreditsController::destroy()` calls `authorize()`, the other five methods are never consulted — listing
and creating credits is gated by `auth:sanctum` alone.

### ☠️ A policy failure returns 500, not 403
`AuthorizationException` falls through to the catch-all branch of the exception handler
([ERROR_HANDLING.md](ERROR_HANDLING.md)). Every denial in this app looks like a server error to the
client.

## Where there is no authorization at all

These are `auth:sanctum` (any role) or `FlexibleAuthMiddleware` (any of four token tiers), with no
further check:

| Surface | Guard | Consequence |
|---|---|---|
| `api/admin/lms/*` (8 endpoints) | `auth:sanctum` | any user reads/rotates any org's signing key ([S-06](SECURITY.md#s-06--lms-provider-secrets-are-stored-in-plaintext)) |
| `api/super-admin/dashboard` | `auth:sanctum` | any user sees the admin dashboard |
| `api/reports/*` | `auth:sanctum` | any user runs cross-tenant reports |
| `api/test-session/*`, `api/test-result/*`, `api/tests/perform` | `FlexibleAuthMiddleware` | any session touches any test ([S-02](SECURITY.md#s-02--test-session-endpoints-never-check-that-the-caller-owns-the-test)) — `test-result/{id}/download-pdf` is now scoped, the rest are not |
| `api/patient-tests/{id}/revoke-credit` | `auth:sanctum` | any user abandons any test ([S-04](SECURITY.md#s-04--revokecredit-has-no-ownership-check)) |

**The SPA hides these surfaces by role** through `RouteConfig` in
`src/constants/routeConfig.js` ([FRONTEND.md](FRONTEND.md)). That is a menu, not a permission — every
endpoint above is reachable directly.

## Choosing a mechanism for new code

1. **Owner-scoped data** (patients, credits, tests belonging to a user) → scope the **query**:
   `->where('user_id', auth()->id())`. This is the only mechanism that composes safely with the
   session-token tiers, because those tiers have no `User`.
2. **Role-gated admin action** → a policy method, and add its ability to `login()`'s super-admin list.
3. **Session-derived id** → take it from the URL never, and from the *merged request keys* only when
   you already know which tier merged them. To **authorize**, read the `auth_context` attribute:
   `FlexibleAuthMiddleware::context($request)` (`null` ⇒ unauthorized). Tiers 1 and 2 merge no
   `org_id` / `patient_id` / `org_session_id` at all, so on those tiers the "merged" keys are plain
   client input — three separate bypasses came from authorizing on them. See
   [AUTH_CONTEXT](CONTEXT/AUTH_CONTEXT.md#-auth_context--the-only-trustworthy-answer-to-who-is-calling-2026-09-02).
4. **Ownership failures return `404`, not `403`,** for id-addressed resources — the caller is not
   entitled to learn whether the id exists. Do not let a `firstOrFail()` inside a `try` become the
   authorization check: the generic `catch (\Throwable)` turns the denial into an opaque 500.
5. **Return the status explicitly.** `ApiResponse::error(HttpStatus::FORBIDDEN, …)` — do not rely on
   `authorize()` producing a 403, because it won't. Every `api.*` key you pass must exist in
   `resources/lang/en/api.php`, or `__()` renders the raw key to the user.
