# Part 1 of 2 — Test emails (low risk)

**Branch:** `fix/test-emails-part-1` in TCV-Backend (`7cf874d6` + review fix `563de816`) and TCV-Frontend (`b829a16` + review fix `9ab27a7`), cut from `develop`.
**Merge first.** Part 2 (`fix/account-restriction-part-2`) is built on top of this branch.
Background and decisions: [test-email-and-account-restriction-fixes.md](test-email-and-account-restriction-fixes.md) (items 1–3).

## Why it is low risk
- It changes **what three emails say and which link they carry**. Nothing here runs on ordinary requests, and nothing touches login, sessions, tokens or permissions.
- Every change is behind one action: a resend, a test-taker registration, or a verification email send.
- The Start / Resume email is fire-and-forget: it cannot block or fail the test flow, and it never shows an error popup.
- Data change: one guarded migration that adds a sentence to one email template row. It is reversible and skips a row that already states an expiry.

## What changed

| # | Issue | Repo · files |
|---|---|---|
| 1 | A resent invitation looked like a first invitation, and the resend broke the original email's link and code | Backend: `TestInvitationController::resendUnregisteredInvitation()`, `TestInvitationMailer` (`sendResend()`), comment in `SendTestInvitationEmailsJob` |
| 2 | The "Start or Resume Test" email was not sent when a test taker registered from an emailed invitation (only the integrated-partner Test URL form sent it) | Frontend: `AddPatient.js`, `VerifiedDefaultUser.js`, `testResumeSlice.js` |
| 3 | The "Verify Your Email Address" email did not say when its link expires | Backend: migration `2026_11_03_000001_state_link_expiry_in_verification_email`, `EmailTemplateSeeder`, `AuthController::sendVerificationEmailForUser()` |

### Dev review — what to look at
1. **Resend keeps the token and code** (`resendUnregisteredInvitation()`): only `expires_at` and `resend_count` change. The old rotation is removed on purpose; Cancel followed by Send is still how a leaked link is revoked.
2. **An invitation that was already mailed, from any sender (organisations included), gets Test Link Resent** on a resend (`TestInvitationMailer::send(..., isResend: true)` → `sendResend()`). `{{test_url}}` is that invitation's own `/test-invitation/verify/{token}`. A first send is unchanged. The resend has **no code or expiry line** by decision (improvement I-7). **Review fix:** an invitation still `pending` (queued, or deferred after the mail host did not answer) was never received, so its resend sends the **full first invitation**, code included. Otherwise marking it `sent` would take it out of the batch and the code would never arrive.
3. **Start / Resume email** (`AddPatient.js` `sendResumeEmailIfEnabled()`): sent only on the **email-invite route**, when the organisation's `send_test_email_to_patients` rule is visible, the form has an email, and the test was **newly assigned** (not re-sent for an already-active test). `sendTestResumeEmail` passes `skipErrorPopup: true`. **Review fix:** it is deliberately **not** sent from the dashboard flow. There the clinic runs the test on its own device: the emailed link would open a second, unsupervised way into the same test (opening it does not end the clinic's session), and for a two-eye test it would carry only the left eye (improvement I-8).
4. **Migration**: inserts the sentence after the first paragraph that holds `{{verification_link}}`, even when a Super Admin has edited the row. It is idempotent, and `down()` removes only that sentence.
5. **24-hour window**: a resend that reuses a still-valid verification token now resets its expiry to 24 hours, so the sentence is true. Known follow-up: the resend endpoints have no rate limit (improvement I-4).

### Automated checks (run on this branch alone)
- Backend: all 1448 tests run (all except `PatientExportTest.php`, which already has a fatal error on `develop`). The only 7 failures also fail on `develop`. New tests: `ResendUnregisteredInvitationTest` (incl. the never-mailed case), `VerificationEmailExpiryMigrationTest`, `RegistrationVerificationEmailTest` (+1), `SessionOwnershipTest` (+1). The review fix added 2 tests; the invitation tests (200) were re-run on Part 1, and only failures already on `develop` remain.
- Frontend: all 368 tests pass. The only failing suite is the existing `App.test.js` module error. No new lint warnings, and the production build succeeds.

## QA test cases

**Setup:** one customer account and one organisation account, each with credits. For the organisation, turn on **Send Test Email to Test Takers** (Integrated Partners → edit → settings). Use mailboxes you can read.

### A. Resent invitation (issue 1)
| # | Steps | Expected |
|---|---|---|
| A1 | As the customer: Send Test to an address. Then Patients → Invited → **Resend** on that row. | A second email arrives with subject **"Your Test Link (Resent)"** (the Super Admin's Test Link Resent template), not the normal invitation. |
| A2 | Open the **original** email's link and enter its code. | It works: the test taker can register. |
| A3 | Open the **resent** email's link and enter the code from the original email. | It works (the link and code are the same invitation). |
| A4 | Repeat A1–A3 as the **organisation**. | Same result: the resend uses Test Link Resent, not the organisation's invitation wording. |
| A5 | Super Admin edits Test Link Resent (subject and body) and saves. Resend again. | The edited subject and body arrive. |
| A6 | Let an invitation pass its expiry date (or use an expired one), then Resend. | The original link and code work again, with a new 7-day window. |
| A7 | Send a **new** invitation (not a resend). | Unchanged: the normal invitation email with its code. |
| A8 | Patients → Invited → Resend on a registered or migrated test ("Resend Test Email"). | Unchanged: Test Link Resent with the test link. |
| A9 | Resend an invitation that has **not been mailed yet** (for example while mail delivery is down). | The full **first invitation** arrives, with its code; not "Your Test Link (Resent)". |

### B. Start / Resume email (issue 2) — organisation with the setting on
| # | Steps | Expected |
|---|---|---|
| B1 | The organisation sends an invitation; the test taker opens it, enters the code and registers. | A **"Start / Resume Test"** email arrives at the address entered on the form. |
| B2 | The organisation user adds a test taker from the dashboard and starts the test. | **No** Start / Resume email (in-clinic tests are not emailed; see I-8). |
| B3 | The integrated-partner Test URL form. | Unchanged: the email arrives. |
| B4 | Turn the setting **off** and repeat B1/B2. | No Start / Resume email. |
| B5 | A customer (not an organisation) adds a test taker and starts a test. | No Start / Resume email (customers have no such setting). |
| B6 | The link in the email. | It opens the test and resumes where the test taker left off. |
| B7 | Force a failure (for example, send more than 3 within a minute from one session to hit the rate limit). | No error popup appears over the test; the test starts normally. |
| B8 | Submit the registration form again for a test that was already started. | No new email; the link in the first email still works. |

Not in scope (tell the product owner): the dashboard flow sends no Start / Resume email, for new or existing patients (improvement I-8).

### C. Verification email expiry (issue 3)
| # | Steps | Expected |
|---|---|---|
| C1 | Register a new account. | The "Verify Your Email Address" email shows **"This link expires in 24 hours."** directly below the link or button. |
| C2 | On the login screen, click **Resend Verification Link**. | The new email also shows the line; the link in the **first** email still works. |
| C3 | Use a verification link older than 24 hours. | "Link Expired" popup with Resend (unchanged behaviour). |
| C4 | A Super Admin who edited the template earlier: check the template after deploy. | The line has been added below the link; the rest of the edit is untouched. |

## Deploy notes
- Backend: run migrations (`2026_11_03_000001`).
- No configuration changes. Backend and frontend can be deployed in either order.
