← [English version](./README.md)

# SharkyPHP 框架文档

SharkyPHP 是一款轻量、原生手写的 PHP 框架，**双内核分离设计**：HTTP 内核专职处理 Web 请求、CLI 内核专职处理命令行指令；各功能组件以独立的 Composer 包发布，边界清晰、相互解耦，并全面遵循 PSR 标准接口（PSR‑7 / PSR‑11 / PSR‑14 / PSR‑17 / PSR‑18 / PSR‑20 等），零第三方运行时依赖，便于二次扩展。

> 本文档面向**使用者**：只描述组件对外暴露的用法（类、方法、门面、属性注解）。框架内核如何调度、绑定、初始化这些组件的生命周期细节不在本文档范围内。

## 组件一览

| 包 | 命名空间 | 说明 |
| --- | --- | --- |
| Foundation | `Sharky\Foundation` | 应用核心、配置管理、异常处理、双内核、进程上下文（`Sharky\Foundation\Runtime`） |
| Facade | `Sharky\Facade` | 静态门面：Config / Container / Context / Http / Route / Cache / Redis / Session / Event / Log / Command / Clock / Database |
| Http | `Sharky\Http` | 路由、请求/响应（PSR‑7）、中间件、Cookie、上传、Http 工厂 |
| Annotation | `Sharky\Annotation` | 路由/控制器/中间件/权限/命令的属性注解 |
| Database | `Sharky\Database` | 连接管理、查询构造器、ActiveRecord 模型、Collection、事务 |
| Cache | `Sharky\Cache` | 缓存管理器（file / redis 驱动） |
| Redis | `Sharky\Redis` | Redis 连接管理器 |
| Session | `Sharky\Session` | 会话管理器（file / redis 驱动） |
| Event | `Sharky\Event` | PSR‑14 事件系统封装 |
| Authorization | `Sharky\Authorization` | JWT、图形验证码、TOTP 多因素认证 |
| Template | `Sharky\Template` | 编译型微模板引擎 |
| Console | `Sharky\Console` | 命令行核心、参数解析、彩色输出 |
| Clock | `Sharky\Clock` | 时钟（SystemClock / FrozenClock，PSR‑20） |
| Container | `Sharky\Container` | 依赖注入容器 |
| Pipeline | `Sharky\Pipeline` | 洋葱中间件管道 |
| Support | `Sharky\Support` | 全局助手函数、I18n、UUID、工具类（零依赖） |
| Contracts | `Sharky\Contracts` | 框架通用接口契约 |
| Skeleton | `Sharky\Skeleton` | 代码脚手架 / 命令生成工具 |
| Invoker | `Sharky\Invoker` | 统一调用调度器 |
| Runtime-Swoole | `Sharky\Runtime\Swoole` / `Sharky\Runtime\Storage` | Swoole 协程运行时（只提供 Swoole 相关实现） |

## 文档目录

1. [路由](./01-routing_zh.md)
2. [控制器、请求与响应](./02-controllers-requests_zh.md)
3. [数据库](./03-database_zh.md)
4. [缓存 / Redis / 会话](./04-cache-redis-session_zh.md)
5. [事件系统](./05-events_zh.md)
6. [鉴权（JWT / 验证码 / MFA）](./06-authorization_zh.md)
7. [模板引擎](./07-template_zh.md)
8. [命令行](./08-console_zh.md)
9. [多语言 I18n](./09-i18n_zh.md)
10. [门面与全局助手函数](./10-facades-helpers_zh.md)
11. [属性注解](./11-annotations_zh.md)

## 通用约定

- **门面（Facade）**：多数组件提供静态门面（`Route`、`Http`、`Config`、`Container`、`Context`、`Event`、`Cache`、`Redis`、`Session`、`Database`、`Clock`、`Command`）。门面本质是「容器里某个服务的静态代理」，调用 `Facade::method()` 等价于调用该服务实例的同名方法。
- **依赖注入**：控制器、命令行处理器、事件监听器的方法参数支持类型推导注入。声明非内置类型（如 `Psr\Http\Message\ServerRequestInterface`）时，框架从容器解析；声明与路由参数同名的参数时，自动填入路由匹配值。
- **响应契约**：HTTP 控制器必须返回实现 `Psr\Http\Message\ResponseInterface` 的对象。用 `Http::response()` / `Http::json()` / `Http::html()` 等助手快速构造，或手动返回 `Response` 实例。
- **配置读取**：通过 `Config::get('文件.键')` 读取 `configs/` 下的配置，支持点分路径，文件按需懒加载。
- **PSR 兼容**：请求/响应均实现 PSR‑7，可与任何兼容 PSR‑7/PSR‑17 的库互换使用。
