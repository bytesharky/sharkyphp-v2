← [English version](./01-routing.md)

# 路由

SharkyPHP 的路由由 `Sharky\Http\Routing\Router` 提供，`Route` 门面是其静态代理。路由在 `app/Http/Routes/` 下的 PHP 文件中注册（`HttpKernel` 启动时会自动 `Route::loadRoutes()` 加载这些文件）。

## 1. 基本注册

```php
use App\Http\Controllers\SharkyDemo;
use Sharky\Facade\Route;

// 单个方法
Route::addRoute('GET', '/', [SharkyDemo::class, 'index']);

// 多个方法
Route::addRoute(['GET', 'POST'], '/getmfa', [SharkyDemo::class, 'getmfa']);

// 任意方法（匹配全部 HTTP 方法）
Route::addRoute('ANY', '/extension', [SharkyDemo::class, 'extension']);
```

`addRoute(array|string $method, string $path, mixed $handler)` 参数说明：

- `$method`：字符串或数组，可选值包含 `GET/POST/PUT/PATCH/DELETE` 等标准方法，以及伪方法 `ANY`（匹配所有方法）。
- `$path`：URI 路径，支持参数占位符（见下文）。
- `$handler`：处理器，支持三种写法：
  - `[Controller::class, 'method']`（推荐）
  - `'Controller::class@method'`
  - 在已设置 `controller()` 默认控制器后，可仅写 `'method'`

## 2. 路径参数

路径中用 `(name)` 声明一个匹配 `[^/]+` 的参数，用 `(name:正则)` 自定义匹配规则。匹配到的值会按**参数名**注入到控制器方法。

```php
Route::addRoute('GET', '/(id:\d+)', [SharkyDemo::class, 'show']);
Route::addRoute('DELETE', '/(id)', [SharkyDemo::class, 'delete'])->where(['id' => '\d']);
```

```php
// 控制器方法中按名字接收：
public function show(\Psr\Http\Message\ServerRequestInterface $request, $id)
{
    // $id 即为路径中匹配到的 (id)
}
```

返回的 `Route` 实例还支持 `where(array $rule)` 追加参数规则。

## 3. 路由中间件

每条路由都可挂中间件（详见[控制器、请求与响应](./02-controllers-requests.md#中间件)）。

```php
Route::addRoute(['GET', 'POST'], '/auth', [SharkyDemo::class, 'auth'])
    ->middleware([\App\Http\Middleware\AuthMiddleware::class]);
```

## 4. 路由组

用 `prefix()` / `middleware()` 设定组内公共前缀与中间件，在闭包内用传入的路由器实例（`$Route`）继续注册：

```php
Route::prefix('/demo')
    ->middleware([\App\Http\Middleware\AuthMiddleware::class])
    ->group(function ($Route) {

        // 组内可叠加或移除中间件
        $Route->prefix('/demo')
            ->addRoute('GET', '/index', [SharkyDemo::class, 'index'])
            ->withoutMiddleware([\App\Http\Middleware\AuthMiddleware::class]);

        $Route->addRoute('GET', '/(id:\d+)', [SharkyDemo::class, 'show']);
    });
```

组相关方法（均在 `Router` 上，闭包内用克隆实例调用）：

- `prefix(string $prefix)`：追加路径前缀
- `controller(string $class)`：设定组内默认控制器
- `middleware(array|string)` / `withoutMiddleware(...)` / `appendMiddleware(...)` / `prependMiddleware(...)`：中间件管理
- `group(callable $callback)`：嵌套分组

## 5. 加载路由与路由缓存

`HttpKernel` 启动时会执行 `Route::loadRoutes(base_path('app/Http/Routes'))`，加载规则按环境区分：

| 环境 | 行为 |
| --- | --- |
| `APP_ENV=production` | **优先加载缓存** `runtime/routes.php`；命中后不再加载路由声明文件、也不再扫描注解 |
| 其他环境 | 忽略缓存：加载 `app/Http/Routes/*.php` + 扫描控制器注解，两者合并注册 |

合并时以「同 `path` + 同 `method`」判定冲突（`ANY` 与任意方法互斥），冲突直接抛 `Sharky\Http\Exception\RouteConflictException`；同路径不同方法允许共存。

缓存文件由脚手架命令 `route:compile`（`Sharky\Skeleton\Toolkits\RouteCompile`）生成：强制忽略已有缓存重新编译，把**编译好的整棵前缀树**落盘。文件路径由容器门面推导项目根目录：

```php
$directory = Container::get('project.directory');   // 项目根目录
// 缓存文件：{$directory}/runtime/routes.php
```

可在 `configs/http.php` 覆盖缓存位置与注解扫描目录：

```php
'routing' => [
    // 路由缓存文件（生产环境优先加载）
    'cache' => Helper::base_path('runtime/routes.php'),

    // 路由/中间件注解扫描目录 [命名空间 => 目录]
    'scan'  => [
        'App\\Http\\Controllers' => Helper::base_path('app/Http/Controllers'),
    ],
],
```

> 闭包处理器无法写入缓存，编译时会直接报错；请使用 `[Controller::class, 'method']` 形式注册路由。

缓存装载**不做任何重编译**：文件内容是 `base64(serialize(...))` 的整棵前缀树（节点为 `[static, param, candidates]` 紧凑数组），装载就是一次 `require` + `unserialize` 赋值。

- 不还原 `Route` 实例：匹配阶段只读，`Router` 全程通过 `ArrayAccess` 取 `path` / `pattern` / `methods` / `paramNames` / `paramRules` 与前缀树槽位，节点树与数组形态共用同一套代码。
- 不重建前缀树：`TrieNode` 的 `static` / `param` / `candidates` 直接以数组存在，`dispatch()` 首段即命中，省掉逐条 `explode` + `preg_match` + `new TrieNode`。
- 用 `serialize` 而非 PHP 数组字面量：嵌套数组字面量的编译成本远高于反序列化，600 条路由的缓存装载从 `4.7ms` 降到 `2.2ms`，单次匹配从 `3.2µs` 降到 `1.8µs`（PHP 8.2，无 opcache）。

声明注册阶段仍返回 `Route` 实例（便于 `where()` 追加约束）；`getRoutes()` 在缓存装载后按需从树里展开一次，不参与匹配路径。装载后若又调用 `addRoute()`，会先把数组树还原成节点树再插入。

## 6. CORS 预检

OPTIONS 请求由内核统一处理：优先匹配你注册的 OPTIONS 路由；未匹配时，按 `configs/http.php` 中 `response.cors.allow-headers` 自动返回 204 预检响应（`response.cors.auto-options` 为 `true` 时）。`Access-Control-Allow-Methods` 由路由自动推导。

## 6.1 HEAD 请求

`HEAD` 语义上等同于 `GET`，因此**不需要单独注册 HEAD 路由**：匹配阶段若未声明 `HEAD`，自动回落到同路径的 `GET` 路由；内核在输出时保留全部响应头（含 `Content-Length`）但**不输出响应体**。

```bash
curl -I http://localhost:8000/          # 200，与 GET 相同的头，无 body
```

> `204` / `304` 同样禁止输出 body，这条规则与请求方法无关。

## 7. 属性注解方式

除门面注册外，控制器方法也可以直接用属性注解声明路由。启动时由 `Sharky\Http\Routing\RouteScanner` 扫描，并与路由声明文件合并（见上节加载规则）：

```php
use Sharky\Annotation\Controller;
use Sharky\Annotation\Http\Get;
use Sharky\Annotation\Http\Post;
use Sharky\Annotation\Middleware;

#[Controller(name: 'demo')]                 // 类级：控制器名称（仅元数据）
#[Middleware(name: AuthMiddleware::class)]  // 类级：本控制器全部路由生效
class Demo
{
    #[Get(path: '/demo/index')]
    public function index() {}

    #[Post(path: '/demo/save', middleware: [LogMiddleware::class])]  // 路由级中间件
    #[Middleware(name: AuditMiddleware::class)]                      // 方法级中间件
    public function save() {}
}
```

- 未声明 `method` 的 `#[Route(path: '...')]` 按 `ANY` 处理；
- 中间件合并顺序：类级 `#[Middleware]` → 路由注解 `middleware` → 方法级 `#[Middleware]`（自动去重）；
- 扫描目录取 `configs/http.php` 的 `routing.scan`，未配置时默认扫描 `App\Http\Controllers`；
- 注解路由与声明文件路由同等参与冲突检测与缓存导出。

---

← [返回文档索引](./README_zh.md)
