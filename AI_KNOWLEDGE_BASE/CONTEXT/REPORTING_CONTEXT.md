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

_[not deeply traced]: `SuperAdminDashboardController::index()`'s aggregation queries, the exact column
sets of the three Export classes, and the dompdf Blade templates._
