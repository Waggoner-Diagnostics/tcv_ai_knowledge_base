# Audit Trail — backend plan outline

**Repo:** `TCV-Backend` — ✅ **shipped to `develop` 2026-09-09** (PR #227, merge `940238fd`).
**Frontend contract:** ✅ also merged — `feat/ui-audit-trail` landed on `TCV-Frontend@develop` as PR #375 (`6b6c5ae`), specified in [AUDIT_TRAIL_FRONTEND_CONTEXT.md](./AUDIT_TRAIL_FRONTEND_CONTEXT.md). Phases 1–3 were built against fixtures; Phase 4 (swap three function bodies for axios calls) and the login/logout instrumentation described in SERVICES.md are both done and merged.

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
| **No `TrustProxies` middleware, no trusted-proxy config** | `app/Http/Middleware/` has 4 files, none of them |
| Nginx forwards `X-Forwarded-For` | `TCV-Frontend/nginx.conf:42,54,63` |
| No account-lockout mechanism anywhere | no `RateLimiter`/`lockout`/attempt counter in `AuthController` |
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

**Export — `GET /api/audit-logs/export`** — the button exists but has **no `onClick`** (`AuditTrail.js:281-291`). Wiring it is frontend work, and it cannot be a `window.open`: the endpoint sits behind `auth:sanctum`, so it needs `axiosInstance.get(..., { responseType: 'blob' })`.

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
| `test_activity` | Test Activity | invitations sent / bulk / resent / cancelled; test started / completed / abandoned | 7 |
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

**Medium** — test sent / resent / bulk, test started / completed / abandoned, patient added / edited, all exports, test catalogue status change, settings and email-template edits, profile edits, Patients-page verification.

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
| **Test abandoned** | Patient name, last section reached | Name is an identifier. | `unique_test_id`, test name, last section. |
| **Test invitation sent** | Recipient email(s), **Verification code** | The verification code is a **live credential** granting entry to a test session. Logging it is a security defect independent of HIPAA — anyone with audit read access could take a patient's test. | Recipient **count**, test name, credits used, expiry. Never the code, never the addresses. If a specific recipient must be traceable, store `test_invitations.id` — that row already has a primary key, so there is nothing to hash. |
| **Contact / enquiry submitted** | Name, Email, Subject, **Message** | Free text from the public web form — it can contain anything, including the sender describing their own medical condition. | Name, email, subject, message length. Not the body. |
| **Payment successful / failed** | "Payment Method" | Only ever brand + last four, straight from the Stripe object. Never a PAN, never a raw token. | Brand + last4, transaction ID, amount. |

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

- **Patients are never an `actor` and never a `target`.** For "Test completed"/"Test abandoned" the spreadsheet names the Patient as the actor; the frontend role vocabulary has no `patient`, and `Patient` is not a `User`, so an `actor_id` would be a dangling reference. Log these with `actor = system` and the owning user/organisation as `target`, with the patient referenced only by `patient_ref` inside `details`.
- **A denylist belongs in the service, not in the call sites.** `AuditService` masks recursively before any write: the reference plan's `password / token / secret / api_key` set **plus** `dob, date_of_birth, gender, zipcode, zip_code, diagnosis, result, verification_code, message, ssn, card_number`. Call sites will drift; one chokepoint will not. Give call sites a `ref()` helper so a patient reference cannot be hand-rolled, and add a test asserting that no `test_activity` or `accounts_users` row's `details`/`changes` JSON ever matches an email- or date-shaped pattern — it catches the regression the day someone adds a new call site.

---

## 6. Two blockers found in the current backend

**B1 — `$request->ip()` will return the reverse proxy, not the user.** There is no `TrustProxies` middleware and no trusted-proxy configuration, while Nginx sets `X-Forwarded-For` (`TCV-Frontend/nginx.conf:42`). Every `actor_ip` in production would be the load balancer, and every geo lookup would resolve to the datacentre. Fix first: register `Illuminate\Http\Middleware\TrustProxies` in `bootstrap/app.php` with the actual proxy CIDR (not `*`). This also fixes `RestrictIpMiddleware`, which has the same latent bug today.

**B2 — "Account locked (too many failed attempts)" has nothing to log.** No lockout, throttle, or failed-attempt counter exists on the login path (`routes/api.php` throttles only `/contact`). The spreadsheet also asks for a "Failed attempt count" on every failed login, which likewise does not exist. Either build lockout as a prerequisite, or cut both rows from Phase 1. Recommend cutting — it is a separate feature, not an audit feature.

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
| `TestExecutionService` / `TestResultService` | completion + abandonment | test started / completed / abandoned — **no result or diagnosis** |
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
