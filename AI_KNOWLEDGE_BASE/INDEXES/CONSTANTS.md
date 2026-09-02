# Constants Index

Class constants are this codebase's stand-in for most enums. The ones that decide behaviour are
`User::SUPER_ADMIN|CUSTOMER|ORGANIZATION` and the `TestConstants` / `HttpStatus` sets — misreading
those is the recurring source of bugs. See also [ENUM_INDEX.md](ENUM_INDEX.md).


### `DiscountCodeReportExport` — `app/Exports/DiscountCodeReportExport.php`

| Constant | Value | Line |
|---|---|---|
| `BLUE_DARK` | `'1D579B'` | [18](../../../TCV-Backend/app/Exports/DiscountCodeReportExport.php#L18) |
| `BLUE_MID` | `'317BBC'` | [19](../../../TCV-Backend/app/Exports/DiscountCodeReportExport.php#L19) |
| `BLUE_LIGHT` | `'DBEAFE'` | [20](../../../TCV-Backend/app/Exports/DiscountCodeReportExport.php#L20) |
| `ROW_ALT` | `'F4F7FB'` | [21](../../../TCV-Backend/app/Exports/DiscountCodeReportExport.php#L21) |
| `GREEN_TEXT` | `'166534'` | [22](../../../TCV-Backend/app/Exports/DiscountCodeReportExport.php#L22) |
| `BORDER_CLR` | `'E5E7EB'` | [23](../../../TCV-Backend/app/Exports/DiscountCodeReportExport.php#L23) |
| `GRAY_TEXT` | `'6B7280'` | [24](../../../TCV-Backend/app/Exports/DiscountCodeReportExport.php#L24) |
| `WHITE` | `'FFFFFF'` | [25](../../../TCV-Backend/app/Exports/DiscountCodeReportExport.php#L25) |
| `DARK_TEXT` | `'1F2937'` | [26](../../../TCV-Backend/app/Exports/DiscountCodeReportExport.php#L26) |
| `COLUMNS` | `[…]` | [29](../../../TCV-Backend/app/Exports/DiscountCodeReportExport.php#L29) |

### `TestResumeController` — `app/Http/Controllers/TestResumeController.php`

| Constant | Value | Line |
|---|---|---|
| `TOKEN_EXPIRY_DAYS` | `7` | [17](../../../TCV-Backend/app/Http/Controllers/TestResumeController.php#L17) |

### `FlexibleAuthMiddleware` — `app/Http/Middleware/FlexibleAuthMiddleware.php`

| Constant | Value | Line |
|---|---|---|
| `CONTEXT_KEY` | `'auth_context'` | [22](../../../TCV-Backend/app/Http/Middleware/FlexibleAuthMiddleware.php#L22) |
| `TIER_SANCTUM` | `'sanctum'` | [24](../../../TCV-Backend/app/Http/Middleware/FlexibleAuthMiddleware.php#L24) |
| `TIER_TEST_SESSION` | `'test_session'` | [25](../../../TCV-Backend/app/Http/Middleware/FlexibleAuthMiddleware.php#L25) |
| `TIER_LMS_SESSION` | `'lms_session'` | [26](../../../TCV-Backend/app/Http/Middleware/FlexibleAuthMiddleware.php#L26) |
| `TIER_ORG_PATIENT_SESSION` | `'org_patient_session'` | [27](../../../TCV-Backend/app/Http/Middleware/FlexibleAuthMiddleware.php#L27) |

### `RestrictIpMiddleware` — `app/Http/Middleware/RestrictIpMiddleware.php`

| Constant | Value | Line |
|---|---|---|
| `ERROR_CODE` | `'IP_RESTRICTED'` | [11](../../../TCV-Backend/app/Http/Middleware/RestrictIpMiddleware.php#L11) |

### `CreateTestRequest` — `app/Http/Requests/CreateTestRequest.php`

| Constant | Value | Line |
|---|---|---|
| `EYE_TESTED_BINOCULAR` | `'OU'` | [10](../../../TCV-Backend/app/Http/Requests/CreateTestRequest.php#L10) |
| `EYE_TESTED_RIGHT` | `'OD'` | [11](../../../TCV-Backend/app/Http/Requests/CreateTestRequest.php#L11) |
| `EYE_TESTED_LEFT` | `'OS'` | [12](../../../TCV-Backend/app/Http/Requests/CreateTestRequest.php#L12) |
| `EYE_TESTED_BOTH` | `'both'` | [13](../../../TCV-Backend/app/Http/Requests/CreateTestRequest.php#L13) |

### `PatientAddRequest` — `app/Http/Requests/PatientAddRequest.php`

| Constant | Value | Line |
|---|---|---|
| `GENDER` | — | [11](../../../TCV-Backend/app/Http/Requests/PatientAddRequest.php#L11) |

### `PatientUpdateRequest` — `app/Http/Requests/PatientUpdateRequest.php`

| Constant | Value | Line |
|---|---|---|
| `GENDER` | — | [11](../../../TCV-Backend/app/Http/Requests/PatientUpdateRequest.php#L11) |

### `CreditConsume` — `app/Models/CreditConsume.php`

| Constant | Value | Line |
|---|---|---|
| `EVENT_TEST_INVITATION` | `'test_invitation'` | [23](../../../TCV-Backend/app/Models/CreditConsume.php#L23) |
| `EVENT_TEST_COMPLETION` | `'test_completion'` | [24](../../../TCV-Backend/app/Models/CreditConsume.php#L24) |

### `Credits` — `app/Models/Credits.php`

| Constant | Value | Line |
|---|---|---|
| `SOURCE_MANUAL` | `0` | [16](../../../TCV-Backend/app/Models/Credits.php#L16) |
| `SOURCE_PURCHASE` | `1` | [17](../../../TCV-Backend/app/Models/Credits.php#L17) |
| `SOURCE_REVOKED` | `2` | [18](../../../TCV-Backend/app/Models/Credits.php#L18) |

### `LmsDeliveryQueue` — `app/Models/LmsDeliveryQueue.php`

| Constant | Value | Line |
|---|---|---|
| `STATUS_PENDING` | `'pending'` | [17](../../../TCV-Backend/app/Models/LmsDeliveryQueue.php#L17) |
| `STATUS_IN_FLIGHT` | `'in_flight'` | [18](../../../TCV-Backend/app/Models/LmsDeliveryQueue.php#L18) |
| `STATUS_DELIVERED` | `'delivered'` | [19](../../../TCV-Backend/app/Models/LmsDeliveryQueue.php#L19) |
| `STATUS_FAILED` | `'failed'` | [20](../../../TCV-Backend/app/Models/LmsDeliveryQueue.php#L20) |
| `STATUS_DEAD_LETTER` | `'dead_letter'` | [21](../../../TCV-Backend/app/Models/LmsDeliveryQueue.php#L21) |

### `LmsProviderConfig` — `app/Models/LmsProviderConfig.php`

| Constant | Value | Line |
|---|---|---|
| `TYPE_CORNERSTONE` | `'cornerstone'` | [27](../../../TCV-Backend/app/Models/LmsProviderConfig.php#L27) |
| `TYPE_HEALTHSTREAM` | `'healthstream'` | [28](../../../TCV-Backend/app/Models/LmsProviderConfig.php#L28) |
| `TYPE_GENERIC_WEBHOOK` | `'generic_webhook'` | [29](../../../TCV-Backend/app/Models/LmsProviderConfig.php#L29) |
| `TYPE_SCORM` | `'scorm'` | [30](../../../TCV-Backend/app/Models/LmsProviderConfig.php#L30) |

### `LmsSession` — `app/Models/LmsSession.php`

| Constant | Value | Line |
|---|---|---|
| `STATUS_LAUNCHED` | `'launched'` | [18](../../../TCV-Backend/app/Models/LmsSession.php#L18) |
| `STATUS_IDENTITY_RESOLVED` | `'identity_resolved'` | [19](../../../TCV-Backend/app/Models/LmsSession.php#L19) |
| `STATUS_FORM_SUBMITTED` | `'form_submitted'` | [20](../../../TCV-Backend/app/Models/LmsSession.php#L20) |
| `STATUS_TEST_ASSIGNED` | `'test_assigned'` | [21](../../../TCV-Backend/app/Models/LmsSession.php#L21) |
| `STATUS_TEST_COMPLETED` | `'test_completed'` | [22](../../../TCV-Backend/app/Models/LmsSession.php#L22) |
| `STATUS_REPORTED` | `'reported'` | [23](../../../TCV-Backend/app/Models/LmsSession.php#L23) |
| `STATUS_FAILED` | `'failed'` | [24](../../../TCV-Backend/app/Models/LmsSession.php#L24) |
| `TERMINAL_STATUSES` | `[…]` | [27](../../../TCV-Backend/app/Models/LmsSession.php#L27) |

### `Patient` — `app/Models/Patient.php`

| Constant | Value | Line |
|---|---|---|
| `GENDER_MALE` | `1` | [13](../../../TCV-Backend/app/Models/Patient.php#L13) |
| `GENDER_FEMALE` | `2` | [14](../../../TCV-Backend/app/Models/Patient.php#L14) |
| `GENDER_INTERSEX` | `3` | [15](../../../TCV-Backend/app/Models/Patient.php#L15) |
| `GENDERS` | `[…]` | [23](../../../TCV-Backend/app/Models/Patient.php#L23) |

### `PatientTest` — `app/Models/PatientTest.php`

| Constant | Value | Line |
|---|---|---|
| `STATUS_PENDING` | `'pending'` | [14](../../../TCV-Backend/app/Models/PatientTest.php#L14) |
| `STATUS_INPROGRESS` | `'inprogress'` | [15](../../../TCV-Backend/app/Models/PatientTest.php#L15) |
| `STATUS_COMPLETED` | `'completed'` | [16](../../../TCV-Backend/app/Models/PatientTest.php#L16) |
| `STATUS_ABANDONED` | `'abandoned'` | [17](../../../TCV-Backend/app/Models/PatientTest.php#L17) |

### `Test` — `app/Models/Test.php`

| Constant | Value | Line |
|---|---|---|
| `STATUS_ACTIVE` | `1` | [23](../../../TCV-Backend/app/Models/Test.php#L23) |
| `STATUS_INACTIVE` | `0` | [24](../../../TCV-Backend/app/Models/Test.php#L24) |
| `TEST_LAYOUTS` | `[…]` | [26](../../../TCV-Backend/app/Models/Test.php#L26) |

### `TestAnswer` — `app/Models/TestAnswer.php`

| Constant | Value | Line |
|---|---|---|
| `SKIP_TIMEOUT` | `'timeout'` | [13](../../../TCV-Backend/app/Models/TestAnswer.php#L13) |
| `SKIP_SECTION_TERMINATED` | `'section_terminated'` | [14](../../../TCV-Backend/app/Models/TestAnswer.php#L14) |
| `SKIP_PRIOR_SECTION_PASSED` | `'prior_section_passed'` | [15](../../../TCV-Backend/app/Models/TestAnswer.php#L15) |

### `TestInvitation` — `app/Models/TestInvitation.php`

| Constant | Value | Line |
|---|---|---|
| `INVITATION_VALIDITY_DAYS` | `7` | [12](../../../TCV-Backend/app/Models/TestInvitation.php#L12) |

### `TestSection` — `app/Models/TestSection.php`

| Constant | Value | Line |
|---|---|---|
| `CATEGORIES` | `[…]` | [13](../../../TCV-Backend/app/Models/TestSection.php#L13) |

### `User` — `app/Models/User.php`

| Constant | Value | Line |
|---|---|---|
| `SUPER_ADMIN` | `1` | [24](../../../TCV-Backend/app/Models/User.php#L24) |
| `CUSTOMER` | `2` | [25](../../../TCV-Backend/app/Models/User.php#L25) |
| `ORGANIZATION` | `4` | [26](../../../TCV-Backend/app/Models/User.php#L26) |

### `UserEmailTemplate` — `app/Models/UserEmailTemplate.php`

| Constant | Value | Line |
|---|---|---|
| `TYPE_TEST_LINK` | `'test_link'` | [33](../../../TCV-Backend/app/Models/UserEmailTemplate.php#L33) |
| `TYPE_ORG_TEST_LINK` | `'org_test_link'` | [34](../../../TCV-Backend/app/Models/UserEmailTemplate.php#L34) |

### `PricingAuditService` — `app/Services/Audit/PricingAuditService.php`

| Constant | Value | Line |
|---|---|---|
| `TABLE` | `'pricing_audit_logs'` | [7](../../../TCV-Backend/app/Services/Audit/PricingAuditService.php#L7) |
| `ENTITY_COLUMN` | `'pricing_id'` | [8](../../../TCV-Backend/app/Services/Audit/PricingAuditService.php#L8) |
| `ACTION` | `'UPDATE'` | [9](../../../TCV-Backend/app/Services/Audit/PricingAuditService.php#L9) |

### `HubSpotService` — `app/Services/HubSpotService.php`

| Constant | Value | Line |
|---|---|---|
| `BASE_URL` | `'https://api.hubapi.com'` | [12](../../../TCV-Backend/app/Services/HubSpotService.php#L12) |

### `TestAssignmentService` — `app/Services/TestAssignmentService.php`

| Constant | Value | Line |
|---|---|---|
| `DEFAULT_BATCH_SIZE` | `3` | [16](../../../TCV-Backend/app/Services/TestAssignmentService.php#L16) |

### `EmailContent` — `app/Support/EmailContent.php`

| Constant | Value | Line |
|---|---|---|
| `LINK_STYLE` | `'color:#007bff;text-decoration:underline;'` | [8](../../../TCV-Backend/app/Support/EmailContent.php#L8) |
| `BUTTON_STYLE` | `'display:inline-block;padding:12px 24px;background-color:#007bff;color:#fff;text-decoration:none;border-radius:4px;'` | [12](../../../TCV-Backend/app/Support/EmailContent.php#L12) |
| `TRAILING_PUNCTUATION` | `'.,:!?'` | [18](../../../TCV-Backend/app/Support/EmailContent.php#L18) |
| `URL_PATTERN` | `'/https?:\/\/(?:&(?:amp|#0*38);|(?!&[a-zA-Z#][a-zA-Z0-9]{0,30};)[^\s<>"'])+/i'` | [23](../../../TCV-Backend/app/Support/EmailContent.php#L23) |
| `RAW_TEXT_TAGS` | `'style|script'` | [27](../../../TCV-Backend/app/Support/EmailContent.php#L27) |
| `BLOCK_TAGS` | `'p|div|table|thead|tbody|tfoot|tr|td|th|ul|ol|li|dl|dt|dd|h[1-6]|blockquote|section|article|header|footer|main|body|hr|form|pre|center'` | [31](../../../TCV-Backend/app/Support/EmailContent.php#L31) |

### `EmailSignature` — `app/Support/EmailSignature.php`

| Constant | Value | Line |
|---|---|---|
| `HTML` | `'<p style="margin:0;line-height:1.6;">Best Regards,<br><strong>The Waggoner Diagnostics Team</strong><br>Email: <a href="mailto:sales@waggonerdiagnostics.com" style="color:#007bff;text-decoration:none;">sales@waggonerdiagnostics.com</a><br>Phone: <a href="tel:+19493961694" style="color:#007bff;text-decoration:none;">+1 (949) 396-1694</a></p>'` | [13](../../../TCV-Backend/app/Support/EmailSignature.php#L13) |
| `LEGACY_HTML` | `'<p>Best Regards,
                The Waggoner Diagnostics Team
                Email: sales@waggonerdiagnostics.com
                Phone: +1 (949) 396-1694</p>'` | [20](../../../TCV-Backend/app/Support/EmailSignature.php#L20) |

### `HttpStatus` — `app/Support/HttpStatus.php`

| Constant | Value | Line |
|---|---|---|
| `OK` | `200` | [8](../../../TCV-Backend/app/Support/HttpStatus.php#L8) |
| `CREATED` | `201` | [9](../../../TCV-Backend/app/Support/HttpStatus.php#L9) |
| `ACCEPTED` | `202` | [10](../../../TCV-Backend/app/Support/HttpStatus.php#L10) |
| `NO_CONTENT` | `204` | [11](../../../TCV-Backend/app/Support/HttpStatus.php#L11) |
| `BAD_REQUEST` | `400` | [14](../../../TCV-Backend/app/Support/HttpStatus.php#L14) |
| `UNAUTHORIZED` | `401` | [15](../../../TCV-Backend/app/Support/HttpStatus.php#L15) |
| `FORBIDDEN` | `403` | [16](../../../TCV-Backend/app/Support/HttpStatus.php#L16) |
| `NOT_FOUND` | `404` | [17](../../../TCV-Backend/app/Support/HttpStatus.php#L17) |
| `CONFLICT` | `409` | [18](../../../TCV-Backend/app/Support/HttpStatus.php#L18) |
| `UNPROCESSABLE` | `422` | [19](../../../TCV-Backend/app/Support/HttpStatus.php#L19) |
| `SERVER_ERROR` | `500` | [22](../../../TCV-Backend/app/Support/HttpStatus.php#L22) |

### `TestConstants` — `app/Support/TestConstants.php`

| Constant | Value | Line |
|---|---|---|
| `DEFAULT_TEST_TITLE` | `'Adult Diagnostic'` | [7](../../../TCV-Backend/app/Support/TestConstants.php#L7) |

---

_Generated from source by `tools/extract.php` + `tools/extract-clients.php` + `tools/render.php` on 2026-09-02. Do not hand-edit — re-run the generator._
