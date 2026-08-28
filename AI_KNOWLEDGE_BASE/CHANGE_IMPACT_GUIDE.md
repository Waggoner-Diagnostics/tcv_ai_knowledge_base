# Change Impact Guide — "if I touch this, what breaks?"

Read the row for the thing you are about to change **before** you change it.

---

## Highest blast radius

| If you change… | It reaches | Why |
|---|---|---|
| **`app/Exceptions/Handler.php`** | every endpoint **and** the SPA's error handling | It currently collapses 403/404/405/429 into 500. Fixing it changes status codes the SPA has work-arounds for. Two-repo change ([ERROR_HANDLING.md](ERROR_HANDLING.md)) |
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
| `Credits::addCreditsToUser()` | Called by admin grant, purchase (`BasePaymentProvider`), and **two** refund paths. It sets `credited_by = auth()->id()` — which is wrong for the invitation-cancel path already |
| `BasePaymentProvider::createTransactionRecord()` | Creates the `Credits` grant **and** the `Transaction` + `TransactionDetail`. Discount usage is counted from `transaction_details`, so a change here changes discount limits |
| `price_details` | `DiscountCode::priceTiers()` matches credit packages against it; `PricingAuditService` logs changes |

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
| Adding an SPA page | `protectedRoutes.js` + `routeConfig.js` + `USER_PANEL_WITH_HEADER` |
| Login/registration payload | backend + SPA + **the website's proxy routes** |

---

## Shared symbols worth grepping before you edit

```
ApiResponse::            HttpStatus::            Credits::getAvailableCredits
CreditConsume::consume   unique_test_id          parent_test_id
FlexibleAuthMiddleware   lms.status              tokenCan(
frontend_app_url         SKIP_                   result_json
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
