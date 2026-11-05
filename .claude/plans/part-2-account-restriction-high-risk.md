# Part 2 of 2 — Account restriction and email verification (high risk)

**Branch:** `fix/account-restriction-part-2` in TCV-Backend (`b57298ff`) and TCV-Frontend (`279d9f0`), with the Part 1 review fixes merged in (`cda6cecb` / `a2165ee`).
**Merge after Part 1.** This branch is built on `fix/test-emails-part-1`, so its diff against `develop` includes Part 1; review **only its own commit** (`b57298ff` / `279d9f0`), or diff against `fix/test-emails-part-1`.
Background and decisions: [test-email-and-account-restriction-fixes.md](test-email-and-account-restriction-fixes.md) (items 4–5).

## Why it is high risk
- **A new global middleware runs on every request that carries a token.** A mistake here can sign everyone out or let a restricted user in.
- It changes **when login tokens are kept and deleted**, including Super Admin "Login as" sessions.
- It changes **what the admin edit forms can save** for users and organisations, which feeds the login gate (`email_verified`).
- The backend and frontend halves must work together for the user to see the right message.

## What changed

| # | Issue | Repo · files |
|---|---|---|
| 4 | A restricted user stayed logged in, and when finally signed out never saw a "restricted" message | Backend: `RestrictAccountMiddleware` (new, global), `bootstrap/app.php`, `User::booted()`. Frontend: `errorHandler.js`, `constants/messages.js` |
| 5 | After restrict → re-activate, a verified user could be asked to verify their email again | Backend: `UserController::update()`, `OrganizationController::update()`. Frontend: `NewUserModal.js`, `OrganisationModal.js`, `Organisation.js`, `organisationUtils.js` |

### Dev review — what to look at
1. **`RestrictAccountMiddleware`** (appended globally after `RestrictIpMiddleware`):
   - Skips requests with no bearer token, and `api/logout`.
   - **`api/login` is not exempt.** `AuthController::login()` returns any still-valid token as "Already logged in" (200), so exempting login would let a restricted user back in.
   - A restricted account's token gets 401 `{success:false, status_code:401, message: api.resticted, error_code: ACCOUNT_RESTRICTED}`.
   - "Login as" tokens (`impersonated-by:{id}`) are allowed while that **admin** is active, even on a restricted target, and refused once the admin is not.
   - The `sanctum` guard caches the resolved user, so `auth:sanctum` and FlexibleAuthMiddleware do not look the token up again.
2. **`User::booted()`** on `account_status` change:
   - **Restricted:** the account's own tokens are **kept** on purpose. A deleted token would surface as a bare "Unauthenticated." and the SPA would say "Your session has expired". The "Login as" tokens this account minted are deleted (they used to outlive a restricted admin for up to 15 hours).
   - **Re-activated:** the account's tokens are deleted, so the user logs in fresh.
3. **`email_verified`** is dropped from the validated input in both update controllers, and the SPA forms no longer send it. It now changes only through the verify-email and set-password flows.
4. **Frontend:** `ACCOUNT_RESTRICTED` is in `errorCodeMap`, with its text in `POPUP_MESSAGES`. The existing interceptor branch (401 with an `error_code`) shows the message, clears the session and goes to `/login` after 2.5 s.

### Automated checks
- Backend: all 1462 tests run (all except `PatientExportTest.php`, which already has a fatal error on `develop`). The only 7 failures also fail on `develop`. `AccountRestrictionTest` has 14 tests covering: the `error_code` on every request, re-activation, login with an old token, logout, impersonation in both directions, a status set straight in the database, and `email_verified` through both edit forms.
- Frontend: all 368 tests pass. The only failing suite is the existing `App.test.js` module error. No new lint warnings, and the production build succeeds.

## QA test cases

**Setup:** two browsers or profiles. A Super Admin in one; a customer or organisation user (with a **verified** email) in the other. One **unverified** account for E3.

### D. Restricting a signed-in user (issue 4)
| # | Steps | Expected |
|---|---|---|
| D1 | User is signed in. Super Admin restricts them (Users or Integrated Partners → edit → status off → save). User clicks anything. | Popup **"Your account has been restricted. Please contact your administrator for assistance."**, then the login page. Not "Your session has expired". |
| D2 | The restricted user tries to log in. | The login page shows the same restricted message (unchanged behaviour). |
| D3 | The restricted user reloads the app with the old session still stored. | Restricted popup, then the login page; they are not let back in. |
| D4 | Super Admin re-activates the user. The user logs in. | Login works. Any session from before the restriction no longer works. |
| D5 | A user who is **not** restricted keeps using the app throughout D1–D4. | No change: no popups, no sign-outs. |
| D6 | Restrict an **organisation** user from Integrated Partners. | Same as D1–D4. |

### E. Email verification survives restriction (issue 5)
| # | Steps | Expected |
|---|---|---|
| E1 | A verified user: restrict, then re-activate (Users screen). Log in. | Logs straight in, **no** "Please verify your email" message. |
| E2 | The same through Integrated Partners for an organisation user. | Same as E1. |
| E3 | An **unverified** user: restrict, then re-activate. Log in. | The verification message **is** shown, with Resend Verification Link. |
| E4 | Edit any other field of a verified user (city, name) and save. | They stay verified. |
| E5 | Create a new user and a new organisation from the admin screens. | Unchanged: they get the set-password email and are verified once they set a password. |

### F. Super Admin "Login as"
| # | Steps | Expected |
|---|---|---|
| F1 | Super Admin uses "Login as" on a **restricted** user. | Works (support access is kept). |
| F2 | Super Admin A uses "Login as" on a user. Super Admin B restricts A. In A's impersonation tab, click anything. | Restricted popup, then the login page; the impersonation session has ended. |
| F3 | Normal "Login as" and "Stop impersonation" on an active user. | Unchanged. |

## Deploy notes
- **Deploy the backend and frontend together.** If only the backend is deployed, a restricted user is still signed out, but sees "Your session has expired" instead of the restricted message. Removing `email_verified` is safe in either order.
- No migrations. No configuration changes.
- **After deploy, data repair (not in this branch):** accounts already turned unverified by the old forms. Review the list first:
  `SELECT id, email, email_verified, email_verified_at FROM users WHERE email_verified = 'no' AND email_verified_at IS NOT NULL;`
- **Knowledge base:** the KB updates (MIDDLEWARE, AUTH_CONTEXT, SECURITY S-07, FRONTEND, FULLSTACK_MAP, INVITATION_CONTEXT, regenerated indexes) describe **both parts**. Commit them once with Part 2, in the `tcv_ai_knowledge_base` repo.
