← [中文版本](./README_zh.md)

# SharkyPHP Framework Documentation (v2.0.0)

SharkyPHP v2.0.0 is a lightweight, handwritten native PHP framework featuring a **dual-kernel separation design**: the HTTP kernel is dedicated to web requests and the CLI kernel to console commands. All functional components are released as individual Composer packages with clear boundaries and loose coupling. It fully complies with PSR standard interfaces (PSR‑7 / PSR‑11 / PSR‑14 / PSR‑17 / PSR‑18 / PSR‑20, etc.), has zero third-party runtime dependencies, and is friendly for further extension.

> This documentation is written for **users**: it only describes the externally exposed usage of components (classes, methods, facades, attribute annotations). The framework's internal lifecycle details — how the kernel dispatches, binds, and initializes these components — are out of scope.

## Component Overview

| Package | Namespace | Description |
| --- | --- | --- |
| Foundation | `Sharky\Foundation` | Application core, configuration management, exception handling, dual kernels, process context (`Sharky\Foundation\Runtime`) |
| Facade | `Sharky\Facade` | Static facades: Config / Container / Context / Http / Route / Cache / Redis / Session / Event / Log / Command / Clock / Database |
| Http | `Sharky\Http` | Routing, request/response (PSR-7), middleware, cookies, uploads, HTTP factory |
| Annotation | `Sharky\Annotation` | Attribute annotations for routing / controller / middleware / permission / command |
| Database | `Sharky\Database` | Connection management, query builder, ActiveRecord models, Collection, transactions |
| Cache | `Sharky\Cache` | Cache manager (file / redis drivers) |
| Redis | `Sharky\Redis` | Redis connection manager |
| Session | `Sharky\Session` | Session manager (file / redis drivers) |
| Event | `Sharky\Event` | PSR-14 event system wrapper |
| Authorization | `Sharky\Authorization` | JWT, image captcha, TOTP multi-factor authentication |
| Template | `Sharky\Template` | Compiled micro template engine |
| Console | `Sharky\Console` | CLI core, argument parsing, colored output |
| Clock | `Sharky\Clock` | Clock (SystemClock / FrozenClock, PSR-20) |
| Container | `Sharky\Container` | Dependency injection container |
| Pipeline | `Sharky\Pipeline` | Onion middleware pipeline |
| Support | `Sharky\Support` | Global helper functions, I18n, UUID, utilities (zero dependencies) |
| Contracts | `Sharky\Contracts` | Framework common interface contracts |
| Skeleton | `Sharky\Skeleton` | Code scaffolding / command generator |
| Invoker | `Sharky\Invoker` | Unified invoker |
| Runtime-Swoole | `Sharky\Runtime\Swoole` / `Sharky\Runtime\Storage` | Swoole coroutine runtime (Swoole-specific implementations only) |

## Documentation Index

1. [Routing](./01-routing.md)
2. [Controllers, Requests & Responses](./02-controllers-requests.md)
3. [Database](./03-database.md)
4. [Cache / Redis / Session](./04-cache-redis-session.md)
5. [Event System](./05-events.md)
6. [Authorization (JWT / Captcha / MFA)](./06-authorization.md)
7. [Template Engine](./07-template.md)
8. [Console](./08-console.md)
9. [Internationalization (I18n)](./09-i18n.md)
10. [Facades & Global Helpers](./10-facades-helpers.md)
11. [Attribute Annotations](./11-annotations.md)

## General Conventions

- **Facade**: Most components provide a static facade (`Route`, `Http`, `Config`, `Container`, `Context`, `Event`, `Cache`, `Redis`, `Session`, `Database`, `Clock`, `Command`). A facade is essentially a "static proxy" for a service inside the container; calling `Facade::method()` is equivalent to calling the same method on that service instance.
- **Dependency Injection**: Method parameters of controllers, command handlers, and event listeners support type-derived injection. When you declare a non-builtin type (e.g. `Psr\Http\Message\ServerRequestInterface`), the framework resolves it from the container; when you declare a parameter with the same name as a route parameter, the matched route value is injected automatically.
- **Response Contract**: HTTP controllers must return an object implementing `Psr\Http\Message\ResponseInterface`. Use helpers such as `Http::response()` / `Http::json()` / `Http::html()` to build them quickly, or return a `Response` instance manually.
- **Reading Configuration**: Read `configs/` configuration via `Config::get('file.key')`, which supports dot paths and lazy-loads files on demand.
- **PSR Compatibility**: Requests/responses implement PSR-7 and can be interchanged with any PSR-7 / PSR-17 compatible library.
