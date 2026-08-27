# Model Index

**40 Eloquent models · 69 declared relationships.**

`SoftDeletes` matters here: a soft-deleted row still occupies unique indexes and still satisfies a
foreign key. Check the trait column before writing a uniqueness or re-create path.

| ID | Model | File:Line | Traits | Relations | Methods |
|---|---|---|---|---|---|
| `MODEL-001` | `AllowedTest` | [app/Models/AllowedTest.php:7](../../../TCV-Backend/app/Models/AllowedTest.php#L7) | — | — | 0 |
| `MODEL-002` | `Compliance` | [app/Models/Compliance.php:7](../../../TCV-Backend/app/Models/Compliance.php#L7) | — | — | 0 |
| `MODEL-003` | `Country` | [app/Models/Country.php:8](../../../TCV-Backend/app/Models/Country.php#L8) | `HasFactory` | `states`→State | 1 |
| `MODEL-004` | `Credit` | [app/Models/Credit.php:8](../../../TCV-Backend/app/Models/Credit.php#L8) | `HasFactory` | `user`→User | 1 |
| `MODEL-005` | `CreditConsume` | [app/Models/CreditConsume.php:8](../../../TCV-Backend/app/Models/CreditConsume.php#L8) | — | `user`→User | 4 |
| `MODEL-006` | `Credits` | [app/Models/Credits.php:9](../../../TCV-Backend/app/Models/Credits.php#L9) | `HasFactory`, `Searchable` | `user`→User, `transactions`→Transaction | 5 |
| `MODEL-007` | `DiscountCode` | [app/Models/DiscountCode.php:11](../../../TCV-Backend/app/Models/DiscountCode.php#L11) | `HasFactory`, `SoftDeletes` | `users`→User, `priceTiers`→PriceDetail, `creator`→User | 12 |
| `MODEL-008` | `DiscountCodePriceTier` | [app/Models/DiscountCodePriceTier.php:7](../../../TCV-Backend/app/Models/DiscountCodePriceTier.php#L7) | — | `discountCode`→DiscountCode, `priceTier`→PriceDetail | 2 |
| `MODEL-009` | `DiscountCodeUser` | [app/Models/DiscountCodeUser.php:7](../../../TCV-Backend/app/Models/DiscountCodeUser.php#L7) | — | `discountCode`→DiscountCode, `user`→User | 2 |
| `MODEL-010` | `EmailTemplate` | [app/Models/EmailTemplate.php:7](../../../TCV-Backend/app/Models/EmailTemplate.php#L7) | — | — | 5 |
| `MODEL-011` | `LmsDeliveryQueue` | [app/Models/LmsDeliveryQueue.php:9](../../../TCV-Backend/app/Models/LmsDeliveryQueue.php#L9) | `HasFactory` | `lmsSession`→LmsSession | 3 |
| `MODEL-012` | `LmsDeliveryToken` | [app/Models/LmsDeliveryToken.php:8](../../../TCV-Backend/app/Models/LmsDeliveryToken.php#L8) | `HasFactory` | `providerConfig`→LmsProviderConfig | 2 |
| `MODEL-013` | `LmsProviderConfig` | [app/Models/LmsProviderConfig.php:10](../../../TCV-Backend/app/Models/LmsProviderConfig.php#L10) | `HasFactory`, `SoftDeletes` | `organization`→Organization, `sessions`→LmsSession, `deliveryToken`→LmsDeliveryToken | 6 |
| `MODEL-014` | `LmsSession` | [app/Models/LmsSession.php:9](../../../TCV-Backend/app/Models/LmsSession.php#L9) | `HasFactory` | `organization`→Organization, `providerConfig`→LmsProviderConfig, `patient`→Patient, `patientTest`→PatientTest, `deliveryQueue`→LmsDeliveryQueue | 9 |
| `MODEL-015` | `Organization` | [app/Models/Organization.php:13](../../../TCV-Backend/app/Models/Organization.php#L13) | `HasApiTokens`, `HasFactory`, `Searchable` | `organizationType`→OrganizationType, `user`→User, `compliance`→Compliance, `privileges`→Privilege, `allowedTests`→AllowedTest, `config`→OrganizationConfig | 7 |
| `MODEL-016` | `OrganizationConfig` | [app/Models/OrganizationConfig.php:8](../../../TCV-Backend/app/Models/OrganizationConfig.php#L8) | `HasFactory` | `organization`→Organization | 1 |
| `MODEL-017` | `OrganizationPatientSession` | [app/Models/OrganizationPatientSession.php:8](../../../TCV-Backend/app/Models/OrganizationPatientSession.php#L8) | `HasFactory` | `organization`→Organization, `patient`→Patient, `test`→Test | 10 |
| `MODEL-018` | `OrganizationSettingsOption` | [app/Models/OrganizationSettingsOption.php:7](../../../TCV-Backend/app/Models/OrganizationSettingsOption.php#L7) | — | — | 0 |
| `MODEL-019` | `OrganizationType` | [app/Models/OrganizationType.php:7](../../../TCV-Backend/app/Models/OrganizationType.php#L7) | — | — | 0 |
| `MODEL-020` | `Patient` | [app/Models/Patient.php:9](../../../TCV-Backend/app/Models/Patient.php#L9) | `HasFactory`, `SoftDeletes` | `user`→User, `tests`→PatientTest | 2 |
| `MODEL-021` | `PatientTest` | [app/Models/PatientTest.php:8](../../../TCV-Backend/app/Models/PatientTest.php#L8) | `HasFactory` | `patient`→Patient, `test`→Test, `testInvitation`→TestInvitation | 9 |
| `MODEL-022` | `PriceDetail` | [app/Models/PriceDetail.php:8](../../../TCV-Backend/app/Models/PriceDetail.php#L8) | `HasFactory` | — | 0 |
| `MODEL-023` | `Privilege` | [app/Models/Privilege.php:7](../../../TCV-Backend/app/Models/Privilege.php#L7) | — | — | 0 |
| `MODEL-024` | `ProlificId` | [app/Models/ProlificId.php:8](../../../TCV-Backend/app/Models/ProlificId.php#L8) | `HasFactory` | `organization`→Organization, `patient`→Patient | 2 |
| `MODEL-025` | `RestrictedIp` | [app/Models/RestrictedIp.php:8](../../../TCV-Backend/app/Models/RestrictedIp.php#L8) | `HasFactory` | — | 0 |
| `MODEL-026` | `State` | [app/Models/State.php:8](../../../TCV-Backend/app/Models/State.php#L8) | `HasFactory` | `country`→Country | 1 |
| `MODEL-027` | `Test` | [app/Models/Test.php:8](../../../TCV-Backend/app/Models/Test.php#L8) | `HasFactory` | `testAnswers`→TestAnswer, `testConditions`→TestCondition, `testSections`→TestSection, `testSectionPlates`→TestSectionPlate, `assignedToUsers`→User | 8 |
| `MODEL-028` | `TestAnswer` | [app/Models/TestAnswer.php:8](../../../TCV-Backend/app/Models/TestAnswer.php#L8) | `HasFactory` | `test`→Test, `testSection`→TestSection, `testSectionPlate`→TestSectionPlate, `patient`→Patient | 7 |
| `MODEL-029` | `TestCondition` | [app/Models/TestCondition.php:8](../../../TCV-Backend/app/Models/TestCondition.php#L8) | `HasFactory` | `test`→Test | 1 |
| `MODEL-030` | `TestEmailTemplates` | [app/Models/TestEmailTemplates.php:8](../../../TCV-Backend/app/Models/TestEmailTemplates.php#L8) | `HasFactory` | — | 0 |
| `MODEL-031` | `TestInvitation` | [app/Models/TestInvitation.php:8](../../../TCV-Backend/app/Models/TestInvitation.php#L8) | `HasFactory` | `test`→Test, `user`→User | 4 |
| `MODEL-032` | `TestResumeToken` | [app/Models/TestResumeToken.php:7](../../../TCV-Backend/app/Models/TestResumeToken.php#L7) | — | `patientTest`→PatientTest | 2 |
| `MODEL-033` | `TestSection` | [app/Models/TestSection.php:8](../../../TCV-Backend/app/Models/TestSection.php#L8) | `HasFactory` | `test`→Test, `testSectionPlates`→TestSectionPlate | 3 |
| `MODEL-034` | `TestSectionPlate` | [app/Models/TestSectionPlate.php:8](../../../TCV-Backend/app/Models/TestSectionPlate.php#L8) | `HasFactory` | `test`→Test, `testSection`→TestSection | 2 |
| `MODEL-035` | `TestSession` | [app/Models/TestSession.php:7](../../../TCV-Backend/app/Models/TestSession.php#L7) | — | `testInvitation`→TestInvitation | 1 |
| `MODEL-036` | `Transaction` | [app/Models/Transaction.php:8](../../../TCV-Backend/app/Models/Transaction.php#L8) | `HasFactory` | `user`→User, `details`→TransactionDetail, `credits`→Credits | 4 |
| `MODEL-037` | `TransactionDetail` | [app/Models/TransactionDetail.php:8](../../../TCV-Backend/app/Models/TransactionDetail.php#L8) | `HasFactory` | `transaction`→Transaction, `discountCode`→DiscountCode | 2 |
| `MODEL-038` | `User` | [app/Models/User.php:15](../../../TCV-Backend/app/Models/User.php#L15) | `HasApiTokens`, `Notifiable`, `HasFactory`, `SoftDeletes`, `Searchable` | `stripeDetail`→UserStripeDetail, `assignedTests`→Test, `organization`→Organization, `country`→Country | 12 |
| `MODEL-039` | `UserEmailTemplate` | [app/Models/UserEmailTemplate.php:9](../../../TCV-Backend/app/Models/UserEmailTemplate.php#L9) | `HasFactory` | `user`→User | 2 |
| `MODEL-040` | `UserStripeDetail` | [app/Models/UserStripeDetail.php:8](../../../TCV-Backend/app/Models/UserStripeDetail.php#L8) | `HasFactory` | `user`→User | 1 |

---

_Generated from source by `tools/extract.php` + `tools/extract-clients.php` + `tools/render.php` on 2026-08-19. Do not hand-edit — re-run the generator._
