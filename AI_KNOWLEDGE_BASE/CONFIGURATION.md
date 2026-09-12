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
        $middleware->prepend(AddRequestId::class);               // GLOBAL, runs first
        $middleware->append(RestrictIpMiddleware::class);        // GLOBAL
        $middleware->alias([
            'FlexibleAuthMiddleware' => FlexibleAuthMiddleware::class,
            'lms.status'             => LmsSessionStatusMiddleware::class,
        ]);
        // only when TRUSTED_PROXIES is a non-empty CIDR list
        $middleware->trustProxies(at: $trustedProxies, headers: ...X_FORWARDED_*);
    })
    ->withExceptions(function (Exceptions $exceptions): void { /* empty */ })
    ->withBindings([ExceptionHandler::class => Handler::class])   // ← custom handler
    ->create();
```

Five things to remember:
- **`GET /up` exists** as a health endpoint (nginx also suppresses `/health` and `/api/health` from the
  access log).
- `withExceptions` is **empty** — all exception behaviour is in the bound `Handler`
  ([ERROR_HANDLING.md](ERROR_HANDLING.md)).
- New middleware aliases go here. There is nowhere else.
- **No `->withSchedule(...)` on `develop`.** Nothing is scheduled. ⚠️ Unmerged `ws-404` adds one, and
  only one — `invitations:send-pending`, every ten minutes, `withoutOverlapping(20)`:

  ```php
  ->withSchedule(function (Schedule $schedule): void {        // ws-404 only
      $schedule->command('invitations:send-pending')->everyTenMinutes()->withoutOverlapping(20);
  })
  ```

  ☠️ **Even on that branch it never fires.** The deployment has no cron entry and no `schedule:work`
  container, so nothing calls `schedule:run`. Registering a task here does not make it run — see
  [JOBS.md](JOBS.md) and [DEPLOYMENT.md](DEPLOYMENT.md).
- **`trustProxies()` is called here**, but only when `TRUSTED_PROXIES` is non-empty
  (comma-separated CIDRs). Deliberately not `*`. Empty default = trust nothing, so the Laravel half is
  inert until the var is set — that is what makes it safe to ship ahead of the nginx half
  ([SECURITY.md](SECURITY.md) `S-16`). On `develop` since 2026-09-12.
- ⚠️ **That block reads `env()`, not `config()`, and must.** The `withMiddleware` closure runs before
  the config provider registers, so `config()` there throws `BindingResolutionException`. Consequence:
  `TRUSTED_PROXIES` has to arrive as a real container env var — `entrypoint.sh:34` runs `config:cache`
  on every boot, and once config is cached Laravel stops re-parsing `.env`, so a `.env`-only entry is
  invisible here.
- ☠️ **`TRUSTED_PROXIES` has no plumbing at all yet.** It appears nowhere in the repo except that one
  `env()` call — in particular it is **not** in the `environment:` allowlist of either compose file, so
  it cannot reach the container no matter who sets it. Adding it there is a code change; only then does
  supplying a value do anything. See [ENVIRONMENT.md](ENVIRONMENT.md).

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
| `config/mail.php` | `messages_per_connection` — **20** (`ws-404`); how many messages one SMTP connection may carry before it is recycled |

☠️ **`MAIL_MESSAGES_PER_CONNECTION` cannot be set from the environment in a deployed container.**
`.dockerignore` excludes `.env`, so the container has no env file, and the compose `environment:`
whitelist does not list the variable — `env()` therefore always falls back to the config default.
Retuning it means editing `config/mail.php` and deploying. The same is true of any new `MAIL_*` key:
adding it to the env file alone does nothing.

Why it exists: Symfony's `SmtpTransport` recycles its connection only after 100 messages, and SES cuts
it off before that with `421 too many messages in this connection`. See
[CONTEXT/INVITATION_CONTEXT.md](CONTEXT/INVITATION_CONTEXT.md).

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
