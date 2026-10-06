> **Status:** Approved 2026-09-23, revised 2026-09-29 (v8) · implemented on both branches; v8 changes are listed below
> **Branches:** `feature/patient-export-server-side` in TCV-Backend and TCV-Frontend, both cut from `origin/develop`
> **Ticket:** audit-log the patient data export from the user panel

# Server-side patient CSV export, with audit logging

## Context

Exporting the patient list is the highest-risk PHI disclosure in the product — one click turns an organisation's patient table into a file on a workstation. Today it leaves **no trace**: `RegisteredPatientsTab.js:107` calls `exportPatientsToCSV(filteredPatients)`, which builds the CSV in the browser (`src/components/ExportPatients.js`) with no network round trip.

Two things make moving this server-side the right fix rather than bolting a "log my export" call onto the client:

1. **HIPAA §164.312(b)** wants the audit record and the disclosure to be the *same transaction*. A client that self-reports its own exports can simply not report one, and the record count it claims is unverifiable. Server-side, the row is written by the same request that produces the bytes.
2. **The current export is mostly empty.** `ExportPatients.js` opens with `// dummy data is here when we get actual api will integrate the api here`, and 9 of its 13 columns reference fields absent from the `/api/patients` payload, so they emit empty strings on every row. Only name, email and patient ID carry data.

`patient.exported` already exists at `AuditEventCatalog.php:400` (category `patient_records`, sensitivity `medium`) and is emitted by nothing. **No migration or schema change is required** — one optional index is called out in §1 and is not part of the default build.

> **v8 revisions (2026-09-29)** — these supersede anything below that says otherwise:
> 1. **Scope is the Registered tab, not "every patient you own".** The export uses the same clause as `GET /api/patients` (`Patient::applyRegisteredTabScope()`, shared by `index()` and `PatientExportService`). Invitees (no `patients` row) and migrated patients whose only tests are open invitations are excluded — they belong to the Invited tab.
> 2. **The date range is read in the viewer's timezone**, sent as an IANA `timezone` param (UTC when absent). The same zone drives the Today/Yesterday quick filters and the "no future date" check. CSV timestamp columns stay UTC.
> 3. **The audit `Date Range` detail** is `"<from> to <to> (<timezone>)"` (label no longer says `(UTC)`); the `AuditService` date allowlist key is now `date_range`. No `Tab` key is logged.
> 4. **The modal and the CSV banner describe what was applied** (search / quick filter), via `exportScopeNote()` in the frontend and `PatientCsvExport::exportNote()` on the backend.
> 5. **A download without the trailing `End of Export, N row(s)` line is rejected** in `exportPatients.js` ("export interrupted"); nothing is saved.
> 6. **The date pickers grey out future days and days outside the one-year / other-field bounds**, with the From day marked while To is open. Typed out-of-bounds dates show an error.
> 7. **Rejected attempts are audited** as `patient.exported` with status `failed` and a `Reason` detail: `Export too large` (controller, before streaming) and `Rate limit exceeded` (the `patient-export` limiter's response callback, de-duplicated per limiter window). Neither row carries the search term.

**The report's purpose is unchanged from the original: it is the patient list (the Registered-tab patients — see v8 note 1).** Every such patient is exported, with or without tests. The date range narrows *which tests* are attached to each patient, never which patients appear. This distinction is the thing most likely to be misread — by an implementer (§3) and by a user (§11), so it is stated in both places.

---

## 1. How many rows can this do without a queue?

There is no single hard number; there is a stack of limits, each with a known cost to lift. Measured from this repo's own config:

| Constraint | Current value | Binding? | Fix | Cost |
|---|---|---|---|---|
| `memory_limit` | 512M (`custom.ini:12`) | **No** | — | `fputcsv` to `php://output` keeps memory flat at any row count. This is why `AuditLogCsvExport` exists instead of a PhpSpreadsheet export. |
| nginx read gap | **unset → 60s default** (`nginx.conf`) | **Yes — the sharp edge** | `fastcgi_read_timeout 300s;` | 1 line, nginx |
| `max_execution_time` | 300s (`custom.ini:10`) | Yes, above ~5 min | `set_time_limit(0)` inside the stream callback | 1 line — only safe once the nginx timeout is also raised, or a runaway request outlives its proxy |
| `result_json` transfer | multi-KB per row | **Yes, badly** | extract in SQL, not PHP | driver branch (below) |
| Response size | uncompressed (no `gzip` in `nginx.conf`) | Yes, on slow links | `gzip on;` + `gzip_types text/csv;` | 2 lines, nginx. CSV compresses roughly 10:1 |
| Flush cadence | PHP's default 4K output buffer | Marginal | explicit `flush()` after each batch | 1 line — keeps nginx's read clock resetting on a slow query |
| Date-filter scan | **no index on `created_at` or `result_generated_at`** | Only for large orgs | composite index migration | a migration — **not in the default build** |

**The nginx default is the one to understand.** `fastcgi_read_timeout` measures the gap *between successive reads* from PHP-FPM, not total duration. Once rows are streaming, every batch resets the clock, so a five-minute stream survives fine. What does not survive is a single gap over 60s — and the largest gap is before the first byte, spanning the preflight `count()` and the first batch query.

**Extract the diagnosis in SQL.** `result_json` is a full result snapshot (per-section aggregates plus diagnosis, order of KBs per row). Selecting the column to pluck one nested string means moving ~100MB and running 50,000 `json_decode`s for a 50K export. Select `JSON_UNQUOTE(JSON_EXTRACT(result_json, '$.diagnosis.calculated'))` so only the short string crosses the wire. ⚠️ Driver-dependent: MySQL in deployment, SQLite locally (`json_extract`, no `JSON_UNQUOTE`). Branch on `DB::getDriverName()` and cover both in tests, or the export silently differs between local and production. **This is the single largest win and is worth making regardless of the ceiling chosen.**

**Practical guidance:**

- **~25,000** — comfortable today with only the SQL extraction, no infra change.
- **~100,000** — needs the nginx timeout, `set_time_limit(0)`, and the explicit flush. All small, all reversible.
- **beyond ~250,000** — the org-scoped date filter starts scanning unindexed date columns; add the composite index, and accept that a single request holding a DB cursor for minutes is queue territory.

**Make the ceiling configurable rather than a constant.** `config('exports.patient_max_rows')` backed by an env var, defaulting to **50,000**, so it can be tuned per environment without a code change — which is what "not a hard limit" needs in practice. Enforce it with a 422 before streaming starts, and validate the real number in verification step 12 rather than assuming it.

> The query is scoped by `patients.user_id` and joins on the indexed `patient_id`, so the unindexed date columns are filtered over one organisation's tests, not the whole table. That is what keeps the missing index off the critical path for typical orgs. Note also that the row floor is the patient count — every patient produces at least one row regardless of the date range, so the range no longer bounds file size the way it would if patients were filtered too.

---

## 2. The output file

`patients_export_20260928_141205.csv` — 15 columns, **every patient**, one row per matching test and one row reading `No tests in range` for patients with none. Sorted by patient name, then sent date. All timestamps UTC. Empty cells blank.

| First Name | Last Name | Email Address | Date of Birth | Patient ID | Zip Code | Gender | Test ID/Plate | Test Name | Eye Tested | Date Test Sent (UTC) | Test Status | Completion Date (UTC) | Calculated Report | Patient IP Address |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| Ava | Thompson | ava.thompson@example.com | 03/14/1986 | WG-10021 | 75201 | Female | 8f3c1d2e-4b7a-49c0-9e11-2a6d5c8f0b31 | Waggoner D-15 Adult | Binocular | 2026-09-02 09:05:17 UTC | Completed | 2026-09-02 09:19:44 UTC | Normal Color Vision | 203.0.113.42 |
| Jonah | Ellis | | 01/22/1991 | WG-10024 | 30309 | Male | | | | | **No tests in range** | | | |
| Marcus | Reed | marcus.reed@example.com | 11/28/1974 | WG-10022 | 60614 | Male | c1a90f44-8d2b-4e63-b7aa-0f5e91c3d872 | Waggoner PDT Adult | Left Eye (OS) | 2026-08-27 14:22:31 UTC | Completed | 2026-08-27 14:41:09 UTC | Moderate Tritan (OS) | 198.51.100.7 |
| Marcus | Reed | marcus.reed@example.com | 11/28/1974 | WG-10022 | 60614 | Male | 7b44e0c9-1f38-4a51-9cd6-3e82b7a41f05 | Waggoner PDT Adult | Right Eye (OD) | 2026-08-27 14:22:31 UTC | Inprogress | | | |
| Priya | Nair | | 06/05/1999 | WG-10023 | 94107 | Female | 2d7f6b18-5c04-4e9a-8b33-61ca7d0e4952 | Waggoner D-15 Pediatric | Binocular | 2026-09-19 16:48:02 UTC | Pending | | | |

```
"First Name","Last Name","Email Address","Date of Birth","Patient ID","Zip Code","Gender","Test ID/Plate","Test Name","Eye Tested","Date Test Sent (UTC)","Test Status","Completion Date (UTC)","Calculated Report","Patient IP Address"
"Ava","Thompson","ava.thompson@example.com","03/14/1986","WG-10021","75201","Female","8f3c1d2e-4b7a-49c0-9e11-2a6d5c8f0b31","Waggoner D-15 Adult","Binocular","2026-09-02 09:05:17 UTC","Completed","2026-09-02 09:19:44 UTC","Normal Color Vision","203.0.113.42"
"Jonah","Ellis","","01/22/1991","WG-10024","30309","Male","","","","","No tests in range","","",""
```

### Column sources

| # | Column | Source | Notes |
|---|---|---|---|
| 1–3 | First / Last Name, Email Address | `patients.first_name`, `.last_name`, `.email` | |
| 4 | Date of Birth | `patients.dob` | **PHI**, `m/d/Y` (a date, not an instant — no UTC suffix) |
| 5 | Patient ID | `patients.patient_id` | org-assigned ref; doubles as Prolific ID |
| 6 | Zip Code | `patients.zipcode` | **PHI** |
| 7 | Gender | `patients.gender` (tinyint) | via existing `Patient::genderLabel()` — do not re-map |
| 8 | Test ID/Plate | `patient_tests.unique_test_id` | the UUID threaded through URLs, results and PDFs |
| 9 | Test Name | `tests.title` | |
| 10 | Eye Tested | `patient_tests.eye_tested` | via existing `TestHelper::getEyeLabel()` → `Binocular` / `Left Eye (OS)` / `Right Eye (OD)` |
| 11 | **Date Test Sent (UTC)** | `patient_tests.created_at` | the secondary sort key, and one of the two columns the range matches on |
| 12 | **Test Status** | `patient_tests.status`, or the literal `No tests in range` | `ucfirst` of `pending / inprogress / completed / abandoned`. When no test is attached, this column carries `No tests in range` and columns 8–11 and 13–15 stay blank — so a patient with no tests at all and one whose tests fall outside the window read the same, and neither reads as a broken row |
| 13 | **Completion Date (UTC)** | `patient_tests.result_generated_at` | the other column the range matches on. Only ever set on completion, so blank means not completed |
| 14 | Calculated Report | `result_json` → `$.diagnosis.calculated` | extracted **in SQL** (§1). Never call `ColorVisionDiagnosisService` from the export — it re-reads every section and answer per test |
| 15 | **Patient IP Address** | `patient_tests.ip_address` | the **test-taker's** IP, captured at completion (`TestExecutionService.php:143`). Blank for pending/inprogress. Not the exporting user's IP; that is on the audit row |

**Other empty cells are blank, not `--`.** A `--` placeholder collides with `AuditLogCsvExport::escape()` (`:139-157`), which prefixes an apostrophe to any value opening with `= + - @ \t \r` — so `--` would be written `'--`. Fixing that is a three-line helper (apply the placeholder *after* escaping, never before), so switching later is cheap if blank columns read poorly. Blank is the smaller change and is what this plan specifies.

---

## 3. The query — all patients, filtered tests

### ⚠️ The date filter goes in the JOIN, not the WHERE

This is the one thing most likely to be got wrong. Putting the date predicate in `WHERE` silently drops every patient with no matching test — turning "all patients" back into "patients with tests in range" and undoing the whole point of the report. It must sit in the `ON` clause, where a non-matching test simply yields NULLs:

```sql
FROM patients
LEFT JOIN patient_tests
       ON patient_tests.patient_id = patients.id
      AND patient_tests.deleted_at IS NULL
      AND ( patient_tests.created_at          BETWEEN :from AND :to      -- sent in window
         OR patient_tests.result_generated_at BETWEEN :from AND :to )    -- completed in window
LEFT JOIN tests ON tests.id = patient_tests.test_id
WHERE patients.user_id = :userId
  AND patients.deleted_at IS NULL
ORDER BY patients.first_name, patients.last_name, patients.id,
         patient_tests.created_at, patient_tests.id
```

Two further traps in that snippet:

- **`patients.deleted_at IS NULL` must be explicit.** `Patient` uses `SoftDeletes`, but its global scope only applies to Eloquent queries. The moment this is built with joins on the query builder, the scope is bypassed and soft-deleted patients reappear in a PHI export. Either start from `Patient::query()` and keep the scope, or write the predicate by hand.
- **`patient_tests.deleted_at IS NULL` likewise.** The table has a `deleted_at` column (`2025_06_23_131210_create_patient_tests_table.php:20`) but `PatientTest` does **not** use the `SoftDeletes` trait, so nothing filters it anywhere today. Filter it here rather than inheriting a latent bug.

### Range semantics

A test is attached to its patient if it was **either sent or completed** inside the window. Consequences worth a line in the PR:

- A test sent in January and completed in March is attached in both a January export and a March export. That is the intent of "either", not duplication. Within one export it still appears once.
- When a test is attached because it *completed* in the window, its **Date Test Sent falls outside the range**. The Completion Date column is what explains its presence.
- A patient whose tests all fall outside the window exports as a single `No tests in range` row.

### Sorting and pagination

**Patient name ascending — `first_name`, then `last_name` — then `patient_tests.created_at` within each patient.** `patients.id` breaks name ties and `patient_tests.id` breaks timestamp ties, so the order is total and the file is reproducible.

**Paginate by patient, not by row.** Keyset on `(first_name, last_name, patients.id)`, then load that batch's matching tests and emit their rows. This keeps a patient's rows contiguous, bounds memory to one batch, and avoids a five-part row cursor.

⚠️ **Do not use `chunkById`.** Read `AuditLogCsvExport.php:29-36`: `chunkById`/`chunkByIdDesc` strip an existing ORDER BY on the *cursor column only*; any other ORDER BY survives, leads, and silently truncates the file — the docblock records this producing 1,999 rows with 1,000 distinct ids and no error anywhere. Do not use `chunk()` either; it paginates with OFFSET, so rows arriving mid-export shift every later page.

⚠️ **Name ordering differs by driver.** MySQL's default collation is case-insensitive (`utf8mb4_..._ci`), SQLite's is binary and case-sensitive, so `alice` sorts after `Zoe` locally but before it in production. Assert ordering in tests with names that do not depend on case, and note the difference in the PR.

### UTC

*(Superseded for the range by v8 note 2: `from`/`to` are calendar days in the viewer's `timezone`, converted to UTC instants. The paragraph below still describes the timestamp columns.)* `config/app.php:68` sets `'timezone' => 'UTC'`, so timestamps are stored and compared in UTC. Render them exactly as `AuditLogCsvExport::colCreatedAt()` already does — `Y-m-d H:i:s` plus a literal ` UTC`, chosen because Excel parses that format as a date and shows ISO-8601 as a string — and put `(UTC)` in the two timestamp headers. The range inputs were originally interpreted as UTC days; that dropped a test taken at 9 pm US Eastern from that day's export. They are now the viewer's local days (v8 note 2), which the file's banner row states.

---

## 4. ⚠️ The range cap is one calendar year, not 366 days

Worth settling before either side is written, because the two are not the same rule and the stated requirement needs the calendar one.

The requirement's own example is *"if a start date is 23 March 2023, end date should be till 23 March 2024"*. Counted the way the Audit Trail counts (`round(diff / 86400) + 1`, `ValidatesAuditDateRange.php:57-59`), that window is **367 inclusive days**, because 2024 is a leap year and 29 February falls inside it. A `MAX_DATE_RANGE_DAYS = 366` cap would reject the exact example it was written to allow, and would behave differently depending on whether the window happens to span a 29 February.

**So express the cap as a calendar rule: `to` may not exceed `from` plus one year.** `moment(from).add(1, 'year')` on the frontend, `Carbon::parse($from)->addYear()` on the backend. Same-date-next-year is always allowed, leap years handle themselves, and the rule is stateable in the UI in four words.

- **Label it "Maximum range: 1 year"**, not a day count — a day count is both wrong and harder to reason about at the calendar edge.
- **Note the deliberate divergence** from `ValidatesAuditDateRange`'s day-count arithmetic in both files. The audit trait's 31-day cap is correct for the audit trail and must not be changed; this is a different rule, not an inconsistency to be tidied away later.

Start and end date remain **required** on every export — matching `AuditLogExportRequest` (`:56-57`), matching the old system's modal, and bounding how many test rows one file can carry.

---

## Backend — `D:\projects\waggoner-tcv\TCV-Backend`

### 5. Route — `routes/api.php`

```php
Route::get('/patients/export', [PatientController::class, 'export'])   // NOTE: superseded on improve/export-format by POST; see the review follow-up
    ->middleware('throttle:patient-export');
```

Place it in the **`auth:sanctum` group** (`routes/api.php:134`), not the `FlexibleAuthMiddleware` group. Sanctum guarantees a real `$request->user()`, so the audit row can never be written as a headless `system` actor, and it keeps this PHI dump off the patient-session tiers entirely.

⚠️ **Routing trap:** `Route::apiResource('patients', …)` is registered earlier at `routes/api.php:89`, and its `GET patients/{patient}` matches `patients/export` first — the request would land in `show('export')` and 404. Constrain the resource: `->whereNumber('patient')`. Codebase precedent: `AuditLogController@show` is registered `whereNumber` for exactly this reason (`routes/api.php:141`). It only excludes non-numeric ids, which already 404 today.

Register a `patient-export` limiter in `AppServiceProvider::configureRateLimiting()` beside the existing ones (`plate-url` :124, `send-resume-email` :133): `Limit::perMinute(10)->by($request->user()?->id ?: $request->ip())`, reusing the shared `$tooManyRequests` callback.

### 6. `app/Http/Requests/PatientExportRequest.php` (new)

Uses a new `ValidatesPatientExportDateRange` trait in `app/Http/Requests/Concerns/`, modelled on `ValidatesAuditDateRange.php` — same `withValidator` shape, same "skip if `from`/`to` already failed `date_format`" guard — but comparing `to` against `Carbon::parse($from)->addYear()` per §4 rather than counting days. **Do not edit the audit trait.**

**Allowlisted enum for `filter`, not free text**, so nothing arbitrary reaches the audit `details`:

| field | rule |
|---|---|
| `from` | `required`, `date_format:Y-m-d`, `before_or_equal:today` |
| `to` | `required`, `date_format:Y-m-d`, `after_or_equal:from`, `before_or_equal:today` |
| `filter` | `nullable`, `in:today,yesterday,lastWeek,frequent` |
| `search` | `nullable`, `string`, `max:255` |

`before_or_equal:today` is the server-side half of "no future dates" (§11 requirement 5). `authorize()` returns `true` — Sanctum gated the route, ownership scoping lives in the query. Model on `AuditLogExportRequest.php`.

### 7. `app/Services/Reports/PatientExportService.php` (new)

Beside `UserTestsReportService`; keeps the controller thin per the repo's Route → Controller → Service → Model layering. Builds the query in §3.

**Scoping (security-critical):** `patients.user_id = $user->id`, plus `Patient::applyRegisteredTabScope()` (v8 note 1) — the same clause `index()` uses. A user exports only their own patients. Do not widen it, and do not reuse `callerOwnsPatient()` (that is the per-record check for the FlexibleAuth tiers, wrong shape here).

**Filters**, replicating `RegisteredPatientsTab.js:60-97` including that search *wins over* the quick filter (`if (searchText) … else if (activeFilter)`, `:69`/`:74`). All of these narrow **patients**, unlike `from`/`to` which narrow attached tests:
- `search` → `LIKE` across `first_name`, `last_name`, `patient_id`, gender label (the client's `searchFields`, `:61`)
- `today` / `yesterday` / `lastWeek` → on `patients.updated_at`, matching the client (`:76`)
- `frequent` → **no-op, returns everything.** The button exists (`:175`) with no matching filter branch. Preserve the behaviour; do not invent a definition. Note it in the PR.

Do **not** route tests through `PatientTestTransformer` — it groups monocular OS/OD pairs for the UI, and here each eye is its own row.

### 8. `app/Exports/PatientCsvExport.php` (new)

Model on **`AuditLogCsvExport.php`** — not the two XLSX exports, which hold the whole workbook in memory. Carry over verbatim: the `COLUMNS` map driving both header and rows so they cannot drift; the UTF-8 BOM; `fputcsv` to `php://output` inside a `StreamedResponse`; `escape()`. Replace `chunkByIdDesc` with the patient-batch keyset loop from §3, add an explicit `flush()` after each batch (§1), and emit the `No tests in range` row when a patient has no attached test.

`escape()` matters more here than in the audit trail: patient names are free text supplied by the exporting organisation, and a patient named `=cmd|...` is a formula-injection payload in a file someone opens in Excel.

### 9. `PatientController::export()`

`PatientController` already injects `AuditService` (`:43,45`) and emits `patient.added` (`:122-133`) — mirror that call. **Order matters:**

```php
$query    = $this->patientExportService->query($request->user(), $request->validated());
$rowCount = (clone $query)->count();

if ($rowCount > config('exports.patient_max_rows')) {
    return ApiResponse::error(HttpStatus::UNPROCESSABLE, 'api.patient_export_too_large');
}

$this->auditService->log(
    'patient.exported',
    'Patient list exported.',
    $request->user(),
    null,
    'success',
    [
        ['label' => 'Export Format',     'value' => 'CSV'],
        ['label' => 'Number of Records', 'value' => $rowCount],
        ['label' => 'Date Range',        'value' => $from.' to '.$to.' ('.$timezone.')'],
        ['label' => 'Applied Filter',    'value' => self::FILTER_LABELS[$filter ?? 'none']],
    ],
    [],
    $request
);

return (new PatientCsvExport($query))->stream('patients_export_'.date('Ymd_His').'.csv');
```

The count must be taken over the **joined** query so it equals the file's data-row count — which includes the `No tests in range` rows — not the patient count.

**Log before streaming** — a client that aborts mid-download has still been served PHI, and once `streamDownload()` emits, the headers are on the wire and there is no way back to an error response (`AuditLogController.php:95-99`). The count is server-computed and authoritative.

**Never log the search term.** It matches `first_name`/`last_name`/`patient_id`, so it is very often a patient's actual name; `AuditService`'s `DENYLIST` is key-based and its content masking does not catch bare names. Record `'Search applied'`, never the value — `ReportController.php:148-152` makes this exact call in a comment; cross-reference it. The date range is safe to log verbatim and belongs there: it is the scope of the disclosure. `ReportController::appliedDateFilter()` (`:176-183`) is the existing helper for rendering it.

Use `AuditService::log()`, never `AuditLog::create()` — it is the documented single write chokepoint applying masking, impersonation detection, session-key derivation and GeoIP. Category `patient_records` means its `PHI_DENYLIST` masking applies automatically (`AuditService.php:120`) — a second net, not the first. It swallows all `\Throwable` and returns `null` (`:183-190`), so the export proceeds even if the audit write fails; that lands in `Log::error`.

Add a `config/exports.php` with `patient_max_rows` reading an env var (default 50000), and add `'patient_export_too_large'` plus `'patient_export_date_range_too_wide'` to `resources/lang/en/api.php` (near `records_fetch_success`, `:58`).

### 10. Flag, do not bundle

`patient.exported` is catalogued `medium` sensitivity (`AuditEventCatalog.php:403`). A bulk PHI extract is arguably `high`. One word, no DB impact, but it touches an entry `AuditLogSeeder.php:136` and the audit unit tests reference. Raise it as a decision.

---

## Frontend — `D:\projects\waggoner-tcv\TCV-Frontend`

### 11. A two-field From / To picker in the export modal

**The Audit Trail's picker is no longer involved.** Nothing under `src/pages/AuditTrail/` or `src/utils/auditFilters.js` is touched — no extraction, no shared component, no regression risk on a shipped feature. That was the main cost of the previous approach and it is now gone.

Revive `src/pages/UserPannel/PatientPage/ExportPatientModal.js` instead. It is dead code (unimported on this branch) that already has the bootstrap `Modal`, the heading, **two `<input type="date">` fields with floating labels**, and a **Cancel / Export** button pair. The changes:

- **Delete the password field and its fake check.** `:19-25` runs a `setTimeout` that pretends to verify a password and always succeeds — it authenticates nothing. Drop the `password-verification-modal` class from the modal's className list along with it. Do not substitute a real password gate; that is a separate product decision and the session is already authenticated.
- **Use native `<input type="date">`**, as it already does. `min`/`max` express every one of the five constraints declaratively, the browser's own calendar greys out what is out of bounds, and mobile gets the platform picker for free — which is most of requirement 4. `react-datepicker` is available (`package.json:26`) but would need more code for the same result.

**The `min`/`max` matrix** — this is the whole of requirements 1, 2, 3 and 5:

| Field | `min` | `max` |
|---|---|---|
| **From** | `to` minus 1 year, when `to` is set | `to` when set, else today — whichever is earlier |
| **To** | `from` when set | `from` plus 1 year when set, else today — whichever is **earlier** |

The `max` on both fields is capped at today, which is requirement 5. The one-year arms are the §4 calendar rule, not a day count. Taking the *earlier* of the two candidates on the To field is what stops a one-year window running into the future.

**Two behaviours the matrix alone does not cover:**

- **If From moves past an already-chosen To, clear To** (and vice versa). Native inputs keep a stale value that now violates their own `min`/`max`; the browser will not clear it. Without this the modal can hold an invalid range that only fails at the API.
- **Re-check the range on submit.** A user can type directly into a native date input, bypassing the calendar's bounds. The backend trait (§6) is the final backstop, but failing in the modal is a better experience than a 422.

**Requirement 3's text:** render **"Maximum range: 1 year"** as helper text under the two fields — the Audit Trail states its own limit for exactly this reason (`AuditDateRange.js:220-224`: a calendar cell that simply stops responding reads as a bug, not as a rule).

**Requirement 4, responsive:** the two fields sit in a `.form-row`, but ⚠️ **`.form-row` and `.date-group` have no styles anywhere in the codebase** — the dead modal's markup is a mockup, not a styled component. The pattern to copy is `src/pages/UserPannel/AddPatient/AddPatient.scss:33-37`, the closest sibling form:

```scss
.form-row {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 45px;
}
```

`auto-fill` + `minmax` stacks the fields on narrow screens with **no media query needed**. A global `.form-group` already exists at `src/styles/components/FormInput.scss:1`.

> Floating-label detail: `AddPatient.scss` drives its label with `:not(:placeholder-shown)`, which does **not** work on `type="date"` — a date input is never "placeholder shown". That is why the dead modal sets a `filled` class from JS (`:44`, `:56`). Keep the JS approach for these two fields and leave a comment saying why, or the labels will sit on top of the dates.

**`src/constants/patientExport.js` (new)** — `MAX_RANGE_LABEL = 'Maximum range: 1 year'` and a `MAX_RANGE_YEARS = 1`, with a comment that the rule must match `ValidatesPatientExportDateRange` on the backend (the note `auditTrail.js:103` already carries for its own cap).

**Make the range's purpose unmissable** in the modal body — not a tooltip. A date picker on an export dialog naturally reads as "export patients from this period", which is the opposite of what it does:

> *This export includes every patient on your Registered Patients tab[, matching the search "…" | updated today | …], mapped to their tests. The date range narrows the tests shown for each patient — tests that were sent or completed within the range.*

Retitle the heading to something like **"Filter tests by date"** rather than the existing "Please select start and end date for patient export list", which implies the patients are what is being filtered.

**Export is disabled until both dates are set and the range is valid.** Cancel closes without exporting.

### 12. `src/apis/exportPatients.js` (new)

Mirror `src/apis/exportAuditLogs.js` — it already solves blob download *and* the "error arrived as a blob" unwrapping problem (`:14-30`) that every `responseType: 'blob'` call hits. Reuse the default `axiosInstance`; do not add a second instance.

```js
const PATIENTS_EXPORT_ENDPOINT = "/api/patients/export";
// GET, responseType: 'blob', skipErrorPopup: true, params: { from, to, filter, search }
```

### 13. `RegisteredPatientsTab.js:106-109` and `PatientPage.js:67-75`

The Export button now opens the modal instead of exporting directly. On submit:

```js
await exportPatients({ from, to, filter: searchText ? null : activeFilter, search: searchText || null });
```

Add `searchText` and `activeFilter` to the effect's dependency array. Make the handler `async` inside `try/catch`, disable the Export button while in flight (guards double-click, which would otherwise mean duplicate audit rows and duplicate PHI downloads), close the modal on success, and surface the standard error toast on failure — leaving the modal open so the chosen dates are not lost. Keep `onExportableCountChange?.(filteredPatients.length)` — it drives on-screen UI, not the file; the on-screen count is *patients* while the file has one row per matching test, so they will differ, which is correct.

### 14. Retire the client-side builder

Delete `src/components/ExportPatients.js` and its import at `RegisteredPatientsTab.js:9`. Leaving it invites a future caller to bypass the audited path — the whole point of this change.

---

## Tests

**Backend** — `tests/Feature/PatientExportTest.php` (new), following `tests/Feature/AuditLogExportTest.php`:

- **Cross-tenant isolation** (the one that matters most): user A's export contains none of user B's patients. Assert on the streamed body.
- **Every patient appears**, the headline regression guard for §3: a patient with no tests at all, and a patient whose only test falls *outside* the window, both export as one row whose Test Status reads `No tests in range` with columns 8–11 and 13–15 blank. A `WHERE`-clause implementation fails exactly here.
- **The OR predicate:** a test sent in-window but not completed → attached. Sent before the window but completed in-window → attached. Sent and completed outside → not attached, and its patient still appears. A test attached **once**, not twice, when both dates fall in the window.
- Boundary: tests at exactly `from 00:00:00Z` and `to 23:59:59Z` are attached.
- **Soft deletes:** a soft-deleted patient is absent from the file. (Guards the bypassed global scope in §3.)
- **The §4 calendar-year rule, explicitly:** `2023-03-23` → `2024-03-23` is **accepted** (the requirement's own example, and the case a 366-day cap would wrongly reject); `2023-03-23` → `2024-03-24` is rejected. Same-day `from`/`to` is a one-day window, not zero. A future `to` → 422. Missing `from` or `to` → 422.
- **Sorting:** patients ascending by first name then last name, a patient's tests ascending by sent date beneath them, rows contiguous. Assert across a set larger than the batch size — the regression guard for the keyset trap in §3, checking the row *count* plus the first and last row.
- Row grain: a patient with 2 in-window tests → 2 rows; an OS/OD pair → 2 rows with distinct `unique_test_id` and eye labels.
- Column mapping: a completed test renders `Completed`, both UTC timestamps with the ` UTC` suffix, the `result_json` calculated value and an IP; a pending test leaves completion date, calculated report and IP blank.
- A patient named `=cmd|'/c calc'!A1` is apostrophe-prefixed in the file.
- Audit: exactly one row per export, `category=patient_records`, `event_title='Patients exported'`, `actor_id` = caller, `details` carrying all five labels with `Number of Records` **equal to the actual data-row count** (not the patient count), and the date range present.
- **`search=Jane Doe` → the term appears nowhere** in the audit row, including `event_description`. This is the PHI barrier; assert it explicitly.
- Over the configured ceiling → 422, **and no audit row and no bytes streamed**.
- Unauthenticated → 401. `GET /api/patients/5` still resolves (the `whereNumber` change did not break the resource).
- Both DB drivers exercise the JSON extraction (§1), or the SQLite path is tested and the MySQL path reviewed by hand — say which in the PR.

**Frontend** — `npm test` on `ExportPatientModal`:
- Picking From sets the To field's `min` to it, and the To field's `max` to From + 1 year.
- Picking To sets the From field's `max` to it.
- Neither field's `max` ever exceeds today.
- Moving From past an already-chosen To clears To.
- Export is disabled until both dates are set; enabled once they are.
- A range typed past one year is rejected on submit without calling the API.
- The export call receives `{ from, to, filter: null, search: "smith" }` when a search is active.

---

## Verification (end to end)

1. `php artisan test --filter=PatientExportTest`, then `vendor/bin/pint`.
2. Run both apps (`composer dev`; `npm start`). Log in as a user-panel user with patients → Patients → Registered → Export.
3. The modal opens with **two date fields**, the "Maximum range: 1 year" helper text, the "all patients are included" explanation, and Cancel / Export. Export is disabled until both dates are set. Cancel closes without downloading.
4. **Work through the constraints in the browser's own calendar:** pick From, then confirm the To calendar greys out everything before it and everything past From + 1 year. Pick To first instead, then confirm the From calendar greys out everything after it. Confirm **future dates are unselectable in both**. Then set From past an existing To and confirm To clears rather than going stale.
5. Confirm `2023-03-23` → `2024-03-23` is accepted — the §4 example a day-count cap would reject — and that `2024-03-24` is not.
6. **Responsive:** narrow the window to phone width and confirm the two fields stack rather than overflowing, and that the modal has no horizontal scroll.
7. Export a wide window → file downloads as `patients_export_<timestamp>.csv`. In Excel: 15 headers in the order above, `(UTC)` on the two timestamp columns, accented names intact, patients **alphabetical by first name**, each patient's tests beneath them **ascending by Date Test Sent**, rows contiguous.
8. **The headline check:** confirm the file's distinct patient count equals the patient count on screen — including patients with no tests and patients whose tests all fall outside the window, which should show `No tests in range`. Then narrow to a single day and confirm the patient count is *unchanged* while test rows drop.
9. Confirm a pending test leaves Completion Date, Calculated Report and Patient IP Address blank, and that no cell shows a stray leading apostrophe. Pick a window containing a test *completed* in it but *sent* before it → the row appears with a Date Test Sent outside the window, as §3 predicts.
10. Super Admin → `/audit-trail`, category *Patient Records*. Confirm a **Patients exported** row: correct actor, IP, timestamp; detail drawer shows Export Format `CSV`, Number of Records **matching the file's data-row count**, the date range, Applied Filter, Tab.
11. **The critical negative check:** export with a real patient name in the search box, then inspect that audit row's `details` *and* `event_description` — the name must appear in neither.
12. Cross-tenant: export as a different organisation's user; confirm zero overlap with the first file.
13. **Volume — this is the step that sets the real ceiling.** Seed past the configured limit and confirm a clean 422 with no partial file. Then seed just under it and time the request against the 60s/300s limits in §1, with and without the nginx changes. Record the numbers in the PR so the config value is chosen from a measurement rather than a guess.
14. Double-click Export → exactly one audit row, one download.

## Explicitly out of scope

No schema change in the default build (the §1 index is optional and separate). No change to `GET /api/patients` or the patient Redux slice. **Nothing under `src/pages/AuditTrail/` or `src/utils/auditFilters.js` is touched.** No user-agent capture — the "User Device" column is replaced by Patient IP Address, which has real data. No queue-backed export. No password gate on the export modal.
