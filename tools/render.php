<?php
/**
 * TCV — AI Knowledge Base renderer.
 *
 * Turns .data/facts.json (backend AST) + .data/clients.json (SPA / website scan)
 * into the mechanical index documents. Every row here is derived from source, so
 * re-running extract.php + extract-clients.php + render.php after a code change
 * refreshes the indexes without anyone hand-editing them.
 *
 * Usage:  php tools/render.php
 */

declare(strict_types=1);

$base    = dirname(__DIR__);
$facts   = json_decode((string) file_get_contents($base . '/.data/facts.json'), true);
$clients = json_decode((string) @file_get_contents($base . '/.data/clients.json'), true) ?: null;
$kb      = $base . '/AI_KNOWLEDGE_BASE';
$idx     = $kb . '/INDEXES';

// Link prefixes for source files — derived ENTIRELY from config.json (single source of truth, see
// PATHS.md), so the generated INDEXES track a layout change with zero hand-editing. INDEXES/*.md sit
// two dirs below the KB root, so step up to the KB root (../../) then follow config's KB-root-relative
// repo path (e.g. ../TCV-Backend  →  ../../../TCV-Backend/).
$cfg      = json_decode((string) @file_get_contents($base . '/config.json'), true) ?: [];
$beLink   = '../../' . rtrim($cfg['repos']['backend']  ?? '../TCV-Backend', '/')  . '/';
$feLink   = '../../' . rtrim($cfg['repos']['frontend'] ?? '../TCV-Frontend', '/') . '/';
$webLink  = '../../' . rtrim($cfg['repos']['website']  ?? '../TCV-Website', '/')  . '/';

@mkdir($idx, 0777, true);

$stamp = "\n---\n\n_Generated from source by `tools/extract.php` + `tools/extract-clients.php` + "
       . "`tools/render.php` on " . date('Y-m-d') . ". Do not hand-edit — re-run the generator._\n";

$classes = $facts['classes'];
$byFile  = [];
foreach ($classes as $c) $byFile[$c['file']][] = $c;

/** Stable, deterministic ID assignment (sorted by name so IDs don't churn). */
function ids(array $items, string $prefix, string $key = 'name'): array
{
    usort($items, fn($a, $b) => strcmp((string) $a[$key], (string) $b[$key]));
    $out = [];
    foreach (array_values($items) as $i => $item) {
        $out[] = $item + ['id' => sprintf('%s-%03d', $prefix, $i + 1)];
    }
    return $out;
}

function pick(array $classes, string $needle): array
{
    return array_values(array_filter($classes, fn($c) => str_contains($c['file'], $needle)));
}

$controllers = ids(pick($classes, 'app/Http/Controllers'), 'CTRL');
$models      = ids(pick($classes, 'app/Models'), 'MODEL');
$services    = ids(pick($classes, 'app/Services'), 'SVC');
$jobs        = ids(pick($classes, 'app/Jobs'), 'JOB');
$middleware  = ids(pick($classes, 'app/Http/Middleware'), 'MW');
$requests    = ids(pick($classes, 'app/Http/Requests'), 'REQ');
$policies    = ids(pick($classes, 'app/Policies'), 'POL');
$events      = ids(pick($classes, 'app/Events'), 'EVT');
$listeners   = ids(pick($classes, 'app/Listeners'), 'LSN');
$commands    = ids(pick($classes, 'app/Console'), 'CMD');
$tables      = ids($facts['tables'], 'TABLE');

// ── Route table ──────────────────────────────────────────────────────────────
$apiRoutes = array_values(array_filter($facts['routes'], fn($r) => str_starts_with($r['uri'], 'api/')));
usort($apiRoutes, fn($a, $b) => strcmp($a['uri'], $b['uri']) ?: strcmp($a['method'], $b['method']));
$apiRoutes = array_map(fn($r, $i) => $r + ['id' => sprintf('API-%03d', $i + 1)], $apiRoutes, array_keys($apiRoutes));

$webRoutes = array_values(array_filter($facts['routes'], fn($r) => !str_starts_with($r['uri'], 'api/')));

/** Does this route require a caller to prove anything at all? */
function guarded(array $r): bool
{
    foreach ((array) $r['middleware'] as $m) {
        if (str_contains($m, 'auth:sanctum') || str_contains($m, 'FlexibleAuth') || $m === 'signed') return true;
    }
    return false;
}

function mwList(array $m): string
{
    $keep = array_values(array_filter($m, fn($x) => $x !== 'api' && $x !== 'web'));
    return $keep ? '`' . implode('` · `', $keep) . '`' : '—';
}

function actionShort(string $a): string
{
    $a = preg_replace('#^App\\\\Http\\\\Controllers\\\\#', '', $a);
    $a = preg_replace('#^Closure$#', '_(closure)_', (string) $a);
    return (string) $a;
}

// ── API_ENDPOINT_INDEX ───────────────────────────────────────────────────────
$srcNote = $facts['routes_source'];
$o = "# API Endpoint Index\n\n"
   . "Every `api/*` route in TCV-Backend, with the middleware that actually executes — the group stack a\n"
   . "route is physically nested inside, resolved the way Laravel resolves it.\n\n"
   . "**" . count($apiRoutes) . " endpoints.** Auth column: `auth:sanctum` = Sanctum token required · "
   . "`FlexibleAuthMiddleware` = **four** accepted token kinds (see [AUTH_CONTEXT](../CONTEXT/AUTH_CONTEXT.md)) · "
   . "`—` = **public**.\n\n"
   . "> Route source: `{$srcNote}`.\n"
   . "> `RestrictIpMiddleware` is appended **globally** in `bootstrap/app.php` and therefore runs on every\n"
   . "> row below; it is omitted from the table rather than repeated 179 times.\n\n"
   . "| ID | Method | URI | Action | Middleware | Route file |\n|---|---|---|---|---|---|\n";
foreach ($apiRoutes as $r) {
    $loc = $r['line'] ? "[api.php:{$r['line']}]({$GLOBALS['beLink']}routes/api.php#L{$r['line']})" : '—';
    $o .= "| `{$r['id']}` | {$r['method']} | `{$r['uri']}` | " . actionShort($r['action']) . " | " . mwList((array) $r['middleware']) . " | {$loc} |\n";
}
if ($webRoutes) {
    $o .= "\n## Non-`api/` routes (`routes/web.php`)\n\n| Method | URI | Action | Middleware |\n|---|---|---|---|\n";
    foreach ($webRoutes as $r) {
        $uri = $r['uri'] === '' ? '/' : $r['uri'];
        $o .= "| {$r['method']} | `{$uri}` | " . actionShort($r['action']) . " | " . mwList((array) $r['middleware']) . " |\n";
    }
}
file_put_contents($idx . '/API_ENDPOINT_INDEX.md', $o . $stamp);

// ── PUBLIC_ROUTE_AUDIT ───────────────────────────────────────────────────────
$public = array_values(array_filter($apiRoutes, fn($r) => !guarded($r)));
$o = "# Public (Unauthenticated) Endpoint Audit\n\n"
   . "Derived view: every `api/*` route reachable **with no token of any kind**. Auth in this codebase is\n"
   . "opt-in per route — a route added outside the `auth:sanctum` or `FlexibleAuthMiddleware` group is\n"
   . "public by default. This list is the blast radius of that design; re-read it every release.\n\n"
   . "**" . count($public) . " of " . count($apiRoutes) . " endpoints are public.**\n\n"
   . "Several are legitimately public (login and registration precede a token; the invitation and resume\n"
   . "flows authenticate by emailed token *inside* the controller). The ones to scrutinise are those that\n"
   . "read or mutate money, credits, or another user's data.\n\n"
   . "| ID | Method | URI | Action |\n|---|---|---|---|\n";
foreach ($public as $r) {
    $o .= "| `{$r['id']}` | {$r['method']} | `{$r['uri']}` | " . actionShort($r['action']) . " |\n";
}
$o .= "\n## Also public: `routes/web.php`\n\n| Method | URI | Action |\n|---|---|---|\n";
foreach ($webRoutes as $r) {
    if (guarded($r)) continue;
    $uri = $r['uri'] === '' ? '/' : $r['uri'];
    $o .= "| {$r['method']} | `{$uri}` | " . actionShort($r['action']) . " |\n";
}
file_put_contents($idx . '/PUBLIC_ROUTE_AUDIT.md', $o . $stamp);

// ── CLASS_INDEX ──────────────────────────────────────────────────────────────
$allIded = array_merge($controllers, $models, $services, $jobs, $middleware, $requests, $policies, $events, $listeners, $commands);
$o = "# Class Index\n\n**" . count($classes) . " classes/interfaces/traits** under `app/` + `database/`. "
   . "IDs are stable across regenerations (assigned by sorted name).\n\n"
   . "| ID | Class | Kind | Extends | File:Line | Methods |\n|---|---|---|---|---|---|\n";
foreach ($allIded as $c) {
    $o .= "| `{$c['id']}` | `{$c['name']}` | {$c['kind']} | " . ($c['extends'] ?: '—') . " | [{$c['file']}:{$c['line']}]({$beLink}{$c['file']}#L{$c['line']}) | " . count($c['methods']) . " |\n";
}
$listed = array_column($allIded, 'fqcn');
$other  = array_values(array_filter($classes, fn($c) => !in_array($c['fqcn'], $listed, true)));
if ($other) {
    $o .= "\n## Other classes (providers, exports, notifications, mail, rules, traits, support, seeders)\n\n"
        . "| Class | Kind | File:Line | Methods |\n|---|---|---|---|\n";
    foreach ($other as $c) {
        $o .= "| `{$c['name']}` | {$c['kind']} | [{$c['file']}:{$c['line']}]({$beLink}{$c['file']}#L{$c['line']}) | " . count($c['methods']) . " |\n";
    }
}
file_put_contents($idx . '/CLASS_INDEX.md', $o . $stamp);

// ── METHOD_INDEX ─────────────────────────────────────────────────────────────
$totalMethods = array_sum(array_map(fn($c) => count($c['methods']), $classes));
$o = "# Method Index\n\n**{$totalMethods} methods across " . count($classes) . " classes.**\n\n"
   . "Grouped by file; jump straight to the line. Use this instead of opening a controller to find a\n"
   . "method — several controllers here run 400–900 lines.\n\n";
ksort($byFile);
foreach ($byFile as $file => $cs) {
    $o .= "\n### `{$file}`\n\n| Class | Method | Line | Vis | Params | Returns |\n|---|---|---|---|---|---|\n";
    foreach ($cs as $c) {
        foreach ($c['methods'] as $m) {
            $p = $m['params'] ? '`' . implode('`, `', $m['params']) . '`' : '—';
            $o .= "| {$c['name']} | [`{$m['name']}()`]({$beLink}{$file}#L{$m['line']}) | {$m['line']} | {$m['visibility']}" . ($m['static'] ? ' static' : '') . " | {$p} | " . ($m['returns'] ?: '—') . " |\n";
        }
    }
}
file_put_contents($idx . '/METHOD_INDEX.md', $o . $stamp);

// ── FUNCTION_INDEX ───────────────────────────────────────────────────────────
$fns = ids($facts['functions'], 'FUNC');
$o = "# Function Index (global helpers)\n\n";
if (!$fns) {
    $o .= "**There are no global (free) PHP functions in this codebase.** `composer.json` has no\n"
        . "`autoload.files` entry, so nothing is callable without an import.\n\n"
        . "What plays the helper role instead — both are **static classes you must import**:\n\n"
        . "| Class | File | Role |\n|---|---|---|\n"
        . "| `App\\Helpers\\ApiResponse` | `app/Helpers/ApiResponse.php` | The JSON envelope every controller returns |\n"
        . "| `App\\Helpers\\TestHelper` | `app/Helpers/TestHelper.php` | Test-flow helpers |\n"
        . "| `App\\Support\\HttpStatus` · `App\\Support\\TestConstants` | `app/Support/` | Status codes and test constants |\n\n"
        . "See [METHOD_INDEX.md](METHOD_INDEX.md) for their methods, and [CONSTANTS.md](CONSTANTS.md)\n"
        . "for the constant values.\n";
} else {
    $o .= "**" . count($fns) . " global functions.**\n\n| ID | Function | File:Line | Params | Returns | Purpose |\n|---|---|---|---|---|---|\n";
    foreach ($fns as $f) {
        $p = $f['params'] ? '`' . implode('`, `', $f['params']) . '`' : '—';
        $o .= "| `{$f['id']}` | [`{$f['name']}()`]({$beLink}{$f['file']}#L{$f['line']}) | {$f['file']}:{$f['line']} | {$p} | " . ($f['returns'] ?: '—') . " | " . ($f['doc'] ?: '—') . " |\n";
    }
}
file_put_contents($idx . '/FUNCTION_INDEX.md', $o . $stamp);

// ── MODEL_INDEX ──────────────────────────────────────────────────────────────
$relCount = 0;
foreach ($models as $m) $relCount += count($m['relations']);
$o = "# Model Index\n\n**" . count($models) . " Eloquent models · {$relCount} declared relationships.**\n\n"
   . "`SoftDeletes` matters here: a soft-deleted row still occupies unique indexes and still satisfies a\n"
   . "foreign key. Check the trait column before writing a uniqueness or re-create path.\n\n"
   . "| ID | Model | File:Line | Traits | Relations | Methods |\n|---|---|---|---|---|---|\n";
foreach ($models as $m) {
    $rel = $m['relations'] ? implode(', ', array_map(fn($r) => "`{$r['method']}`→{$r['target']}", $m['relations'])) : '—';
    $tr  = $m['traits'] ? '`' . implode('`, `', $m['traits']) . '`' : '—';
    $o .= "| `{$m['id']}` | `{$m['name']}` | [{$m['file']}:{$m['line']}]({$beLink}{$m['file']}#L{$m['line']}) | {$tr} | {$rel} | " . count($m['methods']) . " |\n";
}
file_put_contents($idx . '/MODEL_INDEX.md', $o . $stamp);

// ── DATABASE_TABLE_INDEX ─────────────────────────────────────────────────────
$o = "# Database Table Index\n\n**" . count($tables) . " tables**, reconstructed from {$facts['migration_count']} migrations.\n\n"
   . "Columns are the **union of every `create`/`table` migration** touching the table, so a column added\n"
   . "and later dropped may still appear. The `Migrations` count is the audit trail — and `DESCRIBE` is\n"
   . "the only authority before you write a migration against a column.\n\n"
   . "| ID | Table | Columns | Created in | Migrations |\n|---|---|---|---|---|\n";
foreach ($tables as $t) {
    $created = $t['created_in'] ? '`' . basename($t['created_in']) . '`' : '_altered only_';
    $o .= "| `{$t['id']}` | `{$t['name']}` | " . count($t['columns']) . " | {$created} | " . count($t['migrations']) . " |\n";
}
$o .= "\n---\n\n## Column detail\n";
foreach ($tables as $t) {
    $o .= "\n### `{$t['name']}` — `{$t['id']}`\n\n";
    if (!$t['columns']) { $o .= "_No columns detected (index/constraint-only migrations)._\n"; continue; }
    $o .= "| Column | Type | Defined in |\n|---|---|---|\n";
    $seen = [];
    foreach ($t['columns'] as $c) {
        $k = $c['name'] . '|' . $c['type'];
        if (isset($seen[$k])) continue;
        $seen[$k] = 1;
        $o .= "| `{$c['name']}` | {$c['type']} | `" . basename($c['migration']) . "` |\n";
    }
    $drops = array_values(array_filter($t['indexes'], fn($i) => $i['kind'] === 'dropColumn'));
    if ($drops) {
        $o .= "\n_Dropped later by a migration (may still be listed above): "
            . implode(', ', array_map(fn($d) => "`{$d['column']}`", $drops)) . "._\n";
    }
}
file_put_contents($idx . '/DATABASE_TABLE_INDEX.md', $o . $stamp);

// ── FILE_INDEX ───────────────────────────────────────────────────────────────
$o = "# File Index\n\n**" . count($byFile) . " PHP files** containing classes, under `app/` + `database/`.\n\n"
   . "| File | Classes | Methods |\n|---|---|---|\n";
foreach ($byFile as $file => $cs) {
    $mc = array_sum(array_map(fn($c) => count($c['methods']), $cs));
    $o .= "| [`{$file}`]({$beLink}{$file}) | " . implode(', ', array_column($cs, 'name')) . " | {$mc} |\n";
}
file_put_contents($idx . '/FILE_INDEX.md', $o . $stamp);

// ── CONSTANTS ────────────────────────────────────────────────────────────────
$o = "# Constants Index\n\nClass constants are this codebase's stand-in for most enums. The ones that decide behaviour are\n"
   . "`User::SUPER_ADMIN|CUSTOMER|ORGANIZATION` and the `TestConstants` / `HttpStatus` sets — misreading\n"
   . "those is the recurring source of bugs. See also [ENUM_INDEX.md](ENUM_INDEX.md).\n\n";
$anyConst = false;
foreach ($classes as $c) {
    if (!$c['constants']) continue;
    $anyConst = true;
    $o .= "\n### `{$c['name']}` — `{$c['file']}`\n\n| Constant | Value | Line |\n|---|---|---|\n";
    foreach ($c['constants'] as $k) {
        $o .= "| `{$k['name']}` | " . ($k['value'] !== '' ? '`' . $k['value'] . '`' : '—') . " | [{$k['line']}]({$beLink}{$c['file']}#L{$k['line']}) |\n";
    }
}
if (!$anyConst) $o .= "_No class constants found._\n";
file_put_contents($idx . '/CONSTANTS.md', $o . $stamp);

// ── ENUM_INDEX ───────────────────────────────────────────────────────────────
$enums = array_values(array_filter($classes, fn($c) => $c['kind'] === 'enum'));
$o = "# Enum Index\n\n";
if (!$enums) {
    $o .= "**No native PHP `enum` types exist in this codebase.**\n\n"
        . "Enumerated values are expressed three other ways, none of them type-safe:\n\n"
        . "1. **Class constants** — `User::SUPER_ADMIN`, `App\\Support\\TestConstants`, `App\\Support\\HttpStatus`. See [CONSTANTS.md](CONSTANTS.md).\n"
        . "2. **Database `enum` / string columns** — see [DATABASE_TABLE_INDEX.md](DATABASE_TABLE_INDEX.md).\n"
        . "3. **Bare string literals** — the LMS session state machine (`launched`, `identity_resolved`,\n"
        . "   `form_submitted`, `test_assigned`, `completed`) travels as plain strings through\n"
        . "   `lms.status:` middleware arguments in `routes/api.php`. A typo there fails **open or closed\n"
        . "   silently** — there is no compiler check. See [CONTEXT/LMS_CONTEXT.md](../CONTEXT/LMS_CONTEXT.md).\n\n"
        . "> **Trap:** `users.usertype` skips 3 — `1 = SUPER_ADMIN`, `2 = CUSTOMER`, `4 = ORGANIZATION`.\n"
        . "> Do not assume the values are contiguous, and never iterate `1..n` over them.\n";
} else {
    $o .= "| Enum | File:Line |\n|---|---|\n";
    foreach ($enums as $e) $o .= "| `{$e['name']}` | [{$e['file']}:{$e['line']}]({$beLink}{$e['file']}#L{$e['line']}) |\n";
}
file_put_contents($idx . '/ENUM_INDEX.md', $o . $stamp);

// ── EVENT_INDEX ──────────────────────────────────────────────────────────────
$dispatches = array_values(array_filter($facts['events'], fn($e) => $e['kind'] === 'dispatch'));
$listens    = array_values(array_filter($facts['events'], fn($e) => $e['kind'] === 'listen'));
$o = "# Event & Listener Index\n\n"
   . "**" . count($events) . " events · " . count($listeners) . " listeners · " . count($listens) . " explicit `Event::listen` bindings.**\n\n"
   . "## Dispatch sites (`event(new …)`)\n\n| Event | Dispatched from |\n|---|---|\n";
foreach ($dispatches as $d) {
    $o .= "| `" . basename(str_replace('\\', '/', $d['event'])) . "` | [{$d['file']}:{$d['line']}]({$beLink}{$d['file']}#L{$d['line']}) |\n";
}
$o .= "\n## Explicit bindings (`Event::listen`)\n\n| Event | Listener | Bound in |\n|---|---|---|\n";
foreach ($listens as $l) {
    $o .= "| `{$l['event']}` | `{$l['listener']}` | [{$l['file']}:{$l['line']}]({$beLink}{$l['file']}#L{$l['line']}) |\n";
}
$o .= "\n> **Everything not listed under *explicit bindings* is wired by Laravel's automatic listener\n"
    . "> discovery** (any `app/Listeners` class whose `handle()` type-hints the event). `EventServiceProvider`\n"
    . "> is **not registered** in `bootstrap/providers.php`, so its `\$listen` array binds nothing — see\n"
    . "> [ARCHITECTURE_REALITY.md](../ARCHITECTURE_REALITY.md).\n";
file_put_contents($idx . '/EVENT_INDEX.md', $o . $stamp);

// ── Client indexes ───────────────────────────────────────────────────────────
$feCounts = $webCounts = [];
if ($clients) {
    $fe  = $clients['frontend'];
    $wb  = $clients['website'];
    $feCounts  = $fe['counts'];
    $webCounts = $wb['counts'];

    // FRONTEND_ROUTE_INDEX — SPA routes + the role gate, and where the two disagree.
    $parents = array_values(array_filter($fe['spa_routes'], fn($r) => !str_contains($r['kind'], 'child')));
    $parents = ids($parents, 'FE', 'path');

    $gatedAll = [];
    foreach ($fe['route_config'] as $role => $conf) {
        foreach ($conf['parentRoutes'] as $p) $gatedAll[$p][] = $role;
    }

    $o = "# Frontend SPA Route Index (TCV-Frontend)\n\n"
       . "**" . count($parents) . " top-level routes** (" . count(array_filter($fe['spa_routes'], fn($r) => str_contains($r['kind'], 'child'))) . " nested tab routes) from "
       . "`src/router/routes/`.\n\n"
       . "A protected route renders **only if it appears in both places**: `protectedRoutes.js` (the route\n"
       . "exists) *and* `RouteConfig[role].parentRoutes` (the role may see it). `Router.js` intersects the two\n"
       . "at runtime. The `Roles` column below is that intersection — `⚠ none` means the route is registered\n"
       . "but **unreachable by every role**.\n\n"
       . "| ID | Kind | Path | Component | Roles allowed |\n|---|---|---|---|---|\n";
    foreach ($parents as $r) {
        $roles = $r['kind'] === 'protected' ? ($gatedAll[$r['path']] ?? []) : ['(ungated)'];
        $rolesTxt = $r['kind'] === 'protected'
            ? ($roles ? implode(', ', $roles) : '⚠ **none**')
            : '_n/a — ' . $r['kind'] . '_';
        $o .= "| `{$r['id']}` | {$r['kind']} | `{$r['path']}` | `{$r['element']}` | {$rolesTxt} |\n";
    }

    $definedPaths = array_column($parents, 'path');
    $o .= "\n---\n\n## Gating drift\n\n";

    $unreachable = array_values(array_filter($parents, fn($r) => $r['kind'] === 'protected' && empty($gatedAll[$r['path']])));
    $o .= "### Registered but no role can reach it (" . count($unreachable) . ")\n\n";
    $o .= $unreachable
        ? "| Path | Component |\n|---|---|\n" . implode('', array_map(fn($r) => "| `{$r['path']}` | `{$r['element']}` |\n", $unreachable))
        : "_None._\n";

    $phantom = [];
    foreach ($gatedAll as $path => $roles) {
        if (!in_array($path, $definedPaths, true)) $phantom[$path] = $roles;
    }
    ksort($phantom);
    $o .= "\n### Granted to a role but no such route exists (" . count($phantom) . ")\n\n"
        . "Dead entries in `RouteConfig`. Harmless at runtime, but they make the config lie about what a\n"
        . "role can do — read the table above, not `routeConfig.js`, to answer \"can this role see X?\".\n\n";
    $o .= $phantom
        ? "| Path in RouteConfig | Granted to |\n|---|---|\n" . implode('', array_map(fn($p, $r) => "| `{$p}` | " . implode(', ', $r) . " |\n", array_keys($phantom), $phantom))
        : "_None._\n";

    $o .= "\n---\n\n## Child (tab) routes\n\n| Parent | Tab | Component |\n|---|---|---|\n";
    foreach ($fe['spa_routes'] as $r) {
        if (!str_contains($r['kind'], 'child')) continue;
        $o .= "| _(see `{$r['file']}`)_ | `{$r['path']}` | `{$r['element']}` |\n";
    }
    file_put_contents($idx . '/FRONTEND_ROUTE_INDEX.md', $o . $stamp);

    // FRONTEND_API_CALL_INDEX + CONTRACT_DRIFT
    $normBackend = [];
    foreach ($apiRoutes as $r) {
        $key = normKey($r['uri']);
        foreach (explode('|', $r['method']) as $verb) $normBackend[$verb . ' ' . $key] = $r;
        $normBackend['*' . ' ' . $key] = $r;
    }

    $calls = [];
    $seen  = [];
    foreach ($fe['api_calls'] as $c) {
        $k = $c['method'] . ' ' . $c['path'];
        if (isset($seen[$k])) { $calls[$seen[$k]]['sites'][] = $c; continue; }
        $seen[$k] = count($calls);
        $calls[] = ['method' => $c['method'], 'path' => $c['path'], 'sites' => [$c]];
    }

    $matched = $unmatched = [];
    foreach ($calls as $c) {
        $key = normKey($c['path']);
        $hit = $normBackend[$c['method'] . ' ' . $key] ?? null;
        // A PUT call satisfied by a PUT|PATCH resource route, and vice versa.
        if (!$hit && isset($normBackend['PUT|PATCH ' . $key])) $hit = $normBackend['PUT|PATCH ' . $key];
        if ($hit) {
            $matched[] = $c + ['route' => $hit];
        } else {
            $c['path_exists_other_verb'] = isset($normBackend['* ' . $key]);
            $unmatched[] = $c;
        }
    }

    $o = "# Frontend → Backend API Call Index\n\n"
       . "**" . count($calls) . " distinct calls** found across " . $feCounts['source_files'] . " TCV-Frontend source files, matched against the "
       . count($apiRoutes) . " backend endpoints in [API_ENDPOINT_INDEX.md](API_ENDPOINT_INDEX.md).\n\n"
       . "> **Lower bound, not a census.** These come from a lexical scan for literal `axios*.<verb>('…')`\n"
       . "> URLs. A URL built at runtime from variables is invisible to it, so *absent from this table*\n"
       . "> does **not** prove *never called*. Present rows, though, are real call sites with real line numbers.\n\n"
       . "## Resolved (" . count($matched) . ")\n\n| Method | Client path | Backend | Call site |\n|---|---|---|---|\n";
    foreach ($matched as $c) {
        $site = $c['sites'][0];
        $o .= "| {$c['method']} | `{$c['path']}` | `{$c['route']['id']}` " . actionShort($c['route']['action'])
            . " | [{$site['file']}:{$site['line']}]({$feLink}{$site['file']}#L{$site['line']})"
            . (count($c['sites']) > 1 ? ' _(+' . (count($c['sites']) - 1) . ')_' : '') . " |\n";
    }
    file_put_contents($idx . '/FRONTEND_API_CALL_INDEX.md', $o . $stamp);

    $o = "# Contract Drift — client calls with no backend route\n\n"
       . "Derived view: TCV-Frontend / TCV-Website call sites whose URL matches **no route** in\n"
       . "[API_ENDPOINT_INDEX.md](API_ENDPOINT_INDEX.md). Each one is a request that reaches the API and comes\n"
       . "back **404**.\n\n"
       . "**" . count($unmatched) . " unmatched of " . count($calls) . " distinct SPA calls.**\n\n"
       . "Read the `Why` column before acting — a row is one of three things:\n\n"
       . "- **`no such path`** — the endpoint does not exist at all. Either the client is calling a removed\n"
       . "  endpoint (fix the client) or one that was never built (fix the backend). Both are real bugs.\n"
       . "- **`verb mismatch`** — the path exists under a different HTTP method. Almost always a client bug.\n"
       . "- **`scanner limit`** — the literal URL is a fragment (a `{param}` base assembled elsewhere). Not a\n"
       . "  finding; the scanner simply cannot resolve it.\n"
       . "- **`dead file`** — the call is real, but it sits in a module **no other module imports**. Nothing\n"
       . "  reaches it at runtime, so it is not a live 404 — it is code to delete.\n\n"
       . "| Method | Client path | Why | Call site |\n|---|---|---|---|\n";
    foreach ($unmatched as $c) {
        $site   = $c['sites'][0];
        $orphan = true;
        foreach ($c['sites'] as $s) { if (empty($s['orphan'])) $orphan = false; }
        $isFragment = str_starts_with($c['path'], '/{param}') || $c['path'] === '/';
        if ($isFragment)                        $why = '_scanner limit_';
        elseif ($orphan)                        $why = '_dead file_';
        elseif ($c['path_exists_other_verb'])   $why = '**verb mismatch**';
        else                                    $why = '**no such path**';
        $o .= "| {$c['method']} | `{$c['path']}` | {$why} | [{$site['file']}:{$site['line']}]({$feLink}{$site['file']}#L{$site['line']})"
            . (count($c['sites']) > 1 ? ' _(+' . (count($c['sites']) - 1) . ')_' : '') . " |\n";
    }

    $o .= "\n---\n\n## TCV-Website proxy routes\n\n"
        . "The marketing site never calls the API from the browser. Four Next.js **server** routes forward to\n"
        . "the backend, so the browser only ever talks to the website's own origin.\n\n"
        . "| Website route | Methods | Forwards to | Backend route exists? |\n|---|---|---|---|\n";
    foreach ($wb['api_routes'] as $r) {
        foreach ($r['proxies'] ?: [''] as $target) {
            $key    = normKey($target);
            $verb   = $r['methods'][0] ?? 'GET';
            $exists = isset($normBackend[$verb . ' ' . $key]) ? '✅ `' . $normBackend[$verb . ' ' . $key]['id'] . '`' : '❌ **none**';
            $o .= "| `{$r['route']}` | " . implode(', ', $r['methods']) . " | `{$target}` | {$exists} |\n";
        }
    }
    file_put_contents($idx . '/CONTRACT_DRIFT.md', $o . $stamp);

    // WEBSITE_ROUTE_INDEX
    $pages = array_values(array_filter($wb['pages'], fn($p) => $p['kind'] === 'page'));
    $pages = ids($pages, 'WEB', 'route');
    $o = "# Website Route Index (TCV-Website)\n\n"
       . "**" . count($pages) . " pages · " . $webCounts['layouts'] . " layouts · " . $webCounts['api_routes'] . " server API routes** — Next.js App Router.\n\n"
       . "Every page is a **Server Component** that imports one `*Client.jsx` from `/views` for the\n"
       . "interactive half. `use_client` should be `false` for every row in `/app`; a `true` there means\n"
       . "someone broke the split.\n\n"
       . "| ID | Route | Client view | `'use client'` in /app? | File |\n|---|---|---|---|---|\n";
    foreach ($pages as $p) {
        $o .= "| `{$p['id']}` | `{$p['route']}` | " . ($p['view'] ? "`{$p['view']}`" : '_inline_') . " | "
            . ($p['use_client'] ? '⚠ **yes**' : 'no') . " | [{$p['file']}]({$webLink}{$p['file']}) |\n";
    }
    $o .= "\n## Layouts\n\n| Route | File |\n|---|---|\n";
    foreach ($wb['pages'] as $p) {
        if ($p['kind'] !== 'layout') continue;
        $o .= "| `{$p['route']}` | [{$p['file']}]({$webLink}{$p['file']}) |\n";
    }
    $o .= "\n## Server API routes\n\n| Route | Methods | Forwards to |\n|---|---|---|\n";
    foreach ($wb['api_routes'] as $r) {
        $o .= "| `{$r['route']}` | " . implode(', ', $r['methods']) . " | " . ($r['proxies'] ? '`' . implode('`, `', $r['proxies']) . '`' : '—') . " |\n";
    }
    file_put_contents($idx . '/WEBSITE_ROUTE_INDEX.md', $o . $stamp);
}

/** Comparable route shape: no leading slash, no `api/` doubling, every `{x}` collapsed to `{}`. */
function normKey(string $uri): string
{
    $u = strtolower(trim($uri, '/'));
    $u = preg_replace('/\{[^}]*\}/', '{}', $u);
    return (string) $u;
}

// ── counts.json ──────────────────────────────────────────────────────────────
$counts = [
    'routes'      => count($apiRoutes),
    'public'      => count($public),
    'classes'     => count($classes),
    'methods'     => $totalMethods,
    'functions'   => count($fns),
    'tables'      => count($tables),
    'migrations'  => $facts['migration_count'],
    'controllers' => count($controllers),
    'models'      => count($models),
    'services'    => count($services),
    'requests'    => count($requests),
    'policies'    => count($policies),
    'middleware'  => count($middleware),
    'jobs'        => count($jobs),
    'events'      => count($events),
    'listeners'   => count($listeners),
    'relations'   => $relCount,
];
if ($clients) {
    $counts += [
        'spa_routes'      => count(array_filter($clients['frontend']['spa_routes'], fn($r) => !str_contains($r['kind'], 'child'))),
        'spa_api_calls'   => count($calls ?? []),
        'spa_drift'       => count($unmatched ?? []),
        'spa_slices'      => $feCounts['slices'],
        'website_pages'   => $webCounts['pages'],
        'website_api'     => $webCounts['api_routes'],
    ];
}
file_put_contents($base . '/.data/counts.json', json_encode($counts, JSON_PRETTY_PRINT));

echo "rendered indexes:\n";
foreach ($counts as $k => $v) printf("  %-14s %d\n", $k, $v);
