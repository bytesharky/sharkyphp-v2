# SharkyPHP

SharkyPHP is a lightweight, handwritten native PHP framework featuring a **dual-kernel separation design**: the HTTP kernel is dedicated to web requests and the CLI kernel to console commands. has zero third-party runtime dependencies, and is friendly for further extension.

## Overview

SharkyPHP deliberately avoids bloated abstractions and redundant layers in favor of straightforward, readable source code. Framework functionality is distributed as a set of independent, composable Composer packages — each with clear boundaries and minimal coupling — while the core kernel orchestrates their configuration and lifecycle.

Key characteristics:

- **Zero third-party runtime dependencies** — the entire framework is implemented in hand-written PHP, making it easy to trace, audit, and extend.
- **Full PSR compliance** — implements PSR‑7, PSR‑11, PSR‑14, PSR‑17, PSR‑18, and PSR‑20, ensuring interoperability with any compatible library.
- **Explicit over magical** — dependency injection, route definition, and request handling are explicit and predictable; there is no hidden behavior to fight against.
- **Onion middleware pipeline** with request/response context isolation.
- **Database layer** with a fluent query builder, ActiveRecord models, and read/write separation support.
- **Pluggable drivers** for cache, session, and storage, backed by a flexible configuration system.

The framework is intended for developers who prefer to understand and control their runtime rather than delegate it to opaque framework magic.

## Packages

SharkyPHP is distributed as a collection of independent packages under `framework/packages/`, including:

| Package                 | Responsibility                                         |
| ----------------------- | ------------------------------------------------------ |
| Foundation              | Application core, configuration, context, event wiring |
| Http                    | Routing, PSR‑7 messages, middleware, cookies, uploads  |
| Database                | Connections, query builder, ActiveRecord, transactions |
| Cache / Redis / Session | Caching, Redis adapter, session management             |
| Event                   | PSR‑14 event system                                    |
| Authorization           | JWT, image captcha, TOTP multi-factor authentication   |
| Template                | Compiled micro-template engine                         |
| Console                 | CLI core, argument parsing, colored output             |
| Clock                   | SystemClock / FrozenClock (PSR‑20)                     |
| Container               | DI container                                           |
| Pipeline                | middleware 、pipeline                                  |
| Support                 | helpers                                                |
| Contracts               | interfaces                                             |
| Annotation              | attributes                                             |
| Skeleton                | scaffolding                                            |

Package versions are deliberately **not** hard-coded in each package's `composer.json`: Packagist resolves them from git tags, and a `version` field only produces validation warnings and tag drift. Local `path` development gets its versions from the repository definition instead — bump the release in [`packages/versions.json`](./packages/versions.json), and `sharky new` will inject that map into generated projects.

## Documentation

Comprehensive, user-facing usage documentation is maintained under [`docs/`](./docs/README.md). It describes each component's public API — classes, methods, facades, and attribute annotations — together with practical examples.

- **Documentation index:** [docs/README.md](./docs/README.md)
- Topics covered: routing, controllers & responses, database, cache / Redis / session, events, authorization (JWT / captcha / MFA), template engine, console, I18n, facades & helpers, and attribute annotations — each detailed in the linked files.

> The documentation focuses on *how to use* the framework. Internal lifecycle and component-wiring internals are intentionally omitted.

## Author & Links

- **Author:** ByteSharky `<fish@doffish.com>`
- **Demo:** <https://sharky.doffish.com/>
- **GitHub:** <https://github.com/bytesharky/sharkyphp-v2>
- **Gitee:** <https://gitee.com/bytesharky/sharkyphp-v2>

---

© ByteSharky. Licensed under the terms in [LICENSE](./LICENSE).