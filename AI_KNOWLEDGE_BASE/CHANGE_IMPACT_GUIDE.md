# Change Impact Guide — "if I touch this, what breaks?"

Read the row for the thing you are about to change **before** you change it.

---

## Highest blast radius

| If you change… | It reaches | Why |
|---|---|---|
| **`app/Exceptions/Handler.php`** | every endpoint **and** the SPA's error handling | It currently collapses 403/404/405/429 into 500. Fixing it changes status codes the SPA has work-arounds for. Two-repo change ([ERROR_HANDLING.md](ERROR_HANDLING.md)). **`ws-402` (merged 2026-09-07) takes the first slice**: `AuthorizationException` only → 403. Verified safe for that case — `errorHandler.js` already has a `case 403:` — but the same caution applies to any *other* type you unwrap next |
| **`RestrictIpMiddleware`** | every request, including unmatched routes | Global middleware with a DB query. A bug here takes the API down; a slow query slows everything. Since `ws-449` it is also the SPA boot gate (`GET api/access-check` has no logic of its own) |
| **`trustProxies()` in `bootstrap/app.php`** / either `nginx.conf` | every rate limiter, `restricted_ips`, `audit_logs.ip_address` | ☠️ Since `ws-449` the private-range default is always on, and both `TCV-Website/nginx.conf` (the edge) and `TCV-Frontend/nginx.conf` still trust `X-Forwarded-For` from anyone. Change all of them together — [S-16](SECURITY.md#status-2026-09-17--both-backend-halves-shipped-the-frontend-nginx-precondition-did-not) |
| **The `login` rate limiter's name or key** | the `auth.account_locked` audit dedup | `accountLockedResponse()` recomputes `md5('login'.$key)` — Laravel's own cache key for a named limiter. Rename or re-key the limiter and the dedup silently misses ([CONFIGURATION.md](CONFIGURATION.md)) |
| **`FlexibleAuthMiddleware`** | 23 endpoints × 4 token tiers | Every patient-facing flow. The merged request keys (`test_invitation_id`, `org_id`, `unique_test_id`) are read by controllers downstream |
| **`ApiResponse`** | ~everything that returns JSON | Its shape is what the SPA parses. **`ws-402` (merged 2026-09-07) appends `array $replace = []` to `success()` and `error()`** — additive and last, so existing calls are unaffected, but note it lands *behind* `success()`'s dead `$meta`, so an interpolating call must pass `[]` for meta ([HELPERS.md](HELPERS.md#apiresponse)) |
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
| `Credits::addCreditsToUser()` | Called by admin grant, purchase (`BasePaymentProvider`), cancel, and the two **automated** invitation refunds (`markFailed()`, `expireStaleInvitations()`). ☠️ Third arg `$creditedBy` (2026-09-15) defaults to the sentinel `false` → `auth()->id()`; automated callers **must** pass `null`, because a sweep runs inside another customer's request. **`ws-402` (merged 2026-09-07)** adds `original_source` to the payload on the refund paths |
| `BasePaymentProvider::createTransactionRecord()` | Creates the `Credits` grant **and** the `Transaction` + `TransactionDetail`. Discount usage is counted from `transaction_details`, so a change here changes discount limits |
| `Credits::revokeGrant()` / `traceConsumedOrigin()` / `getGrantAllocation()` — **`ws-402`, merged 2026-09-07** | `CreditsController::destroy()` and `CreditsPolicy::delete()` both changed shape around these. `revokeGrant()` is the only writer of `SOURCE_ADMIN_REVOKED`/`SOURCE_ADJUSTMENT` rows; `traceConsumedOrigin()` is a read-only FIFO replay with no stored link, so it is only as accurate as `credit_consume`'s history. `getGrantAllocation()` covers `active()` grants only, so a row missing from it means "not computed" — the controller sends `null`, and a consumer that reads that as `0` reports a spent-and-expired grant as untouched. See [CONTEXT/CREDITS_CONTEXT.md](CONTEXT/CREDITS_CONTEXT.md) |
| `Credits::revokeGrant()`'s counter-entry — **`ws-402`, merged 2026-09-07** | ☠️ It **must** inherit the grant's `has_expiry`/`expiry_date`. Written non-expiring, the negative row outlives the positive one and the active total goes negative by the revoked amount the day the grant expires — the hidden-debt bug the method exists to prevent, reintroduced by the fix for it. ⚠️ **The "no backfill needed" escape hatch is gone**: `SOURCE_ADMIN_REVOKED` has existed on `develop` since 2026-09-07, so from the first admin claw-back on a deployed environment there are real counter-entry rows, and a regression here now needs a data fix, not just a code fix. TCV is not yet in production, so the exposure today is QA data only |
| `Credits::countsTowardBalance()` vs `hasExpired()` — **`ws-402`, merged 2026-09-07** | Not opposites. They disagree on `has_expiry = 1, expiry_date = NULL`, and branching on the wrong one left such a grant permanently undeletable behind a false 422. `countsTowardBalance()` mirrors `scopeActive()`; keep them in step |
| `Credits::settleNegativeBalance()` / `credits:settle-negative-balances` — **`ws-402`, merged 2026-09-07** | ☠️ **Read trap 10 in [CONTEXT/CREDITS_CONTEXT.md](CONTEXT/CREDITS_CONTEXT.md) first.** Both subtract all-time consumption from `active()` grants. That asymmetry looks like a bug, has already been reported as one, and is correct — "correcting" it silently re-opens the hole where a user's next purchase vanishes. Two tests pin the behaviour down |
| `price_details` | `DiscountCode::priceTiers()` matches credit packages against it; `PricingAuditService` logs changes |
| `GET api/user/credits` (`UserController::getUserCredits`) | It is now on a **60 s timer per open SPA tab** (`useCreditsSync.js`, ws-397), not just a page-load call. Slowing it or changing its `data.credits` shape hits the header on every poll ([FRONTEND.md](FRONTEND.md#the-credit-balance-is-polled-not-pushed)) |
| `slices/userCredits/userCreditSlice.js` `loading` / `initialized` | Four components read `loading` (header, `Home.js`, `CreditPage.js`, `Profile.js`). `initialized` latches for one identity, so `loading` fires **once per page load** — restoring a per-fetch spinner re-breaks the poll's silent refresh. `clearedState()` resets it on logout and on an owner change; `ws-480`'s purchase gate reads `initialized` as "this user's balance is known", so keeping it across identities would flash the purchase flow at an unlimited account |

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
| `EmailTemplateService::getTemplateForUser()`'s hard-coded fallback | It is a live send path used when the admin default row is missing — keep its link anchored and styled like the seeded templates. ⚠️ `ws-373` (body) and `ws-400` (subject) both rewrote this one `return` block and **conflicted** on merge; the resolution now on `develop` keeps both sides ([INVITATION_CONTEXT](CONTEXT/INVITATION_CONTEXT.md)) |
| The `{{…}}` placeholder names | Three writers substitute them by `str_replace`, and `2026_08_31_000001` anchors them by exact string. Renaming one means the controller, the seeder **and** that migration's `TARGETS` map — plus, since `ws-400`, `LINK_PLACEHOLDER` in the SPA's `emailPlaceholders.js` and the required-token lists in the three template forms. `ws-401` (on `develop`) adds a second such migration, `2026_09_03_000002` (bracket normalisation), whose `TOKEN_MAP` targets are filtered through `EmailTemplatePlaceholders::known()` — there a rename silently drops the mapping rather than writing a dead token |
| Which `type` a placeholder belongs to (`catalogue()` / `unlisted()`) | It is not just editor copy: it is what every save path validates against, so moving a token between types can make **stored** rows unsaveable. Check `templates:check-placeholders` across both tables before and after ([INVITATION_CONTEXT](CONTEXT/INVITATION_CONTEXT.md#placeholder-validation-ws-404)) |
| The SPA's `RichTextEditor` `formats` whitelist | Widening it changes what survives a template save, which is the whole reason the send path restyles and linkifies. Narrowing it strips more markup out of existing templates. Since `ws-400` it must also carry `PLACEHOLDER_FORMATS` wherever `lockPlaceholders` is set, or every system-value chip vanishes on save. A caller passing its own `formats` still gets the blots appended — passing a *narrower* list is how you'd lose them |
| The default test-invitation **subject or body** | Three edits in lockstep, or dev and prod drift: `AdminSettingsSeeder` (fresh DBs only), the `EmailTemplateService` fallback, and a match-on-old-value data migration for deployed rows (`ws-400`'s `2026_08_29_000001` is the template to copy). ⚠️ **Decide per row:** `test_email_templates` has a `test_link` *and* an `org_test_link` admin default, and these migrations are type-scoped — `ws-400` moved only the first and stranded the second until `ws-456` ([INVITATION_CONTEXT](CONTEXT/INVITATION_CONTEXT.md)). The fallback is type-agnostic, so it takes one edit however many rows you move |
| `hasTestLinkButton()` / `emailPlaceholders.js` | The single definition of "this template has a working Start Test button", used by all three template forms. It parses HTML, so it needs `DOMParser` — it degrades to a substring check rather than throwing under SSR or a node-environment test. `src/components/richTextEditor/emailPlaceholders.test.js` is the guard rail ([TESTING.md](TESTING.md)) |
| Anything that reads a template body into an editor | `toEditorHtml`/`toTemplateHtml` must stay a **fixed point**, and their first-render rewrite must reach the caller through `onNormalize`, not `onChange` — otherwise an untouched form reads as dirty ([INVITATION_CONTEXT](CONTEXT/INVITATION_CONTEXT.md)) |

---

## Invitation delivery (`ws-404`, on `develop` since 2026-09-15)

| Change | Also check |
|---|---|
| `SendTestInvitationEmailsJob::deferralReason()` and its helpers — `isConnectFailure()`, `isTransientReply()`, `isSenderQuotaRejection()`, `isSesRequestThatNeverDelivered()`, `isFailoverExhaustion()` | ☠️ **This decides whether a customer is refunded and a patient un-invited.** Too narrow → `failed`, which revokes the invitation and refunds the credit (the QA 550 hourly cap wrote off 286 rows this way). Too broad → a post-DATA error is deferred and the patient is **mailed again every sweep**. `looksLikeConnectionTrouble()` is deliberately broader and governs retry *pacing only* — do not merge the two. Matching is by message text (Symfony code 0; SES wraps everything in one `TransportException`). Pin any edit with `BatchedInvitationSendTest` and `InvitationSendReviewFixesTest` ([JOBS.md](JOBS.md#-connection-failure-is-not-address-rejection)) |
| `mail.invitation_max_deferrals` (default 36) / `countDeferral()` | ☠️ A **downtime tolerance** — read it against the 10-minute schedule (36 ≈ 6h). Lowering it makes ordinary SES incidents irreversibly revoke and refund in-flight invitations. The comparison is `>` (36 deferrals allowed). The increment and any write-off must happen **before** `releaseClaim()` |
| `markFailed()`'s `WHERE email_status='sending' AND is_revoked=false` | The only thing stopping a delivered row being revoked, or a cancelled row refunded twice. An unconditional update reintroduces both |
| `SendPendingInvitations::expireStaleInvitations()` | The only refund for rows that expired undelivered — `awaitingDelivery()` excludes them from everything else. Its `is_revoked = false` guard (select **and** update) is what stops it double-refunding a cancel; its `--limit` bound is what keeps a backlog from holding the 20-minute overlap lock |
| `cancelUnregisteredInvitation()`'s claim + 409 | The third settle path. The 409 is new client-visible behaviour with no dedicated SPA arm: `InvitedPatientsTab.js` shows `err?.message || "Failed to cancel invitation."` in an "Error" popup, so whether the user sees "already cancelled" depends on the `cancelUnregisteredInvitation` thunk rejecting with the server message [not traced] |
| `config/queue.php` `database.retry_after` (360) vs `backend-queue --timeout=300` vs job `$timeout` (180) | ☠️ Must stay ordered `$timeout < --timeout < retry_after`. Below the worker timeout, a running batch is re-reserved and sent twice |
| `dispatchEmailBatch()` queue-mode budget | Pass a **duration** (`invitation_queue_batch_budget`) and resolve it in `handle()`. A timestamp computed at dispatch expires for every batch but the first few |
| Compose `profiles: ["workers"]` on `backend-queue` / `backend-scheduler` | Removing it starts both on the next deploy of **every** environment and drains months of LMS deliveries and stranded invitations. Follow the runbook in [DEPLOYMENT.md](DEPLOYMENT.md) instead |
| `entrypoint.sh` `RUN_INIT` | Worker containers must stay `false` (hourly `--max-time` restarts); the web container must stay `true`. Don't fall back to an argv string compare — it skipped migrations on `php-fpm -F` |
| `MAX_CONSECUTIVE_CONNECTION_FAILURES` / `HOST_STANDDOWN_SECONDS` | The stand-down is a **static**, shared by every batch in the FPM child and surviving across requests — which is why it expires on a timestamp and why `resetHostStandDown()` exists for `invitations:send-pending`. A per-instance rewrite silently makes each of a 500-address send's 20 batches rediscover the same outage |
| Anything that resets `$consecutiveConnectionFailures` | A `failed` result **must** reset it alongside `sent`: a rejection aimed at one address is not evidence about the host, and counting it trips the breaker on a healthy server |
| `mail.invitation_dispatch` | Setting `queue` requires `backend-queue` running — which means `COMPOSE_PROFILES=workers`, off by default — or batches pile up in `jobs` silently. It also makes `$tries`/`$backoff`/`failed()` live — check `failed()` still only *releases* claims and never reopens a `sent` row ([JOBS.md](JOBS.md)) |
| `TestInvitation::scopeAwaitingDelivery()` | Three readers — `invitations:send-pending`, `SweepPendingInvitationsJob`, and the job's own re-query. It exists so they cannot drift; the `is_revoked` / `expires_at` clauses are what stop a cancelled-and-refunded invitation being mailed ([CONTEXT/INVITATION_CONTEXT.md](CONTEXT/INVITATION_CONTEXT.md)) |
| The `failover` mailer chain | ☠️ Never put `log` or `array` in it. Both accept every message and report success while delivering nothing, so a broken primary reads as a clean send ([THIRD_PARTY.md](THIRD_PARTY.md)) |
| **Adding a method to a queued job** | ☠️ Check the name against `InteractsWithQueue` first — `release()`, `attempts()`, `delete()`, `fail()`. A class method silently shadows the trait's with no error, and queue middleware call `$job->release($seconds)` on the handler. `SendTestInvitationEmailsJob` carried exactly this collision until `ws-404` renamed it `releaseClaim()` ([JOBS.md](JOBS.md)) |
| **Adding queue middleware to `SendTestInvitationEmailsJob`** | It is the natural next step once `mail.invitation_dispatch=queue` — and it is what would have detonated the `release()` collision above. Re-check the job's method names against the trait before adding one |

---

## Admin grids — sorting and paging (`ws-502`, unmerged)

| Change | Also check |
|---|---|
| **`components/table/TableWithGlobalFilter.js`** | ~13 pages render it. On `ws-502` it forces `disableSortRemove` whenever `useServerSorting` is set, so every server grid's header toggles asc ↔ desc only. Its sort effect emits `onSort` only for a *changed* `id:direction` (`lastEmittedSortRef`), and the optional `currentSort` prop syncs the header from the page through the same ref. Redemptions, which renders the grid twice, depends on it ([FRONTEND.md](FRONTEND.md#server-sorted-grids-ws-502-unmerged)). Pinned by `TableWithGlobalFilter.test.js` |
| A list endpoint's `ORDER BY` | keep the trailing primary-key tiebreak, qualified if the query joins (`organizations.id`, `td.id`, `users.id`). SQLite tests pass without it, and MySQL pages repeat rows ([FRONTEND.md](FRONTEND.md#server-sorted-grids-ws-502-unmerged)) |
| A list endpoint's sort allow-list or `$sortMap` | the SPA column `id`s that send those keys. A removed key keeps returning 200, sorted by the default instead |
| `createPaginatedCrudSlice`'s fetch reducers | `listRequestId` compared with `action.meta.requestId` is what stops an old response overwriting a newer sort — credits, `userTestsReport`, `patientTests`. It is strict, so tests load lists through the thunk, not a hand-dispatched `fulfilled` |
| `UserTestsReportService::buildTransformedTests()` | the patient drill-down **and** its Excel/PDF exports are ordered by `sortTransformedTests()` at the end of it. Filter before that call, never after ([REPORTING_CONTEXT](CONTEXT/REPORTING_CONTEXT.md) trap 7) |
| `createCrudSlice`'s `createItem` (still `push`) | any page that shows newest first must re-order itself — Restricted IPs does ([FRONTEND.md](FRONTEND.md#redux)) |

---

## Credit purchase gate (`ws-480`, unmerged)

| Change | Also check |
|---|---|
| **`pages/UserPannel/CreditPage/CreditPage.js`** | Three flags decide the whole page: `hasUnlimitedCredits` (string compare), `purchaseAllowed` (`!isImpersonating && !hasUnlimitedCredits`) and `purchaseGateResolved` (`isImpersonating \|\| creditsSettled`). `purchaseAllowed` also overrides `?tab=`, so a new tab or a new reason to block purchasing goes through those flags, not through a fresh condition per JSX block. ☠️ The gate must stay on `settled` — the `initialized \|\| !!error` it replaced pinned the page on `Loading credits…` for good on any failure without a JSON `message` ([FRONTEND.md](FRONTEND.md#credit-purchase-gate-ws-480-unmerged)) |
| **`pages/UserPannel/CheckOutPage/Checkout.js`** | Its `blockedFromCheckout` is deliberately **not** the same expression — it waits for `creditsInitialized` before acting on the unlimited case, so a slow balance read cannot bounce a paying user off checkout. The `createSetupIntent()` dispatch carries a **second** condition, `creditsSettled`, without which an unlimited account opening this route directly sets up a payment method in the window before the redirect. Hiding the credits-page tab is not a guard: this route is reachable by URL and by history |
| `slices/userCredits/userCreditSlice.js` — `settled`, `initialized`, `error`, `clearedState` | ☠️ **`settled` is the "has the balance question been answered?" flag; `initialized` means a read *succeeded*.** Gates use `settled`, code that needs the number uses `initialized`. Do not re-derive either from `error`: it is cleared on every `pending`, and the thunk leaves it `undefined` for any failure without a JSON `message`. All three reset only through `clearedState()` (logout, or a `loginSuccess`/`setImpersonationUser` with a different `ownerId`); making the slice keep them across identities would show the purchase flow to an unlimited account on the first paint |
| A **new** refusal in a payment handler | Return the response; do not throw. Every one of these handlers' `catch` blocks turns an exception into a 500 carrying the message (*"Payment failed"* on the legacy pair), which reads as an outage rather than a refusal ([BILLING_CONTEXT trap 9](CONTEXT/BILLING_CONTEXT.md#9--the-unlimited-purchase-refusal-is-on-the-deprecated-surface-ws-480)) |
| A **new** "is this account unlimited?" test | Call `Credits::hasUnlimited($userId)`, added with `ws-480`'s fix. Do not write a fifth `getAvailableCredits() === 'Unlimited'` comparison — the string compare is only for code already holding that return value, and copies of a "does this grant count?" predicate drifting apart is what left a `has_expiry = 1, expiry_date = NULL` grant undeletable ([CREDITS_CONTEXT](CONTEXT/CREDITS_CONTEXT.md#-traps)) |
| Anything that must hold server-side on the purchase path | ☠️ **Four handlers, not one**: `PaymentController::initializePayment()` *and* `confirmPayment()` (the live path — separate requests, and only `confirm` writes the grant), plus both legacy `StripePaymentController` halves, which no SPA code calls but which are still routed. `ws-480` originally put its 422 on the legacy `createPaymentIntent()` alone, so the rule did not bind where money moves. Put a refusal ahead of `DiscountCodeService::validate()` in `confirmPayment()`, or it writes discount audit rows for a checkout that never happened |
| **`components/NewUserModal.js`** — the Assigned Tests block | The at-least-one-test check must stay *before* `createUser`/`updateUser` dispatches: the backend rule lives on `POST api/user/tests/bulk-update-assignment`, which only runs after the row exists, and the modal swallows that call's rejection with `console.error`. It is also conditioned on `allTests.length > 0`, so a failed test-list load does not block account creation |

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
| The `PUT api/profile` response's `user` shape | since `ws-407` it feeds `auth.user` **and** `localStorage.auth`, not just the profile slice — dropping a key there stales out Checkout's billing pre-fill silently, and changing `usertype` there re-roles the client session ([AUTH_CONTEXT](CONTEXT/AUTH_CONTEXT.md#-traps) trap 10) |
| The phone format rule | one definition in the SPA's `src/utils/validation.js` (`normalizePhone` / `getPhoneError` / `phoneForSubmit`), consumed by checkout billing **and** Settings ▸ Profile, with no backend format rule behind either. Widening the digit cap past 15 also needs `users.phone_no` (`varchar(20)`) and `UpdateProfileRequest`'s `max:20` — the character cap, not the digit cap, is what bites first. `src/utils/validation.test.js` is the guard rail ([BILLING_CONTEXT](CONTEXT/BILLING_CONTEXT.md) trap 8) |
| A patient-field format rule | there are **two** validators behind one Add Patient screen — `formUtils.js`'s `FORMAT_VALIDATORS` (org/field-rules branch) and the inline checks in `usePatientForm.js` (edit + fallback). `ws-407` added `EMAIL_PATTERN` to the first only ([PATIENT_CONTEXT](CONTEXT/PATIENT_CONTEXT.md#-traps) trap 8) |
| `countries` table data / `DropdownValuesController::getCountriesWithStates()` — `fix/countries-list-update`, unmerged | Consumed with no shared caller by `TCV-Website`'s `DistributorSignupClient.jsx` and `AuthModal.jsx`, and by `TCV-Frontend`'s `loginSlice.js` `fetchCountries` (Register, Checkout, `NewUserModal`, `OrganisationModal`, Profile/Settings, Admin tables) — fix once on the backend, it reaches all three. Neither frontend sorts the list; order is whatever the endpoint returns. ☠️ `WorldSeeder` truncates and reseeds all ~250 rows from the `nnjeim/world` package on every run, so a hand-fix to already-seeded data (e.g. a retired country name) must live in the seeder itself, not only in a migration — otherwise a fresh `migrate --seed` / `migrate:fresh --seed` silently reverts it, with the migration already marked applied ([THIRD_PARTY.md](THIRD_PARTY.md#country-and-state-reference-data-nnjeimworld)) |

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
draftFromDate            date-picker-input       startOfToday
```

☠️ The last row is the **report date filter, which exists in three copies** — `UserTests.js`,
`UserTestDetail.js`, `DiscountCode.js`, plus a disabled-state block in three separate stylesheets. Grep
before you edit; there is no shared component (ws-455,
[FRONTEND.md](FRONTEND.md#report-date-filters-are-three-copies-ws-455)).

---

## After the change

1. `php artisan test` — real coverage exists only for **LMS** and **credit history**
   ([TESTING.md](TESTING.md)). If your change is elsewhere, say the suite doesn't cover it rather than
   claiming it passes.
2. `composer lint` (Pint check) — and remember **CI does not fail on lint or run tests**.
3. Re-run the KB generator and diff `PUBLIC_ROUTE_AUDIT.md`, `CONTRACT_DRIFT.md` and
   `FRONTEND_ROUTE_INDEX.md` ([GUIDES/HOW_TO_REGENERATE.md](GUIDES/HOW_TO_REGENERATE.md)).
4. Hand-update only the affected KB prose. **Never regenerate the whole KB.**
