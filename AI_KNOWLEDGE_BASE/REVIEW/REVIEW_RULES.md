# Review Rules — what `tools/review.php` checks

Every rule encodes a trap documented elsewhere in this KB. **If a rule can't cite a doc that explains
why it matters, it doesn't belong here** — a rule people learn to ignore is worse than no rule.

Run: `composer review -- --repo=backend --base=develop --head=<branch>`

---

## Rule 0 — the route-guard delta (not a regex)

| | |
|---|---|
| **Id** | `R-B00` |
| **Severity** | **CRITICAL** when a previously-guarded route is now public · **HIGH** for a brand-new public route · **INFO** when a route stops being public |
| **Why** | [ROUTES.md](../ROUTES.md) · [INDEXES/PUBLIC_ROUTE_AUDIT.md](../INDEXES/PUBLIC_ROUTE_AUDIT.md) |

`routes/api.php` expresses authorisation by a route's **physical position** inside nested group
closures. A route can become public because a `});` moved — with **no change to the route's own line**.
No line-based diff or regex can see that.

So the scanner parses `routes/api.php` at the **merge base** and at the branch tip with the same AST
walker the indexes use (`tools/lib/RouteParser.php`), expands `Route::resource`/`apiResource`, computes
the public set at each revision, and diffs them.

> It uses `git merge-base base head`, matching what `git diff base...head` shows. Comparing branch tips
> instead would report the base branch's own drift as this PR's doing.

**Requires `--base`.** Skipped when reviewing a bare patch or uncommitted work.

---

## Pattern rules

Regexes over **added lines only**, skipping comment lines.

### Backend

| Id | Sev | Fires on | Why | Doc |
|---|---|---|---|---|
| `R-B01` | HIGH | `$request->all()` in a controller | defeats the FormRequest; every `$fillable` column becomes writable, `user_id` included | [REQUESTS.md](../REQUESTS.md) · [S-14](../SECURITY.md) |
| `R-B02` | HIGH | `env(` under `app/` | `config:cache` runs at boot; `env()` returns `null` outside config files | [CONFIGURATION.md](../CONFIGURATION.md) |
| `R-B03` | HIGH | `protected $listen` in `EventServiceProvider` | that provider is **not registered** — the array binds nothing | [EVENTS.md](../EVENTS.md) |
| `R-B04` | HIGH | `dd(` `dump(` `var_dump(` `ray(` `print_r(` | leftover debug | [CODING_GUIDELINES.md](../CODING_GUIDELINES.md) |
| `R-B05` | **CRITICAL** | `sk_live_` `sk_test_` `AKIA…` `BEGIN … PRIVATE KEY` | credential in source | [SECURITY.md](../SECURITY.md) |
| `R-B06` | HIGH | `Log::*` whose **arguments** contain `$request->all()` or a token/password/secret **value** | the codebase already logs a live 24-hour credential | [LOGGING.md](../LOGGING.md) |
| `R-B07` | HIGH | `DB::raw(` containing `$` | interpolation into raw SQL | [REPOSITORIES.md](../REPOSITORIES.md) |
| `R-B08` | HIGH | `Schema::dropIfExists` in a migration | `entrypoint.sh` runs `migrate --force` each boot and **continues on failure** | [DEPLOYMENT.md](../DEPLOYMENT.md) |
| `R-B09` | MEDIUM | `getAvailableCredits(` / `getTotalUserCredit(` **without** `'Unlimited'` within ±12 added lines | returns `int\|string`; unguarded arithmetic blocks unlimited customers | [CREDITS_CONTEXT](../CONTEXT/CREDITS_CONTEXT.md) |
| `R-B10` | MEDIUM | `return response()->json(` in a controller | eight response shapes already exist | [ERROR_HANDLING.md](../ERROR_HANDLING.md) |
| `R-B11` | MEDIUM | `tokenCan(` in a policy | must match `login()`'s 9-item super-admin ability array or the check inverts | [POLICIES.md](../POLICIES.md) |
| `R-B12` | MEDIUM | `set_time_limit(0)` | removes the execution ceiling; the existing use fronts a ≤500-email loop | [QUEUES.md](../QUEUES.md) |
| `R-B13` | MEDIUM | empty `catch (…) {}` | swallows the only diagnostic this codebase gives you | [ERROR_HANDLING.md](../ERROR_HANDLING.md) |
| `R-B14` | LOW | `ApiResponse::success(200` — a bare status integer | use an `HttpStatus` constant | [HELPERS.md](../HELPERS.md) |
| `R-B15` | LOW | change to `TEST_PLATE_URL_*_SECONDS` | cache TTL (880) must stay **below** URL validity (900) | [CACHE.md](../CACHE.md) |
| `R-B16` | HIGH | a `*ServiceProvider.php` changed **without** `bootstrap/providers.php` in the diff | an unregistered provider never runs, with no error | [CONFIGURATION.md](../CONFIGURATION.md) |
| `R-B17` | MEDIUM | `$request->all()` under `app/Services` | services should receive validated data, not the request | [SERVICES.md](../SERVICES.md) |

### Frontend

| Id | Sev | Fires on | Why | Doc |
|---|---|---|---|---|
| `R-F01` | HIGH | a new `axiosInstance.<verb>('/api/…')` whose path matches **no route** in `.data/facts.json` | the call 404s | [INDEXES/CONTRACT_DRIFT.md](../INDEXES/CONTRACT_DRIFT.md) |
| `R-F02` | MEDIUM | plain `lazy(() => import(…))` under `src/router` | use `lazyWithRetry` — plain `lazy` breaks on a stale chunk after deploy | [FRONTEND.md](../FRONTEND.md) |
| `R-F03` | MEDIUM | a hard-coded `http(s)://` URL under `src/` | the API base comes from `REACT_APP_BASE_URL` | [ENVIRONMENT.md](../ENVIRONMENT.md) |
| `R-F04` | LOW | `console.log` / `console.debug` | leftover output | [CODING_GUIDELINES.md](../CODING_GUIDELINES.md) |
| `R-F05` | **CRITICAL** | a secret literal | every `REACT_APP_*` value ships to the browser | [ENVIRONMENT.md](../ENVIRONMENT.md) |
| `R-F06` | HIGH | a route in `protectedRoutes.js` granted in **no** role's `parentRoutes` | `Router.js` filters it out — the page never renders and there is no error | [FRONTEND.md](../FRONTEND.md) |

`R-F06` is checked against the files **as they exist at HEAD**, not the diff, so a pre-existing gap in a
file the PR touches still surfaces.

### Website

| Id | Sev | Fires on | Why | Doc |
|---|---|---|---|---|
| `R-W01` | HIGH | `'use client'` under `app/` | `/app` is Server Components; the client half belongs in `/views/*Client.jsx` | [WEBSITE.md](../WEBSITE.md) |
| `R-W02` | HIGH | `NEXT_PUBLIC_*API/URL/SECRET/KEY` | `API_URL` is deliberately server-only | [WEBSITE.md](../WEBSITE.md) |
| `R-W03` | LOW | `from '../..'` | use the `@/` alias | [WEBSITE.md](../WEBSITE.md) |
| `R-W04` | LOW | `console.log` / `console.debug` | the proxy routes already log to the server log in production | [WEBSITE.md](../WEBSITE.md) |

### Cross-repo

| Id | Sev | Fires on | Why |
|---|---|---|---|
| `R-X01` | **CRITICAL** | a real `.env` file in the diff (`.env.example` and friends are excluded) | secrets must not be committed |
| `R-X02` | LOW | a `.md` file added inside a code repo (excluding `README`/`CHANGELOG`) | **all docs live in the KB** |

---

## Known limits — read before trusting a clean run

- **Added lines only.** A defect created by *deleting* a line is invisible, except to the route-delta
  check, which compares whole revisions.
- **Line-scoped regexes.** A pattern split across lines is missed. `R-B09`'s ±12-line window is a
  heuristic, not scope analysis.
- **No semantic analysis.** It cannot tell whether a query is correctly scoped, whether logic is right,
  or whether a race exists. That is the whole of §3–§5 of the
  [checklist](REVIEW_CHECKLIST.md) — and it is where the real defects are.
- **`R-F01` resolves against `.data/facts.json`.** Run `composer regenerate` first, or a route added in
  the same PR reads as missing.
- **The route delta needs both revisions.** Skipped for `--diff` and for uncommitted work.

**A clean run means "none of the known traps fired". It is not an approval.**

---

## Adding a rule

In `tools/review.php`, `rules()`:

```php
['R-B18', 'MEDIUM', '#your-regex#', 'path/hint', 'What is wrong and what to do instead.', 'DOC.md'],
```

- `path/hint` is a substring match on the file path — `''` means any file.
- Then document it in the table above with the KB link. **A rule without a doc reference gets ignored by
  reviewers, which erodes trust in every other rule.**
- Test it against a real branch before committing:
  `php tools/review.php --repo=backend --base=develop --head=<a branch you know>`
- Prefer a **false negative over a false positive.** Three noisy rules will get the whole tool
  switched off.

---

## `R-X99` — git failed

**CRITICAL.** Not a code finding: git could not resolve the range, so **nothing was reviewed** for that
repo. It is CRITICAL because the alternative — an empty report — reads as "clean", and a review tool
that silently reviews nothing is worse than no tool.

Usual cause: the ref doesn't exist locally. `TCV-Website`'s default branch is `main`, not `develop`, and
remote-only branches need `origin/` (or a `git fetch`).

```bash
php tools/review.php --repo=website --base=origin/main --head=origin/dev
```
