# API Endpoint Index

Every `api/*` route in TCV-Backend, with the middleware that actually executes — the group stack a
route is physically nested inside, resolved the way Laravel resolves it.

**177 endpoints.** Auth column: `auth:sanctum` = Sanctum token required · `FlexibleAuthMiddleware` = **four** accepted token kinds (see [AUTH_CONTEXT](../CONTEXT/AUTH_CONTEXT.md)) · `—` = **public**.

> Route source: `AST static parse of routes/api.php + routes/web.php (TCV-Backend/vendor absent, so artisan cannot boot)`.
> `RestrictIpMiddleware` is appended **globally** in `bootstrap/app.php` and therefore runs on every
> row below; it is omitted from the table rather than repeated 179 times.

| ID | Method | URI | Action | Middleware | Route file |
|---|---|---|---|---|---|
| `API-001` | GET | `api/admin/lms/dead-letters` | LmsAdminController@deadLetters | `auth:sanctum` | [api.php:257](../../../TCV-Backend/routes/api.php#L257) |
| `API-002` | POST | `api/admin/lms/dead-letters/{id}/dismiss` | LmsAdminController@dismiss | `auth:sanctum` | [api.php:259](../../../TCV-Backend/routes/api.php#L259) |
| `API-003` | POST | `api/admin/lms/dead-letters/{id}/replay` | LmsAdminController@replay | `auth:sanctum` | [api.php:258](../../../TCV-Backend/routes/api.php#L258) |
| `API-004` | GET | `api/admin/lms/delivery-status` | LmsAdminController@deliveryStatus | `auth:sanctum` | [api.php:260](../../../TCV-Backend/routes/api.php#L260) |
| `API-005` | GET | `api/admin/lms/provider-configs` | LmsAdminController@listProviderConfigs | `auth:sanctum` | [api.php:253](../../../TCV-Backend/routes/api.php#L253) |
| `API-006` | POST | `api/admin/lms/provider-configs` | LmsAdminController@upsertProviderConfig | `auth:sanctum` | [api.php:254](../../../TCV-Backend/routes/api.php#L254) |
| `API-007` | POST | `api/admin/lms/provider-configs/{id}/rotate-key` | LmsAdminController@rotateSigningKey | `auth:sanctum` | [api.php:256](../../../TCV-Backend/routes/api.php#L256) |
| `API-008` | GET | `api/admin/lms/provider-configs/{id}/signing-key` | LmsAdminController@revealSigningKey | `auth:sanctum` | [api.php:255](../../../TCV-Backend/routes/api.php#L255) |
| `API-009` | POST | `api/contact` | ContactController@submit | `auth:sanctum` · `throttle:10,1` | [api.php:249](../../../TCV-Backend/routes/api.php#L249) |
| `API-010` | GET | `api/countries-with-states` | DropdownValuesController@getCountriesWithStates | — | [api.php:53](../../../TCV-Backend/routes/api.php#L53) |
| `API-011` | GET | `api/credits` | CreditsController@index | `auth:sanctum` | [api.php:175](../../../TCV-Backend/routes/api.php#L175) |
| `API-012` | POST | `api/credits` | CreditsController@store | `auth:sanctum` | [api.php:175](../../../TCV-Backend/routes/api.php#L175) |
| `API-013` | GET | `api/credits/create` | CreditsController@create | `auth:sanctum` | [api.php:175](../../../TCV-Backend/routes/api.php#L175) |
| `API-014` | GET | `api/credits/{coupon-code}` | CreditsController@checkDiscountCodeValidity | `auth:sanctum` | [api.php:176](../../../TCV-Backend/routes/api.php#L176) |
| `API-015` | DELETE | `api/credits/{credit}` | CreditsController@destroy | `auth:sanctum` | [api.php:175](../../../TCV-Backend/routes/api.php#L175) |
| `API-016` | GET | `api/credits/{credit}` | CreditsController@show | `auth:sanctum` | [api.php:175](../../../TCV-Backend/routes/api.php#L175) |
| `API-017` | PUT|PATCH | `api/credits/{credit}` | CreditsController@update | `auth:sanctum` | [api.php:175](../../../TCV-Backend/routes/api.php#L175) |
| `API-018` | GET | `api/credits/{credit}/edit` | CreditsController@edit | `auth:sanctum` | [api.php:175](../../../TCV-Backend/routes/api.php#L175) |
| `API-019` | GET | `api/discount-codes` | DiscountCodeController@index | `auth:sanctum` | [api.php:213](../../../TCV-Backend/routes/api.php#L213) |
| `API-020` | POST | `api/discount-codes` | DiscountCodeController@store | `auth:sanctum` | [api.php:213](../../../TCV-Backend/routes/api.php#L213) |
| `API-021` | GET | `api/discount-codes/form-options` | DiscountCodeController@formOptions | `auth:sanctum` | [api.php:210](../../../TCV-Backend/routes/api.php#L210) |
| `API-022` | GET | `api/discount-codes/stats` | DiscountCodeController@stats | `auth:sanctum` | [api.php:209](../../../TCV-Backend/routes/api.php#L209) |
| `API-023` | POST | `api/discount-codes/validate` | DiscountCodeController@validateCode | `auth:sanctum` | [api.php:211](../../../TCV-Backend/routes/api.php#L211) |
| `API-024` | DELETE | `api/discount-codes/{discount_code}` | DiscountCodeController@destroy | `auth:sanctum` | [api.php:213](../../../TCV-Backend/routes/api.php#L213) |
| `API-025` | GET | `api/discount-codes/{discount_code}` | DiscountCodeController@show | `auth:sanctum` | [api.php:213](../../../TCV-Backend/routes/api.php#L213) |
| `API-026` | PUT|PATCH | `api/discount-codes/{discount_code}` | DiscountCodeController@update | `auth:sanctum` | [api.php:213](../../../TCV-Backend/routes/api.php#L213) |
| `API-027` | PATCH | `api/discount-codes/{discount_code}/toggle` | DiscountCodeController@toggle | `auth:sanctum` | [api.php:212](../../../TCV-Backend/routes/api.php#L212) |
| `API-028` | GET | `api/dropdown/allowed-tests` | DropdownValuesController@activeAllowedTests | `auth:sanctum` | [api.php:201](../../../TCV-Backend/routes/api.php#L201) |
| `API-029` | GET | `api/dropdown/compliances` | DropdownValuesController@activeCompliances | `auth:sanctum` | [api.php:200](../../../TCV-Backend/routes/api.php#L200) |
| `API-030` | GET | `api/dropdown/organization-settings-options` | DropdownValuesController@activeOrgSettingsOptions | `auth:sanctum` | [api.php:203](../../../TCV-Backend/routes/api.php#L203) |
| `API-031` | GET | `api/dropdown/organization-types` | DropdownValuesController@activeOrganizationTypes | `auth:sanctum` | [api.php:199](../../../TCV-Backend/routes/api.php#L199) |
| `API-032` | GET | `api/dropdown/privileges` | DropdownValuesController@activePrivileges | `auth:sanctum` | [api.php:202](../../../TCV-Backend/routes/api.php#L202) |
| `API-033` | POST | `api/impersonate/{id}` | AuthController@impersonateUser | `auth:sanctum` | [api.php:218](../../../TCV-Backend/routes/api.php#L218) |
| `API-034` | POST | `api/login` | AuthController@login | — | [api.php:33](../../../TCV-Backend/routes/api.php#L33) |
| `API-035` | POST | `api/logout` | AuthController@logout | `auth:sanctum` | [api.php:191](../../../TCV-Backend/routes/api.php#L191) |
| `API-036` | POST | `api/organization/patient/default` | OrganizationPatientController@storeDefaultPatient | `FlexibleAuthMiddleware` · `lms.status:launched,identity_resolved` | [api.php:84](../../../TCV-Backend/routes/api.php#L84) |
| `API-037` | POST | `api/organization/patient/prolific` | OrganizationPatientController@storeProlificPatient | `FlexibleAuthMiddleware` · `lms.status:launched,identity_resolved` | [api.php:82](../../../TCV-Backend/routes/api.php#L82) |
| `API-038` | GET | `api/organization/patientForm` | OrganizationController@getPatientForm | `FlexibleAuthMiddleware` | [api.php:80](../../../TCV-Backend/routes/api.php#L80) |
| `API-039` | GET | `api/organization/privileges` | OrganizationController@getOrganizationPrivileges | `FlexibleAuthMiddleware` | [api.php:87](../../../TCV-Backend/routes/api.php#L87) |
| `API-040` | GET | `api/organization/redirect-url` | OrganizationController@getOrganizationRedirectUrl | `FlexibleAuthMiddleware` | [api.php:89](../../../TCV-Backend/routes/api.php#L89) |
| `API-041` | GET | `api/organization/test` | _(closure)_ | `auth:sanctum` · `signed` | [api.php:195](../../../TCV-Backend/routes/api.php#L195) |
| `API-042` | GET | `api/organization/tests/default` | OrganizationController@getDefaultTests | `FlexibleAuthMiddleware` | [api.php:78](../../../TCV-Backend/routes/api.php#L78) |
| `API-043` | POST | `api/organization/verify-signature` | OrganizationController@verifySignature | — | [api.php:57](../../../TCV-Backend/routes/api.php#L57) |
| `API-044` | GET | `api/organizations` | OrganizationController@index | `auth:sanctum` | [api.php:192](../../../TCV-Backend/routes/api.php#L192) |
| `API-045` | POST | `api/organizations` | OrganizationController@store | `auth:sanctum` | [api.php:192](../../../TCV-Backend/routes/api.php#L192) |
| `API-046` | POST | `api/organizations/{id}/upload-logo` | OrganizationController@uploadLogo | `auth:sanctum` | [api.php:193](../../../TCV-Backend/routes/api.php#L193) |
| `API-047` | DELETE | `api/organizations/{organization}` | OrganizationController@destroy | `auth:sanctum` | [api.php:192](../../../TCV-Backend/routes/api.php#L192) |
| `API-048` | GET | `api/organizations/{organization}` | OrganizationController@show | `auth:sanctum` | [api.php:192](../../../TCV-Backend/routes/api.php#L192) |
| `API-049` | PUT|PATCH | `api/organizations/{organization}` | OrganizationController@update | `auth:sanctum` | [api.php:192](../../../TCV-Backend/routes/api.php#L192) |
| `API-050` | PUT | `api/password/change` | PasswordController@update | `auth:sanctum` | [api.php:135](../../../TCV-Backend/routes/api.php#L135) |
| `API-051` | POST | `api/password/forgot` | AuthController@sendResetLinkEmail | — | [api.php:41](../../../TCV-Backend/routes/api.php#L41) |
| `API-052` | POST | `api/password/reset` | AuthController@setOrResetPassword | — | [api.php:42](../../../TCV-Backend/routes/api.php#L42) |
| `API-053` | POST | `api/password/verify-setup-token` | AuthController@verifySetupToken | — | [api.php:43](../../../TCV-Backend/routes/api.php#L43) |
| `API-054` | POST | `api/patient-tests/{identifier}/revoke-credit` | CreditsController@revokeCredit | `auth:sanctum` | [api.php:166](../../../TCV-Backend/routes/api.php#L166) |
| `API-055` | GET | `api/patients` | PatientController@index | `FlexibleAuthMiddleware` | [api.php:71](../../../TCV-Backend/routes/api.php#L71) |
| `API-056` | POST | `api/patients` | PatientController@store | `FlexibleAuthMiddleware` | [api.php:71](../../../TCV-Backend/routes/api.php#L71) |
| `API-057` | GET | `api/patients/create` | PatientController@create | `FlexibleAuthMiddleware` | [api.php:71](../../../TCV-Backend/routes/api.php#L71) |
| `API-058` | GET | `api/patients/{id}/tests` | PatientController@getPatientTests | `auth:sanctum` | [api.php:169](../../../TCV-Backend/routes/api.php#L169) |
| `API-059` | DELETE | `api/patients/{patient}` | PatientController@destroy | `FlexibleAuthMiddleware` | [api.php:71](../../../TCV-Backend/routes/api.php#L71) |
| `API-060` | GET | `api/patients/{patient}` | PatientController@show | `FlexibleAuthMiddleware` | [api.php:71](../../../TCV-Backend/routes/api.php#L71) |
| `API-061` | PUT|PATCH | `api/patients/{patient}` | PatientController@update | `FlexibleAuthMiddleware` | [api.php:71](../../../TCV-Backend/routes/api.php#L71) |
| `API-062` | GET | `api/patients/{patient}/edit` | PatientController@edit | `FlexibleAuthMiddleware` | [api.php:71](../../../TCV-Backend/routes/api.php#L71) |
| `API-063` | POST | `api/payment/confirm` | PaymentController@confirmPayment | `auth:sanctum` | [api.php:231](../../../TCV-Backend/routes/api.php#L231) |
| `API-064` | POST | `api/payment/initialize` | PaymentController@initializePayment | `auth:sanctum` | [api.php:230](../../../TCV-Backend/routes/api.php#L230) |
| `API-065` | GET | `api/payment/providers` | PaymentController@getProviders | `auth:sanctum` | [api.php:229](../../../TCV-Backend/routes/api.php#L229) |
| `API-066` | POST | `api/payment/setup-intent` | PaymentController@createSetupIntent | `auth:sanctum` | [api.php:228](../../../TCV-Backend/routes/api.php#L228) |
| `API-067` | POST | `api/payment/webhook/{provider}` | PaymentController@handleWebhook | `auth:sanctum` | [api.php:232](../../../TCV-Backend/routes/api.php#L232) |
| `API-068` | GET | `api/price-details` | PriceDetailController@index | `auth:sanctum` | [api.php:216](../../../TCV-Backend/routes/api.php#L216) |
| `API-069` | POST | `api/price-details` | PriceDetailController@store | `auth:sanctum` | [api.php:216](../../../TCV-Backend/routes/api.php#L216) |
| `API-070` | DELETE | `api/price-details/{price_detail}` | PriceDetailController@destroy | `auth:sanctum` | [api.php:216](../../../TCV-Backend/routes/api.php#L216) |
| `API-071` | PUT|PATCH | `api/price-details/{price_detail}` | PriceDetailController@update | `auth:sanctum` | [api.php:216](../../../TCV-Backend/routes/api.php#L216) |
| `API-072` | GET | `api/profile` | ProfileController@show | `auth:sanctum` | [api.php:130](../../../TCV-Backend/routes/api.php#L130) |
| `API-073` | PUT | `api/profile` | ProfileController@update | `auth:sanctum` | [api.php:131](../../../TCV-Backend/routes/api.php#L131) |
| `API-074` | POST | `api/register` | AuthController@register | — | [api.php:34](../../../TCV-Backend/routes/api.php#L34) |
| `API-075` | GET | `api/reports/discount-codes` | ReportController@discountCode | `auth:sanctum` | [api.php:238](../../../TCV-Backend/routes/api.php#L238) |
| `API-076` | GET | `api/reports/list-patients-having-tests` | ReportController@getPatientsHavingTests | `auth:sanctum` | [api.php:236](../../../TCV-Backend/routes/api.php#L236) |
| `API-077` | GET | `api/reports/user-tests` | ReportController@userTestsReport | `auth:sanctum` | [api.php:237](../../../TCV-Backend/routes/api.php#L237) |
| `API-078` | POST | `api/resend-test-link` | PatientController@resendTestLink | `auth:sanctum` | [api.php:147](../../../TCV-Backend/routes/api.php#L147) |
| `API-079` | POST | `api/resend-verification-by-token` | AuthController@resendVerificationByToken | — | [api.php:40](../../../TCV-Backend/routes/api.php#L40) |
| `API-080` | POST | `api/resend_email_verification_link` | AuthController@resendEmailVerificationLink | — | [api.php:39](../../../TCV-Backend/routes/api.php#L39) |
| `API-081` | GET | `api/reset-password/{token}` | _(closure)_ | — | [api.php:44](../../../TCV-Backend/routes/api.php#L44) |
| `API-082` | GET | `api/restricted-ips` | RestrictedIpController@index | `auth:sanctum` | [api.php:180](../../../TCV-Backend/routes/api.php#L180) |
| `API-083` | GET | `api/restricted-ips` | RestrictedIpController@index | `auth:sanctum` | [api.php:206](../../../TCV-Backend/routes/api.php#L206) |
| `API-084` | POST | `api/restricted-ips` | RestrictedIpController@store | `auth:sanctum` | [api.php:180](../../../TCV-Backend/routes/api.php#L180) |
| `API-085` | POST | `api/restricted-ips` | RestrictedIpController@store | `auth:sanctum` | [api.php:206](../../../TCV-Backend/routes/api.php#L206) |
| `API-086` | DELETE | `api/restricted-ips/{id}` | RestrictedIpController@destroy | `auth:sanctum` | [api.php:180](../../../TCV-Backend/routes/api.php#L180) |
| `API-087` | PUT|PATCH | `api/restricted-ips/{id}` | RestrictedIpController@update | `auth:sanctum` | [api.php:180](../../../TCV-Backend/routes/api.php#L180) |
| `API-088` | DELETE | `api/restricted-ips/{restricted_ip}` | RestrictedIpController@destroy | `auth:sanctum` | [api.php:206](../../../TCV-Backend/routes/api.php#L206) |
| `API-089` | PUT|PATCH | `api/restricted-ips/{restricted_ip}` | RestrictedIpController@update | `auth:sanctum` | [api.php:206](../../../TCV-Backend/routes/api.php#L206) |
| `API-090` | POST | `api/stop-impersonate/{id}` | AuthController@stopImpersonation | `auth:sanctum` | [api.php:219](../../../TCV-Backend/routes/api.php#L219) |
| `API-091` | POST | `api/stripe/confirm-payment` | StripePaymentController@confirmPayment | — | [api.php:49](../../../TCV-Backend/routes/api.php#L49) |
| `API-092` | POST | `api/stripe/create-payment-intent` | StripePaymentController@createPaymentIntent | — | [api.php:48](../../../TCV-Backend/routes/api.php#L48) |
| `API-093` | GET | `api/stripe/payment-methods` | StripePaymentController@getPaymentMethods | — | [api.php:50](../../../TCV-Backend/routes/api.php#L50) |
| `API-094` | POST | `api/stripe/payment-methods/set-default` | StripePaymentController@setDefaultPaymentMethod | — | [api.php:51](../../../TCV-Backend/routes/api.php#L51) |
| `API-095` | DELETE | `api/stripe/payment-methods/{payment_method_id}` | StripePaymentController@removePaymentMethod | — | [api.php:52](../../../TCV-Backend/routes/api.php#L52) |
| `API-096` | GET | `api/stripe/transactions` | PaymentController@getTransactions | `auth:sanctum` | [api.php:225](../../../TCV-Backend/routes/api.php#L225) |
| `API-097` | GET | `api/super-admin/dashboard` | SuperAdminDashboardController@index | `auth:sanctum` | [api.php:119](../../../TCV-Backend/routes/api.php#L119) |
| `API-098` | GET | `api/test-email-templates` | TestEmailTemplateController@index | `auth:sanctum` | [api.php:185](../../../TCV-Backend/routes/api.php#L185) |
| `API-099` | GET | `api/test-email-templates/placeholders/{type}` | TestEmailTemplateController@getPlaceholders | `auth:sanctum` | [api.php:187](../../../TCV-Backend/routes/api.php#L187) |
| `API-100` | PUT | `api/test-email-templates/{id}` | TestEmailTemplateController@update | `auth:sanctum` | [api.php:186](../../../TCV-Backend/routes/api.php#L186) |
| `API-101` | POST | `api/test-invitation/check-validity` | TestInvitationController@checkTokenStatus | — | [api.php:62](../../../TCV-Backend/routes/api.php#L62) |
| `API-102` | POST | `api/test-invitation/verify-code` | TestInvitationController@verifyCode | — | [api.php:61](../../../TCV-Backend/routes/api.php#L61) |
| `API-103` | POST | `api/test-invitations/send` | TestInvitationController@sendInvitations | — | [api.php:60](../../../TCV-Backend/routes/api.php#L60) |
| `API-104` | GET | `api/test-invitations/unregistered` | TestInvitationController@getUnregisteredInvitations | `auth:sanctum` | [api.php:172](../../../TCV-Backend/routes/api.php#L172) |
| `API-105` | POST | `api/test-invitations/{id}/cancel` | TestInvitationController@cancelUnregisteredInvitation | `auth:sanctum` | [api.php:174](../../../TCV-Backend/routes/api.php#L174) |
| `API-106` | POST | `api/test-invitations/{id}/resend` | TestInvitationController@resendUnregisteredInvitation | `auth:sanctum` | [api.php:173](../../../TCV-Backend/routes/api.php#L173) |
| `API-107` | GET | `api/test-result/{unique_test_id}` | TestController@getTestResult | `FlexibleAuthMiddleware` | [api.php:103](../../../TCV-Backend/routes/api.php#L103) |
| `API-108` | GET | `api/test-result/{unique_test_id}/download-pdf` | TestController@downloadTestResultPDF | `FlexibleAuthMiddleware` | [api.php:104](../../../TCV-Backend/routes/api.php#L104) |
| `API-109` | GET | `api/test-session/{unique_test_id}` | TestController@getTestSession | `FlexibleAuthMiddleware` | [api.php:92](../../../TCV-Backend/routes/api.php#L92) |
| `API-110` | GET | `api/test-session/{unique_test_id}/plate/{test_answer_id}/url` | TestController@getPlateUrl | `FlexibleAuthMiddleware` · `lms.status:test_assigned` | [api.php:98](../../../TCV-Backend/routes/api.php#L98) |
| `API-111` | GET | `api/test-session/{unique_test_id}/section/{section_id}/plates` | TestController@getSectionPlates | `FlexibleAuthMiddleware` · `lms.status:test_assigned` | [api.php:96](../../../TCV-Backend/routes/api.php#L96) |
| `API-112` | POST | `api/test/resume` | TestResumeController@resume | — | [api.php:65](../../../TCV-Backend/routes/api.php#L65) |
| `API-113` | POST | `api/test/send-resume-email` | TestResumeController@sendResumeEmail | `FlexibleAuthMiddleware` | [api.php:69](../../../TCV-Backend/routes/api.php#L69) |
| `API-114` | GET | `api/tests` | TestController@index | `auth:sanctum` | [api.php:138](../../../TCV-Backend/routes/api.php#L138) |
| `API-115` | POST | `api/tests` | TestController@store | `auth:sanctum` | [api.php:138](../../../TCV-Backend/routes/api.php#L138) |
| `API-116` | POST | `api/tests/assign` | TestController@assignTest | `FlexibleAuthMiddleware` · `lms.status:form_submitted` | [api.php:74](../../../TCV-Backend/routes/api.php#L74) |
| `API-117` | POST | `api/tests/check-active` | TestController@getActiveTest | `FlexibleAuthMiddleware` · `lms.status:form_submitted,test_assigned` | [api.php:72](../../../TCV-Backend/routes/api.php#L72) |
| `API-118` | GET | `api/tests/create` | TestController@create | `auth:sanctum` | [api.php:138](../../../TCV-Backend/routes/api.php#L138) |
| `API-119` | POST | `api/tests/perform` | TestController@performTest | `FlexibleAuthMiddleware` · `lms.status:test_assigned` | [api.php:108](../../../TCV-Backend/routes/api.php#L108) |
| `API-120` | POST | `api/tests/result-pdf` | TestController@generateTestResultPDF | `FlexibleAuthMiddleware` | [api.php:111](../../../TCV-Backend/routes/api.php#L111) |
| `API-121` | GET | `api/tests/{testID}/answers` | TestAnswerController@index | `auth:sanctum` | [api.php:142](../../../TCV-Backend/routes/api.php#L142) |
| `API-122` | POST | `api/tests/{testID}/answers` | TestAnswerController@store | `auth:sanctum` | [api.php:142](../../../TCV-Backend/routes/api.php#L142) |
| `API-123` | GET | `api/tests/{testID}/answers/create` | TestAnswerController@create | `auth:sanctum` | [api.php:142](../../../TCV-Backend/routes/api.php#L142) |
| `API-124` | DELETE | `api/tests/{testID}/answers/{answer}` | TestAnswerController@destroy | `auth:sanctum` | [api.php:142](../../../TCV-Backend/routes/api.php#L142) |
| `API-125` | GET | `api/tests/{testID}/answers/{answer}` | TestAnswerController@show | `auth:sanctum` | [api.php:142](../../../TCV-Backend/routes/api.php#L142) |
| `API-126` | PUT|PATCH | `api/tests/{testID}/answers/{answer}` | TestAnswerController@update | `auth:sanctum` | [api.php:142](../../../TCV-Backend/routes/api.php#L142) |
| `API-127` | GET | `api/tests/{testID}/answers/{answer}/edit` | TestAnswerController@edit | `auth:sanctum` | [api.php:142](../../../TCV-Backend/routes/api.php#L142) |
| `API-128` | POST | `api/tests/{testID}/clone` | TestController@cloneTest | `auth:sanctum` | [api.php:145](../../../TCV-Backend/routes/api.php#L145) |
| `API-129` | GET | `api/tests/{testID}/conditions` | TestConditionController@index | `auth:sanctum` | [api.php:141](../../../TCV-Backend/routes/api.php#L141) |
| `API-130` | POST | `api/tests/{testID}/conditions` | TestConditionController@store | `auth:sanctum` | [api.php:141](../../../TCV-Backend/routes/api.php#L141) |
| `API-131` | GET | `api/tests/{testID}/conditions/create` | TestConditionController@create | `auth:sanctum` | [api.php:141](../../../TCV-Backend/routes/api.php#L141) |
| `API-132` | DELETE | `api/tests/{testID}/conditions/{condition}` | TestConditionController@destroy | `auth:sanctum` | [api.php:141](../../../TCV-Backend/routes/api.php#L141) |
| `API-133` | GET | `api/tests/{testID}/conditions/{condition}` | TestConditionController@show | `auth:sanctum` | [api.php:141](../../../TCV-Backend/routes/api.php#L141) |
| `API-134` | PUT|PATCH | `api/tests/{testID}/conditions/{condition}` | TestConditionController@update | `auth:sanctum` | [api.php:141](../../../TCV-Backend/routes/api.php#L141) |
| `API-135` | GET | `api/tests/{testID}/conditions/{condition}/edit` | TestConditionController@edit | `auth:sanctum` | [api.php:141](../../../TCV-Backend/routes/api.php#L141) |
| `API-136` | GET | `api/tests/{testID}/section/plates` | TestSectionPlateController@index | `auth:sanctum` | [api.php:144](../../../TCV-Backend/routes/api.php#L144) |
| `API-137` | POST | `api/tests/{testID}/section/plates` | TestSectionPlateController@store | `auth:sanctum` | [api.php:144](../../../TCV-Backend/routes/api.php#L144) |
| `API-138` | GET | `api/tests/{testID}/section/plates/create` | TestSectionPlateController@create | `auth:sanctum` | [api.php:144](../../../TCV-Backend/routes/api.php#L144) |
| `API-139` | DELETE | `api/tests/{testID}/section/plates/{plate}` | TestSectionPlateController@destroy | `auth:sanctum` | [api.php:144](../../../TCV-Backend/routes/api.php#L144) |
| `API-140` | GET | `api/tests/{testID}/section/plates/{plate}` | TestSectionPlateController@show | `auth:sanctum` | [api.php:144](../../../TCV-Backend/routes/api.php#L144) |
| `API-141` | PUT|PATCH | `api/tests/{testID}/section/plates/{plate}` | TestSectionPlateController@update | `auth:sanctum` | [api.php:144](../../../TCV-Backend/routes/api.php#L144) |
| `API-142` | GET | `api/tests/{testID}/section/plates/{plate}/edit` | TestSectionPlateController@edit | `auth:sanctum` | [api.php:144](../../../TCV-Backend/routes/api.php#L144) |
| `API-143` | GET | `api/tests/{testID}/sections` | TestSectionController@index | `auth:sanctum` | [api.php:143](../../../TCV-Backend/routes/api.php#L143) |
| `API-144` | POST | `api/tests/{testID}/sections` | TestSectionController@store | `auth:sanctum` | [api.php:143](../../../TCV-Backend/routes/api.php#L143) |
| `API-145` | GET | `api/tests/{testID}/sections/create` | TestSectionController@create | `auth:sanctum` | [api.php:143](../../../TCV-Backend/routes/api.php#L143) |
| `API-146` | DELETE | `api/tests/{testID}/sections/{section}` | TestSectionController@destroy | `auth:sanctum` | [api.php:143](../../../TCV-Backend/routes/api.php#L143) |
| `API-147` | GET | `api/tests/{testID}/sections/{section}` | TestSectionController@show | `auth:sanctum` | [api.php:143](../../../TCV-Backend/routes/api.php#L143) |
| `API-148` | PUT|PATCH | `api/tests/{testID}/sections/{section}` | TestSectionController@update | `auth:sanctum` | [api.php:143](../../../TCV-Backend/routes/api.php#L143) |
| `API-149` | GET | `api/tests/{testID}/sections/{section}/edit` | TestSectionController@edit | `auth:sanctum` | [api.php:143](../../../TCV-Backend/routes/api.php#L143) |
| `API-150` | DELETE | `api/tests/{test}` | TestController@destroy | `auth:sanctum` | [api.php:138](../../../TCV-Backend/routes/api.php#L138) |
| `API-151` | GET | `api/tests/{test}` | TestController@show | `auth:sanctum` | [api.php:138](../../../TCV-Backend/routes/api.php#L138) |
| `API-152` | PUT|PATCH | `api/tests/{test}` | TestController@update | `auth:sanctum` | [api.php:138](../../../TCV-Backend/routes/api.php#L138) |
| `API-153` | GET | `api/tests/{test}/edit` | TestController@edit | `auth:sanctum` | [api.php:138](../../../TCV-Backend/routes/api.php#L138) |
| `API-154` | DELETE | `api/user-email-template` | UserEmailTemplateController@destroy | `auth:sanctum` | [api.php:245](../../../TCV-Backend/routes/api.php#L245) |
| `API-155` | GET | `api/user-email-template` | UserEmailTemplateController@show | `auth:sanctum` | [api.php:243](../../../TCV-Backend/routes/api.php#L243) |
| `API-156` | PUT | `api/user-email-template` | UserEmailTemplateController@update | `auth:sanctum` | [api.php:244](../../../TCV-Backend/routes/api.php#L244) |
| `API-157` | GET | `api/user/credit-history` | PaymentController@getCreditHistory | `auth:sanctum` | [api.php:227](../../../TCV-Backend/routes/api.php#L227) |
| `API-158` | GET | `api/user/credits` | UserController@getUserCredits | `auth:sanctum` | [api.php:163](../../../TCV-Backend/routes/api.php#L163) |
| `API-159` | GET | `api/user/tests` | TestController@userIndex | `auth:sanctum` | [api.php:151](../../../TCV-Backend/routes/api.php#L151) |
| `API-160` | GET | `api/user/tests/all` | TestController@getActiveTestsWithAssignmentFlag | `auth:sanctum` | [api.php:152](../../../TCV-Backend/routes/api.php#L152) |
| `API-161` | POST | `api/user/tests/bulk-update-assignment` | TestController@bulkUpdateAssignment | `auth:sanctum` | [api.php:158](../../../TCV-Backend/routes/api.php#L158) |
| `API-162` | GET | `api/user/tests/{id}` | TestController@show | `auth:sanctum` | [api.php:153](../../../TCV-Backend/routes/api.php#L153) |
| `API-163` | DELETE | `api/user/tests/{id}/assign` | TestController@unassignUserTest | `auth:sanctum` | [api.php:156](../../../TCV-Backend/routes/api.php#L156) |
| `API-164` | POST | `api/user/tests/{id}/assign` | TestController@assignUserTest | `auth:sanctum` | [api.php:155](../../../TCV-Backend/routes/api.php#L155) |
| `API-165` | GET | `api/users` | UserController@index | `auth:sanctum` | [api.php:123](../../../TCV-Backend/routes/api.php#L123) |
| `API-166` | POST | `api/users` | UserController@store | `auth:sanctum` | [api.php:123](../../../TCV-Backend/routes/api.php#L123) |
| `API-167` | PUT | `api/users/change-password` | AuthController@changePassword | `auth:sanctum` | [api.php:122](../../../TCV-Backend/routes/api.php#L122) |
| `API-168` | GET | `api/users/create` | UserController@create | `auth:sanctum` | [api.php:123](../../../TCV-Backend/routes/api.php#L123) |
| `API-169` | GET | `api/users/type/{usertype}` | UserController@userWithType | `auth:sanctum` | [api.php:126](../../../TCV-Backend/routes/api.php#L126) |
| `API-170` | GET | `api/users/{id}` | UserController@edit | `auth:sanctum` | [api.php:124](../../../TCV-Backend/routes/api.php#L124) |
| `API-171` | DELETE | `api/users/{user}` | UserController@destroy | `auth:sanctum` | [api.php:123](../../../TCV-Backend/routes/api.php#L123) |
| `API-172` | PUT|PATCH | `api/users/{user}` | UserController@update | `auth:sanctum` | [api.php:123](../../../TCV-Backend/routes/api.php#L123) |
| `API-173` | GET | `api/users/{user}/edit` | UserController@edit | `auth:sanctum` | [api.php:123](../../../TCV-Backend/routes/api.php#L123) |
| `API-174` | GET | `api/validate-token` | AuthController@isTokenValid | — | [api.php:54](../../../TCV-Backend/routes/api.php#L54) |
| `API-175` | POST | `api/verify-email-token` | AuthController@verifyEmailByToken | — | [api.php:38](../../../TCV-Backend/routes/api.php#L38) |
| `API-176` | GET | `api/verify-email/{id}/{hash}` | AuthController@verifyEmail | `signed` | [api.php:35](../../../TCV-Backend/routes/api.php#L35) |
| `API-177` | POST | `api/verify-password` | AuthController@verifyPassword | `auth:sanctum` | [api.php:204](../../../TCV-Backend/routes/api.php#L204) |

## Non-`api/` routes (`routes/web.php`)

| Method | URI | Action | Middleware |
|---|---|---|---|
| GET | `/` | _(closure)_ | — |
| GET | `payment/callback` | StripePaymentController@paymentCallback | — |

---

_Generated from source by `tools/extract.php` + `tools/extract-clients.php` + `tools/render.php` on 2026-08-19. Do not hand-edit — re-run the generator._
