# Context: Reports, Exports & the Admin Dashboard

> Load this **instead of** reading the reporting subsystem. ~850 tokens.
> Depth note: this pack is **shallower than the others** — the report SQL and the Excel column mappings
> were read, the aggregation edge cases were not. Marked `[not deeply traced]` where that applies.

## Files
| File | Role |
|---|---|
| `app/Http/Controllers/ReportController.php` (433 lines) | 6 endpoints, thin over the services |
| `app/Services/Reports/UserTestsReportService.php` | Per-user test activity |
| `app/Services/Reports/DiscountCodeReportService.php` | Discount redemption |
| `app/Exports/UserTestsReportExport.php` · `UserTestsDetailExport.php` · `DiscountCodeReportExport.php` | `maatwebsite/excel` |
| `app/Http/Controllers/SuperAdminDashboardController.php` (163 lines) | `GET api/super-admin/dashboard` |
| `app/Http/Requests/GenerateTestReportRequest.php` | Report parameter validation |

## Routes (all `auth:sanctum`)
```
GET api/reports/list-patients-having-tests   → ReportController::getPatientsHavingTests
GET api/reports/user-tests                   → ReportController::userTestsReport
GET api/reports/discount-codes               → ReportController::discountCode
GET api/super-admin/dashboard                → SuperAdminDashboardController::index
```

☠️ **No policy anywhere; role/owner checks are per endpoint (as of `9082ae22`).** `list-patients-having-tests`
and `user-tests` are owner-scoped for non-admins (`ReportController::reportOwnerId()`, `:44-49`, since
2026-09-18). **`discount-codes` and `super-admin/dashboard` still have no role check** — any authenticated user
can call them. The three `user-tests/table*` / `test-takers` routes are Super Admin only (403). The SPA
hides the menu items via `RouteConfig` ([FRONTEND.md](../FRONTEND.md)) — that is a UI affordance, not
authorisation.

## The PDF path
The patient-facing test result PDF is separate from these reports:
```
GET  api/test-result/{unique_test_id}/download-pdf   → TestController::downloadTestResultPDF
POST api/tests/result-pdf                            → TestController::generateTestResultPDF
```
Both are behind `FlexibleAuthMiddleware` and render via `barryvdh/laravel-dompdf`. They read the stored
`patient_tests.result_json` snapshot, so a PDF reproduces the result **as computed at completion time**
([TEST_EXECUTION_CONTEXT](TEST_EXECUTION_CONTEXT.md)) — regenerating a PDF never re-runs the diagnosis.

---

## ☠️ Traps

1. **Reports are not tenant-scoped by the framework.** Whatever scoping exists is hand-written inside
   each service's query. Adding a report means writing the scoping yourself; there is no base class or
   global scope to inherit it from.
2. **Soft-deleted patients distort joins.** A report joining through `patients` silently omits tests
   belonging to soft-deleted patients ([PATIENT_CONTEXT](PATIENT_CONTEXT.md) trap 3).
3. **Excel exports stream the same query a second time.** The `*Export` classes re-run the report rather
   than receiving the already-fetched collection, so a filter change must be applied in **both** the
   service and the export or the screen and the download disagree. `DiscountCodeReportService` makes it
   three: the range is pasted into `getSummary()` *and* `buildQuery()`, so a filter that misses one
   leaves the summary tiles disagreeing with the table beneath them.
4. **`getAvailableCredits()` can return the string `'Unlimited'`** — any report column that sums or
   averages credits must handle it ([CREDITS_CONTEXT](CREDITS_CONTEXT.md)).
5. **Discount usage counts come from `transaction_details`**, so payments that never reached
   `POST api/payment/confirm` are invisible to the discount report
   ([BILLING_CONTEXT](BILLING_CONTEXT.md)).
6. **The date range is enforced in the SPA only** (`ws-455`, 2026-09-10). All three report endpoints
   read `from_date`/`to_date` off a bare `Illuminate\Http\Request` — no FormRequest covers them
   (`GenerateTestReportRequest` belongs to the PDF endpoints, not these) and nothing checks the shape
   or the ordering of the two values. Each bound is applied independently as
   `where('…created_at', '>=', $from . ' 00:00:00')` / `'<=', $to . ' 23:59:59'`, so an inverted range
   is a satisfiable query that matches nothing: **200 with zero rows and no error**, which reads as a
   data bug rather than a bad request. The three report screens now block it before dispatching
   ([FRONTEND.md](../FRONTEND.md)) — that is a UI affordance, not validation, and the same distinction
   as the `usertype` note above. A saved link, an export re-run or any non-SPA caller still gets there.
7. **The patient drill-down pages an array, not a query, so it has to be sorted in PHP**
   (`ws-502`, on `develop` 2026-09-17). `GET api/reports/user-tests?patient_id=` runs `buildTransformedTests()`: it
   loads every `patient_tests` row, groups monocular OS+OD pairs through `PatientTestTransformer`,
   filters, and `getPatientTestsForReport()` then `array_slice`s the page out. **On `develop` the
   `sort_by`/`sort_order` the SPA sends are ignored on this path**. Every header click returns
   newest-first, so the arrow flips and nothing moves. On `ws-502`, `sortTransformedTests()` sorts the
   grouped rows *before* the slice by `unique_test_id`, `test_name`, `created_at` or `status`
   (`PATIENT_TEST_SORTS`), with an `id` tiebreak. `test_name` uses `strnatcasecmp`, so "Test 2" comes
   before "Test 10". ☠️ `unique_test_id` and `status` use plain `strcasecmp`: the ids are UUIDs, and
   natural order reads their leading digits as numbers, which would put `2f…` and `9c…` before `12ab…`.
   Anything else keeps
   newest-first. The export (`request_type` 2/3) goes through the same method, so the download order
   matches the screen. Guard any new sort key on this path in PHP; adding an `orderBy` to the Eloquent
   load does nothing once the transformer regroups.
8. **Redemptions sort keys are the service's `$sortMap` keys, not the row's field names** (`ws-502`,
   on `develop` 2026-09-17). The row carries `discount_code`, but the key is `code`. Until `ws-502` the SPA column sent
   `discount_code`, which `normaliseSort()` silently swapped for `used_on`. `username` now orders by
   first then last name, and every sort ends on `td.id` (one code is redeemed many times, amounts
   repeat). `company` puts a blank company last ascending. NULL and `''` both render "—", and without
   the explicit `(u.company_name IS NULL OR u.company_name = '')` key they filled the first pages. The
   *Discount* column is not sortable: it has no key in the map.
9. **`UserTestsReportService::query()` passes `sort_by` straight to `orderBy()` with no allow-list.**
   Observed during `ws-502`, **not changed**. It only feeds the non-patient report and its exports.
   Today that is unreachable from the SPA: since PR #445 `UserTests.js` calls `user-tests/table`, and the
   non-patient `user-tests` path has no SPA caller (only `UserTestDetail.js`, patient path). The grammar quotes the identifier, but
   an unknown column is still a 500. Add an allow-list before wiring sorting to that screen.

_[not deeply traced]: `SuperAdminDashboardController::index()`'s aggregation queries, the exact column
sets of the three Export classes, and the dompdf Blade templates._

---

## Export/PDF wording (2026-10-12, merged: backend PR #315)

Headers renamed ("Unique Identifier", "Test Taker IP Address", "Test Takers Export"); in-flight tests print
`PatientTest::statusLabel()` (`PatientTest.php:159`) = "In Progress" (others `ucwords`). Callers:
`PatientXlsxExport.php:638`, `UserTestsReportExport.php:91`, `UserTestsDetailExport.php:52,58-59`,
`UserTestsReportService.php:243` (`status_label`). ⚠️ **Except the older list PDF:** `downloadPdf()` runs
`ucfirst()` on `status` first (`ReportController.php:326-328`), so `statusLabel('Inprogress')` misses and that PDF
prints "Inprogress" (no SPA caller now). ⚠️ History/detail exports and both report PDFs still head the column
"Test ID", not "Unique Identifier" (`UserTestsDetailExport.php:37`, `user-tests-detail-pdf.blade.php:64`,
`user-tests-pdf.blade.php:22`); the older xlsx still says "User Name" (`UserTestsReportExport.php:55-61`).
`test-result.blade.php:178/184` prints "Test Taker Name:" / "Unique Identifier:" (Prolific still "Prolific ID").
Audit labels "Expiry Date"/"Invitation Expiry" intentionally **unchanged**: the `AuditService` redaction allowlist
matches the exact string. Existing audit rows keep old text.

---

## Tabular User Tests report (2026-10-12, merged: backend PR #315, frontend PR #445)

Replaces the card grid on the SPA screen; tests: `tests/Feature/Reports/UserTestsTableReportTest.php` (403 on all
three endpoints, 422 on an inverted range, 404 for a missing patient). Indexed 2026-10-15 as `API-083`…`API-085`.

### Routes (GET, `auth:sanctum`, **Super Admin only**)
```
api/reports/user-tests/table                              → ReportController::userTestsTable          (API-083)
api/reports/user-tests/table/export                       → ReportController::exportUserTestsTable    (API-084)
api/reports/user-tests/test-takers/{patient}/export       → ReportController::exportTestTakerHistory  (API-085)
```
Literal segments sit before `{patient}`; route order is fine. `{patient}` is `->whereNumber()` (`api.php:317-318`),
so a non-numeric id is a route-level 404.

**Authorization (verified):** `table` / `table/export` use `UserTestsTableRequest::authorize()` = `isSuperAdmin()`
(`UserTestsTableRequest.php:19-23`; `failedAuthorization()` → 403 `api.forbidden`, before validation, so a
non-admin never sees a 422). `test-takers/{patient}/export` checks `isSuperAdmin()` inline **before** the
`Patient::withTrashed()->find()` 404 (`ReportController.php:233-240`), so a non-admin cannot probe ids. Super Admin
sees every tenant by design — `tableQuery()` has no owner scoping (docblock, `UserTestsReportService.php:150`).

### Contract
- `table` params: `page`, `limit` (1-100, default 10), `sort_by`, `sort_order`, `search`, `status`
  (`pending|inprogress|completed|abandoned`), `test_id`, `user_id`, `organization_id`, `patient_id`, `from_date`,
  `to_date` (Y-m-d). Returns `data`, `pagination{total,current_page,last_page,per_page}`, `columns` (key=>label),
  `required_columns` (`ReportController.php:181-204`).
- `table/export`: same filters + `columns[]` or `all=1`. **xlsx only** (no PDF); `page`/`limit` accepted but ignored.
  `all=1` **or an empty/absent `columns[]`** = every column; otherwise required columns are added.
- `test-takers/{patient}/export`: full history, **ignores all filters**; the Test Taker may be soft-deleted
  (`Patient::withTrashed()`), but `PatientTest` has no SoftDeletes. xlsx default; any `format` other than `pdf` →
  xlsx. Unknown patient → 404 `api.patient_not_found`. Columns: Test ID, Test Name, Date, Status; OS+OD pairs grouped
  into one row (`UserTestsDetailExport.php:35-38`).
- `UserTestsTableRequest`: `sort_by`/`columns` allow-listed; `to_date < from_date` → 422.

### Service (`UserTestsReportService`)
- `TABLE_COLUMNS` (10): Unique Identifier, Test Name, Test Taker, Account, Integrated Partner, Status, Eye Tested, Test Taken Via, Test Taker IP Address, Date. `REQUIRED_TABLE_COLUMNS`: Unique Identifier, Test Name, Status, Date (`unique_test_id`, `test_name`, `status`, `created_at`).
- `TABLE_SORTS` = sort allow-list. **Test Taker and Test Taken Via (`invite_type`) are not sortable** (names are ciphertext; `invite_type` is derived); `account_name` sorts by `u.first_name` only (`:136-145`). Name search is done in PHP via `PatientNameSearch`.
- `tableQuery()` is the **single filter implementation** shared by screen and export - change filters there only. Filter semantics (`:179-215`): `user_id` = `p.user_id` (owning account); `organization_id` matches through `organizations.user_id = p.user_id`, not a patient FK; `organization_name` is a correlated subquery taking the lowest `o.id` per account (`organizations.user_id` is not unique). Search covers `unique_test_id`, test title, account first/last name, org name and decrypted Test Taker names — not status.
- ☠️ `tableQuery()` is `DB::table()` joining `patients` with **no `deleted_at` filter**: tests of soft-deleted Test Takers are included in the table and its export (`:152-157`; the older `query()` does the same, `:55-57`). Trap 2 does not hold here.
- `tableRow()` decrypts names; `invite_type`: `is_email_invite` null (migrated) → `''` (SPA "—"), true → "Email Invitation", false → "In-Office"; extra `status_label` drives the SPA Status cell (`:229-250`).
- `UserTestsReportExport` takes optional `?array $columns`; `null` = legacy 5-column export (old callers unchanged). Its `map()` resolves the service from the container and calls `tableRow()` per row (`UserTestsReportExport.php:70-78`); `created_at` is m/d/Y, `status` = `status_label`.
- ⚠️ Audit reuses `report.user_tests_exported`, but the `$scope` (`table|patient_history`) and `$patientId` args to `logUserTestsExported()` are **never written** (`ReportController.php:256-262`). Details carry only Export Format, Applied Date Filter ("None" for history) and Number of Records, so table, history and older exports are indistinguishable in the audit log.

### Traps
- Trap 3 (authorisation) as of `9082ae22`: the three new routes are Super Admin only; legacy `user-tests` and `list-patients-having-tests` are callable by any authenticated user but **owner-scoped** for non-admins via `reportOwnerId()` (`ReportController.php:44-49`; foreign `patient_id` → 404, `:100-105`). ☠️ `discount-codes` (`:343`) and `super-admin/dashboard` still have **no** role or owner check.
- Trap 9 (unguarded `sort_by` in `query()`) **still true for the legacy path**, but since PR #445 `UserTests.js` calls `user-tests/table`; only `UserTestDetail.js` (patient path) still uses `user-tests`. The table path is allow-listed via `TABLE_SORTS`.

### Screen
`src/pages/Reports/UserTests.js` - see [FRONTEND.md](../FRONTEND.md#tabular-user-tests-report-2026-10-12-merged-frontend-pr-445).

## Export sanitising (2026-10-13, merged: backend PR #315)

- `App\Support\SpreadsheetSafe` (`cell()` / `row()`): prefixes an apostrophe to strings starting with tab or CR (always), or with `=`, `+`, `-`, `@` when the string is not `is_numeric` (so `-5`, `+3`, `-1.5` are left alone). Non-strings and `''` untouched (`SpreadsheetSafe.php:15-31`). Applied in `UserTestsReportExport` (legacy and table branches) and `UserTestsDetailExport`. Test: `tests/Unit/Support/SpreadsheetSafeTest.php`.
- `PatientXlsxExport` is deliberately **not sanitised** (its headers and status labels did change): it writes `setCellValueExplicit(TYPE_STRING)`, which is never evaluated as a formula. In the Maatwebsite exports the apostrophe is stored as literal text (no `config/excel.php`, no custom value binder) — expect it to show in Excel `[not tested in Excel]`.
- ☠️ **Not yet the single implementation:** `DiscountCodeReportExport` writes user-controlled strings (description, username, email, company) with `setCellValue` and **no** sanitising (`DiscountCodeReportExport.php:132-148`); `AuditLogCsvExport` has its own copy of the logic (`:137-148`).
- **Known limitation:** report name search decrypts names in PHP (`PatientNameSearch`) and the export has **no row cap**; large datasets cost time and memory.
- SPA: only `exportUserTestsTable` / `exportTestTakerHistory` parse a Blob error body into `{message}` ([FRONTEND.md](../FRONTEND.md)); Discount Code and `userTestsReportSlice` exports are unchanged.
