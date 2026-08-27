# TCV — AI Knowledge Base

Reusable knowledge base for **TestingColorVision** across all three repos — `TCV-Backend` (`develop`),
`TCV-Frontend` (`develop`), `TCV-Website` (`main`) — built so an AI assistant can work on the project
without rescanning **~61,700 lines across 506 source files** (`TCV-Backend/app` 164 · `TCV-Frontend/src`
248 · `TCV-Website` 94).

## 🚀 Setting it up? → [GETTING_STARTED.md](GETTING_STARTED.md)

Requirements, how to run the generators, how to install the agent, and a team checklist.
**No Docker, no database, no `.env` — TCV-Backend does not even need `composer install`.**

## 👉 Start at [AI_KNOWLEDGE_BASE/README.md](AI_KNOWLEDGE_BASE/README.md)

That page has the task→docs routing table and the nine facts that cause bugs here. Don't read
everything; read what your task needs.

## 🔎 Reviewing a PR? → [AI_KNOWLEDGE_BASE/REVIEW/](AI_KNOWLEDGE_BASE/REVIEW/README.md)

```bash
composer review -- --repo=backend --base=develop --head=<branch>
```

An automated first pass whose headline check parses `routes/api.php` at **both** revisions and reports
routes that **became public** — guarding here is positional, so a line-by-line diff cannot see it. Then
work the manual [checklist](AI_KNOWLEDGE_BASE/REVIEW/REVIEW_CHECKLIST.md). There is also a
[`tcv-reviewer`](.claude/agents/tcv-reviewer.md) agent.

This matters because **CI gates nothing** here: the pipeline runs no tests and its lint steps never exit
non-zero. Review is the only gate.

## 🤖 Or use the agent: `tcv-dev`

[`.claude/agents/tcv-dev.md`](.claude/agents/tcv-dev.md) is a Claude Code subagent that drives the
workflow automatically — classify the task (new feature / update / bug), pick the repo, read only the
1–3 KB files it routes to, apply the traps, then make a tight change. Invoke it by naming the task
("add an endpoint for X", "fix the credit balance bug", "add a settings page to the portal"). It exists
to keep token use minimal by never rescanning the repos.

---

## Layout

```
tcv-ai-knowledge-base/
├── GETTING_STARTED.md          ← setup, running the tools, installing the agent, troubleshooting
├── config.json                 ← SINGLE SOURCE of repo locations (edit only this on a move)
├── PATHS.md                    ← how paths resolve; the sibling-layout convention
├── composer.json               ← vendors nikic/php-parser INTO THE KB, not into TCV-Backend
├── .agent-memory/              ← portable copy of Claude Code memory (see its README)
├── .claude/
│   ├── agents/tcv-dev.md       ← task-routing dev agent (writes code)
│   ├── agents/tcv-reviewer.md  ← PR review agent (read-only)
│   └── settings.json           ← denies reads of secrets and patient data
├── AI_KNOWLEDGE_BASE/          ← the knowledge base
│   ├── README.md               ← START HERE
│   ├── ARCHITECTURE_REALITY.md ← what exists vs. what's WIRED. Read before assuming a layer runs.
│   ├── SECURITY.md             ← 14 findings with stable S-nn ids
│   ├── … 30 more topic docs
│   ├── INDEXES/                ← 15 GENERATED indexes (never hand-edit)
│   ├── CONTEXT/                ← 10 packs, ~1–2k tokens each
│   ├── REVIEW/                 ← PR review: method, checklist, security gate, rule catalogue
│   └── GUIDES/                 ← 7 how-tos
├── tools/
│   ├── extract.php             ← TCV-Backend AST → .data/facts.json
│   ├── extract-clients.php     ← SPA + website scan → .data/clients.json
│   ├── render.php              ← facts → INDEXES/*.md
│   ├── review.php              ← PR scanner: route-guard delta + trap rules
│   ├── verify.php              ← link + count self-check
│   └── lib/RouteParser.php     ← shared AST route walker (extract + review)
└── .data/
    ├── facts.json              ← machine-readable: classes, methods, call graph, tables, routes, events
    ├── clients.json            ← SPA routes, role gating, API call sites; website pages + proxies
    └── counts.json
```

## Regenerating after a code change

```bash
composer install                 # one-time, after a fresh clone
composer regenerate              # extract → extract-clients → render → verify
```

…or step by step:

```bash
php tools/extract.php            # re-parse the backend (path from config.json)
php tools/extract-clients.php    # re-scan both clients
php tools/render.php             # rebuild the indexes
php tools/verify.php             # check links + counts
```

Then hand-update **only** the affected prose (context pack, feature index, change-impact entry).
Never regenerate the whole KB. Full detail:
[AI_KNOWLEDGE_BASE/GUIDES/HOW_TO_REGENERATE.md](AI_KNOWLEDGE_BASE/GUIDES/HOW_TO_REGENERATE.md).

### Two caveats

1. **Routes are parsed statically, not from `artisan route:list`.** TCV-Backend does not vendor its
   dependencies, so artisan cannot boot from a clean clone. `extract.php` walks `routes/api.php` and
   `routes/web.php` with the AST instead — tracking the group stack the way Laravel does and expanding
   `Route::resource`/`apiResource`. The source used is recorded in `facts.json` → `routes_source` and
   printed at the top of the API index; it never guesses silently. If you *do* run `composer install`
   inside TCV-Backend, the extractor automatically switches to `artisan route:list --json`, which is
   authoritative — prefer that.
2. **The client index is a lower bound.** PHP has no JS parser here, so the SPA scan reads literal
   `axios*.<verb>('…')` URLs. A URL built at runtime is invisible to it. *Absent from the index* does
   **not** prove *never called*.

## How this was built

- **Backend indexes** — extracted with `nikic/php-parser` v5.4, vendored in this KB. Line numbers,
  parameters, return types, Eloquent relationships, class constants and the call graph are read from the
  **AST**, never guessed. Migrations and the route table are parsed the same way.
- **Client indexes** — a lexical scan that also computes two derived views nothing else in the project
  has: **role-gating drift** (SPA routes no role can reach, and role grants no route defines) and
  **contract drift** (SPA calls with no matching backend route).
- **Prose** — hand-written from tracing the code, strongest on the security-critical paths (auth, the
  four token tiers, test execution, credits, LMS launch and delivery, organisation signatures, error
  handling).

## Last synced

**2026-08-19** — first generation. Indexes built from `TCV-Backend` `85586469` (`develop`, 2026-08-18),
`TCV-Frontend` `d7cdbc8` (`develop`, 2026-08-18), `TCV-Website` `9ea8202` (`main`, 2026-05-22). Compare
those SHAs with each repo's `git rev-parse --short HEAD` before trusting the generated indexes.

## Honest scope

- Generated indexes cover **100%** of backend classes, methods, tables and routes.
- Prose is marked **`[not deeply traced]`** where the code was not read closely (PDF templates, the
  Export classes, the super-admin dashboard aggregation, HubSpot, the ACH/bank-transfer payment
  branches) rather than padded with plausible-sounding text.
- Table columns are a **union across 109 migrations** — a column added then dropped may still appear.
  Confirm with `DESCRIBE` before relying on it.
- [`SECURITY.md`](AI_KNOWLEDGE_BASE/SECURITY.md)'s 14 findings are **observations from reading the
  code**, not the output of a pen test. Each names the file it came from so you can re-verify it in one
  read.

## Verification

```
php tools/verify.php   →   markdown files · internal links · 0 broken KB links
                           prose counts cross-checked against the extracted facts
```

## Related

- **Full-stack map (backend ↔ SPA ↔ website):**
  [AI_KNOWLEDGE_BASE/FULLSTACK_MAP.md](AI_KNOWLEDGE_BASE/FULLSTACK_MAP.md) — read for any change that
  crosses the wire. **Lives here in the KB only.**
- `TCV-Frontend` and `TCV-Website` each ship their own `CLAUDE.md`. Those files predate this KB and are
  left untouched; they are **not** the authority. The website's in particular is stale — it states the
  site has no API routes, and there are four.
