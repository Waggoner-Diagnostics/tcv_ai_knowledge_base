# Frontend → Backend API Call Index

**87 distinct calls** found across 256 TCV-Frontend source files, matched against the 176 backend endpoints in [API_ENDPOINT_INDEX.md](API_ENDPOINT_INDEX.md).

> **Lower bound, not a census.** These come from a lexical scan for literal `axios*.<verb>('…')`
> URLs. A URL built at runtime from variables is invisible to it, so *absent from this table*
> does **not** prove *never called*. Present rows, though, are real call sites with real line numbers.

## Resolved (80)

| Method | Client path | Backend | Call site |
|---|---|---|---|
| POST | `/api/contact` | `API-009` ContactController@submit | [src/apis/miscApis.js:90](../../../TCV-Frontend/src/apis/miscApis.js#L90) |
| GET | `/api/countries-with-states` | `API-010` DropdownValuesController@getCountriesWithStates | [src/redux/slices/auth/loginSlice.js:182](../../../TCV-Frontend/src/redux/slices/auth/loginSlice.js#L182) |
| GET | `/api/discount-codes` | `API-019` DiscountCodeController@index | [src/redux/slices/discount/discountSlice.js:10](../../../TCV-Frontend/src/redux/slices/discount/discountSlice.js#L10) |
| POST | `/api/discount-codes` | `API-020` DiscountCodeController@store | [src/redux/slices/discount/discountSlice.js:46](../../../TCV-Frontend/src/redux/slices/discount/discountSlice.js#L46) |
| GET | `/api/discount-codes/code-available` | `API-021` DiscountCodeController@codeAvailable | [src/apis/miscApis.js:110](../../../TCV-Frontend/src/apis/miscApis.js#L110) |
| GET | `/api/discount-codes/form-options` | `API-022` DiscountCodeController@formOptions | [src/redux/slices/discount/discountSlice.js:34](../../../TCV-Frontend/src/redux/slices/discount/discountSlice.js#L34) |
| GET | `/api/discount-codes/stats` | `API-023` DiscountCodeController@stats | [src/redux/slices/discount/discountSlice.js:22](../../../TCV-Frontend/src/redux/slices/discount/discountSlice.js#L22) |
| POST | `/api/discount-codes/validate` | `API-024` DiscountCodeController@validateCode | [src/redux/slices/discount/discountSlice.js:94](../../../TCV-Frontend/src/redux/slices/discount/discountSlice.js#L94) |
| DELETE | `/api/discount-codes/{param}` | `API-025` DiscountCodeController@destroy | [src/redux/slices/discount/discountSlice.js:70](../../../TCV-Frontend/src/redux/slices/discount/discountSlice.js#L70) |
| PUT | `/api/discount-codes/{param}` | `API-027` DiscountCodeController@update | [src/redux/slices/discount/discountSlice.js:58](../../../TCV-Frontend/src/redux/slices/discount/discountSlice.js#L58) |
| PATCH | `/api/discount-codes/{param}/toggle` | `API-028` DiscountCodeController@toggle | [src/redux/slices/discount/discountSlice.js:82](../../../TCV-Frontend/src/redux/slices/discount/discountSlice.js#L82) |
| GET | `/api/dropdown/compliances` | `API-030` DropdownValuesController@activeCompliances | [src/apis/miscApis.js:43](../../../TCV-Frontend/src/apis/miscApis.js#L43) |
| GET | `/api/dropdown/organization-settings-options` | `API-031` DropdownValuesController@activeOrgSettingsOptions | [src/apis/miscApis.js:70](../../../TCV-Frontend/src/apis/miscApis.js#L70) |
| GET | `/api/dropdown/organization-types` | `API-032` DropdownValuesController@activeOrganizationTypes | [src/apis/miscApis.js:34](../../../TCV-Frontend/src/apis/miscApis.js#L34) |
| GET | `/api/dropdown/privileges` | `API-033` DropdownValuesController@activePrivileges | [src/apis/miscApis.js:52](../../../TCV-Frontend/src/apis/miscApis.js#L52) |
| POST | `/api/impersonate/{param}` | `API-034` AuthController@impersonateUser | [src/redux/slices/impersonateSlice.js:8](../../../TCV-Frontend/src/redux/slices/impersonateSlice.js#L8) |
| POST | `/api/login` | `API-035` AuthController@login | [src/redux/slices/auth/loginSlice.js:10](../../../TCV-Frontend/src/redux/slices/auth/loginSlice.js#L10) |
| POST | `/api/logout` | `API-036` AuthController@logout | [src/redux/slices/auth/loginSlice.js:72](../../../TCV-Frontend/src/redux/slices/auth/loginSlice.js#L72) |
| POST | `/api/organization/patient/default` | `API-037` OrganizationPatientController@storeDefaultPatient | [src/redux/slices/patientSlice.js:38](../../../TCV-Frontend/src/redux/slices/patientSlice.js#L38) |
| GET | `/api/organization/patientForm` | `API-039` OrganizationController@getPatientForm | [src/redux/slices/patientSlice.js:20](../../../TCV-Frontend/src/redux/slices/patientSlice.js#L20) |
| GET | `/api/organization/privileges` | `API-040` OrganizationController@getOrganizationPrivileges | [src/apis/miscApis.js:61](../../../TCV-Frontend/src/apis/miscApis.js#L61) |
| GET | `/api/organization/redirect-url` | `API-041` OrganizationController@getOrganizationRedirectUrl | [src/apis/miscApis.js:79](../../../TCV-Frontend/src/apis/miscApis.js#L79) |
| GET | `/api/organization/tests/default` | `API-043` OrganizationController@getDefaultTests | [src/redux/slices/tests/organizationTestSlice.js:9](../../../TCV-Frontend/src/redux/slices/tests/organizationTestSlice.js#L9) |
| POST | `/api/organization/verify-signature` | `API-044` OrganizationController@verifySignature | [src/redux/slices/auth/signatureVerificationSlice.js:9](../../../TCV-Frontend/src/redux/slices/auth/signatureVerificationSlice.js#L9) |
| GET | `/api/organizations` | `API-045` OrganizationController@index | [src/apis/fetchOrganisationPaginated.js:18](../../../TCV-Frontend/src/apis/fetchOrganisationPaginated.js#L18) |
| POST | `/api/organizations/{param}/upload-logo` | `API-047` OrganizationController@uploadLogo | [src/redux/slices/Organisation/OrganisationSlice.js:13](../../../TCV-Frontend/src/redux/slices/Organisation/OrganisationSlice.js#L13) |
| PUT | `/api/password/change` | `API-051` PasswordController@update | [src/redux/slices/userProfile/passwordChangeSlice.js:12](../../../TCV-Frontend/src/redux/slices/userProfile/passwordChangeSlice.js#L12) |
| POST | `/api/password/forgot` | `API-052` AuthController@sendResetLinkEmail | [src/redux/slices/auth/loginSlice.js:85](../../../TCV-Frontend/src/redux/slices/auth/loginSlice.js#L85) |
| POST | `/api/password/reset` | `API-053` AuthController@setOrResetPassword | [src/redux/slices/auth/loginSlice.js:134](../../../TCV-Frontend/src/redux/slices/auth/loginSlice.js#L134) |
| POST | `/api/password/verify-setup-token` | `API-054` AuthController@verifySetupToken | [src/redux/slices/auth/loginSlice.js:147](../../../TCV-Frontend/src/redux/slices/auth/loginSlice.js#L147) |
| POST | `/api/patient-tests/{param}/revoke-credit` | `API-055` CreditsController@revokeCredit | [src/redux/slices/tests/patientTestSlice.js:43](../../../TCV-Frontend/src/redux/slices/tests/patientTestSlice.js#L43) |
| GET | `/api/patients/{param}` | `API-061` PatientController@show | [src/redux/slices/patientSlice.js:52](../../../TCV-Frontend/src/redux/slices/patientSlice.js#L52) |
| GET | `/api/patients/{param}/tests` | `API-059` PatientController@getPatientTests | [src/redux/slices/tests/patientTestSlice.js:11](../../../TCV-Frontend/src/redux/slices/tests/patientTestSlice.js#L11) |
| POST | `/api/payment/confirm` | `API-064` PaymentController@confirmPayment | [src/redux/slices/payment/paymentSlice.js:60](../../../TCV-Frontend/src/redux/slices/payment/paymentSlice.js#L60) _(+1)_ |
| POST | `/api/payment/initialize` | `API-065` PaymentController@initializePayment | [src/redux/slices/payment/paymentSlice.js:22](../../../TCV-Frontend/src/redux/slices/payment/paymentSlice.js#L22) _(+1)_ |
| GET | `/api/payment/providers` | `API-066` PaymentController@getProviders | [src/redux/slices/payment/paymentSlice.js:96](../../../TCV-Frontend/src/redux/slices/payment/paymentSlice.js#L96) |
| POST | `/api/payment/setup-intent` | `API-067` PaymentController@createSetupIntent | [src/redux/slices/payment/paymentSlice.js:8](../../../TCV-Frontend/src/redux/slices/payment/paymentSlice.js#L8) |
| GET | `/api/profile` | `API-073` ProfileController@show | [src/redux/slices/userProfile/profileSlice.js:10](../../../TCV-Frontend/src/redux/slices/userProfile/profileSlice.js#L10) |
| PUT | `/api/profile` | `API-074` ProfileController@update | [src/redux/slices/userProfile/profileSlice.js:27](../../../TCV-Frontend/src/redux/slices/userProfile/profileSlice.js#L27) |
| POST | `/api/resend-test-link` | `API-079` PatientController@resendTestLink | [src/redux/slices/tests/patientTestSlice.js:27](../../../TCV-Frontend/src/redux/slices/tests/patientTestSlice.js#L27) |
| POST | `/api/resend-verification-by-token` | `API-080` AuthController@resendVerificationByToken | [src/apis/miscApis.js:95](../../../TCV-Frontend/src/apis/miscApis.js#L95) |
| POST | `/api/resend_email_verification_link` | `API-081` AuthController@resendEmailVerificationLink | [src/redux/slices/auth/loginSlice.js:52](../../../TCV-Frontend/src/redux/slices/auth/loginSlice.js#L52) |
| POST | `/api/stop-impersonate/{param}` | `API-089` AuthController@stopImpersonation | [src/redux/slices/impersonateSlice.js:20](../../../TCV-Frontend/src/redux/slices/impersonateSlice.js#L20) |
| GET | `/api/stripe/payment-methods` | `API-092` StripePaymentController@getPaymentMethods | [src/redux/slices/payment/paymentSlice.js:72](../../../TCV-Frontend/src/redux/slices/payment/paymentSlice.js#L72) |
| DELETE | `/api/stripe/payment-methods/{param}` | `API-094` StripePaymentController@removePaymentMethod | [src/redux/slices/payment/paymentSlice.js:84](../../../TCV-Frontend/src/redux/slices/payment/paymentSlice.js#L84) |
| GET | `/api/stripe/transactions` | `API-095` PaymentController@getTransactions | [src/redux/slices/transaction/transactionSlice.js:9](../../../TCV-Frontend/src/redux/slices/transaction/transactionSlice.js#L9) |
| GET | `/api/super-admin/dashboard` | `API-096` SuperAdminDashboardController@index | [src/redux/slices/superAdminDashboard/superAdminDashboardSlice.js:8](../../../TCV-Frontend/src/redux/slices/superAdminDashboard/superAdminDashboardSlice.js#L8) |
| GET | `/api/test-email-templates` | `API-097` TestEmailTemplateController@index | [src/redux/slices/admin/adminTestEmailTemplateSlice.js:10](../../../TCV-Frontend/src/redux/slices/admin/adminTestEmailTemplateSlice.js#L10) |
| PUT | `/api/test-email-templates/{param}` | `API-099` TestEmailTemplateController@update | [src/redux/slices/admin/adminTestEmailTemplateSlice.js:27](../../../TCV-Frontend/src/redux/slices/admin/adminTestEmailTemplateSlice.js#L27) |
| POST | `/api/test-invitation/check-validity` | `API-100` TestInvitationController@checkTokenStatus | [src/redux/slices/auth/loginSlice.js:116](../../../TCV-Frontend/src/redux/slices/auth/loginSlice.js#L116) |
| POST | `/api/test-invitation/verify-code` | `API-101` TestInvitationController@verifyCode | [src/redux/slices/auth/loginSlice.js:99](../../../TCV-Frontend/src/redux/slices/auth/loginSlice.js#L99) |
| POST | `/api/test-invitations/send` | `API-102` TestInvitationController@sendInvitations | [src/redux/slices/tests/sendTestSlice.js:15](../../../TCV-Frontend/src/redux/slices/tests/sendTestSlice.js#L15) |
| GET | `/api/test-invitations/unregistered` | `API-103` TestInvitationController@getUnregisteredInvitations | [src/redux/slices/tests/sendTestSlice.js:38](../../../TCV-Frontend/src/redux/slices/tests/sendTestSlice.js#L38) |
| POST | `/api/test-invitations/{param}/cancel` | `API-104` TestInvitationController@cancelUnregisteredInvitation | [src/redux/slices/tests/sendTestSlice.js:72](../../../TCV-Frontend/src/redux/slices/tests/sendTestSlice.js#L72) |
| POST | `/api/test-invitations/{param}/resend` | `API-105` TestInvitationController@resendUnregisteredInvitation | [src/redux/slices/tests/sendTestSlice.js:55](../../../TCV-Frontend/src/redux/slices/tests/sendTestSlice.js#L55) |
| GET | `/api/test-result/{param}` | `API-106` TestController@getTestResult | [src/redux/slices/tests/testResultSlice.js:9](../../../TCV-Frontend/src/redux/slices/tests/testResultSlice.js#L9) |
| GET | `/api/test-result/{param}/download-pdf` | `API-107` TestController@downloadTestResultPDF | [src/apis/testResultService.js:5](../../../TCV-Frontend/src/apis/testResultService.js#L5) |
| GET | `/api/test-session/{param}` | `API-108` TestController@getTestSession | [src/redux/slices/tests/testExecutionSlice.js:9](../../../TCV-Frontend/src/redux/slices/tests/testExecutionSlice.js#L9) |
| GET | `/api/test-session/{param}/plate/{param}/url` | `API-109` TestController@getPlateUrl | [src/redux/slices/tests/testExecutionSlice.js:60](../../../TCV-Frontend/src/redux/slices/tests/testExecutionSlice.js#L60) |
| GET | `/api/test-session/{param}/section/{param}/plates` | `API-110` TestController@getSectionPlates | [src/redux/slices/tests/testExecutionSlice.js:25](../../../TCV-Frontend/src/redux/slices/tests/testExecutionSlice.js#L25) |
| POST | `/api/test/resume` | `API-111` TestResumeController@resume | [src/redux/slices/tests/testResumeSlice.js:23](../../../TCV-Frontend/src/redux/slices/tests/testResumeSlice.js#L23) |
| POST | `/api/test/send-resume-email` | `API-112` TestResumeController@sendResumeEmail | [src/redux/slices/tests/testResumeSlice.js:8](../../../TCV-Frontend/src/redux/slices/tests/testResumeSlice.js#L8) |
| POST | `/api/tests/assign` | `API-115` TestController@assignTest | [src/redux/slices/tests/testSessionSlice.js:13](../../../TCV-Frontend/src/redux/slices/tests/testSessionSlice.js#L13) |
| POST | `/api/tests/check-active` | `API-116` TestController@getActiveTest | [src/redux/slices/tests/testSessionSlice.js:60](../../../TCV-Frontend/src/redux/slices/tests/testSessionSlice.js#L60) |
| POST | `/api/tests/perform` | `API-118` TestController@performTest | [src/redux/slices/tests/testExecutionSlice.js:43](../../../TCV-Frontend/src/redux/slices/tests/testExecutionSlice.js#L43) |
| POST | `/api/tests/result-pdf` | `API-119` TestController@generateTestResultPDF | [src/redux/slices/tests/testSessionSlice.js:36](../../../TCV-Frontend/src/redux/slices/tests/testSessionSlice.js#L36) |
| DELETE | `/api/user-email-template` | `API-153` UserEmailTemplateController@destroy | [src/redux/slices/userProfile/userEmailTemplateSlice.js:48](../../../TCV-Frontend/src/redux/slices/userProfile/userEmailTemplateSlice.js#L48) |
| GET | `/api/user-email-template` | `API-154` UserEmailTemplateController@show | [src/redux/slices/userProfile/userEmailTemplateSlice.js:10](../../../TCV-Frontend/src/redux/slices/userProfile/userEmailTemplateSlice.js#L10) |
| PUT | `/api/user-email-template` | `API-155` UserEmailTemplateController@update | [src/redux/slices/userProfile/userEmailTemplateSlice.js:27](../../../TCV-Frontend/src/redux/slices/userProfile/userEmailTemplateSlice.js#L27) |
| GET | `/api/user/credit-history` | `API-156` PaymentController@getCreditHistory | [src/redux/slices/creditHistory/creditHistorySlice.js:8](../../../TCV-Frontend/src/redux/slices/creditHistory/creditHistorySlice.js#L8) |
| GET | `/api/user/credits` | `API-157` UserController@getUserCredits | [src/redux/slices/userCredits/userCreditSlice.js:21](../../../TCV-Frontend/src/redux/slices/userCredits/userCreditSlice.js#L21) |
| GET | `/api/user/tests/all` | `API-159` TestController@getActiveTestsWithAssignmentFlag | [src/hooks/useTestAssignment.js:34](../../../TCV-Frontend/src/hooks/useTestAssignment.js#L34) _(+1)_ |
| POST | `/api/user/tests/bulk-update-assignment` | `API-160` TestController@bulkUpdateAssignment | [src/redux/slices/tests/testAssignmentSlice.js:24](../../../TCV-Frontend/src/redux/slices/tests/testAssignmentSlice.js#L24) |
| GET | `/api/user/tests/{param}` | `API-161` TestController@show | [src/redux/slices/tests/userTestSlice.js:15](../../../TCV-Frontend/src/redux/slices/tests/userTestSlice.js#L15) |
| PUT | `/api/users/change-password` | `API-166` AuthController@changePassword | [src/redux/slices/auth/loginSlice.js:162](../../../TCV-Frontend/src/redux/slices/auth/loginSlice.js#L162) |
| GET | `/api/users/{param}` | `API-169` UserController@edit | [src/redux/slices/auth/loginSlice.js:222](../../../TCV-Frontend/src/redux/slices/auth/loginSlice.js#L222) |
| PATCH | `/api/users/{param}` | `API-171` UserController@update | [src/pages/Setting/Profile.js:78](../../../TCV-Frontend/src/pages/Setting/Profile.js#L78) |
| GET | `/api/validate-token` | `API-173` AuthController@isTokenValid | [src/pages/AuthInit.js:51](../../../TCV-Frontend/src/pages/AuthInit.js#L51) |
| POST | `/api/verify-email-token` | `API-174` AuthController@verifyEmailByToken | [src/apis/miscApis.js:101](../../../TCV-Frontend/src/apis/miscApis.js#L101) |
| POST | `/api/verify-password` | `API-176` AuthController@verifyPassword | [src/redux/slices/auth/passwordVerificationSlice.js:9](../../../TCV-Frontend/src/redux/slices/auth/passwordVerificationSlice.js#L9) |

---

_Generated from source by `tools/extract.php` + `tools/extract-clients.php` + `tools/render.php` on 2026-09-03. Do not hand-edit — re-run the generator._
