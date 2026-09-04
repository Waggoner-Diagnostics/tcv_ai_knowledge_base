# API Endpoint Index

Every `api/*` route in TCV-Backend, with the middleware that actually executes — the group stack a
route is physically nested inside, resolved the way Laravel resolves it.

**158 endpoints.** Auth column: `auth:sanctum` = Sanctum token required · `FlexibleAuthMiddleware` = **four** accepted token kinds (see [AUTH_CONTEXT](../CONTEXT/AUTH_CONTEXT.md)) · `—` = **public**.

> Route source: `artisan route:list --json`.
> `RestrictIpMiddleware` is appended **globally** in `bootstrap/app.php` and therefore runs on every
> row below; it is omitted from the table rather than repeated 179 times.

| ID | Method | URI | Action | Middleware | Route file |
|---|---|---|---|---|---|
| `API-001` | GET|HEAD | `api/admin/lms/dead-letters` | LmsAdminController@deadLetters | `auth:sanctum` | — |
| `API-002` | POST | `api/admin/lms/dead-letters/{id}/dismiss` | LmsAdminController@dismiss | `auth:sanctum` | — |
| `API-003` | POST | `api/admin/lms/dead-letters/{id}/replay` | LmsAdminController@replay | `auth:sanctum` | — |
| `API-004` | GET|HEAD | `api/admin/lms/delivery-status` | LmsAdminController@deliveryStatus | `auth:sanctum` | — |
| `API-005` | GET|HEAD | `api/admin/lms/provider-configs` | LmsAdminController@listProviderConfigs | `auth:sanctum` | — |
| `API-006` | POST | `api/admin/lms/provider-configs` | LmsAdminController@upsertProviderConfig | `auth:sanctum` | — |
| `API-007` | POST | `api/admin/lms/provider-configs/{id}/rotate-key` | LmsAdminController@rotateSigningKey | `auth:sanctum` | — |
| `API-008` | GET|HEAD | `api/admin/lms/provider-configs/{id}/signing-key` | LmsAdminController@revealSigningKey | `auth:sanctum` | — |
| `API-009` | POST | `api/contact` | ContactController@submit | `auth:sanctum` · `throttle:10,1` | — |
| `API-010` | GET|HEAD | `api/countries-with-states` | DropdownValuesController@getCountriesWithStates | — | — |
| `API-011` | GET|HEAD | `api/credits` | CreditsController@index | `auth:sanctum` | — |
| `API-012` | POST | `api/credits` | CreditsController@store | `auth:sanctum` | — |
| `API-013` | GET|HEAD | `api/credits/coupon/{coupon_code}` | CreditsController@checkDiscountCodeValidity | `auth:sanctum` | — |
| `API-014` | DELETE | `api/credits/{credit}` | CreditsController@destroy | `auth:sanctum` | — |
| `API-015` | GET|HEAD | `api/credits/{credit}` | CreditsController@show | `auth:sanctum` | — |
| `API-016` | PUT|PATCH | `api/credits/{credit}` | CreditsController@update | `auth:sanctum` | — |
| `API-017` | GET|HEAD | `api/discount-codes` | DiscountCodeController@index | `auth:sanctum` | — |
| `API-018` | POST | `api/discount-codes` | DiscountCodeController@store | `auth:sanctum` | — |
| `API-019` | GET|HEAD | `api/discount-codes/code-available` | DiscountCodeController@codeAvailable | `auth:sanctum` | — |
| `API-020` | GET|HEAD | `api/discount-codes/form-options` | DiscountCodeController@formOptions | `auth:sanctum` | — |
| `API-021` | GET|HEAD | `api/discount-codes/stats` | DiscountCodeController@stats | `auth:sanctum` | — |
| `API-022` | POST | `api/discount-codes/validate` | DiscountCodeController@validateCode | `auth:sanctum` | — |
| `API-023` | DELETE | `api/discount-codes/{discount_code}` | DiscountCodeController@destroy | `auth:sanctum` | — |
| `API-024` | GET|HEAD | `api/discount-codes/{discount_code}` | DiscountCodeController@show | `auth:sanctum` | — |
| `API-025` | PUT|PATCH | `api/discount-codes/{discount_code}` | DiscountCodeController@update | `auth:sanctum` | — |
| `API-026` | PATCH | `api/discount-codes/{discount_code}/toggle` | DiscountCodeController@toggle | `auth:sanctum` | — |
| `API-027` | GET|HEAD | `api/dropdown/allowed-tests` | DropdownValuesController@activeAllowedTests | `auth:sanctum` | — |
| `API-028` | GET|HEAD | `api/dropdown/compliances` | DropdownValuesController@activeCompliances | `auth:sanctum` | — |
| `API-029` | GET|HEAD | `api/dropdown/organization-settings-options` | DropdownValuesController@activeOrgSettingsOptions | `auth:sanctum` | — |
| `API-030` | GET|HEAD | `api/dropdown/organization-types` | DropdownValuesController@activeOrganizationTypes | `auth:sanctum` | — |
| `API-031` | GET|HEAD | `api/dropdown/privileges` | DropdownValuesController@activePrivileges | `auth:sanctum` | — |
| `API-032` | POST | `api/impersonate/{id}` | AuthController@impersonateUser | `auth:sanctum` | — |
| `API-033` | POST | `api/login` | AuthController@login | `throttle:login` | — |
| `API-034` | POST | `api/logout` | AuthController@logout | `auth:sanctum` | — |
| `API-035` | POST | `api/organization/patient/default` | OrganizationPatientController@storeDefaultPatient | `FlexibleAuthMiddleware` · `LmsSessionStatusMiddleware:launched,identity_resolved` | — |
| `API-036` | POST | `api/organization/patient/prolific` | OrganizationPatientController@storeProlificPatient | `FlexibleAuthMiddleware` · `LmsSessionStatusMiddleware:launched,identity_resolved` | — |
| `API-037` | GET|HEAD | `api/organization/patientForm` | OrganizationController@getPatientForm | `FlexibleAuthMiddleware` | — |
| `API-038` | GET|HEAD | `api/organization/privileges` | OrganizationController@getOrganizationPrivileges | `FlexibleAuthMiddleware` | — |
| `API-039` | GET|HEAD | `api/organization/redirect-url` | OrganizationController@getOrganizationRedirectUrl | `FlexibleAuthMiddleware` | — |
| `API-040` | GET|HEAD | `api/organization/test` | _(closure)_ | `auth:sanctum` · `signed` | — |
| `API-041` | GET|HEAD | `api/organization/tests/default` | OrganizationController@getDefaultTests | `FlexibleAuthMiddleware` | — |
| `API-042` | POST | `api/organization/verify-signature` | OrganizationController@verifySignature | `throttle:signature-verify` | — |
| `API-043` | GET|HEAD | `api/organizations` | OrganizationController@index | `auth:sanctum` | — |
| `API-044` | POST | `api/organizations` | OrganizationController@store | `auth:sanctum` | — |
| `API-045` | POST | `api/organizations/{id}/upload-logo` | OrganizationController@uploadLogo | `auth:sanctum` | — |
| `API-046` | DELETE | `api/organizations/{organization}` | OrganizationController@destroy | `auth:sanctum` | — |
| `API-047` | GET|HEAD | `api/organizations/{organization}` | OrganizationController@show | `auth:sanctum` | — |
| `API-048` | PUT|PATCH | `api/organizations/{organization}` | OrganizationController@update | `auth:sanctum` | — |
| `API-049` | PUT | `api/password/change` | PasswordController@update | `auth:sanctum` | — |
| `API-050` | POST | `api/password/forgot` | AuthController@sendResetLinkEmail | `throttle:password-reset` | — |
| `API-051` | POST | `api/password/reset` | AuthController@setOrResetPassword | `throttle:password-reset` | — |
| `API-052` | POST | `api/password/verify-setup-token` | AuthController@verifySetupToken | `throttle:password-reset` | — |
| `API-053` | POST | `api/patient-tests/{identifier}/revoke-credit` | CreditsController@revokeCredit | `auth:sanctum` | — |
| `API-054` | GET|HEAD | `api/patients` | PatientController@index | `FlexibleAuthMiddleware` | — |
| `API-055` | POST | `api/patients` | PatientController@store | `FlexibleAuthMiddleware` | — |
| `API-056` | GET|HEAD | `api/patients/{id}/tests` | PatientController@getPatientTests | `auth:sanctum` | — |
| `API-057` | DELETE | `api/patients/{patient}` | PatientController@destroy | `FlexibleAuthMiddleware` | — |
| `API-058` | GET|HEAD | `api/patients/{patient}` | PatientController@show | `FlexibleAuthMiddleware` | — |
| `API-059` | PUT|PATCH | `api/patients/{patient}` | PatientController@update | `FlexibleAuthMiddleware` | — |
| `API-060` | POST | `api/payment/confirm` | PaymentController@confirmPayment | `auth:sanctum` | — |
| `API-061` | POST | `api/payment/initialize` | PaymentController@initializePayment | `auth:sanctum` | — |
| `API-062` | GET|HEAD | `api/payment/providers` | PaymentController@getProviders | `auth:sanctum` | — |
| `API-063` | POST | `api/payment/setup-intent` | PaymentController@createSetupIntent | `auth:sanctum` | — |
| `API-064` | POST | `api/payment/webhook/{provider}` | PaymentController@handleWebhook | `auth:sanctum` | — |
| `API-065` | GET|HEAD | `api/price-details` | PriceDetailController@index | `auth:sanctum` | — |
| `API-066` | POST | `api/price-details` | PriceDetailController@store | `auth:sanctum` | — |
| `API-067` | DELETE | `api/price-details/{price_detail}` | PriceDetailController@destroy | `auth:sanctum` | — |
| `API-068` | PUT|PATCH | `api/price-details/{price_detail}` | PriceDetailController@update | `auth:sanctum` | — |
| `API-069` | GET|HEAD | `api/profile` | ProfileController@show | `auth:sanctum` | — |
| `API-070` | PUT | `api/profile` | ProfileController@update | `auth:sanctum` | — |
| `API-071` | POST | `api/register` | AuthController@register | `throttle:register` | — |
| `API-072` | GET|HEAD | `api/reports/discount-codes` | ReportController@discountCode | `auth:sanctum` | — |
| `API-073` | GET|HEAD | `api/reports/list-patients-having-tests` | ReportController@getPatientsHavingTests | `auth:sanctum` | — |
| `API-074` | GET|HEAD | `api/reports/user-tests` | ReportController@userTestsReport | `auth:sanctum` | — |
| `API-075` | POST | `api/resend-test-link` | PatientController@resendTestLink | `auth:sanctum` | — |
| `API-076` | POST | `api/resend-verification-by-token` | AuthController@resendVerificationByToken | — | — |
| `API-077` | POST | `api/resend_email_verification_link` | AuthController@resendEmailVerificationLink | — | — |
| `API-078` | GET|HEAD | `api/reset-password/{token}` | _(closure)_ | — | — |
| `API-079` | GET|HEAD | `api/restricted-ips` | RestrictedIpController@index | `auth:sanctum` | — |
| `API-080` | POST | `api/restricted-ips` | RestrictedIpController@store | `auth:sanctum` | — |
| `API-081` | DELETE | `api/restricted-ips/{restricted_ip}` | RestrictedIpController@destroy | `auth:sanctum` | — |
| `API-082` | PUT|PATCH | `api/restricted-ips/{restricted_ip}` | RestrictedIpController@update | `auth:sanctum` | — |
| `API-083` | POST | `api/stop-impersonate/{id}` | AuthController@stopImpersonation | `auth:sanctum` | — |
| `API-084` | POST | `api/stripe/confirm-payment` | StripePaymentController@confirmPayment | `auth:sanctum` | — |
| `API-085` | POST | `api/stripe/create-payment-intent` | StripePaymentController@createPaymentIntent | `auth:sanctum` | — |
| `API-086` | GET|HEAD | `api/stripe/payment-methods` | StripePaymentController@getPaymentMethods | `auth:sanctum` | — |
| `API-087` | POST | `api/stripe/payment-methods/set-default` | StripePaymentController@setDefaultPaymentMethod | `auth:sanctum` | — |
| `API-088` | DELETE | `api/stripe/payment-methods/{payment_method_id}` | StripePaymentController@removePaymentMethod | `auth:sanctum` | — |
| `API-089` | GET|HEAD | `api/stripe/transactions` | PaymentController@getTransactions | `auth:sanctum` | — |
| `API-090` | GET|HEAD | `api/super-admin/dashboard` | SuperAdminDashboardController@index | `auth:sanctum` | — |
| `API-091` | GET|HEAD | `api/test-email-templates` | TestEmailTemplateController@index | `auth:sanctum` | — |
| `API-092` | GET|HEAD | `api/test-email-templates/placeholders/{type}` | TestEmailTemplateController@getPlaceholders | `auth:sanctum` | — |
| `API-093` | PUT | `api/test-email-templates/{id}` | TestEmailTemplateController@update | `auth:sanctum` | — |
| `API-094` | POST | `api/test-invitation/check-validity` | TestInvitationController@checkTokenStatus | — | — |
| `API-095` | POST | `api/test-invitation/verify-code` | TestInvitationController@verifyCode | — | — |
| `API-096` | POST | `api/test-invitations/send` | TestInvitationController@sendInvitations | `auth:sanctum` · `throttle:bulk-invitations` | — |
| `API-097` | GET|HEAD | `api/test-invitations/unregistered` | TestInvitationController@getUnregisteredInvitations | `auth:sanctum` | — |
| `API-098` | POST | `api/test-invitations/{id}/cancel` | TestInvitationController@cancelUnregisteredInvitation | `auth:sanctum` | — |
| `API-099` | POST | `api/test-invitations/{id}/resend` | TestInvitationController@resendUnregisteredInvitation | `auth:sanctum` | — |
| `API-100` | GET|HEAD | `api/test-result/{unique_test_id}` | TestController@getTestResult | `FlexibleAuthMiddleware` | — |
| `API-101` | GET|HEAD | `api/test-result/{unique_test_id}/download-pdf` | TestController@downloadTestResultPDF | `FlexibleAuthMiddleware` | — |
| `API-102` | GET|HEAD | `api/test-session/{unique_test_id}` | TestController@getTestSession | `FlexibleAuthMiddleware` | — |
| `API-103` | GET|HEAD | `api/test-session/{unique_test_id}/plate/{test_answer_id}/url` | TestController@getPlateUrl | `FlexibleAuthMiddleware` · `LmsSessionStatusMiddleware:test_assigned` · `throttle:plate-url` | — |
| `API-104` | GET|HEAD | `api/test-session/{unique_test_id}/section/{section_id}/plates` | TestController@getSectionPlates | `FlexibleAuthMiddleware` · `LmsSessionStatusMiddleware:test_assigned` | — |
| `API-105` | POST | `api/test/resume` | TestResumeController@resume | — | — |
| `API-106` | POST | `api/test/send-resume-email` | TestResumeController@sendResumeEmail | `FlexibleAuthMiddleware` | — |
| `API-107` | GET|HEAD | `api/tests` | TestController@index | `auth:sanctum` | — |
| `API-108` | POST | `api/tests` | TestController@store | `auth:sanctum` | — |
| `API-109` | POST | `api/tests/assign` | TestController@assignTest | `FlexibleAuthMiddleware` · `LmsSessionStatusMiddleware:form_submitted` | — |
| `API-110` | POST | `api/tests/check-active` | TestController@getActiveTest | `FlexibleAuthMiddleware` · `LmsSessionStatusMiddleware:form_submitted,test_assigned` | — |
| `API-111` | POST | `api/tests/perform` | TestController@performTest | `FlexibleAuthMiddleware` · `LmsSessionStatusMiddleware:test_assigned` | — |
| `API-112` | POST | `api/tests/result-pdf` | TestController@generateTestResultPDF | `FlexibleAuthMiddleware` | — |
| `API-113` | GET|HEAD | `api/tests/{testID}/answers` | TestAnswerController@index | `auth:sanctum` | — |
| `API-114` | POST | `api/tests/{testID}/answers` | TestAnswerController@store | `auth:sanctum` | — |
| `API-115` | DELETE | `api/tests/{testID}/answers/{answer}` | TestAnswerController@destroy | `auth:sanctum` | — |
| `API-116` | GET|HEAD | `api/tests/{testID}/answers/{answer}` | TestAnswerController@show | `auth:sanctum` | — |
| `API-117` | PUT|PATCH | `api/tests/{testID}/answers/{answer}` | TestAnswerController@update | `auth:sanctum` | — |
| `API-118` | POST | `api/tests/{testID}/clone` | TestController@cloneTest | `auth:sanctum` | — |
| `API-119` | GET|HEAD | `api/tests/{testID}/conditions` | TestConditionController@index | `auth:sanctum` | — |
| `API-120` | POST | `api/tests/{testID}/conditions` | TestConditionController@store | `auth:sanctum` | — |
| `API-121` | DELETE | `api/tests/{testID}/conditions/{condition}` | TestConditionController@destroy | `auth:sanctum` | — |
| `API-122` | GET|HEAD | `api/tests/{testID}/conditions/{condition}` | TestConditionController@show | `auth:sanctum` | — |
| `API-123` | PUT|PATCH | `api/tests/{testID}/conditions/{condition}` | TestConditionController@update | `auth:sanctum` | — |
| `API-124` | GET|HEAD | `api/tests/{testID}/section/plates` | TestSectionPlateController@index | `auth:sanctum` | — |
| `API-125` | POST | `api/tests/{testID}/section/plates` | TestSectionPlateController@store | `auth:sanctum` | — |
| `API-126` | DELETE | `api/tests/{testID}/section/plates/{plate}` | TestSectionPlateController@destroy | `auth:sanctum` | — |
| `API-127` | GET|HEAD | `api/tests/{testID}/section/plates/{plate}` | TestSectionPlateController@show | `auth:sanctum` | — |
| `API-128` | PUT|PATCH | `api/tests/{testID}/section/plates/{plate}` | TestSectionPlateController@update | `auth:sanctum` | — |
| `API-129` | GET|HEAD | `api/tests/{testID}/sections` | TestSectionController@index | `auth:sanctum` | — |
| `API-130` | POST | `api/tests/{testID}/sections` | TestSectionController@store | `auth:sanctum` | — |
| `API-131` | DELETE | `api/tests/{testID}/sections/{section}` | TestSectionController@destroy | `auth:sanctum` | — |
| `API-132` | GET|HEAD | `api/tests/{testID}/sections/{section}` | TestSectionController@show | `auth:sanctum` | — |
| `API-133` | PUT|PATCH | `api/tests/{testID}/sections/{section}` | TestSectionController@update | `auth:sanctum` | — |
| `API-134` | DELETE | `api/tests/{test}` | TestController@destroy | `auth:sanctum` | — |
| `API-135` | GET|HEAD | `api/tests/{test}` | TestController@show | `auth:sanctum` | — |
| `API-136` | PUT|PATCH | `api/tests/{test}` | TestController@update | `auth:sanctum` | — |
| `API-137` | DELETE | `api/user-email-template` | UserEmailTemplateController@destroy | `auth:sanctum` | — |
| `API-138` | GET|HEAD | `api/user-email-template` | UserEmailTemplateController@show | `auth:sanctum` | — |
| `API-139` | PUT | `api/user-email-template` | UserEmailTemplateController@update | `auth:sanctum` | — |
| `API-140` | GET|HEAD | `api/user/credit-history` | PaymentController@getCreditHistory | `auth:sanctum` | — |
| `API-141` | GET|HEAD | `api/user/credits` | UserController@getUserCredits | `auth:sanctum` | — |
| `API-142` | GET|HEAD | `api/user/tests` | TestController@userIndex | `auth:sanctum` | — |
| `API-143` | GET|HEAD | `api/user/tests/all` | TestController@getActiveTestsWithAssignmentFlag | `auth:sanctum` | — |
| `API-144` | POST | `api/user/tests/bulk-update-assignment` | TestController@bulkUpdateAssignment | `auth:sanctum` | — |
| `API-145` | GET|HEAD | `api/user/tests/{id}` | TestController@show | `auth:sanctum` | — |
| `API-146` | DELETE | `api/user/tests/{id}/assign` | TestController@unassignUserTest | `auth:sanctum` | — |
| `API-147` | POST | `api/user/tests/{id}/assign` | TestController@assignUserTest | `auth:sanctum` | — |
| `API-148` | GET|HEAD | `api/users` | UserController@index | `auth:sanctum` | — |
| `API-149` | POST | `api/users` | UserController@store | `auth:sanctum` | — |
| `API-150` | PUT | `api/users/change-password` | AuthController@changePassword | `auth:sanctum` | — |
| `API-151` | GET|HEAD | `api/users/type/{usertype}` | UserController@userWithType | `auth:sanctum` | — |
| `API-152` | GET|HEAD | `api/users/{id}` | UserController@edit | `auth:sanctum` | — |
| `API-153` | DELETE | `api/users/{user}` | UserController@destroy | `auth:sanctum` | — |
| `API-154` | PUT|PATCH | `api/users/{user}` | UserController@update | `auth:sanctum` | — |
| `API-155` | GET|HEAD | `api/validate-token` | AuthController@isTokenValid | — | — |
| `API-156` | POST | `api/verify-email-token` | AuthController@verifyEmailByToken | — | — |
| `API-157` | GET|HEAD | `api/verify-email/{id}/{hash}` | AuthController@verifyEmail | `signed` | — |
| `API-158` | POST | `api/verify-password` | AuthController@verifyPassword | `auth:sanctum` | — |

## Non-`api/` routes (`routes/web.php`)

| Method | URI | Action | Middleware |
|---|---|---|---|
| GET|HEAD | `/` | _(closure)_ | — |
| GET|HEAD | `payment/callback` | StripePaymentController@paymentCallback | — |
| GET|HEAD | `sanctum/csrf-cookie` | Laravel\Sanctum\Http\Controllers\CsrfCookieController@show | — |
| GET|HEAD | `storage/{path}` | _(closure)_ | — |
| PUT | `storage/{path}` | _(closure)_ | — |
| GET|HEAD | `up` | _(closure)_ | — |
| GET|HEAD | `{prefix?}/cities` | Nnjeim\World\Http\Controllers\City\CityController@index | `throttle:60,1` · `Localization` |
| GET|HEAD | `{prefix?}/countries` | Nnjeim\World\Http\Controllers\Country\CountryController@index | `throttle:60,1` · `Localization` |
| GET|HEAD | `{prefix?}/currencies` | Nnjeim\World\Http\Controllers\Currency\CurrencyController@index | `throttle:60,1` · `Localization` |
| GET|HEAD | `{prefix?}/geolocate` | Nnjeim\World\Http\Controllers\Geolocate\GeolocateController@index | `throttle:60,1` · `Localization` · `Geolocate` |
| GET|HEAD | `{prefix?}/languages` | Nnjeim\World\Http\Controllers\Language\LanguageController@index | `throttle:60,1` · `Localization` |
| GET|HEAD | `{prefix?}/states` | Nnjeim\World\Http\Controllers\State\StateController@index | `throttle:60,1` · `Localization` |
| GET|HEAD | `{prefix?}/timezones` | Nnjeim\World\Http\Controllers\Timezone\TimezoneController@index | `throttle:60,1` · `Localization` |

---

_Generated from source by `tools/extract.php` + `tools/extract-clients.php` + `tools/render.php` on 2026-09-04. Do not hand-edit — re-run the generator._
