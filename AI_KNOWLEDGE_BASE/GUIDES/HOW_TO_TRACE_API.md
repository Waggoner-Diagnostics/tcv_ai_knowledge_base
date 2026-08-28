# How to Trace an API Endpoint

**Goal: from a URL to the code, in 2 file reads.** Do not grep the repo.

## Steps

### 1. Find it in the index — do not open `routes/api.php`
[INDEXES/API_ENDPOINT_INDEX.md](../INDEXES/API_ENDPOINT_INDEX.md) → search the URI. You get:

- the **ID** (`API-nnn`)
- **method + URI** (resource routes already expanded)
- **`Controller@method`**
- **the middleware stack** it inherits from the group it is nested in
- the **line in `routes/api.php`** where it is registered

> **Why not the route file?** Because authorisation there is expressed by a route's *physical position*
> inside one of two group blocks, and because `Route::resource` hides seven endpoints behind one line.
> The index resolves both.

### 2. Which guard is it under?

| Middleware column | Means |
|---|---|
| `auth:sanctum` | a valid 15-minute Sanctum token |
| `FlexibleAuthMiddleware` | **any of four** token kinds — Sanctum, TestSession, LmsSession, legacy org session |
| `—` | **public** — 21 endpoints are ([PUBLIC_ROUTE_AUDIT.md](../INDEXES/PUBLIC_ROUTE_AUDIT.md)) |

If you see `lms.status:…`, remember it only bites for LMS sessions
([MIDDLEWARE.md](../MIDDLEWARE.md)).

### 3. Jump straight to the method
[INDEXES/METHOD_INDEX.md](../INDEXES/METHOD_INDEX.md) → find the controller → the **exact line number**,
params and return type. Open the file at that line. Read nothing else.

### 4. Follow it into the service
Most actions are `validate → one service call → ApiResponse`. The behaviour lives in the service. Use
the method index again for the service class.

### 5. Load the matching context pack, not the module

| Endpoint area | Pack |
|---|---|
| `api/login`, `api/register`, `api/password/*`, `api/verify-*` | [AUTH_CONTEXT](../CONTEXT/AUTH_CONTEXT.md) |
| `api/tests/*`, `api/test-session/*`, `api/test-result/*` | [TEST_EXECUTION_CONTEXT](../CONTEXT/TEST_EXECUTION_CONTEXT.md) |
| `api/credits`, `api/user/credits`, `revoke-credit` | [CREDITS_CONTEXT](../CONTEXT/CREDITS_CONTEXT.md) |
| `api/payment/*`, `api/stripe/*` | [BILLING_CONTEXT](../CONTEXT/BILLING_CONTEXT.md) |
| `api/discount-codes/*` | [DISCOUNT_CONTEXT](../CONTEXT/DISCOUNT_CONTEXT.md) |
| `api/organization*/…` | [ORGANIZATION_CONTEXT](../CONTEXT/ORGANIZATION_CONTEXT.md) |
| `api/admin/lms/*`, anything with `lms.status` | [LMS_CONTEXT](../CONTEXT/LMS_CONTEXT.md) |
| `api/test-invitation*/…`, `api/test/resume` | [INVITATION_CONTEXT](../CONTEXT/INVITATION_CONTEXT.md) |
| `api/patients/*` | [PATIENT_CONTEXT](../CONTEXT/PATIENT_CONTEXT.md) |
| `api/reports/*`, `api/super-admin/dashboard` | [REPORTING_CONTEXT](../CONTEXT/REPORTING_CONTEXT.md) |

### 6. Who calls it?
[INDEXES/FRONTEND_API_CALL_INDEX.md](../INDEXES/FRONTEND_API_CALL_INDEX.md) maps the endpoint back to the
SPA file and line that calls it — that is your blast radius on the client side.

---

## Worked example — `POST api/tests/perform`

1. **Index** → `API-…` · `TestController@performTest` · middleware
   `FlexibleAuthMiddleware`, `lms.status:test_assigned` · `api.php:108`
2. **Guard** → any of four token kinds. The `lms.status` gate applies **only** to LMS sessions, so an
   invitation session reaches it unfiltered.
3. **Method index** → `TestController::performTest()` at line 223. It validates via
   `PerformTestRequest`, opens a transaction, and calls
   `TestExecutionService::submitAnswer($test_answer_id, $answer, $isAutoSubmit)`.
4. **Service** → `TestExecutionService::submitAnswer()` at line 43 — scoring, early termination, section
   completion, finalisation.
5. **Pack** → [TEST_EXECUTION_CONTEXT](../CONTEXT/TEST_EXECUTION_CONTEXT.md): tells you `submitAnswer`
   resolves the answer by **integer id alone** with no ownership check, that
   `finalizeTestIfCompleted()` is the only writer of `result_json`, and that the outer transaction
   in `performTest()` nests the inner one.

**Files read: 2** (`TestController.php` at a known line, `TestExecutionService.php` at a known line).
No grep. No route file.

## Anti-patterns

- ❌ `grep -r "tests/perform" .` — slow, and tells you nothing about the guard
- ❌ Reading `routes/api.php` to check auth — positional, and resources are invisible
- ❌ Reading the whole controller — `OrganizationController` is 876 lines
- ❌ Assuming a 500 means a crash — it is usually a 404 or 403 in disguise
  ([ERROR_HANDLING.md](../ERROR_HANDLING.md))
