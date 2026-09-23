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
| `app/Services/Migration/LegacyLocationResolver.php` | Legacy country/state text → `countries`/`states` ids (`ws-459` PR #282). Shared by `migrate:tcv-users-orgs` and the backfill below. [Section](#legacy-countrystate-resolution-ws-459-pr-282) |
| `app/Console/Commands/BackfillMigratedUserLocation.php` | `users:backfill-legacy-location`: re-resolves already-migrated users (`ws-459` PR #282). Dry run by default |
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
8. ☠️ **`insertOrIgnore` against a column with no unique index is a plain INSERT.** It ignores a row only
   when one would be *violated*, so aimed at an unconstrained column it is a rename of `insert()`.
   `runPreMigrationSetup()` wrote the legacy `compliance` and `type` values on top of the ones
   `CompliancesTableSeeder` / `OrganizationTypesTableSeeder` had already inserted, and neither
   `compliances.compliance` nor `organization_types.name` carried a unique index — so every run appended
   another full copy. QA (`tcv_qa_db_test`) held `AICC/SABA` and `Non AICC/SABA` **twice** (ids 1–2
   seeded with NULL timestamps, 3–4 stamped by the run) plus **6** duplicate organisation types, and the
   Add Organisation dropdowns render one `<option>` per row. The lookup caches compounded it: keyed by
   value and **last-row-wins**, so all **63** migrated organisations bound to the duplicate rather than
   the seeded row — and because `validations.js` hardcodes `compliance_id === "1"` as AICC/SABA, all
   **57** AICC/SABA orgs were being asked for a Static IP they should never need
   ([ORGANIZATION trap 8](ORGANIZATION_CONTEXT.md)). ✅ Fixed by `ws-459` (**merged 2026-09-21, PR #271,
   `2fb62959`**): `2026_09_21_000001_deduplicate_organization_lookup_tables` merges
   each value onto its **lowest** id — repointing `organizations` **before** deleting, since neither
   column has an FK and the Organisations grid left-joins, so a dangling id reads as *no compliance at
   all* — and adds the two missing unique indexes; `importLookupValues()` replaces both `insertOrIgnore`
   loops with a case-insensitive existence check and warns when the index is absent. Only these two
   lookup tables are written by any `migrate:*` command; `countries`, `states`, `allowed_tests` and
   `privileges` are unconstrained too but seeder-only, so they cannot duplicate this way.
   ⚠️ `patients` and `credits` carry a `legacy_id` with **no unique index on it** — both rely entirely on
   `migration_tracker`, and `transaction_details` is the same defect already proven to leak (hence
   `deduplicateTransactionDetails()`). Pinned by
   `tests/Feature/Migration/InsertOrIgnoreNeedsUniqueConstraintTest.php`, which fails if a new
   `insertOrIgnore` targets an unconstrained table.

---

## Legacy country/state resolution (`ws-459`, PR #282)

Merged 2026-09-22 (backend `0197fd1b`, frontend `22e5332`). Legacy `tcv_user.country` / `.state` are free
text (`VARCHAR(100)`), filled from two hardcoded picklists in the old helper. The new system stores
ids into the nnjeim/world `countries` / `states` tables. The old migration used a bare name lookup with
one special case ("United States Of America"), so it silently wrote `0` for everything else that did not
match exactly.

**`LegacyLocationResolver::resolveLocation($country, $state, $city)`** is the single entry point. Both
commands call it, so they behave identically. Its order is fixed:

1. `PAIR_SUBSTITUTIONS` wins outright. The only entry is `'0'|'0'` → United States / Alabama.
2. The country resolves by name, then `COUNTRY_ALIASES` (34 retired ISO spellings, e.g. "Russian
   Federation", "Swaziland", "Hong Kong"), then iso2/iso3. Codes are tried only for a value that is
   nothing but 2–3 letters.
3. If that fails and the country column is **digits**, the country comes from the state name, but only
   when exactly one country uses that state name.
4. The state resolves **scoped to that country**, then through `STATE_ALIASES` (US postal codes, the
   `'rhode'` / `'island'` picklist typo, German state names).
5. If that fails and the state is a word, it is tried as a **city** in that country (a unique match only).
6. If the state is digits, the legacy **city** column is used to find the state instead.

Returns `['country_id' => int, 'state_id' => ?int]`. `country_id` falls back to `NO_COUNTRY = 0` because the
column is NOT NULL. ⚠️ An unresolved **state is now `null`, not `0`** as the old `resolveStateId()` wrote.

### ☠️ Traps
1. **The `'0'|'0'` substitution invents data, and is the only rule that does.** It is there on the
   client's instruction, so the new screens show what the old ones displayed: `drawDD()` had no
   placeholder, so every off-list value rendered as the first option, United States / Alabama. After
   the backfill those accounts are **indistinguishable by id from genuine Alabama accounts**.
   `users.legacy_country` / `legacy_state` still hold the original `'0'`, and they are the only way back.
   Country `'1'` (281 accounts) and states `'5'`/`'10'`/`'34'`/`'44'` are deliberately **not** substituted.
2. **Legacy admin screens are not evidence of what was stored.** Because of the same `drawDD()` fallback,
   a blank, an `'US'` and a `'Rhode Island'` all *displayed* as the first option. Verify against
   `tcv_user` with SQL. The migration's report prints blanks ("empty there too — nothing lost")
   separately from unresolved values, which are missing aliases.
3. **ISO codes are accepted, so `'NA'` resolves to Namibia and `'NO'` to Norway.** This was taken on
   purpose after `tcv_user` 40167 turned out to store `'US'`.
4. **Re-running `migrate:tcv-users-orgs` does not fix migrated users.** It skips `completed` tracker rows
   and only ever inserts. Use **`php artisan users:backfill-legacy-location`**, which is a dry run until
   you pass `--apply`. It reads `OLD_DB_CONNECTION` by `users.legacy_id`. It fills only a **missing**
   country/state and never overwrites a value set since the migration, except for a state seated in
   the wrong country (migration damage, because the form cannot produce that pair). Opt-in flags:
   `--infer-country-from-state` writes a country the legacy row never held, and
   `--clear-wrong-country-states` nulls a stored id. Both are operator decisions, so neither is on by
   default. Also `--user-id=`, `--email=`, `--chunk=500`.
5. **`users.legacy_country` / `legacy_state`** (`2026_09_22_000004`, `string(100)`, nullable) hold the raw
   legacy text. **NULL means "not a migrated account"**. They are not in `$fillable`, so no request can
   set them, and only the two commands above write them. Once the legacy DB is retired they are the only
   surviving copy. The migration was **renumbered** from `000001` (a clash with the `ws-401` template
   migration), and each column is `hasColumn`-guarded so an environment that ran the old name is a no-op.
6. **Display fallback lives on the model.** `User::displayCountry()` / `displayState()` return
   `{value, verified}`. The mapped name wins, then the legacy text with `verified = false`, then `null`.
   `Controllers/Concerns/FormatsLocationLabels::locationLabel()` suffixes the unverified value with
   **"(as migrated, unverified)"** on both the `UserController` and `OrganizationController` detail views.
   An organisation's Country/State are its owner user's.

### ☠️ The frontend half of the fallback is dead on arrival
Frontend PR #414 adds three fallbacks, all reading `legacy_country` / `legacy_state` off the API payload:

- the User Management grid's Country cell (`userManagementColumns.js`, class `um-country--legacy`)
- the "Previously recorded as …" hint under the Country/State selects in `NewUserModal.js`
- the same hint in `OrganisationModal.js` (reading `initialData.user.*`)

Backend PR #282's last review commit, `02f8c5d8`, then added both columns to **`User::$hidden`**, so that
login and profile responses would not leak raw legacy text. But every endpoint behind those screens
serializes a `User` model: `UserController::userWithType()` (the grids, paginated `toArray()`),
`UserController::edit()` (`GET api/users/{id}`, `response()->json($user)`), and
`OrganizationController::index()`, which eager-loads `user` for the Organisations grid that
`OrganisationModal` is opened from. **None of them calls `makeVisible()`**, so the fields never arrive and the grid shows `—`, and the modals show nothing, for a
migrated account whose value did not map. Only the backend detail views (trap 6) show the legacy text.
`CONTRACT_DRIFT` cannot catch this, because it matches URLs, not fields. Found at the 2026-09-23 KB sync.

⏳ **Fixed on branches, not yet merged (2026-09-23):**
- **Backend `ws-459-legacy-location-visible`** (`b3506b2c`) adds
  `User::LEGACY_LOCATION_FIELDS` and calls `makeVisible()` in `OrganizationController::index()` for each
  row's owner (already super-admin only through `OrgPolicy::viewAny`). It also calls it in
  `UserController::userWithType()`, but **only for a super admin**, because that route checks nothing
  beyond `auth:sanctum` ([S-22](../SECURITY.md#s-22--any-signed-in-account-can-promote-itself-to-super-admin-through-put-apiusersid)).
  `edit()` is untouched: the user modals are filled from the grid row, and `edit()` also serves the
  signed-in user's own profile fetch. `$hidden` stays the default. Pinned by
  `tests/Feature/Migration/LegacyLocationAdminPayloadTest.php`. Its two reveal cases fail without the fix,
  and its two withhold cases pass either way.
- **Frontend `ws-459-legacy-location-visible`** (`c1dd22a`). There was a **second** break the backend fix
  alone would not have cured: `mapOrganisationToFormData()` (`utils/organisationUtils.js`) rebuilds
  `user` field by field and dropped both values, so `OrganisationModal` still saw nothing. It now carries
  them through. The create/update payloads in `Organisation.js` list their fields explicitly, so the
  values are display-only and are never sent back. Pinned by `src/utils/organisationUtils.test.js`.

---

## See also
- [PATIENT_CONTEXT](PATIENT_CONTEXT.md) — the patient subsystem these columns belong to
- [SECURITY](../SECURITY.md) — where the key-handling finding is tracked
- [TEST_EXECUTION_CONTEXT](TEST_EXECUTION_CONTEXT.md) — how `testanswers` is written live
- [HOW_TO_REGENERATE](../GUIDES/HOW_TO_REGENERATE.md) — how `INDEXES/` is rebuilt (this pack is in it since 2026-09-21)
- [DEPLOYMENT](../DEPLOYMENT.md) — the `LEGACY_*` env keys and the one-off post-deploy backfill
