# Context: Authentication, Tokens & Sessions

> Load this **instead of** reading the auth subsystem. ~1.8k tokens.

## Files
| File | Role |
|---|---|
| `app/Http/Controllers/AuthController.php` (775 lines) | ⭐ Login, register, verify, password set/reset, impersonation |
| `app/Http/Middleware/FlexibleAuthMiddleware.php` | ⭐ **The four-tier session gate** |
| `app/Http/Controllers/PasswordController.php` | Self-service password change |
| `app/Http/Requests/UserRequest.php` | Registration/user validation — see [SECURITY S-01](../SECURITY.md#s-01--public-registration-accepts-usertype--1) |
| `app/Models/User.php` | `usertype` constants, `canImpersonate*()`, verification helpers |
| `app/Notifications/ResetPasswordNotification.php` · `VerifyEmailNotification.php` | Mail |
| `app/Http/Controllers/Auth/*` | **Laravel/ui scaffolding — unused by the API.** Ignore. |

## Tables
`users` · `personal_access_tokens` (Sanctum) · `password_reset_tokens` (shared by **both** brokers) ·
`test_sessions` · `lms_sessions` · `organization_patient_sessions` · `test_resume_tokens`

## Roles
```
usertype: 1 = SUPER_ADMIN   2 = CUSTOMER   4 = ORGANIZATION      ← there is NO 3
account_status: 'active' | 'inactive' | 'suspended'              ← a STRING, not an int
```
Identical values in `TCV-Frontend` (`src/constants/dataObjects.js` → `USER_ROLES`). The website has no
role concept.

---

## The four token tiers

`FlexibleAuthMiddleware` tries them **in order** and stops at the first hit. This is the single most
important mechanism in the codebase.

| # | Tier | Presented as | Looked up | Merged into the request |
|---|---|---|---|---|
| 1 | **Sanctum** | `Authorization: Bearer …` | `Auth::guard('sanctum')->check()` | (normal `$request->user()`) |
| 2 | **TestSession** | Bearer **or** `X-Session-Token` | `test_sessions.session_token` — **plaintext** | `test_session_id`, `test_invitation_id`, `session_token` |
| 3 | **LmsSession** | same | `lms_sessions.session_token` = **SHA-256 of the presented token** | `lms_session_id`, `org_session_id`, `org_id`, `patient_id`, `unique_test_id`, plus `lmsSession` on `$request->attributes` |
| 4 | **OrganizationPatientSession** | same | `organization_patient_sessions.token` — plaintext | `org_session_id`, `org_id`, `patient_id`, `test_id`, `org_session_token` |

Tier 4 is explicitly marked *"fallback for pre-migration sessions — remove after Phase 3 cutover."*
Expired sessions in any tier return `401 {error_type: 'session_expired'}`; no match returns
`401 'Authentication required.'`

### Facts
- **Only tier 3 stores its token hashed.** Tiers 2 and 4 store the raw token. A DB read yields working
  session tokens for the invitation and legacy-org flows.
- **Tier 3 sets `unique_test_id` on the request — and it is the only tier that does.** Controllers
  nonetheless read `unique_test_id` from the URL, which is [S-02](../SECURITY.md#s-02--test-session-endpoints-never-check-that-the-caller-owns-the-test).
- Session TTLs: TestSession **2 h**, LmsSession **per provider config** (120 or 180 min),
  resume token **7 days**, invitation **7 days** (`TestInvitation::INVITATION_VALIDITY_DAYS`).

---

## Sanctum specifics

- **Tokens expire after 15 minutes** — `config/sanctum.php` → `'expiration' => 900`. This is the answer
  to most "it logged me out" reports.
- Token names carry meaning:
  | Name | Created in | Abilities |
  |---|---|---|
  | `superadmin-token` | `login()` when `isSuperAdmin()` | an **explicit 9-item list** (`view-tests`, `create-test`, … `delete-organization`) |
  | `api-token` | `login()` for everyone else | default `['*']` |
  | `impersonation-token` | `impersonateUser()` | `['impersonated-by:{id}']` only |
- ☠️ **The super-admin token's ability list omits `*`.** Any policy that calls `tokenCan('…')` for an
  ability outside those nine **fails for super admins and passes for customers** (whose `['*']` token
  satisfies `tokenCan` unconditionally). The `usertype` half of each policy is what actually holds the
  line. See [AUTHORIZATION.md](../AUTHORIZATION.md).
- ☠️ **`impersonation-token` has neither `*` nor the nine abilities**, so an impersonating super admin
  fails every policy check. Impersonation cannot reach `/tests` or `/organizations` admin endpoints.

---

## Login flow

```
POST api/login
  ├─ Authorization header present and token unexpired?
  │     → 200 "Already logged in"   ← skips account_status, trashed, email_verified   [S-07]
  ├─ validate(email, password)
  ├─ User::withTrashed()->where(email)          → 401 api.unauthorized  if missing
  ├─ account_status !== 'active' || trashed()   → 401 api.resticted     (sic — typo'd lang key)
  ├─ Hash::check(password)                      → 401 api.unauthorized  if wrong
  ├─ createStripeCustomer($user)                ← side effect on EVERY successful login, failures swallowed
  ├─ email_verified !== 'yes'                   → 401 + (re)sends verification mail
  └─ createToken(...) → { access_token, token_type, user }
```

Note the response envelope: `login()` returns a **hand-built** shape, not `ApiResponse`. So do
`isTokenValid()` (`{valid, …}`) and `verifyEmailByToken()` (`{status, message}`). Clients cannot assume
one envelope — see [CODING_GUIDELINES.md](../CODING_GUIDELINES.md).

---

## Password set vs. reset — one endpoint, two brokers

`POST api/password/reset` → `setOrResetPassword()` branches on a required `type` of `setup` | `reset`.

| | `type=setup` | `type=reset` |
|---|---|---|
| Broker | `Password::broker('setup')` — 48 h (`AUTH_PASSWORD_SETUP_TOKEN_EXPIRE`, default 2880 min) | default broker — 60 min |
| Precondition | `$user->password` **must be null** | password must exist and differ |
| Side effects | sets `email_verified_at`, `email_verified='yes'`, clears `password_setup_token`, fires `UserPasswordSet` | fires `PasswordReset` |

☠️ **Both brokers share one table** (`password_reset_tokens`, keyed by email). Issuing a reset token
therefore **overwrites** a pending setup token and vice versa — only one can be live per address at a
time. `register()` creates the setup token with the **default** broker (`Password::broker()`), while
`verifySetupToken()` validates it with the **`setup`** broker; that works only because they share the
table, and it means the effective expiry is the *validating* broker's 48 h.

`UserPasswordSet` → `SendAfterPasswordReset` → mails `OrganizationTestUrlNotification` **if the user
owns an Organization**. That is how an org gets its test URL. Wired by auto-discovery, *not* by
`EventServiceProvider` ([ARCHITECTURE_REALITY.md](../ARCHITECTURE_REALITY.md)).

---

## Self-service change is a third path

`PUT api/password/change` → `PasswordController::update()`, validated by `ChangePasswordRequest`. No
token and **no broker**: it re-checks `current_password` with `Hash::check`, then revokes every *other*
Sanctum token and keeps the caller's. A wrong current password returns 422 via `ApiResponse::error`
(`api.current_password_incorrect`) — so that response carries **no `errors` key**, unlike a real
validation failure.

☠️ **The strength rules differ between the two paths.** `ChangePasswordRequest` adds
`->uncompromised()` — a live k-anonymity lookup against Have I Been Pwned — while the same call sits
**commented out** in `AuthController::setOrResetPassword()`. A breached password rejected on the profile
page is therefore still accepted through the emailed reset link. See
[SECURITY.md](../SECURITY.md#what-is-done-well).

---

## ☠️ Traps

1. **`usertype` has no `3`.** `1`/`2`/`4`. Never `range(1, 4)`.
2. **`account_status` and `email_verified` are strings**, not booleans or ints. `'active'`, `'yes'`.
3. **Two verification flags disagree** — `email_verified` (string, gates login) vs `email_verified_at`
   (timestamp, used by `hasVerifiedEmail()`). `markEmailAsVerified()` sets only the timestamp, so the
   signed-link route leaves the user **unable to log in**. See [S-08](../SECURITY.md#s-08--two-email-verification-systems-that-disagree).
4. **`stopImpersonation` deletes nothing** — the ability string is double-prefixed before the
   `whereJsonContains`. [S-09](../SECURITY.md#s-09--stopimpersonation-never-deletes-the-impersonation-token).
5. **`AuthController::isTokenValid()` is declared `public static`** yet routed as a normal action. It
   works, but do not copy the shape, and do not call it statically expecting `$this`.
6. **`app/Http/Controllers/Auth/*` is `laravel/ui` scaffolding.** Web-session based, unreferenced by
   `routes/api.php`. Editing it changes nothing.
7. **Every successful login hits Stripe.** `createStripeCustomer()` swallows its exception into a log
   line, so a Stripe outage shows up only as slow logins.
