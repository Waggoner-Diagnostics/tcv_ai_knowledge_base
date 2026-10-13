# Context: Reports, Exports & the Admin Dashboard

> Load this **instead of** reading the reporting subsystem. ~850 tokens.
> Depth note: this pack is **shallower than the others** — the report SQL and the Excel column mappings
> were read, the aggregation edge cases were not. Marked `[not deeply traced]` where that applies.

## Files
| File | Role |
|---|---|
| `app/Http/Controllers/ReportController.php` (245 lines) | 3 endpoints, thin over the services |
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

☠️ **None of these carry a policy check or a `usertype` test.** `auth:sanctum` is the only gate, so any
authenticated user of any role can call `super-admin/dashboard` and the cross-tenant reports. The SPA
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
   Today that is unreachable from the SPA with a custom key, because `UserTestsAccordion` renders with
   `disableHeaderSorting` and `UserTests.js` sends no `sort_by`. The grammar quotes the identifier, but
   an unknown column is still a 500. Add an allow-list before wiring sorting to that screen.

_[not deeply traced]: `SuperAdminDashboardController::index()`'s aggregation queries, the exact column
sets of the three Export classes, and the dompdf Blade templates._

---

## Export/PDF wording (2026-10-12, uncommitted)

Headers renamed ("Unique Identifier", "Test Taker IP Address", "Test Takers Export"); in-flight tests print
`PatientTest::statusLabel()` = "In Progress". Audit labels "Expiry Date"/"Invitation Expiry" intentionally **unchanged**:
the `AuditService` redaction allowlist matches the exact string. Existing audit rows keep old text.

---

## Tabular User Tests report (2026-10-12, uncommitted)

Replaces the card grid on the SPA screen; tests: `tests/Feature/Reports/UserTestsTableReportTest.php`. Indexes **not regenerated** (QA/UAT branches, not `develop`) - pending.

### Routes (GET, `auth:sanctum`, **Super Admin only**)
```
api/reports/user-tests/table                              → ReportController::userTestsTable
api/reports/user-tests/table/export                       → ReportController::exportUserTestsTable
api/reports/user-tests/test-takers/{patient}/export       → ReportController::exportTestTakerHistory
```
Literal segments sit before `{patient}`; route order is fine.

### Contract
- `table` params: `page`, `limit` (1-100), `sort_by`, `sort_order`, `search`, `status` (`pending|inprogress|completed|abandoned`), `test_id`, `user_id`, `organization_id`, `patient_id`, `from_date`, `to_date` (Y-m-d). Returns `data`, `pagination`, `columns`, `required_columns`.
- `table/export`: same filters + `columns[]` or `all=1`. Required columns are always added; `all=1` overrides `columns[]`.
- `test-takers/{patient}/export`: full history, **ignores all filters**, includes soft-deleted tests; xlsx default, `?format=pdf`; unknown patient -> 404 `api.patient_not_found`.
- `UserTestsTableRequest`: non-Super-Admin -> 403 `api.forbidden` **before** validation; `sort_by`/`columns` allow-listed; `to_date < from_date` -> 422.

### Service (`UserTestsReportService`)
- `TABLE_COLUMNS` (10): Unique Identifier, Test Name, Test Taker, Account, Integrated Partner, Status, Eye Tested, Test Taken Via, Test Taker IP Address, Date. `REQUIRED_TABLE_COLUMNS`: Unique Identifier, Test Name, Status, Date (`unique_test_id`, `test_name`, `status`, `created_at`).
- `TABLE_SORTS` = sort allow-list. **Test Taker is not sortable** (names are ciphertext); name search is done in PHP via `PatientNameSearch`.
- `tableQuery()` is the **single filter implementation** shared by screen and export - change filters there only. `tableRow()` decrypts names; `resolveTableColumns($requested, $all)` keeps required columns, order follows `TABLE_COLUMNS`.
- `UserTestsReportExport` takes optional `?array $columns`; `null` = legacy 5-column export (old callers unchanged).
- Audit reuses `report.user_tests_exported` with `scope=table|patient_history`; details carry only format, date filter, count.

### Traps
- Trap 3: the new routes are Super Admin gated in the FormRequest/controller, but the legacy `user-tests`, `list-patients-having-tests` stay open to any authenticated user.
- Trap 9 (unguarded `sort_by` in `query()`) **still true for the legacy path**; the new table path is allow-listed via `TABLE_SORTS`.

### Screen
`src/pages/Reports/UserTests.js` - see [FRONTEND.md](../FRONTEND.md#tabular-user-tests-report-2026-10-12-uncommitted).

## Export sanitising (2026-10-13, uncommitted)

- New `App\Support\SpreadsheetSafe` (`cell()` / `row()`): prefixes an apostrophe to non-numeric strings starting with tab, CR, `=`, `+`, `-` or `@` (formula injection). Applied in `UserTestsReportExport` (legacy and table branches) and `UserTestsDetailExport`. Test: `tests/Unit/Support/SpreadsheetSafeTest.php`.
- `PatientXlsxExport` is deliberately **unchanged**: it writes `setCellValueExplicit(TYPE_STRING)`, where an apostrophe would show literally.
- **Known limitation:** report name search decrypts names in PHP (`PatientNameSearch`) and the export has **no row cap**; large datasets cost time and memory.
- SPA: export thunks now parse a Blob error body into `{message}` ([FRONTEND.md](../FRONTEND.md)).
