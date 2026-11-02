← [中文版本](./10-facades-helpers_zh.md)

# Facades and Global Helper Functions

All facades live in the standalone `sharkyphp/facade` package under the `Sharky\Facade` namespace. That package only performs static proxying and never depends back on any business package, so it cannot introduce circular dependencies. Beyond the component-specific facades, the framework provides a set of general-purpose facades and global functions covering configuration, the container, context, clock, UUID, and common utilities.

## Config

```php
use Sharky\Facade\Config;

Config::get('config.timezone', 'Asia/Shanghai');  // dot-separated path: file.key
Config::has('database.default');
```

- The first segment of the path corresponds to a filename (without extension) under `configs/`, followed by the key hierarchy.
- Config files are lazily loaded on demand; `config.php` and similar are loaded automatically by the kernel at startup.
- The configuration structure is described in each component's documentation (`configs/*.php`).

## Container

```php
use Sharky\Facade\Container;

$instance = Container::get(\App\Services\Mailer::class);   // resolve from the DI container
```

- The container implements PSR-11; non-builtin typed parameters of controllers, listeners, and commands are all resolved through it.
- Bindings for business services are declared in the `bindings` node of `configs/service.php` (see each component's examples) or in the package's bootstrap configuration.
- **This facade is a read-only proxy**: it exposes only `get()` / `has()`, with no `bind()` / `singleton()` write access. Register services in `configs/service.php` (the `bindings` node supports `concrete` and `shared`) so runtime code cannot mutate the container and create implicit dependencies.

## Log

```php
use Sharky\Facade\Log;

Log::info('user {uid} registered', ['uid' => 1]);   // PSR-3 placeholders
Log::warning('slow query: {ms}ms', ['ms' => 320]);
Log::error('payment failed', ['order' => $orderId]);
```

- Backed by `Sharky\Log\Logger`, registered as a container singleton during bootstrap, so it **works out of the box** with no extra binding.
- Logs go to `runtime/logs/app.log` via `StreamHandler` by default; override with `configs/log.php`: `path` (log file), `level` (minimum level, `debug` by default), `channel` (channel name, `app` by default).
- Per PSR-3, `{placeholder}` is only replaced when the key exists in `context`; unknown placeholders are kept verbatim. Redact sensitive values before logging.

## Context

`Sharky\Foundation\Runtime\Context` manages request-scoped data and is accessed via the `Context` facade:

```php
use Sharky\Facade\Context;
use Psr\Http\Message\ServerRequestInterface;

$request = Context::getRequest();          // current request (ServerRequestInterface)
Context::setRequest($request);             // set (the kernel sets this automatically when dispatching routes)
Context::get('trace_id', null);            // custom context value
Context::set('trace_id', 'abc');
```

## Clock

`Sharky\Clock` implements the PSR-20 `ClockInterface`:

```php
use Sharky\Facade\Clock;

$now = Clock::now();                       // DateTimeImmutable
Clock::fromUTC();                          // UTC-based clock
Clock::fromSystemTimezone();               // system-timezone-based clock
```

There are also `Sharky\Clock\SystemClock` (real time) and `Sharky\Clock\FrozenClock` (time can be frozen/adjusted, useful for testing):
```php
use Sharky\Clock\FrozenClock;
$clock = new FrozenClock(new \DateTimeImmutable('2026-01-01'));
$clock->adjustTime('+1 day');
$clock->setTo(new \DateTimeImmutable('2026-02-01'));
```

## UUID

`Sharky\Support\UUID` generates various UUIDs:

```php
use Sharky\Support\UUID;

UUID::UUIDv1();   // time-based
UUID::UUIDv4();   // random (most common)
UUID::UUIDv5($namespace, $name);   // namespace SHA1
UUID::isUUID('xxxxxxxx-xxxx-...'); // validate, returns [uuid, version, variant] or false
```

Supports v1/v2/v3/v4/v5/v6/v7/v8; for namespace versions (v3/v5) the namespace must be a valid UUID.

## Global Helper Functions

Defined in `helpers.php` (included when the application starts):

```php
env('APP_DEBUG', false);          // read an environment variable (also supports a callback: env('X', null, fn($v) => ...) )
loadEnv('/path/.env');            // load a .env file into $_ENV
base_path('public/uploads');       // join the project root directory to get an absolute path
redirect('/login', 302);           // return a 3xx redirect response (ResponseInterface)
response($body, 200);              // return a response (ResponseInterface); returns HttpFactory when called with no arguments
```

- `redirect($url, $status = 302)`: only accepts a valid `http(s)://` URL and returns a 3xx response with a `Location` header.
- `response(...)`: when called with no arguments returns `HttpFactory` (chainable `->setHeader()->setStatus()`); passing arguments is equivalent to `Http::response()`.
- `base_path($suffix = '')`: computes an absolute path based on the `project.directory` container identifier.

---

← [Back to Documentation Index](./README.md)
