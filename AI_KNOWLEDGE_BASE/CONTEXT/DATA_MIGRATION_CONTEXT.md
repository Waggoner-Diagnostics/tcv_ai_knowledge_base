# Context: Legacy Data Migration & Encryption at Rest

> Load this **instead of** reading the migration commands or the encryption support classes.
> ~1,400 tokens. Covers how legacy TCV v2 data is pulled into this app and how patient PII is held
> encrypted once it arrives.

✅ **Status: on `develop` and indexed.** `tcv_data_migration` merged as **`ws-459`** — backend PR #255
(`f0541712`) and frontend PR #395 (`d0da885`), both 2026-09-18 — and the KB regenerated against it on
2026-09-21 at `330cf77d` / `d0da885`. 182 files, ~25.3k insertions. Everything in this pack now
describes shipping code, and `INDEXES/` shows the **post**-encryption column shape.

☠️ **This is the single most load-bearing change in the KB's history for anyone writing a patient
query.** Patient, answer and invitation PII is ciphertext at rest on `develop`; `where('email', $x)`
against those columns can never match. Read [You cannot query an encrypted column](#-you-cannot-query-an-encrypted-column)
before touching one. Prose elsewhere in this KB that predates 2026-09-18 and calls these columns
plaintext is stale — this pack wins.

## Files
| File | Role |
|---|---|
| `app/Support/Legacy/LegacyCipher.php` | The CodeIgniter-era cipher itself (Rijndael-256 lineage, base64 payloads) |
| `app/Support/Legacy/LegacyEncrypter.php` | Key resolution + `encrypt`/`decrypt`/`decryptStrict`, and the blind-index helpers |
| `app/Support/Legacy/PatientNameSearch.php` | Free-text name search — decrypts in PHP, returns patient ids |
| `app/Services/Migration/LegacyCipher.php` | The migration commands' own cipher wrapper (`fromConfig()`) |
| `app/Casts/LegacyEncrypted.php` · `LegacyEncryptedInteger.php` | Transparent column casts |
| `app/Casts/ResultJsonWithEncryptedPii.php` | Encrypts the PII embedded inside `patient_tests.result_json` |
| `app/Console/Commands/BaseMigrationCommand.php` | Shared staging/chunking/reporting base for all migrate:* commands |
| `config/legacy.php` | Key material and the `legacy.enabled` switch |

## Tables
Reads `OLD_DB_CONNECTION` (`tcv_*` legacy tables) · writes `patients`, `patient_tests`, `testanswers`,
`test_invitations`, `users`, `organizations`, `credits`, `transactions`, `discount_codes`, `price_details`

---

## The key model

Two keys, and conflating them is the usual mistake:

```
data key = decode(enc_key, master_key)      # enc_key is tbl_encryption.enc_key, itself ciphertext
stored   = encode(value, data key)          # every encrypted field
```

| Config | Env | What it is |
|---|---|---|
| `legacy.master_key` | `LEGACY_MASTER_KEY` (or `LEGACY_DEC_KEY`) | Unwraps `enc_key` **and** keys every blind index |
| `legacy.enc_key` | `LEGACY_ENC_KEY` | The wrapped project key, copied from the legacy key database |
| `legacy.data_key` | `LEGACY_DATA_KEY` | Escape hatch: the already-unwrapped key, skipping the two above |
| `legacy.enabled` | — | Master switch; `false` makes `LegacyEncrypter` a pass-through |

☠️ **`master_key` has no default and must not get one.** It is the *entire* secret behind the md5 blind
indexes (`md5(value . master_key)`), so a committed value plus a database dump is enough to confirm a
guessed patient email or name for every migrated patient at once. A literal default was removed from
`config/legacy.php` by `ws-459`; the value it used to carry should be treated as burned. Missing now
throws from `LegacyEncrypter::blindIndex()` rather than silently hashing against an empty key.

The unwrapped data key lives in a **separate remote database**, not in either repo. `migrate:check-legacy-encryption`
is the command that tells you whether what you have configured actually reads the legacy rows.

---

## What is encrypted

| Model | `const ENCRYPTED` | Notes |
|---|---|---|
| `Patient` | `first_name`, `last_name`, `email`, `dob`, `zipcode`, `test_eyes` | `patient_id` is **deliberately plaintext** — the legacy app never wrapped it, and keeping it readable is what lets it still be matched in SQL |
| `TestAnswer` | `patient_answer`, `correct` | Integer-valued, so `LegacyEncryptedInteger` |
| `TestInvitation` | `email` | |
| `patient_tests.result_json` | PII inside the JSON | via `ResultJsonWithEncryptedPii` |

Each row carries an `is_encrypted` flag. `decrypt()` passes non-ciphertext straight through, so flipping
the flag ahead of a backfill is self-healing rather than destructive.

---

## ☠️ You cannot query an encrypted column

The cipher uses a **random IV**, so the same address encrypts differently every time. `where('email', $x)`
can never match — not with a transformed term, not ever. Three consequences:

1. **Exact lookup goes through a blind index.** `Patient::whereEmail()` and `whereName()` match the keyed
   md5 in `identification` / `first_name_ident` / `last_name_ident`. `identification` carries a plain
   index, **not** a unique one.
2. **Substring search is done in PHP.** `PatientNameSearch::idsMatching()` decrypts the patients table and
   returns ids for a `whereIn`. It is capped at 20,000 matches (a `whereIn` beyond MySQL's 65,535
   placeholders is error 1390, not a slow page) and memoised per request — it is registered as a
   **singleton** in `AppServiceProvider` precisely so the listing, each export chunk and each PDF chunk
   share one scan instead of re-decrypting the table each time.
3. **`unique:patients,email` does not work.** A `unique` rule against a ciphertext column silently passes
   everything. `PatientUpdateRequest` uses a closure over `whereEmail()` instead.

---

## The migration commands

☠️ **The command names changed when `ws-459` merged — the old list in this pack was branch-era and is
gone.** `migrate:all-master`, `migrate:users`, `migrate:patients`, `migrate:credits`,
`migrate:transactions`, `migrate:check-legacy-encryption` and the rest of that vocabulary **do not exist
on `develop`**. Verify with `php artisan list` before citing any name here.

**17 commands, and `migrate:tcv-all` is the only orchestrator.** It runs **7 steps** in dependency
order, each guarded by a precondition check on what the step before it produced, and refuses rather than
running on incomplete input. All extend `BaseMigrationCommand`, which provides staging tables, chunking
and the run report; progress is resumed from the `migration_progress` table.

```
migrate:tcv-all →  1 migrate:base-data
                   2 migrate:tcv-users-orgs
                   3 migrate:tcv-tests
                   4 migrate:tcv-assign-tests
                   5 migrate:patient-tests
                   6 migrate:patient-test-results
                   7 migrate:drop-staging-tables
```

| Group | Commands |
|---|---|
| Orchestrator | `migrate:tcv-all` (`--dry-run`, `--force`, single-step selection) |
| Chain steps | `migrate:base-data` · `migrate:tcv-users-orgs` · `migrate:tcv-tests` · `migrate:tcv-assign-tests` · `migrate:patient-tests` · `migrate:patient-test-results` · `migrate:drop-staging-tables` |
| Outside the chain | `migrate:tcv-test-answers` · `migrate:recover-orphan-patients` |
| Encryption backfills | `patients:encrypt` · `answers:encrypt` · `invitations:encrypt` (each dry-run by default, `--apply` to write) |
| Repair | `patients:backfill-legacy-pii` · `patients:rebuild-email-index` |
| Diagnostics | `legacy:check-encryption` · `migration:verify-results` |

⚠️ **`legacy:check-encryption` is the one to reach for first** — it tells you whether the keys you have
configured actually decrypt the legacy rows, before any command writes anything. It takes `--value=` (a
ciphertext from the legacy database) and `--expect=` (what it should decrypt to).

⚠️ **`patients:rebuild-email-index --apply` must be run once per environment after deploying** — the
blind index is keyed by `LEGACY_MASTER_KEY`, so an environment that gets the key later than the data
has hashes built against the wrong key and patients are unfindable by address ([DEPLOYMENT](../DEPLOYMENT.md)).

---

## ☠️ Traps

1. ☠️ **`is_demo = 0` is a real answer.** `migrate:patient-test-results` filtered its answer rows with
   `whereNull('a.is_demo')`, which keeps only `NULL` and drops every genuine answer. The test then has no
   answers, which is **not** reported as an error — `scoreOne()` reads it as assigned-but-never-taken,
   writes no `result_json`, and `--fix-never-sat-status` rewrites a completed test to `abandoned`.
   Fixed by `ws-459` (`is_demo = 0 OR IS NULL`); every live query in the app uses `where('is_demo', 0)`.
2. ☠️ **Never-shown plates are not passes.** The pass mark was `count($answers) - $missed`, counting
   plates the patient never saw (`patient_answer` of `-1`/`-2`) as passed — so a section abandoned after
   two plates scored `Normal` and put "Normal Color Vision" on the record. Fixed to
   `$platesShown - $missed`, which is also the number reported as `total` beside it. This is the
   fabricated-diagnosis case `UnscoreableTestException` exists to prevent, reached from the other side.
3. ☠️ **Saving a partially-loaded `Patient` used to destroy its blind indexes.** The `saving` hook
   rebuilds them from `$this->attributes`, and a model loaded with a narrowed `select()` has no `email`
   attribute at all — indistinguishable from a null email, so the hash was overwritten with `NULL` and
   the patient became permanently unfindable by address. `syncBlindIndexes()` now skips any index whose
   source column was not loaded (`Patient::BLIND_INDEXES`).
4. **A legacy row flagged `is_encrypted = 1` can hold plaintext.** These exist in production data and the
   `-1`/`-2` sentinels are the worst of them — `LegacyCipher::decode()` rejects a minus sign before a key
   is consulted. Because a single unreadable answer discards the whole test, a ~1.5% rate of mislabelled
   rows removed a third of all migrated results on the first production-scale run. `readValue()` and
   `decryptLegacyPii()` both carry a plaintext fallback; see `tests/Unit/Migration/MislabelledAnswerRowsTest.php`
   and `MislabelledPatientPiiTest.php`.
5. **Patients are still not deduplicated on create.** `PatientAddRequest` has no uniqueness rule, and
   `identification` is not a unique index, so `whereEmail()` can legitimately match several rows.
   `TestController::patientByEmail()` prefers the caller's own patient and orders by id so the choice is
   at least deterministic — an arbitrary pick used to surface as a spurious "patient not found", because
   the ownership check immediately after rejected another customer's row.
6. **Legacy admin screens truncate and clamp their counts.** Verify any migration discrepancy with SQL
   against both databases, never by comparing the old UI against the new one.
7. **`migrate:*` reads `OLD_DB_CONNECTION`.** It is a second connection, configured separately; the
   commands fail loudly if it is absent rather than migrating nothing quietly.

---

## See also
- [PATIENT_CONTEXT](PATIENT_CONTEXT.md) — the patient subsystem these columns belong to
- [SECURITY](../SECURITY.md) — where the key-handling finding is tracked
- [TEST_EXECUTION_CONTEXT](TEST_EXECUTION_CONTEXT.md) — how `testanswers` is written live
- [HOW_TO_REGENERATE](../GUIDES/HOW_TO_REGENERATE.md) — how `INDEXES/` is rebuilt (this pack is in it since 2026-09-21)
- [DEPLOYMENT](../DEPLOYMENT.md) — the `LEGACY_*` env keys and the one-off post-deploy backfill
