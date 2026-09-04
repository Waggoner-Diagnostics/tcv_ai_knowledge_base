# Database

MySQL, **52 tables**, reconstructed from 122 migrations — the indexed snapshot, taken from
`tcv-backend-codefix` after the `develop` merge of 2026-09-04. Full column detail:
[INDEXES/DATABASE_TABLE_INDEX.md](INDEXES/DATABASE_TABLE_INDEX.md).

> **The index is a union across migrations, not a live schema.** A column added and later dropped still
> appears. `DESCRIBE` is the only authority before you write a migration.

## What the 52 include

| Group | Tables |
|---|---|
| Laravel plumbing | `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `password_reset_tokens`, `personal_access_tokens` |
| Identity | `users`, `restricted_ips` |
| Test template | `tests`, `test_sections`, `test_section_plates`, `test_conditions` |
| Test instance | `patient_tests`, **`testanswers`**, `patients`, `prolific_ids` |
| Sessions & tokens | `test_sessions`, `test_invitations`, `test_resume_tokens`, `lms_sessions`, `organization_patient_sessions`, `lms_delivery_tokens` |
| Organisations | `organizations`, `organization_configs`, `organization_types`, `organization_settings_options`, `compliances`, `privileges`, `allowed_tests` |
| LMS | `lms_provider_configs`, `lms_sessions`, `lms_delivery_queue`, `lms_delivery_tokens` |
| Money | `credits`, `credit_consume`, `transactions`, `transaction_details`, `user_stripe_details`, `price_details`, `discount_codes`, `discount_code_users`, `discount_code_price_tiers` |
| Email | `email_template`, `user_email_templates`, `test_email_templates` |
| Assignment | `user_assigned_tests`, `user_hidden_tests` |
| Audit | `pricing_audit_logs` |
| **Historical names** | `user_emails`, `admin_settings`, `user_email_settings`, `discount_code_user` — see below |

---

## Naming is inconsistent — know the exceptions

| Table | Why it's odd |
|---|---|
| **`testanswers`** | No underscore. Every other test table has one. `TestAnswer` declares `protected $table = 'testanswers'` explicitly. Its migration is named `create_test_answers_table.php` — **the file name lies**. |
| **`email_template`** | Singular, while `user_email_templates` and `test_email_templates` are plural. |
| **`credit_consume`** | Singular verb form; `CreditConsume` declares `protected $table = 'credit_consume'`. |
| **`credits`** | Served by **two** models, `Credits` (live) and `Credit` (near-dead) — [CREDITS_CONTEXT](CONTEXT/CREDITS_CONTEXT.md). |
| **`lms_delivery_queue`** | Singular `queue`, while every sibling LMS table is plural. |

Never infer a table name from a model or a migration filename here. Check
[INDEXES/MODEL_INDEX.md](INDEXES/MODEL_INDEX.md) or the model's `$table`.

## Renamed tables the index still lists

`2026_04_12_180607_rename_user_emails_to_user_email_templates.php`:
```php
Schema::rename('user_emails',    'user_email_templates');
Schema::rename('admin_settings', 'test_email_templates');
```
So `user_emails`, `admin_settings` and `user_email_settings` are **former names**. They appear in the
index because their `Schema::create` migrations still exist; they do not exist in a migrated database.
`user_email_templates` and `test_email_templates` show `_altered only_` for the same reason — they were
never `create`d, they were renamed into existence.

Likewise `discount_code_user` (2025) was dropped and replaced by `discount_code_users` in
`2026_04_17_000001_rebuild_discount_codes_system.php`, which drops **all four** discount tables and
recreates them. **Only `discount_code_users` is live.**

---

## Conventions that do hold

- `id` bigint auto-increment primary key everywhere except `lms_sessions` / `lms_delivery_queue`, whose
  ids are supplied (UUID-shaped) — note `LmsSession::$fillable` includes `'id'`.
- `created_at` / `updated_at` on essentially everything — **except `countries` and `states`**, which
  come from the `nnjeim/world` package and have neither column. Both models need
  `public $timestamps = false;` or any write fails. `countries` is exactly
  `id, iso2, name, status, phone_code, iso3, region, subregion` — there is **no `code` column**, though
  `Country::$fillable` still lists one — and every optional column is NOT NULL, so a hand-built row
  (a test fixture, say) must supply `iso3`, `phone_code`, `region` and `subregion` as well.
- Foreign keys are declared on the newer tables (LMS, discounts) and **often absent on the older ones**.
  Do not rely on the DB to enforce referential integrity; the LMS subsystem does, the test subsystem
  largely does not.
- Only **four** models soft-delete: `User`, `Patient`, `DiscountCode`, `LmsProviderConfig`. A
  soft-deleted parent silently drops children from any query that joins through it —
  [PATIENT_CONTEXT trap 3](CONTEXT/PATIENT_CONTEXT.md).
  **A soft-deleted row keeps its unique keys — where a unique index still exists.** The two cases now
  diverge, so check which one you are in:
  - **`users.email` — still uniquely indexed.** The DB enforces it regardless of `deleted_at`, so a
    deleted user's address is **not** reusable. `UserRequest` validates without a
    `whereNull('deleted_at')` clause so validation agrees with the DB ([REQUESTS.md](REQUESTS.md)).
  - **`discount_codes.code` — unique index narrowed to live rows on `ws-392`** (2026-08-27).
    ☠️ **Not in the indexed tree** — the indexes come from `ws-398` (= `develop`), where one unique
    index spans trashed rows too and a deleted code's name stays reserved. The rest of this bullet
    applies only if ws-392 merges.
    Deleting a code now **releases** its name. The blanket unique index became a plain index plus
    `discount_codes_code_active_unique`, unique over **live rows only**: on MySQL a virtual generated
    column `code_active` that is `NULL` whenever `deleted_at` is set, on SQLite a partial index with
    the same filter. NULLs are distinct in a unique index, so trashed rows no longer collide while two
    live rows still cannot share a code. `StoreDiscountCodeRequest` / `UpdateDiscountCodeRequest`
    carry a matching `Rule::unique(…)->whereNull('deleted_at')` — that is what turns a collision into
    a 422 rather than a 500 — and `DiscountCodeController::codeAvailable()` dropped its
    `withTrashed()`. See [DISCOUNT_CONTEXT](CONTEXT/DISCOUNT_CONTEXT.md).
- **`countries` and `states` have no `created_at`/`updated_at`.** They come from the `nnjeim/world`
  package, so `Country` and `State` set `public $timestamps = false` (2026-08-27). Writing to either
  model without that flag throws `Unknown column 'updated_at'`. Treat the whole `nnjeim/world` set as
  timestamp-less reference data.
- Money is `decimal:2` on `transactions`; `organizations.registration_fee_paid` is cast **`float`**.
  Follow the decimal, not the float.
- Booleans are usually `tinyint` cast in the model. `test_answers.answered` / `.correct` are `0`/`1`
  integers and are **summed**, not counted — see `TestExecutionService`.

---

## The tables that carry real semantics

| Table | Why it matters |
|---|---|
| `patient_tests` | `unique_test_id` (UUIDv4) is the de-facto capability for a test session; `parent_test_id` pairs OS/OD; `result_json` is a **write-once** snapshot |
| `testanswers` | One row per plate, created up-front with `answered = 0`. Progress is a `COUNT`, never a counter |
| `credits` + `credit_consume` | Balance is `SUM(grants) − SUM(spends)`. **No balance column exists** |
| `lms_sessions` | `status` is a forward-only state machine; `session_token` is a SHA-256 hash |
| `password_reset_tokens` | Shared by **two** brokers (`users`, `setup`) — one live token per email |
| `personal_access_tokens` | `abilities` decides policy outcomes ([AUTHORIZATION.md](AUTHORIZATION.md)) |
| `restricted_ips` | Read on **every** request by global middleware |

## Migration practice

- `entrypoint.sh` runs `php artisan migrate --force` on every container boot and **continues on
  failure** (it logs `❌ Migrations FAILED — continuing anyway`). A broken migration therefore yields a
  running app on a half-migrated schema. Check the boot log, not just the health check.
- There is no seeder set for reference data beyond `database/seeders`; `compliances`, `privileges`,
  `organization_types`, `organization_settings_options` and `price_details` are lookup tables that must
  be populated for the app to be usable.
- ☠️ **MySQL-only SQL in a migration takes down the entire test suite, not one test.** Tests run on
  in-memory SQLite and `RefreshDatabase` re-migrates from scratch for every test, so a single
  unguarded `ALTER TABLE … MODIFY` or `CONCAT()` aborts migration and **every test errors**. This is
  not hypothetical: `2026_04_17_090859_update_source_column_comment_in_credits_table` left the suite
  100% red from **2026-04-17 to 2026-08-21** (joined in April by the `discount_codes` normalization),
  and nothing caught it because CI runs no tests ([TESTING.md](TESTING.md)). Guard raw
  driver-specific statements:

  ```php
  if (DB::getDriverName() === 'mysql') {
      DB::statement('ALTER TABLE credits MODIFY COLUMN source …');
  }
  ```

  Skipping on SQLite is correct rather than a workaround: SQLite has no column comments and does not
  enforce column types, and a one-off production data normalization has nothing to normalize in an
  empty test database. Adding such a guard to a migration that has **already run** in production is
  safe — it will not re-execute, and the MySQL path is unchanged.
- `Schema::defaultStringLength(191)` is set in `AppServiceProvider::boot()` — a legacy MySQL index-length
  workaround. A `string` column is 191 chars unless you say otherwise.
- **Data migrations that edit seeded content must match on the old value, not overwrite.** `email_template`
  rows are hand-edited in every environment, so a blind `update()` would silently destroy a tailored
  subject or footer, and `down()` would stamp back a value the row never held. The three `ws-373`
  migrations are the reference shape:

  ```php
  $current = DB::table('email_template')->where('name', 'password_reset')->value('subject');
  if ($current !== $from) { Log::info('… left as it is', ['reason' => $current === $to ? 'already applied' : 'customised']); return; }
  ```

  They also guard with `Schema::hasTable()` / `hasColumn()` (so an empty SQLite test DB is a no-op rather
  than an error), `chunkById(100)` over `user_email_templates` because it holds one `longText` body per
  user, and bump `updated_at` so nothing keyed on it serves pre-migration markup. One of the three,
  `2026_08_31_000001_anchor_bare_link_placeholders_in_email_templates`, is deliberately **irreversible** —
  an empty `down()` with a comment saying why, which is better than a `down()` that guesses.
