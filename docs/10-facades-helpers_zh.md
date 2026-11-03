← [English version](./10-facades-helpers.md)

# 门面与全局助手函数

门面统一放在独立的 `sharkyphp/facade` 包中，命名空间 `Sharky\Facade`（该包只做静态代理，不反向依赖任何业务包，因而不会造成循环依赖）。框架提供一批通用的门面与全局函数，覆盖配置、容器、上下文、时钟、UUID 与常用工具。

## 配置 Config

```php
use Sharky\Facade\Config;

Config::get('config.timezone', 'Asia/Shanghai');  // 点分路径：文件.键
Config::has('database.default');
```

- 路径第一段对应 `configs/` 下的文件名（不含扩展名），后续为键层级。
- 配置文件按需懒加载；`config.php` 等由内核在启动时自动加载。
- 配置结构见各组件文档（`configs/*.php`）。

## 容器 Container

```php
use Sharky\Facade\Container;

$instance = Container::get(\App\Services\Mailer::class);   // 从 DI 容器解析
```

- 容器实现 PSR‑11；控制器、监听器、命令的非内置类型参数均由此解析。
- 业务服务的绑定在 `configs/service.php` 的 `bindings` 节点（见各组件示例）或包的自举配置中声明。
- **该门面是只读代理**：只暴露 `get()` / `has()`，不提供 `bind()` / `singleton()` 等写入能力。需要注册服务请改 `configs/service.php`（`bindings` 节点支持 `concrete` 与 `shared`），避免运行期随手改容器造成隐式依赖。

## 日志 Log

```php
use Sharky\Facade\Log;

Log::info('user {uid} registered', ['uid' => 1]);   // PSR-3 占位符
Log::warning('slow query: {ms}ms', ['ms' => 320]);
Log::error('payment failed', ['order' => $orderId]);
```

- 对应 `Sharky\Log\Logger`，框架启动时已注册为容器单例，**开箱即用**，无需额外绑定。
- 默认落地 `runtime/logs/app.log`（`StreamHandler`），可用 `configs/log.php` 覆盖：
  `path`（日志文件）、`level`（最低级别，默认 `debug`）、`channel`（通道名，默认 `app`）。
- 按 PSR-3 规则，`{placeholder}` 只会被 `context` 中的同名键替换，未提供的占位符原样保留；敏感值建议在调用前脱敏。

## 上下文 Context

`Sharky\Foundation\Runtime\Context` 管理请求级数据，通过 `Context` 门面访问：

```php
use Sharky\Facade\Context;
use Psr\Http\Message\ServerRequestInterface;

$request = Context::getRequest();          // 当前请求（ServerRequestInterface）
Context::setRequest($request);             // 设置（内核在派发路由时已自动设置）
$output  = Context::getOutput();           // 当前命令行 Output（内核在执行命令前已设置）
Context::setOutput($output);               // 设置
Context::get('trace_id', null);            // 自定义上下文值
Context::set('trace_id', 'abc');
```

## 时钟 Clock

`Sharky\Clock` 实现 PSR‑20 `ClockInterface`：

```php
use Sharky\Facade\Clock;

$now = Clock::now();                       // DateTimeImmutable
Clock::fromUTC();                          // 基于 UTC 的时钟
Clock::fromSystemTimezone();               // 基于系统时区
```

另有 `Sharky\Clock\SystemClock`（实时）与 `Sharky\Clock\FrozenClock`（可冻结/调整时间，便于测试）：
```php
use Sharky\Clock\FrozenClock;
$clock = new FrozenClock(new \DateTimeImmutable('2026-01-01'));
$clock->adjustTime('+1 day');
$clock->setTo(new \DateTimeImmutable('2026-02-01'));
```

## UUID

`Sharky\Support\UUID` 生成各类 UUID：

```php
use Sharky\Support\UUID;

UUID::UUIDv1();   // 时间
UUID::UUIDv4();   // 随机（最常用）
UUID::UUIDv5($namespace, $name);   // 命名空间 SHA1
UUID::isUUID('xxxxxxxx-xxxx-...'); // 校验，返回 [uuid, version, variant] 或 false
```

支持 v1/v2/v3/v4/v5/v6/v7/v8，命名空间版本（v3/v5）要求 namespace 为合法 UUID。

## 全局助手函数

在 `helpers.php` 中定义（应用启动时引入）：

```php
env('APP_DEBUG', false);          // 读取环境变量（也支持回调：env('X', null, fn($v) => ...)）
loadEnv('/path/.env');            // 加载 .env 文件到 $_ENV
base_path('public/uploads');       // 拼接项目根目录得到绝对路径
redirect('/login', 302);           // 返回 3xx 重定向响应（ResponseInterface）
response($body, 200);              // 返回响应（ResponseInterface）；无参时返回 HttpFactory
```

- `redirect($url, $status = 302)`：仅接受 `http(s)://` 合法 URL，返回带 `Location` 头的 3xx 响应。
- `response(...)`：未传参返回 `HttpFactory`（可链式 `->setHeader()->setStatus()`）；传参等价于 `Http::response()`。
- `base_path($suffix = '')`：基于 `project.directory` 容器标识计算绝对路径。

---

← [返回文档索引](./README_zh.md)
