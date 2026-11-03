# Plan: test-email resend, Start/Resume email, verification email expiry, account restriction

Branch: `develop` in `TCV-Backend` and `TCV-Frontend`. Investigated 2026-11-03.

**Status (2026-11-03): implemented and split into two branches for review and QA** (both repos, local only):
- **Part 1, low risk** (items 1–3, test emails): `fix/test-emails-part-1` — see [part-1-test-emails-low-risk.md](part-1-test-emails-low-risk.md).
- **Part 2, high risk** (items 4–5, account restriction and `email_verified`; includes the review fixes): `fix/account-restriction-part-2`, built on Part 1 — see [part-2-account-restriction-high-risk.md](part-2-account-restriction-high-risk.md).

The earlier single branch `fix/test-email-and-account-restriction` (plus a stash of the review fixes) is superseded and kept only as a backup. The item 5 data repair is not done; it waits on the review query. Improvements I-1 to I-7 are not started.

Each item lists what was **verified** (by reading code or running a throwaway test that has since been deleted) and what is still **inferred**.

---

## 1. A resent test email arrives as a normal "test sent" email

### Root cause (verified)
| UI action | Endpoint | Template used today |
|---|---|---|
| Patients → Invited → Resend (invitation not yet registered) | `POST /test-invitations/{id}/resend` → `TestInvitationController::resendUnregisteredInvitation()` | Sender's invitation template (`test_link`, or `org_test_link` for an organisation), the same as a first send |
| Resend Test Email (registered or migrated test) | `POST /resend-test-link` → `TestService::generateAndSendTestLink()` | `test_link_resent` (correct already) |
| `POST /test-invitations/send` with `unique_test_id` | batch job | Invitation template. **The SPA never calls this, so it is out of scope (I-6).** |

The resend also replaces the invitation's `token` and `verification_code` (`TestInvitationController.php:685-696`). Because of that, the link and code in every earlier email stop working (`checkTokenStatus` returns "Invalid token" and `verifyCode` returns "Invalid verification code").

### What the test taker sees today (rendered from the seeded `org_test_link` template)
An organisation's first invitation and its resend look **identical** today. The only difference is that the resend carries a new token and code, and the old email stops working:

> **Subject:** Testing Color Vision - You have been invited to take a color vision test
>
> Dear Test Taker:
>
> Thank you for enrolling in the Color Vision Testing module in our learning management system.
>
> Since completion of this test is a critical piece of your employee health record, we want to ensure you are able to successfully complete the test and retrieve your results.
>
> This link will allow you to continue the test, if you lost connection to the module for any reason:
>
> **[ Start Test ]**
>
> Your verification code is: **X1Y2Z3**
>
> This invitation expires on 11/10/2026 9:05 AM.
>
> You may also use the above link to access your results and print them.
>
> Cordially,
>
> *{Organisation name}*
> *{Organisation email}*

### Decision (2026-11-03)
**Every resend of an unregistered invitation, organisation or not, uses the super-admin Test Link Resent template.** Only the link differs: each email carries that test taker's own invitation link, `/test-invitation/verify/{token}`. The token is unique per invitation, and the app works out the inviting organisation from it, so an organisation's test taker still lands on that organisation's form and branding. An organisation-specific resend template is deferred to a later release (see Improvements log, I-1).

The organisation's integrated-partner Test URL (`organizations.test_url`) is **not** used:
- it is not tied to the invitation, so the invitation would never be used or marked complete;
- the test taker would re-register as a new patient record;
- it takes a second credit at assignment (`TestAssignmentService.php:124`), on top of the one taken when the invitation was sent.

### Fix
**1a. Keep the original link and code working (backend)**
- In `resendUnregisteredInvitation()`, stop generating a new `token` and `verification_code`. Keep both, set `expires_at = now() + INVITATION_VALIDITY_DAYS`, and increment `resend_count`.
- The test taker can then use any email they hold, the original or a resend. The link opens the same invitation, the code from the original email still works, and all of them get the new expiry. (The original email still prints its old expiry date. The real expiry is later, so nobody is turned away early.)
- The rotation never gave real protection: a resend always goes to the address already on the row. A leaked link is still revoked by Cancel followed by a new Send.
- Keep the `email_status === SENDING` 409 guard, which prevents two emails going out at the same moment. Update the comment in `SendTestInvitationEmailsJob.php:318` that says a resend rotates the token.

**1b. Send every resend with Test Link Resent (backend)**
- Add `bool $isResend = false` to `TestInvitationMailer::send()` and pass `true` from `resendUnregisteredInvitation()`. When it is true, the mailer skips the sender's `test_link` / `org_test_link` template and renders `test_link_resent` through `SystemEmailTemplateService` instead, for every sender type.
- Fill `{{test_url}}` with the invitation link. It is already the template's one required placeholder, so no link placeholder is added.
- **No template or editor changes.** The resend shows only the link; there is no verification code and no expiry line (decision 2026-11-03; logged as I-7). No placeholders, default-body update or data migration are needed for `test_link_resent`.
- **Consequence:** the test taker types the code from their **original** invitation email. That works because 1a keeps the code unchanged. Someone who has lost the original email cannot get in from the resend alone. They need a new invitation (Cancel, then Send) until I-7 ships.
- `/resend-test-link` (registered or migrated tests) is unchanged. It already uses this template with `{{test_url}}` = `/organization/instruction/{unique_test_id}`, which needs no code.

**Tests**
- After a resend, the original token and code still pass `check-validity` and `verify-code`.
- An organisation sender's resend and a non-organisation sender's resend both use Test Link Resent, with that invitation's own link in `{{test_url}}`.
- A first send is unchanged (organisations still get `org_test_link`).

---

## 2. Test taker does not receive the "Start or Resume Test" email

### Root cause (verified)
- **Backend works.** For an organisation LMS session, `POST /test/send-resume-email` returned **200** and sent the `test_resume_link` email (checked with a throwaway feature test).
- **The org toggle is wired correctly.** "Send Test Email to Test Takers" is saved into `organization_configs.fields`, and `generateFieldRules()` turns it into `field_rules.send_test_email_to_patients.visible`.
- **Only one page sends it:** `VerifiedDefaultUser.js:176-184`, the integrated-partner Test URL page.
- **Two paths never send it,** even with the toggle on:
  - an email invitation from an organisation, where the test taker registers at `/test-invitation/verified/:token/add-patient` (`AddPatient.js`);
  - an organisation user adding a test taker from the dashboard and starting the test (`AddPatient.js`, same component).

  `AddPatient.js` already loads the organisation's `field_rules` for both paths (`AddPatient.js:164-175`) but never checks the toggle.
- Every failure is hidden: the one existing call ends in `.catch(() => {})`.

### Fix (frontend, plus one backend test)
- In `AddPatient.js`, after `startTestFlowWith()` succeeds, dispatch `sendTestResumeEmail({ unique_test_id, email })` when `fieldRules?.send_test_email_to_patients?.visible === true` and the form has an email. The toggle dependency stays as it is today (confirmed).
- In `AddPatient.js` and `VerifiedDefaultUser.js`, replace `.catch(() => {})` with a `console.warn` so failures are visible but never block the test.
- Backend test: a registered invitee's TestSession can send its own resume email. Today only the negative ownership case is covered (`SessionOwnershipTest`).

**Still to confirm:** if the failure was seen on the Test URL page itself (not on the email-invite or dashboard path), capture the `send-resume-email` response from the browser's Network tab. That path worked in the backend test.

---

## 3. "Verify Your Email Address" email has no link expiry

### Current state (verified)
- `AuthController::sendVerificationEmailForUser()` sends the `email_verification` row from `email_template`. The token lasts 24 hours (`email_verification_expires_at = now()->addHours(24)`).
- The seeded body has no expiry line. When the email is resent while the token is still valid, the token is reused and its expiry is **not** extended.

### Fix
- Add a data migration that inserts `<p>This link expires in 24 hours.</p>` directly after the paragraph containing the `{{verification_link}}` link or button, only when that line is not already present (safe to run twice). Mirror the change in `EmailTemplateSeeder`.
- To keep the text true on a resend, also reset `email_verification_expires_at` to `now()->addHours(24)` when an existing token is reused. The token stays the same, so earlier emails keep working.
- Test: the rendered verification email contains the expiry line directly below the link.

### When the verification link expires (current behaviour, verified)
- **Opening an expired link:** `verify-email` returns `TOKEN_EXPIRED_OR_INVALID`. `VerifyEmail.js` shows a **"Link Expired"** popup with a **Resend** button. Resend calls `POST /resend-verification-by-token`, which clears the old token and sends a new email with a fresh 24-hour link, and the popup confirms it ("Check Your Inbox").
- **Trying to log in:** login is blocked with "Please verify your email…" and a **Resend Verification Link** button (with a 2-minute cooldown). It calls `POST /resend_email_verification_link`. Because the stored token has expired, a new token and a fresh 24-hour link are created.
- **Both resend endpoints** give the same neutral reply whether the address exists or not (so they can't be used to find out which emails are registered). They do nothing for an already-verified account. Neither has a rate limit (I-4).
- **Super admin:** there is **no** button today to resend a verification email or to mark a user verified. The admin edit form has no verification field either. The only "control" is the hidden `email_verified` value in the edit payload, which item 5 removes because it is what turns verified users into unverified ones. A super admin's only options today are to ask the user to use the Resend links above, or to change the database by hand. Admin tools are logged as I-2 and I-3.

---

## 4. Restricted user is not logged out of an active session

### Root cause (verified)
- Restricting a user (setting `account_status` to `inactive`, shown as "Restricted"), whether through `UserController::update()` or `OrganizationController::update()`, does not revoke the user's Sanctum tokens. No middleware checks `account_status` on each request.
- Reproduced: after restriction, the user's existing token still returned **200** on an authenticated endpoint. Only a new login is blocked.

### Fix (backend, frontend message only)
- When `account_status` changes from `active` to anything else, call `$user->tokens()->delete()`. Do this in both `UserController::update()` and `OrganizationController::update()`. Impersonation tokens issued for that user are revoked too.
- Add a defensive middleware on the `auth:sanctum` group, and on FlexibleAuthMiddleware's Sanctum tier, that returns **401** with `error_code: ACCOUNT_RESTRICTED` and the existing `api.resticted` message when the user is not active. This follows the existing `RestrictIpMiddleware` / `IP_RESTRICTED` pattern.
- **Frontend:** no logic change. The response interceptor already clears auth on a 401 with an `error_code`, shows the message, and redirects to `/login` after 2.5 seconds.
- **Behaviour:** the restricted user is logged out on their next request (any click or navigation). Background polls marked `skipAuthRedirect` deliberately do not log the user out. Logging out an idle user immediately is logged as I-5.
- Tests: after restriction, the old token gets 401 `ACCOUNT_RESTRICTED`; after re-activation, the user can log in again.

---

## 5. "Verification required" shown on login for a verified user after re-activation

### What was checked (verified)
- The login page shows the verification message only when the backend returns 401 "Please verify your email…", which happens only when `users.email_verified !== 'yes'` (`AuthController.php:139`). The restriction check runs earlier and returns its own "restricted" message.
- Reproduced restrict → re-activate through `PUT /api/users/{id}` with the payload the Customers modal (`NewUserModal`) sends, for a verified customer: `email_verified` stayed `yes` and login succeeded. **This path did not reproduce the bug.**
- Risky defaults that can turn a verified user into an unverified one:
  - `NewUserModal.js:116`: `email_verified: cleanedValues.email_verified || "no"`
  - `organisationUtils.js:103`: `email_verified: original.user?.email_verified ?? "no"`
  - `OrganisationModal.js:82`: the initial `email_verified: "no"`

  Admin edit payloads send `email_verified`, and `UserRequest` accepts `yes|no`. So any edit path where the row is missing that field (a different screen, a stale or partial row) writes `no`. That gives exactly this symptom.
- **Inferred:** one of those paths (Organisations, Admins, or a row without `email_verified`) is what the tester used. Check the account in the affected environment: `SELECT id, email, email_verified, email_verified_at FROM users WHERE id = …`. `email_verified = 'no'` with `email_verified_at` set confirms it.

### Fix (backend and frontend)
- **Backend (the main fix):** in `UserController::update()` and `OrganizationController::update()`, stop admin edits from changing `email_verified`. Drop the field from the validated update data, because no admin screen edits it. Verification then changes only through the verify-email and set-password flows. A verified user stays verified through any number of restrictions, and an unverified user stays unverified (and still sees the message), which is the requested behaviour.
- **Frontend:** remove the `"no"` defaults above and stop sending `email_verified` from the edit payloads.
- **Repair existing data:** list accounts with `email_verified = 'no'` and `email_verified_at IS NOT NULL` (verified once, then flipped). Fix them through a reviewed data migration or one-off command. **Run the list query first and review it.** Do not bulk-update without review.
- Tests: restrict → re-activate through both the user and organisation update endpoints keeps `email_verified = 'yes'`; an unverified user restricted and re-activated still gets the verification message.

---

## Improvements log (not in this release)
Optional items found during this investigation. None is part of this plan's work.

| ID | Improvement | Notes |
|---|---|---|
| I-1 | **Organisation-specific resend email template** | Next release. Give organisations their own editable resend wording, for example a `user_email_templates` type `org_test_link_resent` in Settings > Email Configuration, using the organisation placeholders (`{{organization_name}}`, `{{organization_email}}`, `{{patient_firstname}}`, `{{patient_lastname}}`). Until then, organisations' resends use the super-admin Test Link Resent template (item 1b). |
| I-2 | **Super admin: Resend verification email** | An action on the Users and Organisations lists, shown only for unverified accounts. New `POST /users/{id}/resend-verification` reusing `sendVerificationEmailForUser()`, recorded in the audit trail. |
| I-3 | **Super admin: Mark as verified** | Override for support cases. Must be audited, because it skips proving the user owns the address. |
| I-4 | **Rate-limit the verification resend endpoints** | `/resend_email_verification_link` and `/resend-verification-by-token` have no limiter today. Add one keyed like the other mail senders in `AppServiceProvider::configureRateLimiting()`. |
| I-5 | **Log out an idle restricted user immediately** | With item 4 the user is logged out on their next request. Logging out an idle screen would need a periodic session check in the SPA. |
| I-6 | **`POST /test-invitations/send` with `unique_test_id`** | This resend path still sends the regular invitation template, but the SPA never calls it. Align it with item 1b, or remove it. |
| I-8 | **Start / Resume email for in-clinic (dashboard) tests** | Removed from Part 1 after review. It would need its own organisation setting (not "Send Test Email to Test Takers", which organisations turned on for partner links); opening the emailed link would have to end or lock the clinic's session (today it does not); and a two-eye test would need both eyes' links (today only the left eye's ID is returned). |
| I-7 | **Verification code and expiry in the invitation resend email** | Next to-do. Add `{{verification_code}}` and `{{expires_at}}` to `test_link_resent` as read-only system values filled in automatically, as the `test_link` / `org_test_link` templates already do. Update the default body with a guarded data migration that leaves edited rows alone. The registered-test resend has no code, so the renderer must drop a paragraph whose placeholder resolves to empty. Until this ships, a test taker who lost the original invitation email cannot use a resend on its own. |

## Also blocking

`tests/Feature/PatientExportTest.php` on `develop` declares `invitation()` twice (lines 1207 and 1375). That is a PHP fatal error, so `composer test` / `php artisan test` cannot load the suite. It needs fixing before this work can show green tests. Until then, run individual files with `vendor/bin/phpunit <file>`.

## Order of work
1. Backend: items 4, 5, 1a/1b and 3, with their tests. Restriction is the security-relevant one.
2. Frontend: items 2 and 5 (`AddPatient.js`, `VerifiedDefaultUser.js`, the user and organisation modals).
3. Data repair for item 5, after the review query.
