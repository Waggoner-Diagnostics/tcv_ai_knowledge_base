# Change Impact Guide — "if I touch this, what breaks?"

Read the row for the thing you are about to change **before** you change it.

---

## Highest blast radius

| If you change… | It reaches | Why |
|---|---|---|
| **`app/Exceptions/Handler.php`** | every endpoint **and** the SPA's error handling | It currently collapses 403/404/405/429 into 500. Fixing it changes status codes the SPA has work-arounds for. Two-repo change ([ERROR_HANDLING.md](ERROR_HANDLING.md)). **`ws-402` (unmerged) takes the first slice**: `AuthorizationException` only → 403. Verified safe for that case — `errorHandler.js` already has a `case 403:` — but the same caution applies to any *other* type you unwrap next |
| **`RestrictIpMiddleware`** | every request, including unmatched routes | Global middleware with a DB query. A bug here takes the API down; a slow query slows everything |
| **`FlexibleAuthMiddleware`** | 23 endpoints × 4 token tiers | Every patient-facing flow. The merged request keys (`test_invitation_id`, `org_id`, `unique_test_id`) are read by controllers downstream |
| **`ApiResponse`** | ~everything that returns JSON | Its shape is what the SPA parses |
| **`config/sanctum.php` `expiration`** | every logged-in session | There is no refresh flow; the SPA just gets 401s |
| **`User::SUPER_ADMIN/CUSTOMER/ORGANIZATION`** | policies, gating, the SPA's `USER_ROLES` and `RouteConfig` | Two-repo change ([FULLSTACK_MAP.md](FULLSTACK_MAP.md)) |
| **`unique_test_id` format/generation** | test execution, LMS sessions, resume tokens | Three subsystems join on that string with no FK ([MODEL_RELATIONSHIP.md](MODEL_RELATIONSHIP.md)) |
| **`config('app.frontend_app_url')`** | every patient-facing email link | Verification, password setup, resume, org Test URL |

---

## Money and credits

| Change | Also check |
|---|---|
| `Credits::getAvailableCredits()` / `getTotalUserCredit()` | It returns `int|string` — **`'Unlimited'`**. Every caller must guard. Callers: `CreditsController`, `TestInvitationController`, `TestController::assignTest`, `UserController`, `PaymentController` |
| The `if (!$isEmailInvite)` guard in `TestController::assignTest()` | **This one condition is the whole double-charge guard.** It depends on `test_invitation_id` being merged by `FlexibleAuthMiddleware` |
| `CreditConsume::consume()` | Reporting filters on `event_type`, and the two spend paths already use inconsistent values ([CREDITS_CONTEXT](CONTEXT/CREDITS_CONTEXT.md)) |
| `Credits::addCreditsToUser()` | Called by admin grant, purchase (`BasePaymentProvider`), and **two** refund paths. It sets `credited_by = auth()->id()` — which is wrong for the invitation-cancel path already. **`ws-402` (unmerged)** adds `original_source` to the payload on both refund paths |
| `BasePaymentProvider::createTransactionRecord()` | Creates the `Credits` grant **and** the `Transaction` + `TransactionDetail`. Discount usage is counted from `transaction_details`, so a change here changes discount limits |
| `Credits::revokeGrant()` / `traceConsumedOrigin()` / `getGrantAllocation()` — **`ws-402`, unmerged** | `CreditsController::destroy()` and `CreditsPolicy::delete()` both changed shape around these. `revokeGrant()` is the only writer of `SOURCE_ADMIN_REVOKED`/`SOURCE_ADJUSTMENT` rows; `traceConsumedOrigin()` is a read-only FIFO replay with no stored link, so it is only as accurate as `credit_consume`'s history. See [CONTEXT/CREDITS_CONTEXT.md](CONTEXT/CREDITS_CONTEXT.md) |
| `price_details` | `DiscountCode::priceTiers()` matches credit packages against it; `PricingAuditService` logs changes |
| `GET api/user/credits` (`UserController::getUserCredits`) | It is now on a **60 s timer per open SPA tab** (`useCreditsSync.js`, ws-397), not just a page-load call. Slowing it or changing its `data.credits` shape hits the header on every poll ([FRONTEND.md](FRONTEND.md#the-credit-balance-is-polled-not-pushed)) |
| `slices/userCredits/userCreditSlice.js` `loading` / `initialized` | Four components read that flag (header, `Home.js`, `CreditPage.js`, `Profile.js`). `initialized` latches, so `loading` fires **once per page load** — restoring a per-fetch spinner re-breaks the poll's silent refresh |

---

## The test flow

| Change | Also check |
|---|---|
| `TestExecutionService::submitAnswer()` | termination, progression, completion and LMS delivery all hang off it |
| Adding a `TestAnswer::SKIP_*` constant | the `havingRaw` in `getSessionDetails()` that defines "section skipped", and `getSectionPlatesWithProgress()` |
| `finalizeTestIfCompleted()` | it is the **only** writer of `result_json` and the only dispatcher of `TestCompleted` |
| `ColorVisionDiagnosisService` | **does not** affect completed tests (`result_json` is write-once). And its dead JS twin lives in the SPA — delete, don't sync |
| `test_sections.section_instruction` wording | `TestHelper::extractEyeFromSectionInstruction()` regexes `Eye: (OU|OD|OS)` out of that free text ([HELPERS.md](HELPERS.md)) |
| `SecureImageService` constants | 880 (cache) **must** stay below 900 (URL validity) ([CACHE.md](CACHE.md)) |
| `TestAssignmentService::createBothEyesTests()` | `parent_test_id` pairing, the `OS`-is-canonical rule, and the pending→inprogress promotion |
| **Renaming a row in the `tests` table** | the title is load-bearing in **both** repos. `routeCalculation()` matches `'FAA Color Vision Test'` and `'Baseline Test'` by exact string; the SPA's `HIDDEN_TEST_TITLES` / `ORG_EXCLUDED_TEST_TITLES` sets do too. A rename silently changes the diagnosis algorithm *and* un-hides the test |
| Adding a section skip path | `ColorVisionDiagnosisService` drops `is_skipped` sections from the severity breakdown — a new skip that does not set that flag scores as a failure |

---

## Auth surface

| Change | Also check |
|---|---|
| `AuthController::login()`'s super-admin ability array | **every `tokenCan()` in `TestPolicy` and `OrgPolicy`**. The two lists must match ([POLICIES.md](POLICIES.md)) |
| Adding a policy method with a new ability | same — plus impersonation tokens have neither |
| `users.email_verified` vs `email_verified_at` | two flags, two writers, one gate ([S-08](SECURITY.md#s-08--two-email-verification-systems-that-disagree)) |
| Password brokers | `users` and `setup` share `password_reset_tokens`; one live token per email |
| Adding a public patient-facing path | **three** places: `routes/api.php` placement, `publicRoutes.js`, and `isPublicRoute()` in `AxiosInstance.js` |
| `POST api/verify-password` | Its only caller is the SPA's Patients-menu prompt, which reads a 200 as permission to navigate. It persists nothing, so no backend gate can be built on it ([FRONTEND.md](FRONTEND.md#the-patients-menu-password-prompt-is-client-side-only)) |
| Adding a route under `/user-panel/patients*` or `/user-panel/patient-tests*` | a **fourth** list — `PATIENT_SECTION_PATHS` in `Header.js` (ws-399). Miss it and the Patients menu re-prompts for the password from inside the section |

---

## Email templates and mail bodies

| Change | Also check |
|---|---|
| `App\Support\EmailContent::linkify()` | It runs on **every** DB-template send — verification (`AuthController`), password reset/setup (`ResetPasswordNotification`) and invitations (`TestInvitationController`). A regex mistake here corrupts three live mail paths at once; `tests/Unit/EmailContentTest.php` is the guard rail, run it ([TESTING.md](TESTING.md)) |
| `App\Support\EmailSignature::HTML` | Two readers: `EmailTemplateSeeder` (fresh DBs) and `2026_08_31_000003_restyle_email_template_footers` (existing rows). Editing the constant alone changes the seeder but silently stops the migration matching, so deployed rows keep the old footer. `LEGACY_HTML` exists only for that migration's `down()` — do not "tidy" it away |
| `App\Support\EmailHeader::LEGACY_BRANDING_HTML` | Same two-reader shape as `EmailSignature`: `2026_09_03_000001_remove_branding_header_from_email_templates` matches on it, and `EmailBodyHasNoBrandingHeaderTest` asserts against it. It is the value the `header` column **used to** hold — the seeder now writes `''`. Do not "tidy" it away; the migration stops matching deployed rows if you do |
| Copy in `email_template` (subject / body / footer) | The seeder runs on a **fresh database only**. Every real environment needs a match-on-old-value data migration alongside the seeder edit, or dev and prod drift ([AUTH_CONTEXT](CONTEXT/AUTH_CONTEXT.md)) |
| Anything about an email **subject** | Subjects are branded at send time by `PrefixEmailSubject` on `MessageSending`, not stored branded. Editing a subject in a seeder, a migration or `user_email_templates` changes only the half after `Testing Color Vision - `. Assert on the *sent* message, never the stored row ([EVENTS.md](EVENTS.md)) |
| `AuthController::sendVerificationEmailForUser()`'s `catch` | It swallows `TransportExceptionInterface` and rethrows the rest. Widening that back to a message-substring test re-opens the bug where "**Email** verification template not found" matched `'mail'` and a misconfigured template was reported to the user as success ([AUTH_CONTEXT](CONTEXT/AUTH_CONTEXT.md)) |
| `EmailTemplateService::getTemplateForUser()`'s hard-coded fallback | It is a live send path used when the admin default row is missing — keep its link anchored and styled like the seeded templates. ⚠️ `ws-373` (body) and `ws-400` (subject) both rewrite this one `return` block on unmerged branches and **conflict**; the resolution keeps both sides ([INVITATION_CONTEXT](CONTEXT/INVITATION_CONTEXT.md)) |
| The `{{…}}` placeholder names | Three writers substitute them by `str_replace`, and `2026_08_31_000001` anchors them by exact string. Renaming one means the controller, the seeder **and** that migration's `TARGETS` map — plus, since `ws-400`, `LINK_PLACEHOLDER` in the SPA's `emailPlaceholders.js` and the required-token lists in the three template forms |
| The SPA's `RichTextEditor` `formats` whitelist | Widening it changes what survives a template save, which is the whole reason the send path restyles and linkifies. Narrowing it strips more markup out of existing templates. Since `ws-400` it must also carry `PLACEHOLDER_FORMATS` wherever `lockPlaceholders` is set, or every system-value chip vanishes on save. A caller passing its own `formats` still gets the blots appended — passing a *narrower* list is how you'd lose them |
| The default test-invitation **subject or body** | Three edits in lockstep, or dev and prod drift: `AdminSettingsSeeder` (fresh DBs only), the `EmailTemplateService` fallback, and a match-on-old-value data migration for deployed rows (`ws-400`'s `2026_08_29_000001` is the template to copy) |
| `hasTestLinkButton()` / `emailPlaceholders.js` | The single definition of "this template has a working Start Test button", used by all three template forms. It parses HTML, so it needs `DOMParser` — it degrades to a substring check rather than throwing under SSR or a node-environment test. `src/components/richTextEditor/emailPlaceholders.test.js` is the guard rail ([TESTING.md](TESTING.md)) |
| Anything that reads a template body into an editor | `toEditorHtml`/`toTemplateHtml` must stay a **fixed point**, and their first-render rewrite must reach the caller through `onNormalize`, not `onChange` — otherwise an untouched form reads as dirty ([INVITATION_CONTEXT](CONTEXT/INVITATION_CONTEXT.md)) |

---

## Routes

| Change | Also check |
|---|---|
| Any route addition/move | re-run the generator and **diff `PUBLIC_ROUTE_AUDIT.md`** |
| Adding a literal route near a resource | ordering — `credits/{coupon-code}` is already dead because of this ([ROUTES.md](ROUTES.md#ordering-traps)) |
| Renaming/removing an endpoint | `CONTRACT_DRIFT.md` — the SPA may call it |
| Route caching | routes are cached at boot; a change needs a restart |

---

## Cross-repo

| Change | Repos |
|---|---|
| Response shape | backend + SPA slice/selector |
| `usertype` values | backend + `dataObjects.js` + `routeConfig.js` |
| `error_code` strings | backend middleware + `services/errorHandler.js`'s `errorCodeMap` |
| Org patient-form fields | `organizations` column + `getPatientForm()` + the SPA renderer |
| Patient `gender` values | `Patient::GENDERS` (drives both FormRequests' `in:` rule and `genderLabel()`) + `GENDER_OPTIONS` in the SPA's `testUtils.js` — two lists, one per repo, that must agree |
| Adding an SPA page | `protectedRoutes.js` + `routeConfig.js` + `USER_PANEL_WITH_HEADER` |
| Login/registration payload | backend + SPA + **the website's proxy routes** |

---

## Shared symbols worth grepping before you edit

```
ApiResponse::            HttpStatus::            Credits::getAvailableCredits
CreditConsume::consume   unique_test_id          parent_test_id
FlexibleAuthMiddleware   lms.status              tokenCan(
frontend_app_url         SKIP_                   result_json
Patient::GENDERS         GENDER_OPTIONS          genderLabel(
EmailContent::linkify    EmailSignature::HTML    email_template
hasTestLinkButton        lockPlaceholders        PLACEHOLDER_FORMATS
```

---

## After the change

1. `php artisan test` — real coverage exists only for **LMS** and **credit history**
   ([TESTING.md](TESTING.md)). If your change is elsewhere, say the suite doesn't cover it rather than
   claiming it passes.
2. `composer lint` (Pint check) — and remember **CI does not fail on lint or run tests**.
3. Re-run the KB generator and diff `PUBLIC_ROUTE_AUDIT.md`, `CONTRACT_DRIFT.md` and
   `FRONTEND_ROUTE_INDEX.md` ([GUIDES/HOW_TO_REGENERATE.md](GUIDES/HOW_TO_REGENERATE.md)).
4. Hand-update only the affected KB prose. **Never regenerate the whole KB.**
