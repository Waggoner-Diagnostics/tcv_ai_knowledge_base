# Deployment

## Pipeline

`.github/workflows/non-prod.yml` — **"TCV Non-Production Pipeline (dev/qa)"**.

| Trigger | Detail |
|---|---|
| `pull_request: types: [closed], branches: [dev]` | fires when a PR into `dev` closes |
| `workflow_dispatch` | manual, with inputs |

Dispatch inputs: `environment` (`dev`/`qa`) · `region` (`us-east-1`) · `deploy_target`
(`frontend`/`backend`/`both`) · `frontend_branch` (default `develop`) · `backend_branch` (default
`develop`) · `generate_audit_reports` (compliance + SBOM).

☠️ **The PR trigger branch is `dev`; both repos' working branch is `develop`,** which is also what the
dispatch inputs default to. Confirm which branch you are deploying before assuming a merge shipped.

☠️ **There is no production workflow in this repo.** Whatever promotes to production lives elsewhere.

⭐ **Scope note, 2026-09-09: this whole page is about `TCV-Backend`.** `TCV-Website` grew its own
independent stack — `Dockerfile`, `docker-compose.yml`, `nginx.conf`, and **two** workflows including a
uat/prod one with a manual approval gate. Nothing here describes it; see
[WEBSITE.md](WEBSITE.md#deployment--docker-nginx-and-two-github-workflows). The website's nginx is now
the **edge for the whole product** and proxies to the SPA, which changes what "separate deployments"
means in point 9 of the checklist below.

## Image build

Multi-stage `Dockerfile` (see [SYSTEM_ARCHITECTURE.md](SYSTEM_ARCHITECTURE.md)):

1. `php:8.4-fpm-alpine3.21` builder — installs `pdo_mysql mbstring exif pcntl bcmath gd zip`, runs
   `composer install --no-dev --no-scripts --no-autoloader`, copies the app, then
   `composer dump-autoload --optimize --no-dev` + `post-autoload-dump`.
2. Runtime stage on the same base — recreates `www-data` as uid/gid **33** and copies the built app.

☠️ **The image is PHP 8.4** while `composer.json` requires `^8.2`. Local development on 8.2/8.3 will not
reproduce 8.4-specific behaviour (deprecations in particular).

## Runtime

`docker-compose.yml`, two services on the **external** network `tcv_network`:

| Service | Notes |
|---|---|
| `backend-app-tcv` | php-fpm; every setting arrives as an env var; `restart: unless-stopped` |
| `backend-nginx-tcv` | `nginx:1.25-alpine`, host **8080** → 80, mounts `./nginx.conf` and `./public` |

Volumes: `/var/www/html/storage/logs` bind-mounted from the host, and `./public` shared with nginx.

☠️ **No database service and no queue worker.** MySQL is external. Nothing consumes the `database`
queue — LMS deliveries accumulate ([QUEUES.md](QUEUES.md)).

## Boot (`entrypoint.sh`)

```
chown/chmod storage bootstrap/cache
APP_KEY      unset → FATAL, exit 1
FRONTEND_URL unset → FATAL, exit 1
config:clear route:clear cache:clear
config:cache route:cache
php artisan migrate --force --path=…create_cache_table.php   ← lock bootstrap, non-fatal
php artisan migrate --force --isolated   ← failure writes an unhealthy marker, then PROCEEDS
  └ on success, marker cleared only if `migrate:status --pending` says none are pending
exec php-fpm
```

Operational consequences:
1. **A failed migration still yields a "running" container** on a half-migrated schema — deliberately,
   so it can be debugged. It writes `storage/framework/migration_failed`, which `/up` reports on, so
   orchestration sees the replica as unhealthy. Read the boot log; a container being "up" says nothing
   about the schema.
2. **Routes and config are cached at boot** — a route or config change needs a restart, not just a new
   file.
3. ✅ **Fixed 2026-09-07 (`tcv-backend-codefix`, since merged into `develop`) — a fresh database could not bootstrap.**
   `--isolated` takes its lock through the default cache store, which is the *database* store
   (`CACHE_STORE` defaults to `database`), and `cache_locks` is itself created by a migration. On a
   brand-new database the lock INSERT hit a table that did not exist yet and killed the whole run
   before applying anything — then the `else` branch wrote the marker, so `/up` failed **permanently**
   on any new environment, DR restore or `migrate:fresh` recovery, with an error naming the database
   rather than the lock. `entrypoint.sh` now runs the framework's `create_cache_table` migration
   unisolated first, so the lock has somewhere to live; that step is deliberately non-fatal and
   unmarked (a no-op on an already-migrated database, and harmless if two replicas race it — the
   isolated run below stays the sole authority). Reproduced before the fix and verified after, both on
   a genuinely empty database.
4. ✅ **Fixed 2026-09-07 (same branch, since merged) — a replica reported healthy without ever verifying the schema.**
   `--isolated` exits 0 when it *skipped* because another replica held the lock, and the success branch
   cleared the marker unconditionally — so a replica that migrated nothing still asserted a healthy
   schema.

   📌 **Correction to how this trap was previously described here.** It was written up as "a skipping
   replica wipes another replica's marker *off the shared `storage` volume*". That premise is wrong:
   `docker-compose.yml` bind-mounts only `/var/www/html/storage/logs` and declares no named volumes,
   so `storage/framework/migration_failed` is **per-container** and one replica cannot erase another's.
   Verified against both `docker-compose.yml` and `docker-compose-dev.yml`. The fix is still correct —
   a replica must not claim a schema it never checked — but the cross-replica wipe it was said to
   prevent could not occur. This also settles the apparent disagreement between `entrypoint.sh` and
   `AppServiceProvider::configureMigrationHealthCheck()` over whether the marker is shared: **it is
   not.**

   The success branch now clears the marker only when `migrate:status --pending --no-ansi` reports
   `"No pending migrations"`;
   otherwise it logs and leaves the health state untouched.

   Three things about that check, each verified by measurement and each easy to "improve" into a bug:
   - ☠️ **The exit code cannot be used.** `migrate:status --pending` exits **0 whether or not anything
     is pending** — it only exits non-zero when it cannot read the `migrations` table at all (which
     also fails the string match, so that path stays safe). Swapping the grep for `if php artisan
     migrate:status --pending; then` clears the marker unconditionally and silently undoes this fix.
   - Match the **whole** phrase, not `pending` alone: the "No pending migrations" message contains the
     word too, so a loose match clears the marker in both directions. `--no-ansi` keeps the phrase free
     of colour escapes however TTY detection goes.
   - The `else` branch deliberately does **not** write a marker. A replica that booted while another
     was mid-migration would otherwise be stranded permanently unhealthy, because nothing re-evaluates
     the marker after boot. The accepted cost is that such a replica may briefly serve an incomplete
     schema; a genuinely failing replica still surfaces through its own marker, which this branch no
     longer erases.

## ☠️ Rolling deploys are not safe for schema-changing releases

`migrate --force --isolated` means exactly **one** replica migrates while the others carry straight on
serving. Those others are running the *new* image against the *old* schema for the length of the
migration, and nothing gates them behind it — the `/up` marker only records this replica's own boot
result, and it is per-container (trap 4 above), so orchestration has no signal that the schema is mid-
flight anywhere else.

For `tcv-backend-codefix` that window is not cosmetic. Two of its migrations change data the running
code depends on:

- **Session-token hashing** — until the migration lands, a replica running new code hashes the
  presented token and finds no row, so **every in-flight patient session 401s**.
- **`test_sessions.patient_id`** — a replica on the new code calling `TestSession::create()` against
  the old schema hits a column that does not exist yet, and **500s**.

Deploy this release with a **single replica**, or take a short maintenance window. The same caution
applies to any future release whose migrations change a column the request path reads on every call —
check the migration list before choosing a rolling deploy.

## Deployment checklist

1. Env vars — use the `environment:` block of `docker-compose.yml` as the list, **not**
   `.env.example` ([ENVIRONMENT.md](ENVIRONMENT.md)).
2. `APP_KEY` and `FRONTEND_URL` set (the boot will refuse otherwise).
3. `APP_ENV=production` if you do not want exception messages and traces in API responses
   ([S-12](SECURITY.md#s-12--non-production-error-responses-leak-messages-and-stack-traces)).
4. External MySQL reachable — `RestrictIpMiddleware` queries it on the very first request.
5. S3 bucket + credentials, or every test plate returns a null URL.
6. `TURNSTILE_SECRET_KEY` set, or organisation patient intake **fails closed**.
7. Lookup tables populated (`compliances`, `privileges`, `organization_types`,
   `organization_settings_options`, `price_details`, `email_template`) — the app is unusable without them.
8. **A queue worker**, if LMS delivery is expected to work: `php artisan queue:work` against the same
   image and env.
9. The SPA and website are **separate deployments** with their own nginx configs
   (`TCV-Frontend/nginx.conf`, `nginx.integration.conf`).

## Rollback notes

- Migrations are forward-only in practice; several have real `down()` methods but the
  discount-code rebuild (`2026_04_17_000001_rebuild_discount_codes_system.php`) **drops and recreates**
  four tables. Rolling it back destroys discount data.
- Because config and routes are cached into the image's runtime, rolling back the image is a clean
  revert of both.
