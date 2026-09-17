# Context: Legacy Data Migration & Encryption at Rest

> Load this **instead of** reading the migration commands or the encryption support classes.
> ~1,400 tokens. Covers how legacy TCV v2 data is pulled into this app and how patient PII is held
> encrypted once it arrives.

🚧 **Status: `TCV-Backend@tcv_data_migration`, not on `develop`, not indexed.** Every count and file in
this pack was read from that branch by hand. The `INDEXES/` views do **not** include any of it — they
are generated from `develop` and
[must never be generated from a feature branch](../GUIDES/HOW_TO_REGENERATE.md). Check
`git merge-base --is-ancestor origin/tcv_data_migration origin/develop` before believing this pack
describes shipping code.

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
`config/legacy.php` on this branch; the value it used to carry should be treated as burned. Missing now
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

26 commands. `migrate:all-master` runs the base data in dependency order —
`users → organizations → patients → credits → price-details → discount-codes → restricted-ips →
user-emails → transactions` — and `migrate:tcv-all` drives the test-side ones. All extend
`BaseMigrationCommand`, which provides staging tables, chunking and the run report.

| Group | Commands |
|---|---|
| Orchestrators | `migrate:all-master` · `migrate:tcv-all` |
| Accounts & org | `migrate:users` · `migrate:tcv-users-and-organizations` · `migrate:organizations` · `migrate:user-emails` · `migrate:restricted-ips` |
| Patients | `migrate:patients` · `migrate:lookup-fsg-to-patient` · `migrate:backfill-migrated-patient-pii` |
| Tests | `migrate:tcv-tests` · `migrate:tcv-assign-tests` · `migrate:tcv-test-answers` · `migrate:patient-tests` · `migrate:patient-test-results` |
| Money | `migrate:credits` · `migrate:transactions` · `migrate:price-details` · `migrate:discount-codes` |
| Encryption | `migrate:encrypt-patient-data` · `migrate:encrypt-test-answers` · `migrate:encrypt-test-invitations` · `migrate:check-legacy-encryption` |
| Cleanup | `migrate:drop-migration-staging-tables` |

---

## ☠️ Traps

1. ☠️ **`is_demo = 0` is a real answer.** `migrate:patient-test-results` filtered its answer rows with
   `whereNull('a.is_demo')`, which keeps only `NULL` and drops every genuine answer. The test then has no
   answers, which is **not** reported as an error — `scoreOne()` reads it as assigned-but-never-taken,
   writes no `result_json`, and `--fix-never-sat-status` rewrites a completed test to `abandoned`.
   Fixed on this branch (`is_demo = 0 OR IS NULL`); every live query in the app uses `where('is_demo', 0)`.
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
- [HOW_TO_REGENERATE](../GUIDES/HOW_TO_REGENERATE.md) — why none of this is in `INDEXES/` yet
