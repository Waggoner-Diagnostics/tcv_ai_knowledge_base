# TCV — AI Knowledge Base

Single source of truth for **TestingColorVision** across its three repos, built so an AI assistant can
work on the project **without rescanning ~61,700 lines across 506 source files**
(`TCV-Backend/app` 164 · `TCV-Frontend/src` 248 · `TCV-Website` 94).

| | |
|---|---|
| **Repos covered** | `TCV-Backend` (Laravel 12 API) · `TCV-Frontend` (React 18 SPA) · `TCV-Website` (Next.js 15 marketing site) |
| **Branches indexed** | `TCV-Backend`: `tcv-backend-codefix` (`develop` merged in 2026-09-04 — see *tcv-backend-codefix delta* below) · `TCV-Frontend`: `ws-398` · `TCV-Website`: `ws-website-343` — frontend/website are indexed from an **unmerged feature branch**, each exactly `develop` + one commit. `TCV-Website` is **unchanged since the last sync** — `ws-website-343` is the pre-merge parent of `website-integration`'s `ce410d5`, identical tree |
| **First generated** | 2026-08-19 |
| **Code state at sync** | `TCV-Backend` `f96382ea` (2026-09-04) · `TCV-Frontend` `73667c1` (2026-08-28) · `TCV-Website` `2166ec0` (2026-08-26, same tree as `ce410d5`) |
| **Backend scale** | 196 classes/interfaces/traits · 773 methods · 158 API endpoints · 52 tables · 122 migrations |
| **Client scale** | 64 top-level routes · 42 Redux slices (SPA) · 32 marketing pages (website) |

> **Check freshness before trusting prose.** Compare the SHAs above with `git -C <repo> rev-parse --short HEAD`.
> If they differ, the generated indexes may be stale — re-run the generator (see [Regenerating](#regenerating)).

### ✅ ws-398 / ws-392 / ws-417 — merged, now baseline

Three backend branches previously tracked here as "unmerged deltas" (`ws-398` intersex gender,
`ws-392` discount-code unique index, `ws-417` email verification rework) are now **all confirmed present**
in the indexed tree — `tcv-backend-codefix` is `develop` with all three already folded in, plus its own
work (below). Prose elsewhere in this KB that reads "since `ws-417`" or "`ws-398` adds …" is describing
**current baseline behaviour**, not a hypothetical; nothing needs to be read as "if it merges" anymore.
See [CONTEXT/PATIENT_CONTEXT.md](CONTEXT/PATIENT_CONTEXT.md) (gender), [DISCOUNT_CONTEXT](CONTEXT/DISCOUNT_CONTEXT.md)
(unique index) and [CONTEXT/AUTH_CONTEXT.md](CONTEXT/AUTH_CONTEXT.md) / [AUTHENTICATION.md](AUTHENTICATION.md)
(verification rework) for the detail. Route, endpoint, public-route and contract-drift counts already
reflect this — see *Backend scale* above.

### ⚠️ `tcv-backend-codefix` delta — what this branch adds on top of that baseline

Beyond the three merges above, `tcv-backend-codefix` carries its own unmerged security-hardening pass
(2026-09-02, re-verified 2026-09-04). Highlights, each cross-referenced to its finding:

| Area | Before | On `tcv-backend-codefix` |
|---|---|---|
| Session tokens | `test_sessions.session_token` and `organization_patient_sessions.token` stored **plaintext** | **SHA-256 hashed**, matching the LMS tier — see [SECURITY.md "what is done well"](SECURITY.md#what-is-done-well) |
| Patient / test-session ownership | `patients/{id}`, `assignTest`, `getActiveTest`, `sendResumeEmail`, certificate download had **no** ownership check (or one built on forgeable request input) | All read the new unforgeable `auth_context` request attribute — [S-02](SECURITY.md#s-02--test-session-endpoints-never-check-that-the-caller-owns-the-test) (partial), [S-03](SECURITY.md#s-03--sendresumeemail-mails-a-resume-link-for-any-test-to-any-address), [S-14](SECURITY.md#s-14--patientsid-showupdatedestroy-have-no-ownership-scoping), [S-17](SECURITY.md#s-17--assigntest--getactivetest-let-a-session-act-on-another-organizations-patient) — all fixed |
| Rate limiting | none on login/register/password-reset/signature-verify/bulk-invitations/plate-url | 6 named `throttle:` limiters added — but see [S-16](SECURITY.md#s-16--every-client-shares-one-ip-rate-limits-and-ip-restriction-are-both-inert): they currently share one bucket, fix written but held back |
| Migration failure | silent — container serves traffic on a stale schema | `entrypoint.sh` writes a marker; `/up` health check fails loudly (two open bugs in the fix itself — see [DEPLOYMENT.md](DEPLOYMENT.md)) |
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

**☠️ `ws-402` is not indexed** (credit revocation, 2026-09-03/04, branched off the `ws-401` line
— backend and frontend both). Passages flagged `ws-402` describe that branch, not the indexed tree. Read
them as "if ws-402 merges". What changes when it does:

| Area | On the indexed tree (`tcv-backend-codefix`) | On `ws-402` |
|---|---|---|
| `CreditsController::destroy()` | hard-deletes the whole grant row, even the spent part — pushes `granted` below `consumed`, hidden by the `max(0, …)` clamp | `Credits::revokeGrant()` takes back only the **unspent** part; a partly-used grant is kept, with a negative `SOURCE_ADMIN_REVOKED` counter-entry, instead of being deleted |
| `credits.source` values | `0` Manual · `1` Purchased · `2` Revoked | + `3` `SOURCE_ADMIN_REVOKED` · `4` `SOURCE_ADJUSTMENT` (ledger-balancing entry) |
| `credits.original_source` | column does not exist | new nullable column; on a `SOURCE_REVOKED` row it records which underlying grant (Manual/Purchase) funded the test being refunded, traced FIFO via `Credits::traceConsumedOrigin()` |
| `CreditsPolicy::delete()` | `true` only for `source === SOURCE_MANUAL` | also `true` for a `SOURCE_REVOKED` row whose `original_source === SOURCE_MANUAL` — a refund of manually-granted credits is deletable; one tracing back to a purchase never is |
| `GET api/credits` (list) response | grant rows only | each row also carries `used_credits` / `remaining_credits`, from `Credits::getGrantAllocation()` — FIFO spread of consumption + prior claw-backs across active grants |
| `AuthorizationException` (`$this->authorize()` denial) | **500**, per fact #1 above / [ERROR_HANDLING.md](ERROR_HANDLING.md) | **403** — `Handler.php` gains a dedicated branch. Scoped to this one exception type only; `ModelNotFoundException` and the rest are still 500 |
| Artisan commands | — | + `credits:settle-negative-balances {--apply}` — one-time repair for accounts already carrying pre-fix hidden debt (dry run by default) |
| SPA `AddCredits` page / `addCreditsColumns.js` | Available / Used / Expired status; delete always removes the row | + "Revoked" status and an "Utilized" column (`used` / `remaining`); delete is disabled with a tooltip once a grant's `remaining` hits 0; the confirm dialog states the exact used/remaining split |
| SPA `DiscountCodeModal.jsx` price-tier chips | every tier selectable regardless of Minimum Order | a tier whose priciest possible order still falls short of Minimum Order renders disabled and is auto-dropped from the selection if Minimum Order is raised past it |

See [CONTEXT/CREDITS_CONTEXT.md](CONTEXT/CREDITS_CONTEXT.md),
[CONTEXT/DISCOUNT_CONTEXT.md](CONTEXT/DISCOUNT_CONTEXT.md), [ERROR_HANDLING.md](ERROR_HANDLING.md),
[POLICIES.md](POLICIES.md) and [CHANGE_IMPACT_GUIDE.md](CHANGE_IMPACT_GUIDE.md). Adds two migrations
(`2026_09_03_101500_…`, `2026_09_03_140000_…`) and one console command, so a regeneration on `ws-402`
moves the migration count by two and the command count by one.

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
   `$this->authorize()` → 500, not 403 (fixed for this one exception type on the unmerged `ws-402`
   branch — see the delta above). An unmatched route → 500. See [ERROR_HANDLING.md](ERROR_HANDLING.md).
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
| [CONTROLLERS.md](CONTROLLERS.md) | ✅ 34 |
| [SERVICES.md](SERVICES.md) | ✅ 33 — the real home of business logic |
| [REQUESTS.md](REQUESTS.md) | ✅ 24 FormRequest classes |
| [MIDDLEWARE.md](MIDDLEWARE.md) | ✅ 4 (`EnsureTokenIsValid` deleted; `AddRequestId` added) |
| [POLICIES.md](POLICIES.md) | ✅ 3 — ability-gated, with a super-admin trap |
| [EVENTS.md](EVENTS.md) | ✅ 3 events / 4 listeners — wired by discovery + `LmsServiceProvider` + one `AppServiceProvider` hook, not by the provider |
| [JOBS.md](JOBS.md) / [QUEUES.md](QUEUES.md) | ✅ 2 jobs · `database` driver · **no worker in compose** |
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
| [API_ENDPOINT_INDEX.md](INDEXES/API_ENDPOINT_INDEX.md) | 158 |
| [PUBLIC_ROUTE_AUDIT.md](INDEXES/PUBLIC_ROUTE_AUDIT.md) | **15 public** |
| [CLASS_INDEX.md](INDEXES/CLASS_INDEX.md) | 196 |
| [METHOD_INDEX.md](INDEXES/METHOD_INDEX.md) | 773 |
| [MODEL_INDEX.md](INDEXES/MODEL_INDEX.md) | 40 |
| [DATABASE_TABLE_INDEX.md](INDEXES/DATABASE_TABLE_INDEX.md) | 52 |
| [FILE_INDEX.md](INDEXES/FILE_INDEX.md) | 196 |
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
- **Column lists** are the union across all 122 migrations, so a column added then dropped may still
  show. Verify against a live `DESCRIBE` before relying on it for a migration.
- **[SECURITY.md](SECURITY.md) findings are observations from reading the code**, not the output of a
  pen test or an exploit attempt. Each states exactly what was read and where.
