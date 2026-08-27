# Context: Test Execution (the colour-vision test itself)

> Load this **instead of** reading the test subsystem. ~2k tokens. This is the product's core loop.

## Files
| File | Role |
|---|---|
| `app/Services/TestExecutionService.php` | ⭐ **Answer submission, section completion, finalisation** |
| `app/Services/TestAssignmentService.php` | Creates `PatientTest` + the full `TestAnswer` plate set |
| `app/Services/TestSectionTerminationService.php` | Early-exit rule ("last N all wrong") |
| `app/Services/TestSectionProgressionService.php` | Conditional section skipping |
| `app/Services/TestResultService.php` | Builds the stored result snapshot |
| `app/Services/ColorVisionDiagnosisService.php` (559 lines) | ⭐ The diagnosis algorithm |
| `app/Services/SecureImageService.php` | S3 pre-signed plate URLs |
| `app/Http/Controllers/TestController.php` (679 lines) | Thin HTTP layer over the above |
| SPA: `src/pages/UserPannel/TestPage/TestPage.js`, `src/constants/testConfig.js` | The player |

## Tables
`tests` · `test_sections` · `test_section_plates` · `test_conditions` · `patient_tests` ·
`test_answers` · `patients`

---

## The object model

```
Test ──< TestSection ──< TestSectionPlate          ← the template (authored by super admin)
                │
PatientTest ────┴──< TestAnswer                    ← the instance (one row per plate, per attempt)
   unique_test_id (UUIDv4)  ·  parent_test_id (UUIDv4, only for both-eyes)
```

**`TestAnswer` rows are created up-front** by `TestAssignmentService`, one per plate, all with
`answered = 0`. Progress is therefore "how many `test_answers` rows have `answered = 1`", never a
counter. That is why almost every progress query is a `COUNT`/`SUM` over `test_answers`.

### `patient_tests.status`
`pending` → `inprogress` → `completed`, plus `abandoned` (set by
[credit revocation](CREDITS_CONTEXT.md)). Constants on `PatientTest`.

### Monocular ("both eyes") tests
Two `PatientTest` rows share one `parent_test_id`, with `eye_tested` of `OS` / `OD`. Completing one
flips its still-`pending` partner to `inprogress`. **`OS` is always the canonical result** —
`resolveCanonicalTestId()` maps an `OD` id to its `OS` partner. A binocular test has
`parent_test_id = null` and is its own canonical id.

---

## The answer loop

```
POST api/tests/perform  { test_answer_id, answer, is_auto_submit }
  → TestExecutionService::submitAnswer()
      1. correct = !is_null(answer) && (int) correct_answer === (int) answer
      2. update test_answers: patient_answer, answered=1, correct, skip_reason
      3. SecureImageService::revokeAccess()          ← cache only; see trap 2
      4. terminationService->shouldTerminateSection()  → maybe terminateSection()
      5. section complete? (no answered=0 rows left in this section)
           → progressionService->evaluateAndSkipConditionedSections()
           → event(TestSectionCompleted)
      6. finalizeTestIfCompleted()
```

### `finalizeTestIfCompleted()` — the only place a test becomes `completed`
Runs inside `DB::transaction` with `lockForUpdate()` on the `PatientTest`:
- returns immediately if already `completed` (idempotent),
- requires **zero** `answered = 0` rows across the whole test,
- writes `status`, `ip_address`, then `result_json` + `result_generated_at` **once**
  (`if (!$patientTest->result_json)`),
- marks the `TestInvitation` used,
- promotes the paired eye,
- fires `TestCompleted` **after the transaction commits** — which is what drives LMS delivery
  ([LMS_CONTEXT](LMS_CONTEXT.md)).

**`result_json` is a snapshot, written once and never recomputed.** Changing
`ColorVisionDiagnosisService` does **not** change any already-completed test's result. That is
deliberate (results are clinical records) — remember it before "fixing" a historical result.

---

## `skip_reason` — three distinct meanings

| Constant | Value | Set when |
|---|---|---|
| `TestAnswer::SKIP_TIMEOUT` | `'timeout'` | auto-submit fired with a null answer |
| `TestAnswer::SKIP_SECTION_TERMINATED` | `'section_terminated'` | early-exit closed the section |
| `TestAnswer::SKIP_PRIOR_SECTION_PASSED` | `'prior_section_passed'` | a `test_conditions` rule skipped the section |

A section counts as **skipped** (not completed) only when *every* non-demo plate in it carries
`SKIP_PRIOR_SECTION_PASSED` — that exact `havingRaw` is in `getSessionDetails()`. Adding a fourth skip
reason without updating that query silently mis-reports section state.

## Early termination
`shouldTerminateSection()` looks at the last *N* **non-demo answered** plates and terminates when their
`SUM(correct) = 0` (all wrong). `terminateSection()` then marks every remaining plate answered with
`SKIP_SECTION_TERMINATED`, which is why the completion check right after it is accurate either way.

## Conditional progression
`test_conditions` rows are `(cond_section, cond_status, cond_section_next)` — "if section X finishes with
status S, jump to section N". Evaluated **before** finalisation so that finalisation sees the skips.

---

## Plate delivery (S3)

```
GET api/test-session/{uuid}/section/{id}/plates   → metadata for unanswered plates
                                                    + a pre-signed URL for the FIRST plate only
GET api/test-session/{uuid}/plate/{answerId}/url  → a pre-signed URL, on demand, per plate
```

`SecureImageService::getSecurePlateUrl()`:
- caches the URL under `plate_url:{uniqueTestId}:{testAnswerId}` for **880 s**,
- validates the test is `inprogress` **and** the plate is unanswered before signing,
- signs for **900 s**.

The 880/900 split is deliberate: the cache must expire before the URL does, never after.

---

## ☠️ Traps

1. **No ownership check anywhere in this flow.** `unique_test_id` and `test_answer_id` come from the
   request and are used unchecked — `submitAnswer()` resolves an answer by **integer id alone**.
   The UUID's unguessability is the only barrier. [S-02](../SECURITY.md#s-02--test-session-endpoints-never-check-that-the-caller-owns-the-test).
2. **`revokeAccess()` revokes nothing.** It clears the cache entry; the already-issued S3 URL stays live
   for its full 900 s. [S-11](../SECURITY.md#s-11--revokeaccess-does-not-revoke-s3-access).
3. **`lms.status:test_assigned` on these routes is a no-op for non-LMS sessions.** The middleware
   returns early when no `LmsSession` is attached — invitation and resume flows pass through ungated.
   See [MIDDLEWARE.md](../MIDDLEWARE.md).
4. **The diagnosis algorithm exists twice.** `ColorVisionDiagnosisService.php` (559 lines) is a port of
   `TCV-Frontend/src/utils/calculateColorVisionResult.js` (349 lines) — its own docblock says so. The JS
   copy is **exported but imported nowhere**, i.e. dead. Change the **PHP** one; do not "sync" the JS
   copy, delete it. See [FULLSTACK_MAP.md](../FULLSTACK_MAP.md).
5. **`result_json` is written once.** No recompute path exists. Fixing the algorithm does not fix
   historical results, and adding a recompute is a clinical-records decision, not a refactor.
6. **`getSessionDetails()` returns a *different shape* when the test is completed** — a short object with
   `sections: []`. A client that assumes `progress` exists will break on completed tests.
7. **`performTest` wraps the service in its own `DB::beginTransaction`**, and
   `finalizeTestIfCompleted()` opens a nested one. Nested transactions are savepoints, so a rollback in
   the outer one discards the inner commit — but `TestCompleted` has *already fired* outside it. An
   outer failure after finalisation can therefore deliver an LMS completion for a rolled-back test.
