# Modules

There is no enforced module system — Laravel's default flat layout, with `app/Services/` sub-namespaces
as the only real grouping. These are the **conceptual** boundaries, and how tightly each one actually
holds.

| Module | Owns | Cohesion | Context pack |
|---|---|---|---|
| **Auth & identity** | `AuthController`, `PasswordController`, `FlexibleAuthMiddleware`, `User`, tokens | ⚠️ low — one 775-line controller does login, registration, verification, password, impersonation | [AUTH_CONTEXT](CONTEXT/AUTH_CONTEXT.md) |
| **Test authoring** | `Test`, `TestSection`, `TestSectionPlate`, `TestCondition` + their controllers | ✅ good | — |
| **Test execution** | `TestExecutionService`, `TestAssignmentService`, `TestSection{Progression,Termination}Service`, `TestResultService`, `ColorVisionDiagnosisService`, `SecureImageService` | ✅ **the best-factored area** | [TEST_EXECUTION_CONTEXT](CONTEXT/TEST_EXECUTION_CONTEXT.md) |
| **Patients** | `Patient`, `PatientController`, `OrganizationPatientController` | ⚠️ intake logic split across two controllers | [PATIENT_CONTEXT](CONTEXT/PATIENT_CONTEXT.md) |
| **Invitations & resume** | `TestInvitationController`, `TestResumeController`, 3 token models | ⚠️ resend logic also lives in `PatientController` | [INVITATION_CONTEXT](CONTEXT/INVITATION_CONTEXT.md) |
| **Credits** | `Credits`, `CreditConsume`, `CreditsController` | ⚠️ two models on one table; spends initiated from 2 other modules | [CREDITS_CONTEXT](CONTEXT/CREDITS_CONTEXT.md) |
| **Payments** | `PaymentManager`, `PaymentProviders/*`, `StripeService`, both payment controllers | ❌ **two parallel surfaces**, one deprecated and public | [BILLING_CONTEXT](CONTEXT/BILLING_CONTEXT.md) |
| **Discounts** | `DiscountCodeService`, `DiscountCodeController`, 3 models | ✅ good | [DISCOUNT_CONTEXT](CONTEXT/DISCOUNT_CONTEXT.md) |
| **Organisations** | `OrganizationController`, `Organization` + 6 config models | ⚠️ 876-line controller | [ORGANIZATION_CONTEXT](CONTEXT/ORGANIZATION_CONTEXT.md) |
| **LMS** | `app/Services/Lms/**` (11 classes), `LmsAdminController`, 4 models, 1 job, 2 listeners | ✅ **the only module with a real interface, a registry and tests** | [LMS_CONTEXT](CONTEXT/LMS_CONTEXT.md) |
| **Reporting** | `ReportController`, `Services/Reports/*`, `Exports/*`, `SuperAdminDashboardController` | ⚠️ export duplicates the query | [REPORTING_CONTEXT](CONTEXT/REPORTING_CONTEXT.md) |
| **Email templating** | `email_template` table, `EmailTemplateService`, `EmailTemplateRepository`, `TestEmailTemplateController`, `UserEmailTemplateController` | ❌ four sending mechanisms | [ARCHITECTURE_REALITY](ARCHITECTURE_REALITY.md) |
| **Platform config** | `RestrictedIpController`, `DropdownValuesController`, `PriceDetailController` | ✅ small and clear | — |
| **Audit** | `Services/Audit/AuditLogger`, `PricingAuditService`, `pricing_audit_logs` | ⚠️ **pricing only** — there is no general audit trail | — |

## The two coupling hot-spots

**1. Credits are spent from three modules.** `TestInvitationController` (send), `TestAssignmentService`
(assign), and refunded from `CreditsController` and `TestInvitationController`. The double-charge guard
is a single condition — `if (!$isEmailInvite)` — split across two files. Any change to how
`test_invitation_id` reaches `assignTest()` is a **billing** change.

**2. `unique_test_id` is the join key for three subsystems.** Test execution, LMS sessions and resume
tokens all key on that string, and none of them declares a relationship for it
([MODEL_RELATIONSHIP.md](MODEL_RELATIONSHIP.md)). Changing its format or generation breaks all three
silently.

## What "adding a module" looks like here

There is exactly one worked example of a well-bounded module: **LMS**. If you are adding a subsystem,
copy its shape:

```
app/Services/<Name>/
├── Contracts/<Name>ProviderInterface.php      ← the seam
├── <Name>Registry.php                         ← singleton, registered in a ServiceProvider
├── Providers/<Concrete>Provider.php
└── <Name>Service.php
app/Providers/<Name>ServiceProvider.php        ← register in bootstrap/providers.php
app/Jobs/…                                     ← if delivery is async
tests/Feature/<Name>/…                         ← the LMS module is the only one with these
```

And register the provider in `bootstrap/providers.php` — the file everyone forgets
([ARCHITECTURE_REALITY.md](ARCHITECTURE_REALITY.md)).
