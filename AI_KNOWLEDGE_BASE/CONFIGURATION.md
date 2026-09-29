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
        // ALWAYS called since ws-449 (2026-09-14); unset TRUSTED_PROXIES ⇒ every private range
        $middleware->trustProxies(at: $trustedProxies, headers: ...X_FORWARDED_*);
    })
    ->withSchedule(function (Schedule $schedule): void {          // ws-404, on develop 2026-09-15
        $schedule->command('invitations:send-pending')->everyTenMinutes()->withoutOverlapping(20);
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
- **One scheduled task on `develop`** (since `ws-404` merged, 2026-09-15): `invitations:send-pending`,
  every ten minutes, `withoutOverlapping(20)`, deliberately **not** `runInBackground()` so its summary
  line lands in the scheduler's log.

  ☠️ **Registered ≠ running.** Only a `schedule:work` process fires it, and the `backend-scheduler`
  service that runs one sits behind the compose **`workers` profile, which is off by default** — a plain
  `docker compose up -d` starts no scheduler and no queue worker. Until an environment opts in with
  `COMPOSE_PROFILES=workers`, the only automatic recovery is `SweepPendingInvitationsJob` riding on web
  traffic. See [DEPLOYMENT.md](DEPLOYMENT.md) for the first-boot runbook, and [JOBS.md](JOBS.md).
  (📌 The KB's 2026-09-14 note "on `ws-404` it does fire" was true of `07a1c9b2`; the profile gate was
  added by `9c7d0f1e` before merge.)
- ☠️ **`trustProxies()` is now called unconditionally** (`ws-449`, 2026-09-14). `TRUSTED_PROXIES` is
  parsed `trim(env(...)) ?: '10.0.0.0/8,172.16.0.0/12,192.168.0.0/16,127.0.0.1'`, and `'*'` is passed
  through as `'*'`. The old "empty default = trust nothing" gate is **gone**, and because
  `TCV-Website/nginx.conf` (the edge) and `TCV-Frontend/nginx.conf` still trust `X-Forwarded-For` from
  `0.0.0.0/0`, the default makes client IPs forgeable by the documented trace — read
  [S-16 *Status 2026-09-17*](SECURITY.md#status-2026-09-17--both-backend-halves-shipped-the-frontend-nginx-precondition-did-not)
  before touching either file.
- ⚠️ **That block reads `env()`, not `config()`, and must.** The `withMiddleware` closure runs before
  the config provider registers, so `config()` there throws `BindingResolutionException`. Consequence:
  `TRUSTED_PROXIES` has to arrive as a real container env var — `entrypoint.sh` runs `config:cache`
  on every web boot, and once config is cached Laravel stops re-parsing `.env`, so a `.env`-only entry is
  invisible here. It **is** now in the compose `environment:` allowlist of both files, so an unset host
  variable reaches the container as an empty string — which the `?:` above turns into the private-range
  default, not into "trust nothing".

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
| `config/mail.php` | `messages_per_connection` — **20** (`ws-404`); how many messages one SMTP connection may carry before it is recycled. ⚠️ `'default' => env('MAIL_MAILER', 'log')` — the fallback **discards mail**. Plus the invitation keys: `invitation_send_budget` 240 · `invitation_queue_batch_budget` 60 · `connection_retry_delay` 2 · `invitation_max_deferrals` **36** · `invitation_sweep_{interval,age_minutes,budget}` 600/15/60 · `invitation_dispatch` `after_response` ([ENVIRONMENT.md](ENVIRONMENT.md)) |
| `config/queue.php` | `database.retry_after` — **360** (was 90, `ws-404`). ☠️ Must stay **longer** than `backend-queue`'s `--timeout=300`: below the real runtime the queue re-reserves a still-running job, two workers send the same invitation batch, and patients get duplicates. Raise both together |

☠️ **The default mailer is still `log`**, whatever the code comments say. `SendTestInvitationEmailsJob::isFailoverExhaustion()`'s
docblock claims "config/mail.php ships `ses-v2` as the default" — it does not; production reaches
`ses-v2` only because `MAIL_MAILER` is set in its environment. An environment with the variable unset
discards every email and reports success.

⭐ **`ws-404` reworks the transport list** (on `develop` since 2026-09-15):

- Adds a **`ses-v2`** mailer. It talks to SES over HTTPS instead of opening an SMTP socket, which
  removes the entire failure class the rest of that file works around — nothing to refuse on `:587`, no
  per-connection message ceiling, no shared-mailbox connection limit, nothing for a firewall or fail2ban
  to rate-limit. Credentials come from `services.ses`, which already reads the same `AWS_*` variables the
  S3 disk uses, so switching is `MAIL_MAILER=ses-v2` and nothing else. v2 rather than legacy v1 because
  SES exposes its rate and reputation controls only on v2.
- ☠️ **Removes `log` from the `failover` chain**, which now reads `['ses-v2', 'smtp']`. `log` was never
  a fallback: it discards the message and reports success, so a broken primary read as a clean send
  while no patient received anything. A fallback has to be something that actually delivers.

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

```php
Schema::defaultStringLength(191);      // legacy MySQL index-length workaround → every string col is 191
$this->configureRateLimiting();        // the 7 named throttle: limiters — see SECURITY.md S-16
$this->configureMigrationHealthCheck(); // DiagnosingHealth → /up fails while the migration-failure marker exists
Event::listen(MessageSending::class, PrefixEmailSubject::class);
$this->warnIfFrontendAppUrlLooksInvalid();
```

⭐ **The `login` limiter has its own response callback** (2026-09-15, PR #245, `4c92b5e2` + `c6734fb3`):
`accountLockedResponse()` returns the same 429 body as the other six **and** writes an
`auth.account_locked` audit row, because `ThrottleRequests` rejects before `AuthController::login()` runs
and nothing else can see a lockout. It dedups with `Cache::add("audit:login-lockout:{key}")` for
exactly `RateLimiter::availableIn(md5('login'.$key))` seconds — the limiter's own remaining window, read
from the key `ThrottleRequests` itself uses for a named limiter. ☠️ Rename the limiter and that `md5()`
must change with it, or the dedup reads a key that never exists and logs a row per rejected request.
Details in [AUDIT_TRAIL_BACKEND_CONTEXT §16](CONTEXT/AUDIT_TRAIL_BACKEND_CONTEXT.md#16--merged-2026-09-15--lockout-audit-and-diff-fidelity).

## Config caching

`entrypoint.sh` runs `config:cache` **and** `route:cache` at boot — ⚠️ **in the web container only**
since `ws-404`: the block is skipped unless `RUN_INIT` is `1|true|yes` (compose sets `"true"` on
`backend-tcv`, `"false"` on `backend-queue`/`backend-scheduler`); unset, it falls back to "is argv[0]'s
basename `php-fpm*`". Consequences:

- **`env()` returns `null` outside config files** after caching. Read `config('…')` everywhere else.
- **A route change requires a container restart.** Locally, `php artisan route:clear`.
- A config change requires the same. `php artisan config:clear` locally.

## Message keys

`resources/lang/en/api.php` — **78 keys**, resolved by `ApiResponse` through `__()`. A missing key
renders as the literal key string in the response, which is how the typo'd `api.resticted` is visible to
clients today. Add a key whenever you add an `ApiResponse` call.
