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
| `app/Services/Reports/PatientExportTracker.php` | 🔶 unmerged: appends the `patient.export_outcome` entry (Completed Yes/No); never edits an audit row ([pending section](#-pending-not-on-develop-yet-export-review-follow-up-2026-10-01)) |
| `config/exports.php` | `patient_max_rows` (`PATIENT_EXPORT_MAX_ROWS`, default 50 000; 🔶 **5 000** on unmerged `improve/export-format`, see [the XLSX update](#-update-2026-10-05-xlsx-workbook-5-000-row-cap-clear-button)) |

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

**Derived dates (branch `feat/export-completion-and-sent-date`, unmerged).** Two columns are computed at read
time in `PatientExportService`, with no schema change; each expression is shared by the SELECT and the
window predicate. *Date Test Sent* for an email-invite test (`test_invitation_id` set) is the invitation's
latest send, `COALESCE(email_sent_at, created_at)`, instead of the test's start time; other tests keep
`patient_tests.created_at`. Only the latest send is stored (a resend overwrites it), and an invite never
started has no row. *Completion Date* is `result_generated_at`, falling back for migrated completed tests
(`legacy_id` set) to `updated_at`. **That fallback is unstable:** any later write to the row moves the
date and can move the test in or out of an export window.

### 🔶 Pending, NOT on `develop` yet: export review follow-up (2026-10-01)

Branches: backend `improve/export-format`, frontend `ui/refine-export-patient-modal`. **Everything in this
section is unmerged; the diagram and traps below still describe `develop` until it merges. Deploy both
repos together** (an old SPA bundle calling `GET` gets an error, reported as a 500 by `Handler.php`).
Re-run `composer regenerate` and update the API/route indexes and the counts after the merge.

**Why:** code review found (1) the modal could be dismissed mid-export, so a second export, second audit
row and second rate-limit slot were possible; (2) `search` (often a patient's name) travelled in the URL
and so reached the Nginx access logs; (3) a timezone PHP could not resolve returned 422 and blocked the
export; (4) an export cut off mid-stream stayed `success` in the audit log.

| Area | Change |
|---|---|
| Route | `GET` → **`POST api/patients/export`**; `from`, `to`, `timezone`, `filter`, `search` go in the body. The GET route is removed. `whereNumber('patient')` stays as a guard for future literal segments (trap 1 no longer bites this route) |
| Timezone | `PatientExportRequest::prepareForValidation()` **drops** a `timezone` PHP cannot resolve (same `DateTimeZone::ALL_WITH_BC` list as the rule), so the export proceeds in **UTC** instead of failing 422. Trap 8's rule is otherwise unchanged |
| "Future" check | With no usable timezone, `ValidatesPatientExportDateRange` judges "future" against **UTC+14** (`Pacific/Kiritimati`), not UTC, so someone ahead of UTC is never refused their own today. A known timezone is still strict |
| Response header | `X-Export-Timezone` = the zone actually used (`UTC` after a fallback). Exposed through `config/cors.php` with `Content-Disposition` and `Retry-After`. The SPA compares it with what it sent |
| Audit on early stop | `PatientCsvExport` takes an `onInterrupted` callback and registers `register_shutdown_function([$this, 'handleShutdown'])` (PHP skips `finally` when a client disconnects but runs shutdown functions). Two append-only entries per export. **Started:** `patient.exported`, written before streaming, `success`, `Patient export started.`, details `Export Format` · `Records Requested` · `Date Range` · `Applied Filter`; never edited again. **Outcome:** `patient.export_outcome` ("Patients export outcome"), appended by `PatientExportTracker` when the export ends: `Completed: Yes` + `Number of Records` (streamed count) + `Export Entry ID` (the started entry's id), `success`; or `Completed: No` + `Reason` + `Records Sent Before It Stopped` + `Export Entry ID`, status `failed` (the outcome entry fails; the started entry stays `success`, so a report on `success` still lists every export that began sending). Reasons never claim intent: **"…the connection was dropped before the file was fully delivered"** when `connection_aborted()`, **"…it was stopped by a server error or timeout"** otherwise. Cancel, reload, closed tab and network drop look identical to the server, so there is deliberately **no** cancel endpoint. Develop's in-place count correction (`recordStreamedRowCount()`) is removed. **Best effort:** it only works while PHP is still writing rows (nginx buffering, a small export already handed to the proxy, or a cancel before streaming starts leave only the started entry, or record the export as completed). The hook is switched off after it reports once (`$finished`), since PHP cannot unregister a shutdown function |
| Frontend cancel | Best effort too: a Cancel pressed just after the response arrived may still save the file. It exists for long-running exports |
| Frontend modal | While exporting: Export disabled, Esc / backdrop / header X blocked (`backdrop='static'`, `keyboard={false}`, X hidden). **Cancel stays enabled**: it aborts the request (`AbortController`), saves nothing, shows no error, and closes. Note shown: "Your export is being prepared. Keep this window open, or select Cancel to stop it." |
| Frontend stale requests | An attempt counter (`attemptRef`) stops a request that finishes after Cancel or a reopen from closing or changing the modal now on screen |
| Frontend one-at-a-time | `exportPatients.js` keeps a module-level `exportInFlight` guard: a second export while one runs (e.g. after navigating away and back) is refused with `EXPORT_IN_PROGRESS`. Navigating away does **not** abort; the download completes |
| Frontend timezone | `viewerTimeZone()` omits a zone the browser reports as missing or unresolvable. With no zone, the modal says the days are read as UTC (the picker's "today" was UTC too until `80e6de7`; it is now always the local date, see below). After download, `notice` (`UTC_NOT_REPORTED_NOTICE` / `UTC_NOT_RECOGNISED_NOTICE`) is shown when the header says UTC but the browser's zone differed |
| Frontend errors | 429 → "Too many exports. Please try again in N seconds." from `Retry-After`. `messageFromBlobError()` now reads the blob with the FileReader helper (`Blob.text()` is missing in jsdom and old Safari) |
| Picker UI | From/To use the outlined calendar-icon field shared with the admin report screens (`DateRangeInput`, new opt-in `allowTyping` prop forwarding `id`, `onKeyDown`, `onFocus`, `onBlur`, `autoComplete`, `className`, `aria-invalid`, `aria-required`). Static labels, styles scoped to `.export-patient-modal` in `UserPanel.scss` (`.date-picker-input` is defined differently in three global stylesheets). Note copy split into `exportScopeLead()` (rendered bold) and `EXPORT_DATE_RANGE_NOTE`; hint is "Maximum selection range: 1 year" |
| Accessibility | The required `*` is `aria-hidden`; both date inputs carry `aria-required="true"`, so they read as "From" / "To". Each input's `aria-describedby` points at its always-present error line (`{id}-error`, `aria-live="polite"`) and the shared range hint (`export-patients-range-hint`); `DateRangeInput` forwards `aria-required`, `aria-invalid` and `aria-describedby` (react-datepicker 9 passes them through). The wait message sits in an always-rendered `<div role="status" aria-live="polite">` (a live region inserted together with its text is often not announced). The form is `aria-busy` while exporting and the dialog is `aria-labelledby` its title. `viewerTimeZone()` is memoised per mount. Tests look the fields up by accessible name (`getByRole('textbox', {name: 'From'})`), not label text |

#### 🔶 Update 2026-10-05: XLSX workbook, 5 000-row cap, clear button

Backend `2127d581` + `f361256d`, frontend `80e6de7`, all on the same two unmerged branches. **Local only (not
pushed) when this was written**; `origin/improve/export-format` was still at `c4786d3f` (CSV). **This pairing is
strict:** frontend `80e6de7` rejects every CSV download as incomplete, so it needs backend `f361256d` or later, and
`develop`'s SPA (GET + CSV trailer regex) cannot use this backend at all. A temporary GET route would not help.

| Area | Change |
|---|---|
| Format | `PatientCsvExport` is **deleted**; `app/Exports/PatientXlsxExport.php` replaces it (PhpSpreadsheet, already present via `maatwebsite/excel`; no new dependency). Same columns and row rules. `Content-Type` `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet`, file `patients_export_YYYYmmdd_His.xlsx`. Audit `Export Format` is `XLSX` |
| Cell safety | Every cell is written with `setCellValueExplicit(…, TYPE_STRING)`, so the file stores text, never a formula. The CSV `'` prefix (`escape()`) is **not** used: in an xlsx string cell it would show up literally. Cells are capped at Excel's 32 767 characters (`fitCell()`). Leading zeros are kept |
| Built, then sent | An xlsx is a zip and cannot be streamed row by row. `stream()` builds the whole workbook in memory, saves it to a temp file, then streams that file. Nothing reaches the browser until the build finishes |
| "End of Export" | Now a styled **last row of the sheet** (A `End of Export`, B `N row(s)`), visual only. It is no longer the completeness signal |
| Row cap | `PATIENT_EXPORT_MAX_ROWS` default **50 000 → 5 000** (`config/exports.php:34`). Measured without Laravel: 10 000 rows 138 MB / 11 s; 50 000 rows **522 MB** (over the 512 MB `memory_limit`, `custom.ini`) / 113 s (past nginx's default 60 s `fastcgi_read_timeout` → 504). 5 000 rows ≈ 90 MB / 4 s |
| Too large (422) | Two cases, both with `error_code`. Over the cap with **more patients than the cap** → `EXPORT_TOO_MANY_PATIENTS`, `api.patient_export_too_many_patients` ("Too many patients to export. Search or filter the patient list, then try again."). Otherwise → `EXPORT_TOO_LARGE`, `api.patient_export_too_large` (reworded: "Too many records to export. Choose a shorter date range, or search or filter the patient list."). `countPatients()` runs only on this refusal path |
| Build failure (500) | Any exception while building or saving → JSON `{success:false, status_code:500, message, error_code:'EXPORT_GENERATION_FAILED'}`, `api.patient_export_failed` ("We couldn't generate the Excel file. Please try again, or choose a shorter date range."), and an interrupted outcome entry with 0 rows |
| Temp files | `createTempFile()` calls `tempnam(sys_get_temp_dir(), 'tcv-patient-export-')` (mode 0600) and **immediately** registers a shutdown function that deletes it, before `save()`, so an out-of-memory or timeout fatal still cleans up. The stream closure also deletes it after sending. `deleteStaleTempFiles()` removes files with that prefix older than `STALE_TEMP_FILE_SECONDS` (3 600 s) to catch SIGKILL / container OOM-kill leftovers. A `tempnam()` failure throws and becomes the 500 above |
| Frontend completeness | `exportPatients.js` drops the `End of Export` regex (`hasEndOfExportMarker()`). `isCompleteXlsx()` looks for the zip end-of-central-directory signature `PK\x05\x06` in the last 22 + 65 535 bytes; missing → `EXPORT_INCOMPLETE`. The blob is re-wrapped as `XLSX_MIME_TYPE`; the fallback name is `patients_export_<ms>.xlsx` |
| Frontend messages | `EXPORT_FAILED_MESSAGE` = the backend's `api.patient_export_failed`, word for word. 502 / 503 / 504 or no response → `EXPORT_TIMED_OUT_MESSAGE`. A 422 shows the server's `message` as-is (`error_code` is not read), so the new 422 copy flows through without a frontend change |
| Slow notice | After `SLOW_EXPORT_MS` (15 000 ms) the modal's wait message says large exports take longer to prepare |
| Picker "today" | Always `localDateToIso(new Date())`, the viewer's local date, even with no timezone. The backend's no-timezone rule (UTC+14) accepts it |
| Clear button | `DateRangeInput` gains `onClear` + `clearLabel` (`allowTyping` mode only). The button (`.date-clear-btn`, `type="button"`) shows when the field has text and is not disabled, clears **only its own field** through the picker's `clear()` (focus returns to the input), and is hidden while exporting. It is out of the tab order (`tabIndex={-1}`); from the keyboard, deleting the text does the same. Accessible names "Clear From date" / "Clear To date". The report screens use `DateRangeInput` without `onClear` and are unchanged. Not react-datepicker's `isClearable`, which also shows on an empty From once To is set |
| Modal styles | Shared shell moved to `src/styles/components/UserPanelModal.scss` (class `user-panel-modal`), imported by `ExportPatientModal`, `PasswordVerificationModal` (plus its own new `PasswordVerificationModal.scss`) and `SendTestModal`. Previously the export modal borrowed `.send-test-modal` from `SendTestModal.scss`, which only loaded with the Home chunk, so a direct load of the patients page could show an unstyled header |

☠️ **Traps from this update**
- **Deploy in lockstep, no backward-compatible window.** Each side breaks against the other's old version: the old SPA sends GET and checks for the CSV trailer; the new SPA rejects a CSV as incomplete; the old backend has no POST. Open tabs on the old bundle fail until reloaded.
- **The 5 000 cap is a product trade-off.** An org with more than 5 000 registered patients can no longer export the full list in one file (it was allowed on `develop`); it must search or use a quick filter. Needs product sign-off. A streaming writer (e.g. OpenSpout, not a dependency) would remove the trade-off.
- **`PATIENT_EXPORT_MAX_ROWS` is only a default.** An environment that sets it (e.g. to the old 50 000) brings back the out-of-memory and 504 path. Check deployed env before release. Raise it only after re-measuring on the target infrastructure.
- **Register cleanup before you write PHI to disk.** `try/finally` does not run on a fatal; only a shutdown function does. Keep `register_shutdown_function` right after `tempnam()`.
- **The temp-file test passes vacuously on Windows.** `tempnam()` keeps only 3 prefix characters there (`tcvXXXX.tmp`), so `test_the_built_file_is_not_left_behind_in_the_temp_directory` and the stale sweep never match a real file on a Windows dev machine. Linux is unaffected. No test can cover a real fatal during `save()`.
- **`isCompleteXlsx()` is a heuristic.** A truncated file whose compressed tail happens to contain `PK\x05\x06` would pass. Checking the record's comment-length field (offset 20) against the bytes after it would tighten it.
- **Error bodies built by hand.** `PatientController` builds the 422 and 500 envelopes itself (`:268`, `:298`) because `ApiResponse::error()` cannot carry `error_code`; the scanner's `R-B10` flags both. Either give `ApiResponse::error()` an `error_code` parameter or narrow `R-B10`.
- **"Records Sent Before It Stopped" for an XLSX** is the full row count once any byte left: a partial zip cannot be read row by row.

☠️ **New traps from this change**
- **`audit_logs` stays append-only; the outcome is a second entry, not an edit.** The earlier design flipped the
  row to `failed` in place, which drops a partial disclosure out of `success` reports and leaves no trace of the
  edit (no `updated_at`). Now `failed` on `patient.exported` again means "nothing left the server" (too many
  rows, rate limit, audit write failed), and the started entry is always `success`.
- **Reading the trail:** a `patient.exported` entry with **no** `patient.export_outcome` entry whose
  `Export Entry ID` matches it means the outcome is unknown: still running, the worker was killed before the
  shutdown hook could write (SIGKILL, OOM, PHP-FPM `request_terminate_timeout`), the stream never started, or
  the outcome write itself failed (reported to the log only). A reconciler job to flag old unmatched entries is
  possible later and not built.
- **`Completed: No` does not mean nothing was disclosed:** `Records Sent Before It Stopped` says how much may have
  left. The SPA rejects an incomplete file (the `End of Export` trailer for CSV; the zip end record for XLSX
  since `80e6de7`), but that is enforced **only by this SPA**:
  a script could keep a partial file. The meaning of the outcome entry's `failed` status and the reconciliation
  rule need the compliance owner's confirmation before production.
- **`Completed: Yes` means the server finished writing the file, not that the browser received it.** A Cancel after
  PHP wrote the last row (a small export, or a buffered response from `php artisan serve` / nginx) cannot be seen
  by the server, so it is recorded as completed (description "Patient export finished (file fully sent by the
  server)."). To see a `Completed: No` locally, export something slow (seed ~15 000 patients with
  `PATIENT_SEED_COUNT`, under the 50 000-row cap) and cancel while it is running, ideally behind nginx + PHP-FPM.
  With the XLSX build (cap 5 000) nothing is sent until the workbook is built, so cancel during the build
  leaves only the started entry; to see `Completed: No`, cancel while the file itself is downloading.
  Only a receipt from the browser could close this gap (an extra call per export; not built), and a trailing-probe
  heuristic was rejected as unreliable.
- **Two entries per export now, and `Records Requested` on the started entry is the pre-flight count** (it is never
  corrected); the delivered count is `Number of Records` on the outcome entry. Anything that read the old
  `Number of Records` on `patient.exported` must read the outcome entry instead.
- **The interrupted-export path was only unit-tested** (the hook is called directly). A real client
  disconnect through PHP-FPM and both nginx layers has not been exercised: verify on QA (cancel a large export,
  check the row).
- **`search` / `filter` / `timezone` are still also read from the query string** (Laravel merges query and
  body), so a stale client calling `POST api/patients/export?search=...` would put a name back in the logs.
  Deferred: read `$this->json()` only and 422 on a query-string `search`.
- **Deploying GET → POST:** QA is deployed as one unit (both repos' `non-prod.yml` accept `deploy_target: both` plus a `frontend_branch` and `backend_branch`), so there is no one-sided window; testers with an old tab must reload. **No temporary GET route was added** (it would keep accepting `search` in the URL). Production needs a rollout plan (backend first, GET overlap for one release) and the production edge/CORS/buffering config in `TCV-Website` was not read. Note the `pull_request: closed` trigger on `dev`: merging one PR there can auto-deploy that repo alone.
- **A wrong-method call returns 500, not 405**, because `app/Exceptions/Handler.php` flattens
  `MethodNotAllowedHttpException`. Tests pin the route table instead of a status.
- **Backend date tests must not depend on the clock.** With no timezone the future check uses UTC+14, so "tomorrow in UTC" stops being the future once UTC passes 10:00 (two tests failed at 11:39 UTC for that reason). Pin time with `Carbon::setTestNow()` or build the date from `now('Pacific/Kiritimati')`.
- **Test mocks in CRA are reset between tests** (`resetMocks`): set `mockReturnValue` in `beforeEach`, not
  in the `jest.mock` factory. An Esc test passes vacuously while a calendar popup is open (it swallows the
  first Esc): close it first and assert `.react-datepicker-popper` is gone.
- **The wider leak is not fixed:** Nginx logs `$request_uri` and the Referer in both repos, and other
  endpoints (Invited-tab search, report searches, tokens in query strings) still put patient data and
  secrets in URLs. See the local, git-ignored note `TCV-Frontend/.claude/known-issues/phi-in-url-and-nginx-logs.md`.

**Tests (pending branches):** backend `Feature/PatientExportTest` **74** (route table, body helper
`exportBody()`, bad zone in body and query, UTC+13 future-date case, timezone header, `Retry-After` on 429,
started + outcome entries (completed, dropped connection, server stop; started entry byte-for-byte unchanged;
no outcome when the stream never ran; search term in neither entry), shutdown hook only fires when the trailer
was not reached and only once); `AuditEventCatalogTest` now counts **69** events (`patient_records` 6). Frontend: the
three export suites (`src/apis`, `PatientPage`, `constants/patientExport`) pass with **64** tests, including Cancel
aborts, stale completion ignored, the in-flight guard, 429 and 401 flows, UTC notices, and the accessibility
checks (`aria-describedby`, live regions, `aria-busy`, dialog name).

**After the 2026-10-05 update** (run on archived copies of the commits, not CI): backend `PatientExportTest` **86**
pass (987 assertions; reads the XLSX back, formula-shaped name stays text, temp file removed, stale sweep, 5 000
default, both 422 `error_code`s, `EXPORT_GENERATION_FAILED`). Full backend suite 1 430 pass / 3 fail; the same 3
fail on `develop` (`InvitationSendReviewFixesTest`, `NormalizeLegacyBracketPlaceholdersMigrationTest`,
`OrganizationEmailTemplateTypeTest`). Frontend: `src/apis/exportPatients.test.js`, `PatientPage` and
`src/components` suites, **155** pass (7 new clear-button tests). `exportPatients.test.js:190` still uses the old
422 wording as a fixture (it tests pass-through, so it passes).

### ☠️ Export traps (as on `develop`)

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
