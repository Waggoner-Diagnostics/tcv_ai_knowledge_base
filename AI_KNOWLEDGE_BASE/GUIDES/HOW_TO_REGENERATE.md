# How to Regenerate the Indexes

The `INDEXES/` folder is **generated, never hand-edited**. Everything else in the KB is prose you
maintain by hand.

## One-time setup

```bash
cd tcv-ai-knowledge-base
composer install          # vendors nikic/php-parser INTO THE KB — never into TCV-Backend
```

The KB carries its own `composer.json` precisely so the tooling does not depend on TCV-Backend having a
`vendor/` directory. `vendor/` here is gitignored; run `composer install` after a fresh clone.

Requirements: **PHP 8.2+** with the `tokenizer`, `json` and `mbstring` extensions. No Docker and no
database. `artisan` is used for routes *when TCV-Backend has a `vendor/`* — see the caveat below — but
the KB tooling never requires it.

## Check out the right branch first

☠️ **`extract.php` records whatever branch is checked out — it does not select one.** The `git` block in
`facts.json` comes from `rev-parse --abbrev-ref HEAD` in each repo, so regenerating while a repo sits on
a feature branch silently indexes that branch **and** labels the KB with it. Before any run:

```bash
git -C ../TCV-Backend  rev-parse --abbrev-ref HEAD    # expect develop
git -C ../TCV-Frontend rev-parse --abbrev-ref HEAD    # expect develop
git -C ../TCV-Website  rev-parse --abbrev-ref HEAD    # expect website-integration
```

The website is tracked on **`website-integration`**, not `develop` — the two are the same commit today
(`ce410d5`), but the integration branch is the one to index. Put each repo back on its own working
branch afterwards, and remember that the *next* regeneration will pick up wherever you left it.

Cross-check the `git` block in `facts.json` against the **Branches indexed** row in
[AI_KNOWLEDGE_BASE/README.md](../README.md) after every run; a mismatch there means the wrong tree was
indexed, and every count in this KB is describing code nobody asked about.

## The run

```bash
php tools/extract.php            # TCV-Backend AST      → .data/facts.json
php tools/extract-clients.php    # SPA + website scan   → .data/clients.json
php tools/render.php             # both                 → AI_KNOWLEDGE_BASE/INDEXES/*.md + .data/counts.json
php tools/verify.php             # links + prose counts
```

Or in one line:
```bash
composer regenerate      # runs all four, in order
```

`extract.php` optionally takes a backend path (`php tools/extract.php /some/path/TCV-Backend`);
otherwise it resolves it from [`config.json`](../../config.json).

## What each tool does

| Tool | Reads | Writes |
|---|---|---|
| `extract.php` | `TCV-Backend/app`, `database/{migrations,seeders,factories}`, `routes/*.php`, and `git rev-parse` in all three repos | `.data/facts.json` |
| `extract-clients.php` | `TCV-Frontend/src`, `TCV-Website/{app,views,components,context}` | `.data/clients.json` |
| `render.php` | both fact files | 15 index files + `.data/counts.json` |
| `verify.php` | every `.md` in the KB + `counts.json` | a report; exit 1 on a broken KB link |

## The one caveat you must know

**Routes come from one of two sources, and which one ran changes the numbers.**

`extract.php` prefers `php artisan route:list --json` when `TCV-Backend/vendor/autoload.php` exists.
When it does not, `artisan` cannot boot and `extract.php` falls back to walking `routes/api.php` and
`routes/web.php` with the AST — tracking the group stack (prefix + middleware) the way Laravel does, and
expanding `Route::resource` / `apiResource` including `->only()` and `->except()`.

The method used is always recorded in `facts.json` → `routes_source` and printed at the top of
[API_ENDPOINT_INDEX.md](../INDEXES/API_ENDPOINT_INDEX.md). It never guesses silently.

**The two sources do not produce identical counts.** Artisan sees what Laravel actually registers —
framework and package routes the AST parser cannot see (`sanctum/csrf-cookie`, `storage/{path}`, `up`,
and the `nnjeim/world` `{prefix?}/…` endpoints all appear under `routes/web.php` in the audit). It also
reports `GET|HEAD` where the AST parser reports `GET`. Expect a step change in the web-route rows the
first time the source flips; a step change in the **`api/*`** rows is what deserves scrutiny.

**If you run `composer install` inside TCV-Backend, `extract.php` automatically prefers
`php artisan route:list --json`** — Laravel's own router is authoritative and should be used when it is
available. The static parser is the fallback, not the preference.

**As of the 2026-08-28 sync, TCV-Backend *does* have `vendor/`, so the artisan path is what runs.**
Check `routes_source` in `facts.json` rather than assuming either one.

### Middleware names differ between the two sources — they are normalised

`artisan route:list --json` prints middleware as **fully-qualified class names**
(`Illuminate\Auth\Middleware\Authenticate:sanctum`); the AST parser and every prose doc here use
Laravel's **alias** vocabulary (`auth:sanctum`, `signed`, `throttle:60,1`). `extract.php` therefore runs
every artisan middleware string through `normaliseMiddleware()` before writing `facts.json`: known
Illuminate classes map to their alias, anything else keeps its short class name
(`App\Http\Middleware\FlexibleAuthMiddleware` → `FlexibleAuthMiddleware`).

☠️ **Do not remove that normalisation.** `guarded()` in `render.php` recognises an authenticated route by
matching `auth:sanctum` / `FlexibleAuth` / `signed`. Feed it raw FQCNs and it silently stops matching
`auth:sanctum` — on the first artisan-sourced run this reported **153 of the 176 routes then indexed as public,
against a true figure of 20**, because 130 Sanctum-guarded routes looked unguarded. The count check is what caught it. If you
add a middleware alias to the backend, add it to the map.

Known limits of the static parser: it does not resolve `Route::controller()`, `Route::match()`, or
routes registered from a service provider. None of those are used today; if one is introduced, the
counts will silently drop — which is what `verify.php`'s count check is for.

## The client scan is a lower bound

`extract-clients.php` is a **lexical scan** — PHP has no JS parser here. It reads literal
`axios*.<verb>('…')` URLs, skips commented-out lines, and tags call sites in modules that nothing
imports as `orphan`.

- A URL assembled at runtime is invisible to it (those appear as `_scanner limit_` rows in
  [CONTRACT_DRIFT.md](../INDEXES/CONTRACT_DRIFT.md)).
- **Absent from the index ≠ never called.** Present rows are real, with real line numbers.

## What to do after regenerating

1. **Diff the three derived views** — they are where regressions show up:
   - `INDEXES/PUBLIC_ROUTE_AUDIT.md` — did a route become public?
   - `INDEXES/CONTRACT_DRIFT.md` — did a client call lose its endpoint?
   - `INDEXES/FRONTEND_ROUTE_INDEX.md` — did a page become unreachable by every role?
2. **Run `verify.php`** and fix what it reports:
   - *broken KB links* → a prose file points at a file that doesn't exist. Exit code 1.
   - *stale prose counts* → a headline number in prose no longer matches the extracted facts. Fix the
     prose by hand; the expected values come from `counts.json`, never from a literal in `verify.php`.
3. **Hand-update only the affected prose** — the context pack, `FEATURE_INDEX.md`,
   `CHANGE_IMPACT_GUIDE.md`, and the "Code state at sync" row in
   [AI_KNOWLEDGE_BASE/README.md](../README.md).

**Never regenerate the whole KB.** The prose is the part that carries the traps; only the indexes are
mechanical.

## Adding an index

Render it in `render.php` and add its count to the `$counts` array. If prose will quote that count, add
a pattern for it to `verify.php`'s `$patterns` map so drift is caught automatically. Do **not** hard-code
an expected number in `verify.php` — the whole point is that expectations come from the source.
