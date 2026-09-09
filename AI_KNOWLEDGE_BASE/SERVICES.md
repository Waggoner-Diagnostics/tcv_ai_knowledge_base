# Services

**35 classes under `app/Services/`.** This is where the business logic lives — start here, not in the
controller. Full list with line numbers: [INDEXES/CLASS_INDEX.md](INDEXES/CLASS_INDEX.md) (`SVC-nnn`).

## By subsystem

| Group | Classes |
|---|---|
| **Test execution** | `TestExecutionService` · `TestAssignmentService` · `TestSectionProgressionService` · `TestSectionTerminationService` · `TestResultService` · `TestService` · `ColorVisionDiagnosisService` · `PatientTestTransformer` · `SecureImageService` |
| **LMS** (`Lms/`) | `LmsLaunchService` · `LmsDeliveryService` · `LmsProviderRegistry` · `XapiStatementBuilder` · `Providers/CornerstoneProvider` · `Providers/GenericWebhookProvider` · `Contracts/LmsProviderInterface` · `Contracts/LmsIdentity` · `Contracts/LmsLaunchContext` · `Contracts/DeliveryResult` |
| **Payments** (`PaymentProviders/`) | `PaymentManager` · `PaymentProviderInterface` · `BasePaymentProvider` · `StripeProvider` · `StripeService` |
| **Commerce** | `DiscountCodeService` |
| **Reports** (`Reports/`) | `UserTestsReportService` · `DiscountCodeReportService` |
| **Audit** (`Audit/`) | `AuditLogger` · `PricingAuditService` |
| **Email** | `EmailTemplateService`, `TestInvitationMailer` (`ws-404` — renders and sends one invitation; the queue job and the resend path share it) — plus `App\Support\EmailContent` / `EmailSignature`, which are **not** services: static, dependency-free string helpers shared by the controllers, the notification, the seeder and three data migrations (`ws-373`) |
| **Integrations** | `HubSpotService` · `TurnstileService` |

## Instantiation patterns — three of them

| Pattern | Example | Notes |
|---|---|---|
| **Constructor injection** (dominant) | `TestExecutionService` takes 4 services; `PaymentController` takes `DiscountCodeService` | ✅ follow this |
| **Container singleton** | `LmsProviderRegistry`, `LmsLaunchService`, `LmsDeliveryService`, `XapiStatementBuilder` — bound in `LmsServiceProvider::register()` | needed because the registry holds state |
| **Static class** | `PaymentManager::*`, `AuditLogger::log()`, `PricingAuditService::createLog()` | ⚠️ `PaymentManager`'s static state is a real bug — see below |

One deliberate escape hatch exists: `app(TestAssignmentService::class)` inside
`TestExecutionService::getSessionDetails()`, to avoid a constructor cycle. Do not multiply it.

## The three seams worth using

1. **`LmsProviderInterface` + `LmsProviderRegistry`** — the model for any pluggable integration.
2. **`PaymentProviderInterface` + `BasePaymentProvider`** — `StripeProvider` shows the shape;
   `BasePaymentProvider::createTransactionRecord()` is what turns a payment into `Transaction` +
   `TransactionDetail` + a `Credits` grant.
3. **`Searchable`** (a trait, not a service) — how `?search=` is implemented on list endpoints. Used by
   `Credits`, `DiscountCode`, `User`, `Patient`. Reuse it rather than hand-writing `LIKE`.

## ☠️ Traps

1. **`PaymentManager` latches.** `self::$initialized` is static and `initialize()` returns early once
   set, so the first call in a process decides which providers exist for the rest of it. Under php-fpm
   this mostly hides; under a queue worker or Octane it is a cross-request bug.
   [CONTEXT/BILLING_CONTEXT.md](CONTEXT/BILLING_CONTEXT.md).
2. **`ColorVisionDiagnosisService` is a port of frontend JS** and its source is still in `TCV-Frontend`
   (dead). Change the PHP; delete the JS ([FULLSTACK_MAP.md](FULLSTACK_MAP.md)).
3. **`SecureImageService::revokeAccess()` only clears a cache key** — the S3 URL stays live for 900 s
   ([S-11](SECURITY.md#s-11--revokeaccess-does-not-revoke-s3-access)).
4. **`AuditLogger` (the old one) is generic but used once.** Its only caller is `PricingAuditService`,
   writing to `pricing_audit_logs` — unrelated to the general audit trail below; both still exist
   side by side ([CONTEXT/AUDIT_TRAIL_BACKEND_CONTEXT.md](CONTEXT/AUDIT_TRAIL_BACKEND_CONTEXT.md) §9, Q3: migrate later, not yet).
5. **A general audit trail exists as of `feat/ui-audit-trail` (not yet in `develop`)** —
   `App\Services\Audit\AuditService` is the single write chokepoint for the `audit_logs` table
   (masking, role resolution, GeoIP/browser enrichment, session correlation), backed by
   `AuditEventCatalog` (61 defined events across 8 categories) and read via `AuditLogController`
   (Super Admin only). **Coverage is a first slice, not comprehensive**: only `AuthController::login()`/
   `logout()` actually call it today (login success/failed in its several forms, and logout). Every
   other module's events (accounts/orgs, billing, credits, test activity, patients, settings) exist
   only as catalog entries with no call site yet — do not assume those actions are recorded until
   their instrumentation lands. See [CONTEXT/AUDIT_TRAIL_BACKEND_CONTEXT.md](CONTEXT/AUDIT_TRAIL_BACKEND_CONTEXT.md) for the full design.
6. **`TestService` vs `TestExecutionService` vs `TestAssignmentService`** are three different things with
   similar names. `TestService` is the thin one used by `PatientController`; the other two own the real
   flow.
7. **Services throw; controllers catch.** Nearly every service method lets exceptions propagate and the
   controller converts them to `ApiResponse::error(…SERVER_ERROR…)`. That means a service exception is
   indistinguishable from a crash at the API boundary ([ERROR_HANDLING.md](ERROR_HANDLING.md)).

## Adding a service

1. Constructor-inject its dependencies; let Laravel resolve it.
2. Bind it in a ServiceProvider **only** if it holds state or needs configuration
   (`LmsServiceProvider` is the example) — and register that provider in `bootstrap/providers.php`.
3. Keep it free of `request()` and `auth()` where you can — `TestExecutionService` reaches for
   `request()->ip()` inside `finalizeTestIfCompleted()`, which is exactly what makes that method hard to
   test.
4. Throw; do not return error arrays. The controller's `catch` is the convention.
