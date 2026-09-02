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
php artisan migrate --force --isolated   ← failure writes an unhealthy marker, then PROCEEDS
exec php-fpm
```

Operational consequences:
1. **A failed migration still yields a "running" container** on a half-migrated schema — deliberately,
   so it can be debugged. It writes `storage/framework/migration_failed`, which `/up` reports on, so
   orchestration sees the replica as unhealthy. Read the boot log; a container being "up" says nothing
   about the schema, and per trap 4 below the marker itself is not yet reliable across replicas.
2. **Routes and config are cached at boot** — a route or config change needs a restart, not just a new
   file.
3. ☠️ **Open — a fresh database cannot bootstrap.** `--isolated` takes its lock through the default
   cache store, which is the *database* store (`CACHE_STORE` defaults to `database`), and `cache_locks`
   is itself created by a migration. On a brand-new database the lock INSERT hits a table that does not
   exist yet and kills the whole run before applying anything. **Fix shape:** run the framework's
   `create_cache_table` migration unisolated first, then take the lock for the rest. Written
   2026-09-02, held back with the rest of `entrypoint.sh`.
4. ☠️ **Open — a skipping replica erases another replica's failure marker.** `--isolated` exits 0 when
   it *skipped* because another replica held the lock, and the success branch clears the marker
   unconditionally. On the shared `storage` volume that lets a skipping replica wipe the marker a
   genuinely failing replica just wrote, marking every replica healthy on an unmigrated schema — the
   exact silent failure the marker exists to prevent. **Fix shape:** clear it only after
   `migrate:status --pending` confirms none are pending, matching the literal string
   `"No pending migrations"` (grepping for `pending` alone also matches that message). Same hold.

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
