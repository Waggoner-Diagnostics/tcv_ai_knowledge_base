# How to Trace the Database

## 1. Start at the index, not the migrations folder

[INDEXES/DATABASE_TABLE_INDEX.md](../INDEXES/DATABASE_TABLE_INDEX.md) — **52 tables**, each with its
`TABLE-nnn` id, column count, the migration that created it, and a full column list.

> ☠️ **It is a union across 109 migrations, not a live schema.** A column added and later dropped still
> appears (the "Dropped later" note under a table lists the ones the extractor caught). **`DESCRIBE` is
> the only authority** before you write a migration against a column.

## 2. Do not infer the table name from the model

| Model | Table |
|---|---|
| `TestAnswer` | **`testanswers`** (no underscore — and its migration file is named `create_test_answers_table.php`) |
| `CreditConsume` | `credit_consume` |
| `Credits` **and** `Credit` | both → `credits` |
| `EmailTemplate` | `email_template` (singular) |

Check the `Traits`/`$table` column in [INDEXES/MODEL_INDEX.md](../INDEXES/MODEL_INDEX.md), or the model's
`protected $table`.

## 3. Watch for renamed and superseded tables

`user_emails` → `user_email_templates` and `admin_settings` → `test_email_templates` were **renamed**,
so the old names still have `create` migrations and appear in the index while not existing in a migrated
database. `discount_code_user` was dropped and replaced by `discount_code_users`.
[DATABASE.md](../DATABASE.md) has the full list.

## 4. Find who reads and writes it

```bash
grep 'discount_code_users' AI_KNOWLEDGE_BASE/INDEXES/MODEL_INDEX.md     # the model + relations
grep 'DiscountCode'        AI_KNOWLEDGE_BASE/INDEXES/METHOD_INDEX.md    # every method, with lines
```

[MODEL_RELATIONSHIP.md](../MODEL_RELATIONSHIP.md) shows the graph and — more usefully — the joins that
are **not** relationships:

- `testanswers` ↔ `patient_tests` join on the **string** `unique_test_id`, with no relation and no FK.
- `patient_tests` pairs itself through `parent_test_id`, with no self-relation.
- `user_hidden_tests` has **no model at all** — grep the literal table name.

## 5. Soft deletes change what a join returns

Exactly four models soft-delete: `User`, `Patient`, `DiscountCode`, `LmsProviderConfig`.

A query that joins **through** a soft-deleted parent silently drops rows (the global scope hides the
parent), while a query straight off the child table still returns them. That is why a deleted patient's
tests appear in one report and vanish from another. Use `withTrashed()` on the join when the result must
be complete.

## 6. Writing a migration

```bash
php artisan make:migration add_x_to_y_table
```

- **`DESCRIBE y;` first.** The index may list a column that no longer exists.
- Match the neighbours on foreign keys: the LMS and discount tables declare them, the older test tables
  largely don't. Adding an FK to a table with existing orphan rows will fail at deploy.
- `Schema::defaultStringLength(191)` is global — a `string` column is 191 chars unless you say otherwise.
- Write a real `down()`. Note the discount rebuild
  (`2026_04_17_000001_rebuild_discount_codes_system.php`) **drops and recreates** four tables; rolling it
  back destroys data.

## 7. Deploying it

`entrypoint.sh` runs `php artisan migrate --force` on **every container boot** and **continues on
failure** — it logs `❌ Migrations FAILED — continuing anyway`. So a broken migration produces a running
app on a half-migrated schema, and the health check stays green.

**Read the boot log after deploying a migration.**

## 8. Regenerate the index

```bash
php tools/extract.php && php tools/render.php && php tools/verify.php
```

Then hand-update [DATABASE.md](../DATABASE.md) only if a *convention* changed, and the relevant context
pack if the column carries behaviour.

---

## The tables that carry real semantics

| Table | Read this before touching it |
|---|---|
| `patient_tests` | [TEST_EXECUTION_CONTEXT](../CONTEXT/TEST_EXECUTION_CONTEXT.md) — `result_json` is write-once |
| `testanswers` | same — rows are created up-front; progress is a `COUNT` |
| `credits` + `credit_consume` | [CREDITS_CONTEXT](../CONTEXT/CREDITS_CONTEXT.md) — no balance column exists |
| `lms_sessions` | [LMS_CONTEXT](../CONTEXT/LMS_CONTEXT.md) — forward-only status, hashed token |
| `password_reset_tokens` | [AUTH_CONTEXT](../CONTEXT/AUTH_CONTEXT.md) — two brokers share it |
| `personal_access_tokens` | `abilities` decides policy outcomes ([POLICIES.md](../POLICIES.md)) |
| `restricted_ips` | read on **every** request |
