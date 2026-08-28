<?php
/**
 * TCV Backend — AI Knowledge Base extractor.
 *
 * Parses TCV-Backend with nikic/php-parser and emits a single JSON fact file
 * (.data/facts.json) that every generated index is rendered from. Nothing here
 * guesses: line numbers, parameters, return types, Eloquent relationships, the
 * call graph and the route table all come from the AST.
 *
 * php-parser is vendored in THIS KB (composer.json here), not in TCV-Backend —
 * the KB never requires the backend's vendor/ to exist.
 *
 * Usage:  php tools/extract.php [/path/to/TCV-Backend]
 */

declare(strict_types=1);

// Backend path: an explicit CLI arg wins; otherwise the default comes from config.json
// (single source of truth for repo locations — see PATHS.md), resolved relative to the KB root.
$kbRoot = dirname(__DIR__);
$cfg    = json_decode((string) @file_get_contents($kbRoot . '/config.json'), true) ?: [];
$root   = rtrim($argv[1] ?? ($kbRoot . '/' . ($cfg['repos']['backend'] ?? '../TCV-Backend')), '/\\');

require $kbRoot . '/vendor/autoload.php';
require __DIR__ . '/lib/RouteParser.php';   // shared with tools/review.php

use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;

$parser     = (new ParserFactory())->createForNewestSupportedVersion();
$nodeFinder = new NodeFinder();

/** Recursively collect .php files under a directory. */
function phpFiles(string $dir): array
{
    if (!is_dir($dir)) return [];
    $out = [];
    $it  = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        if ($f->isFile() && $f->getExtension() === 'php') $out[] = str_replace('\\', '/', $f->getPathname());
    }
    sort($out);
    return $out;
}

/** Render a parameter node back to a readable signature fragment. */
function paramToString(Node\Param $p): string
{
    $type = $p->type ? typeToString($p->type) : '';
    $name = '$' . (is_string($p->var->name ?? null) ? $p->var->name : 'var');
    $def  = $p->default !== null ? ' = ' . defaultToString($p->default) : '';
    return trim($type . ' ' . $name . $def);
}

function typeToString($t): string
{
    if ($t === null) return '';
    if ($t instanceof Node\NullableType) return '?' . typeToString($t->type);
    if ($t instanceof Node\UnionType) return implode('|', array_map('typeToString', $t->types));
    if ($t instanceof Node\IntersectionType) return implode('&', array_map('typeToString', $t->types));
    if ($t instanceof Node\Identifier || $t instanceof Node\Name) return $t->toString();
    return '';
}

function defaultToString($d): string
{
    if ($d instanceof Node\Scalar\String_)  return "'" . $d->value . "'";
    if ($d instanceof Node\Scalar\Int_)     return (string) $d->value;
    if ($d instanceof Node\Scalar\Float_)   return (string) $d->value;
    if ($d instanceof Node\Expr\ConstFetch) return $d->name->toString();
    if ($d instanceof Node\Expr\Array_)     return '[]';
    return '…';
}

function firstDocLine(?string $doc): string
{
    if (!$doc) return '';
    foreach (explode("\n", $doc) as $line) {
        $line = trim($line, " \t*/\r\n");
        if ($line !== '' && !str_starts_with($line, '@')) return $line;
    }
    return '';
}

/** Literal string value of an arg, or '' when it isn't a plain string. */
function argString(?Node\Arg $a): string
{
    return ($a && $a->value instanceof Node\Scalar\String_) ? $a->value->value : '';
}

$facts = [
    'generated_at' => date('c'),
    'root'         => $root,
    'git'          => [],
    'classes'      => [],
    'functions'    => [],
    'tables'       => [],
    'routes'       => [],
    'events'       => [],
];

// Record the exact commit the indexes describe, so staleness is checkable.
foreach (['backend' => 'backend', 'frontend' => 'frontend', 'website' => 'website'] as $key => $_) {
    $path = $kbRoot . '/' . ($cfg['repos'][$key] ?? '');
    if (!is_dir($path . '/.git')) continue;
    $sha  = trim((string) @shell_exec('git -C ' . escapeshellarg($path) . ' rev-parse --short HEAD 2>&1'));
    $br   = trim((string) @shell_exec('git -C ' . escapeshellarg($path) . ' rev-parse --abbrev-ref HEAD 2>&1'));
    $date = trim((string) @shell_exec('git -C ' . escapeshellarg($path) . ' log -1 --format=%cd --date=short 2>&1'));
    $facts['git'][$key] = ['sha' => $sha, 'branch' => $br, 'date' => $date];
}

// `artisan route:list --json` prints middleware as fully-qualified class names
// (`Illuminate\Auth\Middleware\Authenticate:sanctum`), while the AST parser and every prose
// doc in this KB speak Laravel's alias vocabulary (`auth:sanctum`, `signed`, `throttle:60,1`).
// Normalise the artisan form to the alias form so the two route sources stay interchangeable —
// otherwise guarded() in render.php stops recognising authenticated routes and the
// public-route audit balloons (130 guarded routes once reported themselves public this way).
function normaliseMiddleware(string $m): string
{
    static $aliases = [
        'Illuminate\Auth\Middleware\Authenticate'                 => 'auth',
        'Illuminate\Auth\Middleware\AuthenticateWithBasicAuth'    => 'auth.basic',
        'Illuminate\Session\Middleware\AuthenticateSession'       => 'auth.session',
        'Illuminate\Auth\Middleware\Authorize'                    => 'can',
        'Illuminate\Auth\Middleware\RedirectIfAuthenticated'      => 'guest',
        'Illuminate\Auth\Middleware\EnsureEmailIsVerified'        => 'verified',
        'Illuminate\Auth\Middleware\RequirePassword'              => 'password.confirm',
        'Illuminate\Routing\Middleware\SubstituteBindings'        => 'bindings',
        'Illuminate\Routing\Middleware\ThrottleRequests'          => 'throttle',
        'Illuminate\Routing\Middleware\ThrottleRequestsWithRedis' => 'throttle',
        'Illuminate\Routing\Middleware\ValidateSignature'         => 'signed',
        'Illuminate\Http\Middleware\SetCacheHeaders'              => 'cache.headers',
    ];

    $class  = $m;
    $params = '';
    if (($pos = strpos($m, ':')) !== false) {
        $class  = substr($m, 0, $pos);
        $params = substr($m, $pos);          // keeps the leading ':'
    }

    if (isset($aliases[$class])) return $aliases[$class] . $params;

    // Not a Laravel alias: keep the short class name (App\Http\Middleware\FlexibleAuthMiddleware
    // → FlexibleAuthMiddleware) — how the prose and the AST path already refer to app middleware.
    if (($slash = strrpos($class, '\\')) !== false) return substr($class, $slash + 1) . $params;

    return $m;
}

// ── Pass 1: classes, methods, functions, constants, relationships ────────────
foreach (['app', 'database/seeders', 'database/factories'] as $sub) {
    foreach (phpFiles($root . '/' . $sub) as $file) {
        $rel  = ltrim(str_replace(str_replace('\\', '/', $root), '', $file), '/');
        try {
            $ast = $parser->parse((string) file_get_contents($file));
        } catch (\Throwable $e) {
            fwrite(STDERR, "parse error: $rel — {$e->getMessage()}\n");
            continue;
        }
        if (!$ast) continue;

        $ns = '';
        foreach ($nodeFinder->findInstanceOf($ast, Node\Stmt\Namespace_::class) as $n) {
            $ns = $n->name ? $n->name->toString() : '';
        }

        foreach ($nodeFinder->findInstanceOf($ast, Node\Stmt\ClassLike::class) as $c) {
            if (!isset($c->name) || $c->name === null) continue;
            $short = $c->name->toString();

            $kind = match (true) {
                $c instanceof Node\Stmt\Interface_ => 'interface',
                $c instanceof Node\Stmt\Trait_     => 'trait',
                $c instanceof Node\Stmt\Enum_      => 'enum',
                default                            => 'class',
            };

            $extends = '';
            if ($c instanceof Node\Stmt\Class_ && $c->extends) $extends = $c->extends->toString();

            $implements = [];
            if ($c instanceof Node\Stmt\Class_) {
                foreach ($c->implements as $i) $implements[] = $i->toString();
            }

            $traits = [];
            foreach ($nodeFinder->findInstanceOf($c, Node\Stmt\TraitUse::class) as $tu) {
                foreach ($tu->traits as $t) $traits[] = $t->toString();
            }

            $entry = [
                'name'       => $short,
                'fqcn'       => $ns ? $ns . '\\' . $short : $short,
                'kind'       => $kind,
                'file'       => $rel,
                'line'       => $c->getStartLine(),
                'end_line'   => $c->getEndLine(),
                'extends'    => $extends,
                'implements' => $implements,
                'traits'     => $traits,
                'doc'        => firstDocLine($c->getDocComment()?->getText()),
                'methods'    => [],
                'constants'  => [],
                'relations'  => [],
            ];

            foreach ($c->getMethods() as $m) {
                $calls = [];
                foreach ($nodeFinder->find($m, fn(Node $n) => $n instanceof Node\Expr\MethodCall || $n instanceof Node\Expr\StaticCall) as $call) {
                    $nm = $call->name instanceof Node\Identifier ? $call->name->toString() : null;
                    if (!$nm) continue;
                    if ($call instanceof Node\Expr\StaticCall && $call->class instanceof Node\Name) {
                        $calls[] = $call->class->toString() . '::' . $nm;
                    } else {
                        $calls[] = $nm;
                    }
                }
                $entry['methods'][] = [
                    'name'       => $m->name->toString(),
                    'line'       => $m->getStartLine(),
                    'end_line'   => $m->getEndLine(),
                    'visibility' => $m->isPrivate() ? 'private' : ($m->isProtected() ? 'protected' : 'public'),
                    'static'     => $m->isStatic(),
                    'params'     => array_map('paramToString', $m->params),
                    'returns'    => typeToString($m->returnType),
                    'doc'        => firstDocLine($m->getDocComment()?->getText()),
                    'calls'      => array_values(array_unique($calls)),
                ];

                // Eloquent relationships, read from the method body.
                foreach ($nodeFinder->findInstanceOf($m, Node\Expr\MethodCall::class) as $call) {
                    $nm = $call->name instanceof Node\Identifier ? $call->name->toString() : '';
                    if (!in_array($nm, ['hasOne', 'hasMany', 'belongsTo', 'belongsToMany', 'morphTo', 'morphMany', 'morphOne', 'hasOneThrough', 'hasManyThrough'], true)) continue;
                    $target = '';
                    if (isset($call->args[0]) && $call->args[0]->value instanceof Node\Expr\ClassConstFetch) {
                        $target = $call->args[0]->value->class->toString();
                    }
                    $entry['relations'][] = ['method' => $m->name->toString(), 'type' => $nm, 'target' => $target, 'line' => $m->getStartLine()];
                }
            }

            foreach ($nodeFinder->findInstanceOf($c, Node\Stmt\ClassConst::class) as $cc) {
                foreach ($cc->consts as $const) {
                    $value = '';
                    if ($const->value instanceof Node\Scalar\String_) $value = "'" . $const->value->value . "'";
                    elseif ($const->value instanceof Node\Scalar\Int_) $value = (string) $const->value->value;
                    elseif ($const->value instanceof Node\Expr\Array_) $value = '[…]';
                    $entry['constants'][] = ['name' => $const->name->toString(), 'value' => $value, 'line' => $cc->getStartLine()];
                }
            }

            $facts['classes'][] = $entry;
        }

        // free functions (there are none today — the check is what proves it)
        foreach ($nodeFinder->findInstanceOf($ast, Node\Stmt\Function_::class) as $fn) {
            $facts['functions'][] = [
                'name'    => $fn->name->toString(),
                'file'    => $rel,
                'line'    => $fn->getStartLine(),
                'params'  => array_map('paramToString', $fn->params),
                'returns' => typeToString($fn->returnType),
                'doc'     => firstDocLine($fn->getDocComment()?->getText()),
            ];
        }
    }
}

// ── Pass 2: database schema from migrations ──────────────────────────────────
$colTypes = ['string','text','integer','bigInteger','unsignedBigInteger','boolean','date','dateTime','timestamp','decimal','float','json','jsonb','enum','tinyInteger','unsignedInteger','unsignedTinyInteger','unsignedSmallInteger','longText','char','double','uuid','ulid','foreignId','foreignIdFor','bigIncrements','increments','id','mediumText','smallInteger','year','time','ipAddress','macAddress','binary','set'];

$migrationCount = 0;
foreach (phpFiles($root . '/database/migrations') as $file) {
    $migrationCount++;
    $rel = ltrim(str_replace(str_replace('\\', '/', $root), '', $file), '/');
    try { $ast = $parser->parse((string) file_get_contents($file)); } catch (\Throwable) { continue; }
    if (!$ast) continue;

    foreach ($nodeFinder->findInstanceOf($ast, Node\Expr\StaticCall::class) as $sc) {
        if (!($sc->class instanceof Node\Name) || $sc->class->toString() !== 'Schema') continue;
        $op = $sc->name instanceof Node\Identifier ? $sc->name->toString() : '';
        if (!in_array($op, ['create', 'table', 'dropIfExists', 'rename'], true)) continue;
        $table = argString($sc->args[0] ?? null);
        if ($table === '') continue;

        $facts['tables'][$table] ??= ['name' => $table, 'created_in' => '', 'dropped_in' => '', 'columns' => [], 'foreign_keys' => [], 'indexes' => [], 'migrations' => []];
        $facts['tables'][$table]['migrations'][] = ['file' => $rel, 'op' => $op, 'line' => $sc->getStartLine()];
        if ($op === 'create')        $facts['tables'][$table]['created_in'] = $rel;
        if ($op === 'dropIfExists')  $facts['tables'][$table]['dropped_in'] = $rel;

        foreach ($nodeFinder->findInstanceOf($sc, Node\Expr\MethodCall::class) as $mc) {
            $nm = $mc->name instanceof Node\Identifier ? $mc->name->toString() : '';
            $a0 = argString($mc->args[0] ?? null);

            if (in_array($nm, $colTypes, true) && $a0 !== '') {
                $facts['tables'][$table]['columns'][] = ['name' => $a0, 'type' => $nm, 'migration' => $rel];
            }
            if ($nm === 'foreign' && $a0 !== '') {
                $facts['tables'][$table]['foreign_keys'][] = ['column' => $a0, 'migration' => $rel];
            }
            if ($nm === 'foreignId' && $a0 !== '') {
                $facts['tables'][$table]['foreign_keys'][] = ['column' => $a0, 'migration' => $rel];
            }
            if (in_array($nm, ['unique', 'index', 'primary'], true)) {
                $facts['tables'][$table]['indexes'][] = ['kind' => $nm, 'column' => $a0, 'migration' => $rel];
            }
            if ($nm === 'dropColumn' && $a0 !== '') {
                $facts['tables'][$table]['indexes'][] = ['kind' => 'dropColumn', 'column' => $a0, 'migration' => $rel];
            }
        }
    }
}
$facts['migration_count'] = $migrationCount;

// ── Pass 3: routes ───────────────────────────────────────────────────────────
// TCV-Backend has no vendored dependencies checked in, so `artisan route:list`
// usually cannot boot. Prefer it when it can (Laravel's own router is
// authoritative for middleware group resolution); otherwise walk routes/*.php
// with the AST. Either way the source is recorded in facts.json — never fake it.
$facts['routes_source'] = '';

if (is_file($root . '/vendor/autoload.php')) {
    $json    = @shell_exec('cd ' . escapeshellarg($root) . ' && php artisan route:list --json 2>&1');
    $decoded = json_decode((string) $json, true);
    if (is_array($decoded) && $decoded !== []) {
        foreach ($decoded as $r) {
            $facts['routes'][] = [
                'method'     => $r['method'] ?? '',
                'uri'        => $r['uri'] ?? '',
                'action'     => $r['action'] ?? '',
                'middleware' => array_map('normaliseMiddleware', (array) ($r['middleware'] ?? [])),
                'name'       => $r['name'] ?? '',
                'line'       => 0,
                'source'     => 'artisan',
            ];
        }
        $facts['routes_source'] = 'artisan route:list --json';
    }
}

if ($facts['routes'] === []) {
    $facts['routes']        = staticRoutes($parser, $nodeFinder, $root);
    $facts['routes_source'] = 'AST static parse of routes/api.php + routes/web.php (TCV-Backend/vendor absent, so artisan cannot boot)';
}

// ── Pass 4: event wiring (who dispatches what, who listens) ──────────────────
foreach (phpFiles($root . '/app') as $file) {
    $rel = ltrim(str_replace(str_replace('\\', '/', $root), '', $file), '/');
    try { $ast = $parser->parse((string) file_get_contents($file)); } catch (\Throwable) { continue; }
    if (!$ast) continue;

    foreach ($nodeFinder->findInstanceOf($ast, Node\Expr\FuncCall::class) as $fc) {
        if (!($fc->name instanceof Node\Name) || $fc->name->toString() !== 'event') continue;
        $arg = $fc->args[0]->value ?? null;
        if ($arg instanceof Node\Expr\New_ && $arg->class instanceof Node\Name) {
            $facts['events'][] = ['kind' => 'dispatch', 'event' => ltrim($arg->class->toString(), '\\'), 'file' => $rel, 'line' => $fc->getStartLine()];
        }
    }
    foreach ($nodeFinder->findInstanceOf($ast, Node\Expr\StaticCall::class) as $sc) {
        if (!($sc->class instanceof Node\Name) || $sc->class->toString() !== 'Event') continue;
        if (!($sc->name instanceof Node\Identifier) || $sc->name->toString() !== 'listen') continue;
        $ev = ($sc->args[0]->value ?? null) instanceof Node\Expr\ClassConstFetch ? $sc->args[0]->value->class->toString() : '';
        $ln = ($sc->args[1]->value ?? null) instanceof Node\Expr\ClassConstFetch ? $sc->args[1]->value->class->toString() : '';
        $facts['events'][] = ['kind' => 'listen', 'event' => $ev, 'listener' => $ln, 'file' => $rel, 'line' => $sc->getStartLine()];
    }
}

$facts['tables'] = array_values($facts['tables']);

@mkdir($kbRoot . '/.data', 0777, true);
file_put_contents(
    $kbRoot . '/.data/facts.json',
    json_encode($facts, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
);

printf(
    "facts.json written\n  classes:    %d\n  methods:    %d\n  functions:  %d\n  tables:     %d\n  migrations: %d\n  routes:     %d (%s)\n",
    count($facts['classes']),
    array_sum(array_map(fn($c) => count($c['methods']), $facts['classes'])),
    count($facts['functions']),
    count($facts['tables']),
    $facts['migration_count'],
    count($facts['routes']),
    $facts['routes_source']
);
