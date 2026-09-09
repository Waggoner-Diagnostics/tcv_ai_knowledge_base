# Audit Trail — implementation plan

**Repo:** `TCV-Frontend` (branch `develop`) · **Route:** `/audit-trail`
**Written:** 2026-08-28 · **Phase 1 built:** 2026-08-28 · **Phase 2 built:** 2026-08-31 ·
**Phase 3 built:** 2026-08-31

The Audit Trail is a Super-Admin page reached from the dashboard sidebar. It lists platform
activity — who did what to whom, when, and whether it succeeded — in a sortable, searchable,
filterable table, with a detail drawer per row.

Delivered in three phases:

| Phase | Scope | State |
|---|---|---|
| 1 | Page UI — route, sidebar entry, header/toolbar, table, fixture data | **done** |
| 2 | Filter popup + filter/query logic + active-filter chips | **done** |
| 3 | Row detail side drawer | **done** |
| 4 | Swap fixtures for the live endpoint | blocked on backend |

Backend work is tracked separately (see [Backend — not in phase 1](#backend--not-in-phase-1)).

---

## Decisions

| Decision | Choice | Rationale |
|---|---|---|
| Phase-1 data source | Fixtures behind an API-shaped adapter | UI reviewable without waiting on the backend; phase 4 swaps one function body |
| Visibility | Super Admin only | Matches the mock; "Who was affected" spans organisations |
| Export format | Backend-generated file (`GET /api/audit-logs/export`) | Respects filters server-side, not limited to the current page; no new frontend dependency |
| State management | Local `useState` in the page, no Redux slice | One paginated list, no cross-page sharing — a slice would be ceremony. Matches `Users.js` |

### Existing-code baseline

Verified against the working tree on `develop`:

- There is **no generic `audit_logs` table** in `TCV-Backend`. Only `pricing_audit_logs`
  (`database/migrations/2026_01_27_112600_create_pricing_audit_logs_table.php`) and a thin
  `AuditLogger::log()` helper (`app/Services/Audit/AuditLogger.php`) that writes to a
  caller-supplied table. Every category in the mock is greenfield.
- `TCV-Frontend` and `TCV-Backend` are both checked out on `develop`, **not** `main` as the
  root `CLAUDE.md` states.

---

## API contract

Fixed now so both sides build to it. The Laravel side returns `ApiResponse::success()` wrapping
a paginator, which `src/apis/parsePaginatedResponse.js:31-45` already handles at its second
branch — **including `counts`**, which is how `Users.js:66-68` drives its tab counters.
Reuse it verbatim; no new parser.

```
GET /api/audit-logs
  page, limit, sortBy, sortOrder, search
  status: '' | 'success' | 'failed'                                  <- top-level tabs
  from, to, actor_id, target_id, category, sensitivity, admin_only   <- phase 2
```

```jsonc
{ "data": {
    "data": [{
      "id": 720,
      "audit_id": "720",
      "category": "accounts_users",        // key, not label — label lives in the FE constant
      "event_title": "User details are Edited/updated",
      "event_description": "User details are edited",
      // role: 'admin' | 'user' | 'organization' | 'system' — admin names render in #074C8C
      "actor":  { "id": 12, "name": "Dynamisch Admin", "email": "…", "role": "admin", "company_name": null },
      "target": { "id": 45, "name": "Jeffrey Flores",  "email": "…", "role": "user",  "company_name": "Barnes LLC" },
      "created_at": "2026-06-09T16:03:49Z",
      "status": "success",                 // 'success' | 'failed'
      "sensitivity": "medium",             // phase 2 filter; not a column
      "is_admin_action": true              // phase 2 filter
    }],
    "total": 152, "current_page": 1, "per_page": 10, "last_page": 16,
    "counts": { "total": 152, "success": 133, "failed": 12 }
} }
```

`actor` and `target` are both nullable — the mock's first row shows a dash for an unaffected
target. Both carry `company_name`, so the organisation sub-line renders identically in either
column.

---

## Phase 1 — page UI

Static-data page. The filter popup and the drawer are **shells only**: the buttons render, the
click is a no-op. Phases 2 and 3 attach to seams left in place here.

### New files

#### 1. `src/constants/auditTrail.js`

- `AUDIT_CATEGORIES` — a map of `key -> { label, tone }` for all seven chips in the mock:
  Sign-ins & Security, Broadcast Management, Billing & Payment, License Management,
  Accounts & Users, Version Management, Devices. `tone` is a class suffix, not a hex —
  colours live in SCSS.
- `STATUS_TABS` — All / Success / Failed, mirroring `Users.js:28-32`.
- `SENSITIVITY_LEVELS` — declared now, unused until phase 2.

#### 2. `src/apis/fixtures/auditLogs.js`

~25 rows covering every category, both statuses, a null actor, a null target, a missing
company, and one very long title (to prove truncation).

#### 3. `src/apis/fetchAuditLogsPaginated.js`

Same signature shape as `src/apis/fetchUsersPaginated.js`. The body slices and filters the
fixture in memory and returns the *exact* `parsePaginatedResponse` output, behind a 300 ms
delay so loading skeletons are real. Phase 4 replaces the body with the axios call — callers
do not change.

#### 4. `src/utils/avatar.js`

Lift `AVATAR_PALETTE` and `avatarColor()` out of `src/utils/columns/userManagementColumns.js:6-12`
and re-import them there. Audit Trail needs the same hash in two columns; copying it a third
time is how it drifts.

#### 5. `src/utils/columns/auditTrailColumns.js`

| Column | Width | Sortable | Notes |
|---|---|---|---|
| Audit ID | 100 (fixed) | yes | |
| Category | min 150 | yes | chip span, class `at-cat at-cat--<tone>` |
| What Happened | min 180 | no | title + single-line ellipsised subtext |
| Who Did It | min 200 | no | |
| Who Was Affected | min 200 | no | renders an em-dash when null |
| Date & Time | min 160 | yes | `parseDateTimeString(v, 23)` |
| Status | min 100 | yes | |

The two person columns share one `PersonCell` component local to this file — avatar, name,
email (wrapped in `CustomTooltip`, as the users table does), company. Markup mirrors
`.um-name-cell` so the visual rhythm matches; class prefix `at-person`.

**Do not reuse `StatusBadge`.** `src/components/misc.js:14-18` only knows `active` / `inactive`
and would render success/failed as `.pending`. Use a small local `AuditStatusBadge` instead.

#### 6. `src/pages/AuditTrail/AuditTrail.js`

Container, structured like `src/pages/users/Users.js`.

State: `logs, loading, total, page, sortBy, sortOrder, search, activeTab, tabCounts,
lastFetchedAt`, plus `filters` initialised to `{}` and `selectedRow: null`. The last two are
inert in phase 1 and are the seams phases 2 and 3 attach to.

`lastFetchedAt` is stamped on every successful fetch; a 30 s `setInterval` re-renders the
"Updated 1 min ago" label via `parseDateTimeString(lastFetchedAt, 4)` — already
`moment().fromNow()` in `src/utils/dateUtils.js:52-55`.

#### 7. `src/pages/AuditTrail/AuditActiveFilters.js`

Presentational. Takes `filters`, `onRemove`, `onClearAll`; returns `null` when empty. Chips copy
the `.dc-chip` pattern from `src/styles/components/DiscountCodes.scss:532-551` — 20 px radius,
`#0B5497` on `#eff6ff` when active — as `.at-chip`, with a remove affordance borrowed from
`.dc-user-chip__remove`. Built in phase 1, fed real data in phase 2.

#### 8. `src/styles/pages/AuditTrail.scss`

`.at-*`, mirroring the structure and tokens of `src/styles/pages/UserManagement.scss`
(`$space-*`, `$radius-*`, `$color-*`). Reuse `.um-search` from `src/scss/custom.scss:10-70`
unchanged — it already ships the `IoClose` clear button.

### Edits to existing files

| File | Change |
|---|---|
| `src/constants/routeConfig.js:24` | add `'/audit-trail'` to `SUPER_ADMIN.parentRoutes` only. This is the entire role gate, per the filtered-route-list convention |
| `src/router/routes/protectedRoutes.js` | `lazyWithRetry` import + a `/audit-trail` route entry |
| `src/components/Sidebar.js:36-64` | `menuItems` entry after Settings, path `/audit-trail`, title "Audit Trail", icon `FiShield`. `filterMenuItems` intersects against `routeConfig`, so it appears for Super Admin and nothing else. `react-icons` is already an accepted sidebar icon source (Dashboard uses `RiDashboard3Fill`) — no new SVG asset needed |
| `src/components/table/TableWithGlobalFilter.js:255` | **the one shared-component change** — see below |

#### The `TableWithGlobalFilter` change

Add `onRowClick` and `selectedRowId` props: call `onRowClick` with `row.original` from the
`<tr>` click handler, and extend the existing `className` (which today only sets `row--new`)
with `row--selected` and `row--clickable`. Both props default to `undefined`, so all current
callers are byte-identical in behaviour.

This is what phase 3 opens the drawer from, and what paints the blue left-bar on the highlighted
row in the mock.

### Layout

The toolbar is heavier than the Users header — tabs, search, and *two* buttons compete for the
right side, and the active-filter bar is a second row.

```
.at-root
├── .at-header          row 1: title + "Updated Xm ago" + Refresh | tabs · search · Filter · Export
├── .at-subhead                shield icon + "Track and review system and admin activity…"
├── .at-active-filters  row 2: AuditActiveFilters — renders null in phase 1
└── .at-table-card             TableWithGlobalFilter, showSearch={false}, useServerSorting, onRowClick
```

Search stays owned by the page (`showSearch={false}` on the table), same as Users — that keeps
it in the toolbar beside the tabs rather than inside the card.

`Filter` and `Export` are `TCVButton variant="outline"` with a `FiChevronDown`, wrapped in an
`.at-popover-anchor` div that exists but holds nothing yet. Phase 2 mounts the filter panel into
that anchor using the existing `src/hooks/useClickOutside.js`.

### Phase 1 is done when

- Sidebar shows Audit Trail for Super Admin and only Super Admin.
- `/audit-trail` renders 10 fixture rows with correct chips, avatars, and status pills.
- Tabs switch and show counts; search filters and shows the close icon.
- Sort works on the four sortable columns.
- Pagination matches the mock's "1–10 of 11 · Prev 1 2 Next".
- Refresh refetches and resets the timestamp label.
- Clicking a row logs the record and paints the selected state.
- Loading shows the existing skeleton rows; an empty result shows `NoData`.
- Filter and Export render but do nothing.

### What was actually built — deviations from the plan above

All eight new files and four edits landed as specified. Three things differ:

1. **A fifth edited file: `src/utils/dateUtils.js`.** No existing format type produced the
   mock's `June 9, 2026, 04:03:49 PM` (type 1 gives `June 9, 2026 at 4:03 PM` — no seconds,
   no comma). Added `type == 23` following the file's existing convention rather than
   introducing a competing date helper.
2. **`formatFetchedAt` is local to `AuditTrail.js`, not `parseDateTimeString(t, 4)`.** moment's
   `fromNow()` renders "a minute ago"; the design asks for "1 min ago". The local helper is
   ~8 lines and covers just now / mins / hrs / days.
3. **`src/utils/avatar.js` also exports `avatarInitials(name)`.** The users table builds
   initials from separate `first_name` / `last_name` fields; audit rows carry a single `name`
   string, so the split lives in the shared module rather than being duplicated.

### Verification performed

- `npx eslint` over every new and edited file: **0 errors, 0 warnings**. (The six warnings
  reported in `TableWithGlobalFilter.js` are pre-existing and untouched.)
- `react-scripts build`: compiles. The repo-wide eslint warnings it prints are all pre-existing;
  **no warning references any Audit Trail file**.
- A temporary React Testing Library smoke suite exercised the mounted page — fixture rows
  rendering, tab counts, tab switching, search, category chip labels, person cells, the
  `MMMM D, YYYY, hh:mm:ss A` date format, the "Updated just now" label, and `row--selected`
  appearing on row click. **3/3 passed**, then the file was deleted: every assertion was tied to
  fixture strings that phase 4 removes. Worth re-creating as a real suite once the API is live.

---

## Phase 2 — filters

Popup anchored to the Filter button, containing: date range, Who Did It, Who Was Affected,
Category, Sensitivity, and an "Admin Actions Only" checkbox, with Clear and Apply.

Applied filters populate the `filters` state object, which drives both the query params and the
already-built `AuditActiveFilters` chip row. The Clear Filters link appears whenever any filter
is active — **search excluded**.

### Requirements as specified

**Date Range**

1. Shows the From–To dates in American format (`MM/DD/YYYY`).
2. Once a range is selected the field shows a close icon that clears it.
3. Clicking the field opens a date popup with presets: Today, Yesterday, Last 7 days,
   Last 30 days, Custom Range.
4. Every preset except Custom Range resolves relative to the current day.
5. The default is Last 30 days. The API call defaults to it, and it is **not** shown as an
   active filter — only its value appears on the field, with "Last 30 days" highlighted in the
   rail. Clearing a user-selected range returns to it.

**Who Did It / Who Was Affected** — searchable single-select over all user names
(organisations, users, super admins).

**Category / Sensitivity** — plain single-select over preset values.

**Admin Actions Only** — checkbox toggling between admin-only and all actions.

Both footer buttons commit and close: **Apply** commits the draft, **Clear** commits the
defaults. The panel carries a shadow heavy enough to read on bright screens.

### The filter state object

One object is the single source of truth for the query params *and* the chip row:

```jsonc
{
  "datePreset": "last30",     // today | yesterday | last7 | last30 | custom
  "from": "2026-08-02",       // always present — the API always receives a window
  "to":   "2026-08-31",
  "actorId": 7,  "actorLabel":  "Dynamisch Admin",   // *Label is display-only,
  "targetId": 41, "targetLabel": "Jeffrey Flores",   // never sent to the API
  "category": "accounts_users",
  "sensitivity": "high",
  "adminOnly": true
}
```

`datePreset` is stored **alongside** the resolved bounds rather than instead of them. That is
what lets the picker re-highlight the rail on reopen, and what lets the default window be
recognised and kept out of the chip row while still being sent to the API — requirement 5 needs
both facts at once, and bounds alone cannot express "this is the default".

### New files

#### 1. `src/utils/auditFilters.js`

The filter-state vocabulary, kept out of the components so the shape has one owner:
`resolveDatePreset()`, `defaultDateFilters()`, `buildDefaultFilters()`, `isDefaultDateRange()`,
`formatDisplayDate()` / `formatDateRange()`, `toDateObject()` / `toApiDate()`, and
`toQueryParams()`.

`toQueryParams()` strips the `*Label` keys, renames the rest to the snake_case in the API
contract above, and drops falsy values. The fixture matcher reads the *same* output, so the
fixture and the real endpoint cannot drift on key names.

Preset maths is `end = today − offset`, `start = end − (days − 1)`, so `days` is inclusive of
its end date. With today = 2026-08-31 that yields `last30 = 08/02/2026 – 08/31/2026`, matching
the reference design exactly.

#### 2. `src/pages/AuditTrail/AuditFilterPanel.js`

The popover. Edits happen against a **draft** copy of the applied filters, re-seeded from them
every time the panel opens — so changing your mind and closing does not refetch the table.

#### 3. `src/pages/AuditTrail/AuditDateRange.js`

Two components: the field (formatted range, calendar icon, × once the window is non-default)
and the picker overlay (preset rail · `« ‹ Month YYYY › »` header · Monday-first calendar ·
Apply).

Built on the existing `react-datepicker` dependency — `inline selectsRange calendarStartDay={1}`
with a `renderCustomHeader` for the four-button nav — rather than a hand-rolled calendar.
Its `--in-range` / `--range-start` / `--range-end` classes are exactly the hooks the design's
band-with-rounded-caps needs.

#### 4. `src/pages/AuditTrail/AuditSelect.js`

One component for all four dropdowns; `searchable` toggles the search box. A native `<select>`
would have covered Category and Sensitivity but not the person pickers (avatar + company · email
per row), and two different-looking controls in one 380 px panel reads as an accident.

Single-select throughout. Clearing is available twice — the × on the trigger, and re-picking the
selected option.

#### 5. `src/apis/fetchAuditPeople.js` + `src/apis/fixtures/auditPeople.js`

`GET /api/audit-logs/people` — every identity that can appear as an actor or a target. Same
fixture-behind-an-adapter pattern as the logs endpoint, same marked block for phase 4. Seventeen
people: the five that appear in `AUDIT_LOG_FIXTURES` (same ids, so picking them actually narrows
the table) plus twelve more so the search box has something to filter.

Fetched **once**, lazily, the first time the panel opens, and searched client-side — the list is
small and a keystroke-per-request would be waste.

### Edits to existing files

| File | Change |
|---|---|
| `src/pages/AuditTrail/AuditTrail.js` | `filters` initialised from `buildDefaultFilters()` instead of `{}`; panel open/close state and anchor ref; lazy people fetch; `handleApplyFilters`; `handleRemoveFilter` re-seeds the default window when the date chip is removed; Filter button gains an active-filter count badge |
| `src/pages/AuditTrail/AuditActiveFilters.js` | date chip suppressed while `isDefaultDateRange()`; a named preset shows its label, a custom range shows its dates; the chip owns `datePreset` + `from` + `to` |
| `src/apis/fetchAuditLogsPaginated.js` | `toQueryParams()` on the way in; a `matchesFilters()` pass added to the fixture block |
| `src/apis/fixtures/auditLogs.js` | `created_at` re-anchored to now — see deviation 1 |
| `src/styles/pages/AuditTrail.scss` | `.at-filters`, `.at-select`, `.at-daterange`, the `.at-toolbtn__badge`, and react-datepicker overrides scoped to `.at-daterange__calendar` |

No shared component was touched in phase 2 — `CustomCheckBox`, `TCVButton`, `useClickOutside`
and `avatar.js` were all reused as-is.

### What was actually built — deviations from the outline

1. **The fixture dates had to be re-anchored to "now".** `auditLogs.js` carried fixed June 2026
   timestamps. Against the current date the *default* 30-day window returns an empty table,
   which makes the whole feature untestable and every preset indistinguishable. Rows now compute
   `created_at` as an offset from `Date.now()`, deliberately stretched past 30 days:

   | Preset | Rows of 27 |
   |---|---|
   | Today | 5 |
   | Yesterday | 2 |
   | Last 7 days | 12 |
   | Last 30 days (default) | 20 |

   (Counts re-verified after phase 3 appended the two reference-screen rows; they were 3 / 2 /
   10 / 18 of 25 when phase 2 shipped.)

   This is fixture-only and disappears with the fixture block in phase 4.

2. **The date picker is a centred overlay, not a nested popover.** The card is wider than the
   380 px panel it opens from, and that panel is itself right-anchored to a button at the far
   right of the toolbar — an anchored popover clips against the viewport edge at any normal
   window width. It renders `position: fixed` over the viewport with a transparent click-catching
   backdrop, matching the reference design, which shows the card floating over the table with no
   dimming.

3. **The Filter button carries a count badge.** Not in the outline. The chip row scrolls out of
   view on a long table, and the button is the only always-visible affordance that can say
   whether anything is applied. The count comes from the same `describeFilters()` call the chip
   row renders, so the two cannot disagree — including the default-window suppression.

4. **`SENSITIVITY_LEVELS` was already correct and was reused unchanged.** Phase 1 declared it
   for exactly this; only its "unused in phase 1" comment was updated. Same for
   `AUDIT_CATEGORY_OPTIONS`.

### Two interaction traps, and how they are handled

Both are commented at the site, because both look like gratuitous complexity otherwise.

- **Dismiss-on-outside-click is scoped to `.at-popover-anchor`, not to the panel** — and is
  owned by the page, not by `AuditFilterPanel`. Scoped to the panel, `mousedown` closes it and
  the Filter button's own `click` immediately re-opens it. The anchor contains both, so the
  button counts as "inside" and its toggle is the only thing that acts.
- **The date overlay listens for `Escape` in the capture phase and calls `stopPropagation()`.**
  The panel behind it listens on `document` too; without swallowing the event first, one
  keypress dismisses both layers. Capture-on-`document` fires before bubble-on-`document`
  regardless of registration order, which `stopImmediatePropagation` alone would not guarantee.

### Phase 2 is done when

- The panel opens under the Filter button, closes on outside click / Escape / ×, and casts a
  shadow that separates it from the table on a bright screen.
- The page loads on Last 30 days: the field reads `08/02/2026 - 08/31/2026`, the rail highlights
  "Last 30 days", **no** date chip appears, and the API receives `from`/`to`.
- Each preset resolves against today and visibly changes the row count.
- Custom Range selection on the calendar switches the rail to Custom and applies as picked.
- Clearing the date range — via the field's × or the chip — returns to Last 30 days, not to an
  empty window.
- Both person dropdowns search the directory and select one person; Category and Sensitivity
  select one preset value; Admin Actions Only toggles.
- Applied filters appear as chips; removing a chip clears every key that produced it.
- Clear (panel) and Clear Filters (chip row) both return to the default state.
- Search remains independent of all of the above.

### Verification performed

- `react-scripts build`: compiles. **No warning references any Audit Trail file** — the
  repo-wide warnings it prints are all pre-existing. (Under `CI=true` the build fails on those
  pre-existing warnings, in files untouched by this phase.)
- `npx sass` over `AuditTrail.scss`: compiles; only the repo's pre-existing `@import`
  deprecation notices.
- Preset arithmetic and fixture coverage checked directly in Node against today's date:
  all five presets resolve to the expected windows — `last30` to `08/02/2026 - 08/31/2026`, the
  value in the reference design — and each returns a distinct, non-empty row count (the table in
  deviation 1).
- ESLint was **not** run as a separate pass this phase; `react-scripts build` runs the same
  `react-app` config and reported nothing in these files.

## Phase 3 — row detail drawer

Right-hand side drawer opened by `onRowClick`, showing the full record for the selected row.
Driven by the `selectedRow` state left in place in phase 1.

The two reference screens differ in exactly one section — everything else is common:

```
┌ header ──────────────  title · Record #NNNNN + copy · ×
├ meta bar ────────────  status pill · category chip · clock + date & time
├ What Happened ───────  event description
├ Who Did It ──────────  avatar · name · email · company|role · IP
├ Who Was Affected ────  same card, or a "no target" placeholder
├ ▸ Details ───────────  key/value pairs — screen 1        ┐ either,
├ ▸ What Changed ──────  before → after table — screen 2   ┘ both, or neither
├ Session Details ─────  location · device
└ Related Activity ────  clickable rows: status · title · timestamp
```

Nothing else about the drawer varies by event type.

### Where the data comes from

The row already in hand carries the whole header — title, category, status, timestamp, actor,
target, and description are all in the list payload. So the drawer opens **instantly** with the
header painted from `selectedRow` and skeletons only the four detail-only sections while
`GET /api/audit-logs/{id}` resolves. No spinner over content we already have.

Same fixture-behind-an-adapter pattern as phases 1 and 2: `fetchAuditLogDetail(id)` returns the
final response shape today out of a fixture module, and phase 4 replaces its marked block with
one axios call. The dummy data lives in `apis/fixtures/`, **not** inside the component — a
component holding its own fixture is a component that has to be edited again in phase 4.

### API contract — `GET /api/audit-logs/{id}`

Extends the list row rather than replacing it: every list key keeps its meaning, and the detail
adds five. The drawer's "Record #" is `audit_id` — the same number the table's Audit ID column
prints — deliberately, not a separate display id: see deviation 1 below.

```jsonc
{ "data": {
    // ─ same shape as a list row ─
    "id": 719, "audit_id": "719", "category": "accounts_users",
    "event_title": "User details are Edited/updated",
    "event_description": "Profile updated by Dynamisch Administrator (admin@yopmail.com) for …",
    "created_at": "2026-06-09T16:03:49Z",
    "status": "success", "sensitivity": "medium", "is_admin_action": true,

    // ─ detail-only ─
    "actor":  { "id": 7, "name": "…", "email": "…", "role": "admin",
                "company_name": null, "ip_address": "172.18.0.4" },   // + ip_address
    "target": { "…": "same shape, nullable" },

    "details": [                           // ordered — screen 1's "Details" box
      { "label": "First Name",           "value": "Robin" },
      { "label": "Assigned Tests",       "value": ["Adult Diagnostic", "Baseline Test"] },
      { "label": "Allow Monocular Test", "value": true }
    ],

    "changes": [                           // screen 2's "What Changed" table
      { "field": "Last Name",      "before": "Smith", "after": "Mark" },
      { "field": "Assigned Tests", "before": ["Adult Diagnostic"],
                                   "after":  ["Adult Diagnostic", "Baseline Test"] }
    ],

    "session": {
      "location": { "city": "Pune", "state": "Maharashtra", "country": "India",
                    "timezone": "Asia/Kolkata" },
      "device":   { "type": "Desktop", "browser": "Microsoft Edge", "os": "Windows 10/11" }
    },

    "related_activity": [                  // same session; this record excluded
      { "id": 720, "event_title": "Admin Impersonation starts",
        "status": "success", "created_at": "…" }
    ]
} }
```

Three shape decisions, each with a reason the backend needs to honour:

- **`details` is an ordered array, not an object map.** The mock's field order is meaningful
  (First Name, Last Name, Email, …) and neither JSON nor a PHP associative array guarantees key
  order survives serialisation. An array of `{label, value}` does.
- **`value` is polymorphic and the renderer infers from its type** — `string|number` → one line,
  `string[]` → a stacked list (Assigned Tests), `boolean` → a check/cross flag row. The backend
  sends data; the drawer decides how it looks — the same principle that keeps category labels in
  the frontend constant. `before`/`after` in `changes` accept those three types plus `null`.
- **`session` is structured, not pre-formatted.** The mock renders
  `India, Pune, Maharashtra, India(Asia/Kolkata)` and `Desktop · Microsoft Edge · Windows 10/11`;
  those separators are presentation. Structured fields also let a missing city degrade to the
  rest of the line instead of leaving a dangling comma.

`details`, `changes`, `session`, and `related_activity` are all optional — an absent or empty one
drops its section entirely rather than rendering an empty box.

### New files

#### 1. `src/apis/fixtures/auditLogDetails.js`

Detail payloads keyed by log id, covering **every** row in `AUDIT_LOG_FIXTURES` so no click can
land on a 404. Two are hand-authored to match the reference screens — `722` "New User Registered"
(the *Details* screen, with its string, array, and boolean values) and `721` "User details are
Edited/updated" (the *What Changed* screen). The rest are derived from their list row. The set
deliberately also covers a record with **neither** optional section (`705`), one with **both**
(`712`), one with no target, and one with no related activity (`699`).

Those two ids are new rows appended to `AUDIT_LOG_FIXTURES` — see deviation 2.

#### 2. `src/apis/fetchAuditLogDetail.js`

`GET /api/audit-logs/{id}`. Mirrors `fetchAuditPeople.js` — a short delay, one marked fixture
block, and it **rejects on an unknown id** so the drawer's error path is reachable in
development rather than only in production.

#### 3. `src/pages/AuditTrail/AuditDetailDrawer.js`

The shell and the orchestration: open/close, the fetch, the record history, the header, the meta
bar, and Related Activity.

#### 4. `src/pages/AuditTrail/AuditDetailSections.js`

The presentational blocks — `PersonCard`, `DetailList`, `ChangeTable`, `SessionCard`,
`SectionSkeleton` — plus the polymorphic value renderer they share. Split out because each is a
pure `props -> markup` function while the shell is the only stateful part; together they would be
one ~450-line file.

#### 5. `src/utils/clipboard.js`

Lift `copyToClipboard` (clipboard API + `execCommand` fallback) out of
`utils/columns/organisationColumns.js:15-35` and re-import it there. Second caller, same rule
phase 1 applied to `avatar.js`.

### Edits to existing files

| File | Change |
|---|---|
| `src/pages/AuditTrail/AuditTrail.js` | mount the drawer; `handleRowClick` seeds it; `onRecordChange` keeps the table highlight following the drawer; close clears the highlight |
| `src/constants/auditTrail.js` | `AUDIT_ROLE_LABELS` + `roleLabel()` — the role text rendered under a person's email |
| `src/utils/columns/organisationColumns.js` | import `copyToClipboard` instead of defining it |
| `src/styles/pages/AuditTrail.scss` | the `.at-drawer*` block |

No shared component is touched this phase — `TableWithGlobalFilter` already gained `onRowClick`
and `selectedRowId` in phase 1, which is what this attaches to.

### Mechanics

- **`react-bootstrap` `Offcanvas`, `placement="end"`.** `bootstrap/scss/offcanvas` is already
  imported (`src/scss/index.scss:54`) and `Modal` from the same package is used in ~10 places, so
  the slide-in, the backdrop, Escape-to-close, scroll lock, and focus return come for free. A
  hand-rolled portal would re-implement all five, and worse.
- **The person sub-line is `company_name || role label`**, not both. That is what the reference
  shows on both screens at once: the admin actor with no company reads "Admin", the target under
  Barnes LLC reads "Barnes LLC".
- **Related Activity navigates in place, with a back arrow.** Clicking a related row loads that
  record into the same drawer and pushes the previous one onto a stack; the arrow appears in the
  header only when the stack is non-empty. Without it a two-click detour is a dead end — you land
  on a record you did not open and have to close the drawer, find your row, and re-open it.
- **The table highlight follows the drawer.** Navigating fires `onRecordChange(id)`, so
  `row--selected` moves with it (or moves off, when that record is not on the current page).
- **Copy** writes `record_number` and swaps the icon for a check for 1.5 s. `showPopup` — what
  `organisationColumns.js` uses for its copy button — is a modal dialog, and a modal on top of a
  drawer to confirm a clipboard write is too much ceremony for the gesture.

### Phase 3 is done when

- Clicking any row opens the drawer with its header already populated, and the detail sections
  skeleton in and then resolve.
- Both reference screens reproduce: record `722` shows *Details* with a string, an array, and two
  boolean flags; record `721` shows *What Changed*.
- Long values, array values, a null `before`, and a missing target all render without overflow.
- A record with no `details`/`changes`/`related_activity` drops those sections rather than
  showing empty boxes.
- Close works from ×, the backdrop, and Escape; the row highlight clears on close.
- Clicking a related-activity row swaps the drawer to that record, the back arrow returns, and
  the table highlight tracks both moves.
- Copy puts the record number on the clipboard and confirms inline.
- A failed detail fetch shows an inline error with a Retry, not a dialog over the drawer.

### What was actually built — deviations from the outline

1. **`record_number` was dropped from the contract; the header prints `audit_id`.** It was
   drafted as a detail-only field, which meant the header opened reading `Record #722` from the
   list row and then *renumbered itself* to `Record #25958` when the fetch resolved. An id that
   changes under the reader is worse than an id that differs from a mock, and nothing in the
   product needs a display number distinct from the audit id — the table column already prints
   it. The reference screens' `#25958` is therefore not reproduced literally.
2. **Two fixture rows were appended rather than reusing existing ones.** Reproducing both
   reference screens needs one *record* for the registration and one for the edit, both about the
   same person (Robin Mark, a new fixture identity). Rewriting existing rows to fit would have
   changed rows that phase 2's person filters and preset counts were verified against; appending
   `722` and `721` at "now" only extends them. The phase-2 preset table above was re-run and
   updated.
3. **The loading placeholder carries no section headings.** The outline had each pending section
   skeleton under its own title. But `details`, `changes`, `session` and `related_activity` are
   all optional and their presence is unknown until the fetch lands — a titled skeleton announces
   a "Details" section that a third of records then don't have. One untitled block stands in for
   the lot.
4. **Related Activity is generated from neighbouring fixture rows, not invented events.** The
   reference screen lists events ("Payment Method Saved", "Signed Out") that have no rows behind
   them; a chip that opens nothing is worse for review than a chip that opens a real record. The
   fixture reproduces the *shape* — a mixed success/failed list of five — from ids that exist.
   The real endpoint will scope this by session id instead.
5. **`.at-drawer` splits its Bootstrap-compounded rules from its BEM children.** `&__header`
   nested inside `.at-drawer.offcanvas` compiles to `.at-drawer.offcanvas__header`, which matches
   nothing. Width and chrome overrides live in an `&.offcanvas` block; the children hang off the
   plain `.at-drawer`. `.at-drawer__body` is compounded with `.offcanvas-body` for the same
   reason padding needs to beat Bootstrap's regardless of stylesheet injection order.

### Verification performed

- `npx sass` over `AuditTrail.scss`: compiles; only the repo's pre-existing `@import`
  deprecation notices. The generated CSS was grepped to confirm `.at-drawer__header` and
  `.at-drawer__body.offcanvas-body` emit as intended and that no `.offcanvas__*` selector exists.
- `npx eslint` over all nine new and edited files: **0 errors, 0 warnings**.
- `react-scripts build`: compiles. No warning references an Audit Trail, clipboard, or
  organisation-columns file.
- A temporary RTL suite of six tests, run and then deleted: both reference screens (headers,
  section contents, the formatted session strings, both person cards on one shared IP, and the
  company-vs-role sub-line), Related Activity navigation with the back arrow and two
  `onRecordChange` calls, a record with neither optional section, a record with both, and
  `fetchAuditLogDetail` resolving for every fixture id while rejecting an unknown one.
  **6/6 passed.** Deleted for the same reason as phases 1-2: every assertion is tied to fixture
  strings that phase 4 removes.
- Note for whoever writes the permanent suite: jsdom has no `matchMedia`, and react-bootstrap's
  `Offcanvas` calls it via `useBreakpoint`. The temporary suite polyfilled it locally;
  `src/setupTests.js` was deliberately left untouched, but that is where it belongs if a second
  Offcanvas-based component appears.

### Phase 3 revisions — design pass (applied 2026-08-31)

Three density changes after reviewing the built drawer. Stylesheet only; no markup moved.

1. **Type scale capped at 14 px.** Everything in the drawer now sits between `$text-xs` (11 px)
   and `$text-base` (14 px); only `.at-drawer__title` stays at `$text-xl` (18 px). Two rules were
   above the cap — `&__sectiontitle` (15 → 14) and `.at-personcard__name` (16 → 14).
2. **`.at-detaillist` row gap** `$space-3` → `$space-2`.
3. **`.at-related__row` padding** `$space-3 $space-4` → `$space-2 $space-3`.

Verified by compiling the sheet and scanning the generated CSS: no drawer-scoped selector outside
the title declares a `font-size` above 14 px or below 11 px.

---

## Backend — not in phase 1

Sized separately, before phase 2 starts:

- migration for a generic `audit_logs` table
- `AuditLog` model, `AuditLogService`, controller, routes
- the `GET /api/audit-logs/export` endpoint
- **the real scope risk:** instrumentation deciding *which* actions emit audit rows. The seven
  categories in the mock imply write-sites scattered across most of the backend's services.

---

## Phase 1 revisions — design pass (applied 2026-08-28)

Six changes requested after reviewing the built page. All applied and verified.

### 1. Category chip palette

Replaced the initial Tailwind-ish tones with the specified pairs. Tone names are unchanged, so
nothing outside the stylesheet moved.

| Category | Tone class | Background | Text |
|---|---|---|---|
| Sign-ins & Security | `--pink` | `#F4D2F3` | `#6A1B69` |
| Broadcast Management | `--teal` | `#D2F4EE` | `#1B6A5B` |
| Billing & Payment | `--green` | `#E0F4D2` | `#3A6A1B` |
| License Management | `--purple` | `#D2D7F4` | `#1B256A` |
| Accounts & Users | `--orange` | `#FEEFD8` | `#CD8307` |
| Version Management | `--yellow` | `#F4F3D2` | `#6A611B` |
| Devices | `--blue` | `#D2F3FF` | `#007599` |

### 2. Chip text weight

`.at-cat` moved from `$font-semibold` (600) to `$font-medium` (500).

### 3. Column min-widths

Category 150 · What Happened 180 · Who Did It 200 · Who Was Affected 200 · Date & Time 160 ·
Status 100. Audit ID keeps its fixed 100 px.

**This required a second change to `TableWithGlobalFilter.js`.** The component applied only
`column.width` to the DOM and silently dropped `column.minWidth`, so a column def asking for a
min-width had no effect — the original phase-1 defs carried `minWidth: 300`/`260` values that
were doing nothing. Both the header and body cell style calculations now go through a shared
`columnSizingStyle()` helper that applies each when set. react-table defaults `minWidth` to 0,
so this is a no-op for every other table in the app.

Note that react-table also defaults `width` to 150, which this component has always applied
inline; under `table-layout: auto` the min-width wins, so the requested minimums are what
render.

### 4. "What happened" subtext

`.at-event__sub` is now single-line with an ellipsis (`@include truncate`, `max-width: 420px`).

### 5. Admin name colour

Admin names render in `#074C8C` via `.at-person__name--admin`.

This needed a new field on the person shape, since the colour is per-person, not per-row — in
the design, an admin actor is blue in "Who Did It" while a non-admin target stays black on the
same row. Added `role` to the contract and to `AUDIT_PERSON_ROLES` in `constants/auditTrail.js`.

It is a **string** (`admin` / `user` / `organization` / `system`), not the numeric `USER_ROLES`,
deliberately: `USER_ROLES.CUSTOMER` is `2` while `ROLE_LABELS` in `Sidebar.js` labels `2` as
"Admin". That contradiction already exists in the codebase; a new contract shouldn't inherit it.
**The backend must send `role` on both `actor` and `target`.**

### Verification

- ESLint on all changed files: 0 errors, 0 warnings (the six in `TableWithGlobalFilter.js` are
  pre-existing).
- `react-scripts build`: compiles, no warning references an Audit Trail file.
- Temporary RTL suite asserted the rendered DOM: all six min-widths present inline on both
  `th` and `td`, Audit ID's fixed width intact, `--admin` class on the admin actor and absent on
  a non-admin, and the four spot-checked category chips resolving to the right tone class.
  Passed, then deleted.
