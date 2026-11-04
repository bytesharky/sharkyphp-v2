# Update Log

## 2026-11-05 v2.0.2 Fixes & Improvements

Incremental fixes on top of 2.0.1 (no breaking changes):

- **New package `sharkyphp/project`**: the default application skeleton produced by `sharky new`. It is now part of the monorepo and split into its own read-only repository, so the starter project stays in lock-step with the framework version.
- **Installer**: fixed component version resolving failure during installation; minor tweaks to the generated starter views/templates.
- **Runtime-Swoole**: `sharky serve swoole` now correctly detects OpenSwoole via `extension_loaded('openswoole')` (previously it checked `swoole`, which is false when only OpenSwoole is installed, causing a false "extension missing" error). The composer `suggest` key was corrected to `ext-openswoole`. The same fix was applied to the `serve` script shipped in the project skeleton.
- **CI**: monorepo now triggers the splitter on push/tag (event-driven sync), so sub-repos on Packagist update immediately on release instead of waiting for the hourly cron.

## 2026-11-04 v2.0.1 Fixes & Improvements

A batch of framework fixes and improvements on top of 2.0.0 (no breaking changes):

- **DB connection resilience**: long-lived connections now auto-reconnect on "server has gone away" / lost-connection errors, for both PDO and MySQLi drivers. No reconnect is attempted inside an active transaction.
- **`where` conditions**: `where([])` is now silently ignored instead of throwing `Invalid where parameter`. Added `whereIf(condition, clause)` and `whereOr(...)` helpers.
- **Join with Model classes**: `leftJoin([Model::class, 'alias'], ...)` resolves to the real table (with prefix); cross-prefix joins no longer need a workaround. Non-model class names produce a clear error.
- **Model write safety**: writes are filtered against real table columns; the missing `InsecureExecuteException` class was added.
- **Routing**: `#[Controller(prefix: '...')]` optional prefix; `{param}` placeholders supported alongside `(param:rule)`; `#[Middleware]` is now repeatable; RouteScanner fails loudly instead of silently dropping middleware.
- **Console & HTTP**: `Output` is injected via the container; `ServerRequestInterface` can be method-injected into controllers.
- **Misc**: `Foundation/composer.json` declares `sharkyphp/authorization`; CORS reads `http.response.cors.whitelist`; `ExceptionHandler::render_web` and `ServerRequest::create` fixes; the `new` command copies an `.env` stub.

## 2026\-11\-03 v2.0.0 Major Refactor Update

This version is a comprehensive full refactor of the SharkyPHP framework\. All functional components are completely split into independent libraries\. Meanwhile, the main package has been converted from a project mode to a library mode, which fully isolates the framework core from user business code and realizes a more standardized and decoupled architecture\.

All official components strictly follow PSR interface specifications\. The framework only introduces standard PSR interfaces without any third\-party implementation dependencies, adhering to the zero\-third\-party\-dependency principle\. This standardized design allows developers to freely replace and access any compliant third\-party implementations, greatly improving framework scalability\.

**Important Note**: Projects built based on version 1\.x\.x cannot be smoothly upgraded to this new refactored version and require manual migration adaptation\.

## Version 1\.x\.x Statement

All updates and iterations for version 1\.x\.x have been stopped\. The corresponding repository has been archived for retention only and will no longer be maintained\.

* **Github**    ：<https://github.com/bytesharky/SharkyPHP>
* **Gitee**     ：<https://gitee.com/bytesharky/SharkyPHP>
