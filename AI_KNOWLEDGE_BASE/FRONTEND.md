# TCV-Frontend — the React SPA

The clinician/admin portal **and** the patient test player. Served under `/app`
(`package.json` → `"homepage": "/app"`), which is why `PUBLIC_URL` shows up in path logic.

| | |
|---|---|
| Stack | React 18 · Redux Toolkit · React Router **v7** · Axios · Bootstrap 5 / React-Bootstrap · Formik + Yup · Stripe.js · Sass |
| Scale | 252 source files · ~34k lines · 64 top-level routes · 40 Redux slices · 16 hooks |
| Env | `REACT_APP_BASE_URL`, `REACT_APP_STRIPE_PUBLIC_KEY`, `REACT_APP_TURNSTILE_SITE_KEY`, `PUBLIC_URL` |
| Build | `react-scripts` (CRA 5) |

The repo also ships its own `CLAUDE.md`. It is accurate on stack and layout; **this KB is the authority
on cross-repo contracts, gating drift and API mismatches**, which that file does not cover.

Generated views: [INDEXES/FRONTEND_ROUTE_INDEX.md](INDEXES/FRONTEND_ROUTE_INDEX.md) ·
[INDEXES/FRONTEND_API_CALL_INDEX.md](INDEXES/FRONTEND_API_CALL_INDEX.md) ·
[INDEXES/CONTRACT_DRIFT.md](INDEXES/CONTRACT_DRIFT.md)

---

## Routing is a two-key lock

A protected page renders **only if it appears in both places**:

| File | Question it answers |
|---|---|
| `src/router/routes/protectedRoutes.js` | Does the route exist? |
| `src/constants/routeConfig.js` → `RouteConfig[role].parentRoutes` | May this role see it? |

`Router.js` intersects them at runtime:

```js
const allowedRoutes = getAllowedRoutes(userType);
protectedRoutes
  .filter(route => allowedRoutes?.parentRoutes?.includes(route.path))
  .map(route => ({ ...route, children: route.children?.filter(c =>
      allowedRoutes?.childRoutes?.[route.path]?.includes(c.tab)) }));
```

**Adding a page is a two-file change.** Register it *and* grant it. Miss the second and the page silently
never renders — no error, no 404, it is simply filtered out of the route table.

The intersection, and every place the two files disagree, is computed for you in
[INDEXES/FRONTEND_ROUTE_INDEX.md](INDEXES/FRONTEND_ROUTE_INDEX.md), which lists:
- **routes registered that no role can reach** (dead pages), and
- **paths granted in `RouteConfig` that no route defines** (dead grants).

Read that table rather than `routeConfig.js` when answering "can this role see X?".

**Current drift** (regenerated each run — check the index, not this paragraph, after any route change):

| Registered but unreachable by every role | Granted to a role but no such route exists |
|---|---|
| `/user-panel/start-test/instruction` → `InstructionPage` | `/logout` · `/profile` · `/page-categories` |
| `/user-panel/start-test/test` → `TestPage` | `/user-panel/instruction` · `/user-panel/test` · `/user-panel/result/:uniqueTestId` |

The pattern is visible in the two columns: `RouteConfig` grants `/user-panel/instruction` and
`/user-panel/test`, while `protectedRoutes.js` registers `/user-panel/start-test/instruction` and
`/user-panel/start-test/test`. **The two files disagree about the same two pages** — the parameterised
variants (`.../instruction/:uniqueTestId`, `.../test/:uniqueTestId`) are granted correctly, which is why
the flow works and the bare paths are simply dead.

### Route kinds
| Kind | File | Wrapper |
|---|---|---|
| public (21) | `publicRoutes.js` | `PublicRoute` |
| protected (42 + 10 tabs) | `protectedRoutes.js` | `ProtectedRoute`, inside `AuthLayout` or `UserPanelLayout` |
| shared (1) | `sharedRoutes.js` | `SharedRoute` |

`USER_PANEL_WITH_HEADER` in `Router.js` is a **hard-coded array of paths** that decides which protected
routes get the persistent `UserPanelLayout` (Header rendered once, children via `<Outlet>`). A new
`/user-panel/*` page must be added there too, or it renders without the header — a **third** list to keep
in sync.

Every lazy route uses `lazyWithRetry` (`src/utils/misc.js`), which reloads the page on `ChunkLoadError`
— the standard stale-chunk-after-deploy fix. Use it for new lazy imports.

---

## Auth in the client

`src/pages/AuthInit.js` runs once on mount: hydrates Redux from `localStorage.auth`, then calls
`GET api/validate-token` to confirm freshness, redirecting to `/login` if not.

`src/apis/AxiosInstance.js` attaches a token in **priority order**:

1. `sessionStorage.impersonateToken`
2. `?impersonateToken=` query param → stored into sessionStorage, then used
3. `localStorage.test_invitation_session_token` — only on `/test-invitation/*`, `/organization/*`, `/test/resume/*`
4. `localStorage.auth.token`, falling back to `localStorage.token`

Those same three prefixes define `isPublicRoute()`, which is what stops a 401 from bouncing a patient
mid-test to `/login`. **Adding a new unauthenticated patient-facing path means adding it here too** —
otherwise the first 401 destroys the session.

☠️ **Backend Sanctum tokens live 15 minutes** ([AUTHENTICATION.md](AUTHENTICATION.md)). There is no
refresh flow. Long admin sessions get logged out; that is the backend's setting, not a client bug.

---

## The Patients menu password prompt is client-side only

Clicking **Patients** in the header is not a plain navigation. `handlePatientsClick`
(`src/pages/UserPannel/Header/Header.js`) opens `components/PasswordVerificationModal.js`, which POSTs
`api/verify-password` (`API-176`) through `slices/auth/passwordVerificationSlice.js`; only on a 200 does
the header navigate to `/user-panel/patients`. The navigate is deferred to the modal's `onExited` via a
`pendingNav` flag, so the route changes *after* the exit animation — move it back into `onSuccess` and
the modal unmounts mid-transition.

**Nothing else in either repo knows about this gate:**

| | |
|---|---|
| `ProtectedRoute` | never reads `state.passwordVerification` |
| `AuthController::verifyPassword()` | `Hash::check` → 200, else 422 `api.incorrect_password`. No session flag, no token ability, nothing persisted |
| every other route into the section | `RegisteredPatientsTab.js` → `/user-panel/patient-tests/:id`, `PatientTestList.js` → `/patients/edit/:id`, `AddPatient.js` and `StartTestConfirmPage.jsx` all `navigate()` straight in |
| typing the URL, back, forward | loads the page directly |

So it is a shoulder-surfing speed bump on **one click path**, not access control — and the endpoints
behind it have their own ownership gap
([S-14](SECURITY.md#s-14--patientsid-showupdatedestroy-have-no-ownership-scoping)). `isVerified` is
reset every time the modal closes, so it is a one-shot handshake between modal and header, never
session state. Do not build authorisation on it.

**ws-399** (2026-08-28 — committed, *not yet merged or deployed*) stops the prompt re-firing from
inside the section:

| Click **Patients** while… | before | ws-399 |
|---|---|---|
| outside the section (Take a Test, Credits, Contact Us, Profile) | prompt | prompt — unchanged |
| already on `/user-panel/patients` | prompt again, then a `replace: true` navigate re-renders the list | nothing happens |
| on a sub-route (`/patients/add`, `/patients/edit/:id`, `/patient-tests/:id`) | prompt again | back to the list, no prompt |

Leaving the section and returning **still** prompts. The rule is once per *entry*, not once per session;
that is deliberate, not a regression.

☠️ **`PATIENT_SECTION_PATHS` in `Header.js` is a fourth hard-coded path list**, after
`protectedRoutes.js`, `routeConfig.js` and `USER_PANEL_WITH_HEADER`. It holds two prefixes —
`/user-panel/patients` and `/user-panel/patient-tests` — matched as `=== base` or `startsWith(base + "/")`.
A new patient sub-route registered in the other three but missing here leaves the user inside the
section while the header still thinks they are outside it, and the prompt comes back.

---

## Redux

`src/redux/store.js` wires **68 reducers** across 17 slice folders. Two generic factories exist:

| Factory | File | Use |
|---|---|---|
| `createCrudSlice` | `slices/createSlice.js` | simple REST resource: `fetchItems`, `createItem`, `updateItem`, `deleteItem` |
| `createPaginatedCrudSlice` | `slices/createpaginatedslice.js` | ⭐ preferred for new paginated tables: `fetchConfig.buildRequest` / `mapResponse`, `resetList`, separate `pagination` |

Both build URLs from a `baseUrl` at runtime, which is why they show as `_scanner limit_` rows in
[CONTRACT_DRIFT.md](INDEXES/CONTRACT_DRIFT.md) — that is expected, not a finding.

Table column definitions live one-file-per-table in `src/utils/columns/`, fed straight into `react-table`.

## Error handling

`src/services/errorHandler.js` is a singleton with an `errorCodeMap` keyed on the backend's
`error_code` (today only `IP_RESTRICTED`, from
[`RestrictIpMiddleware`](MIDDLEWARE.md)). The Axios response interceptor calls it automatically unless
the request passes `skipErrorPopup: true` (or a thunk passes `showPopup: false`).

☠️ It classifies into `NETWORK / VALIDATION / AUTHENTICATION / AUTHORIZATION / PAYMENT / SERVER / UNKNOWN`
— but the backend collapses 403 and 404 into **500** ([ERROR_HANDLING.md](ERROR_HANDLING.md)), so the
`AUTHORIZATION` branch is largely unreachable in practice. Do not add client logic that depends on
receiving a 403.

### ☠️ Two popups for one error — the `skipErrorPopup` trap

`showPopup` is **not** a singleton. Every call appends a new `div.popup-container` to `document.body`
and mounts its own React root (`src/components/showPopup.js`); nothing dedupes them. A slice that both
lets the interceptor fire **and** popups its own rejected state therefore stacks **two modals** for one
response. The later call renders on top, so the user dismisses them in reverse order — which is why the
generic one is seen first and the useful one second.

Tell the two apart by **title**, which is unique to each source:

| Title | Written by |
|---|---|
| `Validation Error` · `Server Error` · `Network Error` · `Access Denied` … | `errorHandler.getErrorTitle()` — the **interceptor** |
| anything else (`Error`, `Password Changed`, …) | the component's own `showPopup` call |

Fixed for password change in **ws-395** (2026-08-28 — committed, *not yet merged or deployed*). A 422
from `PUT api/password/change` raised both `Validation Error — "…has appeared in a data leak…"`
(interceptor, flattening `errors`) and `Error — "The given data was invalid."` (the slice echoing the
backend's useless top-level `message`, see [ERROR_HANDLING.md](ERROR_HANDLING.md)). **Copy this fix:**

1. pass `skipErrorPopup: true` on the request, **and**
2. route `response.data.errors` into a separate `fieldErrors` slice key, leaving `error` **null**
   whenever field errors exist — so the popup fires only when there is nothing to attach to a field.

`passwordChangeSlice.js` + `ChangePasswordPage.js` then render `fieldErrors` inline under the offending
input. Doing only step 1 leaves the generic popup; only step 2 leaves the interceptor's.

☠️ **The same shape is still live on the Profile tab.** `slices/userProfile/profileSlice.js` passes no
`skipErrorPopup` while `ProfilePage.js` popups from its own `catch`, so a 422 on profile save still
double-fires. Not yet fixed.

---

## The credit balance is polled, not pushed

The header's credit counter is the only piece of Redux state that can be invalidated by **someone
else's session** — a Super Admin grants or revokes credits from their own login, and the affected
user's tab has no idea. `Header` mounts once inside `UserPanelLayout`, so before **ws-397** the balance
was fetched a single time per page load and stayed stale until a manual refresh.

☠️ **There is no push transport in this stack, and adding one is not a small change.** The backend has
no `config/broadcasting.php` and no Pusher/Reverb dependency; the SPA has no socket, Echo or
`EventSource`. Do not go looking for a channel to subscribe to — refreshing means re-fetching.

`src/hooks/useCreditsSync.js` (ws-397, 2026-08-28 — committed, *not yet merged or deployed*) owns the
re-fetching. `Header.js` is its only caller: `useCreditsSync(Boolean(user))`. Four triggers:

| Trigger | Call | Throttled? |
|---|---|---|
| mount | `fetchUserCredits({ background: false })` | no — guarded by a `didInitialFetch` ref |
| `location.pathname` change | `{ background: true }` | 5 s (`MIN_REFRESH_GAP_MS`) |
| `window` focus · `visibilitychange` → visible | `{ background: true }` | 5 s |
| `setInterval` 60 s, only while `visibilityState === "visible"` | `{ background: true }` | 5 s |

So "immediately" in the ticket means **instantly on navigation or tab focus, and within 60 s otherwise**.
It is not real time; do not describe it as such in release notes.

`background: true` lands in `action.meta.arg` and is read by `fetchUserCredits.pending` in
`slices/userCredits/userCreditSlice.js`, which skips flipping `loading` so the header keeps showing the
last known number instead of its `—` placeholder while the poll is in flight.

☠️ **`initialized` latches true and nothing ever resets it.** `loading` therefore goes true **exactly
once per page load** — the first non-background fetch. Every consumer of `state.userCredits.loading`
(the header's `—`, `HomePage/Home.js`, `CreditPage/CreditPage.js`, `Setting/Profile.js`) shows its
placeholder only on that first fetch and never again. A spinner that "stopped working" here is this,
not a broken request.

☠️ **Nothing clears the credits slice on logout.** `useLogOut` dispatches `logoutSuccess` and clears
storage, but the store is never reset and there is no page reload. Log out and back in as a **different
user in the same tab** and the header shows the *previous* user's balance until the new fetch resolves —
with no placeholder, because `initialized` is still true. A hard refresh is what clears it.

☠️ **The effect order inside the hook is load-bearing.** The mount effect is declared *before* the
`location.pathname` effect, and both run on mount; the mount effect stamps `lastFetchedAt`, so the 5 s
throttle swallows the route effect's duplicate. Reorder them and every mount fires two requests.

Each poll is `GET api/user/credits` → `UserController::getUserCredits()` → `Credits::getAvailableCredits()`,
which is **two aggregate `SUM` queries with no balance column and no cache**
([CREDITS_CONTEXT](CONTEXT/CREDITS_CONTEXT.md)). Budget one such pair per open tab per minute before
shortening `POLL_INTERVAL_MS`.

The hook does not replace the other refresh points, which are still separate and still needed —
`PatientTestList.js`, `InvitedPatientsTab.js`, `PaymentStatus/PaymentStatus.js` and `Setting/Profile.js`
each dispatch `fetchUserCredits()` with **no argument** (i.e. foreground), and
`sendTestInvitation.fulfilled` / `assignTest.fulfilled` write the backend's `credits_remaining` straight
into the slice without any request at all.

---

## The test player

Sequential URL flow under `/user-panel/start-test/:testId/`:

```
instruction → prepare → countdown → test → (section-complete →) transition → result
```

Driven by `testExecutionSlice`; `src/pages/UserPannel/TestPage/TestPage.js` orchestrates plate display
and answer capture. Plate images are preloaded by `src/utils/testPlatePreloader.js`; per-test
configuration (sections, timing, input type) is in `src/constants/testConfig.js`.

**The same flow is mounted three times** with different prefixes — `/user-panel/start-test/*` (logged
in), `/test-invitation/test/start-test/*` (emailed link), `/organization/*` (org launch). They share the
screen components. A change to a screen affects all three; a change to a **route** affects only one.

☠️ **Plate URLs are pre-signed and short-lived.** `getPlateUrl` returns a URL valid 900 s, cached
server-side for 880 s ([CONTEXT/TEST_EXECUTION_CONTEXT.md](CONTEXT/TEST_EXECUTION_CONTEXT.md)).
Aggressive preloading far ahead of display will fetch URLs that expire before use.

---

## ☠️ Known drift and dead code

Regenerated every run; the current state:

1. **`src/utils/calculateColorVisionResult.js` (349 lines) is dead.** It is exported but imported
   nowhere. The diagnosis now runs server-side in `ColorVisionDiagnosisService.php`, whose docblock says
   it was ported from this file. **Do not sync it — delete it.**
2. **`src/constants/testDescriptionConstants.js` (1,307 lines) is dead.** Despite the name it is a React
   component (`TestSelectionScreen`), imported by nothing, containing React-Native/Electron code
   (`Alert.alert`, `ipcCalls`, `deviceInfo`) and calls to endpoints that do not exist
   (`api/auth/ping-api`, `api/orders/free-trial`). It is pasted from another product.
3. **Two live calls hit endpoints that do not exist** — both return 404 today:
   | Call | Slice |
   |---|---|
   | `GET /api/tests/sent-invitations` | `slices/tests/sendTestSlice.js` |
   | `POST /api/user/tests/bulk-update-visibility` | `slices/tests/testVisibilitySlice.js` |

   Both slices are wired into live components (`InvitedPatientsTab.js`, `useTestProfileVisibility.js`).
   The backend has `POST api/user/tests/bulk-update-assignment` but no `-visibility` variant. Decide per
   case whether the client or the backend is wrong — see
   [INDEXES/CONTRACT_DRIFT.md](INDEXES/CONTRACT_DRIFT.md).

---

## Conventions

- `src/components/index.js` re-exports shared UI — import from there, not by deep path.
- Sass (`.scss`) alongside Bootstrap utilities.
- Formik + Yup for forms; schemas in `src/utils/validationSchema/`.
- Prefer `createPaginatedCrudSlice` over `createCrudSlice` for anything paginated.
- **A slice that renders its own errors must pass `skipErrorPopup: true`**, or the interceptor popups
  on top of it. Field errors belong inline via a `fieldErrors` key, not in a modal (see above).
- **State another session can change must be re-fetched, not assumed fresh.** Nothing is pushed to the
  client. Follow `useCreditsSync.js`'s shape — mount + route change + focus/visibility + a visible-only
  interval, all behind one throttle ref — and pass a `background` flag so a refresh does not flip
  `loading` and blank the value already on screen.
- **Adding a page = 3 files**: `protectedRoutes.js`, `routeConfig.js`, and (for user-panel pages)
  `USER_PANEL_WITH_HEADER` in `Router.js`.
- **A page under `/user-panel/patients*` or `/user-panel/patient-tests*` = 4** — the same three plus
  `PATIENT_SECTION_PATHS` in `Header.js`, or the Patients menu re-prompts for the password from inside
  the section (ws-399, see above).
- **Renaming a page = 4** — the same three plus `Sidebar.js`'s `menuItems`. `/test` → `/tests` on
  2026-08-27 (ws-359) had to touch all four; miss `routeConfig.js` and the page 403s for every role,
  miss `Sidebar.js` and the nav entry silently stops matching.

**Two tests are hidden by title, in the client only** (`src/constants/testConstants.js`, ws-359):
`HIDDEN_TEST_TITLES` drops *FAA Color Vision Test* from the admin Tests listing and
`ORG_EXCLUDED_TEST_TITLES` drops it from the org "allow test" picker. Both are `Set`s keyed on the
**exact title string**, and the backend enforces neither — the API still returns and accepts the test.
Renaming that test in the `tests` table un-hides it here *and* changes its diagnosis path server-side
([TEST_EXECUTION_CONTEXT](CONTEXT/TEST_EXECUTION_CONTEXT.md)); the title is load-bearing in both repos.
