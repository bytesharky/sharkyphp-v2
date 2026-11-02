# SharkyPHP

SharkyPHP v2.0.0 是一款轻量、原生手写的 PHP 框架，**双内核分离设计**：HTTP 内核专职处理 Web 请求、CLI 内核专职处理命令行指令；零第三方运行时依赖，便于二次扩展。

## 概述

SharkyPHP 摒弃臃肿的抽象与冗余层级，坚持直白、可读的源码实现。框架功能以一组相互独立、可组合的独立 Composer 包形式发布，各包边界清晰、耦合极低；核心内核统一完成组件配置与生命周期的编排。

主要特性：

- **零第三方运行时依赖** —— 整套框架以手写 PHP 实现，便于追溯、审计与扩展。
- **全面遵循 PSR 标准** —— 实现 PSR‑7 / PSR‑11 / PSR‑14 / PSR‑17 / PSR‑18 / PSR‑20，可与任何兼容库互换使用。
- **显式优于隐式** —— 依赖注入、路由定义与请求处理均显式、可预测，不存在需要对抗的隐藏行为。
- **洋葱式中间件管道**，支持请求/响应上下文隔离。
- **数据库层** 提供流畅的查询构造器、ActiveRecord 模型与读写分离支持。
- **可插拔驱动** 用于缓存、会话与存储，并配以灵活的配置系统。

框架面向偏好掌控运行时、而非将控制权让渡给不透明框架魔法的开发者。

## 组件包

SharkyPHP 以独立包的形式发布于 `framework/packages/`，主要包括：

| 包                      | 职责                                     |
| ----------------------- | ---------------------------------------- |
| Foundation              | 应用核心、配置、上下文、事件装配         |
| Http                    | 路由、PSR‑7 消息、中间件、Cookie、上传   |
| Database                | 连接管理、查询构造器、ActiveRecord、事务 |
| Cache / Redis / Session | 缓存、Redis 适配器、会话管理             |
| Event                   | PSR‑14 事件系统                          |
| Authorization           | JWT、图形验证码、TOTP 多因素认证         |
| Template                | 编译型微模板引擎                         |
| Console                 | CLI 核心、参数解析、彩色输出             |
| Clock                   | SystemClock / FrozenClock（PSR‑20）      |
| Container               | 容器依赖注入                             |
| Pipeline                | 中间件、洋葱管道                         |
| Support                 | 助手函数                                 |
| Contracts               | 接口契约                                 |
| Annotation              | 属性注解                                 |
| Skeleton                | 脚手架                                   |

各包**有意不**在 `composer.json` 里写死 `version`：包版本由 Packagist 依据 git tag 解析，硬编码只会带来校验告警和 tag 不一致。本地 `path` 开发所需的版本改由仓库定义注入 —— 发版时只改 [`packages/versions.json`](./packages/versions.json)，`sharky new` 会把这份映射写进生成的项目。

## 文档

面向使用者的完整文档维护于 [`docs/`](./docs/README_zh.md)，逐项说明各组件对外暴露的 API —— 类、方法、门面与属性注解，并配有实践示例。

- **文档索引：** [docs/README.md](./docs/README_zh.md)
- 涵盖主题：路由、控制器与响应、数据库、缓存 / Redis / 会话、事件、鉴权（JWT / 验证码 / MFA）、模板引擎、控制台、国际化、门面与助手函数、属性注解 —— 均在对应链接文件中详述。

> 文档聚焦于**如何使用**框架；组件的内部生命周期与装配细节已刻意省略。

## 作者与链接

- **作者：** ByteSharky `<fish@doffish.com>`
- **演示站点：** <https://sharky.doffish.com/>
- **GitHub：** <https://github.com/bytesharky/sharky-php-v2>
- **Gitee：** <https://gitee.com/bytesharky/sharky-php-v2>

---

© ByteSharky。许可证条款见 [LICENSE](./LICENSE)。