← [English version](./11-annotations.md)

# 属性注解（Attributes）

框架在 `Sharky\Annotation` 下提供一组 PHP 8 原生属性，用于声明式地描述路由、控制器、中间件、权限与命令行。

> 说明：路由与中间件属性会在启动时由 `Sharky\Http\Routing\RouteScanner` 扫描，并与路由声明文件合并（见[路由](./01-routing_zh.md)）；命令属性由 `Sharky\Console\Commanding\CommandScanner` 扫描，与命令声明文件合并（见[命令行](./08-console_zh.md)）。

## 路由注解

```php
namespace App\Http\Controllers;

use Sharky\Annotation\Controller;
use Sharky\Annotation\Http\Get;
use Sharky\Annotation\Http\Post;

#[Controller(name: 'demo')]      // 类级：声明控制器
class SharkyDemo
{
    #[Get(path: '/demo/index')]          // GET /demo/index
    public function index() {}

    #[Post(path: '/demo/save', middleware: [AuthMiddleware::class])]
    public function save() {}

    // 通用属性，可自行指定方法：
    #[Route(path: '/demo/mixed', method: ['GET', 'POST'], middleware: [])]
    public function mixed() {}
}
```

可用属性：

| 属性 | 目标 | 参数 |
| --- | --- | --- |
| `#[Controller(name, prefix='')]` | 类 / 方法 | `name`：控制器名；`prefix`（可选）：自动拼到本控制器下所有方法路由 path 前 |
| `#[Route(path, method=[], middleware=[])]` | 方法 | 路径、方法数组、中间件数组 |
| `#[Get(path, middleware=[])]` | 方法 | `method` 固定 `['Get']` |
| `#[Post(path, middleware=[])]` | 方法 | `method` 固定 `['Post']` |
| `#[Put(path, middleware=[])]` | 方法 | `method` 固定 `['Put']` |
| `#[Patch(path, middleware=[])]` | 方法 | `method` 固定 `['Patch']` |
| `#[Delete(path, middleware=[])]` | 方法 | `method` 固定 `['Delete']` |

## 中间件 / 权限注解

```php
#[Middleware(name: 'auth')]          // 类或方法级：声明中间件
#[Permission(rule: 'user:read')]     // 类或方法级：声明所需权限
```

- `#[Middleware(name)]`：可作用于类（整控制器）或方法（单动作），`name` 为中间件标识。该注解为 `IS_REPEATABLE`，同一类或方法上可叠多个 `#[Middleware(...)]` 且都会被收集（不再因重复声明被静默丢弃）。
- `#[Permission(rule)]`：声明该处需要的权限规则字符串，供权限校验层消费。

## 命令注解

```php
namespace App\Console\Toolkits;

use Sharky\Annotation\Command;

class DemoToolkit
{
    #[Command(name: 'helloWord', desc: 'hello word', execute: 'helloWord')]
    public function helloWord() {}
}
```

- `#[Command(name, desc='', execute='execute')]`：标记一个公开方法为命令。
  - `name`：命令名（如 `helloWord`、`db:test`）。
  - `desc`：命令说明。
  - `execute`：实际执行的方法名（默认 `execute`，当方法名与命令名不一致时指定）。

该属性实现了 `IS_REPEATABLE`，允许在同一方法上叠加多个 `#[Command]`。

## 基类

所有注解继承自 `Sharky\Annotation\BaseAnnotation`，提供 `toArray()`（返回对象属性数组），便于工具读取声明元数据。

---

← [返回文档索引](./README_zh.md)
