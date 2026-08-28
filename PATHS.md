# Repo paths — single source of truth

All TCV repo locations live in **one file: [`config.json`](config.json)** (at the KB root). Nothing else
hardcodes an absolute path. If you move the project to a new machine or a different folder layout,
**edit `config.json` only.**

## The convention

The repos are **siblings under one parent** — the KB and the three code repos sit next to each other:

```
<any parent>/
├── tcv-ai-knowledge-base/   ← this KB (the single home for all docs)
├── TCV-Backend/             ← Laravel 12 API
├── TCV-Frontend/            ← React 18 SPA (admin portal + test player)
└── TCV-Website/             ← Next.js 15 marketing site
```

As long as that sibling layout holds, **nothing needs editing on a move** — the paths in `config.json`
are relative (`../TCV-Backend`, …) and the docs refer to repos by **bare name**.

## How each layer resolves a path

| Layer | How it finds a repo |
|---|---|
| **PHP tools** (`tools/extract.php`, `tools/extract-clients.php`, `tools/render.php`) | read `config.json`, relative to the KB root |
| **Generated indexes** (`INDEXES/*`) | `render.php` builds each link prefix from `config.json`'s repo paths |
| **Prose docs** (context packs, guides, maps) | refer to repos by **bare name** (`TCV-Backend`, `TCV-Frontend`, `TCV-Website`); their location is defined here / in `config.json` |

## If the layout changes (repos not siblings)

Edit `config.json` → set each repo's path relative to the KB root (e.g. `../../src/TCV-Backend`). Then
re-run `php tools/render.php` so the generated index links pick up the new location. Prose docs
reference no absolute paths, so they need no change unless a repo is **renamed** — then update its name
in `config.json` and in the prose.

`php tools/verify.php` reports unresolved links into a code repo separately from broken KB links, so a
layout mistake shows up as *"unresolved source links"* rather than a hard failure.

## Rule for new docs

**Never write an absolute path** (`d:/…`, `/Users/…`) in a KB doc. Name the repo (`TCV-Backend`) and let
`config.json` hold the location. Absolute paths only ever appear as a *default* inside `config.json`
itself — nowhere else.
