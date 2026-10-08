# Testing

## TCV-Backend — PHPUnit 11

```bash
composer test          # config:clear + artisan test
php artisan test
php artisan test --filter=LmsLaunchTest
vendor/bin/phpunit --testsuite=Feature
```

`phpunit.xml` runs tests against an **in-memory SQLite** database:

```xml
<env name="APP_ENV"          value="testing"/>
<env name="DB_CONNECTION"    value="sqlite"/>
<env name="DB_DATABASE"      value=":memory:"/>
<env name="CACHE_STORE"      value="array"/>
<env name="QUEUE_CONNECTION" value="sync"/>
<env name="SESSION_DRIVER"   value="array"/>
<env name="MAIL_MAILER"      value="array"/>
<env name="BCRYPT_ROUNDS"    value="4"/>
```

☠️ **SQLite in tests, MySQL in production.** Anything MySQL-specific will pass here and fail there:
`havingRaw` behaviour, `enum` columns, `whereJsonContains`, `SUM` type coercion, and
`Schema::defaultStringLength(191)`. The `havingRaw` in `TestExecutionService::getSessionDetails()` is a
live example of a query worth verifying against real MySQL.

☠️ **SQLite does not enforce `VARCHAR(n)` at all** — it is the same trap, and the one most likely to bite
a data migration, because the tests cannot fail on it. Code that *lengthens* a stored string (every
`[token]` → `{{token}}` rewrite is two characters longer) passes green here and, on MySQL, either throws
`Data too long` in strict mode or truncates the column silently. Check the width in PHP with
`mb_strlen()` and test *that* guard; there is no driver behaviour to assert against.
`Schema::getColumns()` will not save you either — Laravel's SQLite grammar emits a bare `varchar`, so the
declared width is not in the schema the tests see. `ws-401`'s
`test_a_subject_that_would_outgrow_its_column_is_left_alone` is the worked example.

☠️ **SQLite returns native integers; MySQL's PDO can return them as strings.** Any `===` comparison
against a column value therefore passes here and can fail there unless the model casts it. `ws-402`'s
`CreditsPolicy::delete()` is the worked example — it compares `source` and `original_source` with `===`,
and both are in `Credits::$casts` for exactly that reason. A test asserting the policy's behaviour will
be green either way, so the cast is not something the suite can protect; check it by reading
[POLICIES.md](POLICIES.md).

☠️ **`dropForeign()` on SQLite: the array form works, the name form throws.**
`compileDropForeign()` in Laravel 12's SQLite grammar raises
`RuntimeException('This database driver does not support dropping foreign keys by name.')` only when
the command carries no columns — i.e. `dropForeign('fk_name')`. `dropForeign(['column'])` passes the
column through and is handled by SQLite's table rebuild, so a `down()` written that way rolls back
fine under `RefreshDatabase`. Worth knowing before adding a `DB::getDriverName()` guard that isn't
needed: `TestSessionPatientIdMigrationTest` rolls its migration back and forward on SQLite precisely
because the migration uses the array form.

☠️ **`lockForUpdate()` is a no-op on SQLite.** `Credits::revokeGrant()` (`ws-402`) holds the user's
ledger with one to stop two concurrent revokes both reading the same unspent balance. The suite cannot
exercise that race at all — it is only really closed on MySQL (dev/QA/prod).

☠️ **SQLite sorts ties stably; MySQL does not.** Rows with equal sort values come back in insertion
order on SQLite, every time. MySQL's `ORDER BY … LIMIT/OFFSET` leaves their order undefined, so a paged
list with no primary-key tiebreak repeats or skips rows between pages on dev/QA/prod. A test that walks
every page and checks each row appears once **passes with or without the fix**. Assert the tiebreak
directly instead: create rows that tie, and expect ascending id order for `asc` and descending for
`desc`. `ws-502`'s `*ListSortTest` files are the pattern, and each notes which of its tests actually
guards the fix.

☠️ **An `enum` column sorts differently too.** MySQL orders `enum` values by their **declared position**,
SQLite by the string, so a test asserting the order of a plain `orderBy('enum_col')` pins the SQLite
answer. `ws-502`'s first commit fell into this. The fix is in the query, an explicit `CASE`
([DISCOUNT_CONTEXT](CONTEXT/DISCOUNT_CONTEXT.md) trap 6), not in the test.

☠️ **SQLite accepts a string in an integer column; MySQL rejects it.** `tests.status` and `tests.layout`
are `tinyint` on MySQL, so a fixture like `Test::create(['status' => 'active'])` passes the suite and
fails on MariaDB with *Incorrect integer value*. Use `1`. `ReportListSortTest` does. The existing
Authorization and Reports tests still pass `'active'` and would fail on MySQL.

⭐ **To check a MySQL-only behaviour, run the tests against a throwaway MariaDB.** XAMPP ships
MariaDB 10.4 (`C:\xampp\mysql\bin`). Start `mysqld` on a spare port (e.g. 3399) with
`--skip-grant-tables` and a `--datadir` in a temp folder, and create an empty database in it. Then
prefix one run with the connection:
`DB_CONNECTION=mysql DB_HOST=127.0.0.1 DB_PORT=3399 DB_DATABASE=<scratch> DB_USERNAME=root DB_PASSWORD= php artisan test <files>`.
Shut it down and delete the folder afterwards. Your XAMPP data is never touched. `ws-502` used this to
prove the enum and tiebreak fixes.

☠️ **`QUEUE_CONNECTION=sync` in tests.** `ProcessLmsDeliveryJob` runs inline, so the delivery tests
never exercise the fact that **a deployment without `COMPOSE_PROFILES=workers` has no queue worker at
all** — the default ([QUEUES.md](QUEUES.md)). Nor can they see `database.retry_after` re-reserving a
long-running job, or the scheduler not running: the suite calls commands and jobs directly.

☠️ **One MySQL-only migration takes down the whole suite.** `RefreshDatabase` re-runs every migration
for every test, so an unguarded `ALTER TABLE … MODIFY` or `CONCAT()` aborts migration and **every test
errors** — not only the ones touching that table. The suite was 100% red from **2026-04-17** until
`ws-361` guarded the two offending migrations on **2026-08-21**, and nobody noticed for four months
because CI runs no tests. Guard driver-specific SQL with `DB::getDriverName() === 'mysql'`
([DATABASE.md](DATABASE.md#migration-practice)).

## What is covered — and what isn't

| Suite | Files | Tests | Covers |
|---|---|---|---|
| `tests/Feature/Lms/` | 5 + 1 fixture trait | **54** | launch + signature, admin config/keys/dead-letters, delivery + retry, section progress, xAPI batching |
| `tests/Feature/Credits/` | 1 | **12** | `CreditHistoryTest` — the unified credit-history view |
| `tests/Feature/Credits/CreditRevocationTest.php` · `CreditRevokeOriginTest.php` | 2 | **34** | `ws-402` (merged 2026-09-07) — `CreditRevocationTest` (17): `revokeGrant()`'s unspent-only claw-back, the 422 on a fully-spent grant, unlimited-grant removal + `settleNegativeBalance()`, the 403 an ineligible `destroy()` now returns; then the expiry set added post-review — the settle command repairing a grant spent *before* it expired, not handing back credits that expired *unspent*, idempotence, `used_credits`/`remaining_credits` reported as null rather than 0 for an unallocated grant, the partial-revocation message and `data`, and a legacy refund with a null `original_source` returning 403; then a third round (2026-09-07) covering [S-19](SECURITY.md#s-19) — a non-super-admin gets 403 and writes no counter-entry or adjustment — the `has_expiry = 1, expiry_date = NULL` grant being deletable rather than 422, and `used` vs `revoked` being reported as separate figures; then a fourth round (2026-09-07) adding the counter-entry expiry symmetry (a partial revoke must not leave a deficit once the grant expires) and the unlimited-beats-finite origin trace. `CreditRevokeOriginTest` (9): `traceConsumedOrigin()`'s FIFO replay across Manual/Purchase/Revoked grants, and the `SOURCE_PURCHASE` fallback when the trace can't be pinned down |
| `tests/Feature/Auth/AuditedLoginLogoutTest.php` · `Services/AuditServiceLogTest.php` · `Unit/Services/Audit/` ×2 · `Unit/Database/Seeders/AuditLogSeederTest.php` | 5 | **29** | Audit Trail (PR #227, 2026-09-09) — the catalogue's shape, `AuditService` masking/denylist and `sessionKey()` derivation, the seeder, and login/logout actually emitting rows. ⚠️ Covers the **plumbing**, not coverage of the app: only auth and pricing call the service, so a passing suite says nothing about the ~390-line catalogue's other events |
| `tests/Feature/Authorization/AuditLogAccessTest.php` | 1 | **10** | the Super-Admin gate on all three `api/audit-logs` endpoints — including that `index()`'s gate is in `AuditLogIndexRequest::authorize()` and so must 403 *before* validation echoes the valid filter vocabulary back |
| `tests/Feature/Qa/QaAutomationTest.php` | 1 | **17** | ⚠️ `api/qa/*` (PR #223, 2026-09-08) — token issue, forced state, and crucially that the group is **404 outside QA**. This is the only guard on an account-takeover surface: [S-20](SECURITY.md#s-20--the-qa-automation-endpoints-are-an-account-takeover-surface-gated-only-by-app_env) |
| `tests/Feature/DiscountCodes/` | 2 | **19** | code validation + redemption, and the live-code unique index migration (`ws-392`, merged) |
| `tests/Feature/ContactFormTest.php` | 1 | **4** | contact enquiry → HubSpot upsert + ticket; optional `company_name` |
| `tests/Feature/ProfileStateValidationTest.php` | 1 | **3** | `UpdateProfileRequest` — `state_id` required only for countries that have states |
| `tests/Unit/` | 1 | 1 | Laravel's stock `ExampleTest` |
| `tests/Unit/EmailContentTest.php` | 1 | **20** | `EmailContent::linkify()` + `anchorPlaceholders()` — entity handling, attributes, `<style>` blocks, unclosed anchors, idempotence (`ws-373`, merged into `ws-404`) |
| `tests/Feature/TestInvitations/` — `BatchedInvitationSendTest` (31) · `InvitationSendReviewFixesTest` (31) · `InvitationSendRaceTest` (10) · `MailPreflightTest` (6) · `EmailTemplatePlaceholderValidationTest` (14) · `OrganizationEmailTemplateTypeTest` (31) · 3 audit files (10) | 9 (+ the migration test below) | **133** | `ws-404`, on `develop` since 2026-09-15 — batched send + 202, after-response vs queue dispatch (and the queue batch budget resolving at run time), the defer/fail taxonomy (connect failure, SMTP 4xx, sender-quota 550, SES errors, post-DATA errors *failing*), the deferral cap and its `>` boundary, the claim-held write-off ordering, expiry refunds and their double-refund guard, `credited_by = null`, orphan-page starvation, the cancel 409 race, `mail:preflight`, placeholder validation, and the `ws-373` linkify guard. ✅ The last two counts are **on `develop` since 2026-09-21** (`ws-401` PR #252). Round 2 took `OrganizationEmailTemplateTypeTest` from 8 to 31 (the org send path, its escaping and URL-anchor guard, the greeting spacing, the seeded body, and the per-send query cost). Counts are static `test_*` method counts, so PHPUnit reports 150 for these nine: three methods are data providers, 20 cases between them. ⚠️ Counts re-verified against an actual run on 2026-09-21 (`ws-401` review round three) — the previous 128/136 had already drifted before that round touched anything |
| `tests/Feature/Settings/RestrictedIpEnforcementTest.php` | 1 | **6** | `ws-449` — the first test that `restricted_ips` actually blocks anyone: a listed IP as the direct peer, and as `X-Forwarded-For` behind a trusted `172.18.0.4` proxy. ⚠️ It proves Laravel's half; it cannot see nginx, so it says nothing about [S-16](SECURITY.md#status-2026-09-17--both-backend-halves-shipped-the-frontend-nginx-precondition-did-not)'s forgery path |
| `tests/Feature/Auth/AuditedImpersonationTest.php` · `ImpersonatorBackfillTest.php` | 2 | **15** | Audit impersonation (PR #245) — both identities recorded, admin-action flag, IP on the impersonator card, the `FlexibleAuthMiddleware` route group, and the backfill's session-key reconstruction, idempotence and `down()` |
| `tests/Feature/TestInvitations/NormalizeLegacyBracketPlaceholdersMigrationTest.php` | 1 | **13** | `ws-401` (merged) — the legacy `[bracket]` → `{{token}}` repair migration: the rewrite itself, `[link]` → an anchored Start Test button, subjects rewritten *without* anchoring, and the four ways it must hold back — a token the row's `type` does not render, a row whose type has no vocabulary at all, a subject that would outgrow its column, and `<style>` block contents. Also pins that `email_template` is untouched, that a canonical row is byte-identical afterwards (☠️ **failing since 2026-09-22**, [stale fixture](#-develop-is-red-since-2026-09-22-one-stale-ws-401-test)), and that a second `migrate` is a no-op |
| `tests/Feature/RegistrationVerificationEmailTest.php` | 1 | **15** | `ws-417` (merged) — the verification mail fires at registration and *not* at login, the 24 h window is anchored to signup and login cannot move it, expired-token resend, and the untouched login paths (verified user, super admin, wrong password, suspended) |
| `tests/Feature/EmailSubjectPrefixTest.php` | 1 | **10** | `ws-417` — subject branding across raw/`MailMessage`/DB-template sends, idempotence, casing, empty subject |
| `tests/Feature/EmailBodyHasNoBrandingHeaderTest.php` | 1 | **6** | `ws-417` — seeder and migration leave no branding header; `down()` does not re-brand blank rows; three real mail bodies verified |
| `tests/Feature/Authorization/` | 3 | **22** | `tcv-backend-codefix` (merged) — `SessionOwnershipTest` (9) and `OrganizationScopeTest` (8) cover [S-02](SECURITY.md)/[S-03](SECURITY.md)/[S-14](SECURITY.md)/[S-18](SECURITY.md): they build real SHA-256 sessions rather than stubbing the middleware, so a forged `patient_id`/`org_id` is genuinely rejected. `TestSessionPatientIdMigrationTest` (5, added 2026-09-07) covers the migration's data step — sessions with no recoverable identity are expired, invitation-backed ones are untouched, an already-expired row keeps its timestamp, and no duplicate index is left beside the foreign key. All three files were tightened on 2026-09-07: `assertNotEquals(200, …)` — which a stray 500 satisfies — was replaced with exact statuses, and `test_staff_cannot_reassign_a_patient_to_another_account` now asserts the PUT actually returned 200, so it can no longer confuse "ownership is protected" with "the route is broken" |
| `tests/Feature/RateLimitScopeTest.php` | 1 | **5** | `tcv-backend-codefix` (merged) — the limiters are keyed per account, not per (shared) IP: one account exhausting its budget must not lock out another, asserted only after confirming the first really is 429 so it cannot pass vacuously. Plus (2026-09-07) that every `throttle:<name>` a route references resolves to a registered limiter, and (PR #245) exactly one `auth.account_locked` row per lockout, including across two separate lockout cycles |
| `tests/Feature/Patients/PatientUpdateFieldsTest.php` | 1 | **4** | `tcv-backend-codefix` (merged) — added 2026-09-07 after `zipcode` was found silently unwritable. The structural case asserts every `PatientUpdateRequest` rule key names a real fillable attribute, so the *next* misspelling fails here rather than shipping; the rest pin that `zipcode` and `test_condition` actually persist through `PUT` and that `user_id` still cannot be reassigned |
| `tests/Feature/Credits/CreditsExpiryBoundaryTest.php` | 1 | **6** | `tcv-backend-codefix` (merged) — a credit dated "expires today" counts for the whole of that day and stops the day after, for finite and unlimited grants alike. Pins the DATE-vs-DATETIME change described in [CREDITS_CONTEXT](CONTEXT/CREDITS_CONTEXT.md) |

☠️ **`develop` `a5938b2e`: 1398 passed, 3 failed** (1401 tests, 4948 assertions, `php artisan test`, in-memory
SQLite, measured 2026-10-01). The +57 since `e8507fb9` is exactly the patient export (PR #291):
`Feature/PatientExportTest` (55) and `Unit/Services/Reports/PatientExportServiceDiagnosisExpressionTest` (2),
**all passing**. Of the 3 failures, **one is real**: the same stale `ws-401` test below.
⚠️ **The other two are environmental, not regressions:**
`InvitationSendReviewFixesTest::test_a_bare_link_placeholder_is_still_linkified` and
`OrganizationEmailTemplateTypeTest::test_the_verification_link_is_still_anchored_for_an_organization` render
a host-less `/app/test-invitation/…` link that is never anchored. They also fail on `e8507fb9` when run in the same
environment, although the 2026-09-29 run passed them, and the export PR touches no invitation code.
Suspect an unset `FRONTEND_APP_URL` in `.env` ([ENVIRONMENT.md](ENVIRONMENT.md)). Re-measure with a
correctly configured `.env` before treating them as real. ⚠️ `artisan` (and therefore `php artisan test`
and `route:list`) cannot boot on `develop` unless `HUBSPOT_ACCESS_TOKEN` is set: `HubSpotService`'s
constructor throws when it is empty. Any value works locally.

The previous measurement: **`develop` `e8507fb9`: 1343 passed, 1 failed** (4152 assertions, 136 s, `php artisan test`, in-memory
SQLite, measured 2026-09-29). The growth since `0197fd1b` comes from `ws-451`
(`Credits/FreeOrderCheckoutTest` 19, `Stripe/StripeZeroAmountInvoiceTest` 2) and `ws-459` PR #284
(`Migration/LegacyLocationAdminPayloadTest` 4). The one failure is the **same** stale `ws-401` test below,
unchanged. The patient-export feature was committed straight to `develop` on 2026-09-28 (`7a28fa6e`) and
reverted the same day (`e8507fb9`), so it was not in that count. It returned via PR #291 (above).

The previous measurement: **`develop` `0197fd1b`: 1317 passed, 1 failed** (4058 assertions, 96 s, measured
2026-09-23). The +127 since `330cf77d` was the five PRs of the 2026-09-23
sync: `ws-459` dedup (`OrganizationLookupDuplicatesTest`, `InsertOrIgnoreNeedsUniqueConstraintTest`),
`ws-459` location (`LegacyLocationResolverTest`, `BackfillMigratedUserLocationTest`,
`LegacyLocationDisplayTest`), and `ws-401` (`OrganizationEmailTemplateTypeTest` +~800 lines,
`EmailTemplatePlaceholderValidationTest`).

#### ☠️ `develop` is red since 2026-09-22, one stale `ws-401` test

`NormalizeLegacyBracketPlaceholdersMigrationTest::test_a_row_already_using_canonical_tokens_is_left_untouched`
(line 320). Its `reapplyOver()` helper rolls back **every** migration from `2026_09_03_000002` onward and
then runs `migrate` again. Since PR #278 that re-run includes
`2026_09_22_000003_backfill_verification_code_and_expiry_in_org_test_link_templates`, which inserts the
verification-code block after the `Start Test` anchor of any `org_test_link` row that lacks the token.
The fixture row is exactly that shape, so the body is no longer byte-identical. **The migration under test
is fine. The assertion is stale.** Fix the test, either by putting `{{verification_code}}` in the fixture
or by rolling back only the migration under test, and do not weaken `000003`. It went unnoticed because
CI runs no tests (see below). Found at the 2026-09-23 KB sync.
⏳ **Fixed on branch `ws-401-stale-template-test`** (`b2541667`), **still not merged at the 2026-09-29
sync** — `develop` is still red on exactly this test: the fixture now carries
the verification-code/expiry block, as a canonical `org_test_link` body does, and all 13 tests in the file
pass. It is the only *real* failure on `develop` `a5938b2e` (2026-10-01; two more fail on that machine for environmental reasons, see above), so merging it turns the suite green.

The previous measurement, for history: **1191 tests passed on `develop` `330cf77d`** — **3632 assertions, 0 failures**, 1 min 41 s (measured
2026-09-21 with `php artisan test`, PHPUnit 11.5.55, PHP 8.2.12, in-memory SQLite; exit code 0). Up from
812 on `ff9be500`: the +379 is almost entirely **`ws-459`** (PR #255, 2026-09-18), which brought the
legacy-migration and encryption suites — `LegacyCipherTest`, `LegacyEncrypterHardeningTest`,
`MislabelledPatientPiiTest`, `StagingTablesAreIsolatedTest` and the `migrate:*` command coverage — plus
`ws-502`'s tiebreak assertions, `ws-480`'s purchase-refusal suite and the audit-log export tests.
A static count finds **723 `test_*` methods in 119 files** — the gap to 1191 is data providers.
The Stripe SDK prints several `Undefined property of Stripe\PaymentIntent`
notices to stderr during the run; they are noise, not failures. 📌 The
`InvitationSendReviewFixesTest:261` quoted-printable assertion that earlier KB notes call a known failure
**passes** on `develop`.

The per-branch totals this section used to track (93 on `develop`, 149 on `ws-404`, 186 on `ws-417`, 245
on `ws-401`, 267 on `tcv-backend-codefix`) are **history**: those lines have all landed, so `develop` is
the number that matters. Still untested: the test execution loop, resume, payments, reports,
organisations, and anything nginx does. ⚠️ *Payments* is narrowing but not closed — `ws-480` (merged
2026-09-18, below) pins the unlimited-credit purchase refusal; nothing still covers a **successful**
Stripe purchase end to end. `ws-451` (merged 2026-09-28, below) covers a successful **$0** order, which
never creates a PaymentIntent (its Stripe invoice calls are mocked).

### ✅ Patient CSV export added 57 backend + 39 frontend tests — on `develop` since 2026-09-29 (PR #291 / #420)

`tests/Feature/PatientExportTest.php` (**55** on `develop`; **74** on the unmerged `improve/export-format`, which also covers the POST-only route, timezone fallback, `X-Export-Timezone`, the started + outcome audit entries, a once-only shutdown hook, and controller-level runs of the completion/interruption callbacks (a stream that throws or hits an `Error` mid-way, and a broken callback being reported rather than thrown; a real fatal, a killed worker and a real cancel through nginx cannot be raised in-process and stay a manual QA check) (`AuditEventCatalogTest` counts 69 events there); the future-date tests are pinned to a fixed clock because the UTC+14 rule makes a real-clock "tomorrow" time-of-day dependent; 🔶 **86** at `f361256d` (2026-10-05), which reads the XLSX back and adds the formula-as-text, temp-file, stale-sweep, 5 000-cap and 422/500 `error_code` cases; the temp-file test is vacuous on Windows because `tempnam()` truncates the prefix — [PATIENT_CONTEXT](CONTEXT/PATIENT_CONTEXT.md#-update-2026-10-05-xlsx-workbook-5-000-row-cap-clear-button)) and
`tests/Unit/Services/Reports/PatientExportServiceDiagnosisExpressionTest.php` (**2**) cover
`GET api/patients/export` ([PATIENT_CONTEXT](CONTEXT/PATIENT_CONTEXT.md#patient-csv-export--on-develop-since-2026-09-29-pr-291--420)). All 57 pass in the 2026-10-01 full run on
`a5938b2e`. On the frontend, `apis/exportPatients.test.js` (5), `constants/patientExport.test.js` (13) and
`PatientPage/ExportPatientModal.test.js` (21) all pass on `308fe7a` (run with
`CI=true npx react-scripts test --watchAll=false <files>`).

| Case | Covers |
|---|---|
| file shape | UTF-8 BOM + `text/csv`, the two banner rows, the header row in the agreed order, the `End of Export, N row(s)` trailer |
| who is in the file | cross-tenant isolation; soft-deleted patients absent; **every** Registered-tab patient appears, tested or not; invitation-only migrated patients excluded; `the_export_lists_the_same_patients_as_the_registered_tab` |
| which tests attach | sent-in-window **or** completed-in-window; a test matching both counts once; boundary timestamps; local-day windows in the viewer's timezone |
| date range | exactly one calendar year accepted, one day more rejected; leap-day start clamps to 28 Feb; same-day range; future `to`; viewer-timezone "today"; `Asia/Calcutta` accepted, invalid zone rejected; missing `from`/`to` |
| cells | US month-first / ISO / unparseable DOB; UTC timestamps stay UTC under a non-UTC app timezone; four diagnosis shapes (JSON null, missing key, missing diagnosis, the string `"null"`); formula injection neutralised; OS/OD pair → two rows |
| search / filter | search finds an untested patient; search beats the quick filter; `%` is matched literally |
| audit | exactly one success row with the right details; the streamed count replaces the pre-flight count; the search term never reaches the row; a failed audit write aborts before any CSV is sent |
| guards | 401 unauthenticated; 422 + failed row over the row ceiling; 429 + **one** failed row however often the client retries; numeric `GET patients/{id}` still resolves (`whereNumber`) |
| MySQL branch | ⚠️ SQL text only (unit test). No test runs it against a live MySQL — [PATIENT_CONTEXT](CONTEXT/PATIENT_CONTEXT.md#patient-csv-export--on-develop-since-2026-09-29-pr-291--420) trap 12 |

### ✅ `ws-451` added 21 backend tests — on `develop` since 2026-09-28 (PRs #285, #287)

`tests/Feature/Credits/FreeOrderCheckoutTest.php` (**19**) covers the $0-order path,
`POST api/payment/complete-free-order`, and `tests/Feature/Stripe/StripeZeroAmountInvoiceTest.php` (**2**)
covers `StripeService::recordZeroAmountInvoice()`
([BILLING trap 10](CONTEXT/BILLING_CONTEXT.md#10-a-100-discount-code-cannot-go-through-stripe-ws-451)).
All pass in the 2026-09-29 full run on `develop` `e8507fb9`. `RateLimitScopeTest` also gained `free-order`
in its list of limiters that must be registered. The 2026-09-23 branch-only measurement (10 tests) predates
the idempotency, invoice, throttle and tier-overlap review commits. `php artisan test --parallel` does not
run here, because ParaTest is not installed.

| Case | Covers |
|---|---|
| unauthenticated | 401 — the route sits in the `auth:sanctum` group |
| no `idempotency_key` | 422 |
| retry with the same key | the first order comes back, nothing is granted twice, no second invoice |
| key reused for another order / by another account | 409 |
| two price tiers overlap the credit count | 422, no grant |
| throttled per account | `throttle:free-order`, 5/min |
| order recorded in Stripe | a $0 invoice is created and finalized; a refused order is **not** invoiced; a Stripe failure grants nothing and writes `billing.payment_failed` |
| `StripeZeroAmountInvoiceTest` | full-price line + equal negative line, finalized; an invoice Stripe did not mark `paid` throws |
| 100% code, 750 credits | grant of 750 with `SOURCE_PURCHASE`, a $0 `succeeded` transaction with a `free_` id, and `original_amount` 6637.50 **priced on the server**; `countUses()` goes to 1 |
| fixed $50 code, 750 credits | 422 and no grant — the server price leaves an amount due |
| fixed $100 code, 3 credits | accepted — $88.50 is fully covered |
| `max_uses_per_user = 1` | the second order gets a 400 and only one grant is written |
| unknown code / credits outside every tier / unlimited account | 404 / 422 / 422 |
| credit history | the grant shows as `type: purchase`, `payment_method_type: discount_code` |
| failure after the grant is written (`transaction_details` dropped) | 500, **no** credit or transaction row survives, and a `billing.payment_failed` audit row is written (ws-451 PR review) |

☠️ The SPA half (`PaymentForm`'s `isFreeOrder` / `isBelowMinimum` branches) has no test.

### ✅ `ws-459` added 9 backend tests — on `develop` since 2026-09-21 (PR #271, `2fb62959`)

The duplicate Add Organisation dropdown fix
([DATA_MIGRATION_CONTEXT trap 8](CONTEXT/DATA_MIGRATION_CONTEXT.md)). When it was measured before merge,
the full suite passed at 1200 tests / 3665 assertions, and 9 of those tests are in these two files.

| File | Tests | Covers |
|---|---|---|
| `Organizations/OrganizationLookupDuplicatesTest.php` | **6** | the unique index rejects a second `AICC/SABA`; `api/dropdown/compliances` lists each option once; duplicates collapse onto the lowest id; **every organisation row is diffed before/after** — unmerged rows byte-identical, merged rows differing in `compliance_id` and nothing else (`updated_at` included); a merge never hides a compliance that was visible; a clean database is a no-op. Needs `Sanctum::actingAs()` — the dropdown routes are inside the `auth:sanctum` group |
| `Migration/InsertOrIgnoreNeedsUniqueConstraintTest.php` | **3** | scans `app/Console/Commands/*.php` for `->table('x')->insertOrIgnore` and fails if a target has no unique index and no documented reason. `TRACKER_GUARDED` lists the 6 that rely on `migration_tracker` instead; `patients` and `credits` are flagged there as the weakest, both carrying an unindexed `legacy_id` |

☠️ The guard test is **advisory, not a gate** — CI runs no tests (see below), so the unique indexes
themselves are the only enforcement that cannot be skipped.

### ✅ `ws-480` added 6 backend tests — on `develop` since 2026-09-18 (PR #254)

`tests/Feature/Billing/UnlimitedCreditPurchaseRefusedTest.php`, added 2026-09-17 with the PR-review fix
that moved the unlimited-credit refusal onto the live purchase path
([BILLING trap 9](CONTEXT/BILLING_CONTEXT.md#9--the-unlimited-purchase-refusal-is-on-the-deprecated-surface-ws-480)).
Measured on the branch with the fix applied: **the full backend suite passes at 837 tests / 2572
assertions**, 6 of them this file. ⚠️ The fix was **uncommitted in the working tree** when measured, so it
is not in `8d247f8c`.

| Case | Covers |
|---|---|
| 4 refusal cases | 422 from `api/payment/initialize`, `api/payment/confirm`, `api/stripe/create-payment-intent` and `api/stripe/confirm-payment`. The two `api/payment/*` cases inject a `PaymentProviderInterface` mock with `shouldNotReceive()`, so the test fails if the refusal lands *after* any Stripe work rather than before it |
| ordinary account not refused | a finite grant with a balance still reaches `initializePayment()` — the guard keys off an unlimited grant, not off having credits |
| expired unlimited grant | `Credits::hasUnlimited()` goes through `scopeActive()`, so a lapsed `is_unlimited_credit` row does not refuse the purchase — the same boundary `CreditsExpiryBoundaryTest` pins for the balance |

☠️ The SPA half is still unpinned: no `CreditPage` or `Checkout` test, and that is the gate a customer
actually meets ([FRONTEND.md](FRONTEND.md#credit-purchase-gate-ws-480)).

### ✅ `ws-502` added 17 backend tests — on `develop` since 2026-09-17 (PR #253)

First measured on the branch (`00d5e98f`, 2026-09-16): 15 tests, all passing. Run against the
pre-`ws-502` code, every test in the four new files other than `DiscountCodeListSortTest` failed (11 of
11), as did 3 of that file's 4. The 2026-09-17 PR-review fixes (`15207600`) added 2
report tests and extended 2 others. Each new or extended assertion fails against `00d5e98f` and passes
with the fix. After them, the **full backend suite passes, 829 tests on SQLite**, and all 17 sort tests
pass on MariaDB 10.4 (see the ⭐ above).

| File | Tests | Covers |
|---|---|---|
| `DiscountCodes/DiscountCodeListSortTest.php` | **4** | Discount column = fixed before percentage, then value, on every engine; "Never" expiry last ascending; tied rows in id order both directions; every code exactly once across pages (passes without the fix too — see the SQLite trap above) |
| `Users/UserListSortTest.php` | **2** | Country by name with an unmatched `country_id` last; ties in id order for `full_name`, `account_status`, `created_at`, `country_id`, `credits` |
| `Organizations/OrganizationListSortTest.php` | **2** | ties in id order for name, status, compliance, credits; no compliance last ascending. Needs `Sanctum::actingAs(…, ['view-organizations'])` |
| `Credits/CreditListSortTest.php` | **3** | Unlimited above every amount; no expiry last ascending; ties in id order |
| `Reports/ReportListSortTest.php` | **6** | redemptions by first then last name; blank company (NULL and `''`) last ascending; tied redemptions in `td.id` order (rows carry no id, so each is marked by `price_per_credit`); patient drill-down honours `sort_by` and still defaults to newest first; Test IDs (UUIDs) sort as plain text, not naturally; its pages hold tied rows without repeats |

`Credits/CreditRevocationTest.php` also changed on `ws-502`:
`test_credits_an_admin_took_back_are_not_reported_as_user_usage` now finds the grant by id instead of
reading row 0, and asserts the row is there before reading it
([CREDITS_CONTEXT](CONTEXT/CREDITS_CONTEXT.md) trap 11). With the review fixes,
`tests/Feature/{Users,Organizations,Credits,Reports,DiscountCodes}` pass at **127 tests, 648
assertions**.

⭐ **`phpunit.xml` sets `MAIL_CONNECTION_RETRY_DELAY=0`.** The connection-failure tests exhaust every
retry on purpose; without it they would spend about a minute of the suite asleep. Keep it when adding to
that file — a real backoff there is not extra realism, it is just a slower suite.

☠️ **`ws-417` is the first auth coverage that has ever existed**, but it is narrow: registration,
login's verification gate, and the mail paths. Impersonation, password set/reset, the token brokers and
`verify-password` remain untested.

⚠️ **The suite was green on `develop` on 2026-09-21** (1191/1191) and **is not as of 2026-09-22**: one
stale `ws-401` test, [above](#-develop-is-red-since-2026-09-22-one-stale-ws-401-test).
`DiscountCodeIndexMigrationTest > the pair rolls back and reapplies`, which used to fail on a clean tree
and was for a while the one expected red, still passes. If it fails again, that is a regression, not the
known-bad case.

☠️ **Still uncovered on that branch, and "the suite passes" is not evidence for any of it:** the
session-token hashing migration, the `session_superseded` / `test_completed` middleware branches,
`organizationAllowsDownload()`'s positive path, the `/up` marker behaviour, and every
`lockForUpdate()` concurrency claim. The entrypoint bootstrap fix
([DEPLOYMENT.md](DEPLOYMENT.md) traps 3–4) is likewise verified by direct reproduction against a real
empty database, not by the suite — nothing in PHPUnit boots a container. Several assertions in the
authorization tests are `assertNotEquals(200)` rather than an exact status, so an unrelated 500 would
satisfy them.

`ws-402` measured 2026-09-07 (after the fourth review round): **273 passed, 817 assertions, 0 failed**. It
branches off the `ws-401` line, so it carries all of the above; its own delta is the 7 credits cases
added when the review findings were applied (19 → 26 across the two revocation files).

☠️ **CI still runs no tests**, so a
branch is only ever as verified as the last person to run the suite by hand; state in the PR whether you
did. `TCV-Backend/vendor/` must be installed for `php artisan test` to work at all — a checkout without
it cannot run a single test, which is exactly how a suite goes four months unnoticed-red.

`ws-404`'s suite is the first coverage the invitation subsystem has ever had. Two patterns in it are
worth reusing:

- **`Mail::fake()` does not work for these emails.** It only records `Mailable` objects, and the
  invitation is sent as a raw `Mail::send($view, $data, $callback)`. Count
  `Mail::mailer()->getSymfonyTransport()->messages()` instead — phpunit.xml already sets
  `MAIL_MAILER=array`.
- **After-response work runs in feature tests.** The test kernel terminates the request, so
  `->afterResponse()` batches really execute and really send. Use `Bus::fake()` +
  `assertDispatchedAfterResponseTimes()` to assert dispatch without sending; omit it to assert the
  emails genuinely go out.

☠️ **Do not lower `pcre.backtrack_limit` in a test to force a PCRE failure.** Laravel's inflector is
regex-based, so at `limit=1` `Str::plural()` stops working and every Eloquent model resolves to a
singular table name — `TestInvitation` queries `test_invitation` and the test fails with a confusing
"no such table". A guard that can only be reached that way is better left to code review than pinned
with a test that breaks the framework underneath itself.

`EmailContentTest` extends PHPUnit's `TestCase`, not Laravel's — `EmailContent` is pure string handling
with no container, no DB and no mail faking. That is the cheap pattern to copy for anything extractable
into `app/Support/`: it runs in milliseconds and sidesteps the SQLite-vs-MySQL trap above entirely.

`tests/Feature/Lms/CreatesLmsFixtures.php` is a **trait**, not a test — it is the shared factory setup.
Reuse it for anything LMS-related.

`tests/Support/CapturesSentMail.php` (`ws-417`) is the same idea for mail: `transport()`,
`lastSubject()`, `lastHtmlBody()` and a `seedEmailTemplate()` helper, wrapping the array-transport
pattern above so the three email suites do not each re-derive it. **Use it for any new test that
asserts on an email**, and note it makes `config(['mail.default' => 'array'])` in a `setUp()`
redundant — phpunit.xml already pins `MAIL_MAILER=array`.

☠️ **`POST /api/register` needs outbound DNS in tests.** `UserRequest` validates with
`email:rfc,dns`, a live MX lookup, so a runner without egress gets a `422` that has nothing to do with
the behaviour under test. Two consequences: `example.com` is **unusable** as a test address (it
publishes a null MX record and egulias rejects it outright — use a domain that really accepts mail),
and `RegistrationVerificationEmailTest` guards the call with a `checkdnsrr()` probe and
`markTestSkipped()` rather than failing misleadingly. Copy that guard for anything else that posts to
`/api/register`.

## What this means for you

- **Run the suite on `develop` before you start**, and treat the result as your baseline. A red suite
  here is usually not your change — see the migration trap above, which kept it red for four months.
- **Working on LMS or credit history?** Run the suite before and after. It is real coverage and it will
  catch you.
- **Currently failing on `develop`:** `LmsLaunchTest > terminal session is blocked` (expects 403, gets
  200). A genuine bug, not flake — `LmsSession::isTerminal()` has **no callers** and
  `FlexibleAuthMiddleware` never emits `session_ended`, so a token for a `reported`/`failed` session
  still authenticates. Tracked as [S-15](SECURITY.md#s-15--terminal-lms-session-tokens-still-authenticate).
- **Working on anything else?** There is no safety net. Say so plainly rather than claiming "tests
  pass" — passing 65 tests that never touch your change proves nothing.
- **Adding a test is high-value** precisely because the baseline is thin. `CreatesLmsFixtures` and
  `CreditHistoryTest` are the two patterns to copy.

## Model factories

`database/factories/` is included in the extractor's scan. Coverage is partial — check
[INDEXES/CLASS_INDEX.md](INDEXES/CLASS_INDEX.md) before assuming a factory exists for the model you
need. Only 29 of 40 models `use HasFactory`.

### 🔶 Patients newest first: 2 new backend tests, 1 renamed (unmerged, 2026-10-08)

Backend branch `fix/ux-patients-newest-first`, stacked on `feat/export-completion-and-sent-date`
([PATIENT_CONTEXT](CONTEXT/PATIENT_CONTEXT.md)).

- **New `tests/Feature/Patients/RegisteredPatientsOrderTest.php` (2).** `GET api/patients` lists the
  newest `created_at` first, and same-second ties fall back to the higher id first. `created_at` is set
  through the query builder so the fixture controls it exactly.
- **`PatientExportTest`.** `test_patients_are_streamed_in_ascending_order_across_a_batch_boundary` is
  renamed to `…_newest_first_…`. It still checks that all 550 patients cross the 500-patient batch
  exactly once, now as `array_reverse($expected)`. No other export test assumed an order.
- **Result.** `php artisan test --filter='PatientExport|RegisteredPatients'` gives **112 passed** on the
  branch.
- ⚠️ **`vendor/bin/pint --dirty` reformats unrelated lines** in `PatientController.php` and
  `PatientExportTest.php`, because both have pre-existing style drift. Revert those hunks rather than
  shipping them inside a behaviour change.

## TCV-Frontend — Jest + React Testing Library (CRA)

```bash
npm test                                   # watch mode
npm test -- --watchAll=false               # once (CI)
npm test -- --testPathPattern=src/App.test.js
```

**Eight passing test files exist** on `develop` and **117 tests pass** (re-measured 2026-09-11 with
`CI=true npx react-scripts test --watchAll=false`; it was 110 on 2026-09-09). `ws-402` is merged on the
frontend too, so the two credits files below and its six extra `DiscountCodeModal` cases are now the
baseline, not a branch extra.

A ninth suite, `src/App.test.js`, still fails to run for the reason documented below — so a healthy
local run reads **1 failed / 8 passed, 117/117 tests passing**. Read the test counts, not the suite
counts.

On `ws-407` (not yet on `develop`) that becomes **1 failed / 9 passed, 129/129** — the branch adds
`src/utils/validation.test.js`.

On `ws-480` (not yet on `develop`, measured 2026-09-17 with the review fix in the working tree) a full run
reads **1 failed / 17 passed, 191/191 tests passing** — still the same single `App.test.js` suite failure,
confirmed pre-existing by stashing the branch's own changes and re-running. The branch adds **4 tests** to
`src/redux/slices/userCredits/userCreditSlice.test.js`, a `settled` block covering the purchase gate:
`settled` false until a read finishes then true on success; **true after a failure carrying no error
message** (a network error, where the thunk rejects with `undefined` — the case that pinned the credits
page on `Loading credits…` indefinitely); still true while a later poll's `pending` has cleared `error`;
and reset when a different user signs in. See
[FRONTEND.md](FRONTEND.md#-settled-not-initialized--error--the-gate-has-to-key-off-something-forward-only).
⚠️ The SPA gate itself is still unpinned — there is no `CreditPage` or `Checkout` test. ⚠️ In a **full** run on that branch, `DiscountCodeModal.test.js` is
sometimes reported as a failed *suite* with all 55 of its tests passing, on a post-teardown `act()`
warning from Formik; it passes in isolation and imports nothing `ws-407` touches. Confirm a suspected
failure with `--testPathPattern` before blaming a change.

| File | Tests | Covers |
|---|---|---|
| `src/components/DiscountCodeModal.test.js` | **55** | the discount drawer: keystroke limits, tier-derived bounds, type-switch reset (added `ws-356`, extended `ws-392`), plus `ws-402`'s +6 — tier-reachability gating and auto-drop on a Minimum Order raise |
| `src/components/richTextEditor/emailPlaceholders.test.js` | **11** | the locked email-template placeholders: bare-token healing, the nested-anchor case, `data-inner` sanitising, the round-trip fixed point (`ws-400`, merged into `develop`; [INVITATION_CONTEXT](CONTEXT/INVITATION_CONTEXT.md)) |
| `src/utils/columns/addCreditsColumns.test.js` | **15** | `ws-402` (merged 2026-09-07) — the credit grid's Delete-visibility rules mirrored against `CreditsPolicy::delete()` (Manual yes, Purchased no, Revoked only when `original_source` is Manual, legacy null origin no), and the Utilized column's null-vs-zero rendering. Extended 2026-09-07: used vs revoked shown separately, and the disabled-Delete tooltip never blaming the user for an admin's claw-back. The one frontend guard on the 403-on-every-legacy-row bug |
| `src/redux/slices/createpaginatedslice.test.js` | **5** | `ws-402` (merged 2026-09-07) — `deleteItem` handing the server's response back to the caller, still dropping the row from `state.list` (now via `a.meta.arg`), surviving an empty response body, and — added 2026-09-07 — **keeping** the row when the server reports `removed: false`, plus asserting the request carries `skipErrorPopup` so a failed delete cannot raise two popups |
| `src/redux/slices/userCredits/userCreditSlice.test.js` | 9 | credit-read ordering and identity guards (`ws-397`) |
| `src/redux/slices/userProfile/passwordChangeSlice.test.js` | 6 | password-change slice (`ws-395`) |
| `src/utils/validationSchema/validatePricingTiers.test.js` | 5 | pricing-tier schema |
| `src/utils/sliderUtils.test.js` | 4 | slider helpers |
| `src/utils/validation.test.js` | **12** | `ws-407` (on the branch only) — the shared phone helpers in `src/utils/validation.js`: blank and separator-only treated as valid, the 15-digit and 20-character caps **truncating rather than rejecting** (the frozen-field regression), `phoneForSubmit` collapsing `()` to `''`, and `validateProfile` reporting a short number. The only guard on the checkout billing gate ([BILLING_CONTEXT](CONTEXT/BILLING_CONTEXT.md)) |
| `src/App.test.js` | 0 | the CRA "renders without crashing" stub — **fails to run**, see below |
| `src/redux/slices/discount/discountSlice.test.js` | **2** | `ws-502` (on `develop` 2026-09-17) — an older Discount Codes list response landing last is ignored; the latest still applies |
| `src/redux/slices/createpaginatedslice.test.js` | +**1** | `ws-502` (on `develop` 2026-09-17) — the same stale-response race through `fetchItemsPaginated`. Its three `deleteItem` tests now load the list through the thunk (`loadList`), because the strict stale check ignores a hand-dispatched `fulfilled` |
| `src/components/table/TableWithGlobalFilter.test.js` | **3** | `ws-502` (on `develop` 2026-09-17) — a `useServerSorting` header goes asc → desc → asc and never emits a cleared sort; `currentSort` moves the header when the page's sort changes elsewhere, without emitting `onSort`; the page echoing a click back changes nothing |
| `src/pages/Setting/RestrictedIps.test.js` | **2** | `ws-502` (on `develop` 2026-09-17) — newest IP first on load; a just-added IP becomes row 1. The first page-level RTL test with a real store and a mocked `AxiosInstance` — copy it for page tests |
| `src/pages/Reports/UserTests.test.js` | **2** | `ws-502` (on `develop` 2026-09-17) — no error popup for a search request a newer one replaced; the current request's error still shows |
| 🔶 `src/components/DateRangeInput.test.js` | **4** | `fix/ux-result-close-datefilter` (unmerged, 2026-10-08). Report mode: the `label` is the accessible name and the `title`; it falls back to the placeholder; the picked date becomes the tooltip; the `date-picker-input--report` / `is-disabled` / `has-value` classes. Typing mode: the visible `<label>` still names it, with no `aria-label` and no report modifier. Uses `getAllByTitle(...)[0]` for the wrapper, because `testing-library/no-node-access` rejects `parentElement` |
| 🔶 `src/hooks/useDateRangeFilter.test.js` | +assertions | same branch: the `MM/DD/YYYY` / *Pick a From date first* placeholders and `fromLabel` / `toLabel`, locked and unlocked |
| 🔶 `src/pages/UserPannel/ResultPage/ResultPage.test.js` | **3** | same branch. Close falls back when `window.close()` is refused: tokens cleared, flag set, `testResult.result` null. A reload with the `tcv_result_closed:<id>` flag shows the closed screen and never calls the API. Unmount clears the result. Mocks `miscApis`, `OrganisationSlice` and a virtual `react-router-dom` (`useParams` / `useLocation` on the invitation path) |
| `src/pages/AddCredits.test.js` | **2** | `ws-502` (on `develop` 2026-09-17) — the same for a sort click on Add Credits (`latestCreditsRequest`) |

On the `ws-502` branch with its 2026-09-17 review fixes, a full run reads **1 failed / 17 passed,
187/187 tests**. The one failed suite is `App.test.js`, as below. Nine of the twelve `ws-502` tests
fail against the code they fix. The other three are controls that pass either way: `discountSlice`'s
"still applies the latest response", and the two "still reports an error for the current request".

⭐ **Page-test pattern (`ws-502`).** A page that imports `react-router-dom` can't load under Jest (see
`App.test.js` below), so mock it **virtually**:
`jest.mock('react-router-dom', () => ({ useNavigate: () => jest.fn(), useLocation: … }), { virtual: true })`.
Use a real store with just the reducers the page selects, and mock `AxiosInstance`. The paginated
factory calls it as a function when `fetchConfig.buildRequest` is set, and as `.get` otherwise. Mock
`showPopup` to assert on errors. ⚠️ One full run on 2026-09-17 failed a `RestrictedIps` test once, and
8 more full runs (two of them concurrent) didn't reproduce it. The likeliest cause is RTL's 1s default
wait under a loaded parallel run, so the three page tests set `configure({ asyncUtilTimeout: 3000 })`.

Everything outside those files is still untested: auth, the test player, patients, reports.

`emailPlaceholders.test.js` is the guard rail named in
[CHANGE_IMPACT_GUIDE](CHANGE_IMPACT_GUIDE.md) for the template editor — `toEditorHtml`/`toTemplateHtml`
must stay a fixed point, and `hasTestLinkButton()` must keep rejecting a bare `{{verification_link}}`.
Run it before touching either. It is also the only frontend test that needs a real DOM parser rather than
just a reducer or a schema, so it is the one that breaks first if the jsdom environment changes.

☠️ **`src/App.test.js` fails to run, and it is not your change.** `react-router-dom@7` ships a
conditional `exports` map that CRA 5's bundled `jest-resolve` does not honour, so `import { BrowserRouter }`
in `App.js` dies with *"Cannot find module 'react-router-dom'"*. The suite therefore reports
**1 failed / 8 passed with 110/110 tests passing** (2026-09-09) — a failed *suite* with zero failed
*tests*. Read the
test counts, not the suite counts, and do not "fix" it by touching `App.js`.

`src/setupTests.js` wires `@testing-library/jest-dom`.

## TCV-Website

**No test setup at all** — no Jest, no Playwright, no test script in `package.json`. Only
`yarn lint` (`next lint`).

## Linting

| Repo | Command |
|---|---|
| `TCV-Backend` | `composer lint` → `pint --test` (Laravel Pint, PSR-12) |
| `TCV-Frontend` | `npm run lint` → `eslint src --max-warnings 0` |
| `TCV-Website` | `yarn lint` → `next lint` |

`composer lint` runs Pint in **check** mode. `vendor/bin/pint` with no flag **rewrites files across the
whole repo** — use `pint --dirty` so the diff stays limited to what you changed.

The SPA's `--max-warnings 0` means a single new warning fails the command.

## ☠️ CI runs no tests, and its lint gate does not gate

`.github/workflows/non-prod.yml` is a **deployment** pipeline. Its "Static Analysis & Linting" stage
runs `npm run lint` and `composer run lint` — and **`php artisan test` / `phpunit` appear nowhere in
it.** Nothing runs the 65 backend tests on a merge.

Worse, both lint steps are shaped as:

```yaml
if composer run lint; then
  echo "### Backend Code Quality: Passed" >> "$GITHUB_STEP_SUMMARY"
else
  echo "::error::Backend style violations detected."
  echo "### Backend Code Quality: Failed" >> "$GITHUB_STEP_SUMMARY"
fi
```

The `else` branch writes an annotation and a summary but **never exits non-zero**, so a lint failure
does not fail the job and the deploy proceeds. Treat the pipeline's green tick as "it built and
shipped", not "it passed".

**Run the tests locally.** See [DEPLOYMENT.md](DEPLOYMENT.md) for the rest of the pipeline.
