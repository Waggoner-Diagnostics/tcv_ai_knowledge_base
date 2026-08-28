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
| `tests/Feature/Lms/` | 5 + 1 fixture trait | **53** | launch + signature, admin config/keys/dead-letters, delivery + retry, section progress, xAPI batching |
| `tests/Feature/Credits/` | 1 | **12** | `CreditHistoryTest` — the unified credit-history view |
| `tests/Feature/ContactFormTest.php` | 1 | **4** | contact enquiry → HubSpot upsert + ticket; optional `company_name` |
| `tests/Feature/ProfileStateValidationTest.php` | 1 | **3** | `UpdateProfileRequest` — `state_id` required only for countries that have states |
| `tests/Unit/` | 1 | 1 | Laravel's stock `ExampleTest` |

**~73 real tests, and they cover exactly four subsystems.** Everything else is untested: auth, the test
execution loop, invitations, resume, patients, payments, discounts, reports, organisations.

`tests/Feature/Lms/CreatesLmsFixtures.php` is a **trait**, not a test — it is the shared factory setup.
Reuse it for anything LMS-related.

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

**Four test files exist**, and the SPA is no longer entirely untested — **46 tests pass**:

| File | Tests | Covers |
|---|---|---|
| `src/components/DiscountCodeModal.test.js` | **35** | the discount drawer: keystroke limits, tier-derived bounds, type-switch reset (`ws-392` — not in the indexed tree) |
| `src/utils/validationSchema/validatePricingTiers.test.js` | 5 | pricing-tier schema |
| `src/utils/sliderUtils.test.js` | 4 | slider helpers |
| `src/App.test.js` | 0 | the CRA "renders without crashing" stub — **fails to run**, see below |

Everything outside those three helpers is still untested: auth, the test player, patients, reports.

☠️ **`src/App.test.js` fails to run, and it is not your change.** `react-router-dom@7` ships a
conditional `exports` map that CRA 5's bundled `jest-resolve` does not honour, so `import { BrowserRouter }`
in `App.js` dies with *"Cannot find module 'react-router-dom'"*. The suite therefore reports
**1 failed / 3 passed with 46/46 tests passing** — a failed *suite* with zero failed *tests*. Read the
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
