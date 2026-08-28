# Environment Variables

## ☠️ `.env.example` is the stock Laravel file — it is missing every TCV-specific variable

Copying `TCV-Backend/.env.example` to `.env` produces an app that **boots and is broken**: no
`FRONTEND_URL` (every emailed link is a host-less `/app/...`), no Stripe keys, no HubSpot token, no
Turnstile keys, and `DB_CONNECTION=sqlite`.

The **authoritative list** is the `environment:` block of `docker-compose.yml`, which is what the
pipeline actually injects. Use that, not the example file.

## Required — the app refuses to boot without these

| Var | Enforced by |
|---|---|
| `APP_KEY` | `entrypoint.sh` → `❌ FATAL: APP_KEY not set` |
| `FRONTEND_URL` | `entrypoint.sh` → `❌ FATAL: FRONTEND_URL not set` |

`FRONTEND_URL` feeds `config('app.frontend_app_url')`, from which **every** patient-facing link is built:
email verification, password setup, test resume, and the organisation Test URL. Unset, it resolves to
the relative path `"/app"` with no host and nothing else catches it. For environments started outside
the entrypoint (`php artisan serve`), `AppServiceProvider::warnIfFrontendAppUrlLooksInvalid()` logs the
same warning at boot.

## The full set (from `docker-compose.yml`)

| Group | Vars |
|---|---|
| App | `APP_NAME` `APP_ENV` `APP_KEY` `APP_DEBUG` `APP_URL` `APP_LOCALE` `APP_FALLBACK_LOCALE` `APP_FAKER_LOCALE` `APP_MAINTENANCE_DRIVER` `PHP_CLI_SERVER_WORKERS` `BCRYPT_ROUNDS` |
| Frontend link | **`FRONTEND_URL`** |
| Logging | `LOG_CHANNEL` `LOG_STACK` `LOG_DEPRECATIONS_CHANNEL` `LOG_LEVEL` |
| Database | `DB_CONNECTION` `DB_HOST` `DB_PORT` `DB_DATABASE` `DB_USERNAME` `DB_PASSWORD` |
| Session / cache / queue | `SESSION_DRIVER` `SESSION_LIFETIME` `SESSION_ENCRYPT` `SESSION_PATH` `SESSION_DOMAIN` `CACHE_STORE` `CACHE_PREFIX` `QUEUE_CONNECTION` `BROADCAST_CONNECTION` |
| Unused stores | `MEMCACHED_HOST` `REDIS_CLIENT` `REDIS_HOST` `REDIS_PASSWORD` `REDIS_PORT` |
| Mail | `MAIL_MAILER` `MAIL_HOST` `MAIL_PORT` `MAIL_USERNAME` `MAIL_PASSWORD` `MAIL_ENCRYPTION` `MAIL_FROM_ADDRESS` (`MAIL_FROM_NAME` = `APP_NAME`) |
| AWS | `AWS_ACCESS_KEY_ID` `AWS_SECRET_ACCESS_KEY` `AWS_DEFAULT_REGION` `AWS_BUCKET` `AWS_USE_PATH_STYLE_ENDPOINT` `FILESYSTEM_DISK` |
| Stripe | `STRIPE_KEY` `STRIPE_SECRET` `STRIPE_WEBHOOK_SECRET` |
| HubSpot | `HUBSPOT_ACCESS_TOKEN` |
| Turnstile | `TURNSTILE_SITE_KEY` `TURNSTILE_SECRET_KEY` |
| Deploy | `IMAGE_TAG_BACKEND` |

Not in compose but read by config: `AUTH_PASSWORD_BROKER`, `AUTH_PASSWORD_RESET_TOKEN_TABLE`,
**`AUTH_PASSWORD_SETUP_TOKEN_EXPIRE`** (default 2880 min = 48 h), `SANCTUM_TOKEN_PREFIX`.

## Values that are hard-coded, not env-driven

| Setting | Value | Where | Change means |
|---|---|---|---|
| Sanctum token lifetime | **900 s** | `config/sanctum.php` | editing the file, not the env |
| Plate URL validity | 900 s | `SecureImageService` constant | code change |
| Plate URL cache TTL | 880 s | `SecureImageService` constant | code change |
| Invitation / resume validity | 7 days | `TestInvitation::INVITATION_VALIDITY_DAYS`, `TestResumeController::TOKEN_EXPIRY_DAYS` | code change |
| Test session TTL | 2 hours | inline `now()->addHours(2)` in two controllers | code change, **two places** |
| LMS session TTL | 120 / 180 min | provider-config defaults in `LmsLaunchService` | per-org config row |
| Default string length | 191 | `AppServiceProvider::boot()` | code change |

## The client repos

| Repo | Var | Exposure |
|---|---|---|
| `TCV-Frontend` | `REACT_APP_BASE_URL` | **browser** — baked into the build |
| | `REACT_APP_STRIPE_PUBLIC_KEY` | browser (publishable key, fine) |
| | `REACT_APP_TURNSTILE_SITE_KEY` | browser (site key, fine) |
| | `PUBLIC_URL` | browser — `/app` |
| `TCV-Website` | **`API_URL`** | **server only** — deliberately no `NEXT_PUBLIC_` prefix |

☠️ `REACT_APP_*` values are compiled into the JavaScript bundle. Never put a secret there.
☠️ Adding `NEXT_PUBLIC_` to `API_URL` would ship the backend URL to the browser and reintroduce the CORS
problem the website's proxy routes exist to solve ([WEBSITE.md](WEBSITE.md)).

## Reading config in code

`entrypoint.sh` runs `php artisan config:cache` at boot. After that, **`env()` returns `null` outside
config files.** Always read through `config('services.stripe.secret')`, never `env('STRIPE_SECRET')` in a
service or controller.
