# Getting Started — for developers

Everything here lives in `tcv-ai-knowledge-base/`. **No code repo was modified**, and nothing here needs
to run in production. This is a documentation + tooling folder.

---

## 0. What you actually need

| | Requirement | Check |
|---|---|---|
| **To read the KB** | nothing | just open the markdown |
| **To regenerate the indexes** | PHP **8.2+** (CLI) with `tokenizer`, `json`, `mbstring` · Composer 2 | `php -v` · `composer -V` |
| **To use the agent** | Claude Code | `claude --version` |

**No Docker. No database. No `.env`. TCV-Backend does not need `composer install`.**
The generators read source files only.

On this machine PHP is XAMPP's (`C:\xampp\php\php.exe`, 8.2.12) and Composer is at `C:\composer`.
If `php` isn't on your PATH, add `C:\xampp\php` to it.

---

## 1. Just reading it (the common case)

Open **[AI_KNOWLEDGE_BASE/README.md](AI_KNOWLEDGE_BASE/README.md)** and use the task→docs table.

**Do not read the whole KB.** The point is that you read 1–3 files per task. Typical paths:

| You're about to… | Open |
|---|---|
| change an endpoint | `GUIDES/HOW_TO_TRACE_API.md` → `INDEXES/API_ENDPOINT_INDEX.md` |
| debug something | `GUIDES/HOW_TO_DEBUG.md` (symptom→cause table — check it *before* reading code) |
| touch credits / billing / LMS / auth | the matching `CONTEXT/*.md` pack |
| review risk before a release | `SECURITY.md` |
| work in the SPA | `FRONTEND.md` |

---

## 2. Running the generators

Only needed **after code changes**, to refresh `INDEXES/`.

### One-time setup
```bash
cd D:/Git/TCV/tcv-ai-knowledge-base
composer install          # vendors nikic/php-parser INTO THIS FOLDER only
```

### Regenerate
```bash
composer regenerate       # extract → extract-clients → render → verify
```

Equivalent, step by step:
```bash
php tools/extract.php            # TCV-Backend AST   → .data/facts.json
php tools/extract-clients.php    # SPA + website     → .data/clients.json
php tools/render.php             # both              → AI_KNOWLEDGE_BASE/INDEXES/*.md
php tools/verify.php             # links + counts    (exit 1 if a KB link is broken)
```

The tools resolve every path from [`config.json`](config.json) relative to this folder, so **you can run
them from any working directory**:
```bash
php D:/Git/TCV/tcv-ai-knowledge-base/tools/verify.php
```

### What a healthy run looks like
```
facts.json written
  classes: 186 · methods: 710 · tables: 52 · migrations: 109 · routes: 179
clients.json written
  frontend: 248 files · 74 SPA routes · 91 API call sites · 40 slices
  website:  94 files · 32 pages · 4 API proxy routes · 26 views
=== KB LINK CHECK ===
broken KB links: 0  ✅
prose counts match the extracted facts ✅
```

### After regenerating — the 60 seconds that matter

`git diff` (or just eyeball) **three files**. These are the early-warning system:

| File | Answers |
|---|---|
| `INDEXES/PUBLIC_ROUTE_AUDIT.md` | did a route accidentally become **public**? |
| `INDEXES/CONTRACT_DRIFT.md` | did a client call lose its backend endpoint? |
| `INDEXES/FRONTEND_ROUTE_INDEX.md` | did a page become unreachable by every role? |

Then hand-update only the affected prose. **Never regenerate the whole KB** — the prose carries the
traps; only `INDEXES/` is mechanical.

---

## 3. Using the agents

Two agents ship with the KB:

| Agent | Role | Tools |
|---|---|---|
| `tcv-dev` | **writes** code — new feature, change, bug fix | Read, Grep, Glob, Edit, Write, Bash |
| `tcv-reviewer` | **reviews** a PR / branch / uncommitted work | Read, Grep, Glob, Bash — **no Edit/Write** |

⚠️ **Claude Code discovers agents in `<working-directory>/.claude/agents/` or `~/.claude/agents/`.** As
shipped, they are only visible when you start Claude Code **inside this KB folder**. You almost
certainly want them available while working in the *code* repos, so install at user level:

**Windows (PowerShell)**
```powershell
New-Item -ItemType Directory -Force "$env:USERPROFILE\.claude\agents" | Out-Null
Copy-Item "D:\Git\TCV\tcv-ai-knowledge-base\.claude\agents\*.md" "$env:USERPROFILE\.claude\agents\"
```

**macOS / Linux**
```bash
mkdir -p ~/.claude/agents
cp tcv-ai-knowledge-base/.claude/agents/*.md ~/.claude/agents/
```

Then from any of the three repos:
```
> use the tcv-dev agent to add an endpoint that lists a patient's completed tests
> use tcv-dev: fix the credit balance showing 0 for unlimited customers
> use tcv-reviewer to review TCV-132 against develop
> use tcv-reviewer: review my uncommitted frontend changes
```

Re-copy the file whenever you edit the agent definition here — the profile copy is a snapshot, not a
symlink.

### Handing over a ticket

**Paste the ticket text as-is.** Do not explain the codebase — the KB already covers architecture,
traps, file locations and cross-repo contracts. Explaining it again wastes context and risks
contradicting the KB.

Add only the things the KB **cannot** know:

| Always give | Why |
|---|---|
| **The ticket text / acceptance criteria** | verbatim beats a summary |
| **Product intent** where behaviour is ambiguous | the code cannot tell you what was *meant* |
| **Repro steps + expected vs. actual** (bugs) | there's no staging data in the KB |

| Give if it applies | Why |
|---|---|
| Branch / PR target | default is `develop`; the CI trigger is `dev` ([DEPLOYMENT.md](AI_KNOWLEDGE_BASE/DEPLOYMENT.md)) |
| "Migration allowed / not allowed" | schema changes deploy via `entrypoint.sh`, which continues on failure |
| Which repo(s), if you already know | otherwise it will work it out from the KB |
| UI/UX expectation, copy, screenshots | not derivable from code |
| External context — Stripe mode, LMS provider, test org | credentials and provider config live outside the repos |
| Anything **out of scope** | stops scope creep into adjacent traps |

**Do not** paste file contents, describe the folder structure, or restate how auth/credits/the test flow
work. If you find yourself explaining the system, stop — either the KB covers it, or the KB has a gap
worth filling.

#### Template

```
TICKET: <id + title>
GOAL: <one or two sentences>
ACCEPTANCE CRITERIA:
  - …
REPRO (bugs only): <steps> → expected: … / actual: …
CONSTRAINTS: <branch · migration allowed? · out of scope>
PRODUCT DECISION: <any answer the code can't supply>
```

#### What you get back

Per the agent's output discipline: **what changed (`file:line`) · why · residual risk / blast radius ·
any KB doc now stale** — plus what it verified and what it couldn't (real test coverage exists only for
LMS and credit history, so it will say so rather than claim "tests pass").

#### When it will stop and ask

Where the code is genuinely ambiguous and guessing would be wrong. The KB flags these deliberately —
e.g. `discount_code_users` currently **denies** listed users while `priceTiers` **allows** listed tiers
([DISCOUNT_CONTEXT](AI_KNOWLEDGE_BASE/CONTEXT/DISCOUNT_CONTEXT.md)). A one-character fix flips who can
use every existing code, so that is a product decision, not a refactor. Answer it up front if your
ticket touches one.

### Agent memory (optional)

`.agent-memory/` holds a standing instruction ("update the KB after each passed task"). It is **not**
read from this folder — Claude Code reads memory from your profile. Install it per
[`.agent-memory/README.md`](.agent-memory/README.md). Skip this if you'd rather update the KB manually.

---

## 3b. Reviewing a PR

```bash
composer review -- --repo=backend --base=develop --head=origin/TCV-132
composer review -- --all --base=develop                    # all three repos
php tools/review.php --repo=frontend                       # uncommitted work
php tools/review.php --repo=backend --diff=pr.patch        # a saved patch
php tools/review.php --all --base=develop --fail-on=HIGH   # exit 1 for CI
```

The headline check parses `routes/api.php` at the **merge base** and the branch tip and diffs the
**public** route sets — guarding is positional here, so a route can become public because a `});` moved,
with no change to its own line. Nothing else in the toolchain catches that.

Then work the manual checklist. Full method:
[AI_KNOWLEDGE_BASE/REVIEW/README.md](AI_KNOWLEDGE_BASE/REVIEW/README.md).

> **A clean scan is not an approval.** It means none of the *known* traps fired. Ownership scoping,
> logic and product intent still need a human — that's what the checklist is for.

**Why this matters here:** CI runs no tests, and its lint steps write a summary without exiting
non-zero. Review is the only gate ([TESTING.md](AI_KNOWLEDGE_BASE/TESTING.md)).

## 4. Team setup checklist

- [ ] Clone/copy `tcv-ai-knowledge-base` as a **sibling** of the three code repos (see [PATHS.md](PATHS.md))
- [ ] `composer install` in the KB folder
- [ ] `composer regenerate` — confirm `0 broken KB links` and `prose counts match`
- [ ] Copy both agents (`tcv-dev.md`, `tcv-reviewer.md`) into `~/.claude/agents/`
- [ ] Agree that every PR gets `composer review` + the
      [checklist](AI_KNOWLEDGE_BASE/REVIEW/REVIEW_CHECKLIST.md) — CI gates nothing
- [ ] Bookmark [AI_KNOWLEDGE_BASE/README.md](AI_KNOWLEDGE_BASE/README.md)
- [ ] Read [SECURITY.md](AI_KNOWLEDGE_BASE/SECURITY.md) once, end to end — it is the highest-value
      15 minutes in here
- [ ] Agree who triages the `S-nn` findings (several are `critical`/`high`)

### Layout requirement

```
D:\Git\TCV\
├── tcv-ai-knowledge-base\   ← this folder
├── TCV-Backend\
├── TCV-Frontend\
└── TCV-Website\
```

If the layout differs, edit **`config.json` only** and re-run `composer regenerate`.
Verify reports layout problems as *"unresolved source links"* rather than failing.

---

## 5. Putting it in version control (optional, your call)

This folder is **not** a git repo yet. To make it one:

```bash
cd D:/Git/TCV/tcv-ai-knowledge-base
git init
git add .
git commit -m "Add TCV AI knowledge base"
```

`.gitignore` already excludes `vendor/`. `.data/*.json` **is** committed deliberately — it lets someone
read the machine-readable facts without running PHP.

Whether this becomes its own repo or a folder in an existing one is a team decision. Keeping it separate
matches how it's built (it documents three repos, so it can't live in any one of them).

---

## 6. Troubleshooting

| Symptom | Cause / fix |
|---|---|
| `php: command not found` | add `C:\xampp\php` to PATH |
| `Failed to open stream: vendor/autoload.php` | run `composer install` **in the KB folder** |
| `routes: 0` | `config.json` points somewhere wrong, or TCV-Backend isn't a sibling |
| Route count drops unexpectedly | a route form the static parser doesn't handle (`Route::controller`, `Route::match`, provider-registered routes). See [HOW_TO_REGENERATE.md](AI_KNOWLEDGE_BASE/GUIDES/HOW_TO_REGENERATE.md) |
| `broken KB links: N ❌` | a prose file links to a file that doesn't exist — fix the link |
| `N stale count(s) in prose` | a headline number in prose no longer matches source. Fix the **prose**, not `verify.php` |
| Agent `tcv-dev` not offered | not installed at user level — see §3 |
| `unresolved source links` | expected if the KB isn't a sibling of the code repos; fix `config.json` |

---

## 7. What this does *not* do

- It does not run, build, test or deploy any application.
- It does not modify `TCV-Backend`, `TCV-Frontend` or `TCV-Website` — and per the standing convention,
  neither should you for documentation: **all docs live here**.
- It does not replace reading the code. It tells you **which** code to read.
