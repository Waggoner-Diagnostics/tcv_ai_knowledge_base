<?php
/**
 * KB self-check: every internal markdown link must resolve to a real file, and the
 * headline counts quoted in the prose must match .data/counts.json.
 *
 * Usage: php tools/verify.php
 */
declare(strict_types=1);

$base = dirname(__DIR__);
$kb   = $base . '/AI_KNOWLEDGE_BASE';

$files = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($kb, FilesystemIterator::SKIP_DOTS));
foreach ($it as $f) {
    if ($f->isFile() && $f->getExtension() === 'md') $files[] = str_replace('\\', '/', $f->getPathname());
}
sort($files);

$repoNames = ['TCV-Backend', 'TCV-Frontend', 'TCV-Website'];
$broken = [];
$links  = 0;

foreach ($files as $file) {
    $dir  = dirname($file);
    $body = (string) file_get_contents($file);

    preg_match_all('/\[([^\]]+)\]\(([^)#]+)(#[^)]*)?\)/', $body, $m, PREG_SET_ORDER);
    foreach ($m as $match) {
        $target = trim($match[2]);
        if (preg_match('#^(https?:|mailto:)#', $target)) continue;
        $links++;

        if (realpath($dir . '/' . $target) !== false) continue;

        $intoRepo = false;
        foreach ($repoNames as $r) { if (str_contains($target, $r)) $intoRepo = true; }

        $broken[] = [
            'in'     => str_replace($base . '/', '', $file),
            'text'   => $match[1],
            'target' => $target,
            'repo'   => $intoRepo,
        ];
    }
}

$counts = json_decode((string) file_get_contents($base . '/.data/counts.json'), true);

echo "=== KB LINK CHECK ===\n";
printf("markdown files: %d\ninternal links: %d\n", count($files), $links);

$kbBroken   = array_values(array_filter($broken, fn($b) => !$b['repo']));
$repoBroken = array_values(array_filter($broken, fn($b) => $b['repo']));

if (!$kbBroken) {
    echo "broken KB links: 0  ✅\n";
} else {
    printf("broken KB links: %d  ❌\n", count($kbBroken));
    foreach ($kbBroken as $b) echo "  {$b['in']} → {$b['target']}  ({$b['text']})\n";
}
if ($repoBroken) {
    printf("unresolved source links: %d (into a code repo — expected only if the KB was moved)\n", count($repoBroken));
    foreach (array_slice($repoBroken, 0, 5) as $b) echo "  {$b['in']} → {$b['target']}\n";
}

echo "\n=== COUNTS (from source, must match prose) ===\n";
foreach ($counts as $k => $v) printf("  %-14s %d\n", $k, $v);

// Prose-count check. The expected values come from the extractors (counts.json), never from a
// hard-coded literal here — a hard-coded expectation goes stale silently the moment the code
// changes, which is the exact failure this check exists to catch.
echo "\n=== PROSE COUNT CHECK (prose must state the extracted numbers) ===\n";

// Phrases the prose uses to state each count, as regexes with the number as group 1.
// Deliberately narrow: they must match only REPO-WIDE claims. A loose pattern picks up local
// counts ("TestController | 23 methods") and buries the real drift in noise.
$patterns = [
    'routes'      => ['/\bof\s+(\d[\d,]*)\s+endpoints\b/i', '/\b(\d[\d,]*)\s+API endpoints\b/i', '/\|\s*API endpoints\s*\|\s*(\d[\d,]*)\s*\|/i'],
    'public'      => ['/\b(\d[\d,]*)\s+of\s+\d[\d,]*\s+endpoints are public/i', '/\*\*(\d[\d,]*)\s+public\*\*/i'],
    'classes'     => ['/\b(\d[\d,]*)\s+classes\/interfaces\/traits\b/i', '/\|\s*Classes\s*\|\s*(\d[\d,]*)\s*\|/i'],
    'methods'     => ['/\b(\d[\d,]*)\s+methods\s+across\s+\d+\s+classes/i', '/·\s*(\d[\d,]*)\s+methods\s*·/i'],
    'tables'      => ['/·\s*(\d[\d,]*)\s+tables\b/i', '/\|\s*Tables\s*\|\s*(\d[\d,]*)\s*\|/i', '/\b(\d[\d,]*)\s+tables\*\*/i'],
    'migrations'  => ['/\bfrom\s+(\d[\d,]*)\s+migrations\b/i', '/·\s*(\d[\d,]*)\s+migrations\b/i'],
    // These MUST stay anchored on repo-wide phrasing. A bare "N controllers" also matches local
    // claims like "~20 call sites in 5 controllers", which buries real drift in noise.
    'controllers' => ['/(\d[\d,]*)\s+classes under `app\/Http\/Controllers/i'],
    'models'      => ['/\b(\d[\d,]*)\s+Eloquent\s+models\b/i', '/across\s+(\d[\d,]*)\s+models\b/i'],
    'services'    => ['/(\d[\d,]*)\s+classes under `app\/Services/i'],
    'requests'    => ['/(\d[\d,]*)\s+classes in `app\/Http\/Requests/i', '/\b(\d[\d,]*)\s+FormRequest\b/i'],
    'policies'    => ['/\b(\d[\d,]*)\s+policies\b/i'],
    'middleware'  => ['/\|\s*Middleware\s*\|\s*(\d[\d,]*)\s*\|/i'],
    'relations'   => ['/\b(\d[\d,]*)\s+declared relationships\b/i'],
    'spa_routes'  => ['/\b(\d[\d,]*)\s+top-level routes\b/i'],
    'spa_slices'  => ['/\b(\d[\d,]*)\s+Redux slices\b/i'],
    'website_pages' => ['/\b(\d[\d,]*)\s+marketing pages\b/i'],
];

$stale = 0;
foreach ($patterns as $key => $regexes) {
    if (!isset($counts[$key])) continue;
    $expected = $counts[$key];
    $bad = [];
    foreach ($files as $f) {
        // Generated indexes are rendered from the same counts — only prose can drift.
        if (str_contains(str_replace('\\', '/', $f), '/INDEXES/')) continue;
        foreach (file($f) as $n => $line) {
            foreach ($regexes as $re) {
                if (preg_match($re, $line, $m) && (int) str_replace(',', '', $m[1]) !== $expected) {
                    $bad[] = sprintf('%s:%d claims %s', basename($f), $n + 1, $m[1]);
                }
            }
        }
    }
    $stale += count($bad);
    printf("  %-14s %-6d %s\n", $key, $expected, $bad ? 'STALE ❌ → ' . implode(' · ', array_slice($bad, 0, 4)) : 'OK');
}
printf("\n%s\n", $stale ? "$stale stale count(s) in prose — fix by hand, then re-run." : 'prose counts match the extracted facts ✅');

exit($kbBroken ? 1 : 0);
