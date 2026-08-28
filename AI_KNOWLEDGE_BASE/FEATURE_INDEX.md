# Feature Index

Feature → the files, endpoints and tables that implement it. Stable `F-nnn` IDs so other docs and
commits can reference a feature without restating it.

| ID | Feature | Backend entry points | Key files | Tables | Pack |
|---|---|---|---|---|---|
| **F-001** | Registration | `POST api/register` | `AuthController::register()` · `UserRequest` | `users`, `password_reset_tokens`, `user_assigned_tests` | [AUTH](CONTEXT/AUTH_CONTEXT.md) |
| **F-002** | Login & tokens | `POST api/login` · `GET api/validate-token` · `POST api/logout` | `AuthController` · `config/sanctum.php` | `users`, `personal_access_tokens` | [AUTH](CONTEXT/AUTH_CONTEXT.md) |
| **F-003** | Email verification | `GET api/verify-email/{id}/{hash}` · `POST api/verify-email-token` · 2 resend routes | `AuthController::verifyEmail*`, `sendVerificationEmailForUser()` | `users`, `email_template` | [AUTH](CONTEXT/AUTH_CONTEXT.md) |
| **F-004** | Password set / reset / change | `POST api/password/{forgot,reset,verify-setup-token}` · `PUT api/password/change` · `PUT api/users/change-password` | `AuthController` · `PasswordController` · `ResetPasswordNotification` | `password_reset_tokens` | [AUTH](CONTEXT/AUTH_CONTEXT.md) |
| **F-005** | Impersonation | `POST api/impersonate/{id}` · `POST api/stop-impersonate/{id}` | `AuthController` · `User::canImpersonateUser()` | `personal_access_tokens` | [AUTH](CONTEXT/AUTH_CONTEXT.md) |
| **F-006** | IP restriction | `api/restricted-ips` (apiResource ×2) | `RestrictIpMiddleware` · `RestrictedIpController` | `restricted_ips` | [MIDDLEWARE](MIDDLEWARE.md) |
| **F-010** | Test authoring | `api/tests` + nested `conditions`/`answers`/`sections`/`section/plates` resources · `POST api/tests/{id}/clone` | `TestController` + 4 controllers · `TestPolicy` | `tests`, `test_sections`, `test_section_plates`, `test_conditions` | — |
| **F-011** | Test assignment | `POST api/tests/assign` · `POST api/tests/check-active` | `TestAssignmentService` | `patient_tests`, `testanswers`, `credit_consume` | [TEST_EXEC](CONTEXT/TEST_EXECUTION_CONTEXT.md) |
| **F-012** | Test execution | `POST api/tests/perform` · `GET api/test-session/*` | `TestExecutionService` · `TestSection{Progression,Termination}Service` | `patient_tests`, `testanswers` | [TEST_EXEC](CONTEXT/TEST_EXECUTION_CONTEXT.md) |
| **F-013** | Plate delivery | `GET api/test-session/{uuid}/…/plates` · `…/plate/{id}/url` | `SecureImageService` (S3, 900 s pre-signed) | `test_section_plates`, `testanswers` | [TEST_EXEC](CONTEXT/TEST_EXECUTION_CONTEXT.md) |
| **F-014** | Diagnosis & result | (internal, at completion) | `ColorVisionDiagnosisService` · `TestResultService` | `patient_tests.result_json` | [TEST_EXEC](CONTEXT/TEST_EXECUTION_CONTEXT.md) |
| **F-015** | Result PDF | `GET api/test-result/{uuid}/download-pdf` · `POST api/tests/result-pdf` | `TestController` + dompdf | `patient_tests` | [REPORTING](CONTEXT/REPORTING_CONTEXT.md) |
| **F-016** | Monocular (both-eyes) tests | part of F-011/F-012 | `TestAssignmentService::createBothEyesTests()` · `resolveCanonicalTestId()` | `patient_tests.parent_test_id`, `.eye_tested` | [TEST_EXEC](CONTEXT/TEST_EXECUTION_CONTEXT.md) |
| **F-017** | User test assignment / visibility | `api/user/tests/*` | `TestController::userIndex`, `assignUserTest`, `bulkUpdateAssignment` | `user_assigned_tests`, `user_hidden_tests` | — |
| **F-020** | Patients | `api/patients` (resource) · `GET api/patients/{id}/tests` | `PatientController` · `PatientTestTransformer` | `patients`, `patient_tests` | [PATIENT](CONTEXT/PATIENT_CONTEXT.md) |
| **F-021** | Org patient intake | `POST api/organization/patient/{default,prolific}` · `GET api/organization/patientForm` | `OrganizationPatientController` · `TurnstileService` | `patients`, `prolific_ids`, `organization_configs` | [ORG](CONTEXT/ORGANIZATION_CONTEXT.md) |
| **F-030** | Email invitations | `POST api/test-invitations/send` · `/verify-code` · `/check-validity` · `{id}/resend` · `{id}/cancel` | `TestInvitationController` | `test_invitations`, `test_sessions`, `credit_consume` | [INVITATION](CONTEXT/INVITATION_CONTEXT.md) |
| **F-031** | Resume links | `POST api/test/send-resume-email` · `POST api/test/resume` | `TestResumeController` | `test_resume_tokens`, `test_sessions` | [INVITATION](CONTEXT/INVITATION_CONTEXT.md) |
| **F-032** | Email templates | `api/test-email-templates/*` · `api/user-email-template` | `TestEmailTemplateController` · `UserEmailTemplateController` · `EmailTemplateService`/`Repository` | `email_template`, `test_email_templates`, `user_email_templates` | — |
| **F-040** | Credits | `api/credits` (resource) · `GET api/user/credits` · `POST api/patient-tests/{id}/revoke-credit` | `Credits` · `CreditConsume` · `CreditsController` | `credits`, `credit_consume` | [CREDITS](CONTEXT/CREDITS_CONTEXT.md) |
| **F-041** | Payments | `api/payment/*` | `PaymentManager` · `StripeProvider` · `BasePaymentProvider` | `transactions`, `transaction_details`, `user_stripe_details`, `credits` | [BILLING](CONTEXT/BILLING_CONTEXT.md) |
| **F-042** | Legacy Stripe surface | `api/stripe/*` | `StripePaymentController` · `StripeService` | same | [BILLING](CONTEXT/BILLING_CONTEXT.md) |
| **F-043** | Credit history | `GET api/user/credit-history` · `GET api/stripe/transactions` | `PaymentController::getCreditHistory()` | `credits`, `credit_consume`, `transactions` | [CREDITS](CONTEXT/CREDITS_CONTEXT.md) |
| **F-044** | Discount codes | `api/discount-codes/*` | `DiscountCodeService` · `DiscountCodeController` | `discount_codes`, `discount_code_users`, `discount_code_price_tiers` | [DISCOUNT](CONTEXT/DISCOUNT_CONTEXT.md) |
| **F-045** | Pricing tiers | `api/price-details` (apiResource) | `PriceDetailController` · `PricingAuditService` | `price_details`, `pricing_audit_logs` | — |
| **F-050** | Organisations | `api/organizations` (apiResource) · `POST api/organizations/{id}/upload-logo` | `OrganizationController` · `OrgPolicy` | `organizations` + 6 config tables | [ORG](CONTEXT/ORGANIZATION_CONTEXT.md) |
| **F-051** | Org Test URL & signature | `POST api/organization/verify-signature` | `Organization::generateTestUrl()` · `OrganizationTestUrlNotification` | `organizations`, `lms_provider_configs`, `lms_sessions` | [ORG](CONTEXT/ORGANIZATION_CONTEXT.md) |
| **F-052** | Org privileges / redirect | `GET api/organization/{privileges,redirect-url,tests/default}` | `OrganizationController` | `privileges`, `allowed_tests` | [ORG](CONTEXT/ORGANIZATION_CONTEXT.md) |
| **F-060** | LMS launch & session | via F-051 | `LmsLaunchService` · `FlexibleAuthMiddleware` tier 3 · `LmsSessionStatusMiddleware` | `lms_sessions`, `lms_provider_configs` | [LMS](CONTEXT/LMS_CONTEXT.md) |
| **F-061** | LMS delivery | (event-driven) | `LmsDeliveryService` · `ProcessLmsDeliveryJob` · `Cornerstone`/`GenericWebhook` providers · `XapiStatementBuilder` | `lms_delivery_queue`, `lms_delivery_tokens` | [LMS](CONTEXT/LMS_CONTEXT.md) |
| **F-062** | LMS admin | `api/admin/lms/*` | `LmsAdminController` | `lms_provider_configs`, `lms_delivery_queue` | [LMS](CONTEXT/LMS_CONTEXT.md) |
| **F-070** | Reports | `api/reports/*` | `ReportController` · `Services/Reports/*` · `Exports/*` | many | [REPORTING](CONTEXT/REPORTING_CONTEXT.md) |
| **F-071** | Super-admin dashboard | `GET api/super-admin/dashboard` | `SuperAdminDashboardController` | many | [REPORTING](CONTEXT/REPORTING_CONTEXT.md) |
| **F-080** | Contact / enquiry | `POST api/contact` | `ContactController` · `HubSpotService` | — | [THIRD_PARTY](THIRD_PARTY.md) |
| **F-081** | Reference data | `api/dropdown/*` · `GET api/countries-with-states` | `DropdownValuesController` | `countries`, `states`, `compliances`, `privileges`, `organization_types`, `organization_settings_options` | — |

## Depth of tracing

| Traced closely | `[not deeply traced]` |
|---|---|
| F-001…F-005, F-011…F-014, F-016, F-030, F-031, F-040, F-041, F-051, F-060…F-062 | F-015 (dompdf templates), F-017, F-032, F-070, F-071, F-081, and the ACH/bank-transfer branches of F-041 |

Where a feature is marked not deeply traced, this KB tells you **where to look**, not what the code
does in detail. Read the source.

## Features that exist in code but are unreachable

| Thing | Why |
|---|---|
| `CreditsController::checkDiscountCodeValidity()` | its route is shadowed by the `credits` resource ([ROUTES.md](ROUTES.md#ordering-traps)) |
| `StripePaymentController::refund()` / `partialRefund()` | routes commented out in `routes/api.php` |
| `POST api/payment/webhook/{provider}` | inside `auth:sanctum`, and the signature check re-encodes the body ([BILLING](CONTEXT/BILLING_CONTEXT.md)) |
| `app/Http/Controllers/Auth/*` | `laravel/ui` scaffolding, no routes |
| `EnsureTokenIsValid` middleware | never aliased |
| `App\Rules\TurnstileToken` | never referenced |
| `SecureImageService::getBatchSecurePlateUrls()` / `uploadPlateToS3()` | commented as unused |
| `App\Models\Credit` | superseded by `Credits` on the same table |
