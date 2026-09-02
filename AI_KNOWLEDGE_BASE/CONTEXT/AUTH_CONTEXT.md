# Context: Authentication, Tokens & Sessions

> Load this **instead of** reading the auth subsystem. ~2.1k tokens.

## Files
| File | Role |
|---|---|
| `app/Http/Controllers/AuthController.php` (775 lines) | ⭐ Login, register, verify, password set/reset, impersonation |
| `app/Http/Middleware/FlexibleAuthMiddleware.php` | ⭐ **The four-tier session gate** |
| `app/Http/Controllers/PasswordController.php` | Self-service password change |
| `app/Http/Requests/UserRequest.php` | Registration/user validation — see [SECURITY S-01](../SECURITY.md#s-01--public-registration-accepts-usertype--1) |
| `app/Models/User.php` | `usertype` constants, `canImpersonate*()`, verification helpers |
| `app/Notifications/ResetPasswordNotification.php` · `VerifyEmailNotification.php` | Mail |
| `app/Support/EmailContent.php` · `EmailSignature.php` | ⭐ Shared mail-body cleanup + the one sign-off (`ws-373`) |
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
| 1 | **Sanctum** | `Authorization: Bearer …` | `Auth::guard('sanctum')->check()` | **nothing** (normal `$request->user()`) |
| 2 | **TestSession** | Bearer **or** `X-Session-Token` | `test_sessions.session_token` = **SHA-256** | `test_session_id`, `test_invitation_id`, `session_token` |
| 3 | **LmsSession** | same | `lms_sessions.session_token` = **SHA-256** | `lms_session_id`, `org_session_id`, `org_id`, `patient_id`, `unique_test_id`, plus `lmsSession` on `$request->attributes` |
| 4 | **OrganizationPatientSession** | same | `organization_patient_sessions.token` = **SHA-256** | `org_session_id`, `org_id`, `patient_id`, `test_id`, `org_session_token` |

### 🔑 `auth_context` — the only trustworthy answer to "who is calling" (2026-09-02)

Read the **`auth_context` request attribute**, never request input, in any authorization check:

```php
$context = FlexibleAuthMiddleware::context($request);   // null ⇒ treat as unauthorized
// ['tier', 'user_id', 'org_id', 'patient_id', 'test_invitation_id', 'test_session_id']
```

All four tiers publish it. It lives on `$request->attributes`, which — unlike `$request->merge()` —
nothing a client sends can reach or overwrite.

**Why this exists.** Look at the *Merged* column: `org_session_id`, `org_id` and `patient_id` are
merged by tiers **3 and 4 only**. On tiers 1 and 2 they stayed raw client input, but the ownership
checks read them anyway, so:

- `org_session_id` (any value) short-circuited `callerOwnsPatient()` to `true` against any patient;
- `?org_id=<org with privilege 3>` defeated the certificate-download gate;
- a body `patient_id` let any session mail itself a resume link for someone else's test.

Merged keys are safe to *read* on the tier that merged them. They are not safe to *authorize* on,
because you cannot tell from the value which tier you are on. That is what `auth_context` is for.

**Tier 2 now carries a patient.** `test_sessions.patient_id` was added 2026-09-02. Org-added-patient
sessions have `test_invitation_id = null` and previously carried no identity at all — so every
ownership check fell through to "deny" and the whole organization patient flow 404'd, which is why
the forgeable `org_session_id` short-circuit was sitting in front of them. Set it wherever a session
is created for a patient (`PatientController::storeOrganizationPatient`, and the resume path, which
must carry the binding across).

Tier 4 is explicitly marked *"fallback for pre-migration sessions — remove after Phase 3 cutover."*
Expired sessions in any tier return `401 {error_type: 'session_expired'}`; no match returns
`401 'Authentication required.'`

### Facts
- **All four tiers now store their token SHA-256 hashed** (tiers 2 and 4 were migrated; the KB
  previously recorded them as plaintext). A DB read no longer yields usable session tokens.
  Related: the **verification code** is no longer written to the logs either — it is the credential
  that mints a tier-2 session, and the logs are now JSON-formatted and shippable.
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

## `verify-password` is a fourth path, and it decides nothing

`POST api/verify-password` (`API-176`, `auth:sanctum`, no throttle — the only rate limit in
`routes/api.php` is on `/contact`) → `AuthController::verifyPassword()`: validate, `Hash::check`,
return 200, or 422 `api.incorrect_password`. It writes **nothing** — no session flag, no token
ability, no log line. Its only caller is the SPA's Patients-menu prompt, which treats the 200 as
permission to `navigate()`
([FRONTEND.md](../FRONTEND.md#the-patients-menu-password-prompt-is-client-side-only), narrowed by
ws-399). Anything that must *enforce* a re-auth has to add its own state — there is none to read here.

---

## The reset and verification mail bodies live in the database

Neither body is a Blade file. `ResetPasswordNotification::toMail()` and
`AuthController::sendVerificationEmailForUser()` both read a row out of **`email_template`** (by `name`:
`password_reset`, `set_password`, `email_verification`), substitute `{{first_name}}`, `{{reset_url}}`,
`{{set_password_url}}`, `{{verification_link}}` by `str_replace`, and render it through
`emails.dynamic-template`. The row is editable by SQL and by data migrations, so the markup around a
placeholder is **not** guaranteed — the same placeholder can be an `<a href>` in one environment and
plain text in another.

`ws-373` (2026-08-31 — merged into `ws-404` on 2026-09-01, not yet deployed) closed that gap from
both ends:

| Where | What changed |
|---|---|
| **At send time** | `EmailContent::linkify()` runs on the substituted body in both paths, so a bare URL still goes out clickable ([ARCHITECTURE_REALITY.md §4](../ARCHITECTURE_REALITY.md)) |
| **In the stored row** | `2026_08_31_000001_anchor_bare_link_placeholders_in_email_templates` wraps a bare `{{reset_url}}` / `{{set_password_url}}` / `{{verification_link}}` in a styled button, so the template editor stops showing it as plain text |
| **Subject** | `2026_08_31_000002` renames the `password_reset` subject from `Reset Your Password` to **`Forgot your password? Reset it now`** — and `EmailTemplateSeeder` now seeds the new wording |
| **Footer** | `2026_08_31_000003` replaces the old raw-newline sign-off with `EmailSignature::HTML` (styled, `mailto:`/`tel:` links). `EmailSignature::LEGACY_HTML` is kept **only** so `down()` can recognise a row it may revert |
| **Blade fallback** | `resources/views/emails/verify-email.blade.php`'s "copy and paste this link" paragraph is now an anchor, not grey text |

☠️ **The three data migrations are match-on-old-value, not overwrite.** Each one only touches a row that
still holds exactly the value it expects, so a subject or footer somebody tailored in the database
survives, and `down()` cannot stamp a value the row never held. Copy that shape for any future template
migration — and note `000001` is deliberately **irreversible** (`down()` is empty), because unwrapping
anchors could not tell the ones it added from the ones an author wrote.

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
8. **The mail bodies are DB rows, so "change the copy" is usually a migration, not an edit.** Wording,
   footer and subject for `password_reset` / `set_password` / `email_verification` live in
   `email_template`; the seeder only runs on a fresh database. Changing the seeder alone leaves every
   existing environment on the old text — pair it with a match-on-old-value data migration (`ws-373`).
9. **Never hand-write the sign-off.** It is `App\Support\EmailSignature::HTML`, referenced by both the
   seeder and the restyle migration precisely so the contact details cannot drift between them.
