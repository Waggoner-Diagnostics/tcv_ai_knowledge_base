<?php
/**
 * Shared route parsing for the TCV KB tools.
 *
 * TCV-Backend does not vendor its dependencies, so `artisan route:list` cannot boot from a
 * clean clone. This walks routes/*.php with the AST instead — tracking the group stack
 * (prefix + middleware) the way Laravel does, and expanding Route::resource / apiResource
 * including ->only() / ->except().
 *
 * Used by:
 *   tools/extract.php  — to build the route table for the indexes
 *   tools/review.php   — to diff the PUBLIC route set between two git revisions
 *
 * Because review.php parses file CONTENT pulled from `git show`, the entry point here takes a
 * string rather than a path.
 */

declare(strict_types=1);

use PhpParser\Node;
use PhpParser\NodeFinder;

/** Parse one route file's source into a route list. */
function parseRouteSource(string $code, string $basePrefix, array $baseMiddleware, $parser): array
{
    try {
        $ast = $parser->parse($code);
    } catch (\Throwable) {
        return [];
    }
    if (!$ast) return [];

    $routes = [];
    walkRouteStmts($ast, $basePrefix, $baseMiddleware, $routes);
    return $routes;
}

/** Parse a repo's routes/api.php + routes/web.php from disk. */
function staticRoutes($parser, ?NodeFinder $finder, string $root): array
{
    $routes = [];

    foreach ([['routes/api.php', 'api', ['api']], ['routes/web.php', '', ['web']]] as [$relFile, $prefix, $mw]) {
        $path = $root . '/' . $relFile;
        if (!is_file($path)) continue;
        foreach (parseRouteSource((string) file_get_contents($path), $prefix, $mw, $parser) as $r) {
            $routes[] = $r;
        }
    }

    usort($routes, fn($a, $b) => strcmp($a['uri'], $b['uri']) ?: strcmp($a['method'], $b['method']));
    return $routes;
}

/** Unwind `Route::a()->b()->c()` into the base static call plus the chained calls, in source order. */
function unwindChain(Node\Expr $expr): ?array
{
    $chain = [];
    $cur   = $expr;
    while ($cur instanceof Node\Expr\MethodCall) {
        $chain[] = [$cur->name instanceof Node\Identifier ? $cur->name->toString() : '', $cur->args, $cur];
        $cur     = $cur->var;
    }
    if (!($cur instanceof Node\Expr\StaticCall) || !($cur->class instanceof Node\Name)) return null;
    if ($cur->class->toString() !== 'Route') return null;

    return [
        $cur->name instanceof Node\Identifier ? $cur->name->toString() : '',
        $cur->args,
        array_reverse($chain),
        $cur,
    ];
}

function routeArgString(?Node\Arg $a): string
{
    return ($a && $a->value instanceof Node\Scalar\String_) ? $a->value->value : '';
}

function walkRouteStmts(array $stmts, string $prefix, array $middleware, array &$routes): void
{
    foreach ($stmts as $stmt) {
        if (!($stmt instanceof Node\Stmt\Expression)) continue;
        $parsed = unwindChain($stmt->expr);
        if ($parsed === null) continue;
        [$baseName, $baseArgs, $chain, $baseNode] = $parsed;

        $verbs = ['get', 'post', 'put', 'patch', 'delete', 'options', 'any'];

        // Modifiers declared anywhere on the chain apply to whatever the chain defines.
        $localMw   = [];
        $routeName = '';
        $groupBody = null;
        $only = $except = null;
        foreach ($chain as [$callName, $callArgs, $callNode]) {
            if ($callName === 'middleware') {
                foreach ($callArgs as $a) {
                    if ($a->value instanceof Node\Scalar\String_) {
                        $localMw[] = $a->value->value;
                    } elseif ($a->value instanceof Node\Expr\Array_) {
                        foreach ($a->value->items as $it) {
                            if ($it && $it->value instanceof Node\Scalar\String_) $localMw[] = $it->value->value;
                        }
                    }
                }
            } elseif ($callName === 'name') {
                $routeName = routeArgString($callArgs[0] ?? null);
            } elseif ($callName === 'group') {
                $groupBody = $callArgs[0]->value ?? null;
            } elseif ($callName === 'prefix') {
                $prefixExtra = routeArgString($callArgs[0] ?? null);
                if ($prefixExtra !== '') $prefix = joinUri($prefix, $prefixExtra);
            } elseif ($callName === 'only' || $callName === 'except') {
                $vals = [];
                if (($callArgs[0]->value ?? null) instanceof Node\Expr\Array_) {
                    foreach ($callArgs[0]->value->items as $it) {
                        if ($it && $it->value instanceof Node\Scalar\String_) $vals[] = $it->value->value;
                    }
                }
                if ($callName === 'only') $only = $vals; else $except = $vals;
            }
        }

        // A group opened by the base call itself.
        if (in_array($baseName, ['middleware', 'prefix', 'group', 'name'], true)) {
            $childPrefix = $prefix;
            $childMw     = array_merge($middleware, $localMw);

            if ($baseName === 'middleware') {
                foreach ($baseArgs as $a) {
                    if ($a->value instanceof Node\Scalar\String_) {
                        $childMw[] = $a->value->value;
                    } elseif ($a->value instanceof Node\Expr\Array_) {
                        foreach ($a->value->items as $it) {
                            if ($it && $it->value instanceof Node\Scalar\String_) $childMw[] = $it->value->value;
                        }
                    }
                }
            } elseif ($baseName === 'prefix') {
                $p = routeArgString($baseArgs[0] ?? null);
                if ($p !== '') $childPrefix = joinUri($childPrefix, $p);
            }

            $body = $groupBody ?? (($baseName === 'group') ? ($baseArgs[0]->value ?? null) : null);
            if ($body instanceof Node\Expr\Closure) {
                walkRouteStmts($body->stmts, $childPrefix, array_values(array_unique($childMw)), $routes);
            }
            continue;
        }

        // A single route definition.
        if (in_array($baseName, $verbs, true)) {
            $routes[] = [
                'method'     => strtoupper($baseName === 'any' ? 'ANY' : $baseName),
                'uri'        => joinUri($prefix, routeArgString($baseArgs[0] ?? null)),
                'action'     => actionOf($baseArgs[1] ?? null),
                'middleware' => array_values(array_unique(array_merge($middleware, $localMw))),
                'name'       => $routeName,
                'line'       => $baseNode->getStartLine(),
                'source'     => 'ast',
            ];
            continue;
        }

        // Resource controllers.
        if ($baseName === 'resource' || $baseName === 'apiResource') {
            $name = routeArgString($baseArgs[0] ?? null);
            $ctrl = '';
            if (($baseArgs[1]->value ?? null) instanceof Node\Expr\ClassConstFetch) {
                $ctrl = $baseArgs[1]->value->class->toString();
            }
            $verbsMap = $baseName === 'resource'
                ? ['index' => ['GET', ''], 'create' => ['GET', '/create'], 'store' => ['POST', ''], 'show' => ['GET', '/{p}'], 'edit' => ['GET', '/{p}/edit'], 'update' => ['PUT|PATCH', '/{p}'], 'destroy' => ['DELETE', '/{p}']]
                : ['index' => ['GET', ''], 'store' => ['POST', ''], 'show' => ['GET', '/{p}'], 'update' => ['PUT|PATCH', '/{p}'], 'destroy' => ['DELETE', '/{p}']];

            $param = singularParam($name);
            foreach ($verbsMap as $action => [$verb, $suffix]) {
                if ($only !== null && !in_array($action, $only, true)) continue;
                if ($except !== null && in_array($action, $except, true)) continue;
                $routes[] = [
                    'method'     => $verb,
                    'uri'        => joinUri($prefix, rtrim($name, '/') . str_replace('{p}', '{' . $param . '}', $suffix)),
                    'action'     => $ctrl ? $ctrl . '@' . $action : '(resource)',
                    'middleware' => array_values(array_unique(array_merge($middleware, $localMw))),
                    'name'       => $routeName,
                    'line'       => $baseNode->getStartLine(),
                    'source'     => 'ast:' . $baseName,
                ];
            }
        }
    }
}

function joinUri(string $a, string $b): string
{
    $a = trim($a, '/');
    $b = trim($b, '/');
    if ($a === '') return $b;
    if ($b === '') return $a;
    return $a . '/' . $b;
}

/** Laravel's resource parameter name: last segment, singularised, dashes → underscores. */
function singularParam(string $name): string
{
    $seg = trim($name, '/');
    $seg = substr(strrchr('/' . $seg, '/') ?: $seg, 1);
    $seg = str_replace('-', '_', $seg);
    if ($seg === '' || $seg === '_') return 'id';
    if (str_ends_with($seg, 'ies'))                             return substr($seg, 0, -3) . 'y';
    if (preg_match('/(sses|shes|ches|xes|zes)$/', $seg))        return substr($seg, 0, -2);
    if (str_ends_with($seg, 's') && !str_ends_with($seg, 'ss')) return substr($seg, 0, -1);
    return $seg;
}

function actionOf(?Node\Arg $arg): string
{
    if (!$arg) return '';
    $v = $arg->value;
    if ($v instanceof Node\Expr\Closure || $v instanceof Node\Expr\ArrowFunction) return 'Closure';
    if ($v instanceof Node\Scalar\String_) return $v->value;
    if ($v instanceof Node\Expr\Array_) {
        $cls = $m = '';
        if (($v->items[0]->value ?? null) instanceof Node\Expr\ClassConstFetch) $cls = $v->items[0]->value->class->toString();
        if (($v->items[1]->value ?? null) instanceof Node\Scalar\String_)       $m   = $v->items[1]->value->value;
        return $cls ? $cls . '@' . $m : 'Closure';
    }
    return '';
}

/**
 * Does this route require the caller to prove anything at all?
 * Mirrors the definition used by INDEXES/PUBLIC_ROUTE_AUDIT.md — keep the two in step.
 */
function routeIsGuarded(array $r): bool
{
    foreach ((array) $r['middleware'] as $m) {
        if (str_contains($m, 'auth:sanctum') || str_contains($m, 'FlexibleAuth') || $m === 'signed') return true;
    }
    return false;
}
