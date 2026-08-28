# Authentication

> Mechanism-level reference. For the flows and their traps, load
> [CONTEXT/AUTH_CONTEXT.md](CONTEXT/AUTH_CONTEXT.md) instead — it is shorter and covers more.

## Guards

`config/auth.php`:
```php
'defaults' => ['guard' => 'api', 'passwords' => 'users'],
'guards'   => ['api' => ['driver' => 'sanctum', 'provider' => 'users']],
```
There is no `web` guard in use for the API. `app/Http/Controllers/Auth/*` (the `laravel/ui`
scaffolding) assumes one and is unreachable from `routes/api.php` — ignore it.

## Sanctum

| Setting | Value | Where |
|---|---|---|
| Token lifetime | **900 seconds (15 minutes)** | `config/sanctum.php` → `'expiration' => 900` |
| Token prefix | none (`SANCTUM_TOKEN_PREFIX` unset) | `config/sanctum.php` |
| Storage | `personal_access_tokens` | Sanctum default |

`User` uses `HasApiTokens`. Tokens are issued in exactly three places:

| Token name | Issued by | Abilities |
|---|---|---|
| `superadmin-token` | `AuthController::login()` when `usertype === 1` | explicit list of 9 |
| `api-token` | `AuthController::login()` otherwise | default `['*']` |
| `impersonation-token` | `AuthController::impersonateUser()` | `['impersonated-by:{id}']` |

The abilities matter — see [AUTHORIZATION.md](AUTHORIZATION.md).

**Logout** (`AuthController::logout()`) deletes only the current token.
**Password change** (`changePassword()`) deletes **all** of the user's tokens.

## Session tokens (not Sanctum)

Three additional credential types exist, all resolved by `FlexibleAuthMiddleware`
([MIDDLEWARE.md](MIDDLEWARE.md)):

| Type | Table | Stored | TTL |
|---|---|---|---|
| Test session | `test_sessions.session_token` | plaintext, `Str::random(32)` | 2 h |
| LMS session | `lms_sessions.session_token` | **SHA-256** of a 32-byte random | 120 or 180 min (provider config) |
| Legacy org session | `organization_patient_sessions.token` | plaintext | per row |

And two single-use-ish credentials that are *exchanged* for a session:

| Credential | Table | TTL |
|---|---|---|
| Invitation token + 6-char code | `test_invitations` | 7 days |
| Resume token | `test_resume_tokens` | 7 days |

## Password storage & policy

- Hashing: `bcrypt` via the `'password' => 'hashed'` cast on `User`. `BCRYPT_ROUNDS=12`.
- Policy on **reset** and **change**: `PasswordRule::min(8)->mixedCase()->numbers()->symbols()`.
  `->uncompromised()` and `->letters()` are present but **commented out** in `setOrResetPassword()`.
- Policy on **register**: only `min:8|max:20`. The two paths disagree — registration accepts a weaker
  password than a subsequent reset would.

## Password brokers

```php
'passwords' => [
    'users' => ['table' => 'password_reset_tokens', 'expire' => 60,   'throttle' => 60],
    'setup' => ['table' => 'password_reset_tokens', 'expire' => 2880, 'throttle' => 60],  // 48 h
],
```

☠️ **Both brokers share one table**, keyed by email. Only one live token per address can exist, so
issuing a reset invalidates a pending setup and vice versa. `register()` mints the setup token with the
**default** broker while `verifySetupToken()` validates it with the **`setup`** broker; that works only
because of the shared table.

## Email verification

Two flags that can disagree — `email_verified` (`'yes'`/`'no'` string, what `login()` checks) and
`email_verified_at` (timestamp, what `hasVerifiedEmail()` checks). `markEmailAsVerified()` writes only
the timestamp. See [S-08](SECURITY.md#s-08--two-email-verification-systems-that-disagree).

Two verification endpoints exist:
- `GET api/verify-email/{id}/{hash}` — Laravel's `signed` route, `sha1(email)` hash → `markEmailAsVerified()`
- `POST api/verify-email-token` — a 24-hour `Str::random(60)` token in `users.email_verification_token`
  → sets **both** flags

The second is the one the SPA and the email template use. The first is the one that leaves users locked out.

## Client-side token handling

`TCV-Frontend/src/apis/AxiosInstance.js` attaches, in priority order:
1. `sessionStorage.impersonateToken`
2. `?impersonateToken=` query param (stored into sessionStorage on first sight)
3. `localStorage.test_invitation_session_token` — only on `/test-invitation/*`, `/organization/*`, `/test/resume/*`
4. `localStorage.auth.token` (falling back to `localStorage.token`)

On **401** it clears storage and redirects to `/login`, **except** on those same public paths. See
[FRONTEND.md](FRONTEND.md).

`TCV-Website` holds no token in the browser at all — `POST /api/auth` is a **server-side proxy** to
`POST /api/login` ([WEBSITE.md](WEBSITE.md)).
