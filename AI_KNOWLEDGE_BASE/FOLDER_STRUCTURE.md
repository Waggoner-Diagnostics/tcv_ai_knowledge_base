# Folder Structure

Three repos, siblings under one parent alongside this KB ([PATHS.md](../PATHS.md)).

```
<parent>/
├── tcv-ai-knowledge-base/   ← this KB
├── TCV-Backend/
├── TCV-Frontend/
└── TCV-Website/
```

---

## TCV-Backend (Laravel 12)

```
app/
├── Console/Commands/          1 file   UploadTestPlates
├── Events/                    3        TestCompleted · TestSectionCompleted · UserPasswordSet
├── Exceptions/Handler.php     1        ⚠️ collapses every non-auth/validation error to 500
├── Exports/                   3        maatwebsite/excel
├── Helpers/                   2        ApiResponse · TestHelper  (static classes, NOT autoloaded functions)
├── Http/
│   ├── Controllers/          34        + Auth/ = laravel/ui scaffolding, UNUSED
│   ├── Middleware/            4        1 global, 2 aliased, 1 dead
│   └── Requests/             24        FormRequests — validation lives here
├── Jobs/                      1        ProcessLmsDeliveryJob
├── Listeners/                 3
├── Mail/                      1        VerifyEmail (most mail bypasses this)
├── Models/                   40
├── Notifications/             3
├── Policies/                  3
├── Providers/                 4        ⚠️ only 3 are registered — EventServiceProvider is not
├── Repositories/              1        EmailTemplateRepository — not a pattern
├── Rules/                     1        TurnstileToken
├── Services/                 32        ⭐ business logic
│   ├── Audit/                          AuditLogger · PricingAuditService
│   ├── Lms/                 11         Contracts/ · Providers/ · the delivery + launch services
│   ├── PaymentProviders/     3         Interface · Base · Stripe
│   └── Reports/              2
├── Support/                   2        HttpStatus · TestConstants
└── Traits/                    1        Searchable

bootstrap/app.php              ← routing, middleware aliases, exception binding  (no Kernel.php in L12)
bootstrap/providers.php        ← the provider list. EventServiceProvider is absent.
config/                        ← auth, sanctum, services, filesystems, …
database/migrations/         109
resources/lang/en/api.php      ← 78 message keys used by ApiResponse
resources/views/emails/        ← dynamic-template blade
routes/api.php                 ← 264 lines, three guard zones
routes/web.php                 ← 2 routes
tests/Feature/Lms/ + Credits/  ← the only real coverage
Dockerfile · entrypoint.sh · nginx.conf · docker-compose*.yml
```

### Where to start for a given change
| Change | Start at |
|---|---|
| Endpoint behaviour | `app/Services/…`, not the controller |
| Validation | `app/Http/Requests/…` |
| Auth / guards | `app/Http/Middleware/FlexibleAuthMiddleware.php` + `routes/api.php` |
| Schema | `database/migrations/` + [INDEXES/DATABASE_TABLE_INDEX.md](INDEXES/DATABASE_TABLE_INDEX.md) |
| Wiring (providers, aliases, exception binding) | `bootstrap/app.php` + `bootstrap/providers.php` |

---

## TCV-Frontend (React 18, CRA)

```
src/
├── apis/                 AxiosInstance.js ⭐ (token priority, 401 handling) + 5 helpers
├── components/           shared UI, re-exported through components/index.js
├── constants/            dataObjects.js (USER_ROLES) · routeConfig.js ⭐ (role gating)
│                         testConfig.js · testPlates.js · messages.js
│                         ⚠️ testDescriptionConstants.js — 1,307 lines of DEAD pasted code
├── hooks/               15
├── pages/                one folder per feature; UserPannel/ (sic) holds the test player
├── redux/
│   ├── store.js          68 reducers
│   └── slices/           17 folders · 40 slice files · 2 generic factories
├── router/
│   ├── Router.js         ⭐ intersects routes × role; USER_PANEL_WITH_HEADER lives here
│   └── routes/           publicRoutes · protectedRoutes · sharedRoutes
├── services/             errorHandler.js (singleton) · paymentProviders/ · validations.js
├── utils/                columns/ (react-table defs) · validationSchema/ · misc.js (lazyWithRetry)
│                         ⚠️ calculateColorVisionResult.js — DEAD, ported to the backend
└── scss/ styles/ assets/
```

Note the folder is spelled **`UserPannel`** (two n's). Grep accordingly.

---

## TCV-Website (Next.js 15 App Router)

```
app/                  route tree — Server Components only
├── api/{auth,register,countries,logout}/route.js   ⭐ server-side proxies to the backend
├── layout.jsx · page.jsx · not-found.jsx
├── advice/*        (5 pages)
├── colorblindness/* (9 pages + layout)
└── test/*          (9 pages + layout)
components/          26  shared UI (Header, Footer, AuthModal, InfoPageLayout, …)
views/               26  *Client.jsx — the 'use client' half of each page
context/              2  ThemeContext · CVDContext
public/images/
next.config.mjs      ⚠️ dev-only rewrites for /app/*, /backend/*, /api/*
jsconfig.json        @/* → repo root
```

**The `/app` ↔ `/views` split is the rule**: pages in `/app` are Server Components; each imports one
`*Client.jsx` from `/views`. Never put `'use client'` in `/app`.

---

## Where documentation lives

**All TCV documentation lives in this KB.** The two client repos each ship a `CLAUDE.md` that predates
it; those files are left in place but are **not** the authority. (The website's no longer claims the site
has no API routes — that was corrected upstream on 2026-08-26 — but it links to a legacy `docs/` folder
that sits outside this KB and is not maintained here.) Do not add new doc files to the code repos;
extend the KB instead.
