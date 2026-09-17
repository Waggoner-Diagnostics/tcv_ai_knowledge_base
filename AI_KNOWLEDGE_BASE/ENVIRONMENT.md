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

☠️ **`TRUSTED_PROXIES` — the fail-closed default is gone (`ws-449`, 2026-09-14).**
`bootstrap/app.php` now calls `trustProxies()` on every boot. Unset (or empty, which is what compose
substitutes for an unset host variable) resolves to **`10.0.0.0/8,172.16.0.0/12,192.168.0.0/16,127.0.0.1`**;
`*` is honoured. `TCV-Backend/nginx.conf` now forwards `X-Forwarded-For` to php-fpm, so `$request->ip()`
is resolved from that header rather than being the proxy.

That fixes the shared-bucket outage, but the two nginx hops in front — `TCV-Website/nginx.conf:49` (the
edge) and `TCV-Frontend/nginx.conf:41` — both still carry `set_real_ip_from 0.0.0.0/0`, so the chain
starts from a client-supplied value and the private-range default then believes it. **Leaving the
variable unset is no longer the safe choice.** Setting it narrower does not help either while those
nginx files trust every peer; they are the fix. Read [S-16 *Status 2026-09-17*](SECURITY.md#status-2026-09-17--both-backend-halves-shipped-the-frontend-nginx-precondition-did-not)
before changing either.

It **is** wired up now: `TRUSTED_PROXIES: ${TRUSTED_PROXIES}` is in the `environment:` block of both
compose files. Compose passes an **explicit allowlist** — `KEY: ${KEY}`, **59** entries on `develop`
(`TRUSTED_PROXIES` and `MAIL_INVITATION_DISPATCH` are the two newest) — so a variable absent from that
block never reaches the container whatever an env file says. Supplying a value per environment is
DevOps' step.

⚠️ **And it must end up as a real container env var, not only a `.env` line.** The value is read with
`env()` inside the `withMiddleware` closure — which runs before the config provider, so `config()` is
unavailable there — and `entrypoint.sh:34` runs `config:cache` on every boot, after which Laravel stops
parsing `.env` at runtime. The compose `environment:` route satisfies this; a bare `.env` entry does
not. (Compose's own `${...}` interpolation reads a host-side `.env` next to the compose file — that is
a different file from the Laravel `.env` inside the container, and only the compose one feeds the
allowlist.)

**Invitation delivery tuning** (all optional; defaults are the deployed behaviour):

All on `develop` since `ws-404` merged (2026-09-15):

| Variable | Default | Controls |
|---|---|---|
| `MAIL_INVITATION_SEND_BUDGET` | `240` | Seconds for a whole bulk send in `after_response` mode, computed once across all batches |
| `MAIL_INVITATION_QUEUE_BATCH_BUDGET` | `60` | Seconds **per batch** in `queue` mode, converted to a deadline when the worker *starts* the batch (never at dispatch — 20 batches of a 500-address send dispatch together but run serially, so a dispatch-time deadline expired for all but the first few). Kept well under the job's `$timeout` of 180 |
| `MAIL_CONNECTION_RETRY_DELAY` | `2` | Base seconds before retrying an address whose **connection** failed, multiplied by attempt number (≈2s then 4s). Longer than the 4xx retry on purpose: a 421 is the server talking and relents in about a second, whereas nothing answering on the port means a restarting daemon or a rate limiter. `phpunit.xml` sets it to **0** so the failure-path tests do not sleep through the suite |
| `MAIL_INVITATION_MAX_DEFERRALS` | `36` | Separate batch runs one row may be deferred before it is written off, revoked and refunded. ☠️ **A downtime tolerance, not a retry count** — at one deferral per 10-minute scheduled sweep, 36 ≈ six hours of mail-host outage (an earlier value of 5 was ≈40 minutes, short enough for a routine SES incident to irreversibly revoke the oldest invitations of every send in flight) |
| `MAIL_INVITATION_SWEEP_INTERVAL` | `600` | Minimum seconds between `SweepPendingInvitationsJob` runs (floor 60) |
| `MAIL_INVITATION_SWEEP_AGE_MINUTES` | `15` | How old a `pending` row must be before the sweep touches it |
| `MAIL_INVITATION_SWEEP_BUDGET` | `60` | Seconds for one sweep batch |
| `MAIL_INVITATION_DISPATCH` | `after_response` | `queue` hands batches to `backend-queue`; anything else (or unset) runs them in the web process after the response |
| `DB_QUEUE_RETRY_AFTER` | `360` (was `90`) | ☠️ Must exceed `backend-queue`'s `--timeout=300`, or a still-running invitation batch is re-reserved by a second worker and re-sent |

- ⚠️ **Only `MAIL_INVITATION_DISPATCH` is in the compose `environment:` allowlist.** Every other row
  above resolves to its config default in a deployed container, whatever an env file says — retuning
  means editing `config/mail.php` / `config/queue.php` and deploying. That is deliberate: dispatch mode
  is the switch that needs turning per environment; the rest are tuning knobs.
- ☠️ **`MAIL_INVITATION_DISPATCH=queue` without a running `backend-queue` is the dangerous
  combination** — batches accept into `jobs` and nothing sends them, with no error anywhere. And since
  the 2026-09-15 merge the worker is **off by default** (compose `workers` profile), so this now bites on
  a *current* image, not just an old one. Rows stay `pending`, so the sweep and the scheduled command
  are the backstop.

**Deployment / process switches** (new with `ws-404`):

| Variable | Where | Meaning |
|---|---|---|
| `COMPOSE_PROFILES=workers` | host, at `docker compose up` | Starts `backend-queue` and `backend-scheduler`. ☠️ Unset = neither exists. Read the first-boot runbook in [DEPLOYMENT.md](DEPLOYMENT.md) before setting it on any environment with a backlog |
| `RUN_INIT` | compose literal, not `${…}` | `true` on `backend-tcv` → `entrypoint.sh` rebuilds caches and runs migrations; `false` on the two worker containers → skipped. Unset falls back to "argv[0] basename is `php-fpm*`" |

**Seeder-only** (not in compose, never read at runtime): `SUPER_ADMIN_EMAIL` / `SUPER_ADMIN_PASSWORD`
for `Database\Seeders\SuperAdminSeeder` — defaults `superadmin@tcv.test` / `password123`, and the seeder
**prints the password to the console**. It is `updateOrCreate` on email, so re-running it resets that
account's password. Not called from `DatabaseSeeder`; run it explicitly.

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
