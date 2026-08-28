# Method Index

**710 methods across 186 classes.**

Grouped by file; jump straight to the line. Use this instead of opening a controller to find a
method — several controllers here run 400–900 lines.


### `app/Console/Commands/UploadTestPlates.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| UploadTestPlates | [`handle()`](../../../TCV-Backend/app/Console/Commands/UploadTestPlates.php#L14) | 14 | public | — | — |

### `app/Events/TestCompleted.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| TestCompleted | [`__construct()`](../../../TCV-Backend/app/Events/TestCompleted.php#L13) | 13 | public | `PatientTest $patientTest` | — |

### `app/Events/TestSectionCompleted.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| TestSectionCompleted | [`__construct()`](../../../TCV-Backend/app/Events/TestSectionCompleted.php#L12) | 12 | public | `string $uniqueTestId`, `int $sectionId` | — |

### `app/Events/UserPasswordSet.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| UserPasswordSet | [`__construct()`](../../../TCV-Backend/app/Events/UserPasswordSet.php#L14) | 14 | public | `User $user` | — |

### `app/Exceptions/Handler.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| Handler | [`render()`](../../../TCV-Backend/app/Exceptions/Handler.php#L14) | 14 | public | `$request`, `Throwable $exception` | — |

### `app/Exports/DiscountCodeReportExport.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| DiscountCodeReportExport | [`__construct()`](../../../TCV-Backend/app/Exports/DiscountCodeReportExport.php#L49) | 49 | public | `$query`, `array $summary = []`, `array $filters = []` | — |
| DiscountCodeReportExport | [`build()`](../../../TCV-Backend/app/Exports/DiscountCodeReportExport.php#L56) | 56 | public | — | Spreadsheet |
| DiscountCodeReportExport | [`buildSummarySection()`](../../../TCV-Backend/app/Exports/DiscountCodeReportExport.php#L250) | 250 | private | `$sheet`, `int $startRow`, `string $lastCol` | void |
| DiscountCodeReportExport | [`buildFilterSummary()`](../../../TCV-Backend/app/Exports/DiscountCodeReportExport.php#L294) | 294 | private | — | string |
| DiscountCodeReportExport | [`applyStyle()`](../../../TCV-Backend/app/Exports/DiscountCodeReportExport.php#L312) | 312 | private | `$sheet`, `string $range`, `array $styles` | void |
| DiscountCodeReportExport | [`stream()`](../../../TCV-Backend/app/Exports/DiscountCodeReportExport.php#L317) | 317 | public | `string $fileName` | Symfony\Component\HttpFoundation\StreamedResponse |

### `app/Exports/UserTestsDetailExport.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| UserTestsDetailExport | [`__construct()`](../../../TCV-Backend/app/Exports/UserTestsDetailExport.php#L23) | 23 | public | `array $items` | — |
| UserTestsDetailExport | [`array()`](../../../TCV-Backend/app/Exports/UserTestsDetailExport.php#L28) | 28 | public | — | array |
| UserTestsDetailExport | [`headings()`](../../../TCV-Backend/app/Exports/UserTestsDetailExport.php#L33) | 33 | public | — | array |
| UserTestsDetailExport | [`map()`](../../../TCV-Backend/app/Exports/UserTestsDetailExport.php#L38) | 38 | public | `$row` | array |
| UserTestsDetailExport | [`formatStatus()`](../../../TCV-Backend/app/Exports/UserTestsDetailExport.php#L48) | 48 | private | `string $status`, `?array $pairedTest` | string |
| UserTestsDetailExport | [`columnWidths()`](../../../TCV-Backend/app/Exports/UserTestsDetailExport.php#L62) | 62 | public | — | array |
| UserTestsDetailExport | [`styles()`](../../../TCV-Backend/app/Exports/UserTestsDetailExport.php#L72) | 72 | public | `Worksheet $sheet` | array |

### `app/Exports/UserTestsReportExport.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| UserTestsReportExport | [`__construct()`](../../../TCV-Backend/app/Exports/UserTestsReportExport.php#L22) | 22 | public | `$query` | — |
| UserTestsReportExport | [`query()`](../../../TCV-Backend/app/Exports/UserTestsReportExport.php#L30) | 30 | public | — | — |
| UserTestsReportExport | [`headings()`](../../../TCV-Backend/app/Exports/UserTestsReportExport.php#L39) | 39 | public | — | array |
| UserTestsReportExport | [`map()`](../../../TCV-Backend/app/Exports/UserTestsReportExport.php#L54) | 54 | public | `$row` | array |
| UserTestsReportExport | [`columnWidths()`](../../../TCV-Backend/app/Exports/UserTestsReportExport.php#L70) | 70 | public | — | array |
| UserTestsReportExport | [`styles()`](../../../TCV-Backend/app/Exports/UserTestsReportExport.php#L84) | 84 | public | `Worksheet $sheet` | — |

### `app/Helpers/ApiResponse.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| ApiResponse | [`success()`](../../../TCV-Backend/app/Helpers/ApiResponse.php#L9) | 9 | public static | `int $statusCode = 200`, `string $messageKey`, `$data = null`, `array $meta = []` | JsonResponse |
| ApiResponse | [`error()`](../../../TCV-Backend/app/Helpers/ApiResponse.php#L28) | 28 | public static | `int $statusCode = 400`, `string $messageKey`, `$errors = null` | JsonResponse |

### `app/Helpers/TestHelper.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| TestHelper | [`getEyeLabel()`](../../../TCV-Backend/app/Helpers/TestHelper.php#L12) | 12 | public static | `string $eyeCode` | string |
| TestHelper | [`getEyeInstruction()`](../../../TCV-Backend/app/Helpers/TestHelper.php#L26) | 26 | public static | `string $eyeCode` | string |
| TestHelper | [`extractEyeFromSectionInstruction()`](../../../TCV-Backend/app/Helpers/TestHelper.php#L39) | 39 | public static | `?string $instruction` | ?string |

### `app/Http/Controllers/Auth/ConfirmPasswordController.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| ConfirmPasswordController | [`__construct()`](../../../TCV-Backend/app/Http/Controllers/Auth/ConfirmPasswordController.php#L35) | 35 | public | — | — |

### `app/Http/Controllers/Auth/ForgotPasswordController.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|

### `app/Http/Controllers/Auth/LoginController.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| LoginController | [`__construct()`](../../../TCV-Backend/app/Http/Controllers/Auth/LoginController.php#L35) | 35 | public | — | — |

### `app/Http/Controllers/Auth/RegisterController.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| RegisterController | [`__construct()`](../../../TCV-Backend/app/Http/Controllers/Auth/RegisterController.php#L38) | 38 | public | — | — |
| RegisterController | [`validator()`](../../../TCV-Backend/app/Http/Controllers/Auth/RegisterController.php#L49) | 49 | protected | `array $data` | — |
| RegisterController | [`create()`](../../../TCV-Backend/app/Http/Controllers/Auth/RegisterController.php#L64) | 64 | protected | `array $data` | — |

### `app/Http/Controllers/Auth/ResetPasswordController.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|

### `app/Http/Controllers/Auth/VerificationController.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| VerificationController | [`__construct()`](../../../TCV-Backend/app/Http/Controllers/Auth/VerificationController.php#L35) | 35 | public | — | — |

### `app/Http/Controllers/AuthController.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| AuthController | [`__construct()`](../../../TCV-Backend/app/Http/Controllers/AuthController.php#L35) | 35 | public | `StripeService $stripeService` | — |
| AuthController | [`login()`](../../../TCV-Backend/app/Http/Controllers/AuthController.php#L40) | 40 | public | `Request $request` | — |
| AuthController | [`createStripeCustomer()`](../../../TCV-Backend/app/Http/Controllers/AuthController.php#L173) | 173 | private | `User $user` | — |
| AuthController | [`register()`](../../../TCV-Backend/app/Http/Controllers/AuthController.php#L182) | 182 | public | `UserRequest $request` | — |
| AuthController | [`sendResetLinkEmail()`](../../../TCV-Backend/app/Http/Controllers/AuthController.php#L230) | 230 | public | `Request $request` | — |
| AuthController | [`setOrResetPassword()`](../../../TCV-Backend/app/Http/Controllers/AuthController.php#L262) | 262 | public | `Request $request` | — |
| AuthController | [`verifySetupToken()`](../../../TCV-Backend/app/Http/Controllers/AuthController.php#L343) | 343 | public | `Request $request` | — |
| AuthController | [`verifyEmail()`](../../../TCV-Backend/app/Http/Controllers/AuthController.php#L366) | 366 | public | `Request $request` | — |
| AuthController | [`logout()`](../../../TCV-Backend/app/Http/Controllers/AuthController.php#L388) | 388 | public | `Request $request` | — |
| AuthController | [`impersonateUser()`](../../../TCV-Backend/app/Http/Controllers/AuthController.php#L395) | 395 | public | `Request $request`, `$id` | — |
| AuthController | [`stopImpersonation()`](../../../TCV-Backend/app/Http/Controllers/AuthController.php#L416) | 416 | public | `Request $request`, `$id` | — |
| AuthController | [`isTokenValid()`](../../../TCV-Backend/app/Http/Controllers/AuthController.php#L434) | 434 | public static | `Request $request` | — |
| AuthController | [`verifyPassword()`](../../../TCV-Backend/app/Http/Controllers/AuthController.php#L494) | 494 | public | `Request $request` | — |
| AuthController | [`changePassword()`](../../../TCV-Backend/app/Http/Controllers/AuthController.php#L514) | 514 | public | `Request $request` | — |
| AuthController | [`sendVerificationEmailForUser()`](../../../TCV-Backend/app/Http/Controllers/AuthController.php#L548) | 548 | private | `User $user` | — |
| AuthController | [`verifyEmailByToken()`](../../../TCV-Backend/app/Http/Controllers/AuthController.php#L655) | 655 | public | `Request $request` | — |
| AuthController | [`resendVerificationByToken()`](../../../TCV-Backend/app/Http/Controllers/AuthController.php#L717) | 717 | public | `Request $request` | — |
| AuthController | [`resendEmailVerificationLink()`](../../../TCV-Backend/app/Http/Controllers/AuthController.php#L748) | 748 | public | `Request $request` | — |

### `app/Http/Controllers/ContactController.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| ContactController | [`__construct()`](../../../TCV-Backend/app/Http/Controllers/ContactController.php#L17) | 17 | public | `HubSpotService $hubSpotService` | — |
| ContactController | [`submit()`](../../../TCV-Backend/app/Http/Controllers/ContactController.php#L22) | 22 | public | `ContactFormRequest $request` | — |

### `app/Http/Controllers/Controller.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|

### `app/Http/Controllers/CreditsController.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| CreditsController | [`index()`](../../../TCV-Backend/app/Http/Controllers/CreditsController.php#L24) | 24 | public | `Request $request` | — |
| CreditsController | [`store()`](../../../TCV-Backend/app/Http/Controllers/CreditsController.php#L73) | 73 | public | `CreditsAddRequest $request` | — |
| CreditsController | [`show()`](../../../TCV-Backend/app/Http/Controllers/CreditsController.php#L88) | 88 | public | `$userId` | — |
| CreditsController | [`destroy()`](../../../TCV-Backend/app/Http/Controllers/CreditsController.php#L99) | 99 | public | `$id` | — |
| CreditsController | [`checkDiscountCodeValidity()`](../../../TCV-Backend/app/Http/Controllers/CreditsController.php#L112) | 112 | public | `Request $request` | — |
| CreditsController | [`revokeCredit()`](../../../TCV-Backend/app/Http/Controllers/CreditsController.php#L142) | 142 | public | `string $identifier` | Illuminate\Http\JsonResponse |

### `app/Http/Controllers/DiscountCodeController.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| DiscountCodeController | [`__construct()`](../../../TCV-Backend/app/Http/Controllers/DiscountCodeController.php#L20) | 20 | public | `DiscountCodeService $service` | — |
| DiscountCodeController | [`index()`](../../../TCV-Backend/app/Http/Controllers/DiscountCodeController.php#L24) | 24 | public | `Request $request` | JsonResponse |
| DiscountCodeController | [`store()`](../../../TCV-Backend/app/Http/Controllers/DiscountCodeController.php#L69) | 69 | public | `StoreDiscountCodeRequest $request` | JsonResponse |
| DiscountCodeController | [`show()`](../../../TCV-Backend/app/Http/Controllers/DiscountCodeController.php#L90) | 90 | public | `int $id` | JsonResponse |
| DiscountCodeController | [`update()`](../../../TCV-Backend/app/Http/Controllers/DiscountCodeController.php#L98) | 98 | public | `UpdateDiscountCodeRequest $request`, `int $id` | JsonResponse |
| DiscountCodeController | [`destroy()`](../../../TCV-Backend/app/Http/Controllers/DiscountCodeController.php#L119) | 119 | public | `int $id` | JsonResponse |
| DiscountCodeController | [`toggle()`](../../../TCV-Backend/app/Http/Controllers/DiscountCodeController.php#L131) | 131 | public | `int $id` | JsonResponse |
| DiscountCodeController | [`validateCode()`](../../../TCV-Backend/app/Http/Controllers/DiscountCodeController.php#L146) | 146 | public | `ValidateDiscountCodeRequest $request` | JsonResponse |
| DiscountCodeController | [`formOptions()`](../../../TCV-Backend/app/Http/Controllers/DiscountCodeController.php#L171) | 171 | public | — | JsonResponse |
| DiscountCodeController | [`stats()`](../../../TCV-Backend/app/Http/Controllers/DiscountCodeController.php#L192) | 192 | public | — | JsonResponse |
| DiscountCodeController | [`codeAvailable()`](../../../TCV-Backend/app/Http/Controllers/DiscountCodeController.php#L204) | 204 | public | `Request $request` | JsonResponse |
| DiscountCodeController | [`formatDiscount()`](../../../TCV-Backend/app/Http/Controllers/DiscountCodeController.php#L223) | 223 | private | `DiscountCode $discount` | array |

### `app/Http/Controllers/DropdownValuesController.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| DropdownValuesController | [`getCountriesWithStates()`](../../../TCV-Backend/app/Http/Controllers/DropdownValuesController.php#L20) | 20 | public | — | — |
| DropdownValuesController | [`fetchActiveRecords()`](../../../TCV-Backend/app/Http/Controllers/DropdownValuesController.php#L38) | 38 | protected | `$model`, `$fields`, `$fieldName = 'active'`, `$value = true` | — |
| DropdownValuesController | [`activeOrganizationTypes()`](../../../TCV-Backend/app/Http/Controllers/DropdownValuesController.php#L59) | 59 | public | — | — |
| DropdownValuesController | [`activeCompliances()`](../../../TCV-Backend/app/Http/Controllers/DropdownValuesController.php#L64) | 64 | public | — | — |
| DropdownValuesController | [`activeAllowedTests()`](../../../TCV-Backend/app/Http/Controllers/DropdownValuesController.php#L69) | 69 | public | — | — |
| DropdownValuesController | [`activePrivileges()`](../../../TCV-Backend/app/Http/Controllers/DropdownValuesController.php#L74) | 74 | public | — | — |
| DropdownValuesController | [`activeOrgSettingsOptions()`](../../../TCV-Backend/app/Http/Controllers/DropdownValuesController.php#L79) | 79 | public | — | — |

### `app/Http/Controllers/LmsAdminController.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| LmsAdminController | [`__construct()`](../../../TCV-Backend/app/Http/Controllers/LmsAdminController.php#L17) | 17 | public | `LmsDeliveryService $deliveryService` | — |
| LmsAdminController | [`listProviderConfigs()`](../../../TCV-Backend/app/Http/Controllers/LmsAdminController.php#L26) | 26 | public | `Request $request` | JsonResponse |
| LmsAdminController | [`upsertProviderConfig()`](../../../TCV-Backend/app/Http/Controllers/LmsAdminController.php#L57) | 57 | public | `Request $request` | JsonResponse |
| LmsAdminController | [`deadLetters()`](../../../TCV-Backend/app/Http/Controllers/LmsAdminController.php#L113) | 113 | public | `Request $request` | JsonResponse |
| LmsAdminController | [`replay()`](../../../TCV-Backend/app/Http/Controllers/LmsAdminController.php#L130) | 130 | public | `string $id` | JsonResponse |
| LmsAdminController | [`dismiss()`](../../../TCV-Backend/app/Http/Controllers/LmsAdminController.php#L147) | 147 | public | `string $id` | JsonResponse |
| LmsAdminController | [`revealSigningKey()`](../../../TCV-Backend/app/Http/Controllers/LmsAdminController.php#L164) | 164 | public | `int $id` | JsonResponse |
| LmsAdminController | [`rotateSigningKey()`](../../../TCV-Backend/app/Http/Controllers/LmsAdminController.php#L192) | 192 | public | `int $id` | JsonResponse |
| LmsAdminController | [`deliveryStatus()`](../../../TCV-Backend/app/Http/Controllers/LmsAdminController.php#L222) | 222 | public | — | JsonResponse |

### `app/Http/Controllers/OrganizationController.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| OrganizationController | [`__construct()`](../../../TCV-Backend/app/Http/Controllers/OrganizationController.php#L32) | 32 | public | `LmsLaunchService $launchService`, `App\Services\Lms\LmsProviderRegistry $providerRegistry` | — |
| OrganizationController | [`index()`](../../../TCV-Backend/app/Http/Controllers/OrganizationController.php#L38) | 38 | public | `Request $request` | — |
| OrganizationController | [`store()`](../../../TCV-Backend/app/Http/Controllers/OrganizationController.php#L161) | 161 | public | `OrganizationRequest $request` | — |
| OrganizationController | [`show()`](../../../TCV-Backend/app/Http/Controllers/OrganizationController.php#L225) | 225 | public | `$id` | — |
| OrganizationController | [`update()`](../../../TCV-Backend/app/Http/Controllers/OrganizationController.php#L238) | 238 | public | `OrganizationRequest $request`, `$id` | — |
| OrganizationController | [`uploadLogo()`](../../../TCV-Backend/app/Http/Controllers/OrganizationController.php#L335) | 335 | public | `Request $request`, `$id` | — |
| OrganizationController | [`destroy()`](../../../TCV-Backend/app/Http/Controllers/OrganizationController.php#L361) | 361 | public | `$id` | — |
| OrganizationController | [`getPatientForm()`](../../../TCV-Backend/app/Http/Controllers/OrganizationController.php#L394) | 394 | public | `Request $request` | — |
| OrganizationController | [`verifySignature()`](../../../TCV-Backend/app/Http/Controllers/OrganizationController.php#L465) | 465 | public | `Request $request` | — |
| OrganizationController | [`generateFieldRules()`](../../../TCV-Backend/app/Http/Controllers/OrganizationController.php#L594) | 594 | private | `$organizationId` | — |
| OrganizationController | [`getDefaultTests()`](../../../TCV-Backend/app/Http/Controllers/OrganizationController.php#L669) | 669 | public | `Request $request` | — |
| OrganizationController | [`getOrganizationPrivileges()`](../../../TCV-Backend/app/Http/Controllers/OrganizationController.php#L716) | 716 | public | `Request $request` | — |
| OrganizationController | [`getOrganizationRedirectUrl()`](../../../TCV-Backend/app/Http/Controllers/OrganizationController.php#L765) | 765 | public | `Request $request` | — |
| OrganizationController | [`addCreditsToOrganizations()`](../../../TCV-Backend/app/Http/Controllers/OrganizationController.php#L815) | 815 | private | `$orgCollection` | Illuminate\Support\Collection |
| OrganizationController | [`createOrganizationConfig()`](../../../TCV-Backend/app/Http/Controllers/OrganizationController.php#L861) | 861 | private | `$organizationId`, `?array $fields = null`, `?string $redirectUrl = null` | — |

### `app/Http/Controllers/OrganizationPatientController.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| OrganizationPatientController | [`__construct()`](../../../TCV-Backend/app/Http/Controllers/OrganizationPatientController.php#L17) | 17 | public | `LmsLaunchService $launchService` | — |
| OrganizationPatientController | [`verifyTurnstileToken()`](../../../TCV-Backend/app/Http/Controllers/OrganizationPatientController.php#L25) | 25 | private | `$request` | — |
| OrganizationPatientController | [`storeProlificPatient()`](../../../TCV-Backend/app/Http/Controllers/OrganizationPatientController.php#L54) | 54 | public | `Request $request` | — |
| OrganizationPatientController | [`storeDefaultPatient()`](../../../TCV-Backend/app/Http/Controllers/OrganizationPatientController.php#L180) | 180 | public | `Request $request` | — |

### `app/Http/Controllers/PasswordController.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| PasswordController | [`update()`](../../../TCV-Backend/app/Http/Controllers/PasswordController.php#L25) | 25 | public | `ChangePasswordRequest $request` | — |

### `app/Http/Controllers/PatientController.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| PatientController | [`__construct()`](../../../TCV-Backend/app/Http/Controllers/PatientController.php#L27) | 27 | public | `TestService $testService`, `PatientTestTransformer $testTransformer` | — |
| PatientController | [`index()`](../../../TCV-Backend/app/Http/Controllers/PatientController.php#L35) | 35 | public | — | — |
| PatientController | [`store()`](../../../TCV-Backend/app/Http/Controllers/PatientController.php#L67) | 67 | public | `PatientAddRequest $request` | — |
| PatientController | [`show()`](../../../TCV-Backend/app/Http/Controllers/PatientController.php#L107) | 107 | public | `$id` | — |
| PatientController | [`update()`](../../../TCV-Backend/app/Http/Controllers/PatientController.php#L118) | 118 | public | `PatientUpdateRequest $request`, `$id` | — |
| PatientController | [`destroy()`](../../../TCV-Backend/app/Http/Controllers/PatientController.php#L134) | 134 | public | `$id` | — |
| PatientController | [`resendTestLink()`](../../../TCV-Backend/app/Http/Controllers/PatientController.php#L145) | 145 | public | `Request $request` | — |
| PatientController | [`getPatientTests()`](../../../TCV-Backend/app/Http/Controllers/PatientController.php#L187) | 187 | public | `$id` | Illuminate\Http\JsonResponse |
| PatientController | [`storeOrganizationPatient()`](../../../TCV-Backend/app/Http/Controllers/PatientController.php#L242) | 242 | public | `Request $request` | — |

### `app/Http/Controllers/PaymentController.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| PaymentController | [`__construct()`](../../../TCV-Backend/app/Http/Controllers/PaymentController.php#L14) | 14 | public | `DiscountCodeService $discountCodeService` | — |
| PaymentController | [`getProviders()`](../../../TCV-Backend/app/Http/Controllers/PaymentController.php#L18) | 18 | public | — | — |
| PaymentController | [`createSetupIntent()`](../../../TCV-Backend/app/Http/Controllers/PaymentController.php#L33) | 33 | public | `Request $request` | — |
| PaymentController | [`initializePayment()`](../../../TCV-Backend/app/Http/Controllers/PaymentController.php#L56) | 56 | public | `Request $request` | — |
| PaymentController | [`confirmPayment()`](../../../TCV-Backend/app/Http/Controllers/PaymentController.php#L90) | 90 | public | `Request $request` | — |
| PaymentController | [`handleWebhook()`](../../../TCV-Backend/app/Http/Controllers/PaymentController.php#L149) | 149 | public | `Request $request`, `string $provider` | — |
| PaymentController | [`getTransactions()`](../../../TCV-Backend/app/Http/Controllers/PaymentController.php#L170) | 170 | public | — | — |
| PaymentController | [`getCreditHistory()`](../../../TCV-Backend/app/Http/Controllers/PaymentController.php#L207) | 207 | public | — | Illuminate\Http\JsonResponse |

### `app/Http/Controllers/PriceDetailController.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| PriceDetailController | [`validateData()`](../../../TCV-Backend/app/Http/Controllers/PriceDetailController.php#L16) | 16 | private | `array $data` | — |
| PriceDetailController | [`getAllDetails()`](../../../TCV-Backend/app/Http/Controllers/PriceDetailController.php#L25) | 25 | private | — | — |
| PriceDetailController | [`index()`](../../../TCV-Backend/app/Http/Controllers/PriceDetailController.php#L38) | 38 | public | — | JsonResponse |
| PriceDetailController | [`store()`](../../../TCV-Backend/app/Http/Controllers/PriceDetailController.php#L45) | 45 | public | `Request $request` | JsonResponse |
| PriceDetailController | [`update()`](../../../TCV-Backend/app/Http/Controllers/PriceDetailController.php#L57) | 57 | public | `Request $request`, `$id` | JsonResponse |
| PriceDetailController | [`destroy()`](../../../TCV-Backend/app/Http/Controllers/PriceDetailController.php#L92) | 92 | public | `$id` | JsonResponse |

### `app/Http/Controllers/ProfileController.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| ProfileController | [`show()`](../../../TCV-Backend/app/Http/Controllers/ProfileController.php#L19) | 19 | public | — | — |
| ProfileController | [`update()`](../../../TCV-Backend/app/Http/Controllers/ProfileController.php#L52) | 52 | public | `UpdateProfileRequest $request` | — |

### `app/Http/Controllers/ReportController.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| ReportController | [`__construct()`](../../../TCV-Backend/app/Http/Controllers/ReportController.php#L26) | 26 | public | `UserTestsReportService $userReportService`, `DiscountCodeReportService $discountCodeReportService` | — |
| ReportController | [`getPatientsHavingTests()`](../../../TCV-Backend/app/Http/Controllers/ReportController.php#L32) | 32 | public | `Request $request` | — |
| ReportController | [`userTestsReport()`](../../../TCV-Backend/app/Http/Controllers/ReportController.php#L54) | 54 | public | `Request $request` | — |
| ReportController | [`downloadDetailExcel()`](../../../TCV-Backend/app/Http/Controllers/ReportController.php#L119) | 119 | private | `array $items` | — |
| ReportController | [`downloadDetailPdf()`](../../../TCV-Backend/app/Http/Controllers/ReportController.php#L125) | 125 | private | `array $items`, `?string $patientName = null` | — |
| ReportController | [`downloadExcel()`](../../../TCV-Backend/app/Http/Controllers/ReportController.php#L138) | 138 | private | `$query` | — |
| ReportController | [`downloadPdf()`](../../../TCV-Backend/app/Http/Controllers/ReportController.php#L146) | 146 | private | `$query` | — |
| ReportController | [`discountCode()`](../../../TCV-Backend/app/Http/Controllers/ReportController.php#L173) | 173 | public | `Request $request` | — |
| ReportController | [`downloadDiscountCodeExcel()`](../../../TCV-Backend/app/Http/Controllers/ReportController.php#L221) | 221 | private | `array $params` | — |

### `app/Http/Controllers/RestrictedIpController.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| RestrictedIpController | [`index()`](../../../TCV-Backend/app/Http/Controllers/RestrictedIpController.php#L18) | 18 | public | — | JsonResponse |
| RestrictedIpController | [`store()`](../../../TCV-Backend/app/Http/Controllers/RestrictedIpController.php#L30) | 30 | public | `Request $request` | JsonResponse |
| RestrictedIpController | [`update()`](../../../TCV-Backend/app/Http/Controllers/RestrictedIpController.php#L52) | 52 | public | `Request $request`, `$id` | JsonResponse |
| RestrictedIpController | [`destroy()`](../../../TCV-Backend/app/Http/Controllers/RestrictedIpController.php#L79) | 79 | public | `$id` | JsonResponse |

### `app/Http/Controllers/StripePaymentController.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| StripePaymentController | [`__construct()`](../../../TCV-Backend/app/Http/Controllers/StripePaymentController.php#L23) | 23 | public | `StripeService $stripeService` | — |
| StripePaymentController | [`getPaymentMethods()`](../../../TCV-Backend/app/Http/Controllers/StripePaymentController.php#L31) | 31 | public | — | — |
| StripePaymentController | [`createPaymentIntent()`](../../../TCV-Backend/app/Http/Controllers/StripePaymentController.php#L51) | 51 | public | `Request $request` | — |
| StripePaymentController | [`confirmPayment()`](../../../TCV-Backend/app/Http/Controllers/StripePaymentController.php#L129) | 129 | public | `Request $request` | — |
| StripePaymentController | [`confirmACHPayment()`](../../../TCV-Backend/app/Http/Controllers/StripePaymentController.php#L185) | 185 | public | `Request $request` | — |
| StripePaymentController | [`savePaymentMethod()`](../../../TCV-Backend/app/Http/Controllers/StripePaymentController.php#L242) | 242 | private | `$user`, `$paymentMethodId` | — |
| StripePaymentController | [`removePaymentMethod()`](../../../TCV-Backend/app/Http/Controllers/StripePaymentController.php#L294) | 294 | public | `Request $request`, `string $paymentMethodId` | — |
| StripePaymentController | [`partialRefund()`](../../../TCV-Backend/app/Http/Controllers/StripePaymentController.php#L314) | 314 | public | `PartialPaymentRequest $request` | — |
| StripePaymentController | [`refund()`](../../../TCV-Backend/app/Http/Controllers/StripePaymentController.php#L348) | 348 | public | `RefundPaymentRequest $request` | — |
| StripePaymentController | [`paymentCallback()`](../../../TCV-Backend/app/Http/Controllers/StripePaymentController.php#L374) | 374 | public | `Request $request` | — |
| StripePaymentController | [`getTransactions()`](../../../TCV-Backend/app/Http/Controllers/StripePaymentController.php#L390) | 390 | public | — | — |

### `app/Http/Controllers/SuperAdminDashboardController.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| SuperAdminDashboardController | [`index()`](../../../TCV-Backend/app/Http/Controllers/SuperAdminDashboardController.php#L16) | 16 | public | `Request $request` | — |
| SuperAdminDashboardController | [`buildMonthlyGrid()`](../../../TCV-Backend/app/Http/Controllers/SuperAdminDashboardController.php#L147) | 147 | private | `$rawRows`, `Carbon $from`, `int $months`, `callable $mapper` | array |

### `app/Http/Controllers/TestAnswerController.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| TestAnswerController | [`index()`](../../../TCV-Backend/app/Http/Controllers/TestAnswerController.php#L15) | 15 | public | — | — |
| TestAnswerController | [`store()`](../../../TCV-Backend/app/Http/Controllers/TestAnswerController.php#L24) | 24 | public | `TestAnswerRequest $request` | — |
| TestAnswerController | [`show()`](../../../TCV-Backend/app/Http/Controllers/TestAnswerController.php#L36) | 36 | public | `$id` | — |
| TestAnswerController | [`update()`](../../../TCV-Backend/app/Http/Controllers/TestAnswerController.php#L46) | 46 | public | `TestAnswerRequest $request`, `$id` | — |
| TestAnswerController | [`destroy()`](../../../TCV-Backend/app/Http/Controllers/TestAnswerController.php#L60) | 60 | public | `$id` | — |

### `app/Http/Controllers/TestConditionController.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| TestConditionController | [`index()`](../../../TCV-Backend/app/Http/Controllers/TestConditionController.php#L16) | 16 | public | `Request $request`, `$testID` | — |
| TestConditionController | [`store()`](../../../TCV-Backend/app/Http/Controllers/TestConditionController.php#L28) | 28 | public | `TestConditionRequest $request`, `$testID` | — |
| TestConditionController | [`show()`](../../../TCV-Backend/app/Http/Controllers/TestConditionController.php#L42) | 42 | public | `Request $request`, `$testID`, `$id` | — |
| TestConditionController | [`update()`](../../../TCV-Backend/app/Http/Controllers/TestConditionController.php#L53) | 53 | public | `TestConditionRequest $request`, `$testID`, `$id` | — |
| TestConditionController | [`destroy()`](../../../TCV-Backend/app/Http/Controllers/TestConditionController.php#L78) | 78 | public | `Request $request`, `$testID`, `$id` | — |

### `app/Http/Controllers/TestController.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| TestController | [`__construct()`](../../../TCV-Backend/app/Http/Controllers/TestController.php#L36) | 36 | public | `TestService $testService`, `TestAssignmentService $assignmentService`, `TestExecutionService $executionService`, `TestResultService $resultService`, `LmsLaunchService $launchService` | — |
| TestController | [`index()`](../../../TCV-Backend/app/Http/Controllers/TestController.php#L49) | 49 | public | `Request $request` | — |
| TestController | [`userIndex()`](../../../TCV-Backend/app/Http/Controllers/TestController.php#L58) | 58 | public | `Request $request` | — |
| TestController | [`store()`](../../../TCV-Backend/app/Http/Controllers/TestController.php#L87) | 87 | public | `TestRequest $request` | — |
| TestController | [`show()`](../../../TCV-Backend/app/Http/Controllers/TestController.php#L96) | 96 | public | `Request $request`, `$id` | — |
| TestController | [`update()`](../../../TCV-Backend/app/Http/Controllers/TestController.php#L103) | 103 | public | `TestRequest $request`, `$id` | — |
| TestController | [`destroy()`](../../../TCV-Backend/app/Http/Controllers/TestController.php#L122) | 122 | public | `Request $request`, `$id` | — |
| TestController | [`assignTest()`](../../../TCV-Backend/app/Http/Controllers/TestController.php#L139) | 139 | public | `CreateTestRequest $request` | — |
| TestController | [`performTest()`](../../../TCV-Backend/app/Http/Controllers/TestController.php#L223) | 223 | public | `PerformTestRequest $request` | — |
| TestController | [`getTestSession()`](../../../TCV-Backend/app/Http/Controllers/TestController.php#L260) | 260 | public | `$unique_test_id` | — |
| TestController | [`getSectionPlates()`](../../../TCV-Backend/app/Http/Controllers/TestController.php#L288) | 288 | public | `$unique_test_id`, `$section_id` | — |
| TestController | [`getPlateUrl()`](../../../TCV-Backend/app/Http/Controllers/TestController.php#L313) | 313 | public | `string $unique_test_id`, `int $test_answer_id` | — |
| TestController | [`getTestResult()`](../../../TCV-Backend/app/Http/Controllers/TestController.php#L339) | 339 | public | `$unique_test_id` | — |
| TestController | [`downloadTestResultPDF()`](../../../TCV-Backend/app/Http/Controllers/TestController.php#L382) | 382 | public | `$unique_test_id` | — |
| TestController | [`getActiveTest()`](../../../TCV-Backend/app/Http/Controllers/TestController.php#L493) | 493 | public | `Request $request` | — |
| TestController | [`getActiveTestsWithAssignmentFlag()`](../../../TCV-Backend/app/Http/Controllers/TestController.php#L560) | 560 | public | `Request $request` | — |
| TestController | [`assignUserTest()`](../../../TCV-Backend/app/Http/Controllers/TestController.php#L585) | 585 | public | `$id` | — |
| TestController | [`unassignUserTest()`](../../../TCV-Backend/app/Http/Controllers/TestController.php#L593) | 593 | public | `$id` | — |
| TestController | [`bulkUpdateAssignment()`](../../../TCV-Backend/app/Http/Controllers/TestController.php#L606) | 606 | public | `Request $request` | — |

### `app/Http/Controllers/TestEmailTemplateController.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| TestEmailTemplateController | [`index()`](../../../TCV-Backend/app/Http/Controllers/TestEmailTemplateController.php#L22) | 22 | public | — | JsonResponse |
| TestEmailTemplateController | [`update()`](../../../TCV-Backend/app/Http/Controllers/TestEmailTemplateController.php#L76) | 76 | public | `Request $request`, `int $id` | JsonResponse |
| TestEmailTemplateController | [`validateTemplateData()`](../../../TCV-Backend/app/Http/Controllers/TestEmailTemplateController.php#L172) | 172 | private | `array $data`, `string $type` | Illuminate\Contracts\Validation\Validator |
| TestEmailTemplateController | [`validateRequiredPlaceholders()`](../../../TCV-Backend/app/Http/Controllers/TestEmailTemplateController.php#L195) | 195 | private | `string $body`, `string $type` | array |
| TestEmailTemplateController | [`getPlaceholders()`](../../../TCV-Backend/app/Http/Controllers/TestEmailTemplateController.php#L235) | 235 | public | `string $type` | JsonResponse |

### `app/Http/Controllers/TestInvitationController.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| TestInvitationController | [`__construct()`](../../../TCV-Backend/app/Http/Controllers/TestInvitationController.php#L22) | 22 | public | `EmailTemplateService $emailTemplateService` | — |
| TestInvitationController | [`sendInvitations()`](../../../TCV-Backend/app/Http/Controllers/TestInvitationController.php#L27) | 27 | public | `Request $request` | — |
| TestInvitationController | [`getUnregisteredInvitations()`](../../../TCV-Backend/app/Http/Controllers/TestInvitationController.php#L205) | 205 | public | `Request $request` | — |
| TestInvitationController | [`resendUnregisteredInvitation()`](../../../TCV-Backend/app/Http/Controllers/TestInvitationController.php#L261) | 261 | public | `int $invitationId` | — |
| TestInvitationController | [`cancelUnregisteredInvitation()`](../../../TCV-Backend/app/Http/Controllers/TestInvitationController.php#L295) | 295 | public | `int $invitationId` | — |
| TestInvitationController | [`sendInvitationEmail()`](../../../TCV-Backend/app/Http/Controllers/TestInvitationController.php#L333) | 333 | private | `$email`, `$test`, `$token`, `$verificationCode`, `int $userId` | void |
| TestInvitationController | [`verifyCode()`](../../../TCV-Backend/app/Http/Controllers/TestInvitationController.php#L381) | 381 | public | `Request $request` | — |
| TestInvitationController | [`checkTokenStatus()`](../../../TCV-Backend/app/Http/Controllers/TestInvitationController.php#L484) | 484 | public | `Request $request` | — |

### `app/Http/Controllers/TestResumeController.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| TestResumeController | [`sendResumeEmail()`](../../../TCV-Backend/app/Http/Controllers/TestResumeController.php#L22) | 22 | public | `Request $request` | — |
| TestResumeController | [`resume()`](../../../TCV-Backend/app/Http/Controllers/TestResumeController.php#L81) | 81 | public | `Request $request` | — |
| TestResumeController | [`dispatchResumeEmail()`](../../../TCV-Backend/app/Http/Controllers/TestResumeController.php#L156) | 156 | private | `string $email`, `PatientTest $patientTest`, `string $token` | void |

### `app/Http/Controllers/TestSectionController.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| TestSectionController | [`index()`](../../../TCV-Backend/app/Http/Controllers/TestSectionController.php#L16) | 16 | public | `Request $request`, `$testID` | — |
| TestSectionController | [`store()`](../../../TCV-Backend/app/Http/Controllers/TestSectionController.php#L28) | 28 | public | `TestSectionRequest $request`, `$testID` | — |
| TestSectionController | [`show()`](../../../TCV-Backend/app/Http/Controllers/TestSectionController.php#L43) | 43 | public | `Request $request`, `$testID`, `$id` | — |
| TestSectionController | [`update()`](../../../TCV-Backend/app/Http/Controllers/TestSectionController.php#L58) | 58 | public | `TestSectionRequest $request`, `$testID`, `$id` | — |
| TestSectionController | [`destroy()`](../../../TCV-Backend/app/Http/Controllers/TestSectionController.php#L75) | 75 | public | `Request $request`, `$testID`, `$id` | — |

### `app/Http/Controllers/TestSectionPlateController.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| TestSectionPlateController | [`index()`](../../../TCV-Backend/app/Http/Controllers/TestSectionPlateController.php#L16) | 16 | public | `Request $request`, `$testID` | — |
| TestSectionPlateController | [`store()`](../../../TCV-Backend/app/Http/Controllers/TestSectionPlateController.php#L28) | 28 | public | `TestSectionPlateRequest $request`, `$testID` | — |
| TestSectionPlateController | [`show()`](../../../TCV-Backend/app/Http/Controllers/TestSectionPlateController.php#L42) | 42 | public | `$testID`, `$id` | — |
| TestSectionPlateController | [`update()`](../../../TCV-Backend/app/Http/Controllers/TestSectionPlateController.php#L52) | 52 | public | `TestSectionPlateRequest $request`, `$testID`, `$id` | — |
| TestSectionPlateController | [`destroy()`](../../../TCV-Backend/app/Http/Controllers/TestSectionPlateController.php#L68) | 68 | public | `$testID`, `$id` | — |

### `app/Http/Controllers/UserController.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| UserController | [`index()`](../../../TCV-Backend/app/Http/Controllers/UserController.php#L31) | 31 | public | `Request $request` | — |
| UserController | [`store()`](../../../TCV-Backend/app/Http/Controllers/UserController.php#L50) | 50 | public | `UserRequest $request` | — |
| UserController | [`edit()`](../../../TCV-Backend/app/Http/Controllers/UserController.php#L129) | 129 | public | `string $id` | — |
| UserController | [`update()`](../../../TCV-Backend/app/Http/Controllers/UserController.php#L137) | 137 | public | `UserRequest $request`, `$id` | — |
| UserController | [`show()`](../../../TCV-Backend/app/Http/Controllers/UserController.php#L174) | 174 | public | — | — |
| UserController | [`destroy()`](../../../TCV-Backend/app/Http/Controllers/UserController.php#L189) | 189 | public | `string $id` | — |
| UserController | [`userWithType()`](../../../TCV-Backend/app/Http/Controllers/UserController.php#L212) | 212 | public | `Request $request`, `$usertype` | — |
| UserController | [`getUserCredits()`](../../../TCV-Backend/app/Http/Controllers/UserController.php#L325) | 325 | public | — | — |
| UserController | [`addCreditsToUsers()`](../../../TCV-Backend/app/Http/Controllers/UserController.php#L342) | 342 | private | `$userCollection` | — |

### `app/Http/Controllers/UserEmailTemplateController.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| UserEmailTemplateController | [`__construct()`](../../../TCV-Backend/app/Http/Controllers/UserEmailTemplateController.php#L14) | 14 | public | `EmailTemplateService $emailTemplateService` | — |
| UserEmailTemplateController | [`show()`](../../../TCV-Backend/app/Http/Controllers/UserEmailTemplateController.php#L24) | 24 | public | — | JsonResponse |
| UserEmailTemplateController | [`update()`](../../../TCV-Backend/app/Http/Controllers/UserEmailTemplateController.php#L50) | 50 | public | `UpdateUserEmailTemplateRequest $request` | JsonResponse |
| UserEmailTemplateController | [`destroy()`](../../../TCV-Backend/app/Http/Controllers/UserEmailTemplateController.php#L80) | 80 | public | — | JsonResponse |

### `app/Http/Middleware/EnsureTokenIsValid.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| EnsureTokenIsValid | [`handle()`](../../../TCV-Backend/app/Http/Middleware/EnsureTokenIsValid.php#L16) | 16 | public | `Request $request`, `Closure $next` | Response |

### `app/Http/Middleware/FlexibleAuthMiddleware.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| FlexibleAuthMiddleware | [`handle()`](../../../TCV-Backend/app/Http/Middleware/FlexibleAuthMiddleware.php#L16) | 16 | public | `Request $request`, `Closure $next` | Response |
| FlexibleAuthMiddleware | [`sessionExpired()`](../../../TCV-Backend/app/Http/Middleware/FlexibleAuthMiddleware.php#L94) | 94 | private | — | Response |
| FlexibleAuthMiddleware | [`unauthenticated()`](../../../TCV-Backend/app/Http/Middleware/FlexibleAuthMiddleware.php#L103) | 103 | private | — | Response |

### `app/Http/Middleware/LmsSessionStatusMiddleware.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| LmsSessionStatusMiddleware | [`handle()`](../../../TCV-Backend/app/Http/Middleware/LmsSessionStatusMiddleware.php#L11) | 11 | public | `Request $request`, `Closure $next`, `string $allowedStatuses` | Response |

### `app/Http/Middleware/RestrictIpMiddleware.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| RestrictIpMiddleware | [`handle()`](../../../TCV-Backend/app/Http/Middleware/RestrictIpMiddleware.php#L13) | 13 | public | `Request $request`, `Closure $next` | — |

### `app/Http/Requests/ChangePasswordRequest.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| ChangePasswordRequest | [`authorize()`](../../../TCV-Backend/app/Http/Requests/ChangePasswordRequest.php#L13) | 13 | public | — | bool |
| ChangePasswordRequest | [`rules()`](../../../TCV-Backend/app/Http/Requests/ChangePasswordRequest.php#L23) | 23 | public | — | array |
| ChangePasswordRequest | [`messages()`](../../../TCV-Backend/app/Http/Requests/ChangePasswordRequest.php#L47) | 47 | public | — | array |
| ChangePasswordRequest | [`attributes()`](../../../TCV-Backend/app/Http/Requests/ChangePasswordRequest.php#L64) | 64 | public | — | array |

### `app/Http/Requests/ContactFormRequest.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| ContactFormRequest | [`authorize()`](../../../TCV-Backend/app/Http/Requests/ContactFormRequest.php#L9) | 9 | public | — | bool |
| ContactFormRequest | [`rules()`](../../../TCV-Backend/app/Http/Requests/ContactFormRequest.php#L14) | 14 | public | — | array |
| ContactFormRequest | [`messages()`](../../../TCV-Backend/app/Http/Requests/ContactFormRequest.php#L30) | 30 | public | — | array |

### `app/Http/Requests/CreatePaymentRequest.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| CreatePaymentRequest | [`authorize()`](../../../TCV-Backend/app/Http/Requests/CreatePaymentRequest.php#L9) | 9 | public | — | bool |
| CreatePaymentRequest | [`rules()`](../../../TCV-Backend/app/Http/Requests/CreatePaymentRequest.php#L14) | 14 | public | — | array |

### `app/Http/Requests/CreateTestRequest.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| CreateTestRequest | [`authorize()`](../../../TCV-Backend/app/Http/Requests/CreateTestRequest.php#L15) | 15 | public | — | bool |
| CreateTestRequest | [`getEyeTestedValues()`](../../../TCV-Backend/app/Http/Requests/CreateTestRequest.php#L20) | 20 | protected | — | array |
| CreateTestRequest | [`rules()`](../../../TCV-Backend/app/Http/Requests/CreateTestRequest.php#L30) | 30 | public | — | array |
| CreateTestRequest | [`messages()`](../../../TCV-Backend/app/Http/Requests/CreateTestRequest.php#L44) | 44 | public | — | array |

### `app/Http/Requests/CreditsAddRequest.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| CreditsAddRequest | [`authorize()`](../../../TCV-Backend/app/Http/Requests/CreditsAddRequest.php#L10) | 10 | public | — | bool |
| CreditsAddRequest | [`rules()`](../../../TCV-Backend/app/Http/Requests/CreditsAddRequest.php#L15) | 15 | public | — | array |
| CreditsAddRequest | [`messages()`](../../../TCV-Backend/app/Http/Requests/CreditsAddRequest.php#L29) | 29 | public | — | array |

### `app/Http/Requests/GenerateTestReportRequest.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| GenerateTestReportRequest | [`rules()`](../../../TCV-Backend/app/Http/Requests/GenerateTestReportRequest.php#L9) | 9 | public | — | — |

### `app/Http/Requests/OrganizationRequest.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| OrganizationRequest | [`authorize()`](../../../TCV-Backend/app/Http/Requests/OrganizationRequest.php#L12) | 12 | public | — | bool |
| OrganizationRequest | [`rules()`](../../../TCV-Backend/app/Http/Requests/OrganizationRequest.php#L17) | 17 | public | — | array |

### `app/Http/Requests/PartialPaymentRequest.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| PartialPaymentRequest | [`authorize()`](../../../TCV-Backend/app/Http/Requests/PartialPaymentRequest.php#L9) | 9 | public | — | bool |
| PartialPaymentRequest | [`rules()`](../../../TCV-Backend/app/Http/Requests/PartialPaymentRequest.php#L14) | 14 | public | — | array |

### `app/Http/Requests/PatientAddRequest.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| PatientAddRequest | [`authorize()`](../../../TCV-Backend/app/Http/Requests/PatientAddRequest.php#L13) | 13 | public | — | bool |
| PatientAddRequest | [`rules()`](../../../TCV-Backend/app/Http/Requests/PatientAddRequest.php#L18) | 18 | public | — | array |
| PatientAddRequest | [`failedValidation()`](../../../TCV-Backend/app/Http/Requests/PatientAddRequest.php#L34) | 34 | public | `Illuminate\Contracts\Validation\Validator $validator` | — |

### `app/Http/Requests/PatientUpdateRequest.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| PatientUpdateRequest | [`authorize()`](../../../TCV-Backend/app/Http/Requests/PatientUpdateRequest.php#L13) | 13 | public | — | bool |
| PatientUpdateRequest | [`rules()`](../../../TCV-Backend/app/Http/Requests/PatientUpdateRequest.php#L18) | 18 | public | — | array |

### `app/Http/Requests/PerformTestRequest.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| PerformTestRequest | [`authorize()`](../../../TCV-Backend/app/Http/Requests/PerformTestRequest.php#L9) | 9 | public | — | bool |
| PerformTestRequest | [`rules()`](../../../TCV-Backend/app/Http/Requests/PerformTestRequest.php#L14) | 14 | public | — | array |
| PerformTestRequest | [`messages()`](../../../TCV-Backend/app/Http/Requests/PerformTestRequest.php#L24) | 24 | public | — | array |
| PerformTestRequest | [`isAutoSubmit()`](../../../TCV-Backend/app/Http/Requests/PerformTestRequest.php#L34) | 34 | public | — | bool |

### `app/Http/Requests/RefundPaymentRequest.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| RefundPaymentRequest | [`authorize()`](../../../TCV-Backend/app/Http/Requests/RefundPaymentRequest.php#L9) | 9 | public | — | bool |
| RefundPaymentRequest | [`rules()`](../../../TCV-Backend/app/Http/Requests/RefundPaymentRequest.php#L14) | 14 | public | — | array |

### `app/Http/Requests/StoreDiscountCodeRequest.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| StoreDiscountCodeRequest | [`authorize()`](../../../TCV-Backend/app/Http/Requests/StoreDiscountCodeRequest.php#L10) | 10 | public | — | bool |
| StoreDiscountCodeRequest | [`rules()`](../../../TCV-Backend/app/Http/Requests/StoreDiscountCodeRequest.php#L15) | 15 | public | — | array |
| StoreDiscountCodeRequest | [`messages()`](../../../TCV-Backend/app/Http/Requests/StoreDiscountCodeRequest.php#L35) | 35 | public | — | array |
| StoreDiscountCodeRequest | [`prepareForValidation()`](../../../TCV-Backend/app/Http/Requests/StoreDiscountCodeRequest.php#L44) | 44 | protected | — | void |

### `app/Http/Requests/TestAnswerRequest.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| TestAnswerRequest | [`authorize()`](../../../TCV-Backend/app/Http/Requests/TestAnswerRequest.php#L9) | 9 | public | — | bool |
| TestAnswerRequest | [`rules()`](../../../TCV-Backend/app/Http/Requests/TestAnswerRequest.php#L14) | 14 | public | — | array |

### `app/Http/Requests/TestConditionRequest.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| TestConditionRequest | [`authorize()`](../../../TCV-Backend/app/Http/Requests/TestConditionRequest.php#L9) | 9 | public | — | bool |
| TestConditionRequest | [`rules()`](../../../TCV-Backend/app/Http/Requests/TestConditionRequest.php#L14) | 14 | public | — | array |

### `app/Http/Requests/TestRequest.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| TestRequest | [`authorize()`](../../../TCV-Backend/app/Http/Requests/TestRequest.php#L9) | 9 | public | — | bool |
| TestRequest | [`rules()`](../../../TCV-Backend/app/Http/Requests/TestRequest.php#L14) | 14 | public | — | array |

### `app/Http/Requests/TestSectionPlateRequest.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| TestSectionPlateRequest | [`authorize()`](../../../TCV-Backend/app/Http/Requests/TestSectionPlateRequest.php#L9) | 9 | public | — | bool |
| TestSectionPlateRequest | [`rules()`](../../../TCV-Backend/app/Http/Requests/TestSectionPlateRequest.php#L14) | 14 | public | — | array |

### `app/Http/Requests/TestSectionRequest.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| TestSectionRequest | [`authorize()`](../../../TCV-Backend/app/Http/Requests/TestSectionRequest.php#L9) | 9 | public | — | bool |
| TestSectionRequest | [`rules()`](../../../TCV-Backend/app/Http/Requests/TestSectionRequest.php#L14) | 14 | public | — | array |

### `app/Http/Requests/UpdateDiscountCodeRequest.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| UpdateDiscountCodeRequest | [`authorize()`](../../../TCV-Backend/app/Http/Requests/UpdateDiscountCodeRequest.php#L10) | 10 | public | — | bool |
| UpdateDiscountCodeRequest | [`rules()`](../../../TCV-Backend/app/Http/Requests/UpdateDiscountCodeRequest.php#L15) | 15 | public | — | array |
| UpdateDiscountCodeRequest | [`messages()`](../../../TCV-Backend/app/Http/Requests/UpdateDiscountCodeRequest.php#L37) | 37 | public | — | array |
| UpdateDiscountCodeRequest | [`prepareForValidation()`](../../../TCV-Backend/app/Http/Requests/UpdateDiscountCodeRequest.php#L45) | 45 | protected | — | void |

### `app/Http/Requests/UpdateProfileRequest.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| UpdateProfileRequest | [`authorize()`](../../../TCV-Backend/app/Http/Requests/UpdateProfileRequest.php#L14) | 14 | public | — | bool |
| UpdateProfileRequest | [`rules()`](../../../TCV-Backend/app/Http/Requests/UpdateProfileRequest.php#L25) | 25 | public | — | array |
| UpdateProfileRequest | [`messages()`](../../../TCV-Backend/app/Http/Requests/UpdateProfileRequest.php#L55) | 55 | public | — | array |
| UpdateProfileRequest | [`prepareForValidation()`](../../../TCV-Backend/app/Http/Requests/UpdateProfileRequest.php#L74) | 74 | protected | — | void |

### `app/Http/Requests/UpdateSettingsRequest.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| UpdateSettingsRequest | [`authorize()`](../../../TCV-Backend/app/Http/Requests/UpdateSettingsRequest.php#L9) | 9 | public | — | — |
| UpdateSettingsRequest | [`rules()`](../../../TCV-Backend/app/Http/Requests/UpdateSettingsRequest.php#L14) | 14 | public | — | — |

### `app/Http/Requests/UpdateUserEmailTemplateRequest.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| UpdateUserEmailTemplateRequest | [`authorize()`](../../../TCV-Backend/app/Http/Requests/UpdateUserEmailTemplateRequest.php#L14) | 14 | public | — | bool |
| UpdateUserEmailTemplateRequest | [`rules()`](../../../TCV-Backend/app/Http/Requests/UpdateUserEmailTemplateRequest.php#L22) | 22 | public | — | array |
| UpdateUserEmailTemplateRequest | [`messages()`](../../../TCV-Backend/app/Http/Requests/UpdateUserEmailTemplateRequest.php#L46) | 46 | public | — | array |
| UpdateUserEmailTemplateRequest | [`withValidator()`](../../../TCV-Backend/app/Http/Requests/UpdateUserEmailTemplateRequest.php#L59) | 59 | public | `$validator` | void |
| UpdateUserEmailTemplateRequest | [`prepareForValidation()`](../../../TCV-Backend/app/Http/Requests/UpdateUserEmailTemplateRequest.php#L76) | 76 | protected | — | void |

### `app/Http/Requests/UserRequest.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| UserRequest | [`authorize()`](../../../TCV-Backend/app/Http/Requests/UserRequest.php#L15) | 15 | public | — | bool |
| UserRequest | [`rules()`](../../../TCV-Backend/app/Http/Requests/UserRequest.php#L25) | 25 | public | `$id = null`, `array $data = []` | array |

### `app/Http/Requests/ValidateDiscountCodeRequest.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| ValidateDiscountCodeRequest | [`authorize()`](../../../TCV-Backend/app/Http/Requests/ValidateDiscountCodeRequest.php#L9) | 9 | public | — | bool |
| ValidateDiscountCodeRequest | [`rules()`](../../../TCV-Backend/app/Http/Requests/ValidateDiscountCodeRequest.php#L14) | 14 | public | — | array |

### `app/Jobs/ProcessLmsDeliveryJob.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| ProcessLmsDeliveryJob | [`__construct()`](../../../TCV-Backend/app/Jobs/ProcessLmsDeliveryJob.php#L28) | 28 | public | `string $queueEntryId` | — |
| ProcessLmsDeliveryJob | [`handle()`](../../../TCV-Backend/app/Jobs/ProcessLmsDeliveryJob.php#L33) | 33 | public | `LmsProviderRegistry $registry` | void |
| ProcessLmsDeliveryJob | [`markDeadLetter()`](../../../TCV-Backend/app/Jobs/ProcessLmsDeliveryJob.php#L177) | 177 | private | `LmsDeliveryQueue $entry`, `string $code`, `string $message`, `?array $errorLog = null` | void |

### `app/Listeners/HandleLmsNotificationOnCompletion.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| HandleLmsNotificationOnCompletion | [`__construct()`](../../../TCV-Backend/app/Listeners/HandleLmsNotificationOnCompletion.php#L14) | 14 | public | `LmsDeliveryService $deliveryService` | — |
| HandleLmsNotificationOnCompletion | [`handle()`](../../../TCV-Backend/app/Listeners/HandleLmsNotificationOnCompletion.php#L19) | 19 | public | `TestCompleted $event` | void |

### `app/Listeners/HandleLmsSectionProgressOnCompletion.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| HandleLmsSectionProgressOnCompletion | [`__construct()`](../../../TCV-Backend/app/Listeners/HandleLmsSectionProgressOnCompletion.php#L12) | 12 | public | `LmsDeliveryService $deliveryService` | — |
| HandleLmsSectionProgressOnCompletion | [`handle()`](../../../TCV-Backend/app/Listeners/HandleLmsSectionProgressOnCompletion.php#L17) | 17 | public | `TestSectionCompleted $event` | void |

### `app/Listeners/SendAfterPasswordReset.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| SendAfterPasswordReset | [`handle()`](../../../TCV-Backend/app/Listeners/SendAfterPasswordReset.php#L16) | 16 | public | `UserPasswordSet $event` | void |

### `app/Mail/VerifyEmail.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| VerifyEmail | [`__construct()`](../../../TCV-Backend/app/Mail/VerifyEmail.php#L16) | 16 | public | `User $user` | — |
| VerifyEmail | [`build()`](../../../TCV-Backend/app/Mail/VerifyEmail.php#L21) | 21 | public | — | — |

### `app/Models/AllowedTest.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|

### `app/Models/Compliance.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|

### `app/Models/Country.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| Country | [`states()`](../../../TCV-Backend/app/Models/Country.php#L20) | 20 | public | — | — |

### `app/Models/Credit.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| Credit | [`user()`](../../../TCV-Backend/app/Models/Credit.php#L25) | 25 | public | — | — |

### `app/Models/CreditConsume.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| CreditConsume | [`user()`](../../../TCV-Backend/app/Models/CreditConsume.php#L26) | 26 | public | — | — |
| CreditConsume | [`getTotalConsumed()`](../../../TCV-Backend/app/Models/CreditConsume.php#L34) | 34 | public static | `int $userId` | int |
| CreditConsume | [`record()`](../../../TCV-Backend/app/Models/CreditConsume.php#L42) | 42 | public static | `array $data` | self |
| CreditConsume | [`consume()`](../../../TCV-Backend/app/Models/CreditConsume.php#L51) | 51 | public static | `User $user`, `int $amount`, `string $eventType`, `array $refIds` | self |

### `app/Models/Credits.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| Credits | [`user()`](../../../TCV-Backend/app/Models/Credits.php#L23) | 23 | public | — | — |
| Credits | [`getTotalUserCredit()`](../../../TCV-Backend/app/Models/Credits.php#L31) | 31 | public static | `$userId` | — |
| Credits | [`addCreditsToUser()`](../../../TCV-Backend/app/Models/Credits.php#L59) | 59 | public static | `User $user`, `$credits = []` | self |
| Credits | [`transactions()`](../../../TCV-Backend/app/Models/Credits.php#L75) | 75 | public | — | — |
| Credits | [`getAvailableCredits()`](../../../TCV-Backend/app/Models/Credits.php#L84) | 84 | public static | `int $userId` | int|string |

### `app/Models/DiscountCode.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| DiscountCode | [`users()`](../../../TCV-Backend/app/Models/DiscountCode.php#L41) | 41 | public | — | — |
| DiscountCode | [`priceTiers()`](../../../TCV-Backend/app/Models/DiscountCode.php#L47) | 47 | public | — | — |
| DiscountCode | [`creator()`](../../../TCV-Backend/app/Models/DiscountCode.php#L53) | 53 | public | — | — |
| DiscountCode | [`scopeActive()`](../../../TCV-Backend/app/Models/DiscountCode.php#L60) | 60 | public | `Builder $query` | Builder |
| DiscountCode | [`scopeValid()`](../../../TCV-Backend/app/Models/DiscountCode.php#L65) | 65 | public | `Builder $query` | Builder |
| DiscountCode | [`scopeExpired()`](../../../TCV-Backend/app/Models/DiscountCode.php#L73) | 73 | public | `Builder $query` | Builder |
| DiscountCode | [`scopeSearch()`](../../../TCV-Backend/app/Models/DiscountCode.php#L78) | 78 | public | `Builder $query`, `string $term` | Builder |
| DiscountCode | [`getIsExpiredAttribute()`](../../../TCV-Backend/app/Models/DiscountCode.php#L88) | 88 | public | — | bool |
| DiscountCode | [`getStatusLabelAttribute()`](../../../TCV-Backend/app/Models/DiscountCode.php#L93) | 93 | public | — | string |
| DiscountCode | [`getTotalUsesAttribute()`](../../../TCV-Backend/app/Models/DiscountCode.php#L101) | 101 | public | — | int |
| DiscountCode | [`setCodeAttribute()`](../../../TCV-Backend/app/Models/DiscountCode.php#L112) | 112 | public | `string $value` | void |
| DiscountCode | [`setExpiresAtAttribute()`](../../../TCV-Backend/app/Models/DiscountCode.php#L117) | 117 | public | `$value` | void |

### `app/Models/DiscountCodePriceTier.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| DiscountCodePriceTier | [`discountCode()`](../../../TCV-Backend/app/Models/DiscountCodePriceTier.php#L13) | 13 | public | — | — |
| DiscountCodePriceTier | [`priceTier()`](../../../TCV-Backend/app/Models/DiscountCodePriceTier.php#L18) | 18 | public | — | — |

### `app/Models/DiscountCodeUser.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| DiscountCodeUser | [`discountCode()`](../../../TCV-Backend/app/Models/DiscountCodeUser.php#L13) | 13 | public | — | — |
| DiscountCodeUser | [`user()`](../../../TCV-Backend/app/Models/DiscountCodeUser.php#L18) | 18 | public | — | — |

### `app/Models/EmailTemplate.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| EmailTemplate | [`scopeEnabled()`](../../../TCV-Backend/app/Models/EmailTemplate.php#L25) | 25 | public | `$query` | — |
| EmailTemplate | [`scopeDisabled()`](../../../TCV-Backend/app/Models/EmailTemplate.php#L33) | 33 | public | `$query` | — |
| EmailTemplate | [`isEnabled()`](../../../TCV-Backend/app/Models/EmailTemplate.php#L41) | 41 | public | — | — |
| EmailTemplate | [`enable()`](../../../TCV-Backend/app/Models/EmailTemplate.php#L49) | 49 | public | — | — |
| EmailTemplate | [`disable()`](../../../TCV-Backend/app/Models/EmailTemplate.php#L58) | 58 | public | — | — |

### `app/Models/LmsDeliveryQueue.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| LmsDeliveryQueue | [`boot()`](../../../TCV-Backend/app/Models/LmsDeliveryQueue.php#L50) | 50 | protected static | — | void |
| LmsDeliveryQueue | [`lmsSession()`](../../../TCV-Backend/app/Models/LmsDeliveryQueue.php#L60) | 60 | public | — | — |
| LmsDeliveryQueue | [`appendError()`](../../../TCV-Backend/app/Models/LmsDeliveryQueue.php#L65) | 65 | public | `int $attempt`, `string $errorMessage` | array |

### `app/Models/LmsDeliveryToken.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| LmsDeliveryToken | [`providerConfig()`](../../../TCV-Backend/app/Models/LmsDeliveryToken.php#L25) | 25 | public | — | — |
| LmsDeliveryToken | [`isExpiredOrAboutToExpire()`](../../../TCV-Backend/app/Models/LmsDeliveryToken.php#L30) | 30 | public | `int $bufferMinutes = 5` | bool |

### `app/Models/LmsProviderConfig.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| LmsProviderConfig | [`organization()`](../../../TCV-Backend/app/Models/LmsProviderConfig.php#L32) | 32 | public | — | — |
| LmsProviderConfig | [`sessions()`](../../../TCV-Backend/app/Models/LmsProviderConfig.php#L37) | 37 | public | — | — |
| LmsProviderConfig | [`deliveryToken()`](../../../TCV-Backend/app/Models/LmsProviderConfig.php#L42) | 42 | public | — | — |
| LmsProviderConfig | [`getDecodedConfig()`](../../../TCV-Backend/app/Models/LmsProviderConfig.php#L50) | 50 | public | — | array |
| LmsProviderConfig | [`getSessionTtlMinutes()`](../../../TCV-Backend/app/Models/LmsProviderConfig.php#L63) | 63 | public | — | int |
| LmsProviderConfig | [`hasDeliveryEndpoint()`](../../../TCV-Backend/app/Models/LmsProviderConfig.php#L72) | 72 | public | — | bool |

### `app/Models/LmsSession.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| LmsSession | [`boot()`](../../../TCV-Backend/app/Models/LmsSession.php#L54) | 54 | protected static | — | void |
| LmsSession | [`organization()`](../../../TCV-Backend/app/Models/LmsSession.php#L64) | 64 | public | — | — |
| LmsSession | [`providerConfig()`](../../../TCV-Backend/app/Models/LmsSession.php#L69) | 69 | public | — | — |
| LmsSession | [`patient()`](../../../TCV-Backend/app/Models/LmsSession.php#L74) | 74 | public | — | — |
| LmsSession | [`patientTest()`](../../../TCV-Backend/app/Models/LmsSession.php#L79) | 79 | public | — | — |
| LmsSession | [`deliveryQueue()`](../../../TCV-Backend/app/Models/LmsSession.php#L84) | 84 | public | — | — |
| LmsSession | [`isExpired()`](../../../TCV-Backend/app/Models/LmsSession.php#L89) | 89 | public | — | bool |
| LmsSession | [`isTerminal()`](../../../TCV-Backend/app/Models/LmsSession.php#L94) | 94 | public | — | bool |
| LmsSession | [`scopeActive()`](../../../TCV-Backend/app/Models/LmsSession.php#L99) | 99 | public | `$query` | — |

### `app/Models/Organization.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| Organization | [`organizationType()`](../../../TCV-Backend/app/Models/Organization.php#L59) | 59 | public | — | — |
| Organization | [`user()`](../../../TCV-Backend/app/Models/Organization.php#L65) | 65 | public | — | — |
| Organization | [`compliance()`](../../../TCV-Backend/app/Models/Organization.php#L71) | 71 | public | — | — |
| Organization | [`privileges()`](../../../TCV-Backend/app/Models/Organization.php#L77) | 77 | public | — | — |
| Organization | [`allowedTests()`](../../../TCV-Backend/app/Models/Organization.php#L83) | 83 | public | — | — |
| Organization | [`config()`](../../../TCV-Backend/app/Models/Organization.php#L91) | 91 | public | — | — |
| Organization | [`generateTestUrl()`](../../../TCV-Backend/app/Models/Organization.php#L96) | 96 | public | — | string |

### `app/Models/OrganizationConfig.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| OrganizationConfig | [`organization()`](../../../TCV-Backend/app/Models/OrganizationConfig.php#L37) | 37 | public | — | — |

### `app/Models/OrganizationPatientSession.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| OrganizationPatientSession | [`organization()`](../../../TCV-Backend/app/Models/OrganizationPatientSession.php#L32) | 32 | public | — | — |
| OrganizationPatientSession | [`patient()`](../../../TCV-Backend/app/Models/OrganizationPatientSession.php#L40) | 40 | public | — | — |
| OrganizationPatientSession | [`test()`](../../../TCV-Backend/app/Models/OrganizationPatientSession.php#L48) | 48 | public | — | — |
| OrganizationPatientSession | [`isExpired()`](../../../TCV-Backend/app/Models/OrganizationPatientSession.php#L56) | 56 | public | — | bool |
| OrganizationPatientSession | [`isActive()`](../../../TCV-Backend/app/Models/OrganizationPatientSession.php#L64) | 64 | public | — | bool |
| OrganizationPatientSession | [`scopeActive()`](../../../TCV-Backend/app/Models/OrganizationPatientSession.php#L72) | 72 | public | `$query` | — |
| OrganizationPatientSession | [`scopeByStatus()`](../../../TCV-Backend/app/Models/OrganizationPatientSession.php#L83) | 83 | public | `$query`, `$status` | — |
| OrganizationPatientSession | [`scopeForOrganization()`](../../../TCV-Backend/app/Models/OrganizationPatientSession.php#L91) | 91 | public | `$query`, `$orgId` | — |
| OrganizationPatientSession | [`markAsCompleted()`](../../../TCV-Backend/app/Models/OrganizationPatientSession.php#L99) | 99 | public | — | — |
| OrganizationPatientSession | [`updateStatus()`](../../../TCV-Backend/app/Models/OrganizationPatientSession.php#L107) | 107 | public | `$status` | — |

### `app/Models/OrganizationSettingsOption.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|

### `app/Models/OrganizationType.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|

### `app/Models/Patient.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| Patient | [`user()`](../../../TCV-Backend/app/Models/Patient.php#L28) | 28 | public | — | — |
| Patient | [`tests()`](../../../TCV-Backend/app/Models/Patient.php#L36) | 36 | public | — | — |

### `app/Models/PatientTest.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| PatientTest | [`patient()`](../../../TCV-Backend/app/Models/PatientTest.php#L47) | 47 | public | — | — |
| PatientTest | [`test()`](../../../TCV-Backend/app/Models/PatientTest.php#L55) | 55 | public | — | — |
| PatientTest | [`testInvitation()`](../../../TCV-Backend/app/Models/PatientTest.php#L60) | 60 | public | — | — |
| PatientTest | [`scopeInProgress()`](../../../TCV-Backend/app/Models/PatientTest.php#L66) | 66 | public | `$query` | — |
| PatientTest | [`scopeCompleted()`](../../../TCV-Backend/app/Models/PatientTest.php#L71) | 71 | public | `$query` | — |
| PatientTest | [`scopePending()`](../../../TCV-Backend/app/Models/PatientTest.php#L76) | 76 | public | `$query` | — |
| PatientTest | [`isBothEyesTest()`](../../../TCV-Backend/app/Models/PatientTest.php#L85) | 85 | public | — | bool |
| PatientTest | [`getPairedTest()`](../../../TCV-Backend/app/Models/PatientTest.php#L94) | 94 | public | — | — |
| PatientTest | [`getGroupedTests()`](../../../TCV-Backend/app/Models/PatientTest.php#L108) | 108 | public | — | — |

### `app/Models/PriceDetail.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|

### `app/Models/Privilege.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|

### `app/Models/ProlificId.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| ProlificId | [`organization()`](../../../TCV-Backend/app/Models/ProlificId.php#L30) | 30 | public | — | — |
| ProlificId | [`patient()`](../../../TCV-Backend/app/Models/ProlificId.php#L38) | 38 | public | — | — |

### `app/Models/RestrictedIp.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|

### `app/Models/State.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| State | [`country()`](../../../TCV-Backend/app/Models/State.php#L20) | 20 | public | — | — |

### `app/Models/Test.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| Test | [`getStatusAttribute()`](../../../TCV-Backend/app/Models/Test.php#L31) | 31 | public | `$value` | — |
| Test | [`getLayoutAttribute()`](../../../TCV-Backend/app/Models/Test.php#L36) | 36 | public | `$value` | — |
| Test | [`testAnswers()`](../../../TCV-Backend/app/Models/Test.php#L44) | 44 | public | — | — |
| Test | [`testConditions()`](../../../TCV-Backend/app/Models/Test.php#L52) | 52 | public | — | — |
| Test | [`testSections()`](../../../TCV-Backend/app/Models/Test.php#L60) | 60 | public | — | — |
| Test | [`testSectionPlates()`](../../../TCV-Backend/app/Models/Test.php#L68) | 68 | public | — | — |
| Test | [`scopeActive()`](../../../TCV-Backend/app/Models/Test.php#L76) | 76 | public | `$query` | — |
| Test | [`assignedToUsers()`](../../../TCV-Backend/app/Models/Test.php#L81) | 81 | public | — | — |

### `app/Models/TestAnswer.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| TestAnswer | [`test()`](../../../TCV-Backend/app/Models/TestAnswer.php#L36) | 36 | public | — | — |
| TestAnswer | [`testSection()`](../../../TCV-Backend/app/Models/TestAnswer.php#L44) | 44 | public | — | — |
| TestAnswer | [`testSectionPlate()`](../../../TCV-Backend/app/Models/TestAnswer.php#L52) | 52 | public | — | — |
| TestAnswer | [`patient()`](../../../TCV-Backend/app/Models/TestAnswer.php#L60) | 60 | public | — | — |
| TestAnswer | [`scopeNonDemo()`](../../../TCV-Backend/app/Models/TestAnswer.php#L68) | 68 | public | `$query` | — |
| TestAnswer | [`scopeAnswered()`](../../../TCV-Backend/app/Models/TestAnswer.php#L76) | 76 | public | `$query` | — |
| TestAnswer | [`scopeWrong()`](../../../TCV-Backend/app/Models/TestAnswer.php#L84) | 84 | public | `$query` | — |

### `app/Models/TestCondition.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| TestCondition | [`test()`](../../../TCV-Backend/app/Models/TestCondition.php#L24) | 24 | public | — | — |

### `app/Models/TestEmailTemplates.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|

### `app/Models/TestInvitation.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| TestInvitation | [`test()`](../../../TCV-Backend/app/Models/TestInvitation.php#L35) | 35 | public | — | — |
| TestInvitation | [`user()`](../../../TCV-Backend/app/Models/TestInvitation.php#L43) | 43 | public | — | — |
| TestInvitation | [`isExpired()`](../../../TCV-Backend/app/Models/TestInvitation.php#L51) | 51 | public | — | bool |
| TestInvitation | [`isValid()`](../../../TCV-Backend/app/Models/TestInvitation.php#L59) | 59 | public | — | bool |

### `app/Models/TestResumeToken.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| TestResumeToken | [`patientTest()`](../../../TCV-Backend/app/Models/TestResumeToken.php#L21) | 21 | public | — | — |
| TestResumeToken | [`isExpired()`](../../../TCV-Backend/app/Models/TestResumeToken.php#L26) | 26 | public | — | bool |

### `app/Models/TestSection.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| TestSection | [`getCategoryAttribute()`](../../../TCV-Backend/app/Models/TestSection.php#L45) | 45 | public | `$value` | — |
| TestSection | [`test()`](../../../TCV-Backend/app/Models/TestSection.php#L53) | 53 | public | — | — |
| TestSection | [`testSectionPlates()`](../../../TCV-Backend/app/Models/TestSection.php#L61) | 61 | public | — | — |

### `app/Models/TestSectionPlate.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| TestSectionPlate | [`test()`](../../../TCV-Backend/app/Models/TestSectionPlate.php#L26) | 26 | public | — | — |
| TestSectionPlate | [`testSection()`](../../../TCV-Backend/app/Models/TestSectionPlate.php#L34) | 34 | public | — | — |

### `app/Models/TestSession.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| TestSession | [`testInvitation()`](../../../TCV-Backend/app/Models/TestSession.php#L24) | 24 | public | — | — |

### `app/Models/Transaction.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| Transaction | [`user()`](../../../TCV-Backend/app/Models/Transaction.php#L43) | 43 | public | — | — |
| Transaction | [`details()`](../../../TCV-Backend/app/Models/Transaction.php#L51) | 51 | public | — | — |
| Transaction | [`credits()`](../../../TCV-Backend/app/Models/Transaction.php#L59) | 59 | public | — | — |
| Transaction | [`saveUserTransaction()`](../../../TCV-Backend/app/Models/Transaction.php#L67) | 67 | public static | `$user`, `$paymentIntent`, `$details = []` | — |

### `app/Models/TransactionDetail.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| TransactionDetail | [`transaction()`](../../../TCV-Backend/app/Models/TransactionDetail.php#L32) | 32 | public | — | — |
| TransactionDetail | [`discountCode()`](../../../TCV-Backend/app/Models/TransactionDetail.php#L37) | 37 | public | — | — |

### `app/Models/User.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| User | [`getEmailForVerification()`](../../../TCV-Backend/app/Models/User.php#L72) | 72 | public | — | — |
| User | [`casts()`](../../../TCV-Backend/app/Models/User.php#L82) | 82 | protected | — | array |
| User | [`markEmailAsVerified()`](../../../TCV-Backend/app/Models/User.php#L91) | 91 | public | — | — |
| User | [`hasVerifiedEmail()`](../../../TCV-Backend/app/Models/User.php#L104) | 104 | public | — | — |
| User | [`isSuperAdmin()`](../../../TCV-Backend/app/Models/User.php#L109) | 109 | public | — | — |
| User | [`canImpersonate()`](../../../TCV-Backend/app/Models/User.php#L114) | 114 | public | — | bool |
| User | [`canBeImpersonated()`](../../../TCV-Backend/app/Models/User.php#L119) | 119 | public | — | bool |
| User | [`canImpersonateUser()`](../../../TCV-Backend/app/Models/User.php#L124) | 124 | public | `User $target` | — |
| User | [`stripeDetail()`](../../../TCV-Backend/app/Models/User.php#L138) | 138 | public | — | — |
| User | [`assignedTests()`](../../../TCV-Backend/app/Models/User.php#L143) | 143 | public | — | — |
| User | [`organization()`](../../../TCV-Backend/app/Models/User.php#L152) | 152 | public | — | — |
| User | [`country()`](../../../TCV-Backend/app/Models/User.php#L157) | 157 | public | — | — |

### `app/Models/UserEmailTemplate.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| UserEmailTemplate | [`user()`](../../../TCV-Backend/app/Models/UserEmailTemplate.php#L39) | 39 | public | — | BelongsTo |
| UserEmailTemplate | [`scopeForUser()`](../../../TCV-Backend/app/Models/UserEmailTemplate.php#L47) | 47 | public | `$query`, `int $userId`, `string $type = …` | — |

### `app/Models/UserStripeDetail.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| UserStripeDetail | [`user()`](../../../TCV-Backend/app/Models/UserStripeDetail.php#L18) | 18 | public | — | — |

### `app/Notifications/OrganizationTestUrlNotification.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| OrganizationTestUrlNotification | [`__construct()`](../../../TCV-Backend/app/Notifications/OrganizationTestUrlNotification.php#L20) | 20 | public | `Organization $organization` | — |
| OrganizationTestUrlNotification | [`via()`](../../../TCV-Backend/app/Notifications/OrganizationTestUrlNotification.php#L30) | 30 | public | `object $notifiable` | array |
| OrganizationTestUrlNotification | [`toMail()`](../../../TCV-Backend/app/Notifications/OrganizationTestUrlNotification.php#L38) | 38 | public | `object $notifiable` | MailMessage |
| OrganizationTestUrlNotification | [`toArray()`](../../../TCV-Backend/app/Notifications/OrganizationTestUrlNotification.php#L54) | 54 | public | `object $notifiable` | array |

### `app/Notifications/ResetPasswordNotification.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| ResetPasswordNotification | [`__construct()`](../../../TCV-Backend/app/Notifications/ResetPasswordNotification.php#L20) | 20 | public | `$token`, `$isReset = false` | — |
| ResetPasswordNotification | [`via()`](../../../TCV-Backend/app/Notifications/ResetPasswordNotification.php#L29) | 29 | public | `object $notifiable` | array |
| ResetPasswordNotification | [`toMail()`](../../../TCV-Backend/app/Notifications/ResetPasswordNotification.php#L37) | 37 | public | `object $notifiable` | MailMessage |
| ResetPasswordNotification | [`toArray()`](../../../TCV-Backend/app/Notifications/ResetPasswordNotification.php#L81) | 81 | public | `object $notifiable` | array |

### `app/Notifications/VerifyEmailNotification.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| VerifyEmailNotification | [`__construct()`](../../../TCV-Backend/app/Notifications/VerifyEmailNotification.php#L12) | 12 | public | `$verificationUrl` | — |
| VerifyEmailNotification | [`via()`](../../../TCV-Backend/app/Notifications/VerifyEmailNotification.php#L17) | 17 | public | `$notifiable` | — |
| VerifyEmailNotification | [`toMail()`](../../../TCV-Backend/app/Notifications/VerifyEmailNotification.php#L22) | 22 | public | `$notifiable` | — |

### `app/Policies/CreditsPolicy.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| CreditsPolicy | [`viewAny()`](../../../TCV-Backend/app/Policies/CreditsPolicy.php#L14) | 14 | public | `User $user` | bool |
| CreditsPolicy | [`view()`](../../../TCV-Backend/app/Policies/CreditsPolicy.php#L22) | 22 | public | `User $user`, `Credits $credits` | bool |
| CreditsPolicy | [`create()`](../../../TCV-Backend/app/Policies/CreditsPolicy.php#L30) | 30 | public | `User $user` | bool |
| CreditsPolicy | [`update()`](../../../TCV-Backend/app/Policies/CreditsPolicy.php#L38) | 38 | public | `User $user`, `Credits $credits` | bool |
| CreditsPolicy | [`delete()`](../../../TCV-Backend/app/Policies/CreditsPolicy.php#L46) | 46 | public | `User $user`, `Credits $credits` | bool |
| CreditsPolicy | [`restore()`](../../../TCV-Backend/app/Policies/CreditsPolicy.php#L54) | 54 | public | `User $user`, `Credits $credits` | bool |
| CreditsPolicy | [`forceDelete()`](../../../TCV-Backend/app/Policies/CreditsPolicy.php#L62) | 62 | public | `User $user`, `Credits $credits` | bool |

### `app/Policies/OrgPolicy.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| OrgPolicy | [`viewAny()`](../../../TCV-Backend/app/Policies/OrgPolicy.php#L13) | 13 | public | `User $user` | — |
| OrgPolicy | [`view()`](../../../TCV-Backend/app/Policies/OrgPolicy.php#L21) | 21 | public | `User $user` | — |
| OrgPolicy | [`create()`](../../../TCV-Backend/app/Policies/OrgPolicy.php#L29) | 29 | public | `User $user` | — |
| OrgPolicy | [`update()`](../../../TCV-Backend/app/Policies/OrgPolicy.php#L37) | 37 | public | `User $user` | — |
| OrgPolicy | [`delete()`](../../../TCV-Backend/app/Policies/OrgPolicy.php#L45) | 45 | public | `User $user` | — |

### `app/Policies/TestPolicy.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| TestPolicy | [`viewTests()`](../../../TCV-Backend/app/Policies/TestPolicy.php#L12) | 12 | public | `User $user` | — |
| TestPolicy | [`createTests()`](../../../TCV-Backend/app/Policies/TestPolicy.php#L20) | 20 | public | `User $user` | — |
| TestPolicy | [`updateTests()`](../../../TCV-Backend/app/Policies/TestPolicy.php#L28) | 28 | public | `User $user` | — |
| TestPolicy | [`deleteTests()`](../../../TCV-Backend/app/Policies/TestPolicy.php#L36) | 36 | public | `User $user` | — |
| TestPolicy | [`cloneTests()`](../../../TCV-Backend/app/Policies/TestPolicy.php#L43) | 43 | public | `User $user` | — |

### `app/Providers/AppServiceProvider.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| AppServiceProvider | [`register()`](../../../TCV-Backend/app/Providers/AppServiceProvider.php#L14) | 14 | public | — | void |
| AppServiceProvider | [`boot()`](../../../TCV-Backend/app/Providers/AppServiceProvider.php#L22) | 22 | public | — | void |
| AppServiceProvider | [`warnIfFrontendAppUrlLooksInvalid()`](../../../TCV-Backend/app/Providers/AppServiceProvider.php#L37) | 37 | protected | — | void |

### `app/Providers/AuthServiceProvider.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| AuthServiceProvider | [`boot()`](../../../TCV-Backend/app/Providers/AuthServiceProvider.php#L27) | 27 | public | — | — |

### `app/Providers/EventServiceProvider.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|

### `app/Providers/LmsServiceProvider.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| LmsServiceProvider | [`register()`](../../../TCV-Backend/app/Providers/LmsServiceProvider.php#L20) | 20 | public | — | void |
| LmsServiceProvider | [`boot()`](../../../TCV-Backend/app/Providers/LmsServiceProvider.php#L36) | 36 | public | — | void |

### `app/Repositories/EmailTemplateRepository.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| EmailTemplateRepository | [`getUserTemplate()`](../../../TCV-Backend/app/Repositories/EmailTemplateRepository.php#L14) | 14 | public | `int $userId`, `string $type = …` | ?UserEmailTemplate |
| EmailTemplateRepository | [`getAdminDefaultTemplate()`](../../../TCV-Backend/app/Repositories/EmailTemplateRepository.php#L22) | 22 | public | `string $type = …` | ?TestEmailTemplates |
| EmailTemplateRepository | [`saveUserTemplate()`](../../../TCV-Backend/app/Repositories/EmailTemplateRepository.php#L35) | 35 | public | `int $userId`, `array $data` | UserEmailTemplate |
| EmailTemplateRepository | [`deleteUserTemplate()`](../../../TCV-Backend/app/Repositories/EmailTemplateRepository.php#L52) | 52 | public | `int $userId`, `string $type = …` | bool |
| EmailTemplateRepository | [`hasCustomTemplate()`](../../../TCV-Backend/app/Repositories/EmailTemplateRepository.php#L60) | 60 | public | `int $userId`, `string $type = …` | bool |

### `app/Rules/TurnstileToken.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| TurnstileToken | [`validate()`](../../../TCV-Backend/app/Rules/TurnstileToken.php#L14) | 14 | public | `string $attribute`, `mixed $value`, `Closure $fail` | void |
| TurnstileToken | [`message()`](../../../TCV-Backend/app/Rules/TurnstileToken.php#L31) | 31 | public | — | string |

### `app/Services/Audit/AuditLogger.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| AuditLogger | [`log()`](../../../TCV-Backend/app/Services/Audit/AuditLogger.php#L10) | 10 | public static | `string $table`, `string $entityColumn`, `int $entityId`, `?int $userId`, `string $action`, `array $oldValues = []`, `array $newValues = []` | void |

### `app/Services/Audit/PricingAuditService.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| PricingAuditService | [`createLog()`](../../../TCV-Backend/app/Services/Audit/PricingAuditService.php#L11) | 11 | public static | `int $pricingId`, `?int $userId`, `array $oldValues`, `array $newValues` | void |

### `app/Services/ColorVisionDiagnosisService.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| ColorVisionDiagnosisService | [`calculateDiagnosis()`](../../../TCV-Backend/app/Services/ColorVisionDiagnosisService.php#L23) | 23 | public | `PatientTest $patientTest` | array |
| ColorVisionDiagnosisService | [`getSectionsWithAnswers()`](../../../TCV-Backend/app/Services/ColorVisionDiagnosisService.php#L50) | 50 | private | `string $uniqueTestId`, `int $testId` | array |
| ColorVisionDiagnosisService | [`routeCalculation()`](../../../TCV-Backend/app/Services/ColorVisionDiagnosisService.php#L101) | 101 | private | `array $sections`, `string $testName` | array |
| ColorVisionDiagnosisService | [`isBaselineTest()`](../../../TCV-Backend/app/Services/ColorVisionDiagnosisService.php#L127) | 127 | private | `array $sections` | bool |
| ColorVisionDiagnosisService | [`normalizeCategoryToNumber()`](../../../TCV-Backend/app/Services/ColorVisionDiagnosisService.php#L159) | 159 | private | `$category` | ?int |
| ColorVisionDiagnosisService | [`calculateSectionSeverity()`](../../../TCV-Backend/app/Services/ColorVisionDiagnosisService.php#L182) | 182 | private | `array $section` | string |
| ColorVisionDiagnosisService | [`isSectionPass()`](../../../TCV-Backend/app/Services/ColorVisionDiagnosisService.php#L227) | 227 | public | `array $section` | bool |
| ColorVisionDiagnosisService | [`calculateBaselineTestResult()`](../../../TCV-Backend/app/Services/ColorVisionDiagnosisService.php#L256) | 256 | private | `array $sections` | array |
| ColorVisionDiagnosisService | [`calculateFAATestResult()`](../../../TCV-Backend/app/Services/ColorVisionDiagnosisService.php#L331) | 331 | private | `array $sections` | array |
| ColorVisionDiagnosisService | [`calculateExtendedTestResult()`](../../../TCV-Backend/app/Services/ColorVisionDiagnosisService.php#L385) | 385 | private | `array $sections` | array |
| ColorVisionDiagnosisService | [`calculateSingleSectionResult()`](../../../TCV-Backend/app/Services/ColorVisionDiagnosisService.php#L472) | 472 | private | `array $sections` | array |
| ColorVisionDiagnosisService | [`findSection()`](../../../TCV-Backend/app/Services/ColorVisionDiagnosisService.php#L520) | 520 | private | `array $sections`, `int $category` | ?array |
| ColorVisionDiagnosisService | [`findSectionByTypeContains()`](../../../TCV-Backend/app/Services/ColorVisionDiagnosisService.php#L538) | 538 | private | `array $sections`, `int $category`, `string $contains` | ?array |

### `app/Services/DiscountCodeService.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| DiscountCodeService | [`validate()`](../../../TCV-Backend/app/Services/DiscountCodeService.php#L16) | 16 | public | `User $user`, `string $code`, `float $amount`, `int $credits` | array |
| DiscountCodeService | [`calculate()`](../../../TCV-Backend/app/Services/DiscountCodeService.php#L74) | 74 | public | `DiscountCode $discount`, `float $amount` | array |
| DiscountCodeService | [`syncRestrictions()`](../../../TCV-Backend/app/Services/DiscountCodeService.php#L94) | 94 | public | `DiscountCode $discount`, `?array $userIds`, `?array $priceTierIds` | void |
| DiscountCodeService | [`countUses()`](../../../TCV-Backend/app/Services/DiscountCodeService.php#L107) | 107 | public | `int $discountId`, `?int $userId = null` | int |
| DiscountCodeService | [`creditMatchesTier()`](../../../TCV-Backend/app/Services/DiscountCodeService.php#L119) | 119 | private | `int $credits`, `DiscountCode $discount` | bool |
| DiscountCodeService | [`success()`](../../../TCV-Backend/app/Services/DiscountCodeService.php#L129) | 129 | private | `DiscountCode $discount`, `float $amount` | array |
| DiscountCodeService | [`fail()`](../../../TCV-Backend/app/Services/DiscountCodeService.php#L152) | 152 | private | `string $message`, `int $status = 400` | array |

### `app/Services/EmailTemplateService.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| EmailTemplateService | [`__construct()`](../../../TCV-Backend/app/Services/EmailTemplateService.php#L11) | 11 | public | `EmailTemplateRepository $repository` | — |
| EmailTemplateService | [`getTemplateForUser()`](../../../TCV-Backend/app/Services/EmailTemplateService.php#L21) | 21 | public | `int $userId`, `string $type = …` | array |
| EmailTemplateService | [`saveUserTemplate()`](../../../TCV-Backend/app/Services/EmailTemplateService.php#L67) | 67 | public | `int $userId`, `array $data` | array |
| EmailTemplateService | [`resetToDefault()`](../../../TCV-Backend/app/Services/EmailTemplateService.php#L107) | 107 | public | `int $userId`, `string $type = …` | array |
| EmailTemplateService | [`validatePlaceholder()`](../../../TCV-Backend/app/Services/EmailTemplateService.php#L134) | 134 | public | `string $body`, `string $placeholder = '{{verification_link}}'` | bool |

### `app/Services/HubSpotService.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| HubSpotService | [`__construct()`](../../../TCV-Backend/app/Services/HubSpotService.php#L16) | 16 | public | — | — |
| HubSpotService | [`submitEnquiry()`](../../../TCV-Backend/app/Services/HubSpotService.php#L31) | 31 | public | `array $data` | void |
| HubSpotService | [`http()`](../../../TCV-Backend/app/Services/HubSpotService.php#L44) | 44 | private | — | PendingRequest |
| HubSpotService | [`upsertContact()`](../../../TCV-Backend/app/Services/HubSpotService.php#L56) | 56 | private | `array $data` | string |
| HubSpotService | [`createTicket()`](../../../TCV-Backend/app/Services/HubSpotService.php#L119) | 119 | private | `array $data`, `string $contactId` | void |

### `app/Services/Lms/Contracts/DeliveryResult.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| DeliveryResult | [`__construct()`](../../../TCV-Backend/app/Services/Lms/Contracts/DeliveryResult.php#L7) | 7 | public | `bool $success`, `?string $providerRefId = null`, `?array $providerResponse = null`, `?string $errorCode = null`, `?string $errorMessage = null`, `bool $isAuthError = false` | — |
| DeliveryResult | [`ok()`](../../../TCV-Backend/app/Services/Lms/Contracts/DeliveryResult.php#L17) | 17 | public static | `string $providerRefId`, `?array $response = null` | self |
| DeliveryResult | [`fail()`](../../../TCV-Backend/app/Services/Lms/Contracts/DeliveryResult.php#L22) | 22 | public static | `string $errorCode`, `string $errorMessage`, `bool $isAuthError = false` | self |

### `app/Services/Lms/Contracts/LmsIdentity.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| LmsIdentity | [`__construct()`](../../../TCV-Backend/app/Services/Lms/Contracts/LmsIdentity.php#L7) | 7 | public | `string $externalId`, `?string $fullName = null`, `?string $firstName = null`, `?string $lastName = null`, `?string $email = null` | — |

### `app/Services/Lms/Contracts/LmsLaunchContext.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| LmsLaunchContext | [`__construct()`](../../../TCV-Backend/app/Services/Lms/Contracts/LmsLaunchContext.php#L7) | 7 | public | `string $externalSessionId`, `?string $externalUserId = null`, `?string $externalUserName = null`, `?string $returnUrl = null`, `array $raw = []` | — |

### `app/Services/Lms/Contracts/LmsProviderInterface.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| LmsProviderInterface | [`validateLaunch()`](../../../TCV-Backend/app/Services/Lms/Contracts/LmsProviderInterface.php#L15) | 15 | public | `array $params`, `LmsProviderConfig $config` | LmsLaunchContext |
| LmsProviderInterface | [`buildCompletionPayload()`](../../../TCV-Backend/app/Services/Lms/Contracts/LmsProviderInterface.php#L22) | 22 | public | `PatientTest $test`, `LmsSession $session`, `?PatientTest $pairedTest = null` | array |
| LmsProviderInterface | [`deliver()`](../../../TCV-Backend/app/Services/Lms/Contracts/LmsProviderInterface.php#L28) | 28 | public | `array $payload`, `LmsSession $session` | DeliveryResult |
| LmsProviderInterface | [`refreshToken()`](../../../TCV-Backend/app/Services/Lms/Contracts/LmsProviderInterface.php#L34) | 34 | public | `LmsProviderConfig $config` | string |
| LmsProviderInterface | [`supportsResume()`](../../../TCV-Backend/app/Services/Lms/Contracts/LmsProviderInterface.php#L39) | 39 | public | — | bool |

### `app/Services/Lms/LmsDeliveryService.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| LmsDeliveryService | [`enqueueCompletion()`](../../../TCV-Backend/app/Services/Lms/LmsDeliveryService.php#L19) | 19 | public | `PatientTest $patientTest`, `LmsSession $session`, `?PatientTest $pairedTest = null` | void |
| LmsDeliveryService | [`enqueueSectionProgress()`](../../../TCV-Backend/app/Services/Lms/LmsDeliveryService.php#L62) | 62 | public | `LmsSession $session`, `int $sectionId` | void |
| LmsDeliveryService | [`replayDeadLetter()`](../../../TCV-Backend/app/Services/Lms/LmsDeliveryService.php#L111) | 111 | public | `LmsDeliveryQueue $entry` | void |
| LmsDeliveryService | [`dismissDeadLetter()`](../../../TCV-Backend/app/Services/Lms/LmsDeliveryService.php#L134) | 134 | public | `LmsDeliveryQueue $entry` | void |

### `app/Services/Lms/LmsLaunchService.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| LmsLaunchService | [`getOrCreateProviderConfig()`](../../../TCV-Backend/app/Services/Lms/LmsLaunchService.php#L17) | 17 | public | `int $orgId`, `string $providerType = …` | LmsProviderConfig |
| LmsLaunchService | [`createSession()`](../../../TCV-Backend/app/Services/Lms/LmsLaunchService.php#L34) | 34 | public | `int $orgId`, `LmsProviderConfig $providerConfig`, `Request $request`, `array $lmsContext = []` | array |
| LmsLaunchService | [`advanceStatus()`](../../../TCV-Backend/app/Services/Lms/LmsLaunchService.php#L74) | 74 | public | `LmsSession $session`, `string $newStatus`, `array $extraFields = []` | void |
| LmsLaunchService | [`buildDefaultConfig()`](../../../TCV-Backend/app/Services/Lms/LmsLaunchService.php#L94) | 94 | private | `string $providerType` | string |

### `app/Services/Lms/LmsProviderRegistry.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| LmsProviderRegistry | [`register()`](../../../TCV-Backend/app/Services/Lms/LmsProviderRegistry.php#L15) | 15 | public | `string $type`, `LmsProviderInterface $provider` | void |
| LmsProviderRegistry | [`for()`](../../../TCV-Backend/app/Services/Lms/LmsProviderRegistry.php#L20) | 20 | public | `LmsSession $session` | LmsProviderInterface |
| LmsProviderRegistry | [`forConfig()`](../../../TCV-Backend/app/Services/Lms/LmsProviderRegistry.php#L28) | 28 | public | `LmsProviderConfig $config` | LmsProviderInterface |
| LmsProviderRegistry | [`has()`](../../../TCV-Backend/app/Services/Lms/LmsProviderRegistry.php#L34) | 34 | public | `string $type` | bool |

### `app/Services/Lms/Providers/CornerstoneProvider.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| CornerstoneProvider | [`__construct()`](../../../TCV-Backend/app/Services/Lms/Providers/CornerstoneProvider.php#L20) | 20 | public | `XapiStatementBuilder $statementBuilder` | — |
| CornerstoneProvider | [`validateLaunch()`](../../../TCV-Backend/app/Services/Lms/Providers/CornerstoneProvider.php#L25) | 25 | public | `array $params`, `LmsProviderConfig $config` | LmsLaunchContext |
| CornerstoneProvider | [`buildCompletionPayload()`](../../../TCV-Backend/app/Services/Lms/Providers/CornerstoneProvider.php#L35) | 35 | public | `PatientTest $test`, `LmsSession $session`, `?PatientTest $pairedTest = null` | array |
| CornerstoneProvider | [`deliver()`](../../../TCV-Backend/app/Services/Lms/Providers/CornerstoneProvider.php#L65) | 65 | public | `array $payload`, `LmsSession $session` | DeliveryResult |
| CornerstoneProvider | [`refreshToken()`](../../../TCV-Backend/app/Services/Lms/Providers/CornerstoneProvider.php#L121) | 121 | public | `LmsProviderConfig $config` | string |
| CornerstoneProvider | [`supportsResume()`](../../../TCV-Backend/app/Services/Lms/Providers/CornerstoneProvider.php#L168) | 168 | public | — | bool |
| CornerstoneProvider | [`getCurrentToken()`](../../../TCV-Backend/app/Services/Lms/Providers/CornerstoneProvider.php#L173) | 173 | private | `LmsProviderConfig $config` | ?string |

### `app/Services/Lms/Providers/GenericWebhookProvider.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| GenericWebhookProvider | [`validateLaunch()`](../../../TCV-Backend/app/Services/Lms/Providers/GenericWebhookProvider.php#L16) | 16 | public | `array $params`, `LmsProviderConfig $config` | LmsLaunchContext |
| GenericWebhookProvider | [`buildCompletionPayload()`](../../../TCV-Backend/app/Services/Lms/Providers/GenericWebhookProvider.php#L27) | 27 | public | `PatientTest $test`, `LmsSession $session`, `?PatientTest $pairedTest = null` | array |
| GenericWebhookProvider | [`deliver()`](../../../TCV-Backend/app/Services/Lms/Providers/GenericWebhookProvider.php#L67) | 67 | public | `array $payload`, `LmsSession $session` | DeliveryResult |
| GenericWebhookProvider | [`refreshToken()`](../../../TCV-Backend/app/Services/Lms/Providers/GenericWebhookProvider.php#L103) | 103 | public | `LmsProviderConfig $config` | string |
| GenericWebhookProvider | [`supportsResume()`](../../../TCV-Backend/app/Services/Lms/Providers/GenericWebhookProvider.php#L109) | 109 | public | — | bool |
| GenericWebhookProvider | [`buildAuthHeaders()`](../../../TCV-Backend/app/Services/Lms/Providers/GenericWebhookProvider.php#L114) | 114 | private | `array $config` | array |

### `app/Services/Lms/XapiStatementBuilder.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| XapiStatementBuilder | [`buildSectionProgressBatch()`](../../../TCV-Backend/app/Services/Lms/XapiStatementBuilder.php#L19) | 19 | public | `PatientTest $test`, `LmsSession $session`, `int $sectionId`, `string $idempotencyKey`, `array $config` | array |
| XapiStatementBuilder | [`buildFullBatch()`](../../../TCV-Backend/app/Services/Lms/XapiStatementBuilder.php#L66) | 66 | public | `PatientTest $test`, `LmsSession $session`, `string $idempotencyKey`, `array $config`, `?PatientTest $pairedTest = null` | array |
| XapiStatementBuilder | [`buildInitialized()`](../../../TCV-Backend/app/Services/Lms/XapiStatementBuilder.php#L144) | 144 | public | `PatientTest $test`, `LmsSession $session`, `string $statementId`, `array $config` | array |
| XapiStatementBuilder | [`buildAttempted()`](../../../TCV-Backend/app/Services/Lms/XapiStatementBuilder.php#L166) | 166 | public | `TestAnswer $answer`, `?TestSection $section`, `PatientTest $test`, `LmsSession $session`, `string $statementId`, `array $config` | array |
| XapiStatementBuilder | [`buildSectionCompleted()`](../../../TCV-Backend/app/Services/Lms/XapiStatementBuilder.php#L237) | 237 | public | `Collection $sectionAnswers`, `TestSection $section`, `PatientTest $test`, `LmsSession $session`, `string $statementId`, `array $config` | array |
| XapiStatementBuilder | [`buildCompletion()`](../../../TCV-Backend/app/Services/Lms/XapiStatementBuilder.php#L296) | 296 | public | `PatientTest $test`, `LmsSession $session`, `string $statementId`, `array $config`, `?PatientTest $pairedTest = null` | array |
| XapiStatementBuilder | [`deterministicId()`](../../../TCV-Backend/app/Services/Lms/XapiStatementBuilder.php#L328) | 328 | private | `string $idempotencyKey`, `string $suffix` | string |
| XapiStatementBuilder | [`buildActor()`](../../../TCV-Backend/app/Services/Lms/XapiStatementBuilder.php#L341) | 341 | private | `LmsSession $session`, `array $config` | array |
| XapiStatementBuilder | [`buildObject()`](../../../TCV-Backend/app/Services/Lms/XapiStatementBuilder.php#L367) | 367 | private | `PatientTest $test` | array |
| XapiStatementBuilder | [`buildCombinedResult()`](../../../TCV-Backend/app/Services/Lms/XapiStatementBuilder.php#L386) | 386 | private | `array $osResultJson`, `array $odResultJson` | array |
| XapiStatementBuilder | [`buildResult()`](../../../TCV-Backend/app/Services/Lms/XapiStatementBuilder.php#L430) | 430 | private | `array $resultJson` | array |
| XapiStatementBuilder | [`buildContext()`](../../../TCV-Backend/app/Services/Lms/XapiStatementBuilder.php#L465) | 465 | private | `LmsSession $session` | array |

### `app/Services/PatientTestTransformer.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| PatientTestTransformer | [`transformTests()`](../../../TCV-Backend/app/Services/PatientTestTransformer.php#L24) | 24 | public | `Collection $patientTests` | array |
| PatientTestTransformer | [`groupTests()`](../../../TCV-Backend/app/Services/PatientTestTransformer.php#L50) | 50 | private | `Collection $patientTests` | array |
| PatientTestTransformer | [`transformSingleTest()`](../../../TCV-Backend/app/Services/PatientTestTransformer.php#L80) | 80 | private | `PatientTest $test` | array |
| PatientTestTransformer | [`transformPairedTests()`](../../../TCV-Backend/app/Services/PatientTestTransformer.php#L107) | 107 | private | `array $tests` | array |
| PatientTestTransformer | [`calculateAggregateStatus()`](../../../TCV-Backend/app/Services/PatientTestTransformer.php#L176) | 176 | private | `PatientTest $osTest`, `PatientTest $odTest` | string |

### `app/Services/PaymentManager.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| PaymentManager | [`initialize()`](../../../TCV-Backend/app/Services/PaymentManager.php#L15) | 15 | public static | `?string $selectedProvider = null` | void |
| PaymentManager | [`getProviderClass()`](../../../TCV-Backend/app/Services/PaymentManager.php#L45) | 45 | private static | `string $name` | ?string |
| PaymentManager | [`getProvider()`](../../../TCV-Backend/app/Services/PaymentManager.php#L51) | 51 | public static | `string $name` | ?PaymentProviderInterface |
| PaymentManager | [`getAvailableProviders()`](../../../TCV-Backend/app/Services/PaymentManager.php#L63) | 63 | public static | — | array |
| PaymentManager | [`getActiveProviders()`](../../../TCV-Backend/app/Services/PaymentManager.php#L84) | 84 | public static | — | array |
| PaymentManager | [`isProviderSupported()`](../../../TCV-Backend/app/Services/PaymentManager.php#L103) | 103 | public static | `string $name` | bool |

### `app/Services/PaymentProviders/BasePaymentProvider.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| BasePaymentProvider | [`__construct()`](../../../TCV-Backend/app/Services/PaymentProviders/BasePaymentProvider.php#L15) | 15 | public | `array $config = []` | — |
| BasePaymentProvider | [`getName()`](../../../TCV-Backend/app/Services/PaymentProviders/BasePaymentProvider.php#L20) | 20 | public | — | string |
| BasePaymentProvider | [`isActive()`](../../../TCV-Backend/app/Services/PaymentProviders/BasePaymentProvider.php#L25) | 25 | public | — | bool |
| BasePaymentProvider | [`createTransactionRecord()`](../../../TCV-Backend/app/Services/PaymentProviders/BasePaymentProvider.php#L30) | 30 | protected | `array $paymentData` | Transaction |
| BasePaymentProvider | [`addUserCredits()`](../../../TCV-Backend/app/Services/PaymentProviders/BasePaymentProvider.php#L62) | 62 | protected | `User $user`, `array $paymentData` | Credits |
| BasePaymentProvider | [`logPaymentActivity()`](../../../TCV-Backend/app/Services/PaymentProviders/BasePaymentProvider.php#L73) | 73 | protected | `string $action`, `array $data`, `?Exception $error = null` | void |
| BasePaymentProvider | [`initializePayment()`](../../../TCV-Backend/app/Services/PaymentProviders/BasePaymentProvider.php#L89) | 89 | public | `array $data` | array |
| BasePaymentProvider | [`confirmPayment()`](../../../TCV-Backend/app/Services/PaymentProviders/BasePaymentProvider.php#L90) | 90 | public | `array $data` | array |
| BasePaymentProvider | [`handleWebhook()`](../../../TCV-Backend/app/Services/PaymentProviders/BasePaymentProvider.php#L91) | 91 | public | `array $data` | array |
| BasePaymentProvider | [`getSupportedMethods()`](../../../TCV-Backend/app/Services/PaymentProviders/BasePaymentProvider.php#L92) | 92 | public | — | array |

### `app/Services/PaymentProviders/PaymentProviderInterface.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| PaymentProviderInterface | [`getName()`](../../../TCV-Backend/app/Services/PaymentProviders/PaymentProviderInterface.php#L7) | 7 | public | — | string |
| PaymentProviderInterface | [`initializePayment()`](../../../TCV-Backend/app/Services/PaymentProviders/PaymentProviderInterface.php#L8) | 8 | public | `array $paymentData` | array |
| PaymentProviderInterface | [`confirmPayment()`](../../../TCV-Backend/app/Services/PaymentProviders/PaymentProviderInterface.php#L9) | 9 | public | `array $paymentData` | array |
| PaymentProviderInterface | [`handleWebhook()`](../../../TCV-Backend/app/Services/PaymentProviders/PaymentProviderInterface.php#L10) | 10 | public | `array $data` | array |
| PaymentProviderInterface | [`isActive()`](../../../TCV-Backend/app/Services/PaymentProviders/PaymentProviderInterface.php#L11) | 11 | public | — | bool |
| PaymentProviderInterface | [`getSupportedMethods()`](../../../TCV-Backend/app/Services/PaymentProviders/PaymentProviderInterface.php#L12) | 12 | public | — | array |

### `app/Services/PaymentProviders/StripeProvider.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| StripeProvider | [`__construct()`](../../../TCV-Backend/app/Services/PaymentProviders/StripeProvider.php#L20) | 20 | public | `array $config = []` | — |
| StripeProvider | [`getSupportedMethods()`](../../../TCV-Backend/app/Services/PaymentProviders/StripeProvider.php#L28) | 28 | public | — | array |
| StripeProvider | [`createSetupIntent()`](../../../TCV-Backend/app/Services/PaymentProviders/StripeProvider.php#L43) | 43 | public | `array $data` | array |
| StripeProvider | [`initializePayment()`](../../../TCV-Backend/app/Services/PaymentProviders/StripeProvider.php#L64) | 64 | public | `array $paymentData` | array |
| StripeProvider | [`confirmPayment()`](../../../TCV-Backend/app/Services/PaymentProviders/StripeProvider.php#L106) | 106 | public | `array $paymentData` | array |
| StripeProvider | [`handleWebhook()`](../../../TCV-Backend/app/Services/PaymentProviders/StripeProvider.php#L179) | 179 | public | `array $data` | array |
| StripeProvider | [`getOrCreateCustomer()`](../../../TCV-Backend/app/Services/PaymentProviders/StripeProvider.php#L208) | 208 | private | `User $user` | Customer |
| StripeProvider | [`attachAndPersistPaymentMethod()`](../../../TCV-Backend/app/Services/PaymentProviders/StripeProvider.php#L213) | 213 | private | `User $user`, `string $customerId`, `Stripe\PaymentMethod $paymentMethod` | void |

### `app/Services/Reports/DiscountCodeReportService.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| DiscountCodeReportService | [`getReports()`](../../../TCV-Backend/app/Services/Reports/DiscountCodeReportService.php#L9) | 9 | public | `array $params` | object |
| DiscountCodeReportService | [`getSummary()`](../../../TCV-Backend/app/Services/Reports/DiscountCodeReportService.php#L31) | 31 | public | `array $params` | array |
| DiscountCodeReportService | [`buildQuery()`](../../../TCV-Backend/app/Services/Reports/DiscountCodeReportService.php#L83) | 83 | public | `?string $search`, `string $sortBy`, `string $sortOrder`, `?string $fromDate`, `?string $toDate`, `?string $code` | — |

### `app/Services/Reports/UserTestsReportService.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| UserTestsReportService | [`__construct()`](../../../TCV-Backend/app/Services/Reports/UserTestsReportService.php#L18) | 18 | public | `PatientTestTransformer $transformer` | — |
| UserTestsReportService | [`query()`](../../../TCV-Backend/app/Services/Reports/UserTestsReportService.php#L23) | 23 | public | `array $filters` | — |
| UserTestsReportService | [`getPatientTestsForReport()`](../../../TCV-Backend/app/Services/Reports/UserTestsReportService.php#L86) | 86 | public | `int $patientId`, `array $filters`, `int $limit`, `int $page` | array |
| UserTestsReportService | [`getPatientTestsForExport()`](../../../TCV-Backend/app/Services/Reports/UserTestsReportService.php#L109) | 109 | public | `int $patientId`, `array $filters` | array |
| UserTestsReportService | [`buildTransformedTests()`](../../../TCV-Backend/app/Services/Reports/UserTestsReportService.php#L119) | 119 | private | `int $patientId`, `array $filters` | array |
| UserTestsReportService | [`getPatientsWithTests()`](../../../TCV-Backend/app/Services/Reports/UserTestsReportService.php#L165) | 165 | public | `array $filters` | — |

### `app/Services/SecureImageService.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| SecureImageService | [`getSecurePlateUrl()`](../../../TCV-Backend/app/Services/SecureImageService.php#L24) | 24 | public | `string $imagePath`, `string $uniqueTestId`, `int $testAnswerId` | ?string |
| SecureImageService | [`validateTestSession()`](../../../TCV-Backend/app/Services/SecureImageService.php#L73) | 73 | private | `string $uniqueTestId`, `int $testAnswerId` | bool |
| SecureImageService | [`getBatchSecurePlateUrls()`](../../../TCV-Backend/app/Services/SecureImageService.php#L98) | 98 | public | `string $uniqueTestId`, `int $sectionId` | array |
| SecureImageService | [`uploadPlateToS3()`](../../../TCV-Backend/app/Services/SecureImageService.php#L131) | 131 | public | `string $localPath`, `string $s3Path` | bool |
| SecureImageService | [`revokeAccess()`](../../../TCV-Backend/app/Services/SecureImageService.php#L160) | 160 | public | `string $uniqueTestId`, `int $testAnswerId` | void |

### `app/Services/StripeService.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| StripeService | [`__construct()`](../../../TCV-Backend/app/Services/StripeService.php#L18) | 18 | public | — | — |
| StripeService | [`getStripeClient()`](../../../TCV-Backend/app/Services/StripeService.php#L32) | 32 | public | — | — |
| StripeService | [`createOrGetCustomer()`](../../../TCV-Backend/app/Services/StripeService.php#L40) | 40 | public | `User $user` | Customer |
| StripeService | [`getCustomerPaymentMethods()`](../../../TCV-Backend/app/Services/StripeService.php#L81) | 81 | public | `User $user` | array |
| StripeService | [`paymentMethodExists()`](../../../TCV-Backend/app/Services/StripeService.php#L118) | 118 | public | `User $user`, `string $paymentMethodId` | bool |
| StripeService | [`createStripePaymentIntent()`](../../../TCV-Backend/app/Services/StripeService.php#L146) | 146 | public | `User $user`, `float $amount`, `int $credits`, `string $paymentMethod`, `array $billingInfo` | PaymentIntent |
| StripeService | [`createACHPaymentIntent()`](../../../TCV-Backend/app/Services/StripeService.php#L188) | 188 | public | `User $user`, `float $amount`, `int $credits`, `array $billingInfo` | PaymentIntent |
| StripeService | [`createBankTransferTransaction()`](../../../TCV-Backend/app/Services/StripeService.php#L215) | 215 | public | `User $user`, `float $amount`, `int $credits`, `array $billingInfo` | array |
| StripeService | [`getPaymentMethods()`](../../../TCV-Backend/app/Services/StripeService.php#L261) | 261 | public | `User $user` | array |
| StripeService | [`setDefaultPaymentMethod()`](../../../TCV-Backend/app/Services/StripeService.php#L269) | 269 | public | `User $user`, `string $paymentMethodId` | bool |
| StripeService | [`removePaymentMethod()`](../../../TCV-Backend/app/Services/StripeService.php#L299) | 299 | public | `User $user`, `string $paymentMethodId` | bool |
| StripeService | [`attachPaymentMethod()`](../../../TCV-Backend/app/Services/StripeService.php#L321) | 321 | public | `$customerId`, `$paymentMethodId` | — |

### `app/Services/TestAssignmentService.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| TestAssignmentService | [`buildActiveTestQuery()`](../../../TCV-Backend/app/Services/TestAssignmentService.php#L26) | 26 | private | `int $patientId`, `int $testId`, `string $eyeTested` | — |
| TestAssignmentService | [`checkForActiveTest()`](../../../TCV-Backend/app/Services/TestAssignmentService.php#L54) | 54 | public | `int $patientId`, `int $testId`, `string $eyeTested` | void |
| TestAssignmentService | [`findActiveTest()`](../../../TCV-Backend/app/Services/TestAssignmentService.php#L72) | 72 | public | `int $patientId`, `int $testId`, `string $eyeTested` | ?PatientTest |
| TestAssignmentService | [`loadTestSections()`](../../../TCV-Backend/app/Services/TestAssignmentService.php#L89) | 89 | public | `int $testId` | — |
| TestAssignmentService | [`createSingleEyeTest()`](../../../TCV-Backend/app/Services/TestAssignmentService.php#L100) | 100 | public | `int $testId`, `Patient $patient`, `string $eyeTested`, `bool $sendMail`, `bool $isEmailInvite = false`, `array $occupationData = []`, `array $extraData = []` | array |
| TestAssignmentService | [`createBothEyesTests()`](../../../TCV-Backend/app/Services/TestAssignmentService.php#L149) | 149 | public | `int $testId`, `Patient $patient`, `bool $sendMail`, `bool $isEmailInvite = false`, `array $occupationData = []`, `array $extraData = []` | array |
| TestAssignmentService | [`createTestSession()`](../../../TCV-Backend/app/Services/TestAssignmentService.php#L227) | 227 | private | `int $testId`, `Patient $patient`, `string $eyeTested`, `$testSections`, `?string $parentTestId = null`, `string $status = …`, `bool $isEmailInvite = false`, `array $occupationData = []`, `array $extraData = []` | PatientTest |
| TestAssignmentService | [`getPairedTestInfo()`](../../../TCV-Backend/app/Services/TestAssignmentService.php#L289) | 289 | public | `PatientTest $patientTest` | ?array |
| TestAssignmentService | [`shuffleInBatches()`](../../../TCV-Backend/app/Services/TestAssignmentService.php#L314) | 314 | private | `array $plates`, `int $batchSize = …` | array |

### `app/Services/TestExecutionService.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| TestExecutionService | [`__construct()`](../../../TCV-Backend/app/Services/TestExecutionService.php#L23) | 23 | public | `TestSectionTerminationService $terminationService`, `TestSectionProgressionService $progressionService`, `TestResultService $resultService`, `SecureImageService $secureImageService` | — |
| TestExecutionService | [`submitAnswer()`](../../../TCV-Backend/app/Services/TestExecutionService.php#L43) | 43 | public | `int $testAnswerId`, `$submittedAnswer`, `bool $isAutoSubmit` | array |
| TestExecutionService | [`finalizeTestIfCompleted()`](../../../TCV-Backend/app/Services/TestExecutionService.php#L114) | 114 | public | `string $uniqueTestId` | void |
| TestExecutionService | [`getSessionDetails()`](../../../TCV-Backend/app/Services/TestExecutionService.php#L179) | 179 | public | `string $uniqueTestId` | array |
| TestExecutionService | [`getSectionPlatesWithProgress()`](../../../TCV-Backend/app/Services/TestExecutionService.php#L304) | 304 | public | `string $uniqueTestId`, `int $sectionId` | array |
| TestExecutionService | [`getPlateUrl()`](../../../TCV-Backend/app/Services/TestExecutionService.php#L382) | 382 | public | `string $uniqueTestId`, `int $testAnswerId` | ?string |
| TestExecutionService | [`resolveCanonicalTestId()`](../../../TCV-Backend/app/Services/TestExecutionService.php#L406) | 406 | private | `PatientTest $patientTest` | string |
| TestExecutionService | [`markTestInvitationAsUsed()`](../../../TCV-Backend/app/Services/TestExecutionService.php#L419) | 419 | private | `PatientTest $patientTest` | void |

### `app/Services/TestResultService.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| TestResultService | [`__construct()`](../../../TCV-Backend/app/Services/TestResultService.php#L16) | 16 | public | `ColorVisionDiagnosisService $diagnosisService` | — |
| TestResultService | [`generateTestResult()`](../../../TCV-Backend/app/Services/TestResultService.php#L26) | 26 | public | `PatientTest $patientTest` | array |

### `app/Services/TestSectionProgressionService.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| TestSectionProgressionService | [`__construct()`](../../../TCV-Backend/app/Services/TestSectionProgressionService.php#L13) | 13 | public | `ColorVisionDiagnosisService $diagnosisService` | — |
| TestSectionProgressionService | [`evaluateAndSkipConditionedSections()`](../../../TCV-Backend/app/Services/TestSectionProgressionService.php#L24) | 24 | public | `string $uniqueTestId`, `int $completedSectionId` | void |
| TestSectionProgressionService | [`maybeSkipSection()`](../../../TCV-Backend/app/Services/TestSectionProgressionService.php#L56) | 56 | private | `string $uniqueTestId`, `TestSection $section`, `Collection $allSections` | void |

### `app/Services/TestSectionTerminationService.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| TestSectionTerminationService | [`shouldTerminateSection()`](../../../TCV-Backend/app/Services/TestSectionTerminationService.php#L15) | 15 | public | `string $uniqueTestId`, `int $sectionId` | bool |
| TestSectionTerminationService | [`checkConsecutiveWrong()`](../../../TCV-Backend/app/Services/TestSectionTerminationService.php#L37) | 37 | private | `string $uniqueTestId`, `int $sectionId`, `int $threshold` | bool |
| TestSectionTerminationService | [`checkTotalWrong()`](../../../TCV-Backend/app/Services/TestSectionTerminationService.php#L61) | 61 | private | `string $uniqueTestId`, `int $sectionId`, `int $threshold` | bool |
| TestSectionTerminationService | [`terminateSection()`](../../../TCV-Backend/app/Services/TestSectionTerminationService.php#L76) | 76 | public | `string $uniqueTestId`, `int $sectionId` | int |

### `app/Services/TestService.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| TestService | [`generateTestReport()`](../../../TCV-Backend/app/Services/TestService.php#L26) | 26 | public | `$testID`, `$patientID`, `$uniqueTestID` | — |
| TestService | [`generateTestResultPDF()`](../../../TCV-Backend/app/Services/TestService.php#L78) | 78 | public | `$report` | — |
| TestService | [`updateTestAnswer()`](../../../TCV-Backend/app/Services/TestService.php#L90) | 90 | public | `$validated` | — |
| TestService | [`getRemainingPlates()`](../../../TCV-Backend/app/Services/TestService.php#L121) | 121 | public | `$validated` | — |
| TestService | [`getNextSection()`](../../../TCV-Backend/app/Services/TestService.php#L139) | 139 | public | `$testID`, `$sectionID` | — |
| TestService | [`markTestAsCompleted()`](../../../TCV-Backend/app/Services/TestService.php#L153) | 153 | public | `$validated` | — |
| TestService | [`cloneTest()`](../../../TCV-Backend/app/Services/TestService.php#L176) | 176 | public | `$testID` | — |
| TestService | [`generateAndSendTestLink()`](../../../TCV-Backend/app/Services/TestService.php#L222) | 222 | public | `$patient`, `$test`, `$uniqueTestID` | — |
| TestService | [`getLastAnsweredPlate()`](../../../TCV-Backend/app/Services/TestService.php#L247) | 247 | public | `$uniqueTestID` | — |
| TestService | [`completeTest()`](../../../TCV-Backend/app/Services/TestService.php#L256) | 256 | public | `$validated`, `$request` | — |

### `app/Services/TurnstileService.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| TurnstileService | [`verify()`](../../../TCV-Backend/app/Services/TurnstileService.php#L17) | 17 | public static | `string $token`, `?string $ip = null` | array |
| TurnstileService | [`isValid()`](../../../TCV-Backend/app/Services/TurnstileService.php#L82) | 82 | public static | `string $token`, `?string $ip = null` | bool |
| TurnstileService | [`validationRule()`](../../../TCV-Backend/app/Services/TurnstileService.php#L93) | 93 | public static | — | string |

### `app/Support/HttpStatus.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|

### `app/Support/TestConstants.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|

### `app/Traits/Searchable.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| Searchable | [`scopeSearch()`](../../../TCV-Backend/app/Traits/Searchable.php#L12) | 12 | public | `$query`, `$search`, `array $fields = []`, `array $relations = []` | — |
| Searchable | [`querySearch()`](../../../TCV-Backend/app/Traits/Searchable.php#L24) | 24 | public | `$query`, `$search`, `array $fields = []`, `array $relations = []` | — |
| Searchable | [`applySearchLogic()`](../../../TCV-Backend/app/Traits/Searchable.php#L36) | 36 | private | `$query`, `$search`, `array $fields = []`, `array $relations = []` | — |

### `database/factories/UserFactory.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| UserFactory | [`definition()`](../../../TCV-Backend/database/factories/UserFactory.php#L24) | 24 | public | — | array |
| UserFactory | [`unverified()`](../../../TCV-Backend/database/factories/UserFactory.php#L48) | 48 | public | — | static |

### `database/seeders/AdminSettingsSeeder.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| AdminSettingsSeeder | [`run()`](../../../TCV-Backend/database/seeders/AdminSettingsSeeder.php#L13) | 13 | public | — | void |

### `database/seeders/AllowedTestsTableSeeder.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| AllowedTestsTableSeeder | [`run()`](../../../TCV-Backend/database/seeders/AllowedTestsTableSeeder.php#L10) | 10 | public | — | void |

### `database/seeders/BaselineTestSectionSeeder.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| BaselineTestSectionSeeder | [`run()`](../../../TCV-Backend/database/seeders/BaselineTestSectionSeeder.php#L12) | 12 | public | — | void |
| BaselineTestSectionSeeder | [`generalPlates()`](../../../TCV-Backend/database/seeders/BaselineTestSectionSeeder.php#L108) | 108 | private | — | array |
| BaselineTestSectionSeeder | [`tritanPlates()`](../../../TCV-Backend/database/seeders/BaselineTestSectionSeeder.php#L139) | 139 | private | — | array |

### `database/seeders/CompliancesTableSeeder.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| CompliancesTableSeeder | [`run()`](../../../TCV-Backend/database/seeders/CompliancesTableSeeder.php#L10) | 10 | public | — | void |

### `database/seeders/DatabaseSeeder.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| DatabaseSeeder | [`run()`](../../../TCV-Backend/database/seeders/DatabaseSeeder.php#L13) | 13 | public | — | void |

### `database/seeders/EmailTemplateSeeder.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| EmailTemplateSeeder | [`run()`](../../../TCV-Backend/database/seeders/EmailTemplateSeeder.php#L10) | 10 | public | — | — |

### `database/seeders/FAAColorVisionTestSectionSeeder.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| FAAColorVisionTestSectionSeeder | [`run()`](../../../TCV-Backend/database/seeders/FAAColorVisionTestSectionSeeder.php#L12) | 12 | public | — | void |
| FAAColorVisionTestSectionSeeder | [`generalPlates()`](../../../TCV-Backend/database/seeders/FAAColorVisionTestSectionSeeder.php#L115) | 115 | private | — | array |
| FAAColorVisionTestSectionSeeder | [`tritanPlates()`](../../../TCV-Backend/database/seeders/FAAColorVisionTestSectionSeeder.php#L146) | 146 | private | — | array |
| FAAColorVisionTestSectionSeeder | [`protanPlates()`](../../../TCV-Backend/database/seeders/FAAColorVisionTestSectionSeeder.php#L164) | 164 | private | — | array |
| FAAColorVisionTestSectionSeeder | [`deutanPlates()`](../../../TCV-Backend/database/seeders/FAAColorVisionTestSectionSeeder.php#L202) | 202 | private | — | array |

### `database/seeders/FourteenPlateTritanTestWithTestSectionSeeder.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| FourteenPlateTritanTestWithTestSectionSeeder | [`run()`](../../../TCV-Backend/database/seeders/FourteenPlateTritanTestWithTestSectionSeeder.php#L12) | 12 | public | — | void |

### `database/seeders/OlderChildrenTestWithTestSectionSeeder.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| OlderChildrenTestWithTestSectionSeeder | [`run()`](../../../TCV-Backend/database/seeders/OlderChildrenTestWithTestSectionSeeder.php#L12) | 12 | public | — | void |
| OlderChildrenTestWithTestSectionSeeder | [`generalPlates()`](../../../TCV-Backend/database/seeders/OlderChildrenTestWithTestSectionSeeder.php#L118) | 118 | private | — | array |
| OlderChildrenTestWithTestSectionSeeder | [`tritanPlates()`](../../../TCV-Backend/database/seeders/OlderChildrenTestWithTestSectionSeeder.php#L149) | 149 | private | — | array |
| OlderChildrenTestWithTestSectionSeeder | [`protanPlates()`](../../../TCV-Backend/database/seeders/OlderChildrenTestWithTestSectionSeeder.php#L167) | 167 | private | — | array |
| OlderChildrenTestWithTestSectionSeeder | [`deutanPlates()`](../../../TCV-Backend/database/seeders/OlderChildrenTestWithTestSectionSeeder.php#L205) | 205 | private | — | array |

### `database/seeders/OrganizationConfigSeederUpdated.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| OrganizationConfigSeederUpdated | [`run()`](../../../TCV-Backend/database/seeders/OrganizationConfigSeederUpdated.php#L18) | 18 | public | — | void |
| OrganizationConfigSeederUpdated | [`getOrganizationIdMapping()`](../../../TCV-Backend/database/seeders/OrganizationConfigSeederUpdated.php#L36) | 36 | private | — | array |
| OrganizationConfigSeederUpdated | [`getConfigurations()`](../../../TCV-Backend/database/seeders/OrganizationConfigSeederUpdated.php#L63) | 63 | private | `array $idMapping` | array |

### `database/seeders/OrganizationSettingsOptionSeeder.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| OrganizationSettingsOptionSeeder | [`run()`](../../../TCV-Backend/database/seeders/OrganizationSettingsOptionSeeder.php#L10) | 10 | public | — | void |

### `database/seeders/OrganizationTypesTableSeeder.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| OrganizationTypesTableSeeder | [`run()`](../../../TCV-Backend/database/seeders/OrganizationTypesTableSeeder.php#L14) | 14 | public | — | void |

### `database/seeders/OrganizationsSeeder.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| OrganizationsSeeder | [`run()`](../../../TCV-Backend/database/seeders/OrganizationsSeeder.php#L20) | 20 | public | — | void |
| OrganizationsSeeder | [`getOrganizationData()`](../../../TCV-Backend/database/seeders/OrganizationsSeeder.php#L103) | 103 | private | — | array |

### `database/seeders/PediatricCVTMETestWithTestSectionSeeder.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| PediatricCVTMETestWithTestSectionSeeder | [`run()`](../../../TCV-Backend/database/seeders/PediatricCVTMETestWithTestSectionSeeder.php#L12) | 12 | public | — | void |

### `database/seeders/PriceDetailSeeder.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| PriceDetailSeeder | [`run()`](../../../TCV-Backend/database/seeders/PriceDetailSeeder.php#L15) | 15 | public | — | void |

### `database/seeders/PrivilegesTableSeeder.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| PrivilegesTableSeeder | [`run()`](../../../TCV-Backend/database/seeders/PrivilegesTableSeeder.php#L10) | 10 | public | — | void |

### `database/seeders/ProlificIdSeeder.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| ProlificIdSeeder | [`run()`](../../../TCV-Backend/database/seeders/ProlificIdSeeder.php#L16) | 16 | public | — | void |
| ProlificIdSeeder | [`getProlificIds()`](../../../TCV-Backend/database/seeders/ProlificIdSeeder.php#L50) | 50 | private | — | array |

### `database/seeders/SeniorDiagnosticsTestWithTestSectionSeeder.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| SeniorDiagnosticsTestWithTestSectionSeeder | [`run()`](../../../TCV-Backend/database/seeders/SeniorDiagnosticsTestWithTestSectionSeeder.php#L12) | 12 | public | — | void |
| SeniorDiagnosticsTestWithTestSectionSeeder | [`generalPlates()`](../../../TCV-Backend/database/seeders/SeniorDiagnosticsTestWithTestSectionSeeder.php#L103) | 103 | private | — | array |
| SeniorDiagnosticsTestWithTestSectionSeeder | [`deutanPlates()`](../../../TCV-Backend/database/seeders/SeniorDiagnosticsTestWithTestSectionSeeder.php#L137) | 137 | private | — | array |
| SeniorDiagnosticsTestWithTestSectionSeeder | [`tritanPlates()`](../../../TCV-Backend/database/seeders/SeniorDiagnosticsTestWithTestSectionSeeder.php#L176) | 176 | private | — | array |
| SeniorDiagnosticsTestWithTestSectionSeeder | [`protanPlates()`](../../../TCV-Backend/database/seeders/SeniorDiagnosticsTestWithTestSectionSeeder.php#L195) | 195 | private | — | array |

### `database/seeders/TestWithTestSectionSeeder.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| TestWithTestSectionSeeder | [`run()`](../../../TCV-Backend/database/seeders/TestWithTestSectionSeeder.php#L12) | 12 | public | — | void |
| TestWithTestSectionSeeder | [`generalPlates()`](../../../TCV-Backend/database/seeders/TestWithTestSectionSeeder.php#L118) | 118 | private | — | array |
| TestWithTestSectionSeeder | [`tritanPlates()`](../../../TCV-Backend/database/seeders/TestWithTestSectionSeeder.php#L149) | 149 | private | — | array |
| TestWithTestSectionSeeder | [`protanPlates()`](../../../TCV-Backend/database/seeders/TestWithTestSectionSeeder.php#L167) | 167 | private | — | array |
| TestWithTestSectionSeeder | [`deutanPlates()`](../../../TCV-Backend/database/seeders/TestWithTestSectionSeeder.php#L205) | 205 | private | — | array |

### `database/seeders/TwelvePlateTritanTestWithTestSectionSeeder.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| TwelvePlateTritanTestWithTestSectionSeeder | [`run()`](../../../TCV-Backend/database/seeders/TwelvePlateTritanTestWithTestSectionSeeder.php#L12) | 12 | public | — | void |

### `database/seeders/WorldSeeder.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| WorldSeeder | [`run()`](../../../TCV-Backend/database/seeders/WorldSeeder.php#L10) | 10 | public | — | — |

---

_Generated from source by `tools/extract.php` + `tools/extract-clients.php` + `tools/render.php` on 2026-08-28. Do not hand-edit — re-run the generator._
