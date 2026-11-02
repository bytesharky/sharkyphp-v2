← [中文版本](./11-annotations_zh.md)

# Attribute Annotations

The framework provides a set of native PHP 8 attributes under `Sharky\Annotation` for declaratively describing routes, controllers, middleware, permissions, and the command line.

> Note: route and middleware attributes are scanned at boot by `Sharky\Http\Routing\RouteScanner` and merged with the route files (see [Routing](./01-routing.md)). Command attributes are scanned by `Sharky\Console\Commanding\CommandScanner` and merged with the command definition files (see [Console](./08-console.md)).

## Route Annotations

```php
namespace App\Http\Controllers;

use Sharky\Annotation\Controller;
use Sharky\Annotation\Http\Get;
use Sharky\Annotation\Http\Post;

#[Controller(name: 'demo')]      // class-level: declare a controller
class SharkyDemo
{
    #[Get(path: '/demo/index')]          // GET /demo/index
    public function index() {}

    #[Post(path: '/demo/save', middleware: [AuthMiddleware::class])]
    public function save() {}

    // generic attribute, method can be specified freely:
    #[Route(path: '/demo/mixed', method: ['GET', 'POST'], middleware: [])]
    public function mixed() {}
}
```

Available attributes:

| Attribute | Target | Parameters |
| --- | --- | --- |
| `#[Controller(name)]` | Class / Method | `name`: controller name |
| `#[Route(path, method=[], middleware=[])]` | Method | path, method array, middleware array |
| `#[Get(path, middleware=[])]` | Method | `method` fixed to `['Get']` |
| `#[Post(path, middleware=[])]` | Method | `method` fixed to `['Post']` |
| `#[Put(path, middleware=[])]` | Method | `method` fixed to `['Put']` |
| `#[Patch(path, middleware=[])]` | Method | `method` fixed to `['Patch']` |
| `#[Delete(path, middleware=[])]` | Method | `method` fixed to `['Delete']` |

## Middleware / Permission Annotations

```php
#[Middleware(name: 'auth')]          // class- or method-level: declare middleware
#[Permission(rule: 'user:read')]     // class- or method-level: declare required permission
```

- `#[Middleware(name)]`: can act on a class (the whole controller) or a method (a single action); `name` is the middleware identifier.
- `#[Permission(rule)]`: declares the permission rule string required at that point, to be consumed by the permission-check layer.

## Command Annotations

```php
namespace App\Console\Toolkits;

use Sharky\Annotation\Command;

class DemoToolkit
{
    #[Command(name: 'helloWord', desc: 'hello word', execute: 'helloWord')]
    public function helloWord() {}
}
```

- `#[Command(name, desc='', execute='execute')]`: marks a public method as a command.
  - `name`: the command name (e.g. `helloWord`, `db:test`).
  - `desc`: a description of the command.
  - `execute`: the actual method name to execute (defaults to `execute`, specified when the method name differs from the command name).

This attribute implements `IS_REPEATABLE`, allowing multiple `#[Command]` attributes to be stacked on the same method.

## Base Class

All annotations extend `Sharky\Annotation\BaseAnnotation`, which provides `toArray()` (returns an array of object properties), making it easy for tools to read the declared metadata.

---

← [Back to Documentation Index](./README.md)
