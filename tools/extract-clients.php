<?php
/**
 * TCV clients — AI Knowledge Base extractor for TCV-Frontend and TCV-Website.
 *
 * Emits .data/clients.json: the SPA route table, the role→route gating config,
 * every backend URL the SPA calls, the Next.js route tree and its API proxy
 * routes. render.php cross-references the API call list against the backend
 * route table extracted by extract.php, which is what surfaces contract drift.
 *
 * HONEST LIMIT: this is a lexical scan (PHP has no JS parser here), not an AST
 * walk like the backend extractor. It reads literal string forms only. A URL
 * assembled at runtime from variables cannot be seen, so the API call list is a
 * lower bound — never treat "absent from the index" as "not called".
 *
 * Usage:  php tools/extract-clients.php
 */

declare(strict_types=1);

$kbRoot = dirname(__DIR__);
$cfg    = json_decode((string) @file_get_contents($kbRoot . '/config.json'), true) ?: [];
$fe     = rtrim($kbRoot . '/' . ($cfg['repos']['frontend'] ?? '../TCV-Frontend'), '/\\');
$web    = rtrim($kbRoot . '/' . ($cfg['repos']['website']  ?? '../TCV-Website'), '/\\');

/** Recursively collect files with the given extensions. */
function srcFiles(string $dir, array $exts = ['js', 'jsx', 'ts', 'tsx']): array
{
    if (!is_dir($dir)) return [];
    $out = [];
    $it  = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        $p = str_replace('\\', '/', $f->getPathname());
        if (str_contains($p, '/node_modules/') || str_contains($p, '/build/') || str_contains($p, '/.next/')) continue;
        if ($f->isFile() && in_array(strtolower($f->getExtension()), $exts, true)) $out[] = $p;
    }
    sort($out);
    return $out;
}

function relTo(string $root, string $file): string
{
    return ltrim(str_replace(str_replace('\\', '/', $root), '', $file), '/');
}

/** 1-indexed line number of a byte offset. */
function lineAt(string $body, int $offset): int
{
    return substr_count($body, "\n", 0, $offset) + 1;
}

$out = [
    'generated_at' => date('c'),
    'frontend'     => ['root' => $fe, 'spa_routes' => [], 'route_config' => [], 'api_calls' => [], 'slices' => [], 'hooks' => [], 'env' => [], 'counts' => []],
    'website'      => ['root' => $web, 'pages' => [], 'api_routes' => [], 'views' => [], 'components' => [], 'env' => [], 'counts' => []],
];

// ── TCV-Frontend: SPA route table ────────────────────────────────────────────
foreach ([['publicRoutes.js', 'public'], ['protectedRoutes.js', 'protected'], ['sharedRoutes.js', 'shared']] as [$fileName, $kind]) {
    $path = $fe . '/src/router/routes/' . $fileName;
    if (!is_file($path)) continue;
    $body = (string) file_get_contents($path);

    // Each entry is `{ path: '…', element: <X /> }`, optionally followed by a children: [ … ] block.
    preg_match_all("/\{\s*path:\s*'([^']+)'\s*,\s*element:\s*(?:<\s*([A-Za-z0-9_]+)|([A-Za-z0-9_]+))/", $body, $m, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
    foreach ($m as $set) {
        $routePath = $set[1][0];
        // Top-level entries render JSX (`element: <Page />`); nested children pass the
        // component itself (`element: Page`). That difference is the reliable marker —
        // a parent's own `children: [` block starts too close to key off `tab:`.
        $isChild   = ($set[2][0] ?? '') === '';
        $element   = $isChild ? ($set[3][0] ?? '') : $set[2][0];
        $out['frontend']['spa_routes'][] = [
            'kind'    => $isChild ? $kind . ':child' : $kind,
            'path'    => $routePath,
            'element' => $element,
            'file'    => 'src/router/routes/' . $fileName,
            'line'    => lineAt($body, $set[0][1]),
        ];
    }
}

// ── TCV-Frontend: role → allowed routes ──────────────────────────────────────
$rcPath = $fe . '/src/constants/routeConfig.js';
if (is_file($rcPath)) {
    $body = (string) file_get_contents($rcPath);
    // Split on each `[USER_ROLES.X]: {` block, then read its parentRoutes / childRoutes arrays.
    preg_match_all('/\[USER_ROLES\.([A-Z_]+)\]:\s*\{(.*?)\n  \},?/s', $body, $blocks, PREG_SET_ORDER);
    foreach ($blocks as $b) {
        $role   = $b[1];
        $parent = [];
        if (preg_match('/parentRoutes:\s*\[(.*?)\]/s', $b[2], $pm)) {
            preg_match_all("/['\"]([^'\"]+)['\"]/", $pm[1], $items);
            $parent = $items[1];
        }
        $child = [];
        if (preg_match('/childRoutes:\s*\{(.*?)\n    \}/s', $b[2], $cm)) {
            preg_match_all("/['\"]([^'\"]+)['\"]:\s*\[(.*?)\]/s", $cm[1], $cs, PREG_SET_ORDER);
            foreach ($cs as $c) {
                preg_match_all("/['\"]([^'\"]+)['\"]/", $c[2], $tabs);
                $child[$c[1]] = $tabs[1];
            }
        }
        $out['frontend']['route_config'][$role] = ['parentRoutes' => $parent, 'childRoutes' => $child];
    }
}

// ── TCV-Frontend: every literal backend URL the SPA calls ────────────────────
$feFiles = srcFiles($fe . '/src');

// A module nobody imports is dead code, and its API calls are dead calls. Collect every
// import specifier first so each call site can be tagged reachable / orphan — otherwise
// commented-out and abandoned files pollute the contract-drift report with phantom bugs.
$imported = [];
foreach ($feFiles as $file) {
    $body = (string) file_get_contents($file);
    preg_match_all('/(?:from|import\()\s*[\'"]([^\'"]+)[\'"]/', $body, $im);
    foreach ($im[1] as $spec) {
        $imported[pathinfo($spec, PATHINFO_FILENAME)] = true;
        $imported[basename(dirname($spec))] = true;   // `./foo` resolving to `foo/index.js`
    }
}
// Entry points are reached by the bundler, not by an import.
foreach (['index', 'App', 'setupTests', 'reportWebVitals'] as $entry) $imported[$entry] = true;

foreach ($feFiles as $file) {
    $rel   = relTo($fe, $file);
    $body  = (string) file_get_contents($file);
    $lines = explode("\n", $body);
    $orphan = !isset($imported[pathinfo($file, PATHINFO_FILENAME)]);

    preg_match_all(
        '/\b(?:axiosInstance|axios|api|instance|client)\s*\.\s*(get|post|put|patch|delete)\s*\(\s*([`\'"])(.*?)\2/s',
        $body,
        $m,
        PREG_SET_ORDER | PREG_OFFSET_CAPTURE
    );
    foreach ($m as $set) {
        $line = lineAt($body, $set[0][1]);
        // Skip commented-out calls — a `//`-ed or block-comment line is not a call site.
        $text = ltrim($lines[$line - 1] ?? '');
        if (str_starts_with($text, '//') || str_starts_with($text, '*') || str_starts_with($text, '/*')) continue;

        $out['frontend']['api_calls'][] = [
            'method' => strtoupper($set[1][0]),
            'path'   => normalisePath($set[3][0]),
            'raw'    => $set[3][0],
            'file'   => $rel,
            'line'   => $line,
            'orphan' => $orphan,
        ];
    }

    foreach (['REACT_APP_[A-Z0-9_]+', 'PUBLIC_URL'] as $pat) {
        preg_match_all('/process\.env\.(' . $pat . ')/', $body, $em);
        foreach ($em[1] as $v) $out['frontend']['env'][$v] = true;
    }
}

/** Reduce a JS template URL to a comparable route shape: `${x}` → `{x}`, drop query + base URL. */
function normalisePath(string $raw): string
{
    $p = preg_replace('/\$\{[^}]*REACT_APP_BASE_URL[^}]*\}/', '', $raw);
    $p = preg_replace('/\$\{([^}]*)\}/', '{param}', (string) $p);
    $p = explode('?', (string) $p)[0];
    $p = '/' . ltrim((string) $p, '/');
    return rtrim($p, '/') ?: '/';
}

$out['frontend']['env'] = array_keys($out['frontend']['env']);
sort($out['frontend']['env']);

usort($out['frontend']['api_calls'], fn($a, $b) => strcmp($a['path'], $b['path']) ?: strcmp($a['method'], $b['method']));

// Redux slices + hooks — the SPA's state seams.
foreach (srcFiles($fe . '/src/redux/slices') as $f) $out['frontend']['slices'][] = relTo($fe, $f);
foreach (srcFiles($fe . '/src/hooks') as $f)        $out['frontend']['hooks'][]  = relTo($fe, $f);

$out['frontend']['counts'] = [
    'source_files' => count($feFiles),
    'spa_routes'   => count($out['frontend']['spa_routes']),
    'api_calls'    => count($out['frontend']['api_calls']),
    'slices'       => count($out['frontend']['slices']),
    'hooks'        => count($out['frontend']['hooks']),
    'pages'        => count(srcFiles($fe . '/src/pages')),
    'components'   => count(srcFiles($fe . '/src/components')),
    'loc'          => locOf($feFiles),
];

function locOf(array $files): int
{
    $n = 0;
    foreach ($files as $f) $n += substr_count((string) file_get_contents($f), "\n") + 1;
    return $n;
}

// ── TCV-Website: Next.js App Router tree ─────────────────────────────────────
$webFiles = srcFiles($web . '/app');
foreach ($webFiles as $file) {
    $rel  = relTo($web, $file);
    $base = basename($rel);

    if (preg_match('/^(page|layout|not-found)\.(jsx?|tsx?)$/', $base)) {
        $routePath = '/' . trim(str_replace(['app/', $base], '', $rel), '/');
        $body      = (string) file_get_contents($file);
        $client    = str_contains($body, "'use client'") || str_contains($body, '"use client"');
        preg_match_all("/from\s+['\"]@\/views\/([A-Za-z0-9_]+)['\"]/", $body, $vm);
        $out['website']['pages'][] = [
            'route'      => $routePath === '/' ? '/' : rtrim($routePath, '/'),
            'kind'       => str_starts_with($base, 'page') ? 'page' : (str_starts_with($base, 'layout') ? 'layout' : 'not-found'),
            'file'       => $rel,
            'use_client' => $client,
            'view'       => $vm[1][0] ?? '',
        ];
    }

    if ($base === 'route.js' || $base === 'route.ts') {
        $body = (string) file_get_contents($file);
        preg_match_all('/export\s+async\s+function\s+(GET|POST|PUT|PATCH|DELETE)/', $body, $vm);
        preg_match_all('/`\$\{apiUrl\}(\/[^`]*)`|fetch\(\s*`\$\{apiUrl\}(\/[^`]*)`/', $body, $tm, PREG_SET_ORDER);
        $targets = [];
        foreach ($tm as $t) {
            $u = $t[1] !== '' ? $t[1] : ($t[2] ?? '');
            if ($u !== '') $targets[] = $u;
        }
        preg_match_all('/targetUrl\s*=\s*`\$\{apiUrl\}([^`]*)`/', $body, $tu);
        foreach ($tu[1] as $u) $targets[] = $u;

        $out['website']['api_routes'][] = [
            'route'   => '/' . trim(str_replace(['app/', $base], '', $rel), '/'),
            'methods' => $vm[1],
            'proxies' => array_values(array_unique($targets)),
            'file'    => $rel,
        ];
    }

    preg_match_all('/process\.env\.([A-Z0-9_]+)/', (string) file_get_contents($file), $em);
    foreach ($em[1] as $v) $out['website']['env'][$v] = true;
}

foreach (srcFiles($web . '/views') as $f)      $out['website']['views'][]      = relTo($web, $f);
foreach (srcFiles($web . '/components') as $f) $out['website']['components'][] = relTo($web, $f);

$out['website']['env'] = array_keys($out['website']['env'] ?: []);
sort($out['website']['env']);

usort($out['website']['pages'], fn($a, $b) => strcmp($a['route'], $b['route']) ?: strcmp($a['kind'], $b['kind']));

$allWeb = array_merge($webFiles, srcFiles($web . '/views'), srcFiles($web . '/components'), srcFiles($web . '/context'));
$out['website']['counts'] = [
    'source_files' => count($allWeb),
    'pages'        => count(array_filter($out['website']['pages'], fn($p) => $p['kind'] === 'page')),
    'layouts'      => count(array_filter($out['website']['pages'], fn($p) => $p['kind'] === 'layout')),
    'api_routes'   => count($out['website']['api_routes']),
    'views'        => count($out['website']['views']),
    'components'   => count($out['website']['components']),
    'loc'          => locOf($allWeb),
];

@mkdir($kbRoot . '/.data', 0777, true);
file_put_contents($kbRoot . '/.data/clients.json', json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

printf(
    "clients.json written\n  frontend: %d files · %d SPA routes · %d API call sites · %d slices\n  website:  %d files · %d pages · %d API proxy routes · %d views\n",
    $out['frontend']['counts']['source_files'],
    $out['frontend']['counts']['spa_routes'],
    $out['frontend']['counts']['api_calls'],
    $out['frontend']['counts']['slices'],
    $out['website']['counts']['source_files'],
    $out['website']['counts']['pages'],
    $out['website']['counts']['api_routes'],
    $out['website']['counts']['views']
);
