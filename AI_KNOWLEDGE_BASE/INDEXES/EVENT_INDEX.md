# Event & Listener Index

**3 events · 4 listeners · 4 explicit `Event::listen` bindings.**

## Dispatch sites (`event(new …)`)

| Event | Dispatched from |
|---|---|
| `UserPasswordSet` | [app/Http/Controllers/AuthController.php:420](../../../TCV-Backend/app/Http/Controllers/AuthController.php#L420) |
| `PasswordReset` | [app/Http/Controllers/AuthController.php:452](../../../TCV-Backend/app/Http/Controllers/AuthController.php#L452) |
| `Verified` | [app/Http/Controllers/AuthController.php:526](../../../TCV-Backend/app/Http/Controllers/AuthController.php#L526) |
| `UserPasswordSet` | [app/Http/Controllers/Qa/QaAutomationController.php:194](../../../TCV-Backend/app/Http/Controllers/Qa/QaAutomationController.php#L194) |
| `TestSectionCompleted` | [app/Services/TestExecutionService.php:103](../../../TCV-Backend/app/Services/TestExecutionService.php#L103) |
| `TestCompleted` | [app/Services/TestExecutionService.php:178](../../../TCV-Backend/app/Services/TestExecutionService.php#L178) |

## Explicit bindings (`Event::listen`)

| Event | Listener | Bound in |
|---|---|---|
| `MessageSending` | `PrefixEmailSubject` | [app/Providers/AppServiceProvider.php:60](../../../TCV-Backend/app/Providers/AppServiceProvider.php#L60) |
| `DiagnosingHealth` | `` | [app/Providers/AppServiceProvider.php:76](../../../TCV-Backend/app/Providers/AppServiceProvider.php#L76) |
| `TestCompleted` | `HandleLmsNotificationOnCompletion` | [app/Providers/LmsServiceProvider.php:38](../../../TCV-Backend/app/Providers/LmsServiceProvider.php#L38) |
| `TestSectionCompleted` | `HandleLmsSectionProgressOnCompletion` | [app/Providers/LmsServiceProvider.php:39](../../../TCV-Backend/app/Providers/LmsServiceProvider.php#L39) |

> **Everything not listed under *explicit bindings* is wired by Laravel's automatic listener
> discovery** (any `app/Listeners` class whose `handle()` type-hints the event). `EventServiceProvider`
> is **not registered** in `bootstrap/providers.php`, so its `$listen` array binds nothing — see
> [ARCHITECTURE_REALITY.md](../ARCHITECTURE_REALITY.md).

---

_Generated from source by `tools/extract.php` + `tools/extract-clients.php` + `tools/render.php` on 2026-09-21. Do not hand-edit — re-run the generator._
