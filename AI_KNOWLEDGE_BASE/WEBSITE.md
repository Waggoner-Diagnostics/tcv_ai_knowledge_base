# TCV-Website — the marketing site

`testingcolorvision.com`. Public content, plus the **login and registration entry point** for the SPA.

| | |
|---|---|
| Stack | Next.js 15 (App Router) · React 19 · Tailwind CSS 4 (`@tailwindcss/postcss`) · GSAP 3 (ScrollTrigger + ScrollSmoother) |
| Scale | 94 source files · ~10k lines · 32 marketing pages · 3 layouts · 4 server API routes · 26 client views |
| Package manager | **yarn** (a `yarn.lock` and a `package-lock.json` both exist — use yarn) |
| Ports | dev `3001`, start `3001` (the SPA runs on `3000`) |
| Build | `output: 'standalone'`, Turbopack root pinned to the repo |
| Env | **`API_URL`** — server-side only, no `NEXT_PUBLIC_` prefix |

Generated view: [INDEXES/WEBSITE_ROUTE_INDEX.md](INDEXES/WEBSITE_ROUTE_INDEX.md)

The repo ships its own `CLAUDE.md`. ⚠️ **It is out of date on one important point**: it states *"No API
routes, no database, static content only."* There are now **four API routes** (below). Trust this page.

---

## Server/client split — the one hard rule

Everything in `/app` is a **Server Component**. Each page imports its interactive half from
`/views/*Client.jsx`, which carries `'use client'`.

```
app/about/page.jsx           →  views/AboutClient.jsx        ('use client')
app/colorblindness/deutan/…  →  views/DeutanClient.jsx       ('use client')
```

**Never add `'use client'` to a file in `/app`.** The generated route index has a `'use client' in /app?`
column precisely so a regression shows up as a `⚠ yes`.

`/components` holds UI shared across pages; `/views` holds the per-page client component. `@/*` maps to
the repo root (`jsconfig.json`) — always import via `@/`, never with relative `../..` traversal.

---

## The four API routes — a server-side proxy, not a backend

```
POST /api/auth       → {API_URL}/api/login
POST /api/register   → {API_URL}/api/register
GET  /api/countries  → {API_URL}/api/countries-with-states
POST /api/logout     → {API_URL}/api/logout
```

They exist to solve **CORS**: the browser only ever talks to the website's own origin, and Next.js
forwards server-side. Consequences worth knowing:

- **No token is ever stored in this app's browser context.** `AuthModal.jsx` posts to `/api/auth` and
  hands off; the SPA at `/app` owns the session.
- **`API_URL` is server-only.** If it is unset, every proxy returns `500 "API_URL is not configured on
  the server."` — a deliberate guard, not a crash.
- Each proxy checks `content-type` before calling `.json()` and returns **502** with the first 400
  characters of the body when the backend replies with HTML instead of JSON. That is the fastest signal
  that the backend is down or misrouted; look for `[/api/auth] Backend returned non-JSON` in the server
  log.
- All four backend targets exist today — verified in
  [INDEXES/CONTRACT_DRIFT.md](INDEXES/CONTRACT_DRIFT.md#tcv-website-proxy-routes).

☠️ **`console.log`/`console.error` in these routes echo the target URL and response fragments** into the
server log. Fine for diagnosis, but they run in production too.

---

## Dev-only rewrites

`next.config.mjs` adds rewrites **only when `NODE_ENV === 'development'`**:

| Source | Destination | Why |
|---|---|---|
| `/app/:path*` | `http://localhost:3000/app/:path*` | serve the React SPA through the same origin |
| `/backend/:path*` | `${API_URL}/:path*` | relative Axios paths (`axiosInstance.get('api/validate-token')`) |
| `/api/:path*` | `${API_URL}/api/:path*` | absolute Axios paths (`axiosInstance.get('/api/users/1')`) |

Local setup: set `REACT_APP_BASE_URL=http://localhost:3001/backend/` in `TCV-Frontend/.env.local` so the
SPA's calls become same-origin.

**In production these rewrites do not exist** — nginx does the routing
(`TCV-Frontend/nginx.conf`, `nginx.integration.conf`). A behaviour that works in dev and 404s in
production is almost always this difference.

Next.js **built-in API routes take precedence over rewrites**, so `/api/auth`, `/api/register`,
`/api/countries` and `/api/logout` hit the local handlers even in dev while everything else under
`/api/*` is forwarded.

---

## Content architecture

| Route family | Layout | Pattern |
|---|---|---|
| `/advice/*` (5) · `/colorblindness/*` (9) | `InfoPageLayout` | props: `breadcrumb`, `badge`, `heading`, `subheading`, `sections[]`, `sidebar` |
| `/test/*` (9) | `app/test/layout.jsx` + `TestsSidebar` | gradient hero |
| `/`, `/about`, `/faq`, `/pricing`, `/distributors`, `/distributors/signup` | root layout | bespoke |

Two React contexts, no Redux/Zustand:

| Context | Purpose |
|---|---|
| `ThemeContext` | 6 preset colour themes; components read `theme.hex` / `.bg` / `.hover` / `.text` and apply them as **inline styles** (values are runtime-dynamic, so Tailwind classes can't express them) |
| `CVDContext` | The colour-vision-deficiency simulator — `CVD_CONDITIONS`, `CVD_SEVERITIES`, `CONDITION_TO_TAB` |

`SmoothScrollProvider` wraps content with GSAP ScrollSmoother **on desktop only** (disabled via
`isTouchDevice()`). The DOM structure `#smooth-wrapper > #smooth-content` **must be preserved** or GSAP
silently stops working.

---

## ☠️ Traps

1. **The repo's own `CLAUDE.md` says there are no API routes.** There are four.
2. **`'use client'` belongs in `/views`, never in `/app`.** The route index flags violations.
3. **Rewrites are dev-only.** Production routing lives in nginx, in a different repo.
4. **`API_URL` has no `NEXT_PUBLIC_` prefix on purpose** — adding one would ship the backend URL to the
   browser and reintroduce the CORS problem the proxies exist to avoid.
5. **Theme colours must be inline styles.** Tailwind cannot compile a runtime value; a `bg-[${hex}]`
   will not exist in the built CSS.
6. **Both `yarn.lock` and `package-lock.json` are committed.** Use yarn; npm will produce a divergent
   tree.
7. **This site and the SPA are separate deployments** on separate ports. A "login is broken" report may
   belong to either — check whether the failure is at `/api/auth` (here) or after redirect to `/app`
   (there).
