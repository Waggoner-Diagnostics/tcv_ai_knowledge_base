# Configuration

Laravel 12 layout: **no `app/Http/Kernel.php`**. Everything that used to live there is in
`bootstrap/app.php`.

## `bootstrap/app.php` — the wiring file

```php
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web:      routes/web.php,
        api:      routes/api.php,          // ← auto-prefixed 'api', 'api' middleware group
        commands: routes/console.php,
        health:   '/up',                   // ← a health endpoint exists at GET /up
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(RestrictIpMiddleware::class);        // GLOBAL
        $middleware->alias([
            'FlexibleAuthMiddleware' => FlexibleAuthMiddleware::class,
            'lms.status'             => LmsSessionStatusMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void { /* empty */ })
    ->withBindings([ExceptionHandler::class => Handler::class])   // ← custom handler
    ->create();
```

Four things to remember:
- **`GET /up` exists** as a health endpoint (nginx also suppresses `/health` and `/api/health` from the
  access log).
- `withExceptions` is **empty** — all exception behaviour is in the bound `Handler`
  ([ERROR_HANDLING.md](ERROR_HANDLING.md)).
- New middleware aliases go here. There is nowhere else.
- **No `->withSchedule(...)`.** Nothing is scheduled.

## `bootstrap/providers.php` — the list people forget

```php
return [
    App\Providers\AppServiceProvider::class,
    App\Providers\AuthServiceProvider::class,
    App\Providers\LmsServiceProvider::class,
];
```

☠️ **`EventServiceProvider` exists in `app/Providers/` and is not in this list**, so its `$listen` array
binds nothing ([ARCHITECTURE_REALITY.md](ARCHITECTURE_REALITY.md)). A new provider that is not added
here simply never runs — with no error.

## The config files that carry decisions

| File | The decision |
|---|---|
| `config/sanctum.php` | `'expiration' => 900` — **15-minute tokens**, hard-coded |
| `config/auth.php` | default guard `api` (sanctum); **two** password brokers sharing one table |
| `config/services.php` | Stripe, HubSpot, Turnstile, SES, Postmark, Resend, Slack — and doubles as `PaymentManager`'s provider registry |
| `config/filesystems.php` | `local` / `public` / `s3`; default is `local` |
| `config/logging.php` | default `stack` → `single` ([LOGGING.md](LOGGING.md)) |
| `config/app.php` | `frontend_url` and the derived **`frontend_app_url`** |

## `frontend_app_url`

```php
'frontend_url'     => env('FRONTEND_URL'),
'frontend_app_url' => rtrim( … FRONTEND_URL … ) . '/app',
```

Every patient-facing link is built from it. Unset, it becomes the host-less `"/app"` — guarded by
`entrypoint.sh` at boot and warned about by `AppServiceProvider` otherwise
([ENVIRONMENT.md](ENVIRONMENT.md)).

## `AppServiceProvider::boot()`

Only two things:
```php
Schema::defaultStringLength(191);      // legacy MySQL index-length workaround → every string col is 191
$this->warnIfFrontendAppUrlLooksInvalid();
```

## Config caching

`entrypoint.sh` runs `config:cache` **and** `route:cache` at boot. Consequences:

- **`env()` returns `null` outside config files** after caching. Read `config('…')` everywhere else.
- **A route change requires a container restart.** Locally, `php artisan route:clear`.
- A config change requires the same. `php artisan config:clear` locally.

## Message keys

`resources/lang/en/api.php` — **78 keys**, resolved by `ApiResponse` through `__()`. A missing key
renders as the literal key string in the response, which is how the typo'd `api.resticted` is visible to
clients today. Add a key whenever you add an `ApiResponse` call.
