# Review Checklist

**Filter to the sections the PR actually touches.** Working through all of it on every PR is how
checklists die.

Each item exists because it has gone wrong *in this codebase*. The KB link explains the case.

---

## 1. Always — five questions

- [ ] **Does this add or move a route?** → §2. A route added at the top of `routes/api.php`, or after a
      group's closing `});`, is **public** with no warning.
- [ ] **Does it read or write data owned by a user?** → §3. Middleware authenticates; it never
      authorises. Nothing scopes queries for you.
- [ ] **Does it touch credits or money?** → §4.
- [ ] **Does the response shape change?** → §7. The SPA parses it and there is no API versioning.
- [ ] **Is the ticket's acceptance criteria actually met**, and nothing beyond it? Scope creep here
      tends to wander into adjacent traps.

---

## 2. Routes & guards

- [ ] Route is in the **intended guard zone** — `auth:sanctum` (default), `FlexibleAuthMiddleware`
      (patients with a session token), or none (only if it genuinely precedes any credential).
      [ROUTES.md](../ROUTES.md)
- [ ] `PUBLIC_ROUTE_AUDIT.md` diff reviewed after regenerating. The scanner flags guard removal as
      CRITICAL, but **confirm the intent** — a legitimately-public endpoint is fine, an accidental one
      is not.
- [ ] **Literal segments registered before parameterised ones** in the same prefix.
      `credits/{coupon-code}` is already dead behind the `credits` resource.
      [ROUTES.md](../ROUTES.md#ordering-traps)
- [ ] Anything that **sends mail or costs money is throttled**. Today only `POST api/contact` is —
      `test-invitations/send` (≤500 emails), `password/forgot` and the resend endpoints are not.
- [ ] `lms.status:` is not being relied on as a general precondition — it is a **no-op** when no
      `LmsSession` is attached. [MIDDLEWARE.md](../MIDDLEWARE.md)
- [ ] Route changes require a **container restart** (routes are cached at boot) — noted in the PR if it
      affects deploy.

## 3. Authorization & ownership

- [ ] **Owner-scoped queries are scoped in the query**: `->where('user_id', auth()->id())`, or from the
      session for the patient-facing tiers. This is the single most common defect here
      ([S-02](../SECURITY.md), [S-14](../SECURITY.md)).
- [ ] An id taken from the URL/body is **not** used when the session already implies it.
- [ ] New `tokenCan('…')` in a policy has its ability added to `AuthController::login()`'s super-admin
      array **in the same PR** — otherwise the policy fails for super admins and passes for customers.
      [POLICIES.md](../POLICIES.md)
- [ ] Authorisation failure returns an **explicit** `ApiResponse::error(HttpStatus::FORBIDDEN, …)`.
      Relying on `authorize()` yields a **500**, not a 403. [ERROR_HANDLING.md](../ERROR_HANDLING.md)
- [ ] Admin-only surfaces actually check the role. `api/admin/lms/*`, `api/reports/*` and
      `super-admin/dashboard` currently have **none** — don't add a sixth.

## 4. Credits & money

- [ ] `getAvailableCredits()` / `getTotalUserCredit()` results are guarded with `!== 'Unlimited'`
      **before any arithmetic**. [CREDITS_CONTEXT](../CONTEXT/CREDITS_CONTEXT.md)
- [ ] No double-charge: the `if (!$isEmailInvite)` condition in `assignTest()` is the entire guard, and
      it depends on `test_invitation_id` being merged by `FlexibleAuthMiddleware`.
- [ ] A refund credits the **owner**, not `auth()->user()` — `cancelUnregisteredInvitation()` already
      gets this wrong. [INVITATION_CONTEXT](../CONTEXT/INVITATION_CONTEXT.md)
- [ ] Balance is **derived**, never stored. No new "balance" column or cached total.
- [ ] Money stays `decimal:2`. No new float money.
- [ ] Credit/discount checks under contention are locked, or the race is acknowledged in the PR.

## 5. Test flow

- [ ] A new `TestAnswer::SKIP_*` constant updates the `havingRaw` in `getSessionDetails()` that defines
      "section skipped". [TEST_EXECUTION_CONTEXT](../CONTEXT/TEST_EXECUTION_CONTEXT.md)
- [ ] Nothing recomputes `result_json` — it is **write-once** at completion. A change to
      `ColorVisionDiagnosisService` does not and must not alter historical results.
- [ ] `SecureImageService`'s cache TTL (880) stays **below** the pre-signed URL validity (900).
- [ ] The dead JS twin (`src/utils/calculateColorVisionResult.js`) was **not** "kept in sync" — it
      should be deleted, not updated. [FULLSTACK_MAP.md](../FULLSTACK_MAP.md)
- [ ] Transaction nesting understood: `performTest()` wraps `finalizeTestIfCompleted()`'s own
      transaction, and `TestCompleted` fires from the inner one. [EVENTS.md](../EVENTS.md)

## 6. Database

- [ ] Column existence confirmed with **`DESCRIBE`**, not the index (which is a union across 109
      migrations). [HOW_TO_TRACE_DATABASE](../GUIDES/HOW_TO_TRACE_DATABASE.md)
- [ ] Migration has a real `down()`, and any `dropIfExists` is deliberate — `entrypoint.sh` runs
      `migrate --force` every boot and **continues on failure**.
- [ ] Foreign keys match the neighbours (LMS/discount tables declare them; older test tables don't), and
      an added FK won't fail on existing orphan rows.
- [ ] Soft deletes considered: only `User`, `Patient`, `DiscountCode`, `LmsProviderConfig` soft-delete,
      and a join **through** one silently drops rows.
- [ ] Table name verified against the model's `$table` — `testanswers`, `credit_consume`,
      `email_template` don't follow convention.

### 6a. Data migrations (a migration that writes rows, not schema)

The scanner has no rule for these and the tests run on SQLite, so this section is entirely manual.

- [ ] **Would the app accept what the migration writes?** A migration answers to no FormRequest. Take one
      rewritten row and walk it through the save path by hand — if a validator would 422 it, the
      migration has locked that record's editor, and an irreversible one has locked it for good. The live
      example: the placeholder vocabulary is scoped by template `type`, so a map applied to every row
      writes tokens the app rejects. [INVITATION_CONTEXT](../CONTEXT/INVITATION_CONTEXT.md#placeholder-validation-ws-404)
- [ ] **Does the rewrite make any value longer?** Then it can outgrow its column. MySQL strict mode aborts
      the migration mid-run — there is no transaction around a chunked loop, so the rows already written
      stay written — and `entrypoint.sh` serves traffic through the failure and retries the same
      half-applied migration on the next boot. **SQLite cannot fail this in tests.**
      [DATABASE.md](../DATABASE.md#migration-practice) · [TESTING.md](../TESTING.md)
- [ ] **What does it silently miss?** A literal match is case-sensitive and knows only the spellings
      someone remembered. If a missed row is invisible to every other tool afterwards, the migration must
      log it — that one boot-log line is the whole detection story. [LOGGING.md](../LOGGING.md)
- [ ] **Is `down()` honest?** An empty `down()` with a comment saying why beats one that guesses — but an
      irreversible migration raises the bar on everything above it, because there is no recovery.
- [ ] **Is it idempotent?** `migrate --force` runs every boot and a failed migration is retried forever,
      so a second pass over a row it already fixed must be a no-op. Pin it with a test that reapplies.
- [ ] The map or match is keyed on the row's own discriminator (`type`, `name`, `status`), not applied
      blanket — the `select()` usually already fetches that column.

## 7. API shape & conventions

- [ ] Uses `ApiResponse::success/error` + an `HttpStatus` constant + a key added to
      `resources/lang/en/api.php`. Eight response shapes already exist; don't add a ninth.
- [ ] Validation in a **FormRequest**, consumed with `$request->validated()` — never `$request->all()`.
      [REQUESTS.md](../REQUESTS.md)
- [ ] Business logic in a **Service**, not the controller. [SERVICES.md](../SERVICES.md)
- [ ] No new layer, no repository, no fifth mail mechanism.
      [ARCHITECTURE_REALITY.md](../ARCHITECTURE_REALITY.md)
- [ ] Listeners wired by auto-discovery or an explicit `Event::listen` — **not** by
      `EventServiceProvider`, which is not registered. [EVENTS.md](../EVENTS.md)
- [ ] A new ServiceProvider is added to `bootstrap/providers.php`, or it never runs.
- [ ] Config read via `config('…')`, never `env()` outside `config/`.
- [ ] Nothing logs a token, a password, or `$request->all()`. [LOGGING.md](../LOGGING.md)

## 8. Frontend (TCV-Frontend)

- [ ] A new page is registered in **all three** places: `protectedRoutes.js`, `routeConfig.js`
      (`parentRoutes` for each role), and `USER_PANEL_WITH_HEADER` in `Router.js` if it's a user-panel
      page. Missing the second = the page silently never renders. [FRONTEND.md](../FRONTEND.md)
- [ ] A new unauthenticated patient path is added to `isPublicRoute()` in `AxiosInstance.js` **and**
      `publicRoutes.js` — otherwise the first 401 destroys the session mid-test.
- [ ] New API calls hit endpoints that exist ([CONTRACT_DRIFT.md](../INDEXES/CONTRACT_DRIFT.md)).
- [ ] Lazy imports use `lazyWithRetry`.
- [ ] No client logic that branches on a **403** — the backend returns 500 for those.
- [ ] Paginated tables use `createPaginatedCrudSlice`.
- [ ] `eslint src --max-warnings 0` passes (one new warning fails it).

## 9. Website (TCV-Website)

- [ ] No `'use client'` anywhere under `/app` — the client half belongs in `/views/*Client.jsx`.
- [ ] `API_URL` stayed **server-only** (no `NEXT_PUBLIC_` prefix).
- [ ] Imports use the `@/` alias.
- [ ] Theme colours applied as inline styles, not Tailwind classes (runtime values don't compile).
- [ ] If a proxy route changed, its backend target still exists.

## 10. Verification & honesty

- [ ] `php -l` on changed PHP files; `composer lint` (Pint check).
- [ ] `php artisan test` run — **and the PR states honestly whether the suite covers this change.**
      Real coverage exists only for **LMS** and **credit history**; ~65 tests that never touch the
      change prove nothing. [TESTING.md](../TESTING.md)
- [ ] The actual path was exercised, not just compiled.
- [ ] Remember **CI runs no tests and does not fail on lint** — a green pipeline means "it built and
      shipped".

## 11. KB hygiene

- [ ] No documentation added inside a code repo — **all docs live in the KB**.
- [ ] If routes / models / migrations / SPA routes / API calls changed:
      `composer regenerate`, then **diff the three derived views**:
      - `INDEXES/PUBLIC_ROUTE_AUDIT.md` — did a route become public?
      - `INDEXES/CONTRACT_DRIFT.md` — did a client call lose its endpoint?
      - `INDEXES/FRONTEND_ROUTE_INDEX.md` — did a page become unreachable by every role?
- [ ] Affected prose hand-updated (the context pack, `FEATURE_INDEX.md`, `CHANGE_IMPACT_GUIDE.md`).
- [ ] If the PR **fixes** an `S-nn` finding or a documented trap, it is marked fixed **with the date**
      in [SECURITY.md](../SECURITY.md) or the pack — not deleted.
- [ ] If the PR revealed a trap the KB **missed**, it was added. That is the highest-value edit anyone
      makes here.

---

## Writing the review

State findings as: **`file:line` · what · why (KB reference) · severity**. Link the KB doc rather than
re-explaining — it lets the author check the reasoning and it keeps reviews consistent between people.

Separate **blocking** from **non-blocking** explicitly. And if you approve something that trips a
documented trap on purpose, say why in the PR — the next reviewer will otherwise flag it again.
