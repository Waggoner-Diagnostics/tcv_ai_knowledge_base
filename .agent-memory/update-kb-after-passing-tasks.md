---
name: update-kb-after-passing-tasks
description: "Standing instruction to update the tcv-ai-knowledge-base after each completed, passing task"
metadata:
  node_type: memory
  type: feedback
---

After completing a task in **any** of the three TCV repos — backend `TCV-Backend`, portal
`TCV-Frontend`, or marketing site `TCV-Website` — and once it has passed (verified / QA'd), update the
AI knowledge base (`tcv-ai-knowledge-base`) to reflect the change. (Repo locations are defined once in
the KB's `config.json` — see `PATHS.md`; the repos are siblings, so refer to them by **bare name**,
never an absolute `d:/…` path.)

**Why:** The KB is a separate folder — editing a code repo never auto-updates it. Keeping it current
after every task is what stops it drifting from the code. The three repos share auth, credit, patient-
form and error-status contracts, so a change often spans more than one (see the KB's `FULLSTACK_MAP.md`).

**All documentation lives in the KB ONLY.** Never create or edit doc files (architecture, onboarding,
`CLAUDE.md`, etc.) inside `TCV-Backend`, `TCV-Frontend` or `TCV-Website`. Code fixes belong in the code
repo; **all prose/architecture documentation belongs in the KB**. `TCV-Frontend` and `TCV-Website` each
already contain a legacy `CLAUDE.md` that predates the KB — leave those files alone, do not extend them,
and treat the KB as the authority (the website's is stale: it claims the site has no API routes, and it
has four).

**How to apply:**
- Hand-update only the affected KB prose docs (`CONTEXT/*` packs, `CHANGE_IMPACT_GUIDE.md`,
  `FEATURE_INDEX.md`, `FULLSTACK_MAP.md`, `SECURITY.md`, the relevant topic doc) with the behavioural
  change. Keep it terse, KB gotcha-style.
- If you fixed something recorded in `SECURITY.md`, **mark the `S-nn` finding fixed with the date** —
  don't delete it. Same for a trap listed in a context pack.
- The GENERATED indexes (`INDEXES/*`) come from
  `php tools/extract.php && php tools/extract-clients.php && php tools/render.php`.
  **No Docker and no database needed** — `nikic/php-parser` is vendored inside the KB itself
  (`composer install` in the KB root), and routes are parsed from the AST because TCV-Backend has no
  `vendor/`. Never fake index edits by hand.
- After regenerating, **diff the three derived views**: `PUBLIC_ROUTE_AUDIT.md` (did a route become
  public?), `CONTRACT_DRIFT.md` (did a client call lose its endpoint?), `FRONTEND_ROUTE_INDEX.md` (did a
  page become unreachable by every role?).
- Never regenerate the whole KB. After edits, run `php tools/verify.php` to confirm links + counts.
