# Repositories

## There is no repository layer

`app/Repositories/` contains exactly **one** class:

| Class | Used by |
|---|---|
| `EmailTemplateRepository` | `EmailTemplateService` |

The other **39 models** are queried directly from controllers and services:

```php
$patientTest = PatientTest::where('unique_test_id', $uniqueTestId)
    ->with(['patient', 'test'])->lockForUpdate()->firstOrFail();
```

**Do not "follow the repository pattern" here — there isn't one.** Adding repositories to one module
creates a second competing pattern rather than consistency ([ARCHITECTURE_REALITY.md](ARCHITECTURE_REALITY.md)).

## Where query logic actually lives

| Location | Example |
|---|---|
| **Service** — the right place for anything non-trivial | `TestExecutionService::getSessionDetails()`'s aggregate + `havingRaw` |
| **Model static** | `Credits::getTotalUserCredit()` · `Credits::getAvailableCredits()` · `CreditConsume::getTotalConsumed()` |
| **Model scope** | `PatientTest::scopeInProgress/Completed/Pending()` · the `Searchable` trait's `scopeSearch()` |
| **Controller** | list endpoints (`CreditsController::index()`, `PatientController::index()`) |

For a reusable query, add a **scope** or a **model static** — both patterns already exist and are used.

## Raw SQL

Rare, and all of it is in `TestExecutionService`:

```php
->select('section_id', DB::raw('COUNT(*) as total'), DB::raw('SUM(answered) as answered'))
->havingRaw('COUNT(*) > 0 AND SUM(CASE WHEN skip_reason = ? THEN 1 ELSE 0 END) = COUNT(*)',
            [TestAnswer::SKIP_PRIOR_SECTION_PASSED])
```

That `havingRaw` is the definition of "a section was skipped". Adding a new `skip_reason` without
updating it silently mis-reports section state
([CONTEXT/TEST_EXECUTION_CONTEXT.md](CONTEXT/TEST_EXECUTION_CONTEXT.md)).

`AuditLogger::log()` uses `DB::table($table)->insert(...)` with the table name as a parameter — the only
place a table name is dynamic.

## Transactions and locking

The codebase uses these correctly where it matters; copy the shape:

```php
DB::transaction(function () use ($uniqueTestId) {
    $patientTest = PatientTest::where('unique_test_id', $uniqueTestId)
        ->lockForUpdate()->firstOrFail();
    …
});
```

☠️ Two places **do not** lock and should: the credit-balance check in `TestController::assignTest()`
(the code comments admit the race is only "reduced") and the discount `max_uses` check in
`DiscountCodeService`.

☠️ `TestController::performTest()` opens a transaction that **wraps** the one inside
`finalizeTestIfCompleted()`. Nested transactions are savepoints — see [EVENTS.md](EVENTS.md) for why
that matters.
