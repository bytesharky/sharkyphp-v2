# 更新说明

## 2026-11-05 v2.0.2 修复与优化

在 2.0.1 基础上的增量修复（无破坏性变更）：

- **新增包 `sharkyphp/project`**：`sharky new` 默认生成的应用骨架。现已纳入主仓库并拆为独立只读子库，使起始项目与框架版本保持同步。
- **Installer**：修复安装时组件版本解析失败的问题；对生成的项目模板视图做了微调。
- **Runtime-Swoole**：`sharky serve swoole` 现通过 `extension_loaded('openswoole')` 正确检测 OpenSwoole（此前误用 `swoole`，在仅安装 OpenSwoole 时会误报"扩展未加载"）；composer `suggest` 键更正为 `ext-openswoole`，同样的修复也应用到项目骨架自带的 `serve` 脚本。
- **CI**：主仓库现已在 push/tag 时触发拆库（事件驱动同步），发版后子库与 Packagist 即时更新，无需再等每小时定时。

## 2026-11-04 v2.0.1 修复与优化

在 2.0.0 基础上的框架修复与优化（无破坏性变更）：

- **数据库连接韧性**：长连接失效（server has gone away / 连接断开）时自动重连，PDO 与 MySQLi 双驱动均已支持；处于事务中不重连，以保原子性。
- **where 条件**：`where([])` 改为静默忽略，不再抛 `Invalid where parameter`；新增 `whereIf(condition, clause)` 与 `whereOr(...)` 便捷写法。
- **join 支持模型类名**：`leftJoin([Model::class, 'alias'], ...)` 自动解析为真实表名（含前缀），跨前缀 join 无需 workaround；误传非模型类名会给出清晰报错。
- **模型写入安全**：写入按真实表字段裁剪；补建此前缺失的 `InsecureExecuteException` 类。
- **路由**：`#[Controller(prefix: '...')]` 支持可选前缀；`{param}` 占位符与 `(param:rule)` 并存；`#[Middleware]` 支持重复声明；RouteScanner 由静默吞错改为抛出带上下文异常。
- **命令行与 HTTP**：`Output` 经容器注入；控制器方法可直接注入 `ServerRequestInterface`。
- **其他**：`Foundation/composer.json` 声明 `sharkyphp/authorization` 依赖；CORS 读取配置 `http.response.cors.whitelist`；修复 `ExceptionHandler::render_web` 与 `ServerRequest::create`；`new` 命令复制 `.env` 模板。

## 2026\-11\-03 v2.0.0 重大版本重构更新

本次版本为框架全方位完整重构，核心优化架构与项目定位：将框架所有功能组件彻底拆分、封装为独立依赖库，同时主包从项目工程模式调整为类库模式，彻底实现框架内核与用户业务代码的完全隔离，架构更规范、解耦度更高。

所有官方组件全面遵循 PSR 标准化接口契约，本次适配仅引入 PSR 标准接口，未引入任何第三方实现依赖，始终坚守框架零第三方依赖的核心原则。标准化接口设计，让开发者可灵活替换、适配任意符合规范的第三方实现，扩展性大幅提升。

**重要提示**：基于 1\.x\.x 版本搭建的项目，无法平滑升级至本次重构新版本，需手动适配迁移。

## 1\.x\.x 版本说明

1\.x\.x 版本已停止所有迭代更新，对应代码仓库已正式归档，仅做留存查看，不再维护。

* **Github**    ：<https://github.com/bytesharky/SharkyPHP>
* **Gitee**     ：<https://gitee.com/bytesharky/SharkyPHP>
