# Context: Patients

> Load this **instead of** reading the patient subsystem. ~3,000 tokens (the export section below is about two-thirds of it). A "patient" is the *subject* of a
> test and is **not** a `User` — patients never authenticate.

## Files
| File | Role |
|---|---|
| `app/Models/Patient.php` | 9 fillable columns, two relations, `GENDERS` + `genderLabel()` |
| `app/Http/Controllers/PatientController.php` (432 lines) | CRUD, `getPatientTests()`, `resendTestLink()` |
| `app/Http/Controllers/OrganizationPatientController.php` (305 lines) | Org/LMS intake (`default`, `prolific`) |
| `app/Http/Requests/PatientAddRequest.php` · `PatientUpdateRequest.php` | Validation |
| `app/Services/PatientTestTransformer.php` | Shapes the patient→tests payload |
| `app/Models/ProlificId.php` | Prolific research-panel identity |
| `app/Services/Reports/PatientExportService.php` · `app/Exports/PatientCsvExport.php` | Patient CSV export: query + streaming writer ([below](#patient-csv-export--on-develop-since-2026-09-29-pr-291--420)) |
| `app/Http/Requests/PatientExportRequest.php` · `Requests/Concerns/ValidatesPatientExportDateRange.php` | Export validation + the one-calendar-year cap |
| `config/exports.php` | `patient_max_rows` (`PATIENT_EXPORT_MAX_ROWS`, default 50 000) |

## Tables
`patients` · `patient_tests` · `prolific_ids` · `organization_patient_sessions`

---

## ☠️ Patient PII is encrypted at rest — on `develop` since 2026-09-18 (`ws-459`, PR #255)

✅ **Shipped and indexed.** This is the first thing to know before writing any patient query:
`first_name`, `last_name`, `email`, `dob`, `zipcode` and `test_eyes` are held in the legacy
CodeIgniter cipher with a **random IV**. No SQL comparison against those columns can match — exact
lookups go through the keyed-md5 blind indexes (`identification`, `*_name_ident`) and substring search is
done in PHP by `PatientNameSearch`. `patient_id` stays plaintext deliberately, which is why it is still
matchable in SQL.

Two traps that follow from it are listed below (5) and in the full pack:
[DATA_MIGRATION_CONTEXT](DATA_MIGRATION_CONTEXT.md).

---

## Shape

```php
// Patient::$fillable
'first_name', 'last_name', 'dob', 'user_id', 'patient_id', 'email', 'zipcode', 'test_condition', 'gender'
```

- **`user_id`** — the *owner* (the clinician/customer/org `User`), not the patient. This is the only
  tenancy boundary in the table.
- **`patient_id`** — the owner's own external reference (a chart number), free text. Not the primary key.
  `patients.id` is the primary key and is a sequential integer.
- **`gender`** — nullable tinyInteger: `1` Male · `2` Female · `3` Intersex. The accepted values live
  once in `Patient::GENDERS` ([CONSTANTS](../INDEXES/CONSTANTS.md)); both patient FormRequests alias it
  (`const GENDER = Patient::GENDERS`) and build their `in:` rule from its keys, and
  `Patient::genderLabel()` is the only label mapping (`TestService`, `TestResultService`, and through
  them the result PDF). Add a gender in the constant and validation, reports and the PDF all follow.
  `null` means *not specified* and `genderLabel()` returns `null` for it — the org intake paths
  (`storeDefaultPatient`, `storeProlificPatient`) do not validate gender at all and default it to null.
- `Patient::tests()` → `hasMany(PatientTest, 'patient_id')` — keyed on `patients.id`, not on the
  `patient_id` column. Two similarly-named things; read carefully.

---

## The three ways a patient is created

| Path | Endpoint | Guard |
|---|---|---|
| Clinician adds one | `POST api/patients` | `FlexibleAuthMiddleware` |
| Org form (standard) | `POST api/organization/patient/default` | `FlexibleAuthMiddleware` + `lms.status:launched,identity_resolved` |
| Prolific panel | `POST api/organization/patient/prolific` | same |

☠️ **The org paths also issue a `TestSession`, and it must carry `patient_id`** (column added
2026-09-02). These sessions have `test_invitation_id = null`, so without that binding they have no
verifiable identity and every ownership check downstream either denies them outright or falls back to
forgeable client input — which is exactly what happened. The resume path must carry the binding across
too. See [AUTH_CONTEXT](AUTH_CONTEXT.md#-auth_context--the-only-trustworthy-answer-to-who-is-calling-2026-09-02).

The org paths honour the organisation's display flags (`anonymize_patient`, `show_gender`, `show_zip`,
`show_patient_id`, …) — see [ORGANIZATION_CONTEXT](ORGANIZATION_CONTEXT.md). An anonymised org creates
patients with **no real name**, which is exactly what `index()` keys off to compute `is_prolific`:

```php
'is_prolific' => $isProlificOrg && (empty($p->first_name) || $p->first_name === 'N/A'),
```

☠️ The literal `'N/A'` sentinel is load-bearing. Changing what the anonymised form writes into
`first_name` silently breaks the prolific flag.

---

## ☠️ Traps

1. ✅ **Fixed 2026-09-02 — `show()`, `update()` and `destroy()` are scoped.** They previously had no
   ownership check at all (only `index()` filtered by `user_id`) while being reachable by *any* of the
   four token tiers, ids being sequential. They now go through `PatientController::callerOwnsPatient()`,
   which reads the `auth_context` attribute — **not** request input — and return
   `api.patient_not_found` (404). `destroy()` is staff-only: a test-taker's session cannot delete the
   record it is bound to. [S-14](../SECURITY.md#s-14--patientsid-showupdatedestroy-have-no-ownership-scoping).
2. ✅ **Fixed 2026-09-02 — `update()` uses `$request->validated()`** and explicitly drops `user_id`.
   It previously used `$request->all()`, bypassing `PatientUpdateRequest`'s filtering so every fillable
   column was writable — and `user_id` is fillable *and* required by the rules, so a single PUT
   reassigned the patient to another account. Ownership is not editable through this endpoint.
   Same finding.
3. **`Patient` soft-deletes; `PatientTest` does not.** `destroy()` sets `deleted_at` and leaves every
   `patient_tests` row live and queryable. Any query that joins **through** `patients` silently drops
   those tests (the global scope hides the parent), while a query straight off `patient_tests` still
   returns them — so a deleted patient's tests appear in one report and vanish from another. Use
   `withTrashed()` on the join when the report is meant to be complete.

   Exactly **four** models soft-delete: `User`, `Patient`, `DiscountCode`, `LmsProviderConfig`. Check
   the `Traits` column in [MODEL_INDEX](../INDEXES/MODEL_INDEX.md) before assuming.
4. **`dob` is stored as written.** No cast on the model, no timezone handling. Age arithmetic in reports
   and in `ColorVisionDiagnosisService` reads it as a raw date string.
5. **Patients are never deduplicated.** Nothing enforces uniqueness on `email` or `patient_id`, so the
   same person invited twice becomes two `patients` rows with separate test histories.

   ✅ `ws-459` (on `develop` 2026-09-18) narrows this on the **update** path only: `unique:patients,email`
   was a silent no-op once `email` became ciphertext, and it is replaced by a check through the blind
   index. Creation is unchanged and still admits duplicates, and `identification` is a plain index, not a
   unique one — so `whereEmail()` can still match several rows.
   [DATA_MIGRATION_CONTEXT](DATA_MIGRATION_CONTEXT.md).
6. **`resendTestLink()` lives here, not in `TestInvitationController`** — `POST api/resend-test-link`
   (`auth:sanctum`). If you are changing invitation resend behaviour, there are two places.
7. **The SPA holds `gender` in two different shapes.** The fallback selector (`AddPatient.js`) stores the
   *label* — `form.gender === "Male"` — and `usePatientForm.buildPayload()` converts it with
   `getGenderValue()`. The org/field-rules selector (`PatientFormFields.jsx`) stores the *stringified id*
   — `form.gender === "1"` — and `formUtils.buildPayload()` ships it as-is. Both land in the same column.
   `GENDER_OPTIONS` in `src/utils/testUtils.js` is the single list both render from, but check which
   shape a form is in before comparing `form.gender` to anything.
8. ☠️ **Add Patient validates `email` two different ways on the same screen (`ws-407`, merged
   2026-09-08).** The split follows trap 7's: `AddPatient.js` calls `formUtils.validateForm()` only on
   the **org/field-rules** branch (`!isEditMode && fieldRules`), and everything else — edit mode, and
   the no-field-rules fallback — goes to `usePatientForm.validate()`.
   - `formUtils.js` now has `EMAIL_PATTERN` wired into `FORMAT_VALIDATORS.email`. It is deliberately
     stricter than the `/^\S+@\S+\.\S+$/` check used in `usePatientForm.js`, `services/validations.js`
     and `SendTestModal.js`, which accepts stray `/ [ ;` in the local part — `skdjfh234424//[;/sdf234@
     yopmail.com` passed silently before.
   - So the strict pattern is live on the org intake path **only**. `usePatientForm.js` imports
     `NAME_PATTERN` and `PATIENT_ID_PATTERN` from `formUtils` but *not* `EMAIL_PATTERN`; the loose regex
     is still inline there. Adding a pattern to `formUtils` does not reach the fallback form — wire it
     into the hook as well, or the same address is accepted on one branch and rejected on the other.
   - `FORMAT_VALIDATORS` run through `validateFormat()`, which returns early on a blank value. Format
     checks apply **whenever a value is present**, required or not; requiredness is a separate branch
     above them. Nothing on the backend enforces this — `PatientAddRequest` is the only real gate, and
     patients are never deduplicated by email (trap 5).
9. **The Patients menu's password prompt is a client-side speed bump.** The SPA asks for the password
   before navigating into `/user-panel/patients`, but `api/verify-password` is stateless and no patient
   endpoint knows the prompt exists — a typed URL or any in-app `navigate()` walks straight past it.
   ws-399 (2026-08-28, merged into `TCV-Frontend@develop`) narrows it further: no re-prompt once you are already inside the
   section. [FRONTEND.md](../FRONTEND.md#the-patients-menu-password-prompt-is-client-side-only).

---

## Patient CSV export — on `develop` since 2026-09-29 (PR #291 / #420)

✅ **Shipped and indexed** (backend `a5938b2e`, frontend `308fe7a`). The User Panel's **Patients ▸
Registered** tab's *Export Patients* button now downloads a **server-built, audited** CSV. It replaces
`src/components/ExportPatients.js` (deleted), which assembled the CSV in the browser from the on-screen
list, wrote no audit row, and filled most of its 13 columns with `''` because those fields were never
in the payload. The old modal's password field only ran a fake `setTimeout` check; it has been
removed, not replaced.

(An earlier attempt went straight to `develop` as `7a28fa6e` and was reverted the same day by
`e8507fb9`. That is why the 2026-09-29 sync recorded it as "net zero".)

```
PatientPage.js ── "Export Patients" (disabled when the tab lists 0 rows)
  └─ ExportPatientModal.js       From / To pickers (mm/dd/yyyy), the scope note, the error line
       └─ exportFnRef.current({from, to})          set by RegisteredPatientsTab.js
            └─ apis/exportPatients.js  GET api/patients/export?from&to&timezone[&filter|&search]
                                       responseType: 'blob', skipErrorPopup: true
GET api/patients/export   (API-062 · auth:sanctum · throttle:patient-export, 10/min per user id)
  ├─ PatientExportRequest            from/to required Y-m-d · to ≥ from · timezone:all_with_bc
  │    └─ ValidatesPatientExportDateRange   to ≤ viewer's today · to ≤ from + 1 calendar year
  ├─ PatientExportService::countRows()      > config('exports.patient_max_rows') → 422 + failed audit row
  ├─ AuditService::log('patient.exported', success)   null → 500, nothing streamed
  └─ PatientCsvExport::stream()      BOM · 2 banner rows · header · rows (keyset pages of 500 patients)
                                     · "End of Export, N row(s)" trailer → audit row's count corrected
```

**What is in the file.** It has one row per test attached in the window, plus one row reading
`No tests in range` for each patient who has no such test. The patient set is **exactly the
Registered tab**: `Patient::applyRegisteredTabScope()` is now the single definition shared by
`index()` and the export. The date range narrows **tests**, never **patients**. A test is attached
when it was *sent* (`created_at`) **or** *completed* (`result_generated_at`) inside
`[from 00:00, to+1 00:00)` in the viewer's timezone. Columns are fixed by `PatientCsvExport::COLUMNS`:
First/Last Name, Email, DOB (`m/d/Y`), Patient ID, Zip, Gender label, Test ID (`unique_test_id`), Test
Name, Eye Tested, Date Test Sent (UTC), Test Status, Completion Date (UTC), Calculated Report
(`result_json → $.diagnosis.calculated`, extracted in SQL), Patient IP Address.

**Search and filter mirror the screen.** If a search term is present it wins and the quick filter is
ignored, the same rule `RegisteredPatientsTab.js` applies. Search matches decrypted names through
`PatientNameSearch` (now with `require_test => false`), `patient_id` through an escaped `LIKE`
(`ESCAPE '!'`), and a bare `1`/`2`/`3` as a gender code. `today`/`yesterday`/`lastWeek` filter
`patients.updated_at`. `frequent` is accepted and **does nothing**, on the server and on the screen
alike.

### ☠️ Export traps

1. **`whereNumber('patient')` on `apiResource('patients')` is load-bearing.** Without it,
   `GET patients/export` matches the resource's `GET patients/{patient}` first and lands in
   `show('export')` → 404. It is the same trap, with the same fix, as `AuditLogController@show`. Any new
   literal segment under `patients/` needs that constraint kept.
2. **The export sits in the `auth:sanctum` group, not the `FlexibleAuthMiddleware` group.** The
   resource itself sits in `FlexibleAuthMiddleware`. The split is deliberate: it guarantees a real
   `$request->user()` for the audit row and keeps this PHI dump away from every patient-session token
   tier. Do not "tidy" it into the resource's group.
3. **The audit row is written *before* streaming, and a failed write blocks the export.**
   `AuditService::log()` never throws; it returns `null`. `export()` checks for that `null` and returns
   500 (`api.patient_export_audit_failed`) before any PHI leaves the server (HIPAA §164.312(b)). Once
   `streamDownload()` starts, the 200 is already on the wire and no error response is possible.
4. **"Number of Records" is provisional and gets corrected after the stream.** `countRows()` and
   `streamRows()` are separate queries with no shared snapshot. The `onComplete` callback rewrites the
   audit row's count to the rows actually written (`recordStreamedRowCount()`). If the stream dies, that
   correction never runs **and** the file has no `End of Export` trailer.
5. **The trailer is the only completeness signal, and the SPA enforces it.** `exportPatients.js` reads
   the last 512 bytes. If `End of Export, N row(s)` is missing, it throws `EXPORT_INCOMPLETE` and saves
   nothing. If you reword the trailer, change the regex in `hasEndOfExportMarker()` too.
6. **Blob error bodies.** With `responseType: 'blob'`, a 422 body arrives as a `Blob`, so the Axios
   interceptor cannot read `error_code`. `messageFromBlobError()` re-parses it, mirroring
   `exportAuditLogs.js`. Any new blob-download endpoint needs the same treatment.
7. **The year cap is a calendar year, not a day count, and both layers must clamp a leap day the same
   way.** The backend uses Carbon's `addYearsNoOverflow(1)` and the frontend uses `shiftYears()` in
   `constants/patientExport.js`. Both map `2024-02-29` + 1 year to `2025-02-28`. This rule is
   deliberately **not** `ValidatesAuditDateRange`'s 31-day cap; do not reconcile the two.
8. **"Not in the future" is judged in the viewer's timezone**, from the request's `timezone` (IANA,
   default UTC). That is why there is no `before_or_equal:today` rule. `all_with_bc` is required
   because browsers still report legacy aliases such as `Asia/Calcutta`.
9. **The search term never reaches the audit row.** The row says `Applied Filter: Search applied`.
   Only the allowlisted `filter` value or that phrase is logged, because a search term is usually a
   patient's name and `AuditService`'s masking would not catch a bare name. The term *does* appear in
   the file's own banner row (`PatientCsvExport::exportNote()`), since the file already holds those
   names.
10. **Three things must stay word-for-word in step across repos.** `PatientCsvExport::exportNote()`
    must match `exportScopeNote()` in `constants/patientExport.js` (the modal shows the sentence the
    file prints). The backend `api.patient_export_*` messages must match the modal's own copy. The
    trailer must match the SPA's regex.
11. **Query-builder reads: decrypt by hand, soft-delete by hand.** `PatientExportService` uses
    `DB::table()`, so PII arrives as ciphertext (`decryptPii()`) and `Patient`'s `SoftDeletes` scope
    never runs (`patients.deleted_at` is filtered explicitly). `patient_tests.deleted_at` is filtered
    too, even though `PatientTest` has no `SoftDeletes` (trap 3).
12. ⚠️ **The MySQL branch of the diagnosis expression is unverified against a live MySQL.**
    `JSON_UNQUOTE(JSON_EXTRACT(...))` is suspected to print the literal `null` for a JSON `null`,
    where SQLite returns an empty cell. Only the SQLite branch runs end-to-end in tests. The docblock on
    `diagnosisSelectExpression()` names the fix (`JSON_TYPE(...) = 'NULL'`, **not** `NULLIF`).
13. **DOB is parsed US month-first or ISO, never through `Carbon::parse()`.** `parseUsDate()` accepts
    `m/d/Y`, `m-d-Y` and `Y-m-d`. Anything else is printed exactly as stored, so a value like `today`
    is not turned into a plausible-looking date. Timestamps are printed `Y-m-d H:i:s UTC`. Do **not**
    add `->utc()` in `formatUtcTimestamp()`: the values are already UTC strings, and a second shift
    corrupts them (guarded by
    `test_timestamp_cells_stay_utc_under_a_non_utc_app_timezone`).
14. **Every cell goes through `escape()` against formula injection**, identical to
    `AuditLogCsvExport::escape()`. A leading `= + - @ \t \r` is prefixed with `'`.
15. **Rate-limit rejections are audited, at most once per minute per caller.**
    `AppServiceProvider::patientExportRateLimitedResponse()` writes a failed `patient.exported` row
    guarded by `Cache::add('audit:patient-export-rate-limit:{key}', …, 60)`.
    `PATIENT_EXPORT_PER_MINUTE` (10) and `…_WINDOW` (60) are coupled, so change them together. The
    limiter key is the user id, falling back to the IP.
