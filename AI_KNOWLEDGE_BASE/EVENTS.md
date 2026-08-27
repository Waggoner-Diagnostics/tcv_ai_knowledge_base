# Events & Listeners

**3 events, 3 listeners.** Generated view: [INDEXES/EVENT_INDEX.md](INDEXES/EVENT_INDEX.md).

| Event | Dispatched from | Listener | How it is wired |
|---|---|---|---|
| `TestSectionCompleted` | `TestExecutionService.php:97` | `HandleLmsSectionProgressOnCompletion` | **explicit** `Event::listen` in `LmsServiceProvider::boot()` |
| `TestCompleted` | `TestExecutionService.php:172` | `HandleLmsNotificationOnCompletion` | **explicit** `Event::listen` in `LmsServiceProvider::boot()` |
| `UserPasswordSet` | `AuthController.php:303` | `SendAfterPasswordReset` | **auto-discovery** (the `handle()` type-hint) |

Two Laravel framework events are also dispatched and rely on framework listeners:
`PasswordReset` (`AuthController.php:318`) and `Verified` (`AuthController.php:379`).

## ☠️ `EventServiceProvider` is not registered

```php
// app/Providers/EventServiceProvider.php
protected $listen = [ UserPasswordSet::class => [SendAfterPasswordReset::class] ];
```

`bootstrap/providers.php` lists only `AppServiceProvider`, `AuthServiceProvider`, `LmsServiceProvider`.
**That `$listen` array binds nothing.**

`SendAfterPasswordReset` fires anyway because Laravel 11+ **auto-discovers** listeners in
`app/Listeners` whose `handle()` type-hints an event — which it does.

**What this means when you add an event:**
- Type-hint the event in the listener's `handle()` → it is discovered. Done.
- Or add an explicit `Event::listen(...)` in a **registered** provider, the way `LmsServiceProvider`
  does.
- **Do not** add a mapping to `EventServiceProvider` — it is inert. And do not "fix" it by registering
  the provider without first checking for double-binding: auto-discovery would then fire the same
  listener twice.

## What the listeners actually do

```
TestCompleted        → HandleLmsNotificationOnCompletion   → LmsDeliveryService::enqueueCompletion()
TestSectionCompleted → HandleLmsSectionProgressOnCompletion → LmsDeliveryService::enqueueSectionProgress()
UserPasswordSet      → SendAfterPasswordReset               → if the user owns an Organization,
                                                              notify OrganizationTestUrlNotification
```

Both LMS listeners are a **no-op for non-LMS tests** — they look up an `LmsSession` and return if there
isn't one. Enqueueing is idempotent at the queue-row level
([CONTEXT/LMS_CONTEXT.md](CONTEXT/LMS_CONTEXT.md)).

## ☠️ Transaction ordering

`TestExecutionService::finalizeTestIfCompleted()` fires `TestCompleted` **after** its
`DB::transaction()` commits — correct, and deliberate. But `TestController::performTest()` wraps the
whole call in its **own** outer transaction. Nested transactions are savepoints, so a rollback in the
outer one discards the inner commit **while the event has already fired**. An outer failure after
finalisation can therefore deliver an LMS completion for a test that was rolled back.

## Not present

No queued listeners (`ShouldQueue` is imported by `SendAfterPasswordReset` but **not implemented**), no
event subscribers, no broadcasting (`BROADCAST_CONNECTION=log`), no model observers.
