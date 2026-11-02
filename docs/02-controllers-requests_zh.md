← [English version](./02-controllers-requests.md)

# 控制器、请求与响应

## 控制器

控制器是普通的 PHP 类，方法（action）接收请求并返回响应。方法参数支持依赖注入：

- 声明 `Psr\Http\Message\ServerRequestInterface $request`：自动注入当前请求。
- 声明与路由参数同名的参数：自动填入路径匹配值。
- 其它非内置类型参数：从容器解析。

```php
namespace App\Http\Controllers;

use Psr\Http\Message\ServerRequestInterface;
use Sharky\Facade\Http;

class SharkyDemo extends Controller
{
    // 注入请求对象
    public function index(ServerRequestInterface $request)
    {
        return Http::response('hello');
    }

    // 注入路由参数 $id（配合路由 '/(id:\d+)'）
    public function show(ServerRequestInterface $request, $id)
    {
        return Http::json(['id' => $id]);
    }
}
```

> 推荐使用一个基础 `Controller` 类封装常用能力（如模板渲染、翻译等），示例项目中的 `app/Http/Controllers/Controller.php` 提供了 `render()` 示例。

## 构造响应

所有 action **必须返回 `Psr\Http\Message\ResponseInterface`**。最便捷的方式是使用 `Http` 门面或全局助手：

```php
use Sharky\Facade\Http;

Http::response($body = '', $status = 200, $headers = []);   // 普通文本/HTML 响应
Http::json($data, $status = 200, $headers = []);            // JSON 响应（自动序列化数组/对象）
Http::xml($data, $status = 200, $headers = []);             // XML 响应
Http::html($body, $status = 200, $headers = []);            // HTML 响应
Http::createResponse($status);                              // 空响应
Http::redirect($url, $status = 302);                        // 重定向响应
```

- `Http::json()` 接受：数组、`__toString` 对象、`toArray()`/`toJson()` 对象、`JsonSerializable` 对象，并自动设置 `application/json; charset=utf-8`。
- `Http::response()` 在未传参时返回 `HttpFactory`（可用链式 `->setHeader()->setStatus()`）；传参时直接返回 `ResponseInterface`。
- 全局助手 `response(...)`、`redirect(...)` 与上面等价（`helpers.php` 中定义）。

### 手动操作响应（PSR‑7 + 扩展）

`Sharky\Http\Message\Response` 实现 PSR‑7 且不可变（`withXxx()` 返回新实例），并附带便捷方法：

```php
use Sharky\Facade\Http;

$resp = Http::createResponse(200)
    ->setStatus(201)                       // 设置状态码（扩展方法，原地返回自身）
    ->setHeader(['X-Foo' => 'bar'])        // 设置/覆盖响应头（可数组批量）
    ->addedHeader('X-Bar', 'baz')          // 追加响应头
    ->setContentType('application/json')   // 设置 Content-Type
    ->setBody($stream);                    // 设置消息体（StreamInterface）

$resp->getStatusCode();
$resp->getHeaderLine('Content-Type');      // application/json
(string) $resp;                            // 渲染为完整 HTTP 报文
```

- `setContentType($type, $charset = '')`：第二个参数非空时追加 `; charset=xxx`；若 `$type` 本身已含 `charset=`（如 `'text/html; charset=utf-8'`）则原样使用，不会重复追加，也不会留下尾随 `;`。
- `Http::json()` / `Http::xml()` / `Http::html()` 已内置正确的 `Content-Type`，通常无需手动设置。

## 读取请求

请求对象实现 PSR‑7 `ServerRequestInterface`，并扩展了若干便捷方法：

```php
$request->getMethod();                 // 请求方法
$request->getUri();                    // PSR‑7 Uri
$request->getQuery($key, $default);    // 先查 GET 再查 POST
$request->getCookie($key, $default);   // 读取 Cookie
$request->getClientIP();               // 客户端 IP
$request->getProxys();                 // 代理链（X-Forwarded-For）
$request->isAjax();                    // 是否 XMLHttpRequest
$request->getParsedBody();             // 解析后的请求体（JSON / form）
$request->getUploadedFiles();          // 上传文件数组（UploadedFileInterface）
$request->getHeaderLine('Accept');     // PSR‑7 头读取
$request->getAttribute($name, $default); // 自定义属性
```

也可手动构造请求（如测试）：`Http::serverRequestCapture()`（基于全局变量）或 `Http::createServerRequest($method, $uri)`。

## 文件上传

`$request->getUploadedFiles()` 返回实现 `Psr\Http\Message\UploadedFileInterface` 的对象：

```php
foreach ($request->getUploadedFiles() as $file) {
    if ($file->getError() === UPLOAD_ERR_OK) {
        $file->moveTo(base_path('public/uploads/' . $file->getClientFilename()));
    }
}
```

## Cookie

`Sharky\Http\Cookie\Cookie` 提供静态方法（基于 PHP 原生 `setcookie`）：

```php
Cookie::set($key, $value, $expires = 3600, $path = '', $domain = '', $secure = false, $httponly = true);
Cookie::get($key, $default = null);
Cookie::has($key);
Cookie::delete($key, $path = '/', $domain = '');
```

## 中间件

实现 `Sharky\Http\Middleware\MiddlewareInterface`（它继承空标记接口 `Sharky\Contracts\MiddlewareInterface`），提供 `process` 方法。返回 `$next($request)` 继续后续中间件/控制器；直接返回 `ResponseInterface` 可短路。

```php
namespace App\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Sharky\Http\Middleware\MiddlewareInterface;

class AuthMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, callable $next): ResponseInterface
    {
        $token = $request->getHeaderLine('Authorization');
        if ($token === '') {
            // response() 是全局助手，不带参返回 HttpFactory
            return response('Unauthorized', 401);
        }
        // 放行：把（可修改后的）请求传给下一个
        return $next($request);
    }
}
```

中间件通过路由或路由组的 `middleware()` 注册（见[路由](./01-routing.md)）。

用 `php sharky make:middleware AuthCheck` 可直接生成 HTTP 中间件。命令行是另一套中间件形态（入参 `Input`），用 `make:console:middleware` 生成，见[控制台](./08-console.md#两种中间件)。

## 模板渲染（快捷）

若使用模板引擎，可在基础控制器中封装（详见[模板引擎](./07-template.md)）：

```php
protected function render(string $template, array $variables = []): ResponseInterface
{
    $engine = new \Sharky\Template\TemplateEngine(Config::get('template'));
    $context = $engine->render("SharkyDemo/{$template}", $variables);
    return \Sharky\Facade\Http::html((string) $context);
}
```

---

← [返回文档索引](./README_zh.md)
