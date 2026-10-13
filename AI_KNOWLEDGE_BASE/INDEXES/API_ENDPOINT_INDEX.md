# API Endpoint Index

Every `api/*` route in TCV-Backend, with the middleware that actually executes — the group stack a
route is physically nested inside, resolved the way Laravel resolves it.

**166 endpoints.** Auth column: `auth:sanctum` = Sanctum token required · `FlexibleAuthMiddleware` = **four** accepted token kinds (see [AUTH_CONTEXT](../CONTEXT/AUTH_CONTEXT.md)) · `—` = **public**.

> Route source: `AST static parse of routes/api.php + routes/web.php (TCV-Backend/vendor absent, so artisan cannot boot)`.
> `RestrictIpMiddleware` is appended **globally** in `bootstrap/app.php` and therefore runs on every
> row below; it is omitted from the table rather than repeated 179 times.

| ID | Method | URI | Action | Middleware | Route file |
|---|---|---|---|---|---|
| `API-001` | GET | `api/access-check` | _(closure)_ | — | [api.php:59](../../../TCV-Backend/routes/api.php#L59) |
| `API-002` | GET | `api/admin/lms/dead-letters` | LmsAdminController@deadLetters | `auth:sanctum` | [api.php:332](../../../TCV-Backend/routes/api.php#L332) |
| `API-003` | POST | `api/admin/lms/dead-letters/{id}/dismiss` | LmsAdminController@dismiss | `auth:sanctum` | [api.php:334](../../../TCV-Backend/routes/api.php#L334) |
| `API-004` | POST | `api/admin/lms/dead-letters/{id}/replay` | LmsAdminController@replay | `auth:sanctum` | [api.php:333](../../../TCV-Backend/routes/api.php#L333) |
| `API-005` | GET | `api/admin/lms/delivery-status` | LmsAdminController@deliveryStatus | `auth:sanctum` | [api.php:335](../../../TCV-Backend/routes/api.php#L335) |
| `API-006` | GET | `api/admin/lms/provider-configs` | LmsAdminController@listProviderConfigs | `auth:sanctum` | [api.php:328](../../../TCV-Backend/routes/api.php#L328) |
| `API-007` | POST | `api/admin/lms/provider-configs` | LmsAdminController@upsertProviderConfig | `auth:sanctum` | [api.php:329](../../../TCV-Backend/routes/api.php#L329) |
| `API-008` | POST | `api/admin/lms/provider-configs/{id}/rotate-key` | LmsAdminController@rotateSigningKey | `auth:sanctum` | [api.php:331](../../../TCV-Backend/routes/api.php#L331) |
| `API-009` | GET | `api/admin/lms/provider-configs/{id}/signing-key` | LmsAdminController@revealSigningKey | `auth:sanctum` | [api.php:330](../../../TCV-Backend/routes/api.php#L330) |
| `API-010` | GET | `api/audit-logs` | AuditLogController@index | `auth:sanctum` | [api.php:146](../../../TCV-Backend/routes/api.php#L146) |
| `API-011` | GET | `api/audit-logs/export` | AuditLogController@export | `auth:sanctum` | [api.php:148](../../../TCV-Backend/routes/api.php#L148) |
| `API-012` | GET | `api/audit-logs/people` | AuditLogController@people | `auth:sanctum` | [api.php:147](../../../TCV-Backend/routes/api.php#L147) |
| `API-013` | GET | `api/audit-logs/{id}` | AuditLogController@show | `auth:sanctum` | [api.php:149](../../../TCV-Backend/routes/api.php#L149) |
| `API-014` | POST | `api/contact` | ContactController@submit | `auth:sanctum` · `throttle:10,1` | [api.php:324](../../../TCV-Backend/routes/api.php#L324) |
| `API-015` | GET | `api/countries-with-states` | DropdownValuesController@getCountriesWithStates | — | [api.php:50](../../../TCV-Backend/routes/api.php#L50) |
| `API-016` | GET | `api/credits` | CreditsController@index | `auth:sanctum` | [api.php:243](../../../TCV-Backend/routes/api.php#L243) |
| `API-017` | POST | `api/credits` | CreditsController@store | `auth:sanctum` | [api.php:243](../../../TCV-Backend/routes/api.php#L243) |
| `API-018` | GET | `api/credits/coupon/{coupon_code}` | CreditsController@checkDiscountCodeValidity | `auth:sanctum` | [api.php:253](../../../TCV-Backend/routes/api.php#L253) |
| `API-019` | DELETE | `api/credits/{credit}` | CreditsController@destroy | `auth:sanctum` | [api.php:243](../../../TCV-Backend/routes/api.php#L243) |
| `API-020` | GET | `api/credits/{credit}` | CreditsController@show | `auth:sanctum` | [api.php:243](../../../TCV-Backend/routes/api.php#L243) |
| `API-021` | PUT|PATCH | `api/credits/{credit}` | CreditsController@update | `auth:sanctum` | [api.php:243](../../../TCV-Backend/routes/api.php#L243) |
| `API-022` | GET | `api/discount-codes` | DiscountCodeController@index | `auth:sanctum` | [api.php:286](../../../TCV-Backend/routes/api.php#L286) |
| `API-023` | POST | `api/discount-codes` | DiscountCodeController@store | `auth:sanctum` | [api.php:286](../../../TCV-Backend/routes/api.php#L286) |
| `API-024` | GET | `api/discount-codes/code-available` | DiscountCodeController@codeAvailable | `auth:sanctum` | [api.php:283](../../../TCV-Backend/routes/api.php#L283) |
| `API-025` | GET | `api/discount-codes/form-options` | DiscountCodeController@formOptions | `auth:sanctum` | [api.php:282](../../../TCV-Backend/routes/api.php#L282) |
| `API-026` | GET | `api/discount-codes/stats` | DiscountCodeController@stats | `auth:sanctum` | [api.php:281](../../../TCV-Backend/routes/api.php#L281) |
| `API-027` | POST | `api/discount-codes/validate` | DiscountCodeController@validateCode | `auth:sanctum` | [api.php:284](../../../TCV-Backend/routes/api.php#L284) |
| `API-028` | DELETE | `api/discount-codes/{discount_code}` | DiscountCodeController@destroy | `auth:sanctum` | [api.php:286](../../../TCV-Backend/routes/api.php#L286) |
| `API-029` | GET | `api/discount-codes/{discount_code}` | DiscountCodeController@show | `auth:sanctum` | [api.php:286](../../../TCV-Backend/routes/api.php#L286) |
| `API-030` | PUT|PATCH | `api/discount-codes/{discount_code}` | DiscountCodeController@update | `auth:sanctum` | [api.php:286](../../../TCV-Backend/routes/api.php#L286) |
| `API-031` | PATCH | `api/discount-codes/{discount_code}/toggle` | DiscountCodeController@toggle | `auth:sanctum` | [api.php:285](../../../TCV-Backend/routes/api.php#L285) |
| `API-032` | POST | `api/distributor-enquiry` | DistributorController@submit | `throttle:10,1` | [api.php:62](../../../TCV-Backend/routes/api.php#L62) |
| `API-033` | GET | `api/dropdown/allowed-tests` | DropdownValuesController@activeAllowedTests | `auth:sanctum` | [api.php:273](../../../TCV-Backend/routes/api.php#L273) |
| `API-034` | GET | `api/dropdown/compliances` | DropdownValuesController@activeCompliances | `auth:sanctum` | [api.php:272](../../../TCV-Backend/routes/api.php#L272) |
| `API-035` | GET | `api/dropdown/organization-settings-options` | DropdownValuesController@activeOrgSettingsOptions | `auth:sanctum` | [api.php:275](../../../TCV-Backend/routes/api.php#L275) |
| `API-036` | GET | `api/dropdown/organization-types` | DropdownValuesController@activeOrganizationTypes | `auth:sanctum` | [api.php:271](../../../TCV-Backend/routes/api.php#L271) |
| `API-037` | GET | `api/dropdown/privileges` | DropdownValuesController@activePrivileges | `auth:sanctum` | [api.php:274](../../../TCV-Backend/routes/api.php#L274) |
| `API-038` | POST | `api/impersonate/{id}` | AuthController@impersonateUser | `auth:sanctum` | [api.php:291](../../../TCV-Backend/routes/api.php#L291) |
| `API-039` | POST | `api/login` | AuthController@login | `throttle:login` | [api.php:35](../../../TCV-Backend/routes/api.php#L35) |
| `API-040` | POST | `api/logout` | AuthController@logout | `auth:sanctum` | [api.php:263](../../../TCV-Backend/routes/api.php#L263) |
| `API-041` | POST | `api/organization/patient/default` | OrganizationPatientController@storeDefaultPatient | `FlexibleAuthMiddleware` · `lms.status:launched,identity_resolved` | [api.php:106](../../../TCV-Backend/routes/api.php#L106) |
| `API-042` | POST | `api/organization/patient/prolific` | OrganizationPatientController@storeProlificPatient | `FlexibleAuthMiddleware` · `lms.status:launched,identity_resolved` | [api.php:104](../../../TCV-Backend/routes/api.php#L104) |
| `API-043` | GET | `api/organization/patientForm` | OrganizationController@getPatientForm | `FlexibleAuthMiddleware` | [api.php:102](../../../TCV-Backend/routes/api.php#L102) |
| `API-044` | GET | `api/organization/privileges` | OrganizationController@getOrganizationPrivileges | `FlexibleAuthMiddleware` | [api.php:109](../../../TCV-Backend/routes/api.php#L109) |
| `API-045` | GET | `api/organization/redirect-url` | OrganizationController@getOrganizationRedirectUrl | `FlexibleAuthMiddleware` | [api.php:111](../../../TCV-Backend/routes/api.php#L111) |
| `API-046` | GET | `api/organization/test` | _(closure)_ | `auth:sanctum` · `signed` | [api.php:267](../../../TCV-Backend/routes/api.php#L267) |
| `API-047` | GET | `api/organization/tests/default` | OrganizationController@getDefaultTests | `FlexibleAuthMiddleware` | [api.php:100](../../../TCV-Backend/routes/api.php#L100) |
| `API-048` | POST | `api/organization/verify-signature` | OrganizationController@verifySignature | `throttle:signature-verify` | [api.php:65](../../../TCV-Backend/routes/api.php#L65) |
| `API-049` | GET | `api/organizations` | OrganizationController@index | `auth:sanctum` | [api.php:264](../../../TCV-Backend/routes/api.php#L264) |
| `API-050` | POST | `api/organizations` | OrganizationController@store | `auth:sanctum` | [api.php:264](../../../TCV-Backend/routes/api.php#L264) |
| `API-051` | POST | `api/organizations/{id}/upload-logo` | OrganizationController@uploadLogo | `auth:sanctum` | [api.php:265](../../../TCV-Backend/routes/api.php#L265) |
| `API-052` | DELETE | `api/organizations/{organization}` | OrganizationController@destroy | `auth:sanctum` | [api.php:264](../../../TCV-Backend/routes/api.php#L264) |
| `API-053` | GET | `api/organizations/{organization}` | OrganizationController@show | `auth:sanctum` | [api.php:264](../../../TCV-Backend/routes/api.php#L264) |
| `API-054` | PUT|PATCH | `api/organizations/{organization}` | OrganizationController@update | `auth:sanctum` | [api.php:264](../../../TCV-Backend/routes/api.php#L264) |
| `API-055` | PUT | `api/password/change` | PasswordController@update | `auth:sanctum` | [api.php:187](../../../TCV-Backend/routes/api.php#L187) |
| `API-056` | POST | `api/password/forgot` | AuthController@sendResetLinkEmail | `throttle:password-reset` | [api.php:43](../../../TCV-Backend/routes/api.php#L43) |
| `API-057` | POST | `api/password/reset` | AuthController@setOrResetPassword | `throttle:password-reset` | [api.php:44](../../../TCV-Backend/routes/api.php#L44) |
| `API-058` | POST | `api/password/verify-setup-token` | AuthController@verifySetupToken | `throttle:password-reset` | [api.php:45](../../../TCV-Backend/routes/api.php#L45) |
| `API-059` | POST | `api/patient-tests/{identifier}/revoke-credit` | CreditsController@revokeCredit | `auth:sanctum` | [api.php:223](../../../TCV-Backend/routes/api.php#L223) |
| `API-060` | GET | `api/patients` | PatientController@index | `FlexibleAuthMiddleware` | [api.php:93](../../../TCV-Backend/routes/api.php#L93) |
| `API-061` | POST | `api/patients` | PatientController@store | `FlexibleAuthMiddleware` | [api.php:93](../../../TCV-Backend/routes/api.php#L93) |
| `API-062` | POST | `api/patients/export` | PatientController@export | `auth:sanctum` · `throttle:patient-export` | [api.php:234](../../../TCV-Backend/routes/api.php#L234) |
| `API-063` | GET | `api/patients/{id}/tests` | PatientController@getPatientTests | `auth:sanctum` | [api.php:236](../../../TCV-Backend/routes/api.php#L236) |
| `API-064` | DELETE | `api/patients/{patient}` | PatientController@destroy | `FlexibleAuthMiddleware` | [api.php:93](../../../TCV-Backend/routes/api.php#L93) |
| `API-065` | GET | `api/patients/{patient}` | PatientController@show | `FlexibleAuthMiddleware` | [api.php:93](../../../TCV-Backend/routes/api.php#L93) |
| `API-066` | PUT|PATCH | `api/patients/{patient}` | PatientController@update | `FlexibleAuthMiddleware` | [api.php:93](../../../TCV-Backend/routes/api.php#L93) |
| `API-067` | POST | `api/payment/complete-free-order` | PaymentController@completeFreeOrder | `auth:sanctum` · `throttle:free-order` | [api.php:305](../../../TCV-Backend/routes/api.php#L305) |
| `API-068` | POST | `api/payment/confirm` | PaymentController@confirmPayment | `auth:sanctum` | [api.php:304](../../../TCV-Backend/routes/api.php#L304) |
| `API-069` | POST | `api/payment/initialize` | PaymentController@initializePayment | `auth:sanctum` | [api.php:303](../../../TCV-Backend/routes/api.php#L303) |
| `API-070` | GET | `api/payment/providers` | PaymentController@getProviders | `auth:sanctum` | [api.php:302](../../../TCV-Backend/routes/api.php#L302) |
| `API-071` | POST | `api/payment/setup-intent` | PaymentController@createSetupIntent | `auth:sanctum` | [api.php:301](../../../TCV-Backend/routes/api.php#L301) |
| `API-072` | POST | `api/payment/webhook/{provider}` | PaymentController@handleWebhook | `auth:sanctum` | [api.php:307](../../../TCV-Backend/routes/api.php#L307) |
| `API-073` | GET | `api/price-details` | PriceDetailController@index | `auth:sanctum` | [api.php:289](../../../TCV-Backend/routes/api.php#L289) |
| `API-074` | POST | `api/price-details` | PriceDetailController@store | `auth:sanctum` | [api.php:289](../../../TCV-Backend/routes/api.php#L289) |
| `API-075` | DELETE | `api/price-details/{price_detail}` | PriceDetailController@destroy | `auth:sanctum` | [api.php:289](../../../TCV-Backend/routes/api.php#L289) |
| `API-076` | PUT|PATCH | `api/price-details/{price_detail}` | PriceDetailController@update | `auth:sanctum` | [api.php:289](../../../TCV-Backend/routes/api.php#L289) |
| `API-077` | GET | `api/profile` | ProfileController@show | `auth:sanctum` | [api.php:182](../../../TCV-Backend/routes/api.php#L182) |
| `API-078` | PUT | `api/profile` | ProfileController@update | `auth:sanctum` | [api.php:183](../../../TCV-Backend/routes/api.php#L183) |
| `API-079` | POST | `api/register` | AuthController@register | `throttle:register` | [api.php:36](../../../TCV-Backend/routes/api.php#L36) |
| `API-080` | GET | `api/reports/discount-codes` | ReportController@discountCode | `auth:sanctum` | [api.php:313](../../../TCV-Backend/routes/api.php#L313) |
| `API-081` | GET | `api/reports/list-patients-having-tests` | ReportController@getPatientsHavingTests | `auth:sanctum` | [api.php:311](../../../TCV-Backend/routes/api.php#L311) |
| `API-082` | GET | `api/reports/user-tests` | ReportController@userTestsReport | `auth:sanctum` | [api.php:312](../../../TCV-Backend/routes/api.php#L312) |
| `API-083` | POST | `api/resend-test-link` | PatientController@resendTestLink | `auth:sanctum` | [api.php:201](../../../TCV-Backend/routes/api.php#L201) |
| `API-084` | POST | `api/resend-verification-by-token` | AuthController@resendVerificationByToken | — | [api.php:42](../../../TCV-Backend/routes/api.php#L42) |
| `API-085` | POST | `api/resend_email_verification_link` | AuthController@resendEmailVerificationLink | — | [api.php:41](../../../TCV-Backend/routes/api.php#L41) |
| `API-086` | GET | `api/reset-password/{token}` | _(closure)_ | — | [api.php:46](../../../TCV-Backend/routes/api.php#L46) |
| `API-087` | GET | `api/restricted-ips` | RestrictedIpController@index | `auth:sanctum` | [api.php:278](../../../TCV-Backend/routes/api.php#L278) |
| `API-088` | POST | `api/restricted-ips` | RestrictedIpController@store | `auth:sanctum` | [api.php:278](../../../TCV-Backend/routes/api.php#L278) |
| `API-089` | DELETE | `api/restricted-ips/{restricted_ip}` | RestrictedIpController@destroy | `auth:sanctum` | [api.php:278](../../../TCV-Backend/routes/api.php#L278) |
| `API-090` | PUT|PATCH | `api/restricted-ips/{restricted_ip}` | RestrictedIpController@update | `auth:sanctum` | [api.php:278](../../../TCV-Backend/routes/api.php#L278) |
| `API-091` | POST | `api/stop-impersonate/{id}` | AuthController@stopImpersonation | `auth:sanctum` | [api.php:292](../../../TCV-Backend/routes/api.php#L292) |
| `API-092` | POST | `api/stripe/confirm-payment` | StripePaymentController@confirmPayment | `auth:sanctum` | [api.php:161](../../../TCV-Backend/routes/api.php#L161) |
| `API-093` | POST | `api/stripe/create-payment-intent` | StripePaymentController@createPaymentIntent | `auth:sanctum` | [api.php:160](../../../TCV-Backend/routes/api.php#L160) |
| `API-094` | GET | `api/stripe/payment-methods` | StripePaymentController@getPaymentMethods | `auth:sanctum` | [api.php:162](../../../TCV-Backend/routes/api.php#L162) |
| `API-095` | POST | `api/stripe/payment-methods/set-default` | StripePaymentController@setDefaultPaymentMethod | `auth:sanctum` | [api.php:163](../../../TCV-Backend/routes/api.php#L163) |
| `API-096` | DELETE | `api/stripe/payment-methods/{payment_method_id}` | StripePaymentController@removePaymentMethod | `auth:sanctum` | [api.php:164](../../../TCV-Backend/routes/api.php#L164) |
| `API-097` | GET | `api/stripe/transactions` | PaymentController@getTransactions | `auth:sanctum` | [api.php:298](../../../TCV-Backend/routes/api.php#L298) |
| `API-098` | GET | `api/super-admin/dashboard` | SuperAdminDashboardController@index | `auth:sanctum` | [api.php:140](../../../TCV-Backend/routes/api.php#L140) |
| `API-099` | GET | `api/test-email-templates` | TestEmailTemplateController@index | `auth:sanctum` | [api.php:257](../../../TCV-Backend/routes/api.php#L257) |
| `API-100` | GET | `api/test-email-templates/placeholders/{type}` | TestEmailTemplateController@getPlaceholders | `auth:sanctum` | [api.php:259](../../../TCV-Backend/routes/api.php#L259) |
| `API-101` | PUT | `api/test-email-templates/{id}` | TestEmailTemplateController@update | `auth:sanctum` | [api.php:258](../../../TCV-Backend/routes/api.php#L258) |
| `API-102` | POST | `api/test-invitation/check-validity` | TestInvitationController@checkTokenStatus | — | [api.php:72](../../../TCV-Backend/routes/api.php#L72) |
| `API-103` | POST | `api/test-invitation/verify-code` | TestInvitationController@verifyCode | — | [api.php:71](../../../TCV-Backend/routes/api.php#L71) |
| `API-104` | POST | `api/test-invitations/send` | TestInvitationController@sendInvitations | `auth:sanctum` · `throttle:bulk-invitations` | [api.php:204](../../../TCV-Backend/routes/api.php#L204) |
| `API-105` | GET | `api/test-invitations/unregistered` | TestInvitationController@getUnregisteredInvitations | `auth:sanctum` | [api.php:239](../../../TCV-Backend/routes/api.php#L239) |
| `API-106` | POST | `api/test-invitations/{id}/cancel` | TestInvitationController@cancelUnregisteredInvitation | `auth:sanctum` | [api.php:241](../../../TCV-Backend/routes/api.php#L241) |
| `API-107` | POST | `api/test-invitations/{id}/resend` | TestInvitationController@resendUnregisteredInvitation | `auth:sanctum` | [api.php:240](../../../TCV-Backend/routes/api.php#L240) |
| `API-108` | GET | `api/test-result/{unique_test_id}` | TestController@getTestResult | `FlexibleAuthMiddleware` | [api.php:126](../../../TCV-Backend/routes/api.php#L126) |
| `API-109` | GET | `api/test-result/{unique_test_id}/download-pdf` | TestController@downloadTestResultPDF | `FlexibleAuthMiddleware` | [api.php:127](../../../TCV-Backend/routes/api.php#L127) |
| `API-110` | GET | `api/test-session/{unique_test_id}` | TestController@getTestSession | `FlexibleAuthMiddleware` | [api.php:114](../../../TCV-Backend/routes/api.php#L114) |
| `API-111` | GET | `api/test-session/{unique_test_id}/plate/{test_answer_id}/url` | TestController@getPlateUrl | `FlexibleAuthMiddleware` · `lms.status:test_assigned` · `throttle:plate-url` | [api.php:120](../../../TCV-Backend/routes/api.php#L120) |
| `API-112` | GET | `api/test-session/{unique_test_id}/section/{section_id}/plates` | TestController@getSectionPlates | `FlexibleAuthMiddleware` · `lms.status:test_assigned` | [api.php:118](../../../TCV-Backend/routes/api.php#L118) |
| `API-113` | POST | `api/test/resume` | TestResumeController@resume | — | [api.php:75](../../../TCV-Backend/routes/api.php#L75) |
| `API-114` | POST | `api/test/send-resume-email` | TestResumeController@sendResumeEmail | `FlexibleAuthMiddleware` · `throttle:send-resume-email` | [api.php:82](../../../TCV-Backend/routes/api.php#L82) |
| `API-115` | GET | `api/tests` | TestController@index | `auth:sanctum` | [api.php:192](../../../TCV-Backend/routes/api.php#L192) |
| `API-116` | POST | `api/tests` | TestController@store | `auth:sanctum` | [api.php:192](../../../TCV-Backend/routes/api.php#L192) |
| `API-117` | POST | `api/tests/assign` | TestController@assignTest | `FlexibleAuthMiddleware` · `lms.status:form_submitted` | [api.php:96](../../../TCV-Backend/routes/api.php#L96) |
| `API-118` | POST | `api/tests/check-active` | TestController@getActiveTest | `FlexibleAuthMiddleware` · `lms.status:form_submitted,test_assigned` | [api.php:94](../../../TCV-Backend/routes/api.php#L94) |
| `API-119` | POST | `api/tests/perform` | TestController@performTest | `FlexibleAuthMiddleware` · `lms.status:test_assigned` | [api.php:131](../../../TCV-Backend/routes/api.php#L131) |
| `API-120` | POST | `api/tests/result-pdf` | TestController@generateTestResultPDF | `FlexibleAuthMiddleware` | [api.php:134](../../../TCV-Backend/routes/api.php#L134) |
| `API-121` | GET | `api/tests/{testID}/answers` | TestAnswerController@index | `auth:sanctum` | [api.php:196](../../../TCV-Backend/routes/api.php#L196) |
| `API-122` | POST | `api/tests/{testID}/answers` | TestAnswerController@store | `auth:sanctum` | [api.php:196](../../../TCV-Backend/routes/api.php#L196) |
| `API-123` | DELETE | `api/tests/{testID}/answers/{answer}` | TestAnswerController@destroy | `auth:sanctum` | [api.php:196](../../../TCV-Backend/routes/api.php#L196) |
| `API-124` | GET | `api/tests/{testID}/answers/{answer}` | TestAnswerController@show | `auth:sanctum` | [api.php:196](../../../TCV-Backend/routes/api.php#L196) |
| `API-125` | PUT|PATCH | `api/tests/{testID}/answers/{answer}` | TestAnswerController@update | `auth:sanctum` | [api.php:196](../../../TCV-Backend/routes/api.php#L196) |
| `API-126` | POST | `api/tests/{testID}/clone` | TestController@cloneTest | `auth:sanctum` | [api.php:199](../../../TCV-Backend/routes/api.php#L199) |
| `API-127` | GET | `api/tests/{testID}/conditions` | TestConditionController@index | `auth:sanctum` | [api.php:195](../../../TCV-Backend/routes/api.php#L195) |
| `API-128` | POST | `api/tests/{testID}/conditions` | TestConditionController@store | `auth:sanctum` | [api.php:195](../../../TCV-Backend/routes/api.php#L195) |
| `API-129` | DELETE | `api/tests/{testID}/conditions/{condition}` | TestConditionController@destroy | `auth:sanctum` | [api.php:195](../../../TCV-Backend/routes/api.php#L195) |
| `API-130` | GET | `api/tests/{testID}/conditions/{condition}` | TestConditionController@show | `auth:sanctum` | [api.php:195](../../../TCV-Backend/routes/api.php#L195) |
| `API-131` | PUT|PATCH | `api/tests/{testID}/conditions/{condition}` | TestConditionController@update | `auth:sanctum` | [api.php:195](../../../TCV-Backend/routes/api.php#L195) |
| `API-132` | GET | `api/tests/{testID}/section/plates` | TestSectionPlateController@index | `auth:sanctum` | [api.php:198](../../../TCV-Backend/routes/api.php#L198) |
| `API-133` | POST | `api/tests/{testID}/section/plates` | TestSectionPlateController@store | `auth:sanctum` | [api.php:198](../../../TCV-Backend/routes/api.php#L198) |
| `API-134` | DELETE | `api/tests/{testID}/section/plates/{plate}` | TestSectionPlateController@destroy | `auth:sanctum` | [api.php:198](../../../TCV-Backend/routes/api.php#L198) |
| `API-135` | GET | `api/tests/{testID}/section/plates/{plate}` | TestSectionPlateController@show | `auth:sanctum` | [api.php:198](../../../TCV-Backend/routes/api.php#L198) |
| `API-136` | PUT|PATCH | `api/tests/{testID}/section/plates/{plate}` | TestSectionPlateController@update | `auth:sanctum` | [api.php:198](../../../TCV-Backend/routes/api.php#L198) |
| `API-137` | GET | `api/tests/{testID}/sections` | TestSectionController@index | `auth:sanctum` | [api.php:197](../../../TCV-Backend/routes/api.php#L197) |
| `API-138` | POST | `api/tests/{testID}/sections` | TestSectionController@store | `auth:sanctum` | [api.php:197](../../../TCV-Backend/routes/api.php#L197) |
| `API-139` | DELETE | `api/tests/{testID}/sections/{section}` | TestSectionController@destroy | `auth:sanctum` | [api.php:197](../../../TCV-Backend/routes/api.php#L197) |
| `API-140` | GET | `api/tests/{testID}/sections/{section}` | TestSectionController@show | `auth:sanctum` | [api.php:197](../../../TCV-Backend/routes/api.php#L197) |
| `API-141` | PUT|PATCH | `api/tests/{testID}/sections/{section}` | TestSectionController@update | `auth:sanctum` | [api.php:197](../../../TCV-Backend/routes/api.php#L197) |
| `API-142` | DELETE | `api/tests/{test}` | TestController@destroy | `auth:sanctum` | [api.php:192](../../../TCV-Backend/routes/api.php#L192) |
| `API-143` | GET | `api/tests/{test}` | TestController@show | `auth:sanctum` | [api.php:192](../../../TCV-Backend/routes/api.php#L192) |
| `API-144` | PUT|PATCH | `api/tests/{test}` | TestController@update | `auth:sanctum` | [api.php:192](../../../TCV-Backend/routes/api.php#L192) |
| `API-145` | DELETE | `api/user-email-template` | UserEmailTemplateController@destroy | `auth:sanctum` | [api.php:320](../../../TCV-Backend/routes/api.php#L320) |
| `API-146` | GET | `api/user-email-template` | UserEmailTemplateController@show | `auth:sanctum` | [api.php:318](../../../TCV-Backend/routes/api.php#L318) |
| `API-147` | PUT | `api/user-email-template` | UserEmailTemplateController@update | `auth:sanctum` | [api.php:319](../../../TCV-Backend/routes/api.php#L319) |
| `API-148` | GET | `api/user/credit-history` | PaymentController@getCreditHistory | `auth:sanctum` | [api.php:300](../../../TCV-Backend/routes/api.php#L300) |
| `API-149` | GET | `api/user/credits` | UserController@getUserCredits | `auth:sanctum` | [api.php:220](../../../TCV-Backend/routes/api.php#L220) |
| `API-150` | GET | `api/user/tests` | TestController@userIndex | `auth:sanctum` | [api.php:209](../../../TCV-Backend/routes/api.php#L209) |
| `API-151` | GET | `api/user/tests/all` | TestController@getActiveTestsWithAssignmentFlag | `auth:sanctum` | [api.php:210](../../../TCV-Backend/routes/api.php#L210) |
| `API-152` | POST | `api/user/tests/bulk-update-assignment` | TestController@bulkUpdateAssignment | `auth:sanctum` | [api.php:216](../../../TCV-Backend/routes/api.php#L216) |
| `API-153` | GET | `api/user/tests/{id}` | TestController@show | `auth:sanctum` | [api.php:211](../../../TCV-Backend/routes/api.php#L211) |
| `API-154` | DELETE | `api/user/tests/{id}/assign` | TestController@unassignUserTest | `auth:sanctum` | [api.php:214](../../../TCV-Backend/routes/api.php#L214) |
| `API-155` | POST | `api/user/tests/{id}/assign` | TestController@assignUserTest | `auth:sanctum` | [api.php:213](../../../TCV-Backend/routes/api.php#L213) |
| `API-156` | GET | `api/users` | UserController@index | `auth:sanctum` | [api.php:175](../../../TCV-Backend/routes/api.php#L175) |
| `API-157` | POST | `api/users` | UserController@store | `auth:sanctum` | [api.php:175](../../../TCV-Backend/routes/api.php#L175) |
| `API-158` | PUT | `api/users/change-password` | AuthController@changePassword | `auth:sanctum` | [api.php:167](../../../TCV-Backend/routes/api.php#L167) |
| `API-159` | GET | `api/users/type/{usertype}` | UserController@userWithType | `auth:sanctum` | [api.php:178](../../../TCV-Backend/routes/api.php#L178) |
| `API-160` | GET | `api/users/{id}` | UserController@edit | `auth:sanctum` | [api.php:176](../../../TCV-Backend/routes/api.php#L176) |
| `API-161` | DELETE | `api/users/{user}` | UserController@destroy | `auth:sanctum` | [api.php:175](../../../TCV-Backend/routes/api.php#L175) |
| `API-162` | PUT|PATCH | `api/users/{user}` | UserController@update | `auth:sanctum` | [api.php:175](../../../TCV-Backend/routes/api.php#L175) |
| `API-163` | GET | `api/validate-token` | AuthController@isTokenValid | — | [api.php:51](../../../TCV-Backend/routes/api.php#L51) |
| `API-164` | POST | `api/verify-email-token` | AuthController@verifyEmailByToken | — | [api.php:40](../../../TCV-Backend/routes/api.php#L40) |
| `API-165` | GET | `api/verify-email/{id}/{hash}` | AuthController@verifyEmail | `signed` | [api.php:37](../../../TCV-Backend/routes/api.php#L37) |
| `API-166` | POST | `api/verify-password` | AuthController@verifyPassword | `auth:sanctum` | [api.php:276](../../../TCV-Backend/routes/api.php#L276) |

## Non-`api/` routes (`routes/web.php`)

| Method | URI | Action | Middleware |
|---|---|---|---|
| GET | `/` | _(closure)_ | — |
| GET | `payment/callback` | StripePaymentController@paymentCallback | — |

---

_Generated from source by `tools/extract.php` + `tools/extract-clients.php` + `tools/render.php` on 2026-10-13. Do not hand-edit — re-run the generator._
