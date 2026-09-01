# TCV Full-Stack Map — Backend ↔ SPA ↔ Website

> **Lives in the KB.** Read this when a change spans more than one repo — most auth, patient-form,
> credit and test-flow work does — so you update every affected repo's **code** instead of one and
> forgetting the others.

## The three repos

| Side | Repo | What it is | Start here |
|---|---|---|---|
| **API** | `TCV-Backend` | Laravel 12 REST API, MySQL, S3 plates, Stripe, LMS delivery | [README.md](README.md) |
| **Portal + test player** | `TCV-Frontend` | React 18 SPA served at **`/app`** | [FRONTEND.md](FRONTEND.md) |
| **Marketing + entry point** | `TCV-Website` | Next.js 15 site on port **3001**; login/register proxy | [WEBSITE.md](WEBSITE.md) |

No repo auto-updates another. All three are separate deployments.

```
browser ──▶ TCV-Website (:3001)  ──server-side──▶ TCV-Backend   (login, register, countries, logout)
        └─▶ TCV-Frontend (/app)  ──browser────▶ TCV-Backend     (everything else, Bearer token)
```

The website is the only client that talks to the API **server-side**. The SPA talks to it from the
browser with a Bearer token.

---

## Feature → files across the stack

| Feature | Backend (`TCV-Backend/app/`) | SPA (`TCV-Frontend/src/`) | Website | KB pack |
|---|---|---|---|---|
| **Login / register** | `AuthController` · `UserRequest` | `pages/Login.js`, `Register.js` · `redux/slices/auth/` · `apis/AxiosInstance.js` | `components/AuthModal.jsx` · `app/api/auth`, `app/api/register` | [AUTH_CONTEXT](CONTEXT/AUTH_CONTEXT.md) |
| **Password set / reset** | `AuthController::setOrResetPassword` · `ResetPasswordNotification` | `pages/SetPassword.js`, `ResetPassword.js`, `ForgotPassword.js` | — | [AUTH_CONTEXT](CONTEXT/AUTH_CONTEXT.md) |
| **Email verification** | `AuthController::verifyEmail*` | `pages/VerifyEmail.js` | — | [AUTH_CONTEXT](CONTEXT/AUTH_CONTEXT.md) |
| **Patients** | `PatientController` · `Patient` · `AuthController::verifyPassword` | `pages/UserPannel/PatientPage/`, `AddPatient/` · `hooks/usePatientForm.js` · `Header/Header.js` + `components/PasswordVerificationModal.js` (client-only menu gate) | — | [PATIENT_CONTEXT](CONTEXT/PATIENT_CONTEXT.md) |
| **Test execution** | `TestExecutionService` · `TestController` · `SecureImageService` | `pages/UserPannel/TestPage/` + the 6 sibling screens · `redux/slices/tests/testExecutionSlice.js` · `constants/testConfig.js` | — | [TEST_EXECUTION_CONTEXT](CONTEXT/TEST_EXECUTION_CONTEXT.md) |
| **Result / PDF** | `TestResultService` · `ColorVisionDiagnosisService` · dompdf | `pages/UserPannel/ResultPage/` · ⚠️ dead `utils/calculateColorVisionResult.js` | — | [TEST_EXECUTION_CONTEXT](CONTEXT/TEST_EXECUTION_CONTEXT.md) |
| **Email invitations** | `TestInvitationController` | `redux/slices/tests/sendTestSlice.js` · `pages/UserPannel/SendTestModal/`, `PatientPage/InvitedPatientsTab.js` | — | [INVITATION_CONTEXT](CONTEXT/INVITATION_CONTEXT.md) |
| **Resume link** | `TestResumeController` | `pages/ResumeTest/ResumeTest.js` | — | [INVITATION_CONTEXT](CONTEXT/INVITATION_CONTEXT.md) |
| **Email templates** | `EmailTemplateService`/`Repository` · `TestEmailTemplateController` · `UserEmailTemplateController` · `Support/EmailContent`, `EmailSignature` | `pages/Setting/TestEmailTemplates.js` (admin) · `pages/UserPannel/SettingPage/EmailConfigurationPage.js` (per user) · `components/richTextEditor/RichTextEditor.js` · `redux/slices/admin/adminTestEmailTemplateSlice.js`, `userProfile/userEmailTemplateSlice.js` | — | [AUTH_CONTEXT](CONTEXT/AUTH_CONTEXT.md) · [INVITATION_CONTEXT](CONTEXT/INVITATION_CONTEXT.md) |
| **Credits** | `Credits` · `CreditConsume` · `CreditsController` · `UserController::getUserCredits` | `pages/AddCredits.js`, `CreditHistory.js` · `redux/slices/credits/`, `userCredits/` · `hooks/useCreditsSync.js` | — | [CREDITS_CONTEXT](CONTEXT/CREDITS_CONTEXT.md) |
| **Payments** | `PaymentController` · `StripeProvider` · `StripeService` | `pages/UserPannel/CheckOutPage/`, `PaymentStatus/`, `PaymentResponse/` · `services/paymentProviders/` | — | [BILLING_CONTEXT](CONTEXT/BILLING_CONTEXT.md) |
| **Discount codes** | `DiscountCodeService` · `DiscountCodeController` | `pages/DiscountCodes/` · `redux/slices/discount/` | — | [DISCOUNT_CONTEXT](CONTEXT/DISCOUNT_CONTEXT.md) |
| **Organisations / LMS launch** | `OrganizationController::verifySignature` · `Lms/*` | `pages/Organisation/` · `redux/slices/auth/signatureVerificationSlice.js` · `pages/UserPannel/AddPatient/OrganizationPatient.js` | — | [ORGANIZATION_CONTEXT](CONTEXT/ORGANIZATION_CONTEXT.md) · [LMS_CONTEXT](CONTEXT/LMS_CONTEXT.md) |
| **Reports** | `ReportController` · `Reports/*` · `Exports/*` | `pages/Reports/` · `redux/slices/reports/` | — | [REPORTING_CONTEXT](CONTEXT/REPORTING_CONTEXT.md) |
| **IP restriction** | `RestrictIpMiddleware` · `RestrictedIpController` | `pages/Setting/RestrictedIps.js` · `services/errorHandler.js` (`IP_RESTRICTED`) | — | [MIDDLEWARE.md](MIDDLEWARE.md) |
| **Marketing content** | — | — | `app/**` + `views/*Client.jsx` | [WEBSITE.md](WEBSITE.md) |

---

## Shared contracts (must match everywhere)

- **`usertype`: `1` SUPER_ADMIN · `2` CUSTOMER · `4` ORGANIZATION** — no `3`. Defined in
  `User.php` and mirrored in `TCV-Frontend/src/constants/dataObjects.js` → `USER_ROLES`. Changing one
  without the other breaks role gating silently.
- **Auth = Sanctum bearer, 15-minute expiry.** The SPA's token priority is impersonation → query param →
  invitation session → normal ([FRONTEND.md](FRONTEND.md)). There is **no refresh flow** — a longer
  session means changing `config/sanctum.php`, not the client.
- **`error_code: 'IP_RESTRICTED'`** is a literal string contract between `RestrictIpMiddleware` and
  `src/services/errorHandler.js`'s `errorCodeMap`. Renaming it breaks the user-facing message.
- **The public-path list is duplicated.** The backend decides which routes need no token
  (`routes/api.php`); the SPA decides which paths must survive a 401 without redirecting
  (`isPublicRoute()` in `AxiosInstance.js`: `/test-invitation/`, `/organization/`, `/test/resume/`).
  **Adding an unauthenticated patient-facing flow means editing both.**
- **`unique_test_id` is a UUIDv4** and is the de-facto capability for a test session. Never log it to a
  place a patient can see, never put it in a URL the patient might share.
- **Response envelopes are not uniform** — eight shapes exist ([ERROR_HANDLING.md](ERROR_HANDLING.md)).
  New endpoints should use `ApiResponse`; new client code should not assume a single shape.
- **403 and 404 arrive as 500.** The backend's exception handler collapses them, so the SPA cannot
  branch on status. Any change to `Handler.php` is a coordinated two-repo change.
- **`API_URL` (website, server-only) vs `REACT_APP_BASE_URL` (SPA, browser).** Two different variables
  pointing at the same backend, with opposite exposure rules.
- **Nothing is pushed from the backend to a client.** No `config/broadcasting.php`, no queue-backed
  broadcast, no socket or `EventSource` in either front end. State one session changes for **another**
  user — a Super Admin granting or revoking credits is the live example — becomes visible only when that
  user's client re-fetches. `TCV-Frontend/src/hooks/useCreditsSync.js` is the reference pattern
  ([FRONTEND.md](FRONTEND.md#the-credit-balance-is-polled-not-pushed)); a ticket asking for "real time"
  means choosing a polling cadence, and the endpoint behind it pays that cadence per open tab.

---

## ☠️ Full-stack traps

1. **The diagnosis algorithm was ported, and the original was left behind.**
   `ColorVisionDiagnosisService.php` (559 lines) is a port of
   `TCV-Frontend/src/utils/calculateColorVisionResult.js` (349 lines) — its docblock says so. The JS copy
   is **imported nowhere**. Fix the PHP; **delete** the JS. "Keeping them in sync" is the wrong instinct
   and guarantees drift.
2. **Two live SPA calls hit endpoints that do not exist** — `GET /api/tests/sent-invitations` and
   `POST /api/user/tests/bulk-update-visibility`. Both slices are wired into real components. See
   [INDEXES/CONTRACT_DRIFT.md](INDEXES/CONTRACT_DRIFT.md); regenerate it after any route change.
3. **Adding an SPA page takes three files**, in two of which it is easy to forget:
   `protectedRoutes.js`, `routeConfig.js`, and `USER_PANEL_WITH_HEADER` in `Router.js`. Miss the second
   and the page never renders, with no error.
4. **The org patient form is a two-repo schema.** The display flags on `organizations`
   (`show_gender`, `show_zip`, `show_patient_id`, `anonymize_patient`, …) are served by
   `getPatientForm()` and rendered by the SPA. A new field needs a column, a `getPatientForm()` change
   **and** a renderer change.
5. **The website's `CLAUDE.md` claims it has no API routes.** It has four. Do not trust it over
   [WEBSITE.md](WEBSITE.md).
6. **Dev-only rewrites in `next.config.mjs`** make the website proxy `/app/*` and `/api/*` to the SPA and
   the backend. Production uses nginx from `TCV-Frontend`. "Works locally, 404s in prod" is usually this.
7. **The SPA is served under `/app`.** `PUBLIC_URL` is stripped before path comparisons in
   `AxiosInstance.js`; new path logic must do the same or it will misclassify every route.

---

## When you change X, update everywhere it applies

| Change | Backend | SPA | Website |
|---|---|---|---|
| Add / modify an endpoint | route in `routes/api.php` + controller/service | the slice or `apis/*` fn + consuming component | only if it is one of the four proxied routes |
| Change a request/response shape | controller + `ApiResponse` | the thunk's payload + selectors | the proxy passes through — check the modal's field names |
| Roles / `usertype` | `User` constants + policies | `constants/dataObjects.js` + `routeConfig.js` | — |
| Auth / token lifetime | `config/sanctum.php` | nothing to change — but expect logouts | — |
| New unauthenticated patient flow | route placement in `routes/api.php` | `isPublicRoute()` in `AxiosInstance.js` **and** `publicRoutes.js` | — |
| Org patient-form field | `organizations` column + `getPatientForm()` | the form renderer | — |
| Error-status semantics | `Exceptions/Handler.php` | `services/errorHandler.js` + `skipErrorPopup` call sites | — |

After a full-stack change **that has passed**, update the affected KB prose — including this map — and
re-run the generator so the drift indexes reflect reality
([GUIDES/HOW_TO_REGENERATE.md](GUIDES/HOW_TO_REGENERATE.md)).
