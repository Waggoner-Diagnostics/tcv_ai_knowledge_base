# TCV-Website — the marketing site

`testingcolorvision.com`. Public content, plus the **login and registration entry point** for the SPA.

| | |
|---|---|
| Stack | Next.js 15 (App Router) · React 19 · Tailwind CSS 4 (`@tailwindcss/postcss`) · GSAP 3 (ScrollTrigger + ScrollSmoother) |
| Scale | 95 source files · ~10k lines · 32 marketing pages · 3 layouts · 5 server API routes · 26 client views |
| Package manager | **yarn** — `package-lock.json` was deleted on 2026-08-26, so `yarn.lock` is now the only lockfile |
| Ports | dev `3001`, start `3001` (the SPA runs on `3000`) — ⚠️ **but the deployed container listens on `3000`**, see [Deployment](#deployment--docker-nginx-and-two-github-workflows) |
| Build | `output: 'standalone'`, Turbopack root pinned to the repo |
| Env | **`API_URL`** (server-side only, no `NEXT_PUBLIC_` prefix) · **`NEXT_PUBLIC_DASHBOARD_URL`** (client, used by `Pricing.jsx`). Both are documented in `.env.example`, added 2026-08-26 |
| Deploys as | Docker image → ECR → `docker compose` on a self-hosted runner, behind **this repo's own `nginx.conf`** |

Generated view: [INDEXES/WEBSITE_ROUTE_INDEX.md](INDEXES/WEBSITE_ROUTE_INDEX.md)

### Branch state — indexes and prose both describe `website-integration` (2026-09-12)

⭐ **The three-tier split is gone.** The generated indexes are now synced from `website-integration`
at `deb667a`, the same branch this prose describes, so index and prose no longer disagree. The old
`3ec94ec` tier — no Docker, no nginx, no workflows — is history; every generated view now includes the
deployment story below.

| Branch | State at 2026-09-12 |
|---|---|
| `develop` (`feabac8`) | the container/CI stack plus the copy and distributor-form work |
| `website-integration` (`deb667a`) | ⭐ **indexed.** `develop`, **plus** `uat-prod.yml` with its approval gate, a slimmed `non-prod.yml`, and app commits not on `develop` — `c308caf` (signup success view), `41c0383` (comparison table removed), `edc69f8` (plate counts), the merged `ws-website-373`, and the distributor→HubSpot work |

`website-integration` is a strict superset of `develop` — `git log website-integration..develop` is empty
(verified 2026-09-12: `develop` is **19 commits behind** and has nothing of its own).
**It is the branch the deployment workflows are shaped around, so treat it, not `develop`, as the
integration truth for anything infrastructural.**

⭐ **`ws-website-373` has merged** into this line (PR #7, `05e66ce`) — the `AuthModal` corner fix below
is shipped, and the strip is painted on the panel itself on `website-integration`.

⚠️ **This is the one repo of the three indexed off `develop`.** The backend and frontend indexes come
from their `develop`; the website's come from `website-integration`, because that is where its
deployable truth lives. See the README's branch rule before extending this to any other repo.

The repo ships its own `CLAUDE.md`. **The "no API routes, static content only" claim was corrected on
2026-08-26** — it describes the proxy routes accurately, though it predates the fifth
(`/api/distributor-enquiry`). Two caveats remain: it points at
`../docs/07-website.md`, a **legacy doc set outside this KB** that nothing here maintains, and it is
still not the authority. Trust this page.

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

## The five API routes — a server-side proxy, not a backend

```
POST /api/auth                 → {API_URL}/api/login
POST /api/register             → {API_URL}/api/register
GET  /api/countries            → {API_URL}/api/countries-with-states
POST /api/logout               → {API_URL}/api/logout
POST /api/distributor-enquiry  → {API_URL}/api/distributor-enquiry   ← added on website-integration
```

They exist to solve **CORS**: the browser only ever talks to the website's own origin, and Next.js
forwards server-side. Consequences worth knowing:

- **No token is ever stored in this app's browser context.** `AuthModal.jsx` posts to `/api/auth` and
  hands off; the SPA at `/app` owns the session.
- **`API_URL` is server-only.** If it is unset, every proxy returns `500 "API_URL is not configured on
  the server."` — a deliberate guard, not a crash.
- Each proxy checks `content-type` before calling `.json()` and returns **502** when the backend replies
  with HTML instead of JSON, logging the first 400 characters. That is the fastest signal that the
  backend is down or misrouted; look for `[/api/auth] Backend returned non-JSON` in the server log.
  ⚠️ **`/api/register` alone also puts the upstream status and body into the 502 *response* — but only
  when `NODE_ENV === 'development'`** (added 2026-08-26). Production keeps the generic message. Do not
  copy that branch into the other three proxies without the same `isDev` guard.
- All five backend targets exist today — verified in
  [INDEXES/CONTRACT_DRIFT.md](INDEXES/CONTRACT_DRIFT.md#tcv-website-proxy-routes);
  `/api/distributor-enquiry` resolves to `API-030`.

⭐ **`/api/distributor-enquiry` (2026-09-12) follows the pattern exactly** — same `API_URL` guard, same
`content-type` check returning 502 with the first 400 characters logged, and a generic 500 on throw. It
correctly does **not** copy `/api/register`'s dev-only body echo. It replaced a `mailto:` link in
`views/DistributorSignupClient.jsx`, so the enquiry now reaches HubSpot through the backend
(`DistributorController@submit`, rate-limited `10/min`) instead of opening the visitor's mail client.
Nothing is stored on the website side. `components/Header.jsx` gained the Distributors entry in the same
line of work.

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

**In production these rewrites do not exist** — nginx does the routing. ⭐ **Corrected 2026-09-09:** that
nginx is now **this repo's own `nginx.conf`**, not `TCV-Frontend/nginx.conf` / `nginx.integration.conf`
as this page said while the website had no deployment of its own. The website container is the **edge**;
it proxies to the SPA, not the other way round. See [Deployment](#deployment--docker-nginx-and-two-github-workflows).

A behaviour that works in dev and 404s in production is still almost always this difference — but the
rewrite table above and the nginx `location` blocks below are now **two independently maintained copies
of the same routing decisions**, so check both.

Next.js **built-in API routes take precedence over rewrites**, so `/api/auth`, `/api/register`,
`/api/countries` and `/api/logout` hit the local handlers even in dev while everything else under
`/api/*` is forwarded.

---

## Deployment — Docker, nginx and two GitHub workflows

New since the `3ec94ec` sync (`49571b1` onward, 2026-09-05..09). The website went from "no deployment of
its own" to **owning the front door for the whole product**.

### The container

`Dockerfile` — three stages on `node:22-alpine`: `deps` (`yarn install --frozen-lockfile` behind a
BuildKit cache mount), `builder` (`yarn build`), `runner` (copies `public`, `.next/standalone` and
`.next/static`, boots `node server.js` under `tini`). This is what `output: 'standalone'` exists for.

`docker-compose.yml` — two services on the **external** `tcv_network`, the same network the backend
compose file uses:

| Service | Container | Notes |
|---|---|---|
| `nextjs` | `frontend-next-tcv` | image pulled from ECR by tag; **`expose: 3000`**, never published |
| `nginx` | `frontend-nginx-tcv` | `nginx:1.27-alpine`, host **80** → 80, mounts `./nginx.conf` read-only |

☠️ **The container listens on `3000`, not the `3001` `package.json` uses.** `ENV PORT=3000` in the
Dockerfile and `PORT: "3000"` in compose. `3001` is a local-dev convention only — it exists so the SPA
can hold `3000` on a developer's machine. Do not "fix" one to match the other.

### `nginx.conf` — the website is the edge, and the routing order is load-bearing

Two upstreams: `nextjs_upstream` → `frontend-next-tcv:3000`, `react_upstream` → `frontend-app-tcv:80`.
`location` blocks, in the order nginx resolves them:

| Match | Goes to | Why it is where it is |
|---|---|---|
| `~ ^/api/(auth\|register\|countries\|logout)(/\|$)` | **Next.js** | a regex, so it beats the `/api/` prefix block |
| `/api/` | React nginx → Laravel | everything else |
| `/app` | React nginx | the SPA |
| `/images/` | Next.js, **`404` → `@images_fallback`** → React nginx | marketing images live in Next's `/public`; backend-served ones do not |
| `/_next/static/` | Next.js | `max-age=31536000, immutable` — safe, the names are content-hashed |
| `/` | Next.js | `proxy_buffering off`, **required for App Router RSC streaming** |

☠️ **Adding a file under `app/api/` is a two-repo-file change.** The regex above is a hardcoded list of
four names. A new route handler that is not added to it falls through to the `/api/` block and is
proxied to **Laravel**, which answers `404` — in production only. Dev works, because there the Next.js
handler wins by framework precedence and no nginx is involved. The config says so in a comment; believe
it.

⚠️ `set_real_ip_from 0.0.0.0/0` with `real_ip_header X-Forwarded-For` **trusts a client-supplied
`X-Forwarded-For` from anywhere**. The file carries its own `# restrict in production` comment, still
unactioned. The security headers (`X-Frame-Options`, `nosniff`, HSTS, `Referrer-Policy`) are set; the
**CSP line is commented out**.

Access logs are JSON (`json_combined`) and health-check noise is filtered out by a `map` chain — `/health`,
`/api/health` and any `ELB-HealthChecker` user agent are excluded. So **a missing log line is not
evidence a request never arrived.**

### The two workflows

Both are `workflow_dispatch`-only, both run every real step on **self-hosted runners**, one per
environment, and both build the image on the deploy runner rather than a hosted one.

| | `non-prod.yml` — `TCV-WEBSITE(Dev/QA)` | `uat-prod.yml` — `TCV-WEBSITE(uat/prod)` |
|---|---|---|
| Environments | `dev` · `qa` | `uat` · `prod` |
| Region | `us-east-1` | ⚠️ **`us-east-2`** |
| Runners | `tcv-website-dev` / `-qa` | `tcv-website-uat` / `-prod` |
| Approval | none | ⭐ `trstringer/manual-approval@v1`, **1 approval from `Dynamisch-Thiru`**, as a GitHub issue |
| Default branch input | `develop` | ☠️ **`uatelop`** |

Jobs are the same six in both: detect-environment → build-and-push → check-disk-space → deploy →
notification / failure-notification.

☠️ **`uat-prod.yml`'s `branch_to_deploy` defaults to `uatelop`, which is not a branch.** Almost certainly
a typo for `develop`. Dispatching uat or prod without editing that field fails at checkout — noisily, so
it is a nuisance rather than a hazard, but it means **nobody has yet run that workflow on its defaults.**

⭐ **This is the production pipeline [DEPLOYMENT.md](DEPLOYMENT.md) says does not exist.** That statement
is still true of `TCV-Backend`; it is no longer true of the product.

### How configuration actually reaches the app — read this before debugging an env var

One secret, `FRONTEND_ENV`, holds the whole `.env` body, and it is consumed **twice, for two different
purposes**:

1. **Build job** — written to `.env` in the checkout *before* `docker build`, then deleted in an
   `if: always()` step. `yarn build` reads it, so this is the only point at which a **`NEXT_PUBLIC_*`**
   value can be inlined into the client bundle.
2. **Deploy job** — written to `/opt/workspace/tcv-website/.env` (`chmod 600`) with `ECR_REGISTRY`,
   `ECR_REPOSITORY` and `IMAGE_TAG` appended, for `docker compose` to interpolate. This is where
   **`API_URL`** comes from, correctly, at runtime.

☠️ **`NEXT_PUBLIC_*` is baked at build time and cannot be changed by redeploying.** Compose passes
`NEXT_PUBLIC_API_URL` in the `nextjs` service environment, and that line **does nothing at all**: no
source file reads it, it is absent from `.env.example`, and a runtime env var cannot reach an
already-compiled client bundle regardless. Meanwhile `NEXT_PUBLIC_DASHBOARD_URL`, which *is* read
(`Pricing.jsx`), is **not** in compose — and does not need to be, because it was inlined during the
build. Changing it requires a **rebuild**, not a restart.

⚠️ Trap 4 below still stands, and the stray `NEXT_PUBLIC_API_URL` line is exactly the mistake it warns
about, caught before it did any harm. Delete the line rather than wiring it up.

⚠️ **There is no `.dockerignore`.** `COPY . .` in the builder stage therefore copies `.git` and the
build-time `.env` into the build context and the builder layer. Neither reaches the runtime image —
that stage copies only `public`, `.next/standalone` and `.next/static` — so the secret does not ship,
but the build context is far larger than it needs to be, and the margin is thinner than it looks.

The image tag is the **8-character commit SHA**. Redeploying the same commit reuses the tag, so
`docker compose pull` is a no-op; to force a rebuild you need a new commit.

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

## Plate counts — the numbers QA already corrected once (`edc69f8`, 2026-09-08)

The same physical test is described on four pages, and they disagreed with each other and with the
product for as long as the site has existed. `947ddc6` spotted the conflict and deliberately **did not
guess**; `edc69f8` fixed it after QA confirmed the real figures. **These are the confirmed numbers — do
not re-derive them from page copy:**

| Section | Scored plates | Note |
|---|---|---|
| General / adult | **25** | plus **1 demo plate** = **26 delivered**, hence "PiPiC **26**-Plate Edition" |
| Protan | 32 | confirmed correct, never changed |
| Deutan | 32 | confirmed correct, never changed |
| Tritan | 12 | confirmed correct, never changed |
| **Site-wide total** | **101** | `25 + 32 + 32 + 12`, plus the one demo plate |

Pages carrying these figures: `TestOverviewClient.jsx`, `TestCredibilityClient.jsx`,
`TestExamplesClient.jsx`, `TestInstructionsClient.jsx`. **Change one, change all four.**

☠️ **One sentence was knowingly left wrong.** The Instructions page still says a user *"must correctly
identify 26 of the 29 plates"* — a pass threshold expressed against the **old** 29-plate count. It was
left alone because the corrected threshold is a business decision, not arithmetic: `26/29` is not
obviously `22/25`. **Do not silently rescale it.** Get the number from QA or the business first.

⚠️ The earlier `24-Plate Edition` label was not a different edition — it was the same wrong count under
another name, and is now `26-Plate Edition` everywhere.

Two more content claims are flagged unverified and still live on the FAQ page: the **"U.S. Army"** and
**"Kaiser Permanente"** customer references. Treat them as unconfirmed until someone with authority says
otherwise.

---

## ☠️ Traps

1. **The repo's own `CLAUDE.md` says there are no API routes.** There are four.
2. **`'use client'` belongs in `/views`, never in `/app`.** The route index flags violations.
3. **Rewrites are dev-only.** Production routing lives in nginx — **this repo's own `nginx.conf` since
   `49571b1`**, no longer the SPA's. The two are separate copies of one routing decision.
4. **`API_URL` has no `NEXT_PUBLIC_` prefix on purpose** — adding one would ship the backend URL to the
   browser and reintroduce the CORS problem the proxies exist to avoid. ⚠️ `docker-compose.yml` passes a
   stray, unread `NEXT_PUBLIC_API_URL`; delete it rather than wiring it up.
5. **Theme colours must be inline styles.** Tailwind cannot compile a runtime value; a `bg-[${hex}]`
   will not exist in the built CSS.
6. **Both `yarn.lock` and `package-lock.json` are committed.** Use yarn; npm will produce a divergent
   tree.
7. **This site and the SPA are separate deployments** on separate ports. A "login is broken" report may
   belong to either — check whether the failure is at `/api/auth` (here) or after redirect to `/app`
   (there). **This site's nginx is now in front of both**, so it is also the first place to look.
8. ☠️ **A new file under `app/api/` needs a matching name in `nginx.conf`'s regex `location`** or it
   404s in production while working perfectly in dev. Four names are hardcoded there today.
9. ☠️ **`NEXT_PUBLIC_*` values are frozen at image-build time.** Changing one and redeploying the same
   image does nothing. Only `API_URL` and friends are live at runtime through compose.
10. ⚠️ **The deployed container listens on `3000`, not `3001`.** `3001` is a dev-only convention that
    keeps `3000` free for the SPA locally.
11. ☠️ **Plate counts appear on four pages and one of them is knowingly still wrong** — the Instructions
    page's `26 of the 29` pass threshold. See the plate-count section; do not rescale it yourself.
12. ☠️ **`AuthModal`'s top gradient strip is a *background*, not a child element** — `ws-website-373`
    (2026-09-09, unmerged). See below; do not "simplify" it back into a child `<div>`.

---

## The auth modal's signup success view — `c308caf` (on `website-integration`, not on `develop`)

`SignUpForm` renders its own "Account created!" panel on success, but the **parent** owns the chrome
around it. Until `c308caf` the Sign In / Sign Up tab switcher and the "Create your account" headline
stayed on screen above that confirmation, so a finished signup still looked like an open form.

The parent now tracks `signupDone` and hides both. Three things reset it, and **all three are needed** —
`SignUpForm` is unmounted by the tab switch, so nothing inside it can do this:

| Reset | Why |
|---|---|
| `useEffect` on `mode` | modal reopened in a different mode |
| the tab buttons' `onClick` | user clicked back to Sign In |
| `SignUpForm`'s `onSwitch` | the success panel's own "Go to Sign In" button |

`SignUpForm` reports success upward through a new optional `onSuccess?.()` prop. It is optional on
purpose — the form still works standalone, showing its panel with the chrome left alone.

⚠️ **The countries fetch is now guarded with `Array.isArray`.** `/api/countries` returns an error
*object* when the backend proxy fails, and the old `d?.data ?? d ?? []` happily passed that object
through to `SelectField`, where `options.map` threw. The guard means **a failed country fetch now
degrades to an empty dropdown instead of a crash** — quietly. If country and state are empty, check the
proxy's 502 path, not the component.

---

## The auth modal's rounded clip — `ws-website-373` (merged into `website-integration`, PR #7)

⚠️ **This is the *website* branch `ws-website-373`, not the backend `ws-373`** that
[INVITATION_CONTEXT](CONTEXT/INVITATION_CONTEXT.md) and [AUTH_CONTEXT](CONTEXT/AUTH_CONTEXT.md) flag for
the email-template work. The two are unrelated despite the shared number; `TCV-Website` carries local
branches under **both** names, so check which repo you are in before reading a `ws-373` note. Same
pairing as `ws-343` / `ws-website-343`.

`components/AuthModal.jsx`'s panel is `rounded-3xl overflow-hidden`, and its decorative top strip used to
be a child element relying on that clip:

```jsx
<div className="h-1.5 w-full" style={{ background: `linear-gradient(90deg, …)` }} />
```

**A rounded `overflow` clip is dropped on a composited layer, and this panel forces compositing twice
over** — `animation: 'authModalIn … both'` leaves a transform applied *after* the entry animation
finishes (fill mode `both`), and the overlay behind it sets `backdropFilter: 'blur(6px)'`. The strip
therefore painted as a square bar straight across the modal's rounded top corners. QA reported it as
"the top blue border line is misaligned and overlaps the modal corner radius".

The strip is now painted on the panel itself, which cannot detach from its own background:

```jsx
className="… rounded-3xl overflow-hidden pt-1.5"   // pt-1.5 holds the 6px the child used to occupy
style={{
  backgroundImage: `linear-gradient(90deg, ${theme.hex}, ${theme.hex}88, ${theme.hex})`,
  backgroundSize: '100% 0.375rem',
  backgroundRepeat: 'no-repeat',
}}
```

An element's background is always clipped to its own border box **including the radius**, on the same
paint layer, so no compositing path can square it off. The strip now tucks into the corner curve, which
is what the design intended.

**Generalise this, don't just remember the one case:** any decorative edge — strip, border, ribbon,
image bleed — placed as a *child* of a rounded container that animates a transform or sits under a
`backdrop-filter` is at risk of the same square-corner artifact. Reach for a background, an inset
element with its own matching radius, or a pseudo-element before reaching for `overflow-hidden`.
