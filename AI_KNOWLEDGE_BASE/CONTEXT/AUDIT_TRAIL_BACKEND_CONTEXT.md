# Audit Trail — backend plan outline

**Repo:** `TCV-Backend` — ✅ **shipped to `develop` 2026-09-09** (PR #227, merge `940238fd`).
**Frontend contract:** ✅ also merged — `feat/ui-audit-trail` landed on `TCV-Frontend@develop` as PR #375 (`6b6c5ae`), specified in [AUDIT_TRAIL_FRONTEND_CONTEXT.md](./AUDIT_TRAIL_FRONTEND_CONTEXT.md). Phases 1–3 were built against fixtures; Phase 4 (swap three function bodies for axios calls) and the login/logout instrumentation described in SERVICES.md are both done and merged.

> ✅ **§13's branch has since merged to `develop`.** `feat/audit-trail-improvement-11-sep-26` landed as
> PR #238 (merge `c3449270`, confirmed an ancestor of `origin/develop` as of 2026-09-14) — the
> title/description rework and the 3 new suspended-status events described in §13 are now `develop`'s
> actual state. §13's own "not yet on develop" framing is stale; the content (titles, new keys, catalog
> counts) is otherwise accurate.
>
> ✅ **§14 has since MERGED to `develop`** (commit `4570912b` and its predecessors, confirmed
> 2026-09-15 via `git merge-base --is-ancestor`). Its six User-Panel defect fixes — the discount
> breakdown on `billing.payment_succeeded`, the `test.invitation_sent_bulk` removal, the hard-coded
> `Credits Used`, the orphaned distributor-enquiry key, raw `state_id`/`country_id` in update diffs,
> and the pricing rows on a manual credit grant — are now `develop` behaviour, not pending work.
>
> ✅ **Everything else from that branch has merged too** (verified 2026-09-17 with
> `git merge-base --is-ancestor` against `develop` `ff9be500`): §15's impersonation attribution
> (`0256fe7e`), `3e42305d` (date-only logging), `4c92b5e2` (`auth.account_locked`) and the review fix
> `c6734fb3` — all in PR #245 (2026-09-15); the last three are written up as §16. **The catalog is 68 events** on `develop`
> (`AuditEventCatalogTest` asserts 68; `sign_ins_security` 13). §14's per-step counts were accurate when
> written; 68 is the current total. The KB was regenerated from `develop` on 2026-09-17, so
> `audit_logs`' five `impersonator_*` columns and `AuditService::impersonatorFor()` are in the indexes.

> ⚠️ **This document was written as a *plan*, while the work was still on a branch.** It is kept
> because the reasoning is worth having, but read it as design intent that has now shipped — where it
> says "will" or names a branch, check the code on `develop` before trusting the tense.
**Scenario source:** `TCV Audit trail - Phase1.xlsx` — 46 rows on the Super Admin sheet, 27 on the User Panel sheet.
**Supersedes:** the draft `implementation_plan.md` (a port of another repo's pattern). Kept where it was right; corrected where it conflicts with what the frontend actually sends. See §9.

---

## 1. Verified baseline

| Fact | Evidence |
|---|---|
| No generic `audit_logs` table | only `2026_01_27_112600_create_pricing_audit_logs_table.php` |
| `AuditLogger::log()` is a `DB::table()->insert()` shim | `app/Services/Audit/AuditLogger.php` |
| `PricingAuditService` is its only caller | `app/Services/Audit/PricingAuditService.php` |
| Role constants: `SUPER_ADMIN=1`, `CUSTOMER=2`, `ORGANIZATION=4` (no 3) | `app/Models/User.php:24-26`, `isSuperAdmin()` at `:115` |
| `Searchable` is a scope taking an **explicit field list**, not auto-configured | `app/Traits/Searchable.php` |
| `ApiResponse::success(int, string, $data)` → `{success,status_code,message,data}` | `app/Helpers/ApiResponse.php` |
| Zone 3 = `Route::middleware('auth:sanctum')->group(...)` | `routes/api.php:118` |
| Middleware registration is Laravel 12 `bootstrap/app.php` style | `bootstrap/app.php:20-29` |
| ~~Trusted-proxy config exists but is inert~~ — 📌 **stale since 2026-09-14 (`ws-449`)**: `trustProxies()` is now always called, defaulting to every private range. See B1 | `bootstrap/app.php` · see B1 below |
| Nginx forwards `X-Forwarded-For` | `TCV-Frontend/nginx.conf:42,54,63` — and since `ws-449`, `TCV-Backend/nginx.conf` to php-fpm |
| ~~No account-lockout mechanism anywhere~~ — 📌 **stale**: the `login` limiter (5/min per `email\|ip`) is the lockout, and since PR #245 it logs `auth.account_locked` (§16) | `AppServiceProvider::accountLockedResponse()` |
| No geo-IP or user-agent parsing dependency | `composer.json` require block |

Two of these are blocking and are dealt with in §6.

---

## 2. What the frontend actually sends and expects

Read off the branch, not off the plan doc — these are the binding facts.

**List — `GET /api/audit-logs`**
`page, limit, search, sortBy, sortOrder, status, from, to, actor_id, target_id, category, sensitivity, admin_only`
(`src/utils/auditFilters.js:80-91`; `src/apis/fetchAuditLogsPaginated.js:96-101`)

- `sortBy` ∈ `audit_id | category | created_at | status` (`fetchAuditLogsPaginated.js:56-61`) — **not `id`**.
- `admin_only` is sent as the integer `1`, never `0`.
- `from`/`to` are `YYYY-MM-DD`, resolved in the **viewer's** timezone; the page always sends a default last-30-days window.
- The response is unwrapped by the existing `parsePaginatedResponse` at its paginator branch, `counts` included. No new parser.

**Row shape** — `id, audit_id (string), category, event_title, event_description, actor, target, created_at, status, sensitivity, is_admin_action`.
`actor`/`target` are `{id, name, email, role, company_name}` and both nullable.

**Detail — `GET /api/audit-logs/{id}`** adds `ip_address` on each person plus four optional sections: `details[] {label,value}` (ordered; `value` may be string | number | boolean | string[]), `changes[] {field,before,after}`, `session {location{city,state,country,timezone}, device{type,browser,os}}`, `related_activity[] {id,event_title,status,created_at}`. Each section is dropped entirely when absent (`AuditDetailDrawer.js:235-250`).

**People — `GET /api/audit-logs/people`** → flat `[{id,name,email,role,company_name}]`.

**Export — `GET /api/audit-logs/export`** — ✅ **shipped to `develop` 2026-09-18** (PR #261, merge `07cf5b6f`). The endpoint, its FormRequest, the CSV streamer, and the frontend blob-download wiring all exist — see **§17**. The old note here ("the button exists but has no `onClick`") is superseded; the button now calls `exportAuditLogs()` which does exactly the `axiosInstance.get(..., { responseType: 'blob', skipErrorPopup: true })` predicted here.

### Three frontend constants that constrain the backend

1. **`role` must be `admin | user | organization | system`** — `isAdminPerson()` tests `role === 'admin'` to colour the name `#074C8C` (`constants/auditTrail.js:37-51`). So `usertype 1 → 'admin'` (**not** `'super_admin'`), `2 → 'user'`, `4 → 'organization'`, no actor → `'system'`.
2. **`SENSITIVITY_LEVELS` is `low | medium | high | critical`** (`constants/auditTrail.js:66-71`) — `critical` was added as part of this work. The backend enum must carry exactly these four values: they are passed through as the `sensitivity` query param verbatim. See §4.
3. **`AUDIT_CATEGORIES` now has 8 keys**, derived from the scenario sheet rather than inherited from the reference repo. See §3.

---

## 3. Categories

> [!NOTE]
> **Applied.** `AUDIT_CATEGORIES` in `src/constants/auditTrail.js` now carries the eight keys below, and `AuditTrail.scss` carries a matching tone for each. The backend `category` column must use exactly these keys.

The seven original keys came from the reference repo. Rederiving them from the scenario sheet gives eight — and one structural simplification worth stating, because it is what keeps the count down:

**Users, Super Admins and Organizations collapse into a single category.** The sheet lists them as three modules with seven identical rows each (registered, edited, impersonated, credits added, credits revoked, status changed, deleted). They differ only by *who the target is* — a dimension the frontend already filters separately via `target_id`, `admin_only`, and `role` on each person. Three categories would re-express an existing filter and triple the dropdown for nothing.

| Category key | Label | Covers (from the spreadsheet) | ~Rows |
|---|---|---|---|
| `sign_ins_security` | Sign-ins & Security | login ok/failed, lockout, logout, forgot/reset password, password changed, email verification, restricted IPs | 14 |
| `accounts_users` | Accounts & Users | create / edit / status / delete for **users, super admins and organisations**; self-signup; profile updates; **impersonation start/end** | 18 |
| `billing_payment` | Billing & Payments | pricing tiers, discount codes (CRUD/toggle/expiry), checkout discount apply, payment success/failure | 13 |
| `credits_licensing` | Credits & Licensing | credit assign / revoke / refund, insufficient-credit blocks | 8 |
| `test_activity` | Test Activity | invitations sent / bulk / resent / cancelled; test started / completed | 6 |
| `patient_records` | Patient Records | patient added / edited / deleted / exported, Patients-page access | 5 |
| `reports_exports` | Reports & Exports | report exports, report filtering, patient-test detail views | 4 |
| `settings_config` | Settings & Configuration | application settings, email templates, test catalogue status | 3 |

Retired: `broadcast_management`, `version_management`, `devices` — no spreadsheet rows. `license_management` became `credits_licensing`; nothing here is a software licence.

**Placements worth defending:**

- **Impersonation sits in `accounts_users`.** It is arguably a session-establishment event and could live under Security, but operators searching for it reach for the account it affected — so it goes where they will look.
- **Credits are split from Payments.** A super admin granting 100 credits and a user paying $500 are different concerns with different actors; the sheet's "Credits / Purchase" module merges them and the audit trail should not.
- **Pricing and discount *config* stay with Payments.** "Revenue looks wrong this month" wants pricing changes, discount codes and transactions in one filter.
- **Patients-page access sits in `patient_records`, not Security.** This is the category that answers "show me everything that touched patient data", which is the whole point of the §5 work.

**Chip tones:** seven of the eight reuse an existing hex pair unchanged; only Settings & Configuration needed a new one — a slate (`#DCE0E6` / `#3A4757`) rather than a red, since red reads as severity and would collide with the Failed status badge two columns away.

### Contact Us — dropped

The sheet's one Contact-Us row does not fit any category, and the code says why: `ContactController::submit()` posts straight to HubSpot and **persists nothing locally**. HubSpot is the system of record, so a TCV audit row would be the only local copy of the free-text enquiry — exactly the content §5 says not to store.

Recommendation: audit the **failure**, not the content. A failed HubSpot submission currently produces only a `Log::error` (`app/Http/Controllers/ContactController.php:44`) and the lead is silently lost; that is worth one `settings_config` row. The enquiry body is not.

---

## 4. Sensitivity

> [!NOTE]
> **Resolved — `critical` added to the frontend.** `SENSITIVITY_LEVELS` (`src/constants/auditTrail.js:66-71`) now carries four levels ordered by ascending severity. `sensitivity` is only ever a dropdown option and an active-filter chip label — it is never rendered as a coloured badge — so no stylesheet change was needed, contrary to the earlier note here. Four fixture rows were promoted to `critical` so the new filter option is demonstrable before Phase 4.

Applying your bands:

**Critical** — deletions (user / super admin / organisation / discount code / credit revoke / invitation cancel), pricing tier updates and their failures, all payment events (success and failure), discount-code create/update failures, impersonation start and end, restricted-IP changes, account lockout, **failed logins**.

**High** — registration and self-signup, discount code create/edit/toggle, organisation create/edit, credit assignment, status toggles (active/inactive), password changed, password reset *completed*.

**Medium** — test sent / resent / bulk, test started / completed, patient added / edited, all exports, test catalogue status change, settings and email-template edits, profile edits, Patients-page verification.

**Low** — successful sign-in, sign-out, email verification, OTP and verification-code issuance, password reset *requested*, Contact-Us submissions, report filtering.

One call still made against a literal reading of your list:

- **"Discount code apply failed (invalid)" → `medium`, not `critical`.** It is overwhelmingly a user typo at checkout.

Flips on request.

---

## 5. HIPAA / privacy review — rows that must not be logged as specified

The audit table is a **new store with a new access model**: readable in full by every Super Admin, exportable to CSV, retained indefinitely. Copying patient data into it re-exposes that data to a broader audience than the module it came from. Nine rows in the spreadsheet do exactly that.

| Sheet row | Specified detail | Verdict | Log instead |
|---|---|---|---|
| **Test completed** | "Result / Diagnosis" | **Do not log.** A colour-vision diagnosis is clinical health data — the most sensitive item in either sheet, and it serves no audit purpose. | Test name, `unique_test_id`, completion timestamp. Nothing about the outcome. |
| **New Patient added** | First/Last Name, Email, **DOB**, Patient ID, **Zip**, **Gender** | **Do not log.** Name + DOB + zip is three HIPAA identifiers in one row; the record is fully re-identifying with no de-identification applied. | `patient_ref` (= `patients.id`) and the owning user/org only. **Not `patient_id`** — see the surrogate-key note below. |
| **Test started for patient** | "Patient details (Name, Email, DOB, Patient ID, Gender)" | Same. | `patient_ref`, `unique_test_id`, test name, credits used. |
| **Patient details are Edited/updated** | before/after of patient fields | **Worst case** — stores *two* copies of the PHI, including values the user has since corrected or deleted. | The *names of the fields* that changed. No values. |
| **Patient test details viewed** | Patient name, Test ID, Test Name, Status | Name is an identifier; "Status" here is test progress, not clinical, so it is fine. | `patient_ref`, `unique_test_id`, Test Name, progress Status. Drop the name. |
| **Test abandoned** *(dropped — see below)* | Patient name, last section reached | Name is an identifier. | `unique_test_id`, test name, last section. |
| **Test invitation sent** | Recipient email(s), **Verification code** | The verification code is a **live credential** granting entry to a test session. Logging it is a security defect independent of HIPAA — anyone with audit read access could take a patient's test. | Recipient **count**, test name, credits used, expiry. Never the code, never the addresses. If a specific recipient must be traceable, store `test_invitations.id` — that row already has a primary key, so there is nothing to hash. |
| **Contact / enquiry submitted** | Name, Email, Subject, **Message** | Free text from the public web form — it can contain anything, including the sender describing their own medical condition. | Name, email, subject, message length. Not the body. |
| **Payment successful / failed** | "Payment Method" | Only ever brand + last four, straight from the Stripe object. Never a PAN, never a raw token. | Brand + last4, transaction ID, amount. |

> [!NOTE]
> **`test.abandoned` dropped as its own key in implementation (PR #229).** There is no *patient-initiated* abandonment signal to hang a dedicated event off — a patient closing the tab produces no request. The catalog and seeder carry no `test.abandoned` key; `test_activity` lists only `test.started` and `test.completed`. The admin-initiated case (`CreditsController::revokeCredit`, which requires the test to be `STATUS_INPROGRESS` and marks it `STATUS_ABANDONED`) is folded into `test.invitation_cancelled_credit_refund` — the same key `TestInvitationController::cancelUnregisteredInvitation` uses for a pre-start cancellation, so one key now covers both "invitation cancelled before the patient started" and "in-progress test force-abandoned via credit revoke."

**Surrogate keys — log a pointer, not a copy.**

HIPAA does not prohibit PHI in audit logs; §164.312(b) *requires* audit controls, and a covered entity's trail routinely contains PHI. What makes *this* trail different is its blast radius: every Super Admin sees it across every organisation, it is CSV-exportable, and it is retained immutably and indefinitely. The governing rule is therefore **minimum necessary** (§164.502(b)), and the way to satisfy it structurally is to store a reference the reader can resolve only if they already hold rights to the underlying record.

Two further reasons this beats copying the values:

- **Erasure works.** `patients` is soft-deleted today. If a record is ever genuinely purged, an audit table holding copied PHI would have to be scrubbed alongside it — which directly conflicts with audit immutability. A pointer simply dangles: the PHI disappears, the event survives.
- **No second source of truth.** A copied DOB goes stale the moment someone corrects a typo.

| To reference | Do **not** use | Use |
|---|---|---|
| a patient | `patient_id` | `patients.id`, exposed in `details` as `patient_ref` |
| a test | patient name + test name | `unique_test_id` (system UUID, `create_patient_tests_table.php:16`) |
| an invitation recipient | the email address, or a hash of it | `test_invitations.id` |

> [!CAUTION]
> **`patient_id` is not a safe surrogate.** It is user-entered free text — `'patient_id' => 'nullable|string|max:255'` (`app/Http/Requests/PatientAddRequest.php:26`). Practices will put MRNs, chart numbers, or worse in it. Only the autoincrement `patients.id` is system-generated and meaningless outside the database.

For an **edit**, log the *names* of the changed fields and no values: `"changed_fields": ["dob","email"]`. `"Changed dob, email on patient #4821"` answers the audit question without the trail becoming a second PHI store.

**Two design consequences:**

- **Patients are never an `actor` and never a `target`.** For "Test completed" (and "Test abandoned" in the original spreadsheet, dropped — see §5) the spreadsheet names the Patient as the actor; the frontend role vocabulary has no `patient`, and `Patient` is not a `User`, so an `actor_id` would be a dangling reference. Log these with `actor = system` and the owning user/organisation as `target`, with the patient referenced only by `patient_ref` inside `details`.
- **A denylist belongs in the service, not in the call sites.** `AuditService` masks recursively before any write: the reference plan's `password / token / secret / api_key` set **plus** `dob, date_of_birth, gender, zipcode, zip_code, diagnosis, result, verification_code, message, ssn, card_number`. Call sites will drift; one chokepoint will not. Give call sites a `ref()` helper so a patient reference cannot be hand-rolled, and add a test asserting that no `test_activity` or `accounts_users` row's `details`/`changes` JSON ever matches an email- or date-shaped pattern — it catches the regression the day someone adds a new call site.

---

## 6. Two blockers found in the current backend

**B1 — `$request->ip()` returned the reverse proxy, not the user.** ☠️ **Changed shape on `develop` 2026-09-14 (`ws-449`) — now the opposite problem.** `trustProxies()` is always called with a private-range default and the backend nginx forwards `X-Forwarded-For`, so `$request->ip()` is no longer the proxy. But the edge (`TCV-Website/nginx.conf`) and `TCV-Frontend/nginx.conf` both still `set_real_ip_from 0.0.0.0/0`, so by the traced chain **`audit_logs.ip_address` and the geo `location` are whatever the client puts in `X-Forwarded-For`** — worse for an audit trail than the uniformly wrong datacentre IP it replaced. Not yet reproduced on a live stack; how to check on QA is in [SECURITY.md S-16](../SECURITY.md#status-2026-09-17--both-backend-halves-shipped-the-frontend-nginx-precondition-did-not). The fix is in those two nginx files, not here.

📌 The earlier notes here — "setting `TRUSTED_PROXIES` would make it worse", "you cannot set it accidentally, it is not in the compose allowlist", and "use `X-Real-IP`, the one header a client cannot forge" — are all **superseded**: the default now does what setting it would have done, the variable is in the allowlist, and the backend nginx overwrites `X-Real-IP` with the frontend container's address.

**B2 — "Account locked (too many failed attempts)".** ✅ **Now logged** (PR #245, 2026-09-15) as `auth.account_locked` (`sign_ins_security`, `critical`). The original objection — no lockout exists — was overtaken by the `login` rate limiter (5/min per `email|ip`), and the event hooks that limiter's rejection. See §16. "Failed attempt count" on every failed login is still not implemented.

**Three more rows that are not implementable as written:**

- **"Pricing changes discarded"** — the user abandons an unsaved form. No request reaches the backend. Either the frontend POSTs a client-event, or drop it. Recommend dropping; an unsaved edit is not an event.
- **"Discount Code is Expired (auto)"** — requires a scheduled job, and `docs/12-deployment.md` records that **no environment runs a worker or scheduler**. Derive expiry at read time instead of logging it.
- **"Report filtered (date range)"** — one row per filter interaction on a read-only screen. This will out-volume every other category combined and push real events off the first page. Recommend dropping, or logging exports only.

---

## 7. Architecture — and where this departs from `implementation_plan.md`

The draft plan's centrepiece is a whitelist middleware that logs post-response by route prefix. **That approach cannot satisfy this spreadsheet.** Almost every row demands a before/after diff, an entity-specific detail list, or a failure *reason* — none of which a middleware can see from an HTTP envelope. Following it would produce ~40 rows of "User update failed" with an empty drawer behind each one.

**Primary mechanism: explicit instrumentation in the services and controllers**, where the before-state, the after-state, and the failure reason are all in scope. The middleware is demoted to a **safety net** — it records that a whitelisted mutating route was hit and returned 4xx/5xx with no explicit call site claiming it, so a failure path can never be silently unaudited.

**Failure rows need an exception hook.** Most `status: failed` events in this spreadsheet — "Pricing update failed", "Discount Code create failed (validation)" — originate as a `ValidationException` thrown by a FormRequest *before* the controller body runs. Neither an explicit call site nor a post-response middleware sees the reason. Register a `renderable`/`reportable` hook on the already-bound `App\Exceptions\Handler` that emits one audit row for whitelisted routes on 422/403/5xx, carrying the validation message as the failure reason. The draft plan misses this entirely.

### Components

| # | File | Notes |
|---|---|---|
| 1 | `database/migrations/*_create_audit_logs_table.php` | Broadly as drafted. `sensitivity` enum is `low, medium, high, critical` — all four, matching the frontend. `actor_id`/`target_id` stay unconstrained `unsignedBigInteger` (a `system` actor has none). No `updated_at`. Composite indexes on `(category, created_at)`, `(status, created_at)`, `(actor_id, created_at)`, `(sensitivity, created_at)`. |
| 2 | `app/Models/AuditLog.php` | `$timestamps = false`. Casts for `details`/`changes`/`session_context`. `Searchable` used as `->search($term, ['event_title','event_description','actor_name','actor_email','target_name','target_email'])` — the trait takes an explicit field list; it does not self-configure. |
| 3 | `app/Services/Audit/AuditService.php` | Single write chokepoint. Auto-enriches actor, IP, country, browser, session id. Masks the denylist recursively. Whole body in `try/catch(\Throwable)` → `Log::error` + return null; **an audit failure must never fail the user's request.** |
| 4 | `app/Services/Audit/AuditEventCatalog.php` | **New, not in the draft.** One place mapping each spreadsheet row to `{category, event_title, sensitivity}`. Keeps 70+ magic strings out of 20 controllers and makes the spreadsheet diffable against the code. |
| 5 | `app/Http/Middleware/AuditFallbackMiddleware.php` | Safety net only (see above). Registered via `$middleware->appendToGroup('api', …)`. Always skips `api/audit-logs*` and `api/stripe/*`. |
| 6 | `app/Exceptions/Handler.php` | **Modify** — emit failure rows for whitelisted routes. |
| 7 | `app/Http/Controllers/AuditLogController.php` + `AuditLogIndexRequest` | Super-Admin gate via the existing `User::isSuperAdmin()`. Four methods. |
| 8 | `routes/api.php` | Four routes in Zone 3, **literals before `/{id}`**. |
| 9 | `resources/lang/en/api.php` | New message keys. |
| 10 | ~15 controllers/services | The instrumentation call sites (§8). The bulk of the work. |
| 11 | `app/Console/Commands/PruneAuditLogs.php` | **New.** Retention window; without one this table grows unbounded and the `people`/`counts` queries degrade. Given the no-scheduler constraint, expose it as an artisan command runnable by hand or by cron. |
| 12 | `database/seeders/AuditLogSeeder.php` | Dev only. Must cover: null actor, null target, all four sensitivities, both statuses, every category, and a >30-day spread so the date presets visibly differ. |

### Enrichment: country + browser

Your rule — location = country, device = browser, and **neither on system-generated events** (Stripe webhooks, OTP mails, auto-expiry). The frontend's `session` shape has more slots than that, but `formatLocation`/`formatDevice` (`AuditDetailSections.js:174-185`) drop missing parts cleanly, so sending `{country}` and `{browser}` alone renders correctly with **no frontend change**.

- **Country: MaxMind GeoLite2-Country via `geoip2/geoip2`, not `ip-api.com`.** The draft's choice has three problems: a per-request outbound HTTP call on the write path; a free tier licensed for non-commercial use only; and — most importantly — it **transmits every user's IP address to a third party**, which is a privacy regression inside a privacy feature. A local `.mmdb` is a ~9 MB file, one dependency, no network, no rate limit, no data leaving the host.
- **Browser:** a ~30-line parser in `AuditService` returning the browser family. `jenssegers/agent` is abandoned, and a full parser is disproportionate when the requirement is one string.

### Session correlation for `related_activity`

The draft's `substr(sha256(bearerToken()), 0, 24)` works for Sanctum but not for the other three identity tiers `FlexibleAuthMiddleware` resolves. Derive the session key from whichever tier resolved, and **capture it before `logout()` revokes the token**.

---

## 8. Instrumentation call sites

Grouped by the file that changes. Every method listed was confirmed to exist on `develop`.

| File | Methods | Events |
|---|---|---|
| `AuthController` | `login`, `logout`, `register`, `impersonateUser`, `stopImpersonation`, `sendResetLinkEmail`, `setOrResetPassword`, `verifyEmail`/`verifyEmailByToken`, `changePassword`, `verifyPassword` | sign-in ok/failed, sign-out, registration, impersonation start/end, reset requested/completed, email verified, password changed, Patients-page verification |
| `UserController` | `store`, `update`, `destroy` | user + super-admin create / edit (before-after) / delete; status toggle |
| `OrganizationController` | `store`, `update`, `destroy` | organisation create / edit / delete / status |
| `CreditsController` | `store`, `destroy`, `revokeCredit`, `checkDiscountCodeValidity` | credits assigned, revoked, refunded; discount applied/failed at checkout |
| `DiscountCodeController` | `store`, `update`, `destroy`, `toggle` | discount create / edit / delete / toggle (+ failures via the Handler hook) |
| `PriceDetailController` | `store`, `update`, `destroy` | pricing tier changes (+ failures) |
| `PaymentController`, `StripePaymentController` | webhook + confirm handlers | payment success / failure — **system-generated: no location or device** |
| `TestInvitationController` | `sendInvitations`, `resendUnregisteredInvitation`, `cancelUnregisteredInvitation` | invitation sent / bulk / resent / cancelled+refunded, insufficient credits |
| `PatientController` | `store`, `update`, `destroy` | patient added / edited / deleted — **redacted per §5** |
| `TestController` | `assignTest` | test started |
| `TestExecutionService` | `finalizeTestIfCompleted` | test completed — **no result or diagnosis** |
| `ReportController` | `userTestsReport`, `discountCode`, `getPatientsHavingTests` | exports |
| `RestrictedIpController` | all mutations | IP allowlist changes |
| `TestEmailTemplateController`, `UserEmailTemplateController` | `update` | email template edits |
| `ProfileController` | `update` | profile edits |
| `TestController` | status mutation | test catalogue status change |
| `ContactController` | `submit` | enquiry — **subject only, no body** |

---

## 9. Corrections to `implementation_plan.md`

Beyond the architectural point in §7, four concrete defects — each would have shipped a visible bug:

1. **`resolveRole()` returns `'super_admin'`.** The frontend tests `role === 'admin'` (`constants/auditTrail.js:37`). Super-admin names would render black instead of `#074C8C`, and `AUDIT_ROLE_LABELS` would print an empty sub-line. Must be `'admin'`.
2. **`sortBy` whitelist is `['id','created_at','status','category']`.** The frontend sends `audit_id` (`fetchAuditLogsPaginated.js:56`). Clicking the Audit ID header would silently fall back to `created_at`.
3. **`counts` are computed after `status` is applied.** The frontend's own comment is explicit that counts must reflect search and filters but **not** the tab, so tab numbers stay stable while switching tabs (`fetchAuditLogsPaginated.js:110-116`). Clone the query *before* the status filter.
4. **Scopes are invoked as `$query->scopeStatus(...)`.** Eloquent scopes are called without the prefix: `$query->status(...)`.

Also: `GET /api/audit-logs/people` should be built from `DISTINCT actor_id/target_id` **on `audit_logs`**, not from the users table as drafted. The users table exposes the name and email of every customer on the platform to a filter dropdown, most of whom have no audit rows; the distinct version is smaller, faster, and only reveals people who actually appear in the trail.

Answers to the draft's four open questions:

- **Q1** — GeoLite2, per §7.
- **Q2** — `is_admin_action = (actor is SUPER_ADMIN)`. It drives the `admin_only` filter alongside the `role === 'admin'` colouring; widening it to CUSTOMER would make the filter meaningless.
- **Q3** — leave `pricing_audit_logs` in place and write pricing events to *both* for now; migrate later. It is referenced by existing screens.
- **Q4** — accept empty `details`/`changes` only on the middleware safety-net rows. Every explicitly instrumented site must populate them, which is the whole reason §7 inverts the draft's priority.

---

## 10. One more frontend gap

`from`/`to` are computed in the **viewer's** timezone (`auditFilters.js:38-41`) but will be compared against UTC `created_at`. A user in IST filtering "Today" gets a window shifted by 5½ hours. Either accept a timezone-offset parameter, or convert the day bounds to UTC in the controller using a known application timezone — and decide which before writing the query.

---

## 11. Suggested sequencing

1. **Prerequisites** — TrustProxies (B1); resolve the `critical` decision (§4); confirm the cut list (§6).
2. **Foundation** — migration, model, `AuditService`, `AuditEventCatalog`, enrichment, masking denylist. Unit-test masking and role resolution.
3. **Read API** — controller, FormRequest, routes, lang keys, seeder. Verify against the fixture shape field by field, then swap the three frontend fixture blocks. **The page is demonstrable at the end of this step.**
4. **Instrumentation, in risk order** — auth and impersonation → accounts and organisations → billing, pricing, credits → invitations and test activity (redacted) → settings, exports, contact.
5. **Failure paths** — Handler hook, then the middleware safety net.
6. **Export + retention** — CSV endpoint, the frontend blob download, prune command.

---

## 12. Open decisions

**Resolved:** the eight categories in §3; `critical` added to the frontend enum; failed logins classified `critical` (§4). All three are applied in the frontend on `feat/ui-audit-trail`.

1. Cut "Account locked", "Failed attempt count", "Pricing changes discarded", "Discount code auto-expiry", "Report filtered"? *(recommend: cut all five from Phase 1)*
2. Confirm the §5 redactions — particularly that **test results and diagnoses are never logged**, and that verification codes are never logged.
3. Retention window for `PruneAuditLogs` — 1 year? 7 years? This is a compliance answer, not an engineering one.

---

## 13. ✅ MERGED — title/description review pass (`feat/audit-trail-improvement-11-sep-26`)

**Status: ✅ on `develop`** (PR #238, merge `c3449270`; the "not on develop" wording below is historical). This branch is a post-ship review pass against
`changes to audit log for super admin role.xlsx` review comments, documented in
`docs/plans/audit-trail-backend-changes.md` in the `waggoner-tcv` root (not this KB). Recorded here so a
future KB regeneration against `develop` — once this branch merges — knows what changed and why, and so
nobody re-derives it from scratch in the meantime.

**New mechanism — `event_title` is no longer *always* fixed per key.** `AuditEventCatalog`'s design
invariant ("event_title is fixed per event key") still holds for most of the catalog, but
`AuditService::log()` now takes an optional trailing `?string $eventTitleOverride = null` — when a caller
passes one, it replaces the catalog title for that single row. Fully backward compatible (defaults null,
every pre-existing call site unaffected). Two new `AuditEventCatalog` static helpers build the override
string: `statusChangeTitle(string $eventKey, string $afterStatus)` and `invitationSentTitle(int
$recipientCount)`.

**Titles/descriptions changed to be dynamic (single actual value, never "(X/Y)" literally), title and
description always built from the same resolved local variable so they can't disagree:**

| Event key | Old title | New behaviour |
|---|---|---|
| `account.user_status_changed` | `User Status changed (Active/Inactive)` | `User Status changed (Active)` **or** `(Inactive)`, resolved from the actual after-value. Description: `"Account status changed to {Active\|Inactive}."` |
| `account.super_admin_status_changed` | same pattern | same pattern |
| `account.organization_status_changed` | same pattern | same pattern. Description: `"Organization account status changed to {Active\|Inactive}."` |
| `settings.test_status_changed` | `Test Status changed (Active/Inactive)` | same pattern. `Test::status` accessor is boolean-backed (`active`/`inactive` only) — no suspended concept here, deliberately left untouched below |
| `test.invitation_sent` | `Test invitation sent (single / multiple)` | `Test invitation sent (Single)` **or** `(Multiple)`, from the actual recipient count at `TestInvitationController::sendInvitations()`. Description pluralizes too: `"Test invitation sent."` vs `"Test invitations sent."` |
| `billing.discount_code_create_failed` | `Discount Code create failed (validation)` | reverted (static) to `Discount code creation failed` |
| `auth.restricted_ip_added` / `_removed` / `_updated` | `Restricted IP address (Added\|Removed\|Updated)` | parentheses dropped (static): `Restricted IP address Added` / `Removed` / `Updated` |

**Three new dedicated events — `suspended` is a real third `account_status` value** (`UserRequest.php`
validates `required|in:active,inactive,suspended`), and product decided it should NOT share the
Active/Inactive dynamic title. When the after-value is `suspended`, `UserController::update()`'s
status-only branch and `OrganizationController`'s user-status branch now route to a dedicated static-title
key instead of `statusChangeTitle()`:

| Event key | Title | Category | Sensitivity |
|---|---|---|---|
| `account.user_suspended` | `User account suspended` | `accounts_users` | `high` |
| `account.super_admin_suspended` | `Super Admin account suspended` | `accounts_users` | `high` |
| `account.organization_suspended` | `Organization account suspended` | `accounts_users` | `high` |

**Catalog size:** 64 → **67** events on this branch; `accounts_users` category 16 → **19**. (`develop`'s
current figure is the F-082 row in `FEATURE_INDEX.md` — cross-check that against the real catalog before
trusting either number; it was already observed stale relative to the shipped 64-event state before this
branch even started.)

**Two behavioural fixes, not just title text:**

- **`UserController::update()`** — the "Assigned Tests" audit entry moved from a static current-state
  snapshot under `details` to a real before/after diff appended to the `changes` ("what changed") array,
  emitted only when the set actually differs. (This branch of `update()` doesn't itself mutate the pivot
  today, so the diff is presently always a no-op — mechanism is correct if that ever changes.)
- **`TestController::assignUserTest()` / `unassignUserTest()` / `bulkUpdateAssignment()`** — previously
  logged **nothing** (confirmed gap: only a plain `Log::info()` in `bulkUpdateAssignment`, no
  `AuditService::log()` call at all in any of the three). Now all three log via a new private
  `logAssignedTestsChange()` helper, event key `account.profile_updated`, with a before/after "Assigned
  Tests" diff under `changes` — only when the set actually changed (re-assigning an already-assigned test,
  or a net-zero bulk call, logs nothing). `bulkUpdateAssignment`'s `user_id` param lets a Super Admin act on
  another user's assignments (gated by `TestPolicy::viewTests()`); actor/target are correctly distinct in
  that case, reusing `account.profile_updated` rather than a new key — the plan doc's own event list
  doesn't anticipate a separate one for this.

Reviewed (`tcv-reviewer`, scanner clean, no blocking findings) — two non-blocking notes: `invitationSentTitle()`'s "Multiple" branch is presently unreachable given how the call site is gated (bulk sends use a different key with no override), and the new `suspended` path has no equivalent gap since it got its own dedicated key. `composer test`: 715 passed / 2 failed, both pre-existing and unrelated (confirmed via `git stash` — a HubSpot-mock test and a pre-flagged quoted-printable assertion).

---

## 14. ✅ MERGED — User-Panel defect pass (`feat/audit-trail-user-panel-improvement-14-sep`, `4570912b`)

**Status: ✅ on `develop`** (PRs #242 on 2026-09-14 and #243 on 2026-09-15). Branched off `c3449270` (§13's merge commit). Fixes 3 of 4 issues
reported against the User Panel category of the live catalog; the 4th was evaluated for feasibility
only. Planned in `C:\Users\User\.claude\plans\there-are-some-changes-golden-volcano.md`.

**1. `billing.payment_succeeded` now carries the discount breakdown.** Previously only
`billing.checkout_discount_applied` (a separate, earlier row logged in
`PaymentController::confirmPayment()`) showed Code/Type/Amount for a discounted purchase — the
success row itself only had Amount/Payment Method/Transaction ID/Credits Purchased, so an auditor
had to cross-reference two rows to see a discount was involved.
`PaymentController.php` now forwards `discount_type` (already computed at checkout, previously
never passed downstream) into what `StripeProvider::confirmPayment()` receives; that method's
`billing.payment_succeeded` call now appends Discount Code / Discount Type / Discount Amount,
guarded by the same `isset($paymentData['discount_id'])` check already used for
`transaction_details`, so non-discounted purchases are unaffected. **Only the `/api/payment/confirm`
→ `StripeProvider` path was covered** — the legacy `/api/stripe/confirm-payment` /
`confirm-ach-payment` endpoints (`StripePaymentController`) never accepted a discount in their
validation at all and were left as-is; flagged, not fixed, since nothing there needs a discount
field if no discount can reach it.

**2. `test.invitation_sent_bulk` removed — the "CSV (bulk)" title was fiction.** There is no
CSV-upload feature anywhere on the backend; recipients are always a submitted list. The catalog
nonetheless had a second, fully separate key with a static `'Test invitation sent via CSV (bulk)'`
title, and `TestInvitationController::sendInvitations()` routed every `$createdCount > 1` send to
it — which is exactly why §13's own review flagged `invitationSentTitle()`'s "(Multiple)" branch as
unreachable dead code. This pass **is** that fix: the bulk key is gone, the 3-way `$eventKey`
selection collapsed to 2 (`test.invitation_resent` / `test.invitation_sent`), and
`invitationSentTitle()` is now actually reachable for counts > 1, producing
`"Test invitation sent (Multiple)"` (capitalized, consistent with the rest of the catalog's dynamic
titles — a lowercase `"(multiple)"` was considered and rejected for consistency).
**Catalog size: 67 → 66; `test_activity` 6 → 5.** (Item 4 below then adds one key back, so the
branch ships 67 overall — `AuditEventCatalogTest::test_total_event_count_matches_the_post_cut_spreadsheet_tally`
asserts 67, and the `AuditLogSeeder` docblock still says 66, which is stale.)

**3. `test.started`'s `Credits Used` was a hard-coded literal, not a ledger read.**
`TestController::assignTest()` logged `$isEmailInvite ? 0 : 1` — for a patient-invited test this
always showed **0**, even though 1 credit genuinely was consumed, just earlier (at invitation-send
time, `CreditConsume::consume(..., CreditConsume::EVENT_TEST_INVITATION, ...)`) and on a different
audit row (`test.invitation_sent`). Fixed as a **read-only lookup at the existing audit call site
only** — no change to `TestAssignmentService`, credit deduction, or any control flow — checking
`CreditConsume` for a row whose `ref_id` contains the invitation id. Self-service tests are
untouched (`1`, unchanged, was never the bug). **When no matching `credit_consume` row exists for
an invited test** (a genuine gap — refund, migration, etc.), **the `Credits Used` entry is omitted
from `details` entirely**, not sent as `value: null` — the frontend's `DetailValue`
(`AuditDetailSections.js:76-79`) renders a `null` as the literal string `"NA"`, which reads as "a
number was expected and is missing" rather than "not applicable here"; omitting the key avoids that
false impression.

**4. `settings.distributor_enquiry_submission_failed` was an orphaned key.**
`DistributorController::submit()`'s catch block had always logged this key, but it was never added
to `AuditEventCatalog::EVENTS` — so `AuditEventCatalog::get()` threw on every failed submission and
`AuditService::log()`'s catch-all swallowed the throw. **No audit row was ever written for a lost
distributor enquiry.** Adding the catalog entry (`settings_config`, `low`) is the whole fix.
**Catalog size: 66 → 67; `settings_config` 4 → 5.**

**5. `state_id` / `country_id` reached the drawer as raw foreign keys.**
The reported symptom was "State and City show up as IDs, especially under *User details edited*".
Only half of that is real, and the half that is real is worse than reported:

- **`state_id` / `country_id` — a genuine defect, in three places.** `users.state_id` and
  `users.country_id` are foreign keys. Every **create**-path detail block resolved them by hand
  (`UserController::accountCreateDetails()`, `OrganizationController`'s account details,
  `AuthController`'s registration event all do `optional(State::find(...))->name`), but all three
  **update**-path diffs — `UserController::update()`, `OrganizationController::update()`'s user
  branch, and `ProfileController::update()` — passed the columns straight through
  `BuildsAuditDiffs`, shipping `"State Id": 3963 -> 3971`. The frontend cannot rescue this:
  `AuditDetailSections.js`'s `DetailValue` renders any non-boolean, non-array value as
  `String(value)`, and `formatFieldLabel` only title-cases the field name.
- **`city` — not a defect.** `users.city` is `string(100)`, the only city column in the schema, and
  every form that writes it (`NewUserModal`, `OrganisationModal`, `Register`, both profile pages)
  is a free-text input with a digit-blocking key handler. It was already logging the literal name.
  The reported "City" is almost certainly the adjacent `State Id`/`Country Id` rows in the same
  address block. A regression assertion pins city as an unresolved string so it stays that way.

Fixed **inside `BuildsAuditDiffs` rather than at the three call sites**, because per-call-site
opt-in is exactly what produced the bug — the create paths remembered, the update paths didn't. A
new `AUDIT_RELATION_FIELDS` map rewrites *both* halves of the entry (`state_id => 3963` becomes
`State => 'Illinois'`), and `auditSnapshot()`/`auditChanges()`/`auditDetails()` all consult it with
no caller changes at all. Deliberately **uncached**: a lookup only fires for a field that actually
changed, so it costs at most four queries on an address edit, and a static memo would hand back a
stale name for an id reused across `RefreshDatabase` cases. A null `state_id` (stateless country)
stays null so the drawer still renders `NA`, and a deleted lookup row resolves to null rather than
leaking the number back out. **Patient records are the one place an id legitimately reads as an id,
and no patient field is audited through this trait — so there was nothing to exempt.**

**6. `credits.assigned_to_*` logged pricing that never applied.**
`CreditsController::store()` is a super admin granting credits by hand; `CreditsAddRequest` lets
`price_per_credit` / `total_price` / `coupon_code` sit at their `0`/`null` defaults and nothing is
ever charged. Logging **Price Per Credit / Total Price / Coupon Code** only invited the reader to
think money changed hands. All three rows removed; `Type` / `Credits` / `Added Date` / `Expiry
Date` are untouched. The revoke path (`destroy()`) never carried pricing and needed no change, and
`PriceDetailController`'s own `Price Per Credit` row is a genuine price-settings event and stays.
Real purchases remain audited by `StripeProvider` under `billing.payment_succeeded` with the
amounts actually charged (item 1 above).

**7. Test abandonment — evaluated, not implemented this pass.** No code exists today
(`PatientTest::STATUS_ABANDONED` is only ever set by an admin credit-revoke action, never by
automatic staleness detection; no `test.abandoned` catalog key; no `started_at`/timeout column; no
in-app scheduler in any environment). Recommended design for a later pass, matching the existing
`SendPendingInvitations`/`SettleNegativeCreditBalances` precedent: a new `test.abandoned` catalog key
(`test_activity`, `medium`, redacted per §5 — `unique_test_id`/test name/last section, never the
patient's name) plus a new artisan command run by an ops-provisioned external cron (no in-app
scheduler runs anywhere, so this is the only option that produces a real timestamped audit row
rather than a read-time-only computed badge). Staleness threshold is an open product decision,
deliberately not settled.

**Verification:** `php artisan test` — **739 passed, 1 failed**, that one being
`InvitationSendReviewFixesTest:261` (a quoted-printable `<a href=` assertion), confirmed to fail
identically on `develop` via `git checkout develop` — pre-existing and unrelated. `vendor/bin/pint
--test` fails repo-wide on `line_ending` (CRLF checkout) both here and on `develop`; this branch
introduces no new fixer.

New/updated coverage: `AuditEventCatalogTest` (catalog counts), `AuditLogSeederTest`
(scenario/catalog parity), `AuditedInvitationSendingTest` (renamed multi-recipient case),
`AuditedPaymentConfirmationTest` (new discount-fields case + an explicit absent-when-no-discount
assertion), `AuditedTestLifecycleTest` (two new cases: credits-used-as-1 via a real
`CreditConsume` row, and the row omitted when none exists), and for items 5-6:
`AuditedProfileControllerTest::test_changing_state_logs_the_name_not_the_id` (which also pins
`city` as an unresolved literal), `AuditedUserControllerTest::test_changing_state_and_country_logs_
names_not_ids` (covers a null "before" from a stateless country),
`AuditedOrganizationControllerTest::test_changing_the_owners_state_logs_the_name_not_the_id`, and
`AuditedCreditsControllerTest::test_assigning_credits_does_not_log_pricing_fields`.

**Not covered — worth knowing.** `UpdateProfileRequest` makes `state_id` *required* whenever the
selected country has any states, so "clear the state on a country that has one" is unreachable
through the API and has no test; the null path is covered instead by the UserController case,
which moves off a stateless country.

**KB regeneration note:** ✅ reconciled 2026-09-17 — the indexes were regenerated from `develop` with
§13–§16 all merged.

## 15. ✅ MERGED — impersonation attribution (`feat/audit-trail-user-panel-improvement-14-sep`)

Backend commit `0256fe7e` (PR #245, on `develop` 2026-09-15); frontend PRs #384/#386 on
`TCV-Frontend@develop` — rendering described in
[AUDIT_TRAIL_FRONTEND_CONTEXT](./AUDIT_TRAIL_FRONTEND_CONTEXT.md#post-ship-changes-on-develop-2026-09-15-prs-384--386).
**First schema change to `audit_logs` since the table was created.**

**The defect.** `AuthController::impersonateUser()` mints the impersonation token **on the target
user**, carrying one ability `impersonated-by:{adminId}`, and the SPA sends it as a plain bearer
token (`AxiosInstance.js:43`). So for the whole impersonated session `$request->user()` **is the
impersonated user** — and all ~75 audit call sites pass exactly that as `$actor` (48 of them
literally `$request->user()`/`Auth::user()`/`auth()->user()`). The audit trail was faithfully
recording what the auth layer told it. Two consequences:

1. **"Who Did It" named the impersonated user alone** — the reported symptom.
2. **`is_admin_action` was `false` on every impersonated row.** It was `$actor?->isSuperAdmin()`,
   and the actor is the customer. Since `User::canImpersonateUser()` returns true only for a Super
   Admin, *every* impersonated action is really an admin action — and all of them were invisible to
   the admin-only filter, the one filter whose entire job is surfacing exactly that. This is the
   compliance-relevant half and was **not** in the original report.

**Shape: record both identities, don't swap.** `actor_*` keeps its existing meaning (the account the
action ran as), so nothing that filters, groups or lists on `actor_id` changes behaviour; five
`impersonator_*` columns are added alongside, denormalized like actor/target so the row survives the
admin being edited or deleted. A straight swap was rejected — it would drop impersonated rows out of
"everything that happened on this user's account", and make an admin's own action
indistinguishable from one taken through someone else.

**Detection is central, in `AuditService::log()`.** 75 call sites is untenable, and a new call site
can forget. `impersonatorFor()` reads the current request's Sanctum token abilities for the
`impersonated-by:` marker — now the shared `AuditService::IMPERSONATION_ABILITY_PREFIX` constant,
because drift between the writer in `AuthController` and this reader produces silently wrong rows
rather than an error. Two guards matter:

- **`$request->user()->id === $actor->id`** keeps `account.impersonation_ended` correct: it
  deliberately passes the *impersonator* as actor while the request runs on the impersonated user's
  token, so the ids differ and it is left alone rather than described as impersonating itself.
  `impersonation_started` needs no special case — it runs on the admin's own token, which carries no
  such ability.
- **`$token instanceof PersonalAccessToken`** — session/cookie auth resolves to a Sanctum
  `TransientToken` with no abilities array at all. This is also why the tests mint real tokens with
  `createToken(..., ['impersonated-by:N'])` instead of `Sanctum::actingAs()`.

**`ip_address` moved cards, not columns.** The stored IP is the address the request arrived from,
which on an impersonated row is the *admin's* browser. `AuditLog::toDetailArray()` hangs it off the
impersonator card (`personArray()` now accepts either prefix); the column is unchanged.

**People filter / dropdown.** An admin who only ever acts through impersonation appears as neither
`actor_id` nor `target_id`, so `people()` unions the impersonator side in — and `index()`'s
`actor_id` filter matches `actor_id OR impersonator_id`, so that entry is not a dead option.
Additive: filtering by the impersonated account still returns its rows.

**Backfill (`2026_09_15_000002`).** History reconstructs *exactly*, because the correlation was
already in the data: `impersonateUser()` logs its row with `sessionKeyForToken($newToken)`, and every
later request on that same token derives the identical `session_key` — so one impersonation's rows
all share a `session_key` with the row that opened it, whose actor is the admin. Re-impersonating
mints a new token and therefore a new key, so sessions cannot bleed into each other. The two
impersonation events are excluded **on `actor_id`, not `event_title`**, so a later catalog rename
cannot silently re-include them. `down()` clears only the columns it wrote and deliberately leaves
`is_admin_action`, which cannot be distinguished from a value that was already true and is
re-derived on every write.

**⚠️ Two frontend surfaces, not one.** `PersonCard` in the detail drawer
(`AuditTrail/AuditDetailSections.js`) and `PersonCell` in the **table** column definitions
(`utils/columns/auditTrailColumns.js`) are independent components rendering the same person shape.
The columns file lives OUTSIDE `pages/AuditTrail/`, which is how it was missed on the first pass —
the drawer was fixed while the list column kept showing the impersonated user's name and email.
`toListArray()` already carried `impersonator`, so the table fix was frontend-only. **Anything else
that renders an audit person must be checked against both.**

**Which events this can touch.** Impersonation is Super-Admin-only and the token carries exactly one
ability, which bounds the list. Events that can gain an impersonator are anything reachable on an
authenticated session: `patient.added/updated/deleted`, `test.started`, `test.completed`, the
invitation events, the billing events, `account.profile_updated`, `auth.password_changed`/`_failed`,
`auth.logout`, `report.patient_test_detail_viewed`, `report.user_tests_exported`,
`settings.email_template_updated`. Unaffected: every unauthenticated event (`auth.login_*`,
`account.self_signup`, `auth.password_reset_*`, `auth.email_verification_completed`,
`settings.contact_*`) and the two impersonation events themselves.

**Impact on non-impersonated rows: none.** `actor_*`, `is_admin_action` and `ip_address` placement
are all unchanged (`impersonator_id` is null → every branch falls back to the old behaviour); the
backfill's statements are both scoped to rows with an `impersonator_id`; and detection
short-circuits before any query when the marker is absent, so there are **no extra DB queries** on a
normal write. The `actor_id` filter is additive — no row is ever lost. One shape change: every row
now carries an `impersonator` key, `null` on ordinary rows.

**Accepted consequence.** The admin-only filter returns more rows than before — that is the point of
the fix, confirmed as intended with the product owner, but it does change what an existing saved
view shows.

**⚠️ Route-group trap, found in review.** Every impersonation test initially drove `/api/profile`,
which sits in the `auth:sanctum` group. `patients` sits under `FlexibleAuthMiddleware` instead, where
nothing installs Sanctum's user resolver explicitly — attribution there rests on the default guard
(`api`, whose driver is `sanctum`) resolving `$request->user()`. It does work, and now has its own
test, but adding a patient while impersonating is THE flagship impersonation flow (see
`OrganizationController`'s own note about `AddPatient.js`), so a silent failure there would be the
first thing QA hits. Any future change to guards or middleware ordering must re-check this.

**Pre-existing limitations surfaced by this work (not introduced, not fixed here):**

- **Impersonating another Super Admin gives a degraded session.** `OrgPolicy` and `TestPolicy` gate
  on `tokenCan('update-organization')` etc., and the impersonation token holds none of those
  abilities, so those endpoints 403 even though the impersonated usertype is Super Admin. Net effect:
  super-admin-only events cannot be produced through impersonation at all — which usefully bounds
  both the event list above and any QA plan.
- **`UserController::update()` has no `authorize()` call**, and `Route::apiResource('users')` sits in
  the plain `auth:sanctum` group. Any authenticated user can call it. Worth its own ticket.

**Verification:** `php artisan test` — **761 passed, 1 failed** on the branch, that one being the
`InvitationSendReviewFixesTest:261` quoted-printable assertion. (📌 After the merge, the full suite on
`develop` `ff9be500` is **812 passed, 0 failed** — measured 2026-09-17, so that assertion no longer fails.)
Frontend `AuditDetailSections.test.js` + `auditTrailColumns.test.js` — 27 passed; `eslint` clean.
New coverage: 6 cases in `AuditedImpersonationTest` (both identities, admin-action flag, IP on the
impersonator card, no impersonator on a normal action, neither impersonation event self-marked, and
the `FlexibleAuthMiddleware` route group), 2 in `AuditLogAccessTest` (people dropdown, actor filter),
6 in the new `ImpersonatorBackfillTest` (attribution, out-of-session rows untouched, no
self-impersonation, no cross-session bleed, idempotence, `down()`), 3 `PersonCard` cases and a new
`auditTrailColumns.test.js` (5 cases) on the frontend.

**Stash/rebase hazard, for the record.** This work was stashed and later restored while both branches
moved underneath it. `git stash pop` left `AuditDetailSections.test.js` in an unmerged conflict state
(import line only — upstream had added `formatMaybeDate`/`moment`). Nothing was lost, but a
`git status` showing `UU` after a pop is easy to miss and the file carries conflict markers until
resolved.

## 16. ✅ MERGED 2026-09-15 — lockout audit and diff fidelity

Three commits that shipped in PR #245 alongside §15, plus `986cb29b` from PR #243. None changes the
schema; all change what an auditor reads. Catalog total after this section: **68**.

### `auth.account_locked` (`4c92b5e2`, fixed by `c6734fb3`)

`sign_ins_security` · `critical` · title `Account locked`. Written when the **`login` rate limiter**
(5/min, keyed `callerKey(email)` = `email|ip`) rejects a request — which happens in `ThrottleRequests`
**before** `AuthController::login()` runs, so no `auth.login_failed` call site can see it. The hook is
`AppServiceProvider::accountLockedResponse()`, registered as the `login` limiter's `->response()`
callback. It returns the same `429 {"success": false, "message": "Too many requests. Please try again
later."}` the other six limiters return.

- **Target** is `User::withTrashed()->where('email', …)->first()` — a soft-deleted account still gets
  attributed, and an unknown email gives a null target. **No actor**, so `ip_address` is null on the row;
  the IP is in `details` (`Reason`, `IP address`).
- **One row per lockout, not per rejected request.** `ThrottleRequests` calls the callback on *every* 429.
  Dedup is `Cache::add("audit:login-lockout:{$key}", true, $ttl)` with
  `$ttl = RateLimiter::availableIn(md5('login'.$key))` — the limiter's own remaining window, read from the
  exact key `ThrottleRequests::handleRequestUsingNamedLimiter()` uses. ☠️ The first version used a
  hard-coded 60s TTL anchored to whichever rejection triggered it; under sustained retries the two clocks
  drifted and a stale dedup key swallowed the *next* lockout's row (`c6734fb3`). If `availableIn()` is 0
  the dedup is skipped rather than caching for 0s (which is a no-op on some stores and "forever" on others).
- ⚠️ **Only as good as the limiter key.** Since `ws-449` the `ip` half can be forged
  ([SECURITY.md S-16](../SECURITY.md#status-2026-09-17--both-backend-halves-shipped-the-frontend-nginx-precondition-did-not)),
  so an attacker rotating `X-Forwarded-For` never trips the limiter and never produces this row.
- Tests: `RateLimitScopeTest::test_a_lockout_writes_exactly_one_account_locked_audit_row` and
  `…_two_separate_lockout_cycles_each_log_their_own_account_locked_audit_row` (the regression for the TTL
  drift).

### Dates in diffs are date-only — except when that would hide the change (`3e42305d`, `c6734fb3`)

`BuildsAuditDiffs::normalizeAuditDate()` truncates a Carbon instance, or a string shaped
`YYYY-MM-DD[T ]HH:MM…`, to `Y-m-d` in `auditChanges()` and `auditDetails()` — the drawer shows dates, and a
midnight time next to a date-only value invited readers to think the time mattered. `3e42305d` also
touched hand-built date rows in `AuthController` and `CreditsController` [not deeply traced].

☠️ **The truncation collapsed real changes.** `getChanges()` lists only fields that genuinely changed, so a
`DiscountCode.starts_at` moved from 08:00 to 17:00 on the same day logged `2026-06-09 → 2026-06-09`.
`c6734fb3` fixed it in two places, and both are load-bearing:

- `auditSnapshot()` now stores the **raw** model value (no normalisation) so the "before" keeps its time;
- `auditChanges()` checks: if both sides are date-shaped and equal *after* truncation, emit the full
  `Y-m-d H:i:s` (`fullAuditDateValue()`) instead.

So a What Changed row normally arrives `Y-m-d`, and arrives with a time **only** when the time is the
change. The SPA's `formatMaybeDate()` renders the two shapes differently — see
[AUDIT_TRAIL_FRONTEND_CONTEXT](./AUDIT_TRAIL_FRONTEND_CONTEXT.md#post-ship-changes-on-develop-2026-09-15-prs-384--386).
Test: `AuditedDiscountCodeControllerTest` (same-day, time-only `starts_at` change).

### Boolean-flavoured fields are coerced explicitly (`986cb29b`)

Whether a column's PHP value is a real `bool` depends on whether its model happens to `$casts` it, and the
SPA's `DetailValue` prints a real boolean as Yes/No but anything else as raw text (`1`, `"0"`). Callers now
name their boolean fields and all three helpers take a trailing `array $booleanFields = []`; coercion
happens once, in `auditChanges()`/`auditDetails()` (`castAuditValue()`), and a null "before" stays null so
it still renders `NA`.

| Caller | Constant | Fields |
|---|---|---|
| `UserController::update()` | `AUDIT_BOOLEAN_FIELDS` | `show_occupational_questions`, `allow_monocular_test` |
| `OrganizationController::update()` (user branch) | `USER_AUDIT_BOOLEAN_FIELDS` | same two |
| `OrganizationController::update()` (org branch) | `ORG_AUDIT_BOOLEAN_FIELDS` | `show_tcv_branding`, `anonymize_patient`, `show_gender`, `show_zip`, `show_patient_id`, `send_test_email_to_patients`, `run_test_on_subdomain`, `authorized_redirect` |

⚠️ Adding a boolean column to an audited field list without adding it to the matching constant brings the
raw `1`/`0` back. `auditSnapshot()` accepts the parameter only for signature symmetry; it ignores it.

### Foreign keys are named, and a few small fixes (`4570912b`, `87bd0b24`, `9934a1d6`)

Already written up as §14 items 1–6: `AUDIT_RELATION_FIELDS` (`state_id`/`country_id` → names, applied in
every helper), the discount breakdown on `billing.payment_succeeded`, the real `Credits Used` lookup on
`test.started`, and the orphaned `settings.distributor_enquiry_submission_failed` key.

**Suite on `develop` `ff9be500` after all of the above: 812 passed, 0 failed** (2433 assertions, measured
2026-09-17).

## 17. ✅ MERGED — CSV export + local-timezone date filtering (`feat/audit-trail-export-18-sep`)

> ✅ **On `develop` 2026-09-18** (PR #261, merge `07cf5b6f`). Backend commits `457ed0a6`, `62b031c6`,
> `8d723b1e` (branched off `872cf9a5`/PR #250); frontend counterpart merged as PR #398 (`432f046`) — see
> [AUDIT_TRAIL_FRONTEND_CONTEXT §Export](./AUDIT_TRAIL_FRONTEND_CONTEXT.md#post-ship-export--local-timezone-filtering-develop-2026-09-18-pr-398). The
> merge brought in the same commits with no additional review fixes. **The catalog stays 68 events** — this
> pass adds no `EVENTS` keys, only a `CATEGORY_LABELS` map and export plumbing.

The Export button described in §2 as "no `onClick`" is now wired end-to-end. Three intertwined changes:

### The export endpoint

- **Route:** `GET /api/audit-logs/export`, added in Zone 3 **before** `/{id}` (`whereNumber('id')` on the
  detail route already prevented capture, but literal-before-param is kept as the stated rule). Super-Admin
  gate moved into the FormRequest, not the controller body — same as `index()` now.
- **`AuditLogController::export()`** runs the *same* query as `index()` via the new `AuditLogFilter`, applies
  `status` itself (never sorted — see below), enforces a **50 000-row ceiling** (`MAX_EXPORT_ROWS`) checked
  with a `count()` **before** `streamDownload()` starts (once headers are on the wire there is no way back to
  an error response), and streams via `AuditLogCsvExport`. Over the ceiling → `422 audit_logs_export_too_large`.
- **`AuditLogFilter` (new, `app/Services/Audit/`)** — the filter + sort rules extracted out of `index()` so
  the export applies **byte-identical** filtering; an export that silently disagreed with the table it was
  launched from would be worse than none. `apply()` deliberately does **not** apply `status` (both callers do,
  after cloning for tab counts). Its search branch **replaces the `Searchable` trait** for this table: the
  trait's date-shaped-term branch only covers `expiry_date`/`created_at`/`updated_at`, so a date-shaped search
  ("2026-09-08") would add zero predicates and return every row — fixed at the call site, not the trait.
  The **Audit-ID exact match** (originally added on `develop` in `24e2d739`, see §17.1) lives here now:
  `ctype_digit($term)` → `orWhere('id', (int) $term)`, exact not `LIKE`, since a partial "1" matching 1/10/12
  would surprise.

### `AuditLogCsvExport` (new, `app/Exports/`)

- **CSV, not XLSX** (the other two exports in that folder are XLSX): an audit export is read by tooling as
  often as by a person, and PhpSpreadsheet holds the whole workbook in memory — won't hold at 50 k rows.
  `fputcsv` straight to `php://output` keeps memory flat.
- **`chunkByIdDesc(1000)`**, never `->get()` or `chunk()` (OFFSET pagination shifts under an append-only
  table). ☠️ **The query passed in MUST carry no `ORDER BY`.** `chunkByIdDesc()` only strips existing orders on
  the cursor column (`id`); any other `ORDER BY` (e.g. the table's default `created_at DESC`) survives, leads,
  and the cursor starts mid-set — the docblock records this produced 1,999 rows / 1,000 distinct ids for any
  count above the chunk size, a plausible-looking file silently missing its oldest rows. This is why `export()`
  deliberately omits `applySort()`. Nothing is lost: `audit_logs` is append-only with no `updated_at`, so
  `id DESC` **is** chronological order.
- **15 columns**, order mirrors the on-screen table (`auditTrailColumns.js`) left-to-right, with four
  export-only columns folded into their group: **Sensitivity, IP Address, the untruncated Description, and the
  failure reason** — the four things the list payload can't provide, which is most of why this is a server-side
  export. "Who Did It" is the **impersonator** when there is one (matching the table); the account acted
  through moves to "Acting Through", blank on ordinary rows.
- **Failure reason** comes from `AuditLog::failureReason()` (new) — reads the `['label' => 'Reason', 'value' => …]`
  entry out of `details`, the by-convention slot every `failed`-status call site uses (there is no
  `failure_reason` column).
- **Category label** via `AuditEventCatalog::categoryLabel()` (new) + `CATEGORY_LABELS` const — the eight
  display labels, mirrored from the frontend `AUDIT_CATEGORIES` because the CSV has no frontend to hand keys
  to. Degrades to the raw key rather than throwing (an unknown category must not fail an export of thousands
  of valid rows). `AuditEventCatalogTest` already pins these keys to `AuditLogIndexRequest::CATEGORIES`.
- **UTF-8 BOM** written first (Excel reads a BOM-less UTF-8 CSV as the system codepage). **CSV/formula
  injection neutralised** — a cell opening `= + - @` (tab/CR too) is prefixed with `'` (free-text names,
  companies, descriptions originate from user input).
- **`Date & Time` is UTC, spelled out** (`Y-m-d H:i:s UTC`) — `created_at` is UTC and the date filter compares
  in UTC, so any other zone would put the file and the filter that produced it in different timezones.

### Local-timezone date filtering — resolves the §10 / frontend-timezone gap

This is the fix for the "One more frontend gap" in §10 (viewer-timezone `from`/`to` vs UTC `created_at`).

- The window is stated **only** as `from`/`to` (local calendar dates), plus **`from_offset`/`to_offset`** — the
  viewer's UTC offset in **minutes** at each boundary (IST = 330), sent by the frontend's `toQueryParams()`.
  `AuditLogFilter::boundary()` derives the UTC instants from `from 00:00:00` / `to 23:59:59` minus the offset.
  Absent → 0, i.e. a plain UTC day, so non-SPA callers are unaffected with no fallback branch.
- ☠️ **Offsets, not instants, by design.** An earlier revision took the instants directly as `from_at`/`to_at`,
  which let a 1-day `from`/`to` pass the 31-day cap while a 10-year instant range scanned the table. The cap
  must measure the same values that state the window. **Do not reintroduce a second way to say "which rows".**
- Compared directly against `created_at`, **not `whereDate()`** — `whereDate()` wraps the column in `DATE()`,
  defeating every `(*, created_at)` composite index on this table for its primary filter.
- Validation: `from_offset`/`to_offset` are `integer|between:-720,840` (the real UTC-offset span, UTC−12 to
  UTC+14) and `required_with` each other; `from`/`to` are `required_with` each other on `index` (a one-sided
  range is unbounded on the other end and walks past the cap).

### `ValidatesAuditDateRange` trait (new, `app/Http/Requests/Concerns/`)

- **31-day ceiling** (`MAX_DATE_RANGE_DAYS = 31`), shared by `AuditLogIndexRequest` and `AuditLogExportRequest`
  so the two can't drift; **must match `MAX_DATE_RANGE_DAYS` in `TCV-Frontend/src/constants/auditTrail.js`.**
  Measures `from`/`to` (inclusive: same day = 1 day). Bounds export volume on an append-only,
  cross-organisation table. The frontend calendar refuses a wider selection; this is the backstop.
- **Caps a range that was SUPPLIED** — a request with no `from`/`to` passes straight through. `AuditLogExportRequest`
  marks both **`required`** (an unbounded export would run a full `COUNT(*)` before the row cap can reject it);
  `AuditLogIndexRequest` leaves them optional (its paginator bounds the response regardless).
- `AuditLogExportRequest` reuses `AuditLogIndexRequest::CATEGORIES` / `SENSITIVITY_LEVELS` verbatim, and omits
  `page`/`limit`/`sortBy`/`sortOrder` (whole set, fixed order — accepting a param only to ignore it would be a
  small lie).

**New/updated tests:** `AuditLogExportTest` (534 lines — filter parity with index, date-range validation, row
cap, CSV shape/order, injection escaping, BOM, impersonation columns), `AuditLogSearchTest` (audit-ID exact
match, from `24e2d739`), and 3 cases added to `AuditLogAccessTest` (export gate).

### 17.1. ✅ ON DEVELOP — two smaller audit changes since the 2026-09-17 sync

Merged to `develop` after the KB's `ff9be500` sync, independent of the export branch:

- **`24e2d739` — Audit-ID exact-match search** (PR #250, `fix/audit-trail-improvement-16-sep-26`, merged
  `872cf9a5`). Added to `AuditLogController::index()` on `develop`; the export branch then **moved it into
  `AuditLogFilter`** so index and export share it. `AuditLogSearchTest` (+69 lines).
- **`050d092b` — discount-toggle message is now dynamic.** `DiscountCodeController`'s
  `billing.discount_code_toggled` description changed from the static `'Discount code activated/inactivated.'`
  to `'Discount code '.($discount->is_active ? 'activated' : 'inactivated').'.'` — same dynamic-title style §13
  applied to status-change events. One line, description text only; catalog unchanged.

> **Not audit-trail:** `ws-502` (`00d5e98f`, `15207600`) fixes list-sort order for Credits / Discount Codes /
> Organizations / Users / Reports — unrelated to this feature, noted here only so a reader scanning `develop`'s
> recent commits doesn't mistake it for audit work.

