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

⚠️ **`$request->ip()` is still not the client, even though the code to fix it has landed.**
php-fpm sits behind the `backend-nginx` container, so `REMOTE_ADDR` is always the proxy: the five
IP-keyed rate limiters share one platform-wide bucket and `RestrictIpMiddleware` can never match a
real client.

`bootstrap/app.php` reads **`TRUSTED_PROXIES`** (on `develop` since 2026-09-12) and calls `trustProxies()`
— but **only when it is non-empty**, and the default is empty, so nothing has changed in effect.

☠️ **Do not set `TRUSTED_PROXIES` yet.** `TCV-Frontend/nginx.conf:41` still carries
`set_real_ip_from 0.0.0.0/0`, so nginx rebuilds `X-Forwarded-For` from a client-supplied value; trusting
the hop today lets a caller choose their own IP and bypass every limiter. Narrow the nginx CIDR first,
then set this to the same CIDR — never `*`. Read
[S-16](SECURITY.md#s-16--every-client-shares-one-ip-rate-limits-and-ip-restriction-are-both-inert) first.

☠️ **`TRUSTED_PROXIES` is not wired up yet — setting it changes nothing today.** It appears in exactly
one place in the whole backend repo: the `env()` call in `bootstrap/app.php:51`. It is **not** in the
`environment:` block of `docker-compose.yml` or `docker-compose-dev.yml`, not in `.env.example`, and not
in `entrypoint.sh`.

That matters because of how env reaches this app. Compose passes an **explicit allowlist** —
`KEY: ${KEY}`, 57 entries on `develop` — so a variable absent from that block never reaches the
container whatever an env file says. Two separate steps are therefore needed, and they belong to
different people:

| Step | Owner |
|---|---|
| Add `TRUSTED_PROXIES: ${TRUSTED_PROXIES}` to the `environment:` block of both compose files | **code change in `TCV-Backend`** |
| Supply the value for each environment | **DevOps** — env values are applied directly by them |

⚠️ **And it must end up as a real container env var, not only a `.env` line.** The value is read with
`env()` inside the `withMiddleware` closure — which runs before the config provider, so `config()` is
unavailable there — and `entrypoint.sh:34` runs `config:cache` on every boot, after which Laravel stops
parsing `.env` at runtime. The compose `environment:` route satisfies this; a bare `.env` entry does
not. (Compose's own `${...}` interpolation reads a host-side `.env` next to the compose file — that is
a different file from the Laravel `.env` inside the container, and only the compose one feeds the
allowlist.)

**Invitation delivery tuning** (all optional; defaults are the deployed behaviour):

| Variable | Default | Branch | Controls |
|---|---|---|---|
| `MAIL_INVITATION_SEND_BUDGET` | `240` | `develop` | Seconds for a whole bulk send, computed once across all batches |
| `MAIL_INVITATION_SWEEP_INTERVAL` | `600` | ⚠️ `ws-404` | Minimum seconds between `SweepPendingInvitationsJob` runs (floor 60) |
| `MAIL_INVITATION_SWEEP_AGE_MINUTES` | `15` | ⚠️ `ws-404` | How old a `pending` row must be before the sweep touches it |
| `MAIL_INVITATION_SWEEP_BUDGET` | `60` | ⚠️ `ws-404` | Seconds for one sweep batch |

Two gaps, both of which make these inert today:

- The three `SWEEP` config keys do not exist on `develop` at all — they arrive with `ws-404`.
- ⚠️ **None of the four is in the compose `environment:` block**, on either branch, so none can be
  injected into the container even where the config key exists. `MAIL_INVITATION_SEND_BUDGET` is a live
  config key on `develop` yet always resolves to its `240` default for exactly this reason. (`ws-404`
  adds one invitation variable to compose — `MAIL_INVITATION_DISPATCH` — but not these.)

Wiring any of them is a compose change in `TCV-Backend`, not something an env-file edit can deliver.

Not in compose but read by config: `AUTH_PASSWORD_BROKER`, `AUTH_PASSWORD_RESET_TOKEN_TABLE`,
**`AUTH_PASSWORD_SETUP_TOKEN_EXPIRE`** (default 2880 min = 48 h), `SANCTUM_TOKEN_PREFIX`,
**`HUBSPOT_TICKET_SOURCE_PROPERTY`** and `HUBSPOT_TICKET_SOURCE_VALUE` (default `TCV`) — ws-396.

⚠️ The HubSpot pair is read by `config/services.php` but **absent from compose**, so the config defaults
(`tcv_source` / `TCV`) are what every environment actually uses. The property's internal name is
portal-specific — HubSpot generates it when someone creates the property under Settings → Properties →
Tickets — so a portal that names it differently needs the var injected, and a portal that has no such
property degrades to untagged on its own via the rejection fallback in `HubSpotService`. Both defaults
use `?:` rather than `env()`'s second argument, so setting either var empty is the same as omitting it.

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
