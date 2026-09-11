# Event & Listener Index

**3 events · 4 listeners · 4 explicit `Event::listen` bindings.**

## Dispatch sites (`event(new …)`)

| Event | Dispatched from |
|---|---|
| `UserPasswordSet` | [app/Http/Controllers/AuthController.php:367](../../../TCV-Backend/app/Http/Controllers/AuthController.php#L367) |
| `PasswordReset` | [app/Http/Controllers/AuthController.php:382](../../../TCV-Backend/app/Http/Controllers/AuthController.php#L382) |
| `Verified` | [app/Http/Controllers/AuthController.php:443](../../../TCV-Backend/app/Http/Controllers/AuthController.php#L443) |
| `UserPasswordSet` | [app/Http/Controllers/Qa/QaAutomationController.php:194](../../../TCV-Backend/app/Http/Controllers/Qa/QaAutomationController.php#L194) |
| `TestSectionCompleted` | [app/Services/TestExecutionService.php:97](../../../TCV-Backend/app/Services/TestExecutionService.php#L97) |
| `TestCompleted` | [app/Services/TestExecutionService.php:172](../../../TCV-Backend/app/Services/TestExecutionService.php#L172) |

## Explicit bindings (`Event::listen`)

| Event | Listener | Bound in |
|---|---|---|
| `MessageSending` | `PrefixEmailSubject` | [app/Providers/AppServiceProvider.php:40](../../../TCV-Backend/app/Providers/AppServiceProvider.php#L40) |
| `DiagnosingHealth` | `` | [app/Providers/AppServiceProvider.php:56](../../../TCV-Backend/app/Providers/AppServiceProvider.php#L56) |
| `TestCompleted` | `HandleLmsNotificationOnCompletion` | [app/Providers/LmsServiceProvider.php:38](../../../TCV-Backend/app/Providers/LmsServiceProvider.php#L38) |
| `TestSectionCompleted` | `HandleLmsSectionProgressOnCompletion` | [app/Providers/LmsServiceProvider.php:39](../../../TCV-Backend/app/Providers/LmsServiceProvider.php#L39) |

> **Everything not listed under *explicit bindings* is wired by Laravel's automatic listener
> discovery** (any `app/Listeners` class whose `handle()` type-hints the event). `EventServiceProvider`
> is **not registered** in `bootstrap/providers.php`, so its `$listen` array binds nothing — see
> [ARCHITECTURE_REALITY.md](../ARCHITECTURE_REALITY.md).

---

_Generated from source by `tools/extract.php` + `tools/extract-clients.php` + `tools/render.php` on 2026-09-11. Do not hand-edit — re-run the generator._
