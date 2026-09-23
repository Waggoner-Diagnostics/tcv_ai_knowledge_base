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

⚠️ **2026-09-15 (`45b53173`):** the "Code Quality Analysis: Backend" job now runs
`composer install --prefer-source …` (the old line is left commented above it). `--prefer-source`
clones each package's git repo instead of downloading dist archives — slower, and it needs git access to
every dependency's source host from the runner. The commit carries no rationale; if that step starts
timing out or failing auth, this is the first suspect. The lint step still never fails the pipeline.

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

`docker-compose.yml`, on the **external** network `tcv_network`. Two services start by default; two more
sit behind a profile:

| Service (container) | Starts by default? | Notes |
|---|---|---|
| `backend-tcv` (`backend-app-tcv`) | ✅ | php-fpm; every setting arrives as an env var; `restart: unless-stopped`; `RUN_INIT: "true"` |
| `backend-nginx` (`backend-nginx-tcv`) | ✅ | `nginx:1.25-alpine`, host **8080** → 80, mounts `./nginx.conf` and `./public`. Forwards `X-Forwarded-For`/`-Proto`/`X-Real-IP` to php-fpm since `ws-449` ([S-16](SECURITY.md#s-16--every-client-shares-one-ip-rate-limits-and-ip-restriction-are-both-inert)) |
| `backend-queue` (`backend-queue-tcv`) | ☠️ **no** — `profiles: ["workers"]` | `queue:work --queue=lms,default --tries=1 --timeout=300 --max-time=3600 --memory=384`. Drains the LMS backlog; also runs invitation batches when `MAIL_INVITATION_DISPATCH=queue` (and 🚧 LMS deliveries when `LMS_DELIVERY_DISPATCH=queue`, `ws-460`) |
| `backend-scheduler` (`backend-scheduler-tcv`) | ☠️ **no** — `profiles: ["workers"]` | `schedule:work` — runs the one scheduled task (`invitations:send-pending`, every 10 min; 🚧 plus `lms:deliver-pending` every 5 min on `ws-460`). Foreground, so the image needs no cron daemon. **Single replica by design** |

Volumes: `/var/www/html/storage/logs` bind-mounted from the host, and `./public` shared with nginx.
MySQL is external — there is no database service.

### ☠️ The worker and scheduler merged **off** — `COMPOSE_PROFILES=workers` turns them on

`ws-404` merged both services into `develop` on 2026-09-15, but `9c7d0f1e` put them behind the
`workers` profile first. **`docker compose up -d` starts neither.** On an environment that has not opted
in, nothing consumes the `database` queue (LMS deliveries still accumulate — [QUEUES.md](QUEUES.md)) and
nothing runs the schedule; invitation recovery rests on `SweepPendingInvitationsJob` and web traffic.

```bash
COMPOSE_PROFILES=workers docker compose up -d          # or: docker compose --profile workers up -d
COMPOSE_PROFILES=workers docker compose -f docker-compose-dev.yml up -d
```

**Why off by default — the first-boot runbook** (the foot of `docker-compose.yml` spells it out). An
environment that has never had a worker has a real, old backlog, and both containers start draining it
the moment they come up, with no operator deciding any of it should happen:

1. **The queue.** `SELECT queue, COUNT(*), MIN(FROM_UNIXTIME(created_at)) FROM jobs GROUP BY queue;` —
   the `lms` rows are `ProcessLmsDeliveryJob`; starting the worker posts **every one** to the customer's
   LMS, including results from months ago. Delete or re-queue deliberately *before* starting it.
2. **Stranded invitations.** `SELECT email_status, COUNT(*), MIN(created_at) FROM test_invitations WHERE
   email_status IN ('pending','sending') AND is_revoked = 0 GROUP BY email_status;` — the scheduler's first
   `invitations:send-pending` **resends** anything still in its validity window to the patient, and
   **refunds and revokes** anything past it (irreversible: it closes both resend and cancel).
3. Only then, with ops sign-off, enable the profile.

Safe first run on an environment with a backlog: leave the profile off, run
`php artisan invitations:send-pending --limit=1` in the web container, read its summary line, then decide.
The dev compose file carries the same profile for the same reason — dev shares a database with whatever
was last restored into it, so an unreviewed first boot mails real addresses.

Four details that are easy to "tidy" into a bug:

- **`environment: &backend_env` / `<<: *backend_env`.** The web service's env block is anchored and
  merged into both workers, which override exactly one key (`RUN_INIT: "false"`). Splitting them lets a
  worker resolve a different database or mail host than the web process and silently do the wrong work
  rather than fail.
- **Both drop to `www-data`** via `su`, while the entrypoint still starts as root so its `chown` of
  `storage/` works. Left as root, every file the worker wrote into the shared `storage/logs` volume
  would be root-owned and php-fpm (uid 33) could not append to the day's log — logging breaks for the
  whole web app until the next restart.
- **`init: true`** puts tini at PID 1 so `SIGTERM` reaches php instead of stopping at the shell;
  `queue:work` uses it to finish the job in hand. `stop_grace_period: 200s` covers one invitation batch
  (`$timeout` is 180).
- **`--max-time=3600`** recycles the worker hourly so a leaked connection or stale config cache cannot
  accumulate in a long-lived process.

## Boot (`entrypoint.sh`)

```
chown/chmod storage bootstrap/cache
APP_KEY      unset → FATAL, exit 1
FRONTEND_URL unset → FATAL, exit 1
RUN_INIT true|1|yes → continue · false|0|no → "Skipping…", exec "$@"     ← ws-404
         unset      → continue only if basename(argv[0]) is php-fpm*
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

   ☠️ **Only the web container migrates** (`RUN_INIT`). The switch exists because `--max-time=3600`
   makes `backend-queue` exit hourly by design and `restart: unless-stopped` re-enters this entrypoint,
   which re-ran `config:cache` and an `--isolated` migrate check every hour. `RUN_INIT` is explicit
   because the old `"$1" = php-fpm` string test silently skipped migrations on `php-fpm -F`, an absolute
   path, or any wrapper — the one container meant to migrate, serving a stale schema behind a log line
   that looked intentional. Do not "simplify" it back to argv.
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
   ⚠️ **On any environment that has had a data migration run**, confirm
   `2026_09_21_000001_deduplicate_organization_lookup_tables` (`ws-459`) has applied — without it
   `compliances` and `organization_types` hold one duplicate set per migration run and every Add
   Organisation dropdown lists each option twice. `entrypoint.sh` continues past a failed migration, so
   check the boot log rather than assuming ([DATABASE.md](DATABASE.md)). Verify with
   `SELECT compliance, COUNT(*) FROM compliances GROUP BY compliance HAVING COUNT(*) > 1;` — it must
   return nothing.
   ⚠️ **Same environments, after `ws-459` PR #282 (merged 2026-09-22):** migrated users keep the
   country/state ids the old name lookup gave them until you run
   `php artisan users:backfill-legacy-location`. It is a dry run by default. Read its report, then
   re-run it with `--apply`. It needs `OLD_DB_CONNECTION`, and it also fills
   `users.legacy_country/legacy_state`, which is the only copy once the legacy DB is retired. Leave
   `--infer-country-from-state` / `--clear-wrong-country-states` off unless someone has decided to use
   them ([DATA_MIGRATION_CONTEXT](CONTEXT/DATA_MIGRATION_CONTEXT.md#legacy-countrystate-resolution-ws-459-pr-282)).
8. **A queue worker and scheduler**, if LMS delivery or scheduled invitation recovery is expected to
   work: `COMPOSE_PROFILES=workers`. ☠️ Run the first-boot runbook above **before** enabling it on any
   environment that has been running without one.
9. **`MAIL_MAILER`** — the config default is **`log`**, which accepts every message and delivers
   nothing. It is in the compose allowlist, so this is a DevOps env value, not a code change.
   `php artisan mail:preflight` (on `develop` since 2026-09-15) fails the environment for exactly this
   ([JOBS.md](JOBS.md)).
10. **`set_real_ip_from 0.0.0.0/0` removed or narrowed** in `TCV-Website/nginx.conf` (the edge) **and**
    `TCV-Frontend/nginx.conf` — since `ws-449` the backend believes the `X-Forwarded-For` chain it is handed
    ([S-16](SECURITY.md#status-2026-09-17--both-backend-halves-shipped-the-frontend-nginx-precondition-did-not)).
11. The SPA and website are **separate deployments** with their own nginx configs
    (`TCV-Frontend/nginx.conf`, `nginx.integration.conf`).
12. ☠️ **`LEGACY_*` key material set — new and required since `ws-459`** (PR #255, on `develop`
    2026-09-18). Patient PII is encrypted at rest, so `LEGACY_MASTER_KEY` (or `LEGACY_DEC_KEY`) plus
    `LEGACY_ENC_KEY` (or `LEGACY_DATA_KEY`) must be present, and the master key must **match the legacy
    value exactly**. Missing, `LegacyEncrypter::blindIndex()` throws rather than hashing against an empty
    key. Full list and the traps in [ENVIRONMENT.md](ENVIRONMENT.md); the risk in
    [S-21](SECURITY.md#s-21--the-patient-blind-indexes-are-unsalted-md5-under-a-single-global-key).
13. ⚠️ **Then run `php artisan patients:rebuild-email-index --apply` once per environment.** Blind
    indexes built before the correct key was in place match nothing, and the symptom is silent —
    patients simply stop being findable by email. Verify the keys first with
    `php artisan legacy:check-encryption --value=<ciphertext> --expect=<plaintext>`, which reports
    whether what you configured actually decrypts legacy rows.
14. 🚧 **HealthStream (`ws-460`, only once merged).** Leave `LMS_DELIVERY_DISPATCH` unset
    (`after_response`) unless `backend-queue` runs. Either way, **LMS retries need `backend-scheduler`**
    (`lms:deliver-pending`) — without the `workers` profile, schedule or run it by hand. Then, per HealthStream
    org: `php artisan lms:provision-healthstream --dry-run`, then without `--dry-run`, and give
    HealthStream the printed launch URL as the AU URL. Never `--rotate-key` a live org casually — it
    breaks every URL HealthStream holds ([LMS_CONTEXT](CONTEXT/LMS_CONTEXT.md#healthstream--aicc-ws-460-branch-only)).

## Rollback notes

- Migrations are forward-only in practice; several have real `down()` methods but the
  discount-code rebuild (`2026_04_17_000001_rebuild_discount_codes_system.php`) **drops and recreates**
  four tables. Rolling it back destroys discount data.
- Because config and routes are cached into the image's runtime, rolling back the image is a clean
  revert of both.
