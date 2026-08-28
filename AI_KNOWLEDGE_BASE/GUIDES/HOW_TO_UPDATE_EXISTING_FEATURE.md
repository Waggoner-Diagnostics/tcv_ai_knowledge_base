# How to Update an Existing Feature

The difference from adding one is that you must find **everything that depends on the current
behaviour** before you change it.

## 1. Locate it — 2 reads, not a grep

[GUIDES/HOW_TO_TRACE_API.md](HOW_TO_TRACE_API.md), or start from
[FEATURE_INDEX.md](../FEATURE_INDEX.md) if you have a feature name rather than a URL.

## 2. Read the context pack for the area

It exists specifically so you don't have to read the module, and it lists the traps that are invisible
in the code. Skipping this step is how the recurring defects get reintroduced.

## 3. Check the blast radius

[CHANGE_IMPACT_GUIDE.md](../CHANGE_IMPACT_GUIDE.md) — find the row for what you're touching. Pay
particular attention if it involves:

| Symbol / file | Why |
|---|---|
| `Credits::getAvailableCredits()` | returns `int` **or** the string `'Unlimited'` |
| the `if (!$isEmailInvite)` guard | the whole double-charge guard |
| `unique_test_id` | joins three subsystems with no FK |
| `ApiResponse` / `Handler` | shapes and statuses the SPA parses |
| `login()`'s ability array | must match every `tokenCan()` in the policies |
| `FlexibleAuthMiddleware` | 23 endpoints × 4 token tiers |
| any `TestAnswer::SKIP_*` | the `havingRaw` that defines "section skipped" |

## 4. Find the client-side callers

```
INDEXES/FRONTEND_API_CALL_INDEX.md   → which SPA file:line calls this endpoint
INDEXES/CONTRACT_DRIFT.md            → calls that already have no matching route
```

If you rename or remove an endpoint, it will show up in `CONTRACT_DRIFT.md` on the next regeneration.
Better to find it now.

## 5. Make a tight, idiomatic diff

- Match the surrounding code. If the file uses inline `$request->validate()`, adding a FormRequest for
  one method makes it *less* consistent — either convert the file or follow it.
- Do **not** introduce a new layer, a new response envelope, or a fifth mail mechanism.
- Do **not** "fix" the things this KB documents as deliberate: the 880 < 900 cache/URL ordering, the
  write-once `result_json`, the literal-before-parameterised route ordering in the `discount-codes`
  block.

## 6. Behaviour that is *not* retroactive

| Thing | Why past records don't change |
|---|---|
| `ColorVisionDiagnosisService` | `result_json` is written once at completion and never recomputed |
| Credit balance rules | balance is derived from historical grant/spend rows |
| Discount `max_uses` | counted from `transaction_details` history |
| LMS delivery payload | already-delivered rows are `delivered` and are not re-sent |

Changing any of these changes **new** behaviour only. If the ask is to correct historical data, that is
a migration or a backfill script, and — for `result_json` — a clinical-records decision, not a refactor.

## 7. Verify

```bash
php -l <changed files>
php artisan test --filter=<RelevantTest>     # LMS + credit history are the only real suites
composer lint
```

Then exercise the actual path. For the test flow that means assigning a test and answering plates; for
LMS it means running `tests/Feature/Lms`.

**Report honestly.** If the suite does not cover your change, say that instead of "tests pass".

## 8. Update the KB

```bash
php tools/extract.php && php tools/extract-clients.php && php tools/render.php && php tools/verify.php
```

Diff the three derived views (`PUBLIC_ROUTE_AUDIT`, `CONTRACT_DRIFT`, `FRONTEND_ROUTE_INDEX`), then
hand-update:

- the **context pack** for the area (the behavioural change and any trap you removed or added),
- [FEATURE_INDEX.md](../FEATURE_INDEX.md) if entry points changed,
- [CHANGE_IMPACT_GUIDE.md](../CHANGE_IMPACT_GUIDE.md) if you touched a shared symbol,
- [SECURITY.md](../SECURITY.md) if you fixed or introduced an `S-nn` finding — **mark it fixed with the
  date rather than deleting it**, so the history stays readable.

**Never regenerate the whole KB.**

---

## Checklist

- [ ] Located via the index, not a grep
- [ ] Context pack read
- [ ] `CHANGE_IMPACT_GUIDE.md` row checked
- [ ] SPA callers identified via `FRONTEND_API_CALL_INDEX.md`
- [ ] Diff matches surrounding style; no new layer
- [ ] Retroactivity considered
- [ ] Verified on the real path; coverage stated honestly
- [ ] KB regenerated + affected prose updated
