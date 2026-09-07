# API Endpoint Index

Every `api/*` route in TCV-Backend, with the middleware that actually executes — the group stack a
route is physically nested inside, resolved the way Laravel resolves it.

**178 endpoints.** Auth column: `auth:sanctum` = Sanctum token required · `FlexibleAuthMiddleware` = **four** accepted token kinds (see [AUTH_CONTEXT](../CONTEXT/AUTH_CONTEXT.md)) · `—` = **public**.

> Route source: `AST static parse of routes/api.php + routes/web.php (TCV-Backend/vendor absent, so artisan cannot boot)`.
> `RestrictIpMiddleware` is appended **globally** in `bootstrap/app.php` and therefore runs on every
> row below; it is omitted from the table rather than repeated 179 times.

| ID | Method | URI | Action | Middleware | Route file |
|---|---|---|---|---|---|
| `API-001` | GET | `api/admin/lms/dead-letters` | LmsAdminController@deadLetters | `auth:sanctum` | [api.php:262](../../../TCV-Backend/routes/api.php#L262) |
| `API-002` | POST | `api/admin/lms/dead-letters/{id}/dismiss` | LmsAdminController@dismiss | `auth:sanctum` | [api.php:264](../../../TCV-Backend/routes/api.php#L264) |
| `API-003` | POST | `api/admin/lms/dead-letters/{id}/replay` | LmsAdminController@replay | `auth:sanctum` | [api.php:263](../../../TCV-Backend/routes/api.php#L263) |
| `API-004` | GET | `api/admin/lms/delivery-status` | LmsAdminController@deliveryStatus | `auth:sanctum` | [api.php:265](../../../TCV-Backend/routes/api.php#L265) |
| `API-005` | GET | `api/admin/lms/provider-configs` | LmsAdminController@listProviderConfigs | `auth:sanctum` | [api.php:258](../../../TCV-Backend/routes/api.php#L258) |
| `API-006` | POST | `api/admin/lms/provider-configs` | LmsAdminController@upsertProviderConfig | `auth:sanctum` | [api.php:259](../../../TCV-Backend/routes/api.php#L259) |
| `API-007` | POST | `api/admin/lms/provider-configs/{id}/rotate-key` | LmsAdminController@rotateSigningKey | `auth:sanctum` | [api.php:261](../../../TCV-Backend/routes/api.php#L261) |
| `API-008` | GET | `api/admin/lms/provider-configs/{id}/signing-key` | LmsAdminController@revealSigningKey | `auth:sanctum` | [api.php:260](../../../TCV-Backend/routes/api.php#L260) |
| `API-009` | POST | `api/contact` | ContactController@submit | `auth:sanctum` · `throttle:10,1` | [api.php:254](../../../TCV-Backend/routes/api.php#L254) |
| `API-010` | GET | `api/countries-with-states` | DropdownValuesController@getCountriesWithStates | — | [api.php:53](../../../TCV-Backend/routes/api.php#L53) |
| `API-011` | GET | `api/credits` | CreditsController@index | `auth:sanctum` | [api.php:179](../../../TCV-Backend/routes/api.php#L179) |
| `API-012` | POST | `api/credits` | CreditsController@store | `auth:sanctum` | [api.php:179](../../../TCV-Backend/routes/api.php#L179) |
| `API-013` | GET | `api/credits/create` | CreditsController@create | `auth:sanctum` | [api.php:179](../../../TCV-Backend/routes/api.php#L179) |
| `API-014` | GET | `api/credits/{coupon-code}` | CreditsController@checkDiscountCodeValidity | `auth:sanctum` | [api.php:180](../../../TCV-Backend/routes/api.php#L180) |
| `API-015` | DELETE | `api/credits/{credit}` | CreditsController@destroy | `auth:sanctum` | [api.php:179](../../../TCV-Backend/routes/api.php#L179) |
| `API-016` | GET | `api/credits/{credit}` | CreditsController@show | `auth:sanctum` | [api.php:179](../../../TCV-Backend/routes/api.php#L179) |
| `API-017` | PUT|PATCH | `api/credits/{credit}` | CreditsController@update | `auth:sanctum` | [api.php:179](../../../TCV-Backend/routes/api.php#L179) |
| `API-018` | GET | `api/credits/{credit}/edit` | CreditsController@edit | `auth:sanctum` | [api.php:179](../../../TCV-Backend/routes/api.php#L179) |
| `API-019` | GET | `api/discount-codes` | DiscountCodeController@index | `auth:sanctum` | [api.php:218](../../../TCV-Backend/routes/api.php#L218) |
| `API-020` | POST | `api/discount-codes` | DiscountCodeController@store | `auth:sanctum` | [api.php:218](../../../TCV-Backend/routes/api.php#L218) |
| `API-021` | GET | `api/discount-codes/code-available` | DiscountCodeController@codeAvailable | `auth:sanctum` | [api.php:215](../../../TCV-Backend/routes/api.php#L215) |
| `API-022` | GET | `api/discount-codes/form-options` | DiscountCodeController@formOptions | `auth:sanctum` | [api.php:214](../../../TCV-Backend/routes/api.php#L214) |
| `API-023` | GET | `api/discount-codes/stats` | DiscountCodeController@stats | `auth:sanctum` | [api.php:213](../../../TCV-Backend/routes/api.php#L213) |
| `API-024` | POST | `api/discount-codes/validate` | DiscountCodeController@validateCode | `auth:sanctum` | [api.php:216](../../../TCV-Backend/routes/api.php#L216) |
| `API-025` | DELETE | `api/discount-codes/{discount_code}` | DiscountCodeController@destroy | `auth:sanctum` | [api.php:218](../../../TCV-Backend/routes/api.php#L218) |
| `API-026` | GET | `api/discount-codes/{discount_code}` | DiscountCodeController@show | `auth:sanctum` | [api.php:218](../../../TCV-Backend/routes/api.php#L218) |
| `API-027` | PUT|PATCH | `api/discount-codes/{discount_code}` | DiscountCodeController@update | `auth:sanctum` | [api.php:218](../../../TCV-Backend/routes/api.php#L218) |
| `API-028` | PATCH | `api/discount-codes/{discount_code}/toggle` | DiscountCodeController@toggle | `auth:sanctum` | [api.php:217](../../../TCV-Backend/routes/api.php#L217) |
| `API-029` | GET | `api/dropdown/allowed-tests` | DropdownValuesController@activeAllowedTests | `auth:sanctum` | [api.php:205](../../../TCV-Backend/routes/api.php#L205) |
| `API-030` | GET | `api/dropdown/compliances` | DropdownValuesController@activeCompliances | `auth:sanctum` | [api.php:204](../../../TCV-Backend/routes/api.php#L204) |
| `API-031` | GET | `api/dropdown/organization-settings-options` | DropdownValuesController@activeOrgSettingsOptions | `auth:sanctum` | [api.php:207](../../../TCV-Backend/routes/api.php#L207) |
| `API-032` | GET | `api/dropdown/organization-types` | DropdownValuesController@activeOrganizationTypes | `auth:sanctum` | [api.php:203](../../../TCV-Backend/routes/api.php#L203) |
| `API-033` | GET | `api/dropdown/privileges` | DropdownValuesController@activePrivileges | `auth:sanctum` | [api.php:206](../../../TCV-Backend/routes/api.php#L206) |
| `API-034` | POST | `api/impersonate/{id}` | AuthController@impersonateUser | `auth:sanctum` | [api.php:223](../../../TCV-Backend/routes/api.php#L223) |
| `API-035` | POST | `api/login` | AuthController@login | — | [api.php:33](../../../TCV-Backend/routes/api.php#L33) |
| `API-036` | POST | `api/logout` | AuthController@logout | `auth:sanctum` | [api.php:195](../../../TCV-Backend/routes/api.php#L195) |
| `API-037` | POST | `api/organization/patient/default` | OrganizationPatientController@storeDefaultPatient | `FlexibleAuthMiddleware` · `lms.status:launched,identity_resolved` | [api.php:85](../../../TCV-Backend/routes/api.php#L85) |
| `API-038` | POST | `api/organization/patient/prolific` | OrganizationPatientController@storeProlificPatient | `FlexibleAuthMiddleware` · `lms.status:launched,identity_resolved` | [api.php:83](../../../TCV-Backend/routes/api.php#L83) |
| `API-039` | GET | `api/organization/patientForm` | OrganizationController@getPatientForm | `FlexibleAuthMiddleware` | [api.php:81](../../../TCV-Backend/routes/api.php#L81) |
| `API-040` | GET | `api/organization/privileges` | OrganizationController@getOrganizationPrivileges | `FlexibleAuthMiddleware` | [api.php:88](../../../TCV-Backend/routes/api.php#L88) |
| `API-041` | GET | `api/organization/redirect-url` | OrganizationController@getOrganizationRedirectUrl | `FlexibleAuthMiddleware` | [api.php:90](../../../TCV-Backend/routes/api.php#L90) |
| `API-042` | GET | `api/organization/test` | _(closure)_ | `auth:sanctum` · `signed` | [api.php:199](../../../TCV-Backend/routes/api.php#L199) |
| `API-043` | GET | `api/organization/tests/default` | OrganizationController@getDefaultTests | `FlexibleAuthMiddleware` | [api.php:79](../../../TCV-Backend/routes/api.php#L79) |
| `API-044` | POST | `api/organization/verify-signature` | OrganizationController@verifySignature | — | [api.php:57](../../../TCV-Backend/routes/api.php#L57) |
| `API-045` | GET | `api/organizations` | OrganizationController@index | `auth:sanctum` | [api.php:196](../../../TCV-Backend/routes/api.php#L196) |
| `API-046` | POST | `api/organizations` | OrganizationController@store | `auth:sanctum` | [api.php:196](../../../TCV-Backend/routes/api.php#L196) |
| `API-047` | POST | `api/organizations/{id}/upload-logo` | OrganizationController@uploadLogo | `auth:sanctum` | [api.php:197](../../../TCV-Backend/routes/api.php#L197) |
| `API-048` | DELETE | `api/organizations/{organization}` | OrganizationController@destroy | `auth:sanctum` | [api.php:196](../../../TCV-Backend/routes/api.php#L196) |
| `API-049` | GET | `api/organizations/{organization}` | OrganizationController@show | `auth:sanctum` | [api.php:196](../../../TCV-Backend/routes/api.php#L196) |
| `API-050` | PUT|PATCH | `api/organizations/{organization}` | OrganizationController@update | `auth:sanctum` | [api.php:196](../../../TCV-Backend/routes/api.php#L196) |
| `API-051` | PUT | `api/password/change` | PasswordController@update | `auth:sanctum` | [api.php:136](../../../TCV-Backend/routes/api.php#L136) |
| `API-052` | POST | `api/password/forgot` | AuthController@sendResetLinkEmail | — | [api.php:41](../../../TCV-Backend/routes/api.php#L41) |
| `API-053` | POST | `api/password/reset` | AuthController@setOrResetPassword | — | [api.php:42](../../../TCV-Backend/routes/api.php#L42) |
| `API-054` | POST | `api/password/verify-setup-token` | AuthController@verifySetupToken | — | [api.php:43](../../../TCV-Backend/routes/api.php#L43) |
| `API-055` | POST | `api/patient-tests/{identifier}/revoke-credit` | CreditsController@revokeCredit | `auth:sanctum` | [api.php:170](../../../TCV-Backend/routes/api.php#L170) |
| `API-056` | GET | `api/patients` | PatientController@index | `FlexibleAuthMiddleware` | [api.php:72](../../../TCV-Backend/routes/api.php#L72) |
| `API-057` | POST | `api/patients` | PatientController@store | `FlexibleAuthMiddleware` | [api.php:72](../../../TCV-Backend/routes/api.php#L72) |
| `API-058` | GET | `api/patients/create` | PatientController@create | `FlexibleAuthMiddleware` | [api.php:72](../../../TCV-Backend/routes/api.php#L72) |
| `API-059` | GET | `api/patients/{id}/tests` | PatientController@getPatientTests | `auth:sanctum` | [api.php:173](../../../TCV-Backend/routes/api.php#L173) |
| `API-060` | DELETE | `api/patients/{patient}` | PatientController@destroy | `FlexibleAuthMiddleware` | [api.php:72](../../../TCV-Backend/routes/api.php#L72) |
| `API-061` | GET | `api/patients/{patient}` | PatientController@show | `FlexibleAuthMiddleware` | [api.php:72](../../../TCV-Backend/routes/api.php#L72) |
| `API-062` | PUT|PATCH | `api/patients/{patient}` | PatientController@update | `FlexibleAuthMiddleware` | [api.php:72](../../../TCV-Backend/routes/api.php#L72) |
| `API-063` | GET | `api/patients/{patient}/edit` | PatientController@edit | `FlexibleAuthMiddleware` | [api.php:72](../../../TCV-Backend/routes/api.php#L72) |
| `API-064` | POST | `api/payment/confirm` | PaymentController@confirmPayment | `auth:sanctum` | [api.php:236](../../../TCV-Backend/routes/api.php#L236) |
| `API-065` | POST | `api/payment/initialize` | PaymentController@initializePayment | `auth:sanctum` | [api.php:235](../../../TCV-Backend/routes/api.php#L235) |
| `API-066` | GET | `api/payment/providers` | PaymentController@getProviders | `auth:sanctum` | [api.php:234](../../../TCV-Backend/routes/api.php#L234) |
| `API-067` | POST | `api/payment/setup-intent` | PaymentController@createSetupIntent | `auth:sanctum` | [api.php:233](../../../TCV-Backend/routes/api.php#L233) |
| `API-068` | POST | `api/payment/webhook/{provider}` | PaymentController@handleWebhook | `auth:sanctum` | [api.php:237](../../../TCV-Backend/routes/api.php#L237) |
| `API-069` | GET | `api/price-details` | PriceDetailController@index | `auth:sanctum` | [api.php:221](../../../TCV-Backend/routes/api.php#L221) |
| `API-070` | POST | `api/price-details` | PriceDetailController@store | `auth:sanctum` | [api.php:221](../../../TCV-Backend/routes/api.php#L221) |
| `API-071` | DELETE | `api/price-details/{price_detail}` | PriceDetailController@destroy | `auth:sanctum` | [api.php:221](../../../TCV-Backend/routes/api.php#L221) |
| `API-072` | PUT|PATCH | `api/price-details/{price_detail}` | PriceDetailController@update | `auth:sanctum` | [api.php:221](../../../TCV-Backend/routes/api.php#L221) |
| `API-073` | GET | `api/profile` | ProfileController@show | `auth:sanctum` | [api.php:131](../../../TCV-Backend/routes/api.php#L131) |
| `API-074` | PUT | `api/profile` | ProfileController@update | `auth:sanctum` | [api.php:132](../../../TCV-Backend/routes/api.php#L132) |
| `API-075` | POST | `api/register` | AuthController@register | — | [api.php:34](../../../TCV-Backend/routes/api.php#L34) |
| `API-076` | GET | `api/reports/discount-codes` | ReportController@discountCode | `auth:sanctum` | [api.php:243](../../../TCV-Backend/routes/api.php#L243) |
| `API-077` | GET | `api/reports/list-patients-having-tests` | ReportController@getPatientsHavingTests | `auth:sanctum` | [api.php:241](../../../TCV-Backend/routes/api.php#L241) |
| `API-078` | GET | `api/reports/user-tests` | ReportController@userTestsReport | `auth:sanctum` | [api.php:242](../../../TCV-Backend/routes/api.php#L242) |
| `API-079` | POST | `api/resend-test-link` | PatientController@resendTestLink | `auth:sanctum` | [api.php:148](../../../TCV-Backend/routes/api.php#L148) |
| `API-080` | POST | `api/resend-verification-by-token` | AuthController@resendVerificationByToken | — | [api.php:40](../../../TCV-Backend/routes/api.php#L40) |
| `API-081` | POST | `api/resend_email_verification_link` | AuthController@resendEmailVerificationLink | — | [api.php:39](../../../TCV-Backend/routes/api.php#L39) |
| `API-082` | GET | `api/reset-password/{token}` | _(closure)_ | — | [api.php:44](../../../TCV-Backend/routes/api.php#L44) |
| `API-083` | GET | `api/restricted-ips` | RestrictedIpController@index | `auth:sanctum` | [api.php:184](../../../TCV-Backend/routes/api.php#L184) |
| `API-084` | GET | `api/restricted-ips` | RestrictedIpController@index | `auth:sanctum` | [api.php:210](../../../TCV-Backend/routes/api.php#L210) |
| `API-085` | POST | `api/restricted-ips` | RestrictedIpController@store | `auth:sanctum` | [api.php:184](../../../TCV-Backend/routes/api.php#L184) |
| `API-086` | POST | `api/restricted-ips` | RestrictedIpController@store | `auth:sanctum` | [api.php:210](../../../TCV-Backend/routes/api.php#L210) |
| `API-087` | DELETE | `api/restricted-ips/{id}` | RestrictedIpController@destroy | `auth:sanctum` | [api.php:184](../../../TCV-Backend/routes/api.php#L184) |
| `API-088` | PUT|PATCH | `api/restricted-ips/{id}` | RestrictedIpController@update | `auth:sanctum` | [api.php:184](../../../TCV-Backend/routes/api.php#L184) |
| `API-089` | DELETE | `api/restricted-ips/{restricted_ip}` | RestrictedIpController@destroy | `auth:sanctum` | [api.php:210](../../../TCV-Backend/routes/api.php#L210) |
| `API-090` | PUT|PATCH | `api/restricted-ips/{restricted_ip}` | RestrictedIpController@update | `auth:sanctum` | [api.php:210](../../../TCV-Backend/routes/api.php#L210) |
| `API-091` | POST | `api/stop-impersonate/{id}` | AuthController@stopImpersonation | `auth:sanctum` | [api.php:224](../../../TCV-Backend/routes/api.php#L224) |
| `API-092` | POST | `api/stripe/confirm-payment` | StripePaymentController@confirmPayment | — | [api.php:49](../../../TCV-Backend/routes/api.php#L49) |
| `API-093` | POST | `api/stripe/create-payment-intent` | StripePaymentController@createPaymentIntent | — | [api.php:48](../../../TCV-Backend/routes/api.php#L48) |
| `API-094` | GET | `api/stripe/payment-methods` | StripePaymentController@getPaymentMethods | — | [api.php:50](../../../TCV-Backend/routes/api.php#L50) |
| `API-095` | POST | `api/stripe/payment-methods/set-default` | StripePaymentController@setDefaultPaymentMethod | — | [api.php:51](../../../TCV-Backend/routes/api.php#L51) |
| `API-096` | DELETE | `api/stripe/payment-methods/{payment_method_id}` | StripePaymentController@removePaymentMethod | — | [api.php:52](../../../TCV-Backend/routes/api.php#L52) |
| `API-097` | GET | `api/stripe/transactions` | PaymentController@getTransactions | `auth:sanctum` | [api.php:230](../../../TCV-Backend/routes/api.php#L230) |
| `API-098` | GET | `api/super-admin/dashboard` | SuperAdminDashboardController@index | `auth:sanctum` | [api.php:120](../../../TCV-Backend/routes/api.php#L120) |
| `API-099` | GET | `api/test-email-templates` | TestEmailTemplateController@index | `auth:sanctum` | [api.php:189](../../../TCV-Backend/routes/api.php#L189) |
| `API-100` | GET | `api/test-email-templates/placeholders/{type}` | TestEmailTemplateController@getPlaceholders | `auth:sanctum` | [api.php:191](../../../TCV-Backend/routes/api.php#L191) |
| `API-101` | PUT | `api/test-email-templates/{id}` | TestEmailTemplateController@update | `auth:sanctum` | [api.php:190](../../../TCV-Backend/routes/api.php#L190) |
| `API-102` | POST | `api/test-invitation/check-validity` | TestInvitationController@checkTokenStatus | — | [api.php:63](../../../TCV-Backend/routes/api.php#L63) |
| `API-103` | POST | `api/test-invitation/verify-code` | TestInvitationController@verifyCode | — | [api.php:62](../../../TCV-Backend/routes/api.php#L62) |
| `API-104` | POST | `api/test-invitations/send` | TestInvitationController@sendInvitations | `auth:sanctum` | [api.php:151](../../../TCV-Backend/routes/api.php#L151) |
| `API-105` | GET | `api/test-invitations/unregistered` | TestInvitationController@getUnregisteredInvitations | `auth:sanctum` | [api.php:176](../../../TCV-Backend/routes/api.php#L176) |
| `API-106` | POST | `api/test-invitations/{id}/cancel` | TestInvitationController@cancelUnregisteredInvitation | `auth:sanctum` | [api.php:178](../../../TCV-Backend/routes/api.php#L178) |
| `API-107` | POST | `api/test-invitations/{id}/resend` | TestInvitationController@resendUnregisteredInvitation | `auth:sanctum` | [api.php:177](../../../TCV-Backend/routes/api.php#L177) |
| `API-108` | GET | `api/test-result/{unique_test_id}` | TestController@getTestResult | `FlexibleAuthMiddleware` | [api.php:104](../../../TCV-Backend/routes/api.php#L104) |
| `API-109` | GET | `api/test-result/{unique_test_id}/download-pdf` | TestController@downloadTestResultPDF | `FlexibleAuthMiddleware` | [api.php:105](../../../TCV-Backend/routes/api.php#L105) |
| `API-110` | GET | `api/test-session/{unique_test_id}` | TestController@getTestSession | `FlexibleAuthMiddleware` | [api.php:93](../../../TCV-Backend/routes/api.php#L93) |
| `API-111` | GET | `api/test-session/{unique_test_id}/plate/{test_answer_id}/url` | TestController@getPlateUrl | `FlexibleAuthMiddleware` · `lms.status:test_assigned` | [api.php:99](../../../TCV-Backend/routes/api.php#L99) |
| `API-112` | GET | `api/test-session/{unique_test_id}/section/{section_id}/plates` | TestController@getSectionPlates | `FlexibleAuthMiddleware` · `lms.status:test_assigned` | [api.php:97](../../../TCV-Backend/routes/api.php#L97) |
| `API-113` | POST | `api/test/resume` | TestResumeController@resume | — | [api.php:66](../../../TCV-Backend/routes/api.php#L66) |
| `API-114` | POST | `api/test/send-resume-email` | TestResumeController@sendResumeEmail | `FlexibleAuthMiddleware` | [api.php:70](../../../TCV-Backend/routes/api.php#L70) |
| `API-115` | GET | `api/tests` | TestController@index | `auth:sanctum` | [api.php:139](../../../TCV-Backend/routes/api.php#L139) |
| `API-116` | POST | `api/tests` | TestController@store | `auth:sanctum` | [api.php:139](../../../TCV-Backend/routes/api.php#L139) |
| `API-117` | POST | `api/tests/assign` | TestController@assignTest | `FlexibleAuthMiddleware` · `lms.status:form_submitted` | [api.php:75](../../../TCV-Backend/routes/api.php#L75) |
| `API-118` | POST | `api/tests/check-active` | TestController@getActiveTest | `FlexibleAuthMiddleware` · `lms.status:form_submitted,test_assigned` | [api.php:73](../../../TCV-Backend/routes/api.php#L73) |
| `API-119` | GET | `api/tests/create` | TestController@create | `auth:sanctum` | [api.php:139](../../../TCV-Backend/routes/api.php#L139) |
| `API-120` | POST | `api/tests/perform` | TestController@performTest | `FlexibleAuthMiddleware` · `lms.status:test_assigned` | [api.php:109](../../../TCV-Backend/routes/api.php#L109) |
| `API-121` | POST | `api/tests/result-pdf` | TestController@generateTestResultPDF | `FlexibleAuthMiddleware` | [api.php:112](../../../TCV-Backend/routes/api.php#L112) |
| `API-122` | GET | `api/tests/{testID}/answers` | TestAnswerController@index | `auth:sanctum` | [api.php:143](../../../TCV-Backend/routes/api.php#L143) |
| `API-123` | POST | `api/tests/{testID}/answers` | TestAnswerController@store | `auth:sanctum` | [api.php:143](../../../TCV-Backend/routes/api.php#L143) |
| `API-124` | GET | `api/tests/{testID}/answers/create` | TestAnswerController@create | `auth:sanctum` | [api.php:143](../../../TCV-Backend/routes/api.php#L143) |
| `API-125` | DELETE | `api/tests/{testID}/answers/{answer}` | TestAnswerController@destroy | `auth:sanctum` | [api.php:143](../../../TCV-Backend/routes/api.php#L143) |
| `API-126` | GET | `api/tests/{testID}/answers/{answer}` | TestAnswerController@show | `auth:sanctum` | [api.php:143](../../../TCV-Backend/routes/api.php#L143) |
| `API-127` | PUT|PATCH | `api/tests/{testID}/answers/{answer}` | TestAnswerController@update | `auth:sanctum` | [api.php:143](../../../TCV-Backend/routes/api.php#L143) |
| `API-128` | GET | `api/tests/{testID}/answers/{answer}/edit` | TestAnswerController@edit | `auth:sanctum` | [api.php:143](../../../TCV-Backend/routes/api.php#L143) |
| `API-129` | POST | `api/tests/{testID}/clone` | TestController@cloneTest | `auth:sanctum` | [api.php:146](../../../TCV-Backend/routes/api.php#L146) |
| `API-130` | GET | `api/tests/{testID}/conditions` | TestConditionController@index | `auth:sanctum` | [api.php:142](../../../TCV-Backend/routes/api.php#L142) |
| `API-131` | POST | `api/tests/{testID}/conditions` | TestConditionController@store | `auth:sanctum` | [api.php:142](../../../TCV-Backend/routes/api.php#L142) |
| `API-132` | GET | `api/tests/{testID}/conditions/create` | TestConditionController@create | `auth:sanctum` | [api.php:142](../../../TCV-Backend/routes/api.php#L142) |
| `API-133` | DELETE | `api/tests/{testID}/conditions/{condition}` | TestConditionController@destroy | `auth:sanctum` | [api.php:142](../../../TCV-Backend/routes/api.php#L142) |
| `API-134` | GET | `api/tests/{testID}/conditions/{condition}` | TestConditionController@show | `auth:sanctum` | [api.php:142](../../../TCV-Backend/routes/api.php#L142) |
| `API-135` | PUT|PATCH | `api/tests/{testID}/conditions/{condition}` | TestConditionController@update | `auth:sanctum` | [api.php:142](../../../TCV-Backend/routes/api.php#L142) |
| `API-136` | GET | `api/tests/{testID}/conditions/{condition}/edit` | TestConditionController@edit | `auth:sanctum` | [api.php:142](../../../TCV-Backend/routes/api.php#L142) |
| `API-137` | GET | `api/tests/{testID}/section/plates` | TestSectionPlateController@index | `auth:sanctum` | [api.php:145](../../../TCV-Backend/routes/api.php#L145) |
| `API-138` | POST | `api/tests/{testID}/section/plates` | TestSectionPlateController@store | `auth:sanctum` | [api.php:145](../../../TCV-Backend/routes/api.php#L145) |
| `API-139` | GET | `api/tests/{testID}/section/plates/create` | TestSectionPlateController@create | `auth:sanctum` | [api.php:145](../../../TCV-Backend/routes/api.php#L145) |
| `API-140` | DELETE | `api/tests/{testID}/section/plates/{plate}` | TestSectionPlateController@destroy | `auth:sanctum` | [api.php:145](../../../TCV-Backend/routes/api.php#L145) |
| `API-141` | GET | `api/tests/{testID}/section/plates/{plate}` | TestSectionPlateController@show | `auth:sanctum` | [api.php:145](../../../TCV-Backend/routes/api.php#L145) |
| `API-142` | PUT|PATCH | `api/tests/{testID}/section/plates/{plate}` | TestSectionPlateController@update | `auth:sanctum` | [api.php:145](../../../TCV-Backend/routes/api.php#L145) |
| `API-143` | GET | `api/tests/{testID}/section/plates/{plate}/edit` | TestSectionPlateController@edit | `auth:sanctum` | [api.php:145](../../../TCV-Backend/routes/api.php#L145) |
| `API-144` | GET | `api/tests/{testID}/sections` | TestSectionController@index | `auth:sanctum` | [api.php:144](../../../TCV-Backend/routes/api.php#L144) |
| `API-145` | POST | `api/tests/{testID}/sections` | TestSectionController@store | `auth:sanctum` | [api.php:144](../../../TCV-Backend/routes/api.php#L144) |
| `API-146` | GET | `api/tests/{testID}/sections/create` | TestSectionController@create | `auth:sanctum` | [api.php:144](../../../TCV-Backend/routes/api.php#L144) |
| `API-147` | DELETE | `api/tests/{testID}/sections/{section}` | TestSectionController@destroy | `auth:sanctum` | [api.php:144](../../../TCV-Backend/routes/api.php#L144) |
| `API-148` | GET | `api/tests/{testID}/sections/{section}` | TestSectionController@show | `auth:sanctum` | [api.php:144](../../../TCV-Backend/routes/api.php#L144) |
| `API-149` | PUT|PATCH | `api/tests/{testID}/sections/{section}` | TestSectionController@update | `auth:sanctum` | [api.php:144](../../../TCV-Backend/routes/api.php#L144) |
| `API-150` | GET | `api/tests/{testID}/sections/{section}/edit` | TestSectionController@edit | `auth:sanctum` | [api.php:144](../../../TCV-Backend/routes/api.php#L144) |
| `API-151` | DELETE | `api/tests/{test}` | TestController@destroy | `auth:sanctum` | [api.php:139](../../../TCV-Backend/routes/api.php#L139) |
| `API-152` | GET | `api/tests/{test}` | TestController@show | `auth:sanctum` | [api.php:139](../../../TCV-Backend/routes/api.php#L139) |
| `API-153` | PUT|PATCH | `api/tests/{test}` | TestController@update | `auth:sanctum` | [api.php:139](../../../TCV-Backend/routes/api.php#L139) |
| `API-154` | GET | `api/tests/{test}/edit` | TestController@edit | `auth:sanctum` | [api.php:139](../../../TCV-Backend/routes/api.php#L139) |
| `API-155` | DELETE | `api/user-email-template` | UserEmailTemplateController@destroy | `auth:sanctum` | [api.php:250](../../../TCV-Backend/routes/api.php#L250) |
| `API-156` | GET | `api/user-email-template` | UserEmailTemplateController@show | `auth:sanctum` | [api.php:248](../../../TCV-Backend/routes/api.php#L248) |
| `API-157` | PUT | `api/user-email-template` | UserEmailTemplateController@update | `auth:sanctum` | [api.php:249](../../../TCV-Backend/routes/api.php#L249) |
| `API-158` | GET | `api/user/credit-history` | PaymentController@getCreditHistory | `auth:sanctum` | [api.php:232](../../../TCV-Backend/routes/api.php#L232) |
| `API-159` | GET | `api/user/credits` | UserController@getUserCredits | `auth:sanctum` | [api.php:167](../../../TCV-Backend/routes/api.php#L167) |
| `API-160` | GET | `api/user/tests` | TestController@userIndex | `auth:sanctum` | [api.php:155](../../../TCV-Backend/routes/api.php#L155) |
| `API-161` | GET | `api/user/tests/all` | TestController@getActiveTestsWithAssignmentFlag | `auth:sanctum` | [api.php:156](../../../TCV-Backend/routes/api.php#L156) |
| `API-162` | POST | `api/user/tests/bulk-update-assignment` | TestController@bulkUpdateAssignment | `auth:sanctum` | [api.php:162](../../../TCV-Backend/routes/api.php#L162) |
| `API-163` | GET | `api/user/tests/{id}` | TestController@show | `auth:sanctum` | [api.php:157](../../../TCV-Backend/routes/api.php#L157) |
| `API-164` | DELETE | `api/user/tests/{id}/assign` | TestController@unassignUserTest | `auth:sanctum` | [api.php:160](../../../TCV-Backend/routes/api.php#L160) |
| `API-165` | POST | `api/user/tests/{id}/assign` | TestController@assignUserTest | `auth:sanctum` | [api.php:159](../../../TCV-Backend/routes/api.php#L159) |
| `API-166` | GET | `api/users` | UserController@index | `auth:sanctum` | [api.php:124](../../../TCV-Backend/routes/api.php#L124) |
| `API-167` | POST | `api/users` | UserController@store | `auth:sanctum` | [api.php:124](../../../TCV-Backend/routes/api.php#L124) |
| `API-168` | PUT | `api/users/change-password` | AuthController@changePassword | `auth:sanctum` | [api.php:123](../../../TCV-Backend/routes/api.php#L123) |
| `API-169` | GET | `api/users/create` | UserController@create | `auth:sanctum` | [api.php:124](../../../TCV-Backend/routes/api.php#L124) |
| `API-170` | GET | `api/users/type/{usertype}` | UserController@userWithType | `auth:sanctum` | [api.php:127](../../../TCV-Backend/routes/api.php#L127) |
| `API-171` | GET | `api/users/{id}` | UserController@edit | `auth:sanctum` | [api.php:125](../../../TCV-Backend/routes/api.php#L125) |
| `API-172` | DELETE | `api/users/{user}` | UserController@destroy | `auth:sanctum` | [api.php:124](../../../TCV-Backend/routes/api.php#L124) |
| `API-173` | PUT|PATCH | `api/users/{user}` | UserController@update | `auth:sanctum` | [api.php:124](../../../TCV-Backend/routes/api.php#L124) |
| `API-174` | GET | `api/users/{user}/edit` | UserController@edit | `auth:sanctum` | [api.php:124](../../../TCV-Backend/routes/api.php#L124) |
| `API-175` | GET | `api/validate-token` | AuthController@isTokenValid | — | [api.php:54](../../../TCV-Backend/routes/api.php#L54) |
| `API-176` | POST | `api/verify-email-token` | AuthController@verifyEmailByToken | — | [api.php:38](../../../TCV-Backend/routes/api.php#L38) |
| `API-177` | GET | `api/verify-email/{id}/{hash}` | AuthController@verifyEmail | `signed` | [api.php:35](../../../TCV-Backend/routes/api.php#L35) |
| `API-178` | POST | `api/verify-password` | AuthController@verifyPassword | `auth:sanctum` | [api.php:208](../../../TCV-Backend/routes/api.php#L208) |

## Non-`api/` routes (`routes/web.php`)

| Method | URI | Action | Middleware |
|---|---|---|---|
| GET | `/` | _(closure)_ | — |
| GET | `payment/callback` | StripePaymentController@paymentCallback | — |

---

_Generated from source by `tools/extract.php` + `tools/extract-clients.php` + `tools/render.php` on 2026-09-06. Do not hand-edit — re-run the generator._
