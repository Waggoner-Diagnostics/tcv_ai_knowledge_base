# PR Review

Reviewing a TCV pull request. **Start here**, then use the checklist for the areas the PR touches.

| | |
|---|---|
| **Automated first pass** | `composer review -- --repo=backend --base=develop --head=<branch>` |
| **Manual checklist** | [REVIEW_CHECKLIST.md](REVIEW_CHECKLIST.md) |
| **Security gate** | [SECURITY_REVIEW.md](SECURITY_REVIEW.md) |
| **What the scanner checks, and why** | [REVIEW_RULES.md](REVIEW_RULES.md) |
| **Agent** | `tcv-reviewer` — see [`.claude/agents/tcv-reviewer.md`](../../.claude/agents/tcv-reviewer.md) |

---

## Why this exists

CI **does not gate merges** on this project: `.github/workflows/non-prod.yml` runs no tests, and its
lint steps write a summary without exiting non-zero ([TESTING.md](../TESTING.md)). And the backend has
real test coverage for exactly two subsystems — LMS and credit history.

So **review is the only gate.** These docs make that gate repeatable instead of dependent on who picked
up the PR.

---

## The three-pass method

### Pass 1 — automated (30 seconds)

```bash
cd tcv-ai-knowledge-base
composer review -- --repo=backend --base=develop --head=origin/TCV-132
```

It does two things a human can't do quickly:

1. **Route-guard delta.** Parses `routes/api.php` at the merge base *and* at the branch tip with the
   same AST walker the indexes use, then diffs the **public** route sets. It reports a route that was
   guarded and is now public as **CRITICAL**. Guarding here is positional — a route's own line tells you
   nothing — so no regex or eyeball can catch this reliably.
2. **Pattern rules** tied to documented traps ([REVIEW_RULES.md](REVIEW_RULES.md)).

**A clean run is not an approval.** It means none of the *known* traps fired.

### Pass 2 — the checklist (the real review)

[REVIEW_CHECKLIST.md](REVIEW_CHECKLIST.md), filtered to the areas the PR touches. This is where
ownership scoping, business logic and product intent get caught — none of which a scanner can see.

If the PR touches money, credits, auth, patient data or routes, also run
[SECURITY_REVIEW.md](SECURITY_REVIEW.md).

### Pass 3 — KB drift

Did the PR change routes, models, migrations, SPA routes or API calls? Then the indexes are stale:

```bash
composer regenerate
```

…and diff the three derived views. See [the checklist's final section](REVIEW_CHECKLIST.md#8-kb-hygiene).

---

## Using the agent

```
> use the tcv-reviewer agent to review TCV-132 in TCV-Backend against develop
> use tcv-reviewer: review my uncommitted changes in TCV-Frontend
```

It runs pass 1, reads only the KB docs the changed files route to, and reports findings with
`file:line` + severity + the KB reference that explains why. Install it at user level first — see
[GETTING_STARTED.md](../../GETTING_STARTED.md#3-using-the-tcv-dev-agent).

---

## Reviewing without git access

If you only have a patch:

```bash
php tools/review.php --repo=backend --diff=/path/to/pr.patch
```

The pattern rules run; the route-delta check is skipped (it needs both revisions). The report says so.

---

## Severity — what to do

| | Meaning | Action |
|---|---|---|
| **CRITICAL** | A guard was removed, or a credential is in the diff | **Block.** Do not merge. |
| **HIGH** | A documented trap that has produced real defects here | Block unless the author justifies it in the PR |
| **MEDIUM** | Convention break, or a correctness risk needing a second look | Request changes, or accept with a comment |
| **LOW** | Style / leftovers (`console.log`, bare status ints) | Comment; don't block |
| **INFO** | Context worth confirming, not a defect | Confirm and move on |

Rules encode the KB's traps, not universal truths. **If a finding is wrong for this PR, say so in the
review and — if the rule is wrong in general — fix the rule.** A rule people learn to ignore is worse
than no rule.
