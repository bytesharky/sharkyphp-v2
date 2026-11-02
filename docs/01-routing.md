← [中文版本](./01-routing_zh.md)

# Routing

Routing in SharkyPHP is provided by `Sharky\Http\Routing\Router`, and the `Route` facade is its static proxy. Routes are registered in PHP files under `app/Http/Routes/` (the `HttpKernel` automatically calls `Route::loadRoutes()` to load these files when it boots).

## 1. Basic Registration

```php
use App\Http\Controllers\SharkyDemo;
use Sharky\Facade\Route;

// Single method
Route::addRoute('GET', '/', [SharkyDemo::class, 'index']);

// Multiple methods
Route::addRoute(['GET', 'POST'], '/getmfa', [SharkyDemo::class, 'getmfa']);

// Any method (matches all HTTP methods)
Route::addRoute('ANY', '/extension', [SharkyDemo::class, 'extension']);
```

`addRoute(array|string $method, string $path, mixed $handler)` parameter details:

- `$method`: A string or array. Valid values include standard methods such as `GET/POST/PUT/PATCH/DELETE`, as well as the pseudo-method `ANY` (matches all methods).
- `$path`: The URI path, which supports parameter placeholders (see below).
- `$handler`: The handler, which supports three forms:
  - `[Controller::class, 'method']` (recommended)
  - `'Controller::class@method'`
  - After setting a default controller via `controller()`, you may write just `'method'`

## 2. Path Parameters

Use `(name)` in the path to declare a parameter that matches `[^/]+`, and `(name:regex)` to customize the matching rule. The matched value is injected into the controller method by **parameter name**.

```php
Route::addRoute('GET', '/(id:\d+)', [SharkyDemo::class, 'show']);
Route::addRoute('DELETE', '/(id)', [SharkyDemo::class, 'delete'])->where(['id' => '\d']);
```

```php
// Receive by name in the controller method:
public function show(\Psr\Http\Message\ServerRequestInterface $request, $id)
{
    // $id is the matched (id) from the path
}
```

The returned `Route` instance also supports `where(array $rule)` to append parameter rules.

## 3. Route Middleware

Every route can attach middleware (see [Controllers, Requests and Responses](./02-controllers-requests.md#middleware)).

```php
Route::addRoute(['GET', 'POST'], '/auth', [SharkyDemo::class, 'auth'])
    ->middleware([\App\Http\Middleware\AuthMiddleware::class]);
```

## 4. Route Groups

Use `prefix()` / `middleware()` to set a common prefix and middleware for the group; inside the closure, continue registering routes with the passed-in router instance (`$Route`):

```php
Route::prefix('/demo')
    ->middleware([\App\Http\Middleware\AuthMiddleware::class])
    ->group(function ($Route) {

        // Within a group you can add or remove middleware
        $Route->prefix('/demo')
            ->addRoute('GET', '/index', [SharkyDemo::class, 'index'])
            ->withoutMiddleware([\App\Http\Middleware\AuthMiddleware::class]);

        $Route->addRoute('GET', '/(id:\d+)', [SharkyDemo::class, 'show']);
    });
```

Group-related methods (all on `Router`, called via a cloned instance inside the closure):

- `prefix(string $prefix)`: Append a path prefix
- `controller(string $class)`: Set the default controller for the group
- `middleware(array|string)` / `withoutMiddleware(...)` / `appendMiddleware(...)` / `prependMiddleware(...)`: Middleware management
- `group(callable $callback)`: Nested grouping

## 5. Loading Routes and the Route Cache

`HttpKernel` calls `Route::loadRoutes(base_path('app/Http/Routes'))` on boot. The loading rule depends on the environment:

| Environment | Behaviour |
| --- | --- |
| `APP_ENV=production` | **Prefer the cache** at `runtime/routes.php`; once hit, neither the route files nor the annotations are loaded |
| Anything else | Ignore the cache: load `app/Http/Routes/*.php` **and** scan controller annotations, then merge them |

Merging treats the same `path` + the same `method` as a conflict (`ANY` conflicts with every method) and throws `Sharky\Http\Exception\RouteConflictException`; the same path with different methods may coexist.

The cache file is produced by the scaffold command `route:compile` (`Sharky\Skeleton\Toolkits\RouteCompile`): it ignores any existing cache, rebuilds from source and dumps the **compiled prefix tree**. The location is derived from the project root through the container facade:

```php
$directory = Container::get('project.directory');   // project root
// cache file: {$directory}/runtime/routes.php
```

You may override the cache location and the annotation scan directories in `configs/http.php`:

```php
'routing' => [
    // route cache file (loaded first in production)
    'cache' => Helper::base_path('runtime/routes.php'),

    // annotation scan directories [namespace => directory]
    'scan'  => [
        'App\\Http\\Controllers' => Helper::base_path('app/Http/Controllers'),
    ],
],
```

> Closure handlers cannot be written into the cache and will fail the compilation; register routes as `[Controller::class, 'method']` instead.

Cache loading performs **no recompilation at all**: the file holds the whole prefix tree as `base64(serialize(...))`, with nodes shaped as compact `[static, param, candidates]` arrays — loading is a single `require` + `unserialize` assignment.

- No `Route` instances are rebuilt: the matching stage is read-only and the router reads `path` / `pattern` / `methods` / `paramNames` / `paramRules` plus trie slots through `ArrayAccess`, so node trees and plain arrays share the same code path.
- No prefix tree is rebuilt: `static` / `param` / `candidates` are already arrays, so `dispatch()` lands on the right node immediately — no per-route `explode` + `preg_match` + `new TrieNode`.
- `serialize` instead of PHP array literals: compiling nested array literals is far more expensive than unserializing. For 600 routes, cache loading drops from `4.7ms` to `2.2ms` and a match from `3.2µs` to `1.8µs` (PHP 8.2, no opcache).

Declaration registration still returns a `Route` instance (so `where()` can attach constraints); after a cache load, `getRoutes()` flattens the tree once on demand and never sits on the matching path. If `addRoute()` is called after loading, the array tree is converted back to nodes before insertion.

## 6. CORS Preflight

OPTIONS requests are handled centrally by the kernel: it first tries to match an OPTIONS route you registered; if none matches, it automatically returns a 204 preflight response based on `response.cors.allow-headers` in `configs/config.php` (when `response.cors.auto-options` is `true`). `Access-Control-Allow-Methods` is derived automatically from the routes.

## 6.1 HEAD Requests

`HEAD` is semantically identical to `GET`, so you **never need to register a HEAD route**: when no `HEAD` route is declared, matching falls back to the `GET` route for the same path, and the kernel emits every response header (including `Content-Length`) but **no response body**.

```bash
curl -I http://localhost:8000/          # 200, same headers as GET, no body
```

> `204` / `304` also suppress the body — that rule applies regardless of the request method.

## 7. Attribute Annotation Approach

Besides facade registration, controller methods can declare routes with attributes directly. On boot they are scanned by `Sharky\Http\Routing\RouteScanner` and merged with the route files (see the loading rule above):

```php
use Sharky\Annotation\Controller;
use Sharky\Annotation\Http\Get;
use Sharky\Annotation\Http\Post;
use Sharky\Annotation\Middleware;

#[Controller(name: 'demo')]                 // class level: controller name (metadata only)
#[Middleware(name: AuthMiddleware::class)]  // class level: applies to every route below
class Demo
{
    #[Get(path: '/demo/index')]
    public function index() {}

    #[Post(path: '/demo/save', middleware: [LogMiddleware::class])]  // route level middleware
    #[Middleware(name: AuditMiddleware::class)]                      // method level middleware
    public function save() {}
}
```

- A `#[Route(path: '...')]` without `method` is treated as `ANY`;
- Middleware order: class level `#[Middleware]` -> route attribute `middleware` -> method level `#[Middleware]` (duplicates removed);
- Scan directories come from `routing.scan` in `configs/http.php`, defaulting to `App\Http\Controllers`;
- Annotation routes take part in conflict detection and cache export exactly like route files.

---

← [Back to Documentation Index](./README.md)
