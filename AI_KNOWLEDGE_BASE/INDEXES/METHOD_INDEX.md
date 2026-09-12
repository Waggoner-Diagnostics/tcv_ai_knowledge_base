# Method Index

**860 methods across 207 classes.**

Grouped by file; jump straight to the line. Use this instead of opening a controller to find a
method — several controllers here run 400–900 lines.


### `app/Console/Commands/BackfillStripeSourceApp.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| BackfillStripeSourceApp | [`handle()`](../../../TCV-Backend/app/Console/Commands/BackfillStripeSourceApp.php#L37) | 37 | public | `StripeService $stripeService` | int |

### `app/Console/Commands/CheckEmailTemplatePlaceholders.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| CheckEmailTemplatePlaceholders | [`handle()`](../../../TCV-Backend/app/Console/Commands/CheckEmailTemplatePlaceholders.php#L24) | 24 | public | — | int |
| CheckEmailTemplatePlaceholders | [`report()`](../../../TCV-Backend/app/Console/Commands/CheckEmailTemplatePlaceholders.php#L64) | 64 | private | `string $table`, `int $id`, `string $type`, `string $subject`, `string $body`, `string $context` | int |

### `app/Console/Commands/SendPendingInvitations.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| SendPendingInvitations | [`handle()`](../../../TCV-Backend/app/Console/Commands/SendPendingInvitations.php#L25) | 25 | public | `TestInvitationMailer $mailer` | int |
| SendPendingInvitations | [`reclaimAbandonedClaims()`](../../../TCV-Backend/app/Console/Commands/SendPendingInvitations.php#L138) | 138 | private | `int $minutes` | int |

### `app/Console/Commands/SettleNegativeCreditBalances.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| SettleNegativeCreditBalances | [`handle()`](../../../TCV-Backend/app/Console/Commands/SettleNegativeCreditBalances.php#L38) | 38 | public | — | int |

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
| Handler | [`render()`](../../../TCV-Backend/app/Exceptions/Handler.php#L16) | 16 | public | `$request`, `Throwable $exception` | — |

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
| ApiResponse | [`success()`](../../../TCV-Backend/app/Helpers/ApiResponse.php#L9) | 9 | public static | `int $statusCode = 200`, `string $messageKey`, `$data = null`, `array $meta = []`, `array $replace = []` | JsonResponse |
| ApiResponse | [`error()`](../../../TCV-Backend/app/Helpers/ApiResponse.php#L29) | 29 | public static | `int $statusCode = 400`, `string $messageKey`, `$errors = null`, `array $replace = []` | JsonResponse |

### `app/Helpers/TestHelper.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| TestHelper | [`getEyeLabel()`](../../../TCV-Backend/app/Helpers/TestHelper.php#L12) | 12 | public static | `string $eyeCode` | string |
| TestHelper | [`getEyeInstruction()`](../../../TCV-Backend/app/Helpers/TestHelper.php#L26) | 26 | public static | `string $eyeCode` | string |
| TestHelper | [`extractEyeFromSectionInstruction()`](../../../TCV-Backend/app/Helpers/TestHelper.php#L39) | 39 | public static | `?string $instruction` | ?string |

### `app/Http/Controllers/AuditLogController.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| AuditLogController | [`index()`](../../../TCV-Backend/app/Http/Controllers/AuditLogController.php#L17) | 17 | public | `AuditLogIndexRequest $request` | — |
| AuditLogController | [`people()`](../../../TCV-Backend/app/Http/Controllers/AuditLogController.php#L125) | 125 | public | `Request $request` | — |
| AuditLogController | [`show()`](../../../TCV-Backend/app/Http/Controllers/AuditLogController.php#L180) | 180 | public | `Request $request`, `int $id` | — |

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
| AuthController | [`__construct()`](../../../TCV-Backend/app/Http/Controllers/AuthController.php#L53) | 53 | public | `StripeService $stripeService`, `AuditService $auditService` | — |
| AuthController | [`login()`](../../../TCV-Backend/app/Http/Controllers/AuthController.php#L59) | 59 | public | `Request $request` | — |
| AuthController | [`createStripeCustomer()`](../../../TCV-Backend/app/Http/Controllers/AuthController.php#L237) | 237 | private | `User $user` | — |
| AuthController | [`register()`](../../../TCV-Backend/app/Http/Controllers/AuthController.php#L246) | 246 | public | `UserRequest $request` | — |
| AuthController | [`sendResetLinkEmail()`](../../../TCV-Backend/app/Http/Controllers/AuthController.php#L318) | 318 | public | `Request $request` | — |
| AuthController | [`setOrResetPassword()`](../../../TCV-Backend/app/Http/Controllers/AuthController.php#L361) | 361 | public | `Request $request` | — |
| AuthController | [`verifySetupToken()`](../../../TCV-Backend/app/Http/Controllers/AuthController.php#L466) | 466 | public | `Request $request` | — |
| AuthController | [`verifyEmail()`](../../../TCV-Backend/app/Http/Controllers/AuthController.php#L489) | 489 | public | `Request $request` | — |
| AuthController | [`logout()`](../../../TCV-Backend/app/Http/Controllers/AuthController.php#L524) | 524 | public | `Request $request` | — |
| AuthController | [`impersonationTargetLabel()`](../../../TCV-Backend/app/Http/Controllers/AuthController.php#L553) | 553 | private | `User $target` | string |
| AuthController | [`impersonateUser()`](../../../TCV-Backend/app/Http/Controllers/AuthController.php#L562) | 562 | public | `Request $request`, `$id` | — |
| AuthController | [`stopImpersonation()`](../../../TCV-Backend/app/Http/Controllers/AuthController.php#L601) | 601 | public | `Request $request`, `$id` | — |
| AuthController | [`isTokenValid()`](../../../TCV-Backend/app/Http/Controllers/AuthController.php#L645) | 645 | public static | `Request $request` | — |
| AuthController | [`verifyPassword()`](../../../TCV-Backend/app/Http/Controllers/AuthController.php#L704) | 704 | public | `Request $request` | — |
| AuthController | [`changePassword()`](../../../TCV-Backend/app/Http/Controllers/AuthController.php#L744) | 744 | public | `Request $request` | — |
| AuthController | [`sendVerificationEmailForUser()`](../../../TCV-Backend/app/Http/Controllers/AuthController.php#L782) | 782 | private | `User $user` | — |
| AuthController | [`verifyEmailByToken()`](../../../TCV-Backend/app/Http/Controllers/AuthController.php#L908) | 908 | public | `Request $request` | — |
| AuthController | [`resendVerificationByToken()`](../../../TCV-Backend/app/Http/Controllers/AuthController.php#L982) | 982 | public | `Request $request` | — |
| AuthController | [`resendEmailVerificationLink()`](../../../TCV-Backend/app/Http/Controllers/AuthController.php#L1013) | 1013 | public | `Request $request` | — |

### `app/Http/Controllers/Concerns/BuildsAuditDiffs.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| BuildsAuditDiffs | [`auditSnapshot()`](../../../TCV-Backend/app/Http/Controllers/Concerns/BuildsAuditDiffs.php#L26) | 26 | protected | `$model`, `array $fields` | array |
| BuildsAuditDiffs | [`auditChanges()`](../../../TCV-Backend/app/Http/Controllers/Concerns/BuildsAuditDiffs.php#L43) | 43 | protected | `array $before`, `array $changes`, `array $fields` | array |
| BuildsAuditDiffs | [`auditDetails()`](../../../TCV-Backend/app/Http/Controllers/Concerns/BuildsAuditDiffs.php#L64) | 64 | protected | `$model`, `array $fields` | array |

### `app/Http/Controllers/ContactController.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| ContactController | [`__construct()`](../../../TCV-Backend/app/Http/Controllers/ContactController.php#L20) | 20 | public | `HubSpotService $hubSpotService`, `AuditService $auditService` | — |
| ContactController | [`submit()`](../../../TCV-Backend/app/Http/Controllers/ContactController.php#L26) | 26 | public | `ContactFormRequest $request` | — |

### `app/Http/Controllers/Controller.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|

### `app/Http/Controllers/CreditsController.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| CreditsController | [`__construct()`](../../../TCV-Backend/app/Http/Controllers/CreditsController.php#L26) | 26 | public | `AuditService $auditService` | — |
| CreditsController | [`index()`](../../../TCV-Backend/app/Http/Controllers/CreditsController.php#L34) | 34 | public | `Request $request` | — |
| CreditsController | [`store()`](../../../TCV-Backend/app/Http/Controllers/CreditsController.php#L105) | 105 | public | `CreditsAddRequest $request` | — |
| CreditsController | [`show()`](../../../TCV-Backend/app/Http/Controllers/CreditsController.php#L143) | 143 | public | `$userId` | — |
| CreditsController | [`destroy()`](../../../TCV-Backend/app/Http/Controllers/CreditsController.php#L157) | 157 | public | `Request $request`, `$id` | — |
| CreditsController | [`checkDiscountCodeValidity()`](../../../TCV-Backend/app/Http/Controllers/CreditsController.php#L222) | 222 | public | `Request $request`, `string $coupon_code` | — |
| CreditsController | [`revokeCredit()`](../../../TCV-Backend/app/Http/Controllers/CreditsController.php#L263) | 263 | public | `string $identifier` | JsonResponse |

### `app/Http/Controllers/DiscountCodeController.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| DiscountCodeController | [`__construct()`](../../../TCV-Backend/app/Http/Controllers/DiscountCodeController.php#L31) | 31 | public | `DiscountCodeService $service`, `AuditService $auditService` | — |
| DiscountCodeController | [`discountRelationDetails()`](../../../TCV-Backend/app/Http/Controllers/DiscountCodeController.php#L38) | 38 | private | `DiscountCode $discount` | array |
| DiscountCodeController | [`discountRestrictedUsers()`](../../../TCV-Backend/app/Http/Controllers/DiscountCodeController.php#L50) | 50 | private | `DiscountCode $discount` | array |
| DiscountCodeController | [`discountPackageList()`](../../../TCV-Backend/app/Http/Controllers/DiscountCodeController.php#L61) | 61 | private | `DiscountCode $discount` | array |
| DiscountCodeController | [`index()`](../../../TCV-Backend/app/Http/Controllers/DiscountCodeController.php#L73) | 73 | public | `Request $request` | JsonResponse |
| DiscountCodeController | [`store()`](../../../TCV-Backend/app/Http/Controllers/DiscountCodeController.php#L118) | 118 | public | `StoreDiscountCodeRequest $request` | JsonResponse |
| DiscountCodeController | [`show()`](../../../TCV-Backend/app/Http/Controllers/DiscountCodeController.php#L169) | 169 | public | `int $id` | JsonResponse |
| DiscountCodeController | [`update()`](../../../TCV-Backend/app/Http/Controllers/DiscountCodeController.php#L177) | 177 | public | `UpdateDiscountCodeRequest $request`, `int $id` | JsonResponse |
| DiscountCodeController | [`destroy()`](../../../TCV-Backend/app/Http/Controllers/DiscountCodeController.php#L239) | 239 | public | `Request $request`, `int $id` | JsonResponse |
| DiscountCodeController | [`toggle()`](../../../TCV-Backend/app/Http/Controllers/DiscountCodeController.php#L268) | 268 | public | `Request $request`, `int $id` | JsonResponse |
| DiscountCodeController | [`validateCode()`](../../../TCV-Backend/app/Http/Controllers/DiscountCodeController.php#L296) | 296 | public | `ValidateDiscountCodeRequest $request` | JsonResponse |
| DiscountCodeController | [`formOptions()`](../../../TCV-Backend/app/Http/Controllers/DiscountCodeController.php#L321) | 321 | public | — | JsonResponse |
| DiscountCodeController | [`stats()`](../../../TCV-Backend/app/Http/Controllers/DiscountCodeController.php#L342) | 342 | public | — | JsonResponse |
| DiscountCodeController | [`codeAvailable()`](../../../TCV-Backend/app/Http/Controllers/DiscountCodeController.php#L354) | 354 | public | `Request $request` | JsonResponse |
| DiscountCodeController | [`formatDiscount()`](../../../TCV-Backend/app/Http/Controllers/DiscountCodeController.php#L373) | 373 | private | `DiscountCode $discount` | array |

### `app/Http/Controllers/DistributorController.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| DistributorController | [`__construct()`](../../../TCV-Backend/app/Http/Controllers/DistributorController.php#L14) | 14 | public | `HubSpotService $hubSpotService`, `AuditService $auditService` | — |
| DistributorController | [`submit()`](../../../TCV-Backend/app/Http/Controllers/DistributorController.php#L19) | 19 | public | `DistributorEnquiryFormRequest $request` | — |

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
| OrganizationController | [`__construct()`](../../../TCV-Backend/app/Http/Controllers/OrganizationController.php#L59) | 59 | public | `LmsLaunchService $launchService`, `LmsProviderRegistry $providerRegistry`, `AuditService $auditService` | — |
| OrganizationController | [`organizationExtraDetails()`](../../../TCV-Backend/app/Http/Controllers/OrganizationController.php#L72) | 72 | private | `Organization $organization` | array |
| OrganizationController | [`orgConfigOptionGroups()`](../../../TCV-Backend/app/Http/Controllers/OrganizationController.php#L94) | 94 | private | `Organization $organization` | array |
| OrganizationController | [`organizationCreateDetails()`](../../../TCV-Backend/app/Http/Controllers/OrganizationController.php#L125) | 125 | private | `Organization $organization`, `User $user` | array |
| OrganizationController | [`orgRelationSnapshot()`](../../../TCV-Backend/app/Http/Controllers/OrganizationController.php#L159) | 159 | private | `Organization $organization` | array |
| OrganizationController | [`index()`](../../../TCV-Backend/app/Http/Controllers/OrganizationController.php#L173) | 173 | public | `Request $request` | — |
| OrganizationController | [`store()`](../../../TCV-Backend/app/Http/Controllers/OrganizationController.php#L302) | 302 | public | `OrganizationRequest $request` | — |
| OrganizationController | [`show()`](../../../TCV-Backend/app/Http/Controllers/OrganizationController.php#L386) | 386 | public | `$id` | — |
| OrganizationController | [`update()`](../../../TCV-Backend/app/Http/Controllers/OrganizationController.php#L400) | 400 | public | `OrganizationRequest $request`, `$id` | — |
| OrganizationController | [`uploadLogo()`](../../../TCV-Backend/app/Http/Controllers/OrganizationController.php#L557) | 557 | public | `Request $request`, `$id` | — |
| OrganizationController | [`destroy()`](../../../TCV-Backend/app/Http/Controllers/OrganizationController.php#L584) | 584 | public | `Request $request`, `$id` | — |
| OrganizationController | [`getPatientForm()`](../../../TCV-Backend/app/Http/Controllers/OrganizationController.php#L638) | 638 | public | `Request $request` | — |
| OrganizationController | [`verifySignature()`](../../../TCV-Backend/app/Http/Controllers/OrganizationController.php#L698) | 698 | public | `Request $request` | — |
| OrganizationController | [`generateFieldRules()`](../../../TCV-Backend/app/Http/Controllers/OrganizationController.php#L827) | 827 | private | `$organizationId` | — |
| OrganizationController | [`getDefaultTests()`](../../../TCV-Backend/app/Http/Controllers/OrganizationController.php#L903) | 903 | public | `Request $request` | — |
| OrganizationController | [`getOrganizationPrivileges()`](../../../TCV-Backend/app/Http/Controllers/OrganizationController.php#L951) | 951 | public | `Request $request` | — |
| OrganizationController | [`getOrganizationRedirectUrl()`](../../../TCV-Backend/app/Http/Controllers/OrganizationController.php#L990) | 990 | public | `Request $request` | — |
| OrganizationController | [`addCreditsToOrganizations()`](../../../TCV-Backend/app/Http/Controllers/OrganizationController.php#L1024) | 1024 | private | `$orgCollection` | Collection |
| OrganizationController | [`createOrganizationConfig()`](../../../TCV-Backend/app/Http/Controllers/OrganizationController.php#L1070) | 1070 | private | `$organizationId`, `?array $fields = null`, `?string $redirectUrl = null` | — |

### `app/Http/Controllers/OrganizationPatientController.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| OrganizationPatientController | [`__construct()`](../../../TCV-Backend/app/Http/Controllers/OrganizationPatientController.php#L19) | 19 | public | `LmsLaunchService $launchService` | — |
| OrganizationPatientController | [`verifyTurnstileToken()`](../../../TCV-Backend/app/Http/Controllers/OrganizationPatientController.php#L27) | 27 | private | `$request` | — |
| OrganizationPatientController | [`storeProlificPatient()`](../../../TCV-Backend/app/Http/Controllers/OrganizationPatientController.php#L57) | 57 | public | `Request $request` | — |
| OrganizationPatientController | [`storeDefaultPatient()`](../../../TCV-Backend/app/Http/Controllers/OrganizationPatientController.php#L189) | 189 | public | `Request $request` | — |

### `app/Http/Controllers/PasswordController.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| PasswordController | [`__construct()`](../../../TCV-Backend/app/Http/Controllers/PasswordController.php#L18) | 18 | public | `AuditService $auditService` | — |
| PasswordController | [`update()`](../../../TCV-Backend/app/Http/Controllers/PasswordController.php#L33) | 33 | public | `ChangePasswordRequest $request` | — |

### `app/Http/Controllers/PatientController.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| PatientController | [`__construct()`](../../../TCV-Backend/app/Http/Controllers/PatientController.php#L46) | 46 | public | `TestService $testService`, `PatientTestTransformer $testTransformer`, `AuditService $auditService` | — |
| PatientController | [`index()`](../../../TCV-Backend/app/Http/Controllers/PatientController.php#L56) | 56 | public | — | — |
| PatientController | [`store()`](../../../TCV-Backend/app/Http/Controllers/PatientController.php#L88) | 88 | public | `PatientAddRequest $request` | — |
| PatientController | [`show()`](../../../TCV-Backend/app/Http/Controllers/PatientController.php#L146) | 146 | public | `Request $request`, `$id` | — |
| PatientController | [`update()`](../../../TCV-Backend/app/Http/Controllers/PatientController.php#L167) | 167 | public | `PatientUpdateRequest $request`, `$id` | — |
| PatientController | [`destroy()`](../../../TCV-Backend/app/Http/Controllers/PatientController.php#L216) | 216 | public | `Request $request`, `$id` | — |
| PatientController | [`callerOwnsPatient()`](../../../TCV-Backend/app/Http/Controllers/PatientController.php#L255) | 255 | private | `Request $request`, `Patient $patient` | bool |
| PatientController | [`resendTestLink()`](../../../TCV-Backend/app/Http/Controllers/PatientController.php#L291) | 291 | public | `Request $request` | — |
| PatientController | [`getPatientTests()`](../../../TCV-Backend/app/Http/Controllers/PatientController.php#L352) | 352 | public | `$id` | JsonResponse |
| PatientController | [`storeOrganizationPatient()`](../../../TCV-Backend/app/Http/Controllers/PatientController.php#L407) | 407 | public | `Request $request` | — |

### `app/Http/Controllers/PaymentController.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| PaymentController | [`__construct()`](../../../TCV-Backend/app/Http/Controllers/PaymentController.php#L16) | 16 | public | `DiscountCodeService $discountCodeService`, `AuditService $auditService` | — |
| PaymentController | [`getProviders()`](../../../TCV-Backend/app/Http/Controllers/PaymentController.php#L18) | 18 | public | — | — |
| PaymentController | [`createSetupIntent()`](../../../TCV-Backend/app/Http/Controllers/PaymentController.php#L34) | 34 | public | `Request $request` | — |
| PaymentController | [`initializePayment()`](../../../TCV-Backend/app/Http/Controllers/PaymentController.php#L57) | 57 | public | `Request $request` | — |
| PaymentController | [`confirmPayment()`](../../../TCV-Backend/app/Http/Controllers/PaymentController.php#L95) | 95 | public | `Request $request` | — |
| PaymentController | [`handleWebhook()`](../../../TCV-Backend/app/Http/Controllers/PaymentController.php#L199) | 199 | public | `Request $request`, `string $provider` | — |
| PaymentController | [`getTransactions()`](../../../TCV-Backend/app/Http/Controllers/PaymentController.php#L220) | 220 | public | — | — |
| PaymentController | [`getCreditHistory()`](../../../TCV-Backend/app/Http/Controllers/PaymentController.php#L259) | 259 | public | — | JsonResponse |

### `app/Http/Controllers/PriceDetailController.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| PriceDetailController | [`__construct()`](../../../TCV-Backend/app/Http/Controllers/PriceDetailController.php#L19) | 19 | public | `AuditService $auditService` | — |
| PriceDetailController | [`validateData()`](../../../TCV-Backend/app/Http/Controllers/PriceDetailController.php#L24) | 24 | private | `array $data` | — |
| PriceDetailController | [`getAllDetails()`](../../../TCV-Backend/app/Http/Controllers/PriceDetailController.php#L33) | 33 | private | — | — |
| PriceDetailController | [`index()`](../../../TCV-Backend/app/Http/Controllers/PriceDetailController.php#L47) | 47 | public | — | JsonResponse |
| PriceDetailController | [`store()`](../../../TCV-Backend/app/Http/Controllers/PriceDetailController.php#L55) | 55 | public | `Request $request` | JsonResponse |
| PriceDetailController | [`update()`](../../../TCV-Backend/app/Http/Controllers/PriceDetailController.php#L104) | 104 | public | `Request $request`, `$id` | JsonResponse |
| PriceDetailController | [`destroy()`](../../../TCV-Backend/app/Http/Controllers/PriceDetailController.php#L186) | 186 | public | `Request $request`, `$id` | JsonResponse |
| PriceDetailController | [`auditDetails()`](../../../TCV-Backend/app/Http/Controllers/PriceDetailController.php#L224) | 224 | private | `PriceDetail $detail` | array |

### `app/Http/Controllers/ProfileController.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| ProfileController | [`__construct()`](../../../TCV-Backend/app/Http/Controllers/ProfileController.php#L32) | 32 | public | `AuditService $auditService` | — |
| ProfileController | [`show()`](../../../TCV-Backend/app/Http/Controllers/ProfileController.php#L42) | 42 | public | — | — |
| ProfileController | [`update()`](../../../TCV-Backend/app/Http/Controllers/ProfileController.php#L75) | 75 | public | `UpdateProfileRequest $request` | — |

### `app/Http/Controllers/Qa/QaAutomationController.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| QaAutomationController | [`enabled()`](../../../TCV-Backend/app/Http/Controllers/Qa/QaAutomationController.php#L44) | 44 | public static | — | bool |
| QaAutomationController | [`__construct()`](../../../TCV-Backend/app/Http/Controllers/Qa/QaAutomationController.php#L56) | 56 | public | — | — |
| QaAutomationController | [`userState()`](../../../TCV-Backend/app/Http/Controllers/Qa/QaAutomationController.php#L78) | 78 | public | `Request $request` | — |
| QaAutomationController | [`passwordToken()`](../../../TCV-Backend/app/Http/Controllers/Qa/QaAutomationController.php#L94) | 94 | public | `Request $request` | — |
| QaAutomationController | [`setPassword()`](../../../TCV-Backend/app/Http/Controllers/Qa/QaAutomationController.php#L161) | 161 | public | `Request $request` | — |
| QaAutomationController | [`emailToken()`](../../../TCV-Backend/app/Http/Controllers/Qa/QaAutomationController.php#L210) | 210 | public | `Request $request` | — |
| QaAutomationController | [`verifyEmail()`](../../../TCV-Backend/app/Http/Controllers/Qa/QaAutomationController.php#L268) | 268 | public | `Request $request` | — |
| QaAutomationController | [`resetState()`](../../../TCV-Backend/app/Http/Controllers/Qa/QaAutomationController.php#L296) | 296 | public | `Request $request` | — |
| QaAutomationController | [`resolveUser()`](../../../TCV-Backend/app/Http/Controllers/Qa/QaAutomationController.php#L347) | 347 | private | `Request $request` | User |
| QaAutomationController | [`stateOf()`](../../../TCV-Backend/app/Http/Controllers/Qa/QaAutomationController.php#L368) | 368 | private | `User $user` | array |

### `app/Http/Controllers/ReportController.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| ReportController | [`__construct()`](../../../TCV-Backend/app/Http/Controllers/ReportController.php#L27) | 27 | public | `UserTestsReportService $userReportService`, `DiscountCodeReportService $discountCodeReportService`, `AuditService $auditService` | — |
| ReportController | [`getPatientsHavingTests()`](../../../TCV-Backend/app/Http/Controllers/ReportController.php#L34) | 34 | public | `Request $request` | — |
| ReportController | [`userTestsReport()`](../../../TCV-Backend/app/Http/Controllers/ReportController.php#L56) | 56 | public | `Request $request` | — |
| ReportController | [`logUserTestsExported()`](../../../TCV-Backend/app/Http/Controllers/ReportController.php#L153) | 153 | private | `Request $request`, `array $filters`, `string $format`, `string $scope`, `?int $patientId`, `int $recordCount` | void |
| ReportController | [`appliedDateFilter()`](../../../TCV-Backend/app/Http/Controllers/ReportController.php#L176) | 176 | private | `?string $from`, `?string $to` | string |
| ReportController | [`downloadDetailExcel()`](../../../TCV-Backend/app/Http/Controllers/ReportController.php#L185) | 185 | private | `array $items` | — |
| ReportController | [`downloadDetailPdf()`](../../../TCV-Backend/app/Http/Controllers/ReportController.php#L192) | 192 | private | `array $items`, `?string $patientName = null` | — |
| ReportController | [`downloadExcel()`](../../../TCV-Backend/app/Http/Controllers/ReportController.php#L205) | 205 | private | `$query` | — |
| ReportController | [`downloadPdf()`](../../../TCV-Backend/app/Http/Controllers/ReportController.php#L213) | 213 | private | `$query` | — |
| ReportController | [`discountCode()`](../../../TCV-Backend/app/Http/Controllers/ReportController.php#L240) | 240 | public | `Request $request` | — |
| ReportController | [`downloadDiscountCodeExcel()`](../../../TCV-Backend/app/Http/Controllers/ReportController.php#L289) | 289 | private | `Request $request`, `array $params` | — |

### `app/Http/Controllers/RestrictedIpController.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| RestrictedIpController | [`__construct()`](../../../TCV-Backend/app/Http/Controllers/RestrictedIpController.php#L18) | 18 | public | `AuditService $auditService` | — |
| RestrictedIpController | [`index()`](../../../TCV-Backend/app/Http/Controllers/RestrictedIpController.php#L26) | 26 | public | — | JsonResponse |
| RestrictedIpController | [`store()`](../../../TCV-Backend/app/Http/Controllers/RestrictedIpController.php#L38) | 38 | public | `Request $request` | JsonResponse |
| RestrictedIpController | [`update()`](../../../TCV-Backend/app/Http/Controllers/RestrictedIpController.php#L71) | 71 | public | `Request $request`, `$id` | JsonResponse |
| RestrictedIpController | [`destroy()`](../../../TCV-Backend/app/Http/Controllers/RestrictedIpController.php#L112) | 112 | public | `Request $request`, `$id` | JsonResponse |

### `app/Http/Controllers/StripePaymentController.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| StripePaymentController | [`__construct()`](../../../TCV-Backend/app/Http/Controllers/StripePaymentController.php#L23) | 23 | public | `StripeService $stripeService`, `AuditService $auditService` | — |
| StripePaymentController | [`getPaymentMethods()`](../../../TCV-Backend/app/Http/Controllers/StripePaymentController.php#L32) | 32 | public | — | — |
| StripePaymentController | [`createPaymentIntent()`](../../../TCV-Backend/app/Http/Controllers/StripePaymentController.php#L52) | 52 | public | `Request $request` | — |
| StripePaymentController | [`confirmPayment()`](../../../TCV-Backend/app/Http/Controllers/StripePaymentController.php#L130) | 130 | public | `Request $request` | — |
| StripePaymentController | [`confirmACHPayment()`](../../../TCV-Backend/app/Http/Controllers/StripePaymentController.php#L236) | 236 | public | `Request $request` | — |
| StripePaymentController | [`savePaymentMethod()`](../../../TCV-Backend/app/Http/Controllers/StripePaymentController.php#L344) | 344 | private | `$user`, `$paymentMethodId` | — |
| StripePaymentController | [`removePaymentMethod()`](../../../TCV-Backend/app/Http/Controllers/StripePaymentController.php#L396) | 396 | public | `Request $request`, `string $paymentMethodId` | — |
| StripePaymentController | [`partialRefund()`](../../../TCV-Backend/app/Http/Controllers/StripePaymentController.php#L416) | 416 | public | `PartialPaymentRequest $request` | — |
| StripePaymentController | [`refund()`](../../../TCV-Backend/app/Http/Controllers/StripePaymentController.php#L453) | 453 | public | `RefundPaymentRequest $request` | — |
| StripePaymentController | [`paymentCallback()`](../../../TCV-Backend/app/Http/Controllers/StripePaymentController.php#L482) | 482 | public | `Request $request` | — |
| StripePaymentController | [`getTransactions()`](../../../TCV-Backend/app/Http/Controllers/StripePaymentController.php#L498) | 498 | public | — | — |

### `app/Http/Controllers/SuperAdminDashboardController.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| SuperAdminDashboardController | [`index()`](../../../TCV-Backend/app/Http/Controllers/SuperAdminDashboardController.php#L16) | 16 | public | `Request $request` | — |
| SuperAdminDashboardController | [`buildMonthlyGrid()`](../../../TCV-Backend/app/Http/Controllers/SuperAdminDashboardController.php#L152) | 152 | private | `$rawRows`, `Carbon $from`, `int $months`, `callable $mapper` | array |

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
| TestController | [`__construct()`](../../../TCV-Backend/app/Http/Controllers/TestController.php#L49) | 49 | public | `TestService $testService`, `TestAssignmentService $assignmentService`, `TestExecutionService $executionService`, `TestResultService $resultService`, `LmsLaunchService $launchService`, `AuditService $auditService` | — |
| TestController | [`index()`](../../../TCV-Backend/app/Http/Controllers/TestController.php#L65) | 65 | public | `Request $request` | — |
| TestController | [`userIndex()`](../../../TCV-Backend/app/Http/Controllers/TestController.php#L75) | 75 | public | `Request $request` | — |
| TestController | [`store()`](../../../TCV-Backend/app/Http/Controllers/TestController.php#L104) | 104 | public | `TestRequest $request` | — |
| TestController | [`show()`](../../../TCV-Backend/app/Http/Controllers/TestController.php#L113) | 113 | public | `Request $request`, `$id` | — |
| TestController | [`update()`](../../../TCV-Backend/app/Http/Controllers/TestController.php#L121) | 121 | public | `TestRequest $request`, `$id` | — |
| TestController | [`destroy()`](../../../TCV-Backend/app/Http/Controllers/TestController.php#L161) | 161 | public | `Request $request`, `$id` | — |
| TestController | [`assignTest()`](../../../TCV-Backend/app/Http/Controllers/TestController.php#L178) | 178 | public | `CreateTestRequest $request` | — |
| TestController | [`performTest()`](../../../TCV-Backend/app/Http/Controllers/TestController.php#L286) | 286 | public | `PerformTestRequest $request` | — |
| TestController | [`getTestSession()`](../../../TCV-Backend/app/Http/Controllers/TestController.php#L324) | 324 | public | `$unique_test_id` | — |
| TestController | [`getSectionPlates()`](../../../TCV-Backend/app/Http/Controllers/TestController.php#L353) | 353 | public | `$unique_test_id`, `$section_id` | — |
| TestController | [`getPlateUrl()`](../../../TCV-Backend/app/Http/Controllers/TestController.php#L379) | 379 | public | `string $unique_test_id`, `int $test_answer_id` | — |
| TestController | [`getTestResult()`](../../../TCV-Backend/app/Http/Controllers/TestController.php#L406) | 406 | public | `$unique_test_id` | — |
| TestController | [`downloadTestResultPDF()`](../../../TCV-Backend/app/Http/Controllers/TestController.php#L450) | 450 | public | `Request $request`, `$unique_test_id` | — |
| TestController | [`organizationAllowsDownload()`](../../../TCV-Backend/app/Http/Controllers/TestController.php#L573) | 573 | private | `Request $request`, `PatientTest $patientTest` | bool |
| TestController | [`callerOwnsPatientTest()`](../../../TCV-Backend/app/Http/Controllers/TestController.php#L621) | 621 | private | `Request $request`, `PatientTest $patientTest` | bool |
| TestController | [`callerOwnsPatient()`](../../../TCV-Backend/app/Http/Controllers/TestController.php#L661) | 661 | private | `Request $request`, `Patient $patient` | bool |
| TestController | [`getActiveTest()`](../../../TCV-Backend/app/Http/Controllers/TestController.php#L714) | 714 | public | `Request $request` | — |
| TestController | [`getActiveTestsWithAssignmentFlag()`](../../../TCV-Backend/app/Http/Controllers/TestController.php#L786) | 786 | public | `Request $request` | — |
| TestController | [`assignUserTest()`](../../../TCV-Backend/app/Http/Controllers/TestController.php#L811) | 811 | public | `$id` | — |
| TestController | [`unassignUserTest()`](../../../TCV-Backend/app/Http/Controllers/TestController.php#L819) | 819 | public | `$id` | — |
| TestController | [`bulkUpdateAssignment()`](../../../TCV-Backend/app/Http/Controllers/TestController.php#L832) | 832 | public | `Request $request` | — |

### `app/Http/Controllers/TestEmailTemplateController.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| TestEmailTemplateController | [`__construct()`](../../../TCV-Backend/app/Http/Controllers/TestEmailTemplateController.php#L20) | 20 | public | `AuditService $auditService` | — |
| TestEmailTemplateController | [`index()`](../../../TCV-Backend/app/Http/Controllers/TestEmailTemplateController.php#L30) | 30 | public | — | JsonResponse |
| TestEmailTemplateController | [`update()`](../../../TCV-Backend/app/Http/Controllers/TestEmailTemplateController.php#L81) | 81 | public | `Request $request`, `int $id` | JsonResponse |
| TestEmailTemplateController | [`validateTemplateData()`](../../../TCV-Backend/app/Http/Controllers/TestEmailTemplateController.php#L213) | 213 | private | `array $data`, `string $type` | Illuminate\Contracts\Validation\Validator |
| TestEmailTemplateController | [`validateRequiredPlaceholders()`](../../../TCV-Backend/app/Http/Controllers/TestEmailTemplateController.php#L237) | 237 | private | `string $body`, `string $subject`, `string $type` | array |
| TestEmailTemplateController | [`getPlaceholders()`](../../../TCV-Backend/app/Http/Controllers/TestEmailTemplateController.php#L290) | 290 | public | `string $type` | JsonResponse |

### `app/Http/Controllers/TestInvitationController.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| TestInvitationController | [`__construct()`](../../../TCV-Backend/app/Http/Controllers/TestInvitationController.php#L23) | 23 | public | `TestInvitationMailer $invitationMailer`, `AuditService $auditService` | — |
| TestInvitationController | [`sendInvitations()`](../../../TCV-Backend/app/Http/Controllers/TestInvitationController.php#L28) | 28 | public | `Request $request` | — |
| TestInvitationController | [`createPendingInvitations()`](../../../TCV-Backend/app/Http/Controllers/TestInvitationController.php#L231) | 231 | private | `array $emails`, `int $testId`, `int $userId` | array |
| TestInvitationController | [`dispatchEmailBatch()`](../../../TCV-Backend/app/Http/Controllers/TestInvitationController.php#L278) | 278 | private | `array $invitationIds`, `int $userId`, `?float $deadline = null` | void |
| TestInvitationController | [`getUnregisteredInvitations()`](../../../TCV-Backend/app/Http/Controllers/TestInvitationController.php#L287) | 287 | public | `Request $request` | — |
| TestInvitationController | [`resendUnregisteredInvitation()`](../../../TCV-Backend/app/Http/Controllers/TestInvitationController.php#L362) | 362 | public | `int $invitationId` | — |
| TestInvitationController | [`cancelUnregisteredInvitation()`](../../../TCV-Backend/app/Http/Controllers/TestInvitationController.php#L433) | 433 | public | `int $invitationId` | — |
| TestInvitationController | [`sendInvitationEmail()`](../../../TCV-Backend/app/Http/Controllers/TestInvitationController.php#L501) | 501 | private | `$email`, `$test`, `$token`, `$verificationCode`, `$expiresAt`, `int $userId` | void |
| TestInvitationController | [`verifyCode()`](../../../TCV-Backend/app/Http/Controllers/TestInvitationController.php#L509) | 509 | public | `Request $request` | — |
| TestInvitationController | [`checkTokenStatus()`](../../../TCV-Backend/app/Http/Controllers/TestInvitationController.php#L628) | 628 | public | `Request $request` | — |

### `app/Http/Controllers/TestResumeController.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| TestResumeController | [`sendResumeEmail()`](../../../TCV-Backend/app/Http/Controllers/TestResumeController.php#L23) | 23 | public | `Request $request` | — |
| TestResumeController | [`callerOwnsPatientTest()`](../../../TCV-Backend/app/Http/Controllers/TestResumeController.php#L113) | 113 | private | `Request $request`, `PatientTest $patientTest` | bool |
| TestResumeController | [`resume()`](../../../TCV-Backend/app/Http/Controllers/TestResumeController.php#L156) | 156 | public | `Request $request` | — |
| TestResumeController | [`dispatchResumeEmail()`](../../../TCV-Backend/app/Http/Controllers/TestResumeController.php#L284) | 284 | private | `string $email`, `PatientTest $patientTest`, `string $token` | void |

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
| UserController | [`__construct()`](../../../TCV-Backend/app/Http/Controllers/UserController.php#L45) | 45 | public | `AuditService $auditService` | — |
| UserController | [`accountCreateDetails()`](../../../TCV-Backend/app/Http/Controllers/UserController.php#L58) | 58 | private | `User $user` | array |
| UserController | [`index()`](../../../TCV-Backend/app/Http/Controllers/UserController.php#L83) | 83 | public | `Request $request` | — |
| UserController | [`store()`](../../../TCV-Backend/app/Http/Controllers/UserController.php#L103) | 103 | public | `UserRequest $request` | — |
| UserController | [`edit()`](../../../TCV-Backend/app/Http/Controllers/UserController.php#L195) | 195 | public | `string $id` | — |
| UserController | [`update()`](../../../TCV-Backend/app/Http/Controllers/UserController.php#L204) | 204 | public | `UserRequest $request`, `$id` | — |
| UserController | [`show()`](../../../TCV-Backend/app/Http/Controllers/UserController.php#L288) | 288 | public | — | — |
| UserController | [`destroy()`](../../../TCV-Backend/app/Http/Controllers/UserController.php#L305) | 305 | public | `Request $request`, `string $id` | — |
| UserController | [`userWithType()`](../../../TCV-Backend/app/Http/Controllers/UserController.php#L345) | 345 | public | `Request $request`, `$usertype` | — |
| UserController | [`getUserCredits()`](../../../TCV-Backend/app/Http/Controllers/UserController.php#L465) | 465 | public | — | — |
| UserController | [`addCreditsToUsers()`](../../../TCV-Backend/app/Http/Controllers/UserController.php#L483) | 483 | private | `$userCollection` | — |

### `app/Http/Controllers/UserEmailTemplateController.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| UserEmailTemplateController | [`__construct()`](../../../TCV-Backend/app/Http/Controllers/UserEmailTemplateController.php#L17) | 17 | public | `EmailTemplateService $emailTemplateService`, `AuditService $auditService` | — |
| UserEmailTemplateController | [`show()`](../../../TCV-Backend/app/Http/Controllers/UserEmailTemplateController.php#L28) | 28 | public | — | JsonResponse |
| UserEmailTemplateController | [`update()`](../../../TCV-Backend/app/Http/Controllers/UserEmailTemplateController.php#L57) | 57 | public | `UpdateUserEmailTemplateRequest $request` | JsonResponse |
| UserEmailTemplateController | [`destroy()`](../../../TCV-Backend/app/Http/Controllers/UserEmailTemplateController.php#L124) | 124 | public | — | JsonResponse |

### `app/Http/Middleware/AddRequestId.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| AddRequestId | [`handle()`](../../../TCV-Backend/app/Http/Middleware/AddRequestId.php#L22) | 22 | public | `Request $request`, `Closure $next` | Response |

### `app/Http/Middleware/FlexibleAuthMiddleware.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| FlexibleAuthMiddleware | [`context()`](../../../TCV-Backend/app/Http/Middleware/FlexibleAuthMiddleware.php#L37) | 37 | public static | `Request $request` | ?array |
| FlexibleAuthMiddleware | [`resolvedOrgId()`](../../../TCV-Backend/app/Http/Middleware/FlexibleAuthMiddleware.php#L65) | 65 | public static | `Request $request` | ?int |
| FlexibleAuthMiddleware | [`setAuthContext()`](../../../TCV-Backend/app/Http/Middleware/FlexibleAuthMiddleware.php#L128) | 128 | private | `Request $request`, `array $context` | void |
| FlexibleAuthMiddleware | [`handle()`](../../../TCV-Backend/app/Http/Middleware/FlexibleAuthMiddleware.php#L140) | 140 | public | `Request $request`, `Closure $next` | Response |
| FlexibleAuthMiddleware | [`sessionExpired()`](../../../TCV-Backend/app/Http/Middleware/FlexibleAuthMiddleware.php#L245) | 245 | private | — | Response |
| FlexibleAuthMiddleware | [`sessionSuperseded()`](../../../TCV-Backend/app/Http/Middleware/FlexibleAuthMiddleware.php#L254) | 254 | private | — | Response |
| FlexibleAuthMiddleware | [`unauthenticated()`](../../../TCV-Backend/app/Http/Middleware/FlexibleAuthMiddleware.php#L264) | 264 | private | — | Response |

### `app/Http/Middleware/LmsSessionStatusMiddleware.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| LmsSessionStatusMiddleware | [`handle()`](../../../TCV-Backend/app/Http/Middleware/LmsSessionStatusMiddleware.php#L11) | 11 | public | `Request $request`, `Closure $next`, `string $allowedStatuses` | Response |

### `app/Http/Middleware/RestrictIpMiddleware.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| RestrictIpMiddleware | [`handle()`](../../../TCV-Backend/app/Http/Middleware/RestrictIpMiddleware.php#L13) | 13 | public | `Request $request`, `Closure $next` | — |

### `app/Http/Requests/AuditLogIndexRequest.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| AuditLogIndexRequest | [`authorize()`](../../../TCV-Backend/app/Http/Requests/AuditLogIndexRequest.php#L26) | 26 | public | — | bool |
| AuditLogIndexRequest | [`failedAuthorization()`](../../../TCV-Backend/app/Http/Requests/AuditLogIndexRequest.php#L36) | 36 | protected | — | void |
| AuditLogIndexRequest | [`rules()`](../../../TCV-Backend/app/Http/Requests/AuditLogIndexRequest.php#L43) | 43 | public | — | array |
| AuditLogIndexRequest | [`failedValidation()`](../../../TCV-Backend/app/Http/Requests/AuditLogIndexRequest.php#L62) | 62 | public | `Validator $validator` | — |

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
| CreditsAddRequest | [`messages()`](../../../TCV-Backend/app/Http/Requests/CreditsAddRequest.php#L34) | 34 | public | — | array |

### `app/Http/Requests/DistributorEnquiryFormRequest.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| DistributorEnquiryFormRequest | [`authorize()`](../../../TCV-Backend/app/Http/Requests/DistributorEnquiryFormRequest.php#L9) | 9 | public | — | bool |
| DistributorEnquiryFormRequest | [`rules()`](../../../TCV-Backend/app/Http/Requests/DistributorEnquiryFormRequest.php#L14) | 14 | public | — | array |
| DistributorEnquiryFormRequest | [`messages()`](../../../TCV-Backend/app/Http/Requests/DistributorEnquiryFormRequest.php#L31) | 31 | public | — | array |

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
| PatientAddRequest | [`authorize()`](../../../TCV-Backend/app/Http/Requests/PatientAddRequest.php#L12) | 12 | public | — | bool |
| PatientAddRequest | [`rules()`](../../../TCV-Backend/app/Http/Requests/PatientAddRequest.php#L17) | 17 | public | — | array |
| PatientAddRequest | [`failedValidation()`](../../../TCV-Backend/app/Http/Requests/PatientAddRequest.php#L33) | 33 | public | `Illuminate\Contracts\Validation\Validator $validator` | — |

### `app/Http/Requests/PatientUpdateRequest.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| PatientUpdateRequest | [`authorize()`](../../../TCV-Backend/app/Http/Requests/PatientUpdateRequest.php#L12) | 12 | public | — | bool |
| PatientUpdateRequest | [`rules()`](../../../TCV-Backend/app/Http/Requests/PatientUpdateRequest.php#L17) | 17 | public | — | array |

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
| UpdateUserEmailTemplateRequest | [`authorize()`](../../../TCV-Backend/app/Http/Requests/UpdateUserEmailTemplateRequest.php#L16) | 16 | public | — | bool |
| UpdateUserEmailTemplateRequest | [`rules()`](../../../TCV-Backend/app/Http/Requests/UpdateUserEmailTemplateRequest.php#L24) | 24 | public | — | array |
| UpdateUserEmailTemplateRequest | [`messages()`](../../../TCV-Backend/app/Http/Requests/UpdateUserEmailTemplateRequest.php#L51) | 51 | public | — | array |
| UpdateUserEmailTemplateRequest | [`withValidator()`](../../../TCV-Backend/app/Http/Requests/UpdateUserEmailTemplateRequest.php#L64) | 64 | public | `$validator` | void |
| UpdateUserEmailTemplateRequest | [`prepareForValidation()`](../../../TCV-Backend/app/Http/Requests/UpdateUserEmailTemplateRequest.php#L112) | 112 | protected | — | void |

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

### `app/Jobs/SendTestInvitationEmailsJob.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| SendTestInvitationEmailsJob | [`__construct()`](../../../TCV-Backend/app/Jobs/SendTestInvitationEmailsJob.php#L63) | 63 | public | `array $invitationIds`, `int $userId`, `?float $deadline = null` | — |
| SendTestInvitationEmailsJob | [`handle()`](../../../TCV-Backend/app/Jobs/SendTestInvitationEmailsJob.php#L70) | 70 | public | `TestInvitationMailer $mailer` | void |
| SendTestInvitationEmailsJob | [`sendBatch()`](../../../TCV-Backend/app/Jobs/SendTestInvitationEmailsJob.php#L99) | 99 | private | `TestInvitationMailer $mailer` | void |
| SendTestInvitationEmailsJob | [`pastDeadline()`](../../../TCV-Backend/app/Jobs/SendTestInvitationEmailsJob.php#L208) | 208 | private | — | bool |
| SendTestInvitationEmailsJob | [`claim()`](../../../TCV-Backend/app/Jobs/SendTestInvitationEmailsJob.php#L224) | 224 | private | `TestInvitation $invitation` | bool |
| SendTestInvitationEmailsJob | [`release()`](../../../TCV-Backend/app/Jobs/SendTestInvitationEmailsJob.php#L244) | 244 | private | `TestInvitation $invitation` | void |
| SendTestInvitationEmailsJob | [`sendOne()`](../../../TCV-Backend/app/Jobs/SendTestInvitationEmailsJob.php#L256) | 256 | private | `TestInvitationMailer $mailer`, `TestInvitation $invitation` | bool |
| SendTestInvitationEmailsJob | [`recordSent()`](../../../TCV-Backend/app/Jobs/SendTestInvitationEmailsJob.php#L319) | 319 | private | `TestInvitation $invitation` | void |
| SendTestInvitationEmailsJob | [`isTransient()`](../../../TCV-Backend/app/Jobs/SendTestInvitationEmailsJob.php#L355) | 355 | private | `Throwable $e` | bool |
| SendTestInvitationEmailsJob | [`capMessagesPerConnection()`](../../../TCV-Backend/app/Jobs/SendTestInvitationEmailsJob.php#L372) | 372 | private | — | void |
| SendTestInvitationEmailsJob | [`resetConnection()`](../../../TCV-Backend/app/Jobs/SendTestInvitationEmailsJob.php#L384) | 384 | private | — | void |
| SendTestInvitationEmailsJob | [`markFailed()`](../../../TCV-Backend/app/Jobs/SendTestInvitationEmailsJob.php#L414) | 414 | private | `TestInvitation $invitation`, `string $error` | void |
| SendTestInvitationEmailsJob | [`failed()`](../../../TCV-Backend/app/Jobs/SendTestInvitationEmailsJob.php#L439) | 439 | public | `Throwable $e` | void |

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

### `app/Listeners/PrefixEmailSubject.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| PrefixEmailSubject | [`handle()`](../../../TCV-Backend/app/Listeners/PrefixEmailSubject.php#L24) | 24 | public | `MessageSending $event` | void |
| PrefixEmailSubject | [`apply()`](../../../TCV-Backend/app/Listeners/PrefixEmailSubject.php#L31) | 31 | public static | `string $subject` | string |

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

### `app/Models/AuditLog.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| AuditLog | [`casts()`](../../../TCV-Backend/app/Models/AuditLog.php#L41) | 41 | protected | — | array |
| AuditLog | [`toListArray()`](../../../TCV-Backend/app/Models/AuditLog.php#L58) | 58 | public | — | array |
| AuditLog | [`toDetailArray()`](../../../TCV-Backend/app/Models/AuditLog.php#L78) | 78 | public | — | array |
| AuditLog | [`personArray()`](../../../TCV-Backend/app/Models/AuditLog.php#L95) | 95 | private | `string $prefix`, `bool $withIp = false` | ?array |

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
| Credits | [`sourceLabel()`](../../../TCV-Backend/app/Models/Credits.php#L44) | 44 | public static | `?int $source` | string |
| Credits | [`user()`](../../../TCV-Backend/app/Models/Credits.php#L58) | 58 | public | — | — |
| Credits | [`scopeActive()`](../../../TCV-Backend/app/Models/Credits.php#L73) | 73 | public | `$query` | — |
| Credits | [`getTotalUserCredit()`](../../../TCV-Backend/app/Models/Credits.php#L87) | 87 | public static | `$userId` | — |
| Credits | [`addCreditsToUser()`](../../../TCV-Backend/app/Models/Credits.php#L119) | 119 | public static | `User $user`, `$credits = []` | self |
| Credits | [`transactions()`](../../../TCV-Backend/app/Models/Credits.php#L136) | 136 | public | — | — |
| Credits | [`getAvailableCredits()`](../../../TCV-Backend/app/Models/Credits.php#L145) | 145 | public static | `int $userId` | int|string |
| Credits | [`getGrantAllocation()`](../../../TCV-Backend/app/Models/Credits.php#L198) | 198 | public static | `int $userId` | array |
| Credits | [`getUnusedCreditsForGrant()`](../../../TCV-Backend/app/Models/Credits.php#L260) | 260 | public static | `self $grant` | int |
| Credits | [`traceConsumedOrigin()`](../../../TCV-Backend/app/Models/Credits.php#L284) | 284 | public static | `User $user`, `string $eventType`, `array $candidateRefIds` | int |
| Credits | [`resolveOriginSource()`](../../../TCV-Backend/app/Models/Credits.php#L389) | 389 | private static | `self $grant` | int |
| Credits | [`revokeGrant()`](../../../TCV-Backend/app/Models/Credits.php#L412) | 412 | public static | `self $grant` | array |
| Credits | [`hasExpired()`](../../../TCV-Backend/app/Models/Credits.php#L488) | 488 | public | — | bool |
| Credits | [`countsTowardBalance()`](../../../TCV-Backend/app/Models/Credits.php#L516) | 516 | public | — | bool |
| Credits | [`settleNegativeBalance()`](../../../TCV-Backend/app/Models/Credits.php#L539) | 539 | private static | `int $userId` | void |

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
| Patient | [`genderLabel()`](../../../TCV-Backend/app/Models/Patient.php#L47) | 47 | public static | `$gender` | ?string |
| Patient | [`user()`](../../../TCV-Backend/app/Models/Patient.php#L59) | 59 | public | — | — |
| Patient | [`tests()`](../../../TCV-Backend/app/Models/Patient.php#L67) | 67 | public | — | — |

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
| TestInvitation | [`test()`](../../../TCV-Backend/app/Models/TestInvitation.php#L55) | 55 | public | — | — |
| TestInvitation | [`user()`](../../../TCV-Backend/app/Models/TestInvitation.php#L63) | 63 | public | — | — |
| TestInvitation | [`isExpired()`](../../../TCV-Backend/app/Models/TestInvitation.php#L71) | 71 | public | — | bool |
| TestInvitation | [`isValid()`](../../../TCV-Backend/app/Models/TestInvitation.php#L79) | 79 | public | — | bool |

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
| TestSession | [`testInvitation()`](../../../TCV-Backend/app/Models/TestSession.php#L33) | 33 | public | — | — |
| TestSession | [`patient()`](../../../TCV-Backend/app/Models/TestSession.php#L42) | 42 | public | — | — |

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
| User | [`hasVerifiedEmail()`](../../../TCV-Backend/app/Models/User.php#L108) | 108 | public | — | — |
| User | [`isSuperAdmin()`](../../../TCV-Backend/app/Models/User.php#L113) | 113 | public | — | — |
| User | [`canImpersonate()`](../../../TCV-Backend/app/Models/User.php#L118) | 118 | public | — | bool |
| User | [`canBeImpersonated()`](../../../TCV-Backend/app/Models/User.php#L123) | 123 | public | — | bool |
| User | [`canImpersonateUser()`](../../../TCV-Backend/app/Models/User.php#L128) | 128 | public | `User $target` | — |
| User | [`stripeDetail()`](../../../TCV-Backend/app/Models/User.php#L142) | 142 | public | — | — |
| User | [`assignedTests()`](../../../TCV-Backend/app/Models/User.php#L147) | 147 | public | — | — |
| User | [`organization()`](../../../TCV-Backend/app/Models/User.php#L156) | 156 | public | — | — |
| User | [`country()`](../../../TCV-Backend/app/Models/User.php#L161) | 161 | public | — | — |

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
| ResetPasswordNotification | [`__construct()`](../../../TCV-Backend/app/Notifications/ResetPasswordNotification.php#L21) | 21 | public | `$token`, `$isReset = false` | — |
| ResetPasswordNotification | [`via()`](../../../TCV-Backend/app/Notifications/ResetPasswordNotification.php#L30) | 30 | public | `object $notifiable` | array |
| ResetPasswordNotification | [`toMail()`](../../../TCV-Backend/app/Notifications/ResetPasswordNotification.php#L38) | 38 | public | `object $notifiable` | MailMessage |
| ResetPasswordNotification | [`toArray()`](../../../TCV-Backend/app/Notifications/ResetPasswordNotification.php#L92) | 92 | public | `object $notifiable` | array |

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
| CreditsPolicy | [`delete()`](../../../TCV-Backend/app/Policies/CreditsPolicy.php#L61) | 61 | public | `User $user`, `Credits $credits` | bool |
| CreditsPolicy | [`restore()`](../../../TCV-Backend/app/Policies/CreditsPolicy.php#L78) | 78 | public | `User $user`, `Credits $credits` | bool |
| CreditsPolicy | [`forceDelete()`](../../../TCV-Backend/app/Policies/CreditsPolicy.php#L86) | 86 | public | `User $user`, `Credits $credits` | bool |

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
| AppServiceProvider | [`register()`](../../../TCV-Backend/app/Providers/AppServiceProvider.php#L23) | 23 | public | — | void |
| AppServiceProvider | [`boot()`](../../../TCV-Backend/app/Providers/AppServiceProvider.php#L31) | 31 | public | — | void |
| AppServiceProvider | [`configureMigrationHealthCheck()`](../../../TCV-Backend/app/Providers/AppServiceProvider.php#L54) | 54 | protected | — | void |
| AppServiceProvider | [`configureRateLimiting()`](../../../TCV-Backend/app/Providers/AppServiceProvider.php#L73) | 73 | protected | — | void |
| AppServiceProvider | [`callerKey()`](../../../TCV-Backend/app/Providers/AppServiceProvider.php#L156) | 156 | private | `Request $request`, `string|int|null $identifier` | string |
| AppServiceProvider | [`warnIfFrontendAppUrlLooksInvalid()`](../../../TCV-Backend/app/Providers/AppServiceProvider.php#L175) | 175 | protected | — | void |

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
| EmailTemplateRepository | [`getUserTemplate()`](../../../TCV-Backend/app/Repositories/EmailTemplateRepository.php#L15) | 15 | public | `int $userId`, `string $type = …` | ?UserEmailTemplate |
| EmailTemplateRepository | [`getAdminDefaultTemplate()`](../../../TCV-Backend/app/Repositories/EmailTemplateRepository.php#L23) | 23 | public | `string $type = …` | ?TestEmailTemplates |
| EmailTemplateRepository | [`saveUserTemplate()`](../../../TCV-Backend/app/Repositories/EmailTemplateRepository.php#L36) | 36 | public | `int $userId`, `array $data` | UserEmailTemplate |
| EmailTemplateRepository | [`deleteUserTemplate()`](../../../TCV-Backend/app/Repositories/EmailTemplateRepository.php#L61) | 61 | public | `int $userId`, `string $type = …` | bool |
| EmailTemplateRepository | [`hasCustomTemplate()`](../../../TCV-Backend/app/Repositories/EmailTemplateRepository.php#L69) | 69 | public | `int $userId`, `string $type = …` | bool |

### `app/Rules/TurnstileToken.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| TurnstileToken | [`validate()`](../../../TCV-Backend/app/Rules/TurnstileToken.php#L14) | 14 | public | `string $attribute`, `mixed $value`, `Closure $fail` | void |
| TurnstileToken | [`message()`](../../../TCV-Backend/app/Rules/TurnstileToken.php#L31) | 31 | public | — | string |

### `app/Services/Audit/AuditEventCatalog.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| AuditEventCatalog | [`get()`](../../../TCV-Backend/app/Services/Audit/AuditEventCatalog.php#L416) | 416 | public static | `string $eventKey` | array |
| AuditEventCatalog | [`accountEventKey()`](../../../TCV-Backend/app/Services/Audit/AuditEventCatalog.php#L435) | 435 | public static | `int $usertype`, `string $action` | string |
| AuditEventCatalog | [`creditEventKey()`](../../../TCV-Backend/app/Services/Audit/AuditEventCatalog.php#L455) | 455 | public static | `int $usertype`, `string $action` | string |

### `app/Services/Audit/AuditLogger.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| AuditLogger | [`log()`](../../../TCV-Backend/app/Services/Audit/AuditLogger.php#L10) | 10 | public static | `string $table`, `string $entityColumn`, `int $entityId`, `?int $userId`, `string $action`, `array $oldValues = []`, `array $newValues = []` | void |

### `app/Services/Audit/AuditService.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| AuditService | [`log()`](../../../TCV-Backend/app/Services/Audit/AuditService.php#L84) | 84 | public | `string $eventKey`, `string $description`, `?User $actor`, `?User $target = null`, `string $status = 'success'`, `array $details = []`, `array $changes = []`, `?Request $request = null`, `?string $sessionKeyOverride = null` | ?AuditLog |
| AuditService | [`personSnapshot()`](../../../TCV-Backend/app/Services/Audit/AuditService.php#L167) | 167 | private | `?User $user` | ?array |
| AuditService | [`roleFor()`](../../../TCV-Backend/app/Services/Audit/AuditService.php#L189) | 189 | public static | `int $usertype` | string |
| AuditService | [`lookupCountry()`](../../../TCV-Backend/app/Services/Audit/AuditService.php#L199) | 199 | private | `?string $ip` | ?string |
| AuditService | [`parseBrowser()`](../../../TCV-Backend/app/Services/Audit/AuditService.php#L223) | 223 | private | `?string $userAgent` | ?string |
| AuditService | [`sessionKeyForToken()`](../../../TCV-Backend/app/Services/Audit/AuditService.php#L262) | 262 | public static | `string $plainTextToken` | string |
| AuditService | [`sessionKey()`](../../../TCV-Backend/app/Services/Audit/AuditService.php#L267) | 267 | private | `?Request $request` | ?string |
| AuditService | [`maskDetails()`](../../../TCV-Backend/app/Services/Audit/AuditService.php#L306) | 306 | public | `array $details`, `bool $maskPatientData = true` | array |
| AuditService | [`maskChanges()`](../../../TCV-Backend/app/Services/Audit/AuditService.php#L325) | 325 | public | `array $changes`, `bool $maskPatientData = true` | array |
| AuditService | [`isDenylistedKey()`](../../../TCV-Backend/app/Services/Audit/AuditService.php#L339) | 339 | public | `string $key` | bool |
| AuditService | [`isSecretKey()`](../../../TCV-Backend/app/Services/Audit/AuditService.php#L347) | 347 | private | `string $key` | bool |
| AuditService | [`isPhiKey()`](../../../TCV-Backend/app/Services/Audit/AuditService.php#L355) | 355 | private | `string $key` | bool |
| AuditService | [`keyMatches()`](../../../TCV-Backend/app/Services/Audit/AuditService.php#L363) | 363 | private | `string $key`, `array $terms` | bool |
| AuditService | [`isDateAllowlistedKey()`](../../../TCV-Backend/app/Services/Audit/AuditService.php#L380) | 380 | private | `string $key` | bool |
| AuditService | [`maskValue()`](../../../TCV-Backend/app/Services/Audit/AuditService.php#L396) | 396 | private | `mixed $value`, `bool $isDateAllowlisted = false`, `bool $maskPatientData = true` | mixed |
| AuditService | [`maskFreeText()`](../../../TCV-Backend/app/Services/Audit/AuditService.php#L441) | 441 | private | `string $text`, `bool $maskPatientData = true` | string |

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
| DiscountCodeService | [`calculate()`](../../../TCV-Backend/app/Services/DiscountCodeService.php#L79) | 79 | public | `DiscountCode $discount`, `float $amount` | array |
| DiscountCodeService | [`syncRestrictions()`](../../../TCV-Backend/app/Services/DiscountCodeService.php#L99) | 99 | public | `DiscountCode $discount`, `?array $userIds`, `?array $priceTierIds` | void |
| DiscountCodeService | [`countUses()`](../../../TCV-Backend/app/Services/DiscountCodeService.php#L112) | 112 | public | `int $discountId`, `?int $userId = null` | int |
| DiscountCodeService | [`creditMatchesTier()`](../../../TCV-Backend/app/Services/DiscountCodeService.php#L124) | 124 | private | `int $credits`, `DiscountCode $discount` | bool |
| DiscountCodeService | [`success()`](../../../TCV-Backend/app/Services/DiscountCodeService.php#L134) | 134 | private | `DiscountCode $discount`, `float $amount` | array |
| DiscountCodeService | [`fail()`](../../../TCV-Backend/app/Services/DiscountCodeService.php#L157) | 157 | private | `string $message`, `int $status = 400` | array |

### `app/Services/EmailTemplateService.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| EmailTemplateService | [`__construct()`](../../../TCV-Backend/app/Services/EmailTemplateService.php#L13) | 13 | public | `EmailTemplateRepository $repository` | — |
| EmailTemplateService | [`typeForUser()`](../../../TCV-Backend/app/Services/EmailTemplateService.php#L34) | 34 | public static | `?User $user` | string |
| EmailTemplateService | [`getTemplateForUser()`](../../../TCV-Backend/app/Services/EmailTemplateService.php#L46) | 46 | public | `int $userId`, `string $type = …` | array |
| EmailTemplateService | [`saveUserTemplate()`](../../../TCV-Backend/app/Services/EmailTemplateService.php#L98) | 98 | public | `int $userId`, `array $data` | array |
| EmailTemplateService | [`resetToDefault()`](../../../TCV-Backend/app/Services/EmailTemplateService.php#L138) | 138 | public | `int $userId`, `string $type = …` | array |
| EmailTemplateService | [`validatePlaceholder()`](../../../TCV-Backend/app/Services/EmailTemplateService.php#L165) | 165 | public | `string $body`, `string $placeholder = '{{verification_link}}'` | bool |

### `app/Services/HubSpotService.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| HubSpotService | [`__construct()`](../../../TCV-Backend/app/Services/HubSpotService.php#L36) | 36 | public | — | — |
| HubSpotService | [`submitEnquiry()`](../../../TCV-Backend/app/Services/HubSpotService.php#L61) | 61 | public | `array $data`, `string $subjectPrefix = 'Enquiry'`, `bool $allowUpdatingExistingContact = true` | void |
| HubSpotService | [`http()`](../../../TCV-Backend/app/Services/HubSpotService.php#L74) | 74 | private | — | PendingRequest |
| HubSpotService | [`upsertContact()`](../../../TCV-Backend/app/Services/HubSpotService.php#L86) | 86 | private | `array $data`, `bool $allowUpdatingExistingContact` | string |
| HubSpotService | [`createTicket()`](../../../TCV-Backend/app/Services/HubSpotService.php#L169) | 169 | private | `array $data`, `string $contactId`, `string $subjectPrefix` | void |
| HubSpotService | [`postTicket()`](../../../TCV-Backend/app/Services/HubSpotService.php#L246) | 246 | private | `array $properties`, `string $contactId` | Response |
| HubSpotService | [`sourceUnsupportedKey()`](../../../TCV-Backend/app/Services/HubSpotService.php#L265) | 265 | private | — | string |
| HubSpotService | [`isSourcePropertyRejection()`](../../../TCV-Backend/app/Services/HubSpotService.php#L276) | 276 | private | `Response $response` | bool |
| HubSpotService | [`rejectedPropertyNames()`](../../../TCV-Backend/app/Services/HubSpotService.php#L296) | 296 | private | `Response $response` | array |
| HubSpotService | [`propertyNamesIn()`](../../../TCV-Backend/app/Services/HubSpotService.php#L322) | 322 | private | `mixed $errors` | array |
| HubSpotService | [`embeddedJsonArray()`](../../../TCV-Backend/app/Services/HubSpotService.php#L341) | 341 | private | `string $message` | ?string |

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
| StripeProvider | [`__construct()`](../../../TCV-Backend/app/Services/PaymentProviders/StripeProvider.php#L25) | 25 | public | `array $config = []` | — |
| StripeProvider | [`getSupportedMethods()`](../../../TCV-Backend/app/Services/PaymentProviders/StripeProvider.php#L40) | 40 | public | — | array |
| StripeProvider | [`createSetupIntent()`](../../../TCV-Backend/app/Services/PaymentProviders/StripeProvider.php#L55) | 55 | public | `array $data` | array |
| StripeProvider | [`initializePayment()`](../../../TCV-Backend/app/Services/PaymentProviders/StripeProvider.php#L83) | 83 | public | `array $paymentData` | array |
| StripeProvider | [`confirmPayment()`](../../../TCV-Backend/app/Services/PaymentProviders/StripeProvider.php#L159) | 159 | public | `array $paymentData` | array |
| StripeProvider | [`handleWebhook()`](../../../TCV-Backend/app/Services/PaymentProviders/StripeProvider.php#L265) | 265 | public | `array $data` | array |
| StripeProvider | [`getOrCreateCustomer()`](../../../TCV-Backend/app/Services/PaymentProviders/StripeProvider.php#L295) | 295 | private | `User $user` | Customer |
| StripeProvider | [`attachAndPersistPaymentMethod()`](../../../TCV-Backend/app/Services/PaymentProviders/StripeProvider.php#L300) | 300 | private | `User $user`, `string $customerId`, `PaymentMethod $paymentMethod` | void |

### `app/Services/Reports/DiscountCodeReportService.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| DiscountCodeReportService | [`getReports()`](../../../TCV-Backend/app/Services/Reports/DiscountCodeReportService.php#L9) | 9 | public | `array $params` | object |
| DiscountCodeReportService | [`getSummary()`](../../../TCV-Backend/app/Services/Reports/DiscountCodeReportService.php#L24) | 24 | public | `array $params` | array |
| DiscountCodeReportService | [`buildQuery()`](../../../TCV-Backend/app/Services/Reports/DiscountCodeReportService.php#L94) | 94 | public | `?string $search`, `$sortBy`, `$sortOrder`, `?string $fromDate`, `?string $toDate`, `?string $code` | — |
| DiscountCodeReportService | [`normaliseSort()`](../../../TCV-Backend/app/Services/Reports/DiscountCodeReportService.php#L174) | 174 | private | `$sortBy`, `$sortOrder` | array |

### `app/Services/Reports/UserTestsReportService.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| UserTestsReportService | [`__construct()`](../../../TCV-Backend/app/Services/Reports/UserTestsReportService.php#L18) | 18 | public | `PatientTestTransformer $transformer` | — |
| UserTestsReportService | [`query()`](../../../TCV-Backend/app/Services/Reports/UserTestsReportService.php#L23) | 23 | public | `array $filters` | — |
| UserTestsReportService | [`getPatientTestsForReport()`](../../../TCV-Backend/app/Services/Reports/UserTestsReportService.php#L86) | 86 | public | `int $patientId`, `array $filters`, `int $limit`, `int $page` | array |
| UserTestsReportService | [`getPatientTestsForExport()`](../../../TCV-Backend/app/Services/Reports/UserTestsReportService.php#L109) | 109 | public | `int $patientId`, `array $filters` | array |
| UserTestsReportService | [`buildTransformedTests()`](../../../TCV-Backend/app/Services/Reports/UserTestsReportService.php#L119) | 119 | private | `int $patientId`, `array $filters` | array |
| UserTestsReportService | [`getPatientsWithTests()`](../../../TCV-Backend/app/Services/Reports/UserTestsReportService.php#L165) | 165 | public | `array $filters` | — |
| UserTestsReportService | [`fullNameSearchClause()`](../../../TCV-Backend/app/Services/Reports/UserTestsReportService.php#L208) | 208 | private | `$query`, `string $search` | void |

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
| StripeService | [`__construct()`](../../../TCV-Backend/app/Services/StripeService.php#L22) | 22 | public | `?Stripe\StripeClient $stripe = null` | — |
| StripeService | [`appSource()`](../../../TCV-Backend/app/Services/StripeService.php#L50) | 50 | public static | — | string |
| StripeService | [`describePaymentMethod()`](../../../TCV-Backend/app/Services/StripeService.php#L70) | 70 | public static | `?PaymentMethod $paymentMethod` | ?string |
| StripeService | [`getStripeClient()`](../../../TCV-Backend/app/Services/StripeService.php#L91) | 91 | public | — | — |
| StripeService | [`createOrGetCustomer()`](../../../TCV-Backend/app/Services/StripeService.php#L99) | 99 | public | `User $user` | Customer |
| StripeService | [`getCustomerPaymentMethods()`](../../../TCV-Backend/app/Services/StripeService.php#L203) | 203 | public | `User $user` | array |
| StripeService | [`paymentMethodExists()`](../../../TCV-Backend/app/Services/StripeService.php#L240) | 240 | public | `User $user`, `string $paymentMethodId` | bool |
| StripeService | [`createStripePaymentIntent()`](../../../TCV-Backend/app/Services/StripeService.php#L268) | 268 | public | `User $user`, `float $amount`, `int $credits`, `string $paymentMethod`, `array $billingInfo` | PaymentIntent |
| StripeService | [`createACHPaymentIntent()`](../../../TCV-Backend/app/Services/StripeService.php#L311) | 311 | public | `User $user`, `float $amount`, `int $credits`, `array $billingInfo` | PaymentIntent |
| StripeService | [`createBankTransferTransaction()`](../../../TCV-Backend/app/Services/StripeService.php#L339) | 339 | public | `User $user`, `float $amount`, `int $credits`, `array $billingInfo` | array |
| StripeService | [`getPaymentMethods()`](../../../TCV-Backend/app/Services/StripeService.php#L385) | 385 | public | `User $user` | array |
| StripeService | [`setDefaultPaymentMethod()`](../../../TCV-Backend/app/Services/StripeService.php#L393) | 393 | public | `User $user`, `string $paymentMethodId` | bool |
| StripeService | [`removePaymentMethod()`](../../../TCV-Backend/app/Services/StripeService.php#L423) | 423 | public | `User $user`, `string $paymentMethodId` | bool |
| StripeService | [`attachPaymentMethod()`](../../../TCV-Backend/app/Services/StripeService.php#L445) | 445 | public | `$customerId`, `$paymentMethodId` | — |

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
| TestExecutionService | [`__construct()`](../../../TCV-Backend/app/Services/TestExecutionService.php#L28) | 28 | public | `TestSectionTerminationService $terminationService`, `TestSectionProgressionService $progressionService`, `TestResultService $resultService`, `SecureImageService $secureImageService`, `AuditService $auditService` | — |
| TestExecutionService | [`submitAnswer()`](../../../TCV-Backend/app/Services/TestExecutionService.php#L49) | 49 | public | `int $testAnswerId`, `$submittedAnswer`, `bool $isAutoSubmit` | array |
| TestExecutionService | [`finalizeTestIfCompleted()`](../../../TCV-Backend/app/Services/TestExecutionService.php#L120) | 120 | public | `string $uniqueTestId` | void |
| TestExecutionService | [`getSessionDetails()`](../../../TCV-Backend/app/Services/TestExecutionService.php#L202) | 202 | public | `string $uniqueTestId` | array |
| TestExecutionService | [`getSectionPlatesWithProgress()`](../../../TCV-Backend/app/Services/TestExecutionService.php#L327) | 327 | public | `string $uniqueTestId`, `int $sectionId` | array |
| TestExecutionService | [`getPlateUrl()`](../../../TCV-Backend/app/Services/TestExecutionService.php#L405) | 405 | public | `string $uniqueTestId`, `int $testAnswerId` | ?string |
| TestExecutionService | [`resolveCanonicalTestId()`](../../../TCV-Backend/app/Services/TestExecutionService.php#L429) | 429 | private | `PatientTest $patientTest` | string |
| TestExecutionService | [`markTestInvitationAsUsed()`](../../../TCV-Backend/app/Services/TestExecutionService.php#L442) | 442 | private | `PatientTest $patientTest` | void |

### `app/Services/TestInvitationMailer.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| TestInvitationMailer | [`__construct()`](../../../TCV-Backend/app/Services/TestInvitationMailer.php#L21) | 21 | public | `EmailTemplateService $emailTemplateService` | — |
| TestInvitationMailer | [`send()`](../../../TCV-Backend/app/Services/TestInvitationMailer.php#L29) | 29 | public | `string $email`, `Test $test`, `string $token`, `string $verificationCode`, `$expiresAt`, `int $userId` | void |

### `app/Services/TestResultService.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| TestResultService | [`__construct()`](../../../TCV-Backend/app/Services/TestResultService.php#L17) | 17 | public | `ColorVisionDiagnosisService $diagnosisService` | — |
| TestResultService | [`generateTestResult()`](../../../TCV-Backend/app/Services/TestResultService.php#L27) | 27 | public | `PatientTest $patientTest` | array |

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

### `app/Support/EmailContent.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| EmailContent | [`linkify()`](../../../TCV-Backend/app/Support/EmailContent.php#L43) | 43 | public static | `?string $html` | string |
| EmailContent | [`styleAnchors()`](../../../TCV-Backend/app/Support/EmailContent.php#L64) | 64 | public static | `?string $html` | string |
| EmailContent | [`anchorPlaceholders()`](../../../TCV-Backend/app/Support/EmailContent.php#L113) | 113 | public static | `?string $html`, `array $placeholders` | string |
| EmailContent | [`cleanForAudit()`](../../../TCV-Backend/app/Support/EmailContent.php#L148) | 148 | public static | `?string $html` | string |
| EmailContent | [`mapTextNodes()`](../../../TCV-Backend/app/Support/EmailContent.php#L166) | 166 | private static | `?string $html`, `callable $transform` | string |
| EmailContent | [`linkifyTextNode()`](../../../TCV-Backend/app/Support/EmailContent.php#L212) | 212 | private static | `string $text` | string |
| EmailContent | [`escapeHref()`](../../../TCV-Backend/app/Support/EmailContent.php#L249) | 249 | private static | `string $url` | string |

### `app/Support/EmailHeader.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|

### `app/Support/EmailSignature.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|

### `app/Support/EmailTemplatePlaceholders.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| EmailTemplatePlaceholders | [`catalogue()`](../../../TCV-Backend/app/Support/EmailTemplatePlaceholders.php#L23) | 23 | public static | `string $type` | array |
| EmailTemplatePlaceholders | [`unlisted()`](../../../TCV-Backend/app/Support/EmailTemplatePlaceholders.php#L101) | 101 | private static | `string $type` | array |
| EmailTemplatePlaceholders | [`known()`](../../../TCV-Backend/app/Support/EmailTemplatePlaceholders.php#L114) | 114 | public static | `string $type` | array |
| EmailTemplatePlaceholders | [`required()`](../../../TCV-Backend/app/Support/EmailTemplatePlaceholders.php#L127) | 127 | public static | `string $type` | array |
| EmailTemplatePlaceholders | [`tokensIn()`](../../../TCV-Backend/app/Support/EmailTemplatePlaceholders.php#L146) | 146 | private static | `string $content` | array |
| EmailTemplatePlaceholders | [`unknownIn()`](../../../TCV-Backend/app/Support/EmailTemplatePlaceholders.php#L166) | 166 | public static | `string $content`, `string $type` | array |
| EmailTemplatePlaceholders | [`markupBrokenIn()`](../../../TCV-Backend/app/Support/EmailTemplatePlaceholders.php#L195) | 195 | public static | `string $content`, `string $type` | array |
| EmailTemplatePlaceholders | [`paddedIn()`](../../../TCV-Backend/app/Support/EmailTemplatePlaceholders.php#L215) | 215 | public static | `string $content`, `string $type` | array |
| EmailTemplatePlaceholders | [`messageForPadded()`](../../../TCV-Backend/app/Support/EmailTemplatePlaceholders.php#L239) | 239 | public static | `string $content`, `string $type` | ?string |
| EmailTemplatePlaceholders | [`suggestionFor()`](../../../TCV-Backend/app/Support/EmailTemplatePlaceholders.php#L262) | 262 | public static | `string $unknown`, `string $type` | ?string |
| EmailTemplatePlaceholders | [`messageForMarkupBroken()`](../../../TCV-Backend/app/Support/EmailTemplatePlaceholders.php#L284) | 284 | public static | `string $content`, `string $type` | ?string |
| EmailTemplatePlaceholders | [`messageForUnknown()`](../../../TCV-Backend/app/Support/EmailTemplatePlaceholders.php#L302) | 302 | public static | `string $content`, `string $type` | ?string |

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

### `database/seeders/AuditLogSeeder.php`

| Class | Method | Line | Vis | Params | Returns |
|---|---|---|---|---|---|
| AuditLogSeeder | [`scenarios()`](../../../TCV-Backend/database/seeders/AuditLogSeeder.php#L54) | 54 | private | — | array |
| AuditLogSeeder | [`run()`](../../../TCV-Backend/database/seeders/AuditLogSeeder.php#L149) | 149 | public | — | void |
| AuditLogSeeder | [`offsetFor()`](../../../TCV-Backend/database/seeders/AuditLogSeeder.php#L184) | 184 | private | `int $index` | Carbon |
| AuditLogSeeder | [`row()`](../../../TCV-Backend/database/seeders/AuditLogSeeder.php#L195) | 195 | private | `string $category`, `string $eventTitle`, `string $sensitivity`, `?array $actor`, `?array $target`, `string $status`, `Carbon $createdAt`, `array $details = []`, `array $changes = []` | array |

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
| EmailTemplateSeeder | [`run()`](../../../TCV-Backend/database/seeders/EmailTemplateSeeder.php#L11) | 11 | public | — | — |

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
| OrganizationsSeeder | [`getOrganizationData()`](../../../TCV-Backend/database/seeders/OrganizationsSeeder.php#L104) | 104 | private | — | array |

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

_Generated from source by `tools/extract.php` + `tools/extract-clients.php` + `tools/render.php` on 2026-09-12. Do not hand-edit — re-run the generator._
