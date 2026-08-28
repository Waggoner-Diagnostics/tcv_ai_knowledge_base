# TCV — AI Knowledge Base

Single source of truth for **TestingColorVision** across its three repos, built so an AI assistant can
work on the project **without rescanning ~61,700 lines across 506 source files**
(`TCV-Backend/app` 164 · `TCV-Frontend/src` 248 · `TCV-Website` 94).

| | |
|---|---|
| **Repos covered** | `TCV-Backend` (Laravel 12 API) · `TCV-Frontend` (React 18 SPA) · `TCV-Website` (Next.js 15 marketing site) |
| **Branches indexed** | `ws-392` · `ws-392` · `website-integration` — ⚠️ backend and frontend are indexed from an **unmerged feature branch**, both strictly ahead of `develop`. See *ws-392 delta* below |
| **First generated** | 2026-08-19 |
| **Code state at sync** | `TCV-Backend` `41429345` (2026-08-27) · `TCV-Frontend` `ae74aa7` (2026-08-28) · `TCV-Website` `ce410d5` (2026-08-26) |
| **Backend scale** | 186 classes/interfaces/traits · 710 methods · 176 API endpoints · 52 tables · 110 migrations |
| **Client scale** | 64 top-level routes · 40 Redux slices (SPA) · 32 marketing pages (website) |

> **Check freshness before trusting prose.** Compare the SHAs above with `git -C <repo> rev-parse --short HEAD`.
> If they differ, the generated indexes may be stale — re-run the generator (see [Regenerating](#regenerating)).

### ⚠️ ws-392 delta — read before trusting the discount-code docs

Backend and frontend are indexed from **`ws-392`**, an unmerged feature branch. Both contain all of
`develop` and add only the discount-code work below, so everything else in this KB describes `develop`
unchanged. Exactly one documented behaviour is **reversed** relative to `develop`:

| Area | `develop` | `ws-392` (indexed here) |
|---|---|---|
| `discount_codes.code` | unique index, spanning soft-deleted rows | plain index; **unique dropped** (migration `2026_08_27_000001`, the 110th) |
| Deleting a code | its name is reserved forever | its name is **released for reuse** |
| Uniqueness enforced by | the database | **validation only** — `Rule::unique(…)->whereNull('deleted_at')` on the Store *and* Update requests |
| `GET discount-codes/code-available` | `withTrashed()` | live rows only |

Everything else ws-392 adds is client-side validation in `DiscountCodeModal.jsx`
([FRONTEND.md](FRONTEND.md)). No route, endpoint count, public-route or contract-drift row changes —
the three derived views are byte-identical to the `develop` run.

**If ws-392 is abandoned, the reversal above is the only prose to revert**
([DISCOUNT_CONTEXT](CONTEXT/DISCOUNT_CONTEXT.md), [DATABASE.md](DATABASE.md), [API_INDEX.md](API_INDEX.md))
plus the migration count.

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
   `$this->authorize()` → 500, not 403. An unmatched route → 500. See [ERROR_HANDLING.md](ERROR_HANDLING.md).
2. **`usertype` skips 3.** `1 = SUPER_ADMIN`, `2 = CUSTOMER`, `4 = ORGANIZATION`. There is no `3`.
   Never iterate `1..n`, never assume contiguity. Identical in all three repos.
3. **Test-session endpoints authenticate the caller but never check the caller owns the test.**
   `FlexibleAuthMiddleware` proves you hold *a* valid session; `unique_test_id` then comes from the URL
   and is used unchecked. The UUID's unguessability is the only thing protecting another patient's
   test. See [SECURITY.md](SECURITY.md) and [CONTEXT/TEST_EXECUTION_CONTEXT.md](CONTEXT/TEST_EXECUTION_CONTEXT.md).
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
| [MODEL_RELATIONSHIP.md](MODEL_RELATIONSHIP.md) | ER diagram, 69 declared relationships |

### Layers
| Doc | Exists? |
|---|---|
| [CONTROLLERS.md](CONTROLLERS.md) | ✅ 34 |
| [SERVICES.md](SERVICES.md) | ✅ 32 — the real home of business logic |
| [REQUESTS.md](REQUESTS.md) | ✅ 24 FormRequest classes |
| [MIDDLEWARE.md](MIDDLEWARE.md) | ✅ 4 (one is dead) |
| [POLICIES.md](POLICIES.md) | ✅ 3 — ability-gated, with a super-admin trap |
| [EVENTS.md](EVENTS.md) | ✅ 3 events / 3 listeners — wired by discovery, not by the provider |
| [JOBS.md](JOBS.md) / [QUEUES.md](QUEUES.md) | ✅ 1 job · `database` driver · **no worker in compose** |
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
| [API_ENDPOINT_INDEX.md](INDEXES/API_ENDPOINT_INDEX.md) | 176 |
| [PUBLIC_ROUTE_AUDIT.md](INDEXES/PUBLIC_ROUTE_AUDIT.md) | **20 public** |
| [CLASS_INDEX.md](INDEXES/CLASS_INDEX.md) | 186 |
| [METHOD_INDEX.md](INDEXES/METHOD_INDEX.md) | 710 |
| [MODEL_INDEX.md](INDEXES/MODEL_INDEX.md) | 40 |
| [DATABASE_TABLE_INDEX.md](INDEXES/DATABASE_TABLE_INDEX.md) | 52 |
| [FILE_INDEX.md](INDEXES/FILE_INDEX.md) | 186 |
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
- **Column lists** are the union across all 109 migrations, so a column added then dropped may still
  show. Verify against a live `DESCRIBE` before relying on it for a migration.
- **[SECURITY.md](SECURITY.md) findings are observations from reading the code**, not the output of a
  pen test or an exploit attempt. Each states exactly what was read and where.
