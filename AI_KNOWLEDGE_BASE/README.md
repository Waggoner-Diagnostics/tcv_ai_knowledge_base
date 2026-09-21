# TCV — AI Knowledge Base

Single source of truth for **TestingColorVision** across its three repos, built so an AI assistant can
work on the project **without rescanning ~80,800 lines across 600 source files**
(`TCV-Backend/app` 218 · `TCV-Frontend/src` 287 · `TCV-Website` 95) — measured 2026-09-21; the backend
grew by 44 files with `ws-459`.

| | |
|---|---|
| **Repos covered** | `TCV-Backend` (Laravel 12 API) · `TCV-Frontend` (React 18 SPA) · `TCV-Website` (Next.js 15 marketing site) |
| **Branches indexed** | `develop` · `develop` · `website-integration` — both code repos indexed from `develop`. The website is indexed from `website-integration` ([WEBSITE.md](WEBSITE.md)). ✅ **The three branches this row warned about have all merged** (2026-09-17/18) and are now indexed: `ws-502` (list sort tiebreaks), `ws-480` (credit purchase gate) and `tcv_data_migration` → **`ws-459`** (patient encryption at rest — `INDEXES/` now shows the **post**-encryption shape). `develop` also carries `ws-404`, `ws-449`, every audit-trail follow-up (PRs #238–#245, #250, #251, #261; frontend #384/#386, #391, #393, #398) and `fix/countries-list-update` (PR #265). **No branch is ahead of `develop` in this KB's scope.** |
| **First generated** | 2026-08-19 |
| **Code state at sync** | `TCV-Backend` `330cf77d` (develop) · `TCV-Frontend` `d0da885` (develop) · `TCV-Website` `4d2c0d4` (website-integration) — generated **2026-09-21**. ⚠️ `git fetch` still fails from the sync shell; local `develop` was confirmed **identical to `origin/develop`** in both code repos (`rev-list --left-right --count` = `0 0`), against refs the IDE last fetched **2026-09-18** (backend 18:27, frontend 15:39). Anything merged after that time is not here. |
| **Backend scale** | 242 classes/interfaces/traits · 1215 methods · 164 API endpoints · 55 tables · 151 migrations · suite **1191 passed / 0 failed** |
| **Client scale** | 65 top-level routes · 44 Redux slices (SPA) · 32 marketing pages (website) |

> **Check freshness before trusting prose.** Compare the SHAs above with `git -C <repo> rev-parse --short HEAD`.
> If they differ, the generated indexes may be stale — re-run the generator (see [Regenerating](#regenerating)).

### ✅ `tcv-backend-codefix` and `ws-401` have merged — labels flipped 2026-09-07

The 2026-09-02 sync was generated from `tcv-backend-codefix` while it was unmerged, which is why this
file spent two syncs warning that its "FIXED" notes described code that did not ship. **It has now
merged into `develop`**, verified at `52804ee9` by finding `callerOwnsPatient`, `auth_context`,
`AddRequestId`, the hashing migration and the `entrypoint.sh` boot fixes all present. `S-02` (partial),
`S-03`, `S-14`, `S-18` and `S-17` are flipped in [SECURITY.md](SECURITY.md) accordingly.

`ws-401` merged too, as `2026_09_03_000002_normalize_legacy_bracket_placeholders_in_email_templates`.

**☠️ The rule that produced that mess still stands: never sync this KB from a feature branch.**
Indexing a fix branch makes the generated views describe code that does not ship, and they then
contradict the prose instead of confirming it — the 2026-09-02 audit reported **15** public endpoints
because `tcv-backend-codefix` had already guarded the Stripe routes, while [ROUTES.md](ROUTES.md) and
[BILLING_CONTEXT](CONTEXT/BILLING_CONTEXT.md) went on correctly calling them public. `verify.php`'s
prose-count check is what catches this class of divergence; do not wave it through.

`ws-392`, `ws-398`, `ws-400`, `ws-401`, `ws-402`, `ws-417` and `tcv-backend-codefix` are all **merged
into backend `develop`** — `ws-402` landed 2026-09-07 (PR #213), so passages flagged `ws-402` now
describe shipped behaviour, not a branch. Backend `develop` has since taken two features this KB
tracks separately: the **QA automation helpers** (PR #223, 2026-09-08 — see
[SECURITY.md](SECURITY.md#s-20--the-qa-automation-endpoints-are-an-account-takeover-surface-gated-only-by-app_env))
and the **Audit Trail** read API (PR #227, 2026-09-09 — `AuditLogController`, `Services/Audit/`,
`audit_logs`).

### ✅ `ws-404` and `ws-449` have merged — labels flipped 2026-09-17

**`ws-404`** merged into backend `develop` as PR #240 (2026-09-15) with a follow-up, PR #251
(2026-09-16). Every passage that flagged it "unmerged" / "`ws-404` only" now describes shipped code, and
the indexes list **three** jobs, **six** commands and one schedule. It landed **substantially changed**
from the `3f3aeb58` state the KB last described — read these before trusting older notes:

| Changed after `3f3aeb58` | Where |
|---|---|
| ☠️ **`backend-queue` and `backend-scheduler` merged behind the compose `workers` profile — off by default.** A plain `docker compose up` still runs no worker and no scheduler, so the 2026-09-14 correction "the schedule fires" is **not** true of `develop` as deployed. First-boot runbook added | [DEPLOYMENT.md](DEPLOYMENT.md) · [QUEUES.md](QUEUES.md) · [JOBS.md](JOBS.md) |
| Defer-vs-fail rules rewritten: `deferralReason()` defers never-connected, SMTP **4xx**, and **sender-quota 5xx** (the QA host's 200/hour `550`, `8ee517aa`); SES errors classified; post-DATA socket errors now **fail** to avoid duplicate emails | [JOBS.md](JOBS.md#-connection-failure-is-not-address-rejection) · [THIRD_PARTY.md](THIRD_PARTY.md) |
| **Deferral cap** — `test_invitations.deferred_count`, `mail.invitation_max_deferrals` = **36** (≈6h), then write-off + refund | [JOBS.md](JOBS.md) · [ENVIRONMENT.md](ENVIRONMENT.md) |
| **Expiry refunds** (`expireStaleInvitations()`), claim-guarded `markFailed()`, cancel **409** on a lost race, `credited_by = null` for automated refunds | [CONTEXT/CREDITS_CONTEXT.md](CONTEXT/CREDITS_CONTEXT.md) · [CONTEXT/INVITATION_CONTEXT.md](CONTEXT/INVITATION_CONTEXT.md) |
| Queue batch budget (`invitation_queue_batch_budget` 60s, resolved at run time), `retry_after` 90 → **360**, `RUN_INIT` in `entrypoint.sh` | [QUEUES.md](QUEUES.md) · [CONFIGURATION.md](CONFIGURATION.md) · [DEPLOYMENT.md](DEPLOYMENT.md) |

**`ws-449`** merged as PR #241 (2026-09-14): public `GET api/access-check` (the 17th public endpoint — no
SPA caller yet), backend nginx forwarding `X-Forwarded-For`, and ☠️ `trustProxies()` **always on** with a
private-range default. Because the edge and SPA nginx still `set_real_ip_from 0.0.0.0/0`, **S-16 changed
shape rather than closing** — client IPs are now forgeable by the traced chain. Read
[S-16 *Status 2026-09-17*](SECURITY.md#status-2026-09-17--both-backend-halves-shipped-the-frontend-nginx-precondition-did-not).

On the **frontend**, `develop` carries `ws-395`, `ws-397`, `ws-399`, `ws-402`, `ws-404`, `ws-407`
(PR #378) and `ws-417`, and `origin/ws-400` is merged. **`ws-401` is still unmerged there** (2 commits),
and a local `ws-400` holds one unpushed commit — the frontend and backend halves of the same ticket number
are not in the same state, so check per repo. Backend `ws-401` also has one commit (`af555809`) beyond
`develop`, though its repair migration is merged.

### ✅ `ws-459`, `ws-480`, `ws-502` and the countries fix have merged — labels flipped 2026-09-21

Everything the previous sync tracked as a branch is now on `develop` and **indexed**. Merge set since
`ff9be500` / `80403e7`, by first-parent:

| Ticket | Backend | Frontend | What flipped in this KB |
|---|---|---|---|
| **`ws-502`** list sort tiebreaks | PR #253, `820747a6` (09-17) | PR #390, `9152b45` (09-17) | [FRONTEND.md](FRONTEND.md#server-sorted-grids-ws-502) · [CHANGE_IMPACT_GUIDE](CHANGE_IMPACT_GUIDE.md) · [API_INDEX](API_INDEX.md) · the five context packs that flagged a grid's `ORDER BY` |
| **`ws-480`** credit purchase gate | PR #254, `10a8ae73` (09-18) | PR #392, `1f31854` (09-18) | [FRONTEND.md](FRONTEND.md#credit-purchase-gate-ws-480) · [BILLING trap 9](CONTEXT/BILLING_CONTEXT.md#9--the-unlimited-purchase-refusal-is-on-the-deprecated-surface-ws-480) · [CREDITS_CONTEXT](CONTEXT/CREDITS_CONTEXT.md) · `F-017` / `F-041` / `F-042` |
| **`ws-459`** legacy migration + patient encryption at rest (was `tcv_data_migration`) | PR #255, `f0541712` (09-18) | PR #395, `d0da885` (09-18) | [DATA_MIGRATION_CONTEXT](CONTEXT/DATA_MIGRATION_CONTEXT.md) · [PATIENT_CONTEXT](CONTEXT/PATIENT_CONTEXT.md) · [AUTH_CONTEXT](CONTEXT/AUTH_CONTEXT.md) · [DATABASE.md](DATABASE.md) · `F-090` |
| **audit-trail CSV export** | PR #261, `07cf5b6f` (09-18) | PR #398, `432f046` (09-18) | already written up 2026-09-18; the endpoint is now **indexed** as `API-011` |
| **countries list** | PR #265, `330cf77d` (09-18) | — | [FEATURE_INDEX](FEATURE_INDEX.md) `F-081` · [CHANGE_IMPACT_GUIDE](CHANGE_IMPACT_GUIDE.md) · [THIRD_PARTY](THIRD_PARTY.md) |

☠️ **`ws-459` is the one that changes how you read the rest of this KB.** 182 files, ~25.3k insertions.
Patient, answer and invitation PII is now **ciphertext at rest on `develop`**, so every
`INDEXES/` column for those tables describes the *post*-encryption shape and `where('email', $x)` can
never match — read [DATA_MIGRATION_CONTEXT](CONTEXT/DATA_MIGRATION_CONTEXT.md) before touching a patient
query. It also brought 20 migrations, the `migrate:*` command family and `migration_progress`.

⚠️ **The `ws-480` review defects described below were both fixed before the merge.** The backend fix is
`b081b618` + `23d005ff`; the frontend fix is `e3a222e` + `2b745ce`. The shas `8d247f8c` / `346efce` cited
in older passages are the *pre-review* state and no longer describe `develop`.

#### What the `ws-480` review found (kept — the lesson outlives the branch)

| Repo | What the review found | Where it is written up |
|---|---|---|
| `TCV-Backend` | the refusal sat only on the deprecated `api/stripe/*` surface, so the live purchase path was open | [BILLING trap 9](CONTEXT/BILLING_CONTEXT.md#9--the-unlimited-purchase-refusal-is-on-the-deprecated-surface-ws-480) |
| `TCV-Frontend` | the purchase gate keyed off `initialized \|\| !!error`, which pins the credits page on `Loading credits…` for good on any failure carrying no JSON `message` | [FRONTEND.md](FRONTEND.md#-settled-not-initialized--error--the-gate-has-to-key-off-something-forward-only) |

The write-ups are:

| Where | What it covers |
|---|---|
| [FRONTEND.md](FRONTEND.md#credit-purchase-gate-ws-480) | the three gate flags on `CreditPage`, `userCredits.settled` and why the gate cannot be derived from `error`, the separate `Checkout` guard and its `createSetupIntent()` condition, the `Loading credits…` paint, the `cp-alert--info` notice, and the modal's at-least-one-test pre-check |
| [CONTEXT/BILLING_CONTEXT.md trap 9](CONTEXT/BILLING_CONTEXT.md#9--the-unlimited-purchase-refusal-is-on-the-deprecated-surface-ws-480) | the 422, why it is returned rather than thrown, the four handlers it ended up on, and `Credits::hasUnlimited()` |
| [CONTEXT/CREDITS_CONTEXT.md trap 12](CONTEXT/CREDITS_CONTEXT.md#-traps) | what a purchase on top of an unlimited grant does to the balance once the grant lapses |
| [CHANGE_IMPACT_GUIDE.md](CHANGE_IMPACT_GUIDE.md) | the blast-radius rows for both halves |

☠️ **The one thing to carry out of that ticket:** the backend refusal first landed on
`POST api/stripe/create-payment-intent` **alone** — the deprecated surface no SPA code calls. The portal
buys credits through `POST api/payment/initialize` → `POST api/payment/confirm`, so as written the rule
was enforced in the client only. ✅ **Caught in review and fixed on the branch the same day**: the
refusal now sits on all four routed purchase handlers, behind one predicate
(`Credits::hasUnlimited()`), pinned by `tests/Feature/Billing/UnlimitedCreditPurchaseRefusedTest.php`.
The lesson outlives the fix — read
[BILLING_CONTEXT](CONTEXT/BILLING_CONTEXT.md#two-parallel-payment-surfaces) before adding any rule to a
payment handler, and count the handlers on **both** surfaces.

📌 **Two corrections went in with it**, both about code that had already changed on `develop`:
`FRONTEND.md` said `initialized` is never reset and nothing clears the credits slice on logout — both
untrue since `ws-397`'s follow-up `400cf66` (2026-08-31), which is what `ws-480`'s gate relies on; and
`API_INDEX.md` still listed `api/stripe/*` as **public**, though it moved into `auth:sanctum` on
2026-09-07 with [S-17](SECURITY.md#s-17--five-stripe-payment-endpoints-were-public-on-develop) and the
generated index has said so since.

### TCV-Website is indexed from `website-integration`, not `develop` — the one deliberate exception

**Resolved 2026-09-12.** The website indexes used to lag at `develop` `3ec94ec` while the prose ran
ahead; they are now generated from `website-integration` (`deb667a`), the same branch
[WEBSITE.md](WEBSITE.md) describes, so the two no longer contradict each other.

**Why this is not a breach of the feature-branch rule above.** That rule exists because a *feature*
branch describes code that may never ship. `website-integration` is not one: `git log
website-integration..develop` is empty — it is a strict superset of `develop` (which sits **19 commits
behind** with nothing of its own), and it is the branch the deploy workflows are actually shaped
around. Indexing `develop` here meant indexing the *less* deployable of the two. What the rule forbids
— generating views of code that does not ship — is what the old `3ec94ec` sync was doing: no
`Dockerfile`, no `docker-compose.yml`, no `nginx.conf` fronting the SPA and API, neither deploy
workflow.

☠️ **This exception is scoped to `TCV-Website` and does not generalise.** `TCV-Backend` and
`TCV-Frontend` are still indexed from `develop`, and `ws-502` is prose-only. Before indexing any
other repo off a non-`develop` branch, establish the same two facts: the branch is a strict superset of
`develop`, and it is what actually deploys.

⭐ **`ws-website-373` has merged** into `website-integration` (PR #7, `05e66ce`) — the `AuthModal`
corner-clipping fix, written up in
[WEBSITE.md](WEBSITE.md#the-auth-modals-rounded-clip--ws-website-373-merged-into-website-integration-pr-7).
⚠️ **It is a different branch from the backend `ws-373`** that the email-template passages flag. The
website repo holds local branches under both names, so read every `ws-373` note together with the repo
it belongs to — as with `ws-343` / `ws-website-343`.

### What the 2026-09-21 sync changed

`routes_source` stayed `artisan route:list --json`, so route figures compare directly with 2026-09-17.
This is the largest single sync in the KB's history, and `ws-459` accounts for nearly all of it.

| Count | 2026-09-17 | 2026-09-21 | Why |
|---|---|---|---|
| Endpoints | 163 | **164** | `GET api/audit-logs/export` (PR #261) — the only new route |
| Public `api/*` | 17 | **17** | ✅ **unchanged, and the same 17 endpoints.** Nothing became public |
| Classes/interfaces/traits | 210 | **242** | +32, almost all `ws-459`: the `migrate:*` command family, `Support/Legacy/*`, the three casts, `LegacyPatientSource`, `AuditLogFilter`, `AuditLogCsvExport`, `UnscoreableTestException` |
| Methods | 903 | **1215** | +312, same cause |
| DB tables | 53 | **55** | `migration_progress` (live) and `discount_code_usages` (**created and dropped in the same merge** — see [DATABASE.md](DATABASE.md)) |
| Migrations | 131 | **151** | +20 — the `ws-459` set, plus `update_obsolete_country_names` (PR #265) |
| Models | 41 | **42** | `Models/Concerns/HasLegacyEncryptedAttributes` |
| Services | 35 | **38** | `Services/Migration/LegacyCipher`, `LegacyPatientSource`, `Services/Audit/AuditLogFilter` |
| FormRequests | 26 | **28** | `AuditLogExportRequest`, `Requests/Concerns/ValidatesAuditDateRange` |
| Redux slices | 43 | **44** | frontend `ws-459` / audit-export work |
| Tests | 812 | **1191 passed, 0 failed** | 3632 assertions, measured on `330cf77d` ([TESTING.md](TESTING.md)) |
| Everything else | — | **unchanged** | relations 70 · controllers 38 · policies 3 · middleware 4 · jobs 3 · events 3 · spa_routes 65 · spa_api_calls 88 · spa_drift 8 · website_pages 32 · website_api 5 |

☠️ **`API-nnn` ids shifted by one again, from `API-011` up.** `api/audit-logs/export` sorts ahead of
`api/audit-logs/people`, so it took `API-011` and pushed everything after it. `API-001`…`API-010` are
unchanged. All 15 prose citations were re-resolved against the new index by endpoint, not by arithmetic
— `verify-password` `API-163`→`API-164`, `stripe/create-payment-intent` `API-090`→`API-091`,
`test-invitations/{id}/cancel` `API-103`→`API-104`, `user/tests/bulk-update-assignment`
`API-149`→`API-150`, `distributor-enquiry` `API-031`→`API-032`,
`dropdown/organization-settings-options` `API-034`→`API-035`. `TABLE-nnn` also renumbered from
`TABLE-010`, but the two cited (`TABLE-007`, `TABLE-009`) were below the pivot and still resolve.

**Derived views — all three clean.** [PUBLIC_ROUTE_AUDIT](INDEXES/PUBLIC_ROUTE_AUDIT.md): still 17
public, the **same** 17 endpoints, id renumbering only — no route became public despite 182 files
landing. [CONTRACT_DRIFT](INDEXES/CONTRACT_DRIFT.md): unchanged at 8 drift rows, id renumbering only —
no client call lost its endpoint. [FRONTEND_ROUTE_INDEX](INDEXES/FRONTEND_ROUTE_INDEX.md): **byte-identical
apart from the generation date** — no page became unreachable, and the two known dead routes and three
dead grants are the same ones.

⚠️ **Fetch caveat unchanged.** `git fetch` still fails from the sync shell ("Repository not found").
Local `develop` was verified identical to `origin/develop` in both repos, but those remote refs were
last updated by the IDE on **2026-09-18** — anything merged after that is not in this sync.

### What the 2026-09-17 sync changed

Backend `develop` advanced from `c3449270` to `ff9be500` (33 commits: `ws-404` PRs #240/#251, `ws-449`
PR #241, audit-trail PRs #242/#243/#245, and `45b53173`'s CI tweak); frontend `develop` from `e9b664c` to
`80403e7` (audit-trail PRs #384/#386). `TCV-Website` was not in scope and is unchanged at `cb4a1b6`
(its local `website-integration` is 2 commits behind origin — pull before the next website sync).
`routes_source` stayed `artisan route:list --json`, so route figures compare directly.

| Count | 2026-09-14 | 2026-09-17 | Why |
|---|---|---|---|
| Endpoints | 162 | **163** | `GET api/access-check` (`ws-449`) |
| Public `api/*` | 16 | **17** | the same route — public by design, reviewed; see [S-16](SECURITY.md#status-2026-09-17--both-backend-halves-shipped-the-frontend-nginx-precondition-did-not) |
| Classes/interfaces/traits | 207 | **210** | `MailPreflight` (`CMD-003`), `SweepPendingInvitationsJob` (`JOB-003`), `SuperAdminSeeder` |
| Methods | 863 | **903** | mostly `SendTestInvitationEmailsJob` (13 → 26), `MailPreflight` (13), `BuildsAuditDiffs` (3 → 9), `accountLockedResponse()` |
| Migrations | 128 | **131** | `…_14_000001_add_deferred_count_to_test_invitations`, `…_15_000001_add_impersonator_to_audit_logs`, `…_15_000002_backfill_impersonator_on_audit_logs` |
| Jobs | 2 | **3** | `SweepPendingInvitationsJob` |
| Tests | 812 (09-17) | **1191 passed, 0 failed** | measured on `330cf77d`, 3632 assertions ([TESTING.md](TESTING.md)) |
| Everything else | — | **unchanged** | tables 53, relations 70, controllers 38, models 41, services 35, spa_routes 65, spa_slices 43, spa_api_calls 88, spa_drift 8, website_pages 32 |

☠️ **Every `API-nnn` id shifted by one.** `api/access-check` sorts first, so it took `API-001`. The four
prose citations were re-resolved (two were already stale: `verify-password` is `API-163`,
`distributor-enquiry` is `API-031`); `CMD-003`…`006` also renumbered around `MailPreflight`. See
[HOW_TO_REGENERATE](GUIDES/HOW_TO_REGENERATE.md).

> 📌 **The ids in this paragraph are the 2026-09-17 numbering and have since moved again.** The
> 2026-09-21 sync shifted everything from `API-011` up by one more (`api/audit-logs/export` took
> `API-011`), so `verify-password` is now `API-164` and `distributor-enquiry` is `API-032`. This block
> is kept as the record of *that* sync; the live ids are in
> [API_ENDPOINT_INDEX](INDEXES/API_ENDPOINT_INDEX.md).

**Derived views:** [PUBLIC_ROUTE_AUDIT](INDEXES/PUBLIC_ROUTE_AUDIT.md) gained exactly `api/access-check`
(everything else renumbered only). [CONTRACT_DRIFT](INDEXES/CONTRACT_DRIFT.md) changed only in id
renumbering — no client call lost its endpoint. [FRONTEND_ROUTE_INDEX](INDEXES/FRONTEND_ROUTE_INDEX.md) is
identical apart from the date — no page became unreachable.

**Prose corrections made on the way that were stale before this sync** (not caused by it): `ROUTES.md`
zone counts (still quoting the 09-04 AST parse, and listing the five Stripe routes as public);
`MIDDLEWARE.md` / `ARCHITECTURE_REALITY.md` saying `EnsureTokenIsValid` is still present and there is only
one rate limit; the invitation "cancel refunds the wrong account" trap (the query is caller-scoped);
`ws-373`/`ws-400` "will conflict" (both merged and reconciled); and `ws-401`'s repair migration labelled
unmerged.

### What the 2026-09-14 sync changed

A quiet sync — **one count moved and nothing else**. Backend `develop` advanced from `fe989d4a` to
`c3449270`, taking in the audit-trail improvement PR (#238, `1c7630f5`); frontend `develop` advanced to
`e9b664c`.

| Count | 2026-09-12 | 2026-09-14 | Why |
|---|---|---|---|
| Methods | 860 | **863** | `AuditEventCatalog::invitationSentTitle()`, `AuditEventCatalog::statusChangeTitle()`, `TestController::logAssignedTestsChange()` — dedicated events for account suspension plus before/after diff tracking on assigned tests |
| Everything else | — | **unchanged** | routes 162, public 16, classes 207, tables 53, migrations 128, relations 70, spa_routes 65, spa_slices 43, website_pages 32 |

✅ **All three derived views are byte-identical apart from their generation date** —
[PUBLIC_ROUTE_AUDIT](INDEXES/PUBLIC_ROUTE_AUDIT.md), [CONTRACT_DRIFT](INDEXES/CONTRACT_DRIFT.md) and
[FRONTEND_ROUTE_INDEX](INDEXES/FRONTEND_ROUTE_INDEX.md). No route became public, no SPA call lost its
endpoint, no page became unreachable by every role. `routes_source` stayed
`artisan route:list --json` (backend `vendor/` is present), so the route figures are directly comparable
with the previous sync rather than being a parser artefact.

⚠️ *(As of 2026-09-14 — superseded: both merged and were indexed on 2026-09-17.)* **`ws-404` was still
prose-only and not indexed** — the branch rule above. A second backend branch, **`ws-449`** (adds the
public `GET /access-check` SPA boot gate), was also ahead of `develop` — so the **16** public endpoints
counted here did not include it.

### What the 2026-09-07 sync changed

`tcv-backend-codefix` and `ws-401` merged, and `vendor/` is installed again so route extraction is back
to the authoritative `artisan route:list --json` (it had fallen back to the AST parser). Read the
deltas, not just the totals:

| Count | 2026-09-04 | 2026-09-07 | Why |
|---|---|---|---|
| Endpoints in the index | 178 | **158** | ⭐ **not a loss of features.** `tcv-backend-codefix` converted 8 `Route::resource` → `Route::apiResource`, dropping the 16 unreachable `create`/`edit` form routes each pair registered, plus other route tidying |
| Public `api/*` | 20 | **15** | ✅ the five Stripe routes moved inside `auth:sanctum` — [S-17](SECURITY.md#s-17--five-stripe-payment-endpoints-were-public-on-develop) fixed |
| Methods | 761 | **775** | merged security-hardening work |
| Migrations | 118 | **123** | +5 from the two merged branches |
| Relationships | 69 | **70** | `test_sessions.patient_id` |
| `routes_source` | AST parser (fallback) | **`artisan route:list --json`** | `vendor/` reinstalled, so the authoritative list is back — framework and package routes are visible again |

### What the 2026-09-04 sync changed

Moving from `tcv-backend-codefix` to `develop` — and from `artisan route:list` to the AST parser —
moves numbers in both directions. Read the deltas, not just the totals:

| Count | 2026-09-02 | 2026-09-04 | Why |
|---|---|---|---|
| Endpoints in the index | 158 | **178** | `api/*` only, both times — real growth from the feature work `develop` has absorbed since the last sync |
| Public `api/*` | 15 | **20** | five Stripe routes are outside every guard on `develop` ([S-17](SECURITY.md#s-17--five-stripe-payment-endpoints-are-public-on-develop)) |
| `routes/web.php` rows | 13 | **2** | ⚠️ extraction artefact, not a code change — see below |
| Classes · methods | 189 · 731 | **196 · 761** | merged feature work |
| Migrations | 120 | **118** | `tcv-backend-codefix` carried two that are not on `develop` |
| Relationships | 70 | **69** | one relation removed with the merges |
| Jobs · listeners | 1 · 3 | **2 · 4** | `SendTestInvitationEmailsJob` (`ws-404`), `PrefixEmailSubject` (`ws-417`) |

⚠️ **`routes_source` flipped to the AST parser.** `TCV-Backend` no longer has a `vendor/` directory
*or* a `.env`, so `artisan route:list --json` cannot boot and `extract.php` fell back to the static
parse — recorded in `.data/facts.json` → `routes_source` and printed at the top of
[API_ENDPOINT_INDEX.md](INDEXES/API_ENDPOINT_INDEX.md). The parser cannot see framework or package
routes, so `sanctum/csrf-cookie`, `storage/{path}`, `up` and the seven `nnjeim/world` `{prefix?}/…`
endpoints dropped out of the web-route table, and `GET|HEAD` now reads `GET`. **Those eleven rows are
still live in production.** To get the authoritative list back, run `composer install` in
`TCV-Backend` and copy `.env.example` to `.env`, then regenerate — see
[GUIDES/HOW_TO_REGENERATE.md](GUIDES/HOW_TO_REGENERATE.md).

[CONTRACT_DRIFT.md](INDEXES/CONTRACT_DRIFT.md) and
[FRONTEND_ROUTE_INDEX.md](INDEXES/FRONTEND_ROUTE_INDEX.md) changed only in `API-nnn` renumbering and
the generation date — no client call lost its endpoint, no page became unreachable.

### ✅ `tcv-backend-codefix` — merged; what `develop` gained

This security-hardening pass (2026-09-02) **is now on `develop`**, verified 2026-09-07. Kept as a
change log because several entries changed behaviour rather than only fixing bugs. Each is
cross-referenced to its finding:

| Area | Before | On `tcv-backend-codefix` |
|---|---|---|
| Session tokens | `test_sessions.session_token` and `organization_patient_sessions.token` stored **plaintext** | **SHA-256 hashed**, matching the LMS tier — see [SECURITY.md "what is done well"](SECURITY.md#what-is-done-well) |
| Patient / test-session ownership | `patients/{id}`, `assignTest`, `getActiveTest`, `sendResumeEmail`, certificate download had **no** ownership check (or one built on forgeable request input) | All read the new unforgeable `auth_context` request attribute — [S-02](SECURITY.md#s-02--test-session-endpoints-never-check-that-the-caller-owns-the-test) (partial), [S-03](SECURITY.md#s-03--sendresumeemail-mails-a-resume-link-for-any-test-to-any-address), [S-14](SECURITY.md#s-14--patientsid-showupdatedestroy-have-no-ownership-scoping), [S-18](SECURITY.md#s-18--assigntest--getactivetest-let-a-session-act-on-another-organizations-patient) — all fixed **on that branch only; every one of them is still open on `develop`** |
| Rate limiting | none on login/register/password-reset/signature-verify/bulk-invitations/plate-url | **7** named `throttle:` limiters (`send-resume-email` added 2026-09-07), keyed per account via `callerKey()` rather than per shared IP. See [S-16](SECURITY.md#s-16--every-client-shares-one-ip-rate-limits-and-ip-restriction-are-both-inert): the global-bucket outage is gone, but there is now **no global ceiling**, so credential stuffing is unthrottled until the nginx half ships |
| Migration failure | silent — container serves traffic on a stale schema | `entrypoint.sh` writes a marker; `/up` health check fails loudly. Both bugs in the fix itself are now **closed** (2026-09-07) — see [DEPLOYMENT.md](DEPLOYMENT.md) traps 3–4 |
| Fresh-database boot | n/a | ☠️ was **bricked** by `migrate --isolated` taking its lock in a table a migration creates — fixed 2026-09-07 by bootstrapping `create_cache_table` first ([DEPLOYMENT.md](DEPLOYMENT.md) trap 3) |
| `test_sessions.patient_id` | column does not exist | new nullable FK carrying the session→patient binding. Rows predating it **cannot** be backfilled, so the migration expires them rather than leaving them half-authenticated ([S-14](SECURITY.md#s-14--patientsid-showupdatedestroy-have-no-ownership-scoping)) |
| Credit / coupon expiry | a credit dated "expires today" stopped counting at 00:00 that day | compared DATE-to-DATE, so it is valid all day — a **real production behaviour change**, see [CREDITS_CONTEXT](CONTEXT/CREDITS_CONTEXT.md) |
| Request correlation | none | `AddRequestId` middleware + JSON log formatter — see [MIDDLEWARE.md](MIDDLEWARE.md), [LOGGING.md](LOGGING.md) |
| `Route::resource` on JSON-only controllers | registered unreachable `create`/`edit` form routes | switched to `Route::apiResource` throughout |
| Dead code | `EnsureTokenIsValid` middleware present, never wired | deleted |

☠️ **A near-miss during this same pass:** the branch briefly registered `App\Providers\EventServiceProvider`
in `bootstrap/providers.php`, colliding with the auto-discovery that [EVENTS.md](EVENTS.md) and
[ARCHITECTURE_REALITY.md](ARCHITECTURE_REALITY.md) already warned about by name — confirmed by test to
double-send the `SendAfterPasswordReset` notification. Caught and reverted 2026-09-04 before merge; see
[ARCHITECTURE_REALITY.md §1](ARCHITECTURE_REALITY.md#1-eventserviceprovider-is-never-loaded).

Full detail: [SECURITY.md](SECURITY.md), [CONTEXT/AUTH_CONTEXT.md](CONTEXT/AUTH_CONTEXT.md),
[MIDDLEWARE.md](MIDDLEWARE.md), [DEPLOYMENT.md](DEPLOYMENT.md).

#### Review pass, 2026-09-07 — and why the indexes were *not* regenerated

Both blocking findings from the `tcv-backend-codefix → develop` review were fixed and verified by
reproduction (fresh-database boot; the migration's data step), plus five of the non-blocking ones:
the duplicate FK index, `test_condition` becoming unwritable under `validated()`, the unthrottled
`send-resume-email`, a `RateLimiter::clear()` call that cleared nothing, and the mislabelled
credit-expiry comment. Backend suite **263 passed** with 11 new cases.

Three were deliberately **not** fixed here, each for a stated reason — the credential-stuffing gap
(the honest fix is the nginx half of [S-16](SECURITY.md#s-16--every-client-shares-one-ip-rate-limits-and-ip-restriction-are-both-inert),
and adding a global limiter first would recreate the outage it replaced), and two `TCV-Frontend`
changes: the `test_completed` 409 with no client arm, and the unread `error_type` vocabulary. Both are
written up in [FULLSTACK_MAP.md](FULLSTACK_MAP.md).

☠️ **`composer regenerate` was deliberately skipped, even though routes, models and migrations all
changed.** The repo is checked out on `tcv-backend-codefix`, and regenerating now would re-index the
KB from a feature branch — precisely what the warning above forbids, and exactly what produced the
15-vs-20 public-endpoint contradiction in the 2026-09-02 sync. **Regenerate after the merge, with
`TCV-Backend` on `develop`**, then diff [API_ENDPOINT_INDEX.md](INDEXES/API_ENDPOINT_INDEX.md),
[PUBLIC_ROUTE_AUDIT.md](INDEXES/PUBLIC_ROUTE_AUDIT.md) and
[DATABASE_TABLE_INDEX.md](INDEXES/DATABASE_TABLE_INDEX.md) — `test_sessions` gains `patient_id`, and
[S-17](SECURITY.md#s-17--five-stripe-payment-endpoints-are-public-on-develop)'s five Stripe routes move
inside `auth:sanctum`, taking public `api/*` from 20 back to 15. Flip the `S-02`/`S-03`/`S-14`/`S-18`
labels in [SECURITY.md](SECURITY.md) in the same pass, or the KB will claim fixed-but-unmerged for
findings that have shipped.

**✅ `ws-401` merged into `develop`** (legacy email-placeholder repair). It landed as
`2026_09_03_000002_normalize_legacy_bracket_placeholders_in_email_templates`, which scopes the rewrite
by template type. Passages elsewhere flagged `ws-401` now describe the indexed tree. ⚠️ Note `ws-402`
carries a **second, different** implementation of the same repair — see the collision warning below.

**☠️ `ws-402` is not indexed** (credit revocation, 2026-09-03/04, branched off the `ws-401` line
— backend and frontend both). Passages flagged `ws-402` describe that branch, not the indexed tree. Read
them as "if ws-402 merges". What changes when it does:

| Area | On `develop` (indexed) | On `ws-402` |
|---|---|---|
| `CreditsController::destroy()` | hard-deletes the whole grant row, even the spent part — pushes `granted` below `consumed`, hidden by the `max(0, …)` clamp | `Credits::revokeGrant()` takes back only the **unspent** part; a partly-used grant is kept, with a negative `SOURCE_ADMIN_REVOKED` counter-entry, instead of being deleted |
| `credits.source` values | `0` Manual · `1` Purchased · `2` Revoked | + `3` `SOURCE_ADMIN_REVOKED` · `4` `SOURCE_ADJUSTMENT` (ledger-balancing entry) |
| `credits.original_source` | column does not exist | new nullable column; on a `SOURCE_REVOKED` row it records which underlying grant (Manual/Purchase) funded the test being refunded, traced FIFO via `Credits::traceConsumedOrigin()` |
| `CreditsPolicy::delete()` | `true` only for `source === SOURCE_MANUAL`, **and `$user` is never read** — any authenticated user can delete anyone's grant ([S-19](SECURITY.md#s-19)) | `$user->isSuperAdmin()` **first**, then the source rules. Also `true` for a `SOURCE_REVOKED` row whose `original_source === SOURCE_MANUAL` — a refund of manually-granted credits is deletable; one tracing back to a purchase never is. ⚠️ `index()` stays unscoped |
| `GET api/credits` (list) response | grant rows only | each row also carries `used_credits` / **`revoked_credits`** / `remaining_credits` from `Credits::getGrantAllocation()`. used and revoked are **separate** — both draw a grant down, but only `used` is something the user did. **All three are `null`, not `0`, on a row the allocation never covered** (unlimited, or expired) — null means "not computed" |
| `DELETE api/credits/{id}` response | hand-built `response()->json()` | `ApiResponse` on both branches, so the 422 carries `success: false`; 200 returns `{revoked_credits, available_credits, removed}` and a message naming the split when only part of a grant was taken. `removed: false` tells the client the row survived. Adds 3 lang keys and a trailing `array $replace` to `ApiResponse::success()`/`error()` |
| `AuthorizationException` (`$this->authorize()` denial) | **500**, per fact #1 above / [ERROR_HANDLING.md](ERROR_HANDLING.md) | **403** — `Handler.php` gains a dedicated branch. Scoped to this one exception type only; `ModelNotFoundException` and the rest are still 500 |
| Artisan commands | — | + `credits:settle-negative-balances {--apply}` — one-time repair for accounts already carrying pre-fix hidden debt (dry run by default) |
| SPA `AddCredits` page / `addCreditsColumns.js` | Available / Used / Expired status; delete always removes the row | + "Revoked" status and an "Utilized" column showing **used and revoked separately** (`3` · `7 revoked · 0 remaining`); delete is disabled once `remaining` hits 0, with a tooltip that names *why* rather than blaming the user for an admin's claw-back; the confirm dialog counts against `row.credits` and names the claw-back separately, or says the record no longer counts toward the balance when usage was never computed; the success toast reports the server's message |
| SPA `createPaginatedCrudSlice.deleteItem` | discards the response, returns the bare `id`; omits `skipErrorPopup`, so a component with its own toast shows two popups | returns `{id, ...data}`; the `fulfilled` reducer filters `state.list` on `a.meta.arg` and **skips the removal entirely on `data.removed === false`**, so a partial revoke no longer makes the row vanish and reappear; now passes `{ skipErrorPopup: true }` like `createSlice.js`. `createSlice.js` itself is untouched and still discards the body |
| `credits` counter-entry expiry | n/a | the negative `SOURCE_ADMIN_REVOKED` row **inherits the grant's `has_expiry`/`expiry_date`**. Written non-expiring it outlives the grant it offsets and drives the active total negative — the hidden-debt bug this branch exists to fix |
| `Credits::countsTowardBalance()` | n/a | new row-level mirror of `scopeActive()`. `revokeGrant()` branches on it, **not `hasExpired()`** — the two disagree on a `has_expiry = 1, expiry_date = NULL` row, which was left permanently undeletable behind a false 422 |
| `CreditsAddRequest` | `expiry_date` merely `nullable` | `required_if:has_expiry,true,1`, so that unusable combination can no longer be created |
| `getAvailableCredits()` negative-balance log | n/a | warns when the balance is negative, **throttled to once an hour per user** — it is on a 60s poll per open tab |
| `GET api/user/credit-history` `type` | `purchase` · `admin_assigned` · `revoked` | + `admin_revoked` · `adjustment`, with matching `TYPE_LABEL` entries **and** `&--type-*` SCSS rules in `CreditPage.js`/`.scss` — the badge falls back to the raw string, so a missing label shows a customer a literal `admin_revoked` ([BILLING_CONTEXT](CONTEXT/BILLING_CONTEXT.md)) |
| SPA `DiscountCodeModal.jsx` price-tier chips | every tier selectable regardless of Minimum Order | a tier whose priciest possible order still falls short of Minimum Order renders disabled and is auto-dropped from the selection if Minimum Order is raised past it |

See [CONTEXT/CREDITS_CONTEXT.md](CONTEXT/CREDITS_CONTEXT.md),
[CONTEXT/DISCOUNT_CONTEXT.md](CONTEXT/DISCOUNT_CONTEXT.md), [ERROR_HANDLING.md](ERROR_HANDLING.md),
[POLICIES.md](POLICIES.md), [HELPERS.md](HELPERS.md), [FRONTEND.md](FRONTEND.md),
[TESTING.md](TESTING.md) and [CHANGE_IMPACT_GUIDE.md](CHANGE_IMPACT_GUIDE.md).

**What the generated indexes under `INDEXES/` do not carry**, because they are built from `develop`
and this branch is not indexed: the `credits.original_source` column (`TABLE-007` still reads its
`develop` column count), the three new migrations, the `credits:settle-negative-balances` command, and
the three new `api.php` lang keys. A regeneration on `ws-402` moves the migration count by three, the
command count by one and that table's column count by one. **Routes are untouched**, so
[PUBLIC_ROUTE_AUDIT.md](INDEXES/PUBLIC_ROUTE_AUDIT.md) and the endpoint index are unaffected — the
scanner's `R-B00` staying silent on every run of this branch confirms it.

#### ☠️ `ws-402` carries more than credit revocation — check the scope before reviewing

The branch name describes about two thirds of it. The rest arrived via commits `8810fd9f`, `596a807`
and `816fbd1`, and reviewing "credit revocation" means reviewing these too:

| Also in `ws-402` | What it is | Why it matters |
|---|---|---|
| `2026_09_03_000001_convert_legacy_bracket_placeholders_in_email_templates.php` (199 lines + a 186-line test) | a **second, different** email-template repair | ☠️ **See the collision below — this is now the branch's biggest merge hazard.** |
| `DiscountCodeModal.jsx` (+92) and its test (+71) | price-tier reachability against Minimum Order | a complete discount feature, unrelated to credits — see [DISCOUNT_CONTEXT](CONTEXT/DISCOUNT_CONTEXT.md) |
| `Register.js` (+12) | signup popup copy | small and justified, but it is signup, not credits |
| `CustomTooltip.js` (+3) | a global `wordBreak` change | ⚠️ affects **every tooltip in the SPA**, not just the credits grid |
| four `.scss` files | popup, discount, AddCredits and UserManagement styling | cosmetic, but widens the blast radius |

☠️ **The bracket-placeholder migration now collides with one already on `develop`.** `ws-401` merged,
landing its repair as `2026_09_03_000002_normalize_legacy_bracket_placeholders_in_email_templates`.
`ws-402` carries its **own** implementation of the same repair under a different name and an
**earlier** timestamp, `2026_09_03_000001_convert_…`. They are two different files, so nothing
deduplicates them — merging `ws-402` runs **both**, and the `ws-402` one runs **first**.

That ordering is the wrong way round. `develop`'s `..._000002_normalize_` scopes the rewrite **by
template type**, only ever writing tokens `EmailTemplatePlaceholders::known()` renders for that row's
own type. `ws-402`'s `..._000001_convert_` applies `TOKEN_MAP` **blanket** to every row of both
template tables — it selects `type` but uses it only for logging — so it can write a `test_link`-only
token into an `org_test_link` row, producing a template the app's own validators reject
(`UpdateUserEmailTemplateRequest` and `TestEmailTemplateController` both 422 on an unrecognised
placeholder) and whose `down()` is empty. The type-scoped migration then runs against rows the blanket
one has already mangled.

**Resolve before merging `ws-402`:** drop its `..._000001_convert_` migration and its test, since
`develop` already has the better implementation. This was previously written up here as "if `ws-401`
merges first this is a no-op" — that is now known to be **wrong**, because they are separate files
rather than the same one.

Splitting the branch, or at least renaming it, would make all of this reviewable. `TCV-Frontend`'s
`ws-402` is also **21 commits behind `origin/develop`** (the backend is current); the update is
**conflict-free** — the two sides touch no file in common — but it has not been done.

**Review pass, 2026-09-07 — prose above reflects the post-review branch.** Seven findings were applied
across both repos (the `null` list contract, the `ApiResponse` shapes, the `original_source` integer
cast, the strict null guard in `addCreditsColumns.js`, the delete-thunk response, the
`traceConsumedOrigin()` same-second tiebreak) and backend tests grew 19 → 26 with two new frontend files.
☠️ **One reported finding was rejected as incorrect and deliberately not applied** — "the settle command
grants free credits to healthy accounts", which would have re-opened the very hole the command closes.
It is written up as trap 10 in [CONTEXT/CREDITS_CONTEXT.md](CONTEXT/CREDITS_CONTEXT.md) with the
measurement that disproves it; read that before touching the deficit maths.

---

## Read this first: how to use this KB

**Do not read every file.** Find your task below and read only what it lists.

| Your task | Read, in order | Skip |
|---|---|---|
| Add an API endpoint | [GUIDES/HOW_TO_ADD_NEW_FEATURE.md](GUIDES/HOW_TO_ADD_NEW_FEATURE.md) → [ROUTES.md](ROUTES.md) → [AUTHORIZATION.md](AUTHORIZATION.md) | all indexes |
| Change an existing endpoint | [GUIDES/HOW_TO_TRACE_API.md](GUIDES/HOW_TO_TRACE_API.md) → [INDEXES/API_ENDPOINT_INDEX.md](INDEXES/API_ENDPOINT_INDEX.md) (find the ID) → that controller only | everything else |
| Touch login / registration / tokens | [CONTEXT/AUTH_CONTEXT.md](CONTEXT/AUTH_CONTEXT.md) → [AUTHENTICATION.md](AUTHENTICATION.md) | billing, LMS |
| Touch the colour-vision test flow | [CONTEXT/TEST_EXECUTION_CONTEXT.md](CONTEXT/TEST_EXECUTION_CONTEXT.md) | credits, LMS |
| Touch credits / balances | [CONTEXT/CREDITS_CONTEXT.md](CONTEXT/CREDITS_CONTEXT.md) | LMS, org |
| Touch Stripe / payments | [CONTEXT/BILLING_CONTEXT.md](CONTEXT/BILLING_CONTEXT.md) → [THIRD_PARTY.md](THIRD_PARTY.md) | test flow |
| Touch LMS launch / delivery | [CONTEXT/LMS_CONTEXT.md](CONTEXT/LMS_CONTEXT.md) | billing, credits |
| Touch organisation test URLs | [CONTEXT/ORGANIZATION_CONTEXT.md](CONTEXT/ORGANIZATION_CONTEXT.md) → [CONTEXT/LMS_CONTEXT.md](CONTEXT/LMS_CONTEXT.md) | credits |
| Touch email invitations / resume links | [CONTEXT/INVITATION_CONTEXT.md](CONTEXT/INVITATION_CONTEXT.md) | LMS |
| Touch patients | [CONTEXT/PATIENT_CONTEXT.md](CONTEXT/PATIENT_CONTEXT.md) | billing |
| Touch discount codes | [CONTEXT/DISCOUNT_CONTEXT.md](CONTEXT/DISCOUNT_CONTEXT.md) | test flow |
| Touch reports / exports | [CONTEXT/REPORTING_CONTEXT.md](CONTEXT/REPORTING_CONTEXT.md) | — |
| Add a DB column | [GUIDES/HOW_TO_TRACE_DATABASE.md](GUIDES/HOW_TO_TRACE_DATABASE.md) → [INDEXES/DATABASE_TABLE_INDEX.md](INDEXES/DATABASE_TABLE_INDEX.md) | controllers |
| Assess blast radius | [CHANGE_IMPACT_GUIDE.md](CHANGE_IMPACT_GUIDE.md) | everything else |
| Debug a bug | [GUIDES/HOW_TO_DEBUG.md](GUIDES/HOW_TO_DEBUG.md) → [ERROR_HANDLING.md](ERROR_HANDLING.md) → [LOGGING.md](LOGGING.md) | — |
| Work in the SPA | [FRONTEND.md](FRONTEND.md) → [INDEXES/FRONTEND_ROUTE_INDEX.md](INDEXES/FRONTEND_ROUTE_INDEX.md) | backend layers |
| Work in the marketing site | [WEBSITE.md](WEBSITE.md) → [INDEXES/WEBSITE_ROUTE_INDEX.md](INDEXES/WEBSITE_ROUTE_INDEX.md) | everything backend |
| Any change that crosses the wire | [FULLSTACK_MAP.md](FULLSTACK_MAP.md) → the feature's context pack | — |
| **Review a PR** | [REVIEW/README.md](REVIEW/README.md) → [REVIEW/REVIEW_CHECKLIST.md](REVIEW/REVIEW_CHECKLIST.md) | all indexes |
| Understand the product | [PROJECT_OVERVIEW.md](PROJECT_OVERVIEW.md) → [BUSINESS_FLOW.md](BUSINESS_FLOW.md) | all indexes |

**Rule of thumb:** a context pack (~1–2k tokens) plus one source file beats reading the module.

---

## Read this second: nine facts that cause bugs

These are non-obvious, verified against source, and each one has real consequences. Internalise them
before writing code.

1. **Every unhandled exception becomes a 500.** `app/Exceptions/Handler.php` catches
   `AuthenticationException` and `ValidationException`, then funnels **everything else** through one
   `$request->expectsJson()` branch that returns **500**. `findOrFail()` → 500, not 404. A failed
   `$this->authorize()` → 500, not 403 (fixed for this one exception type by `ws-402`, on `develop`
   since 2026-09-07 — see the delta above). An unmatched route → 500. See [ERROR_HANDLING.md](ERROR_HANDLING.md).
2. **`usertype` skips 3.** `1 = SUPER_ADMIN`, `2 = CUSTOMER`, `4 = ORGANIZATION`. There is no `3`.
   Never iterate `1..n`, never assume contiguity. Identical in all three repos.
3. **Five session-token endpoints still authenticate the caller without checking they own the test.**
   `FlexibleAuthMiddleware` proves you hold *a* valid session; on these five, `unique_test_id` comes
   from the URL and is used unchecked, so the UUID's unguessability is the only thing protecting
   another patient's test. `patients/{id}`, `assignTest`, `getActiveTest`, `sendResumeEmail` and the
   result-certificate download **were** the same shape but are now scoped by reading the unforgeable
   `auth_context` request attribute — don't copy their old pattern.
   See [S-02](SECURITY.md#s-02--test-session-endpoints-never-check-that-the-caller-owns-the-test) and
   [CONTEXT/TEST_EXECUTION_CONTEXT.md](CONTEXT/TEST_EXECUTION_CONTEXT.md).
4. **`POST /api/register` is public and accepts `usertype: 1`.** `UserRequest` validates `usertype`
   against `in:1,2,4` and `account_status` against `in:active,inactive,suspended` — with no restriction
   on who may ask for which. See [SECURITY.md](SECURITY.md#s-01--public-registration-accepts-usertype--1).
5. **There are two credit models on one table.** `App\Models\Credits` (the live one) and
   `App\Models\Credit` (near-dead) both map to `credits`. Balance is **derived**, never stored:
   `SUM(credits) − SUM(credit_consume.credits_used)`, and `getAvailableCredits()` returns the **string**
   `'Unlimited'` for unlimited holders. See [CONTEXT/CREDITS_CONTEXT.md](CONTEXT/CREDITS_CONTEXT.md).
6. **`lms.status:` only gates LMS sessions.** `LmsSessionStatusMiddleware` returns `$next()` untouched
   when no `LmsSession` is attached — so on invitation, resume and Sanctum flows the status argument is
   a **no-op**. See [MIDDLEWARE.md](MIDDLEWARE.md).
7. **`SecureImageService::revokeAccess()` does not revoke anything.** It clears the Laravel cache entry;
   the S3 pre-signed URL stays valid for its full 900 seconds. See [CONTEXT/TEST_EXECUTION_CONTEXT.md](CONTEXT/TEST_EXECUTION_CONTEXT.md).
8. **Sanctum tokens expire after 15 minutes** (`config/sanctum.php` → `'expiration' => 900`). Any
   "why did it log me out?" report starts here, not in the SPA.
9. **`EventServiceProvider` is not registered.** It is absent from `bootstrap/providers.php`, so its
   `$listen` array binds nothing. Event wiring comes from Laravel 11+ **listener auto-discovery** plus
   the two explicit `Event::listen` calls in `LmsServiceProvider`. See [ARCHITECTURE_REALITY.md](ARCHITECTURE_REALITY.md).

---

## Map

### Core
| Doc | What it answers |
|---|---|
| [PROJECT_OVERVIEW.md](PROJECT_OVERVIEW.md) | What the product is, who uses it, what it runs on |
| [ARCHITECTURE_REALITY.md](ARCHITECTURE_REALITY.md) | **What exists vs. what's wired** — read before assuming a layer works |
| [SYSTEM_ARCHITECTURE.md](SYSTEM_ARCHITECTURE.md) | Runtime topology, containers, diagram |
| [BUSINESS_FLOW.md](BUSINESS_FLOW.md) | Register → credits → assign → test → result → report |
| [REQUEST_LIFECYCLE.md](REQUEST_LIFECYCLE.md) | What happens between nginx and a JSON response |
| [FOLDER_STRUCTURE.md](FOLDER_STRUCTURE.md) | Directory layout across all three repos |
| [MODULES.md](MODULES.md) | Module boundaries and who owns what |
| [FEATURE_INDEX.md](FEATURE_INDEX.md) | Feature → files / APIs / tables |
| [FULLSTACK_MAP.md](FULLSTACK_MAP.md) | Backend ↔ SPA ↔ website — cross-repo contracts |
| [FRONTEND.md](FRONTEND.md) | TCV-Frontend — routing, gating, Redux, the test player |
| [WEBSITE.md](WEBSITE.md) | TCV-Website — App Router, proxy routes, theme system |
| [PATHS.md](../PATHS.md) · [config.json](../config.json) | **Single source of repo locations** — the only place a path is defined |

### Interfaces & data
| Doc | What it answers |
|---|---|
| [ROUTES.md](ROUTES.md) / [API_INDEX.md](API_INDEX.md) | Route groups, guarding, the ordering traps |
| [DATABASE.md](DATABASE.md) | Schema conventions, the tables that matter |
| [MODEL_RELATIONSHIP.md](MODEL_RELATIONSHIP.md) | ER diagram, 70 declared relationships |

### Layers
| Doc | Exists? |
|---|---|
| [CONTROLLERS.md](CONTROLLERS.md) | ✅ 38 |
| [SERVICES.md](SERVICES.md) | ✅ 35 — the real home of business logic |
| [REQUESTS.md](REQUESTS.md) | ✅ 28 FormRequest classes |
| [MIDDLEWARE.md](MIDDLEWARE.md) | ✅ 4 (`EnsureTokenIsValid` deleted; `AddRequestId` added) |
| [POLICIES.md](POLICIES.md) | ✅ 3 — ability-gated, with a super-admin trap |
| [EVENTS.md](EVENTS.md) | ✅ 3 events / 4 listeners — wired by discovery + `LmsServiceProvider` + one `AppServiceProvider` hook, not by the provider |
| [JOBS.md](JOBS.md) / [QUEUES.md](QUEUES.md) | ✅ 3 jobs · 6 commands · 1 schedule · `database` driver · worker + scheduler **behind the `workers` compose profile, off by default** |
| [REPOSITORIES.md](REPOSITORIES.md) | ⚠️ 1 only — not a pattern |
| [HELPERS.md](HELPERS.md) | ✅ static classes, **no** global functions |
| [CACHE.md](CACHE.md) / [STORAGE.md](STORAGE.md) | ✅ minimal · S3 for plates |

### Cross-cutting
| Doc | |
|---|---|
| [AUTHENTICATION.md](AUTHENTICATION.md) · [AUTHORIZATION.md](AUTHORIZATION.md) · [SECURITY.md](SECURITY.md) | Sanctum, the four token tiers, roles, known gaps |
| [ENVIRONMENT.md](ENVIRONMENT.md) · [CONFIGURATION.md](CONFIGURATION.md) · [DEPLOYMENT.md](DEPLOYMENT.md) | Env vars, config, Docker/CI |
| [THIRD_PARTY.md](THIRD_PARTY.md) | Stripe, HubSpot, S3, Turnstile, Cornerstone/xAPI |
| [ERROR_HANDLING.md](ERROR_HANDLING.md) · [LOGGING.md](LOGGING.md) | The 500-swallowing handler; what gets logged |
| [TESTING.md](TESTING.md) | PHPUnit (LMS + credits) and the SPA's Jest setup |
| [CHANGE_IMPACT_GUIDE.md](CHANGE_IMPACT_GUIDE.md) | "If I touch this, what breaks?" |
| [CODING_GUIDELINES.md](CODING_GUIDELINES.md) | Real conventions + traps |

### Indexes — generated, never hand-edited
| Index | Rows |
|---|---|
| [API_ENDPOINT_INDEX.md](INDEXES/API_ENDPOINT_INDEX.md) | 163 — ⚠️ **excludes `api/qa/*`**, see [S-20](SECURITY.md#s-20--the-qa-automation-endpoints-are-an-account-takeover-surface-gated-only-by-app_env) |
| [PUBLIC_ROUTE_AUDIT.md](INDEXES/PUBLIC_ROUTE_AUDIT.md) | **17 public** (non-QA environment) |
| [CLASS_INDEX.md](INDEXES/CLASS_INDEX.md) | 210 |
| [METHOD_INDEX.md](INDEXES/METHOD_INDEX.md) | 903 |
| [MODEL_INDEX.md](INDEXES/MODEL_INDEX.md) | 41 |
| [DATABASE_TABLE_INDEX.md](INDEXES/DATABASE_TABLE_INDEX.md) | 53 — ⚠️ its "dropped later" footnotes include `down()`-only drops ([HOW_TO_REGENERATE](GUIDES/HOW_TO_REGENERATE.md)) |
| [FILE_INDEX.md](INDEXES/FILE_INDEX.md) | 210 |
| [EVENT_INDEX.md](INDEXES/EVENT_INDEX.md) | dispatch + listen sites |
| [CONSTANTS.md](INDEXES/CONSTANTS.md) · [FUNCTION_INDEX.md](INDEXES/FUNCTION_INDEX.md) · [ENUM_INDEX.md](INDEXES/ENUM_INDEX.md) | |
| [FRONTEND_ROUTE_INDEX.md](INDEXES/FRONTEND_ROUTE_INDEX.md) | SPA routes **+ role-gating drift** |
| [FRONTEND_API_CALL_INDEX.md](INDEXES/FRONTEND_API_CALL_INDEX.md) | every SPA → API call, matched to its endpoint |
| [CONTRACT_DRIFT.md](INDEXES/CONTRACT_DRIFT.md) | **client calls with no backend route** |
| [WEBSITE_ROUTE_INDEX.md](INDEXES/WEBSITE_ROUTE_INDEX.md) | Next.js pages + proxy routes |

### Context packs (~1–2k tokens each — load one, not the whole KB)
[AUTH_CONTEXT](CONTEXT/AUTH_CONTEXT.md) · [TEST_EXECUTION_CONTEXT](CONTEXT/TEST_EXECUTION_CONTEXT.md) ·
[CREDITS_CONTEXT](CONTEXT/CREDITS_CONTEXT.md) · [BILLING_CONTEXT](CONTEXT/BILLING_CONTEXT.md) ·
[LMS_CONTEXT](CONTEXT/LMS_CONTEXT.md) · [ORGANIZATION_CONTEXT](CONTEXT/ORGANIZATION_CONTEXT.md) ·
[INVITATION_CONTEXT](CONTEXT/INVITATION_CONTEXT.md) · [PATIENT_CONTEXT](CONTEXT/PATIENT_CONTEXT.md) ·
[DISCOUNT_CONTEXT](CONTEXT/DISCOUNT_CONTEXT.md) · [REPORTING_CONTEXT](CONTEXT/REPORTING_CONTEXT.md)

### Review (PR review)
| Doc | What it is |
|---|---|
| [REVIEW/README.md](REVIEW/README.md) | **Start here to review a PR** — the three-pass method |
| [REVIEW/REVIEW_CHECKLIST.md](REVIEW/REVIEW_CHECKLIST.md) | The manual checklist, by area |
| [REVIEW/SECURITY_REVIEW.md](REVIEW/SECURITY_REVIEW.md) | The security gate — what to block on |
| [REVIEW/REVIEW_RULES.md](REVIEW/REVIEW_RULES.md) | What `tools/review.php` checks, and its limits |

Run the automated first pass with `composer review -- --repo=backend --base=develop --head=<branch>`.
Its highest-value check parses `routes/api.php` at **both** revisions and reports routes that
**became public** — guarding here is positional, so no line diff can catch that.

### Guides
[HOW_TO_ADD_NEW_FEATURE](GUIDES/HOW_TO_ADD_NEW_FEATURE.md) · [HOW_TO_UPDATE_EXISTING_FEATURE](GUIDES/HOW_TO_UPDATE_EXISTING_FEATURE.md) ·
[HOW_TO_TRACE_API](GUIDES/HOW_TO_TRACE_API.md) · [HOW_TO_TRACE_FUNCTION](GUIDES/HOW_TO_TRACE_FUNCTION.md) ·
[HOW_TO_TRACE_DATABASE](GUIDES/HOW_TO_TRACE_DATABASE.md) · [HOW_TO_DEBUG](GUIDES/HOW_TO_DEBUG.md) ·
[HOW_TO_REGENERATE](GUIDES/HOW_TO_REGENERATE.md)

---

## ID scheme

Cross-reference by ID instead of restating detail. IDs are **stable** — assigned by sorted name, so
adding a class renumbers only its neighbours.

| Prefix | Example | Index |
|---|---|---|
| `API-nnn` | `API-034` | [API_ENDPOINT_INDEX](INDEXES/API_ENDPOINT_INDEX.md) |
| `CTRL-nnn` `MODEL-nnn` `SVC-nnn` `REQ-nnn` `POL-nnn` `MW-nnn` `EVT-nnn` `LSN-nnn` `JOB-nnn` `CMD-nnn` | `SVC-012` | [CLASS_INDEX](INDEXES/CLASS_INDEX.md) |
| `TABLE-nnn` | `TABLE-009` | [DATABASE_TABLE_INDEX](INDEXES/DATABASE_TABLE_INDEX.md) |
| `FE-nnn` | `FE-018` | [FRONTEND_ROUTE_INDEX](INDEXES/FRONTEND_ROUTE_INDEX.md) |
| `WEB-nnn` | `WEB-004` | [WEBSITE_ROUTE_INDEX](INDEXES/WEBSITE_ROUTE_INDEX.md) |
| `S-nn` | `S-01` | [SECURITY.md](SECURITY.md) findings |

---

## Regenerating

The indexes are **generated, not written**. After changing code:

```bash
cd tcv-ai-knowledge-base
composer install                  # one-time: vendors nikic/php-parser INTO THE KB, not into TCV-Backend
php tools/extract.php             # TCV-Backend AST → .data/facts.json
php tools/extract-clients.php     # TCV-Frontend + TCV-Website scan → .data/clients.json
php tools/render.php              # both → INDEXES/*.md
php tools/verify.php              # links + prose counts
```

Then hand-update only the affected prose docs (context packs, change-impact, feature index).
**Never regenerate the whole KB.** Full detail: [GUIDES/HOW_TO_REGENERATE.md](GUIDES/HOW_TO_REGENERATE.md).

### One caveat you must know

**The route table is parsed statically, not from `artisan route:list`.** TCV-Backend does not vendor its
dependencies, so `artisan` cannot boot from a fresh clone and the extractor walks `routes/api.php` with
the AST instead — tracking the group stack the way Laravel does, and expanding `Route::resource` /
`apiResource`. The source used is always recorded in `facts.json` → `routes_source`, and printed at the
top of [API_ENDPOINT_INDEX.md](INDEXES/API_ENDPOINT_INDEX.md).

If you *do* run `composer install` inside TCV-Backend, `extract.php` automatically prefers
`php artisan route:list --json` — Laravel's own router is authoritative and should be used when available.

---

## Honest scope

Written from a full read of the routes, config, middleware, migrations and the security-critical paths
(auth, test execution, credits, LMS, organisation launch), plus AST extraction of every backend class
and method and a lexical scan of both clients.

- **Generated indexes** cover **100%** of backend classes, methods, tables and routes — mechanically,
  from source.
- **Client indexes are a lower bound.** PHP has no JS parser here, so the SPA scan reads literal
  `axios.<verb>('…')` URLs. A URL assembled at runtime is invisible to it. *Absent from the index* does
  not prove *never called*; *present* rows are real, with real line numbers.
- **Prose** is strongest where the code was traced closely (auth, test execution, credits, LMS,
  organisation signature, error handling) and is deliberately marked **`[not deeply traced]`** where it
  was not (HubSpot sync, PDF generation internals, the Exports classes, the SuperAdmin dashboard
  aggregation) rather than padded with plausible-sounding text.
- **Column lists** are the union across all 126 migrations, so a column added then dropped may still
  show. Verify against a live `DESCRIBE` before relying on it for a migration.
- **[SECURITY.md](SECURITY.md) findings are observations from reading the code**, not the output of a
  pen test or an exploit attempt. Each states exactly what was read and where.
