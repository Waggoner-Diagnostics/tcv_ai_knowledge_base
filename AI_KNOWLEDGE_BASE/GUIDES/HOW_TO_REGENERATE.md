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

Requirements: **PHP 8.2+** with the `tokenizer`, `json` and `mbstring` extensions. Nothing else — no
Docker, no database, no artisan.

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

**Routes are parsed statically, not from `artisan route:list`.**

TCV-Backend does not vendor its dependencies, so `artisan` cannot boot from a clean clone. `extract.php`
therefore walks `routes/api.php` and `routes/web.php` with the AST — tracking the group stack (prefix +
middleware) the way Laravel does, and expanding `Route::resource` / `apiResource` including `->only()`
and `->except()`.

The method used is always recorded in `facts.json` → `routes_source` and printed at the top of
[API_ENDPOINT_INDEX.md](../INDEXES/API_ENDPOINT_INDEX.md). It never guesses silently.

**If you run `composer install` inside TCV-Backend, `extract.php` automatically prefers
`php artisan route:list --json`** — Laravel's own router is authoritative and should be used when it is
available. The static parser is the fallback, not the preference.

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
