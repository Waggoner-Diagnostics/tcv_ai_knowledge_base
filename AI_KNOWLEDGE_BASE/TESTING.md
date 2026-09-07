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

☠️ **`QUEUE_CONNECTION=sync` in tests.** `ProcessLmsDeliveryJob` runs inline, so the delivery tests
never exercise the fact that **production has no queue worker at all** ([QUEUES.md](QUEUES.md)).

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
| `tests/Feature/Credits/CreditRevocationTest.php` · `CreditRevokeOriginTest.php` | 2 | **26** | `ws-402`, **not yet merged** — `CreditRevocationTest` (17): `revokeGrant()`'s unspent-only claw-back, the 422 on a fully-spent grant, unlimited-grant removal + `settleNegativeBalance()`, the 403 an ineligible `destroy()` now returns; then the expiry set added post-review — the settle command repairing a grant spent *before* it expired, not handing back credits that expired *unspent*, idempotence, `used_credits`/`remaining_credits` reported as null rather than 0 for an unallocated grant, the partial-revocation message and `data`, and a legacy refund with a null `original_source` returning 403. `CreditRevokeOriginTest` (9): `traceConsumedOrigin()`'s FIFO replay across Manual/Purchase/Revoked grants, and the `SOURCE_PURCHASE` fallback when the trace can't be pinned down |
| `tests/Feature/DiscountCodes/` | 2 | **19** | code validation + redemption, and the live-code unique index migration (`ws-392`, merged) |
| `tests/Feature/ContactFormTest.php` | 1 | **4** | contact enquiry → HubSpot upsert + ticket; optional `company_name` |
| `tests/Feature/ProfileStateValidationTest.php` | 1 | **3** | `UpdateProfileRequest` — `state_id` required only for countries that have states |
| `tests/Unit/` | 1 | 1 | Laravel's stock `ExampleTest` |
| `tests/Unit/EmailContentTest.php` | 1 | **20** | `EmailContent::linkify()` + `anchorPlaceholders()` — entity handling, attributes, `<style>` blocks, unclosed anchors, idempotence (`ws-373`, merged into `ws-404`) |
| `tests/Feature/TestInvitations/` | 3 | **36** | `ws-404`, **not yet merged to develop** — batched send + 202, after-response delivery, SMTP 421 retry vs 5xx, credit charge/refund, the recovery command, placeholder validation (typo / markup-split / space-padded), the review-fix regressions, and the `ws-373` linkify guard |
| `tests/Feature/TestInvitations/NormalizeLegacyBracketPlaceholdersMigrationTest.php` | 1 | **13** | `ws-401`, **not yet merged** — the legacy `[bracket]` → `{{token}}` repair migration: the rewrite itself, `[link]` → an anchored Start Test button, subjects rewritten *without* anchoring, and the four ways it must hold back — a token the row's `type` does not render, a row whose type has no vocabulary at all, a subject that would outgrow its column, and `<style>` block contents. Also pins that `email_template` is untouched, that a canonical row is byte-identical afterwards, and that a second `migrate` is a no-op |
| `tests/Feature/RegistrationVerificationEmailTest.php` | 1 | **15** | `ws-417`, **not yet merged** — the verification mail fires at registration and *not* at login, the 24 h window is anchored to signup and login cannot move it, expired-token resend, and the untouched login paths (verified user, super admin, wrong password, suspended) |
| `tests/Feature/EmailSubjectPrefixTest.php` | 1 | **10** | `ws-417` — subject branding across raw/`MailMessage`/DB-template sends, idempotence, casing, empty subject |
| `tests/Feature/EmailBodyHasNoBrandingHeaderTest.php` | 1 | **6** | `ws-417` — seeder and migration leave no branding header; `down()` does not re-brand blank rows; three real mail bodies verified |
| `tests/Feature/Authorization/` | 3 | **22** | `tcv-backend-codefix`, **not yet merged** — `SessionOwnershipTest` (9) and `OrganizationScopeTest` (8) cover [S-02](SECURITY.md)/[S-03](SECURITY.md)/[S-14](SECURITY.md)/[S-18](SECURITY.md): they build real SHA-256 sessions rather than stubbing the middleware, so a forged `patient_id`/`org_id` is genuinely rejected. `TestSessionPatientIdMigrationTest` (5, added 2026-09-07) covers the migration's data step — sessions with no recoverable identity are expired, invitation-backed ones are untouched, an already-expired row keeps its timestamp, and no duplicate index is left beside the foreign key. All three files were tightened on 2026-09-07: `assertNotEquals(200, …)` — which a stray 500 satisfies — was replaced with exact statuses, and `test_staff_cannot_reassign_a_patient_to_another_account` now asserts the PUT actually returned 200, so it can no longer confuse "ownership is protected" with "the route is broken" |
| `tests/Feature/RateLimitScopeTest.php` | 1 | **3** | `tcv-backend-codefix`, **not yet merged** — the limiters are keyed per account, not per (shared) IP: one account exhausting its budget must not lock out another, asserted only after confirming the first really is 429 so it cannot pass vacuously. Plus (2026-09-07) that every `throttle:<name>` a route references resolves to a registered limiter |
| `tests/Feature/Patients/PatientUpdateFieldsTest.php` | 1 | **4** | `tcv-backend-codefix`, **not yet merged** — added 2026-09-07 after `zipcode` was found silently unwritable. The structural case asserts every `PatientUpdateRequest` rule key names a real fillable attribute, so the *next* misspelling fails here rather than shipping; the rest pin that `zipcode` and `test_condition` actually persist through `PUT` and that `user_id` still cannot be reassigned |
| `tests/Feature/Credits/CreditsExpiryBoundaryTest.php` | 1 | **6** | `tcv-backend-codefix`, **not yet merged** — a credit dated "expires today" counts for the whole of that day and stops the day after, for finite and unlimited grants alike. Pins the DATE-vs-DATETIME change described in [CREDITS_CONTEXT](CONTEXT/CREDITS_CONTEXT.md) |

**93 real tests on `develop`** — **149 on `ws-404`**, **186 on `ws-417`** (which branches off the
`ws-404` line), and **245 on `ws-401`** (which has `develop` merged in and carries the `ws-404`/`ws-417`
work with it). Still untested: the test execution loop, resume, patients, payments, reports,
organisations.

☠️ **`ws-417` is the first auth coverage that has ever existed**, but it is narrow: registration,
login's verification gate, and the mail paths. Impersonation, password set/reset, the token brokers and
`verify-password` remain untested.

Counts measured with `php artisan test` on 2026-09-03. `ws-417` reports **185 passed, 1 failed** — the
failure is `DiscountCodeIndexMigrationTest > the pair rolls back and reapplies`, which **fails on a
clean tree too** (verified by stashing). It is pre-existing and unrelated to that branch; do not treat
a green-except-that-one run as a regression.

`ws-401` measured 2026-09-04: **245 passed, 741 assertions, 0 failed** in ~24 s — including that
`DiscountCodeIndexMigrationTest` case, which is green on this line.

`tcv-backend-codefix` measured 2026-09-07 (after a second review pass): **267 passed, 722 assertions,
0 failed** in ~25 s, including the 15 cases added while working through both reviews. Note this branch is a
*different line* from the `ws-401`/`ws-402` chain below — the two sets of totals are not comparable and
neither contains the other.

☠️ **Still uncovered on that branch, and "the suite passes" is not evidence for any of it:** the
session-token hashing migration, the `session_superseded` / `test_completed` middleware branches,
`organizationAllowsDownload()`'s positive path, the `/up` marker behaviour, and every
`lockForUpdate()` concurrency claim. The entrypoint bootstrap fix
([DEPLOYMENT.md](DEPLOYMENT.md) traps 3–4) is likewise verified by direct reproduction against a real
empty database, not by the suite — nothing in PHPUnit boots a container. Several assertions in the
authorization tests are `assertNotEquals(200)` rather than an exact status, so an unrelated 500 would
satisfy them.

`ws-402` measured 2026-09-07 (post-review): **265 passed, 783 assertions, 0 failed** in ~31 s. It
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

## TCV-Frontend — Jest + React Testing Library (CRA)

```bash
npm test                                   # watch mode
npm test -- --watchAll=false               # once (CI)
npm test -- --testPathPattern=src/App.test.js
```

**Seven test files exist** on `develop`, and the SPA is no longer entirely untested — **84 tests pass**
(measured 2026-09-04; `ws-400` is merged in now, so `emailPlaceholders.test.js` is part of the baseline
rather than a branch extra). On the unmerged `ws-402` it is **nine files / 104 tests** (measured
2026-09-07) — the two credits files below, plus `ws-402`'s six extra `DiscountCodeModal` cases:

| File | Tests | Covers |
|---|---|---|
| `src/components/DiscountCodeModal.test.js` | **49** | the discount drawer: keystroke limits, tier-derived bounds, type-switch reset (added `ws-356`, extended `ws-392`). **55 on the unmerged `ws-402`** (+6): tier-reachability gating and auto-drop on a Minimum Order raise |
| `src/components/richTextEditor/emailPlaceholders.test.js` | **11** | the locked email-template placeholders: bare-token healing, the nested-anchor case, `data-inner` sanitising, the round-trip fixed point (`ws-400`, merged into `develop`; [INVITATION_CONTEXT](CONTEXT/INVITATION_CONTEXT.md)) |
| `src/utils/columns/addCreditsColumns.test.js` | **11** | `ws-402`, **not yet merged** — the credit grid's Delete-visibility rules mirrored against `CreditsPolicy::delete()` (Manual yes, Purchased no, Revoked only when `original_source` is Manual, legacy null origin no), and the Utilized column's null-vs-zero rendering. The one frontend guard on the 403-on-every-legacy-row bug |
| `src/redux/slices/createpaginatedslice.test.js` | **3** | `ws-402`, **not yet merged** — `deleteItem` handing the server's response back to the caller, still dropping the row from `state.list` (now via `a.meta.arg`), and surviving an empty response body |
| `src/redux/slices/userCredits/userCreditSlice.test.js` | 9 | credit-read ordering and identity guards (`ws-397`) |
| `src/redux/slices/userProfile/passwordChangeSlice.test.js` | 6 | password-change slice (`ws-395`) |
| `src/utils/validationSchema/validatePricingTiers.test.js` | 5 | pricing-tier schema |
| `src/utils/sliderUtils.test.js` | 4 | slider helpers |
| `src/App.test.js` | 0 | the CRA "renders without crashing" stub — **fails to run**, see below |

Everything outside those files is still untested: auth, the test player, patients, reports.

`emailPlaceholders.test.js` is the guard rail named in
[CHANGE_IMPACT_GUIDE](CHANGE_IMPACT_GUIDE.md) for the template editor — `toEditorHtml`/`toTemplateHtml`
must stay a fixed point, and `hasTestLinkButton()` must keep rejecting a bare `{{verification_link}}`.
Run it before touching either. It is also the only frontend test that needs a real DOM parser rather than
just a reducer or a schema, so it is the one that breaks first if the jsdom environment changes.

☠️ **`src/App.test.js` fails to run, and it is not your change.** `react-router-dom@7` ships a
conditional `exports` map that CRA 5's bundled `jest-resolve` does not honour, so `import { BrowserRouter }`
in `App.js` dies with *"Cannot find module 'react-router-dom'"*. The suite therefore reports
**1 failed / 6 passed with 84/84 tests passing** — a failed *suite* with zero failed *tests*. Read the
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
