<?php
/**
 * TCV — PR review scanner.
 *
 * Mechanically checks a diff against the traps this KB documents. It is a *first pass*, not a
 * reviewer: it catches the known-recurring mistakes so a human (or the tcv-reviewer agent) spends
 * their attention on logic and product intent instead.
 *
 * Two kinds of check:
 *   1. ROUTE DELTA (backend) — parses routes/api.php at BOTH revisions with the same AST walker the
 *      indexes use, and reports routes that BECAME PUBLIC. This is the highest-value automated check
 *      in the repo and cannot be done with a regex.
 *   2. PATTERN RULES — regexes over ADDED lines only, each tied to a documented KB trap.
 *
 * Every finding cites the KB doc that explains WHY, so a reviewer can push back with a reference.
 *
 * Usage:
 *   php tools/review.php --repo=backend --base=develop --head=HEAD
 *   php tools/review.php --all --base=develop
 *   php tools/review.php --repo=frontend --base=origin/develop --head=TCV-132
 *   php tools/review.php --repo=backend                    # uncommitted work vs HEAD
 *   php tools/review.php --repo=backend --diff=my.patch
 *   php tools/review.php --all --base=develop --out=REVIEW.md
 *
 * Exit codes: 0 = nothing at or above --fail-on (default: never fails), 1 = findings at that level.
 */

declare(strict_types=1);

$kbRoot = dirname(__DIR__);
$cfg    = json_decode((string) @file_get_contents($kbRoot . '/config.json'), true) ?: [];

require $kbRoot . '/vendor/autoload.php';
require __DIR__ . '/lib/RouteParser.php';

use PhpParser\ParserFactory;

// ── args ─────────────────────────────────────────────────────────────────────
$opts = [];
foreach (array_slice($argv, 1) as $a) {
    if (preg_match('/^--([a-z-]+)(?:=(.*))?$/', $a, $m)) $opts[$m[1]] = $m[2] ?? true;
}
if (isset($opts['help'])) { fwrite(STDOUT, helpText()); exit(0); }

$repos = isset($opts['all'])
    ? ['backend', 'frontend', 'website']
    : [(string) ($opts['repo'] ?? 'backend')];

$base    = isset($opts['base']) ? (string) $opts['base'] : null;
$head    = isset($opts['head']) ? (string) $opts['head'] : null;
$outFile = isset($opts['out'])  ? (string) $opts['out']  : null;
$failOn  = strtoupper((string) ($opts['fail-on'] ?? ''));

$SEVERITY_ORDER = ['CRITICAL' => 4, 'HIGH' => 3, 'MEDIUM' => 2, 'LOW' => 1, 'INFO' => 0];

$parser  = (new ParserFactory())->createForNewestSupportedVersion();
$facts   = json_decode((string) @file_get_contents($kbRoot . '/.data/facts.json'), true) ?: [];
$report  = [];
$context = [];

foreach ($repos as $repoKey) {
    $repoPath = realpath($kbRoot . '/' . ($cfg['repos'][$repoKey] ?? ''));
    if (!$repoPath || !is_dir($repoPath . '/.git')) {
        $context[] = "skipped `{$repoKey}` — not a git repo at the path in config.json";
        continue;
    }

    $diff = isset($opts['diff'])
        ? (string) @file_get_contents((string) $opts['diff'])
        : gitDiff($repoPath, $base, $head);

    // A bad ref makes git print `fatal:` on stdout. Left unchecked that parses to zero findings and
    // reads as "nothing to review" — a silent false-clean, the worst failure mode for a review tool.
    if (str_contains($diff, 'fatal:')) {
        $firstLine = trim(explode("\n", $diff)[0]);
        $report[] = ['id' => 'R-X99', 'severity' => 'CRITICAL', 'repo' => $repoKey,
                     'file' => '(git)', 'line' => 0, 'snippet' => $firstLine,
                     'message' => "**git failed for `{$repoKey}` — nothing was reviewed.** Check that `{$base}` and `" . ($head ?? 'HEAD') . "` exist in that repo (try `git fetch`, or use `origin/{$base}`).",
                     'ref' => 'REVIEW/README.md'];
        continue;
    }

    if (trim($diff) === '') {
        $context[] = "`{$repoKey}` — no changes in range";
        continue;
    }

    $changes = parseDiff($diff);
    $context[] = sprintf('`%s` — %d file(s) changed, %d line(s) added', $repoKey, count($changes), array_sum(array_map(fn($c) => count($c['added']), $changes)));

    foreach (patternFindings($repoKey, $changes, $facts) as $f) $report[] = $f + ['repo' => $repoKey];
    foreach (crossFindings($repoKey, $changes) as $f)           $report[] = $f + ['repo' => $repoKey];

    if ($repoKey === 'backend' && $base !== null) {
        foreach (routeDelta($repoPath, $base, $head ?? 'HEAD', $parser) as $f) $report[] = $f + ['repo' => $repoKey];
    }
    if ($repoKey === 'frontend') {
        foreach (gatingFindings($repoPath, $changes) as $f) $report[] = $f + ['repo' => $repoKey];
    }
}

// ── output ───────────────────────────────────────────────────────────────────
usort($report, fn($a, $b) => ($GLOBALS['SEVERITY_ORDER'][$b['severity']] <=> $GLOBALS['SEVERITY_ORDER'][$a['severity']])
    ?: strcmp($a['file'], $b['file']) ?: ($a['line'] <=> $b['line']));

$md = renderReport($report, $context, $repos, $base, $head);

if ($outFile) {
    file_put_contents($outFile, $md);
    fwrite(STDOUT, "review written to {$outFile}\n");
}
fwrite(STDOUT, $md);

if ($failOn !== '' && isset($SEVERITY_ORDER[$failOn])) {
    foreach ($report as $f) {
        if ($SEVERITY_ORDER[$f['severity']] >= $SEVERITY_ORDER[$failOn]) exit(1);
    }
}
exit(0);

// ─────────────────────────────────────────────────────────────────────────────

function helpText(): string
{
    return <<<TXT
    TCV PR review scanner

      --repo=backend|frontend|website   which repo (default: backend)
      --all                             all three
      --base=<ref>                      base branch/commit. Enables the route-delta check.
      --head=<ref>                      head branch/commit (default: HEAD)
      --diff=<file>                     review a saved patch instead of running git
      --out=<file>                      also write the report to a file
      --fail-on=CRITICAL|HIGH|MEDIUM    exit 1 when a finding at/above this level exists
      --help

    With no --base, it reviews uncommitted work (git diff HEAD).

    TXT;
}

/** Unified diff for the range. `base...head` = merge-base diff, which is what a PR shows. */
function gitDiff(string $repo, ?string $base, ?string $head): string
{
    $range = $base === null
        ? 'HEAD'
        : escapeshellarg($base) . '...' . escapeshellarg($head ?? 'HEAD');

    return (string) @shell_exec(
        'git -C ' . escapeshellarg($repo) . ' diff --unified=0 --no-color --no-ext-diff ' . $range . ' 2>&1'
    );
}

/**
 * Parse a unified diff into per-file added/removed lines with real line numbers.
 * `--unified=0` means every hunk header gives us an exact new-file start line.
 */
function parseDiff(string $diff): array
{
    $files = [];
    $file  = null;
    $newLn = 0;

    foreach (explode("\n", $diff) as $line) {
        if (str_starts_with($line, '+++ ')) {
            $p = trim(substr($line, 4));
            if ($p === '/dev/null') { $file = null; continue; }
            $file = preg_replace('#^b/#', '', $p);
            $files[$file] ??= ['added' => [], 'removed' => []];
            continue;
        }
        if ($file === null) continue;

        if (preg_match('/^@@ -\d+(?:,\d+)? \+(\d+)(?:,\d+)? @@/', $line, $m)) {
            $newLn = (int) $m[1];
            continue;
        }
        if (str_starts_with($line, '+') && !str_starts_with($line, '+++')) {
            $files[$file]['added'][] = ['line' => $newLn, 'text' => substr($line, 1)];
            $newLn++;
            continue;
        }
        if (str_starts_with($line, '-') && !str_starts_with($line, '---')) {
            $files[$file]['removed'][] = ['line' => $newLn, 'text' => substr($line, 1)];
        }
    }

    return $files;
}

/**
 * The rule set. Each rule is a documented trap from this KB — if you add one, link the doc that
 * explains WHY, or the finding is just noise a reviewer will learn to ignore.
 */
function rules(string $repo): array
{
    $backend = [
        ['R-B01', 'HIGH',     '#\$request->all\(\)#',                                    'app/Http/Controllers',  '`$request->all()` discards the FormRequest\'s filtering — every $fillable column becomes writable, `user_id` included.', 'REQUESTS.md · S-14'],
        ['R-B17', 'MEDIUM',   '#\$request->all\(\)#',                                    'app/Services',          '`$request->all()` in a service — pass validated data in, don\'t reach for the request.', 'SERVICES.md'],
        ['R-B02', 'HIGH',     '#(?<!config/)\benv\(#',                                   'app/',                  '`env()` returns null outside config/ once `config:cache` runs at boot. Read `config(\'…\')`.', 'CONFIGURATION.md'],
        ['R-B03', 'HIGH',     '#protected\s+\$listen#',                                  'app/Providers/EventServiceProvider', '`EventServiceProvider` is NOT in bootstrap/providers.php — its `$listen` array binds nothing. Use auto-discovery or an explicit `Event::listen`.', 'EVENTS.md'],
        ['R-B04', 'HIGH',     '#\b(dd|dump|var_dump|ray|print_r)\s*\(#',                  '',                      'Leftover debug output.', 'CODING_GUIDELINES.md'],
        ['R-B05', 'CRITICAL', '#(sk_live_|sk_test_|rk_live_|AKIA[0-9A-Z]{16}|-----BEGIN [A-Z ]*PRIVATE KEY)#', '', 'Hard-coded credential in source.', 'SECURITY.md'],
        // Must match a logged VALUE, not the message string — "Log::info('Password changed', …)" is fine.
        ['R-B06', 'HIGH',     '#Log::(info|debug|warning|error)\(.*(\$request->all\(\)|=>\s*\$?(token|password|secret|api_key)|[\'"](token|password|secret|api_key)[\'"]\s*=>)#i', '', 'Logging a token, a password or a whole request body. `sendVerificationEmailForUser()` already logs a live credential — do not add more.', 'LOGGING.md'],
        ['R-B07', 'HIGH',     '#DB::raw\([^)]*\$#',                                      '',                      'String interpolation inside `DB::raw()` — bind parameters instead.', 'REPOSITORIES.md'],
        ['R-B08', 'HIGH',     '#Schema::dropIfExists#',                                  'database/migrations',   'Destructive migration. `entrypoint.sh` runs `migrate --force` on every boot and CONTINUES on failure.', 'DEPLOYMENT.md · DATABASE.md'],
        ['R-B09', 'MEDIUM',   '#getAvailableCredits\(|getTotalUserCredit\(#',             '',                      'Returns `int|string` — the string `\'Unlimited\'`. Guard with `!== \'Unlimited\'` before any arithmetic.', 'CONTEXT/CREDITS_CONTEXT.md'],
        ['R-B10', 'MEDIUM',   '#return\s+response\(\)->json\(#',                         'app/Http/Controllers',  'Hand-built response shape. Eight already exist; new code should use `ApiResponse::success/error`.', 'ERROR_HANDLING.md'],
        ['R-B11', 'MEDIUM',   '#tokenCan\(#',                                            'app/Policies',          'Ability checks must match the 9-item array `AuthController::login()` grants a super admin — otherwise the policy fails for super admins and passes for customers.', 'POLICIES.md · AUTHORIZATION.md'],
        ['R-B12', 'MEDIUM',   '#set_time_limit\(\s*0\s*\)#',                             '',                      'Removes the execution-time ceiling. The one existing use fronts a ≤500-email loop that should be queued.', 'QUEUES.md'],
        ['R-B13', 'MEDIUM',   '#catch\s*\([^)]*\)\s*\{\s*\}#',                           '',                      'Empty catch — swallows the only diagnostic this codebase gives you.', 'ERROR_HANDLING.md'],
        ['R-B14', 'LOW',      '#ApiResponse::(success|error)\(\s*\d{3}#',                 '',                      'Bare status integer. Use an `App\\Support\\HttpStatus` constant.', 'HELPERS.md'],
        ['R-B15', 'LOW',      '#TEST_PLATE_URL_(TTL|VALIDITY)_SECONDS\s*=#',              '',                      'The cache TTL (880) must stay BELOW the pre-signed URL validity (900), or a cache hit returns an expired URL.', 'CACHE.md'],
    ];

    $frontend = [
        ['R-F01', 'HIGH',     '#axiosInstance\s*\.\s*(get|post|put|patch|delete)\s*\(\s*[`\'"](/api/[^`\'"$]*)#', '', 'New backend call — confirm the endpoint exists (checked below against the route table).', 'INDEXES/CONTRACT_DRIFT.md'],
        ['R-F02', 'MEDIUM',   '#\blazy\s*\(\s*\(\s*\)\s*=>\s*import#',                   'src/router',            'Use `lazyWithRetry` — plain `lazy` breaks on a stale chunk after deploy.', 'FRONTEND.md'],
        ['R-F03', 'MEDIUM',   '#https?://(?!localhost)[^\s\'"`]+#',                       'src/',                  'Hard-coded URL. The API base comes from `REACT_APP_BASE_URL`.', 'ENVIRONMENT.md'],
        ['R-F04', 'LOW',      '#console\.(log|debug)\s*\(#',                              'src/',                  'Leftover console output.', 'CODING_GUIDELINES.md'],
        ['R-F05', 'CRITICAL', '#(sk_live_|sk_test_|AKIA[0-9A-Z]{16})#',                   '',                      'Secret in a browser bundle — every `REACT_APP_*` value ships to the client.', 'ENVIRONMENT.md'],
    ];

    $website = [
        ['R-W01', 'HIGH',     '#[\'"]use client[\'"]#',                                   'app/',                  '`/app` is Server Components only; the client half belongs in `/views/*Client.jsx`.', 'WEBSITE.md'],
        ['R-W02', 'HIGH',     '#NEXT_PUBLIC_[A-Z_]*(API|URL|SECRET|KEY)#',                '',                      '`API_URL` is deliberately server-only. A `NEXT_PUBLIC_` prefix ships it to the browser and reintroduces the CORS problem the proxy routes exist to solve.', 'WEBSITE.md'],
        ['R-W03', 'LOW',      '#from\s+[\'"]\.\./\.\.#',                                  '',                      'Use the `@/` alias, not relative traversal.', 'WEBSITE.md'],
        ['R-W04', 'LOW',      '#console\.(log|debug)\s*\(#',                              '',                      'Leftover console output (the proxy routes already log to the server log in production).', 'WEBSITE.md'],
    ];

    return match ($repo) {
        'frontend' => $frontend,
        'website'  => $website,
        default    => $backend,
    };
}

function patternFindings(string $repo, array $changes, array $facts): array
{
    $out = [];
    foreach (rules($repo) as [$id, $sev, $re, $pathHint, $msg, $ref]) {
        foreach ($changes as $file => $c) {
            if ($pathHint !== '' && !str_contains($file, $pathHint)) continue;
            foreach ($c['added'] as $a) {
                $text = $a['text'];
                if (ltrim($text) === '' || isCommentLine($text)) continue;
                if (!preg_match($re, $text, $m)) continue;

                // R-B09 is only a finding when the guard is absent from the same hunk.
                if ($id === 'R-B09' && hunkHas($c['added'], $a['line'], "'Unlimited'")) continue;
                // R-B01/R-B17 are about mass assignment; inside a Log:: call it's R-B06's business.
                if (($id === 'R-B01' || $id === 'R-B17') && str_contains($text, 'Log::')) continue;
                // R-F01 resolves against the real route table below rather than firing blind.
                if ($id === 'R-F01') {
                    $path = $m[2] ?? '';
                    if ($path === '' || routeExists($facts, $path)) continue;
                    $msg = "`{$path}` matches no route in the backend route table — this call 404s.";
                }

                $out[] = ['id' => $id, 'severity' => $sev, 'file' => $file, 'line' => $a['line'],
                          'snippet' => trim($text), 'message' => $msg, 'ref' => $ref];
            }
        }
    }
    return $out;
}

function crossFindings(string $repo, array $changes): array
{
    $out = [];
    foreach ($changes as $file => $c) {
        // `.env.example` is committed on purpose; only a real env file is a finding.
        if (preg_match('#(^|/)\.env(\.[a-z]+)?$#i', $file) && !preg_match('#\.(example|sample|dist|template)$#i', $file)) {
            $out[] = ['id' => 'R-X01', 'severity' => 'CRITICAL', 'file' => $file, 'line' => 1, 'snippet' => '',
                      'message' => 'An `.env` file is in the diff. Secrets must not be committed.', 'ref' => 'ENVIRONMENT.md'];
        }
        if (preg_match('#\.md$#i', $file) && $c['added'] !== [] && !preg_match('#(CHANGELOG|README)#i', $file)) {
            $out[] = ['id' => 'R-X02', 'severity' => 'LOW', 'file' => $file, 'line' => 1, 'snippet' => '',
                      'message' => 'Documentation added inside a code repo. The standing convention is that **all docs live in the KB**.', 'ref' => '.agent-memory/update-kb-after-passing-tasks.md'];
        }
        if ($repo === 'backend' && str_contains($file, 'app/Providers/') && !isset($changes['bootstrap/providers.php'])) {
            if (preg_match('#app/Providers/\w+ServiceProvider\.php$#', $file) && !str_contains($file, 'EventServiceProvider')) {
                $out[] = ['id' => 'R-B16', 'severity' => 'HIGH', 'file' => $file, 'line' => 1, 'snippet' => '',
                          'message' => 'A provider changed but `bootstrap/providers.php` is not in the diff. A provider absent from that list never runs — with no error.', 'ref' => 'CONFIGURATION.md'];
            }
        }
    }
    return $out;
}

/**
 * Frontend gating: a route registered in protectedRoutes.js must also be granted in routeConfig.js,
 * or it silently never renders. Checked against the files as they exist at HEAD, not the diff, so a
 * pre-existing gap in a file you touched still surfaces.
 */
function gatingFindings(string $repoPath, array $changes): array
{
    if (!isset($changes['src/router/routes/protectedRoutes.js']) && !isset($changes['src/constants/routeConfig.js'])) {
        return [];
    }

    $routesSrc = (string) @file_get_contents($repoPath . '/src/router/routes/protectedRoutes.js');
    $configSrc = (string) @file_get_contents($repoPath . '/src/constants/routeConfig.js');
    if ($routesSrc === '' || $configSrc === '') return [];

    preg_match_all("/\{\s*path:\s*'([^']+)'\s*,\s*element:\s*<\s*\w+/", $routesSrc, $rm);
    preg_match_all("/['\"]([^'\"]+)['\"]/", $configSrc, $cm);
    $granted = array_flip($cm[1]);

    $out = [];
    foreach ($rm[1] as $path) {
        if (isset($granted[$path])) continue;
        $out[] = ['id' => 'R-F06', 'severity' => 'HIGH', 'file' => 'src/router/routes/protectedRoutes.js', 'line' => 1,
                  'snippet' => $path,
                  'message' => "Route `{$path}` is registered but appears in no role's `parentRoutes` — `Router.js` filters it out, so the page never renders and there is no error.",
                  'ref' => 'FRONTEND.md · INDEXES/FRONTEND_ROUTE_INDEX.md'];
    }
    return $out;
}

/**
 * The highest-value check here: parse routes/api.php at BOTH revisions and report routes whose guard
 * disappeared. A regex cannot do this — guarding is positional, inside nested group closures.
 */
function routeDelta(string $repoPath, string $base, string $head, $parser): array
{
    $get = function (string $rev) use ($repoPath): ?string {
        $out = @shell_exec('git -C ' . escapeshellarg($repoPath) . ' show ' . escapeshellarg($rev . ':routes/api.php') . ' 2>&1');
        return ($out === null || str_contains((string) $out, 'fatal:')) ? null : (string) $out;
    };

    // Compare against the MERGE BASE, not the base tip — `git diff base...head` is a merge-base diff,
    // and comparing tips instead would report the base branch's own drift as this PR's doing.
    $mergeBase = trim((string) @shell_exec(
        'git -C ' . escapeshellarg($repoPath) . ' merge-base ' . escapeshellarg($base) . ' ' . escapeshellarg($head) . ' 2>&1'
    ));
    if ($mergeBase === '' || !preg_match('/^[0-9a-f]{7,40}$/', $mergeBase)) $mergeBase = $base;

    $baseSrc = $get($mergeBase);
    $headSrc = $get($head);
    if ($baseSrc === null || $headSrc === null) return [];

    $key = fn(array $r) => $r['method'] . ' ' . $r['uri'];
    $publicSet = function (string $src) use ($parser, $key): array {
        $set = [];
        foreach (parseRouteSource($src, 'api', ['api'], $parser) as $r) {
            if (!routeIsGuarded($r)) $set[$key($r)] = $r;
        }
        return $set;
    };
    $allSet = function (string $src) use ($parser, $key): array {
        $set = [];
        foreach (parseRouteSource($src, 'api', ['api'], $parser) as $r) $set[$key($r)] = $r;
        return $set;
    };

    $basePublic = $publicSet($baseSrc);
    $headPublic = $publicSet($headSrc);
    $baseAll    = $allSet($baseSrc);

    $out = [];
    foreach ($headPublic as $k => $r) {
        if (isset($basePublic[$k])) continue;
        $existed  = isset($baseAll[$k]);
        $severity = $existed ? 'CRITICAL' : 'HIGH';
        $why      = $existed
            ? 'was **guarded** on the base branch and is **public** on this branch — a guard was removed'
            : 'is a **new public endpoint** — reachable with no token of any kind';

        $out[] = ['id' => 'R-B00', 'severity' => $severity, 'file' => 'routes/api.php', 'line' => $r['line'],
                  'snippet' => $r['method'] . ' ' . $r['uri'] . '  →  ' . $r['action'],
                  'message' => "`{$r['uri']}` {$why}. Confirm this is intended; 21 endpoints are already public.",
                  'ref' => 'INDEXES/PUBLIC_ROUTE_AUDIT.md · ROUTES.md'];
    }
    foreach ($basePublic as $k => $r) {
        if (isset($headPublic[$k])) continue;
        $out[] = ['id' => 'R-B00', 'severity' => 'INFO', 'file' => 'routes/api.php', 'line' => $r['line'],
                  'snippet' => $r['method'] . ' ' . $r['uri'],
                  'message' => 'No longer public on this branch — a guard was added or the route was removed. Confirm no client depended on it being open.',
                  'ref' => 'INDEXES/PUBLIC_ROUTE_AUDIT.md'];
    }
    return $out;
}

function routeExists(array $facts, string $path): bool
{
    static $set = null;
    if ($set === null) {
        $set = [];
        foreach ($facts['routes'] ?? [] as $r) {
            $set[normRoute($r['uri'])] = true;
        }
    }
    return isset($set[normRoute($path)]);
}

function normRoute(string $uri): string
{
    $u = strtolower(trim(explode('?', $uri)[0], '/'));
    return (string) preg_replace(['/\$\{[^}]*\}/', '/\{[^}]*\}/'], ['{}', '{}'], $u);
}

function isCommentLine(string $text): bool
{
    $t = ltrim($text);
    return str_starts_with($t, '//') || str_starts_with($t, '*') || str_starts_with($t, '/*') || str_starts_with($t, '#');
}

/** Is $needle present on any added line within ±12 lines? Used to spot a guard next to a risky call. */
function hunkHas(array $added, int $line, string $needle): bool
{
    foreach ($added as $a) {
        if (abs($a['line'] - $line) <= 12 && str_contains($a['text'], $needle)) return true;
    }
    return false;
}

function renderReport(array $report, array $context, array $repos, ?string $base, ?string $head): string
{
    $counts = ['CRITICAL' => 0, 'HIGH' => 0, 'MEDIUM' => 0, 'LOW' => 0, 'INFO' => 0];
    foreach ($report as $f) $counts[$f['severity']]++;

    $range = $base === null ? 'uncommitted changes vs HEAD' : "`{$base}` … `" . ($head ?? 'HEAD') . '`';

    $o  = "# PR Review — automated first pass\n\n";
    $o .= '**Repos:** ' . implode(', ', array_map(fn($r) => "`{$r}`", $repos)) . " · **Range:** {$range} · "
        . '**Generated:** ' . date('Y-m-d H:i') . "\n\n";
    $o .= "| CRITICAL | HIGH | MEDIUM | LOW | INFO |\n|---|---|---|---|---|\n"
        . "| {$counts['CRITICAL']} | {$counts['HIGH']} | {$counts['MEDIUM']} | {$counts['LOW']} | {$counts['INFO']} |\n\n";

    foreach ($context as $c) $o .= "- {$c}\n";
    $o .= "\n";

    if (!$report) {
        $o .= "**No automated findings.** That is not an approval — it means none of the *known* traps\n"
            . "fired. Logic, ownership scoping and product intent still need a human: work through\n"
            . "`AI_KNOWLEDGE_BASE/REVIEW/REVIEW_CHECKLIST.md`.\n";
        return $o;
    }

    $o .= "---\n\n";
    $last = null;
    foreach ($report as $f) {
        if ($f['severity'] !== $last) {
            $o .= "\n## " . $f['severity'] . "\n\n";
            $last = $f['severity'];
        }
        $o .= "**`{$f['id']}`** · `{$f['repo']}` · `{$f['file']}:{$f['line']}`\n\n";
        if ($f['snippet'] !== '') {
            $o .= "```\n" . substr($f['snippet'], 0, 200) . "\n```\n\n";
        }
        $o .= $f['message'] . "\n\n_Why: " . $f['ref'] . "_\n\n";
    }

    $o .= "---\n\n"
        . "## Not checked automatically\n\n"
        . "Ownership scoping, business logic, product intent, race conditions, and anything needing a\n"
        . "product decision. Work through\n"
        . "[AI_KNOWLEDGE_BASE/REVIEW/REVIEW_CHECKLIST.md](../AI_KNOWLEDGE_BASE/REVIEW/REVIEW_CHECKLIST.md).\n";

    return $o;
}
