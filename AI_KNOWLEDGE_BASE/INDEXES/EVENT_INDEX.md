# Event & Listener Index

**3 events · 3 listeners · 2 explicit `Event::listen` bindings.**

## Dispatch sites (`event(new …)`)

| Event | Dispatched from |
|---|---|
| `UserPasswordSet` | [app/Http/Controllers/AuthController.php:303](../../../TCV-Backend/app/Http/Controllers/AuthController.php#L303) |
| `PasswordReset` | [app/Http/Controllers/AuthController.php:318](../../../TCV-Backend/app/Http/Controllers/AuthController.php#L318) |
| `Verified` | [app/Http/Controllers/AuthController.php:379](../../../TCV-Backend/app/Http/Controllers/AuthController.php#L379) |
| `TestSectionCompleted` | [app/Services/TestExecutionService.php:97](../../../TCV-Backend/app/Services/TestExecutionService.php#L97) |
| `TestCompleted` | [app/Services/TestExecutionService.php:172](../../../TCV-Backend/app/Services/TestExecutionService.php#L172) |

## Explicit bindings (`Event::listen`)

| Event | Listener | Bound in |
|---|---|---|
| `TestCompleted` | `HandleLmsNotificationOnCompletion` | [app/Providers/LmsServiceProvider.php:38](../../../TCV-Backend/app/Providers/LmsServiceProvider.php#L38) |
| `TestSectionCompleted` | `HandleLmsSectionProgressOnCompletion` | [app/Providers/LmsServiceProvider.php:39](../../../TCV-Backend/app/Providers/LmsServiceProvider.php#L39) |

> **Everything not listed under *explicit bindings* is wired by Laravel's automatic listener
> discovery** (any `app/Listeners` class whose `handle()` type-hints the event). `EventServiceProvider`
> is **not registered** in `bootstrap/providers.php`, so its `$listen` array binds nothing — see
> [ARCHITECTURE_REALITY.md](../ARCHITECTURE_REALITY.md).

---

_Generated from source by `tools/extract.php` + `tools/extract-clients.php` + `tools/render.php` on 2026-09-03. Do not hand-edit — re-run the generator._
