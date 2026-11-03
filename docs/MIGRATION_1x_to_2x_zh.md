# SharkyPHP 1.x → 2.0 迁移指南（通用版）

> 适用对象：任何基于 **SharkyPHP 1.x** 开发的业务项目，升级到 **2.0** 内核。
> 本文只讲**框架级通用机制**与最常遇到的迁移点，不绑定任何具体业务项目。
> 凡标注 **v2.0.1** 的问题已由框架修复——业务代码无需再加兼容 shim。
> 原则：框架是上游，不为迁移项目写向后兼容 shim；1.x 的历史写法一律改为 2.0 原生 API。

---

## 0. 概览：2.0 改了什么

- **模型 / 查询**：`Model::__call` 魔术方法移除，统一走 `static::query()` 返回的 `ActiveQuery`。
- **表前缀 / 表名**：前缀与表名**完全由模型显式声明决定**，不再有 1.x 的隐式推断魔法；迁移时最常见错误是「表不存在」。
- **路由 / 控制器**：属性路由 + 注解；控制器执行体必须返回 `ResponseInterface`；请求对象可方法注入。
- **命令行**：从继承 `Console` 改为 `#[Command]` 注解 + `execute()`；输出由框架注入。
- **连接管理**：模型连接由框架启动期统一注入；长连接失效可自动重连（事务内不重连）。

---

## 1. ORM：模型不再有 `__call` 魔术

2.0 的 `Model` 没有任何魔术方法，所有查询能力来自 `static::query()` 返回的 `ActiveQuery`（方法名与 1.x 保持一致）。

| 1.x | 2.0 |
| --- | --- |
| `$model->where(...)->select(...)->toArray()`（靠 `Model::__call` 转发） | 先 `static::query()` 拿到 `ActiveQuery`，再链式调用其方法 |

**通用落地模式（代理层）**：在你们的**模型基类**里注入一层代理方法，把常用查询
（`fields / alias / where / orWhere / leftJoin / ...`）写成实例方法，内部返回
`static::query()->xxx()`。调用方从 `$model->where(...)` 改为
`$model->alias('m')->where(...)`——**链首必须是 `static::query()` 或 `alias()`**。

> 这是迁移期的「适配层」，不是框架要求。等全量迁完后，可逐步直接写
> `static::query()->where(...)` 并把代理层标记废弃。

### 1.1 代理覆盖不了、必须手写的点

1. **执行器差异**：1.x 的 `->select()->toArray()`（执行器）→ 2.0 的 `->get()->toArray()`（多行）/ `->first()`（单行）。
2. **闭包内 `whereOr()`** → 改用 `orWhere()`。
3. **原生 SQL 闭包** `fn() => "...sql..."` → 改用 `new Sharky\Database\Query\Expr("...sql...")`。
4. **子查询**：2.0 的查询构造器默认**惰性、不执行**（只产出 SQL 片段，直到显式 `get()/first()` 才跑），
   因此子查询就是「再构造一个查询构造器并嵌入主查询（派生表 / join 条件）」，无需 1.x 那种「标记不执行」的写法。
   - 1.x：子查询构造器会**自动执行**，所以要嵌进主查询时，需要专门的「不执行」标记把它当作 SQL 片段。
   - 2.0：没有这个负担——常用做法是直接用 **join 查询构造器**替代 1.x 的嵌套子查询；把另一个 `QueryBuilder` / `ActiveQuery` 作为片段拼入即可。

### 1.2 `find()` 是静态方法，子类不要写同名非静态代理

- `Model::find($id)` 是框架静态 API；子类若声明同名非静态方法会与签名冲突（静态 vs 非静态、返回类型不一致）。
- **约定**：不要重写 `find`；使用 `static::query()->where(['id' => $id])->first()`，或另起一个实例方法名（如 `fetchOne`）。
- 同理，1.x 的 `Model::fill()` 在 2.0 不存在，改为 `Model::newFromRow($row)`（具体方法名以你项目的 Model 基类为准）。

---

## 2. 表前缀铁律（通用规则）

**2.0 的前缀规则只有一条，且与具体项目无关：**

- `Model::getPrefix()` 优先取模型定义的 `static::PREFIX` 常量；未定义时取框架配置 `DB_PREFIX`（默认值依项目而定，可为空）。
- 因此「系统表前缀 vs 业务表前缀」的区分，**100% 由模型显式声明**，框架不会自动猜。

**迁移期最典型的坑**：1.x 项目常把系统表与业务表用不同前缀区分（如 `sys_` 与 `biz_`）。
升级时若某张表报「`X 表不存在`」，**先检查该模型是否漏声明 `PREFIX` / `TABLE`，而不是去改数据库结构去迁就框架推断。**

- 系统表模型：定义 `public const PREFIX = 'sys_';`（或你项目约定的前缀）。
- 业务表模型：不设 `PREFIX`（走 `DB_PREFIX`）或显式设为业务前缀。
- 不同业务模块若需不同前缀，用一个设了 `PREFIX` 的模块基类 + 一个不设的业务基类即可，无需引入框架未提供的约定类。

---

## 3. 表名推断与显式声明

- `Model::TABLE` 未定义时，框架按「去 `Model / Entity / Record` 后缀 + `camelToSnake`」推断
  （如 `CardKeys` → `card_keys`）。
- 若 1.x 真表名不符（如 `cardkeys`、`biz_card_keys`），**必须显式**
  `public const TABLE = '...'`；否则查询 / `leftJoin` 的表名会错位。
- **迁移前务必逐一核对各模型对应的真实表名**，把不符的全部用 `TABLE` / `PREFIX` 显式写死。

---

## 4. `where` 条件

- `where([])` 空数组：1.x 容错；2.0 早期直接抛 `Invalid where parameter`。
  **v2.0.1 改为静默忽略（不生成该 WHERE 子句）**。
- 因此「条件可能为空」的写法（如 `$ids ? ['id','in',$ids] : []`）必须**先判空再决定是否追加**
  `where` 子句，绝不可把 `[]` 直接传进 `where()`。
- v2.0.1 新增便捷方法（QueryBuilder 与 ActiveQuery 均已提供）：
  - `whereIf(mixed $condition, callable|array $clause)`：条件为真才追加该 where 子句。
  - `whereOr(...)`：`orWhere(...)` 的别名。
- `where` 入参格式：三元素数组列表 `[['列', '运算符', '值'], ...]`；列表达式用 `Expr` 代替 1.x 的 `fn()` 闭包。

---

## 5. ActiveQuery 是流式对象，必须赋回变量

- `$q = $model->alias('m')->select(...)->where(...)` 之后，**务必把链首赋给变量**，再 `$q->orderBy()->get()`。
- 若只在 `$model` 上链式调用而不接收返回值，后续 `->get()` 会在 Model 上重建一条
  「无 alias / 无 join」的新查询 → `Unknown column 'm.xxx'`。`toSql()` 同理（它只存在于 ActiveQuery）。

---

## 6. join 与模型类名解析

- 1.x：`leftJoin([SomeModel::class,'r'])` 把类名当表名（靠框架特殊处理）。
- 2.0 早期：join 把类名当字面量 → `Illegal table name`。
- **v2.0.1 修复**：`QueryBuilder` 的表名解析支持 Model 类名——
  `leftJoin([SomeModel::class, 'r'], ...)` 会自动解析为 `SomeModel` 的真实表名（含其前缀）；
  主表若是另一前缀模型，join 结果会正确带各自前缀，**无需任何「清前缀」的 hack 方法**。
- 若误传一个非模型类名，会给出友好错误：
  `Illegal table name \`Foo\`：它看起来像类名但不是 Model，请传 Model 类或原始表名`。

---

## 7. 路由 / 控制器接线

- 类级 `#[Controller(name, prefix: '/your/prefix')]`：`prefix` 会**自动拼到类下所有方法级路由 path 前**
  （v2.0.1 新增可选 `prefix` 参数；老写法不加 `prefix` 行为完全不变）。
- 动词注解必须 `use Sharky\Annotation\Http\{Get,Post,Put,Delete}`；
  误写成顶层类（如 `use Sharky\Annotation\Get`）会**静默零路由**（扫描器 catch 后跳过，不报错）。
- 路径参数统一用 `string $param`（Invoker 不做类型 cast）；URL 查询参数需自行 `(int)` cast。
- 控制器**必须返回 `ResponseInterface`**；否则 HttpKernel 抛 `HandlerReturnInvalidException`。
  各项目通常有统一的响应构造器（如 `XxxResponse::success/failed/error`），自行保持一致即可。
- 接口契约：若 1.x 项目有控制器接口约定（如要求 `list / add / edit / delete` 等方法），
  2.0 仍需满足该接口，但方法签名必须协变（返回类型、参数不能比接口更宽松）。
- 请求对象的两种获取方式：
  - `Sharky\Facade\Context::getRequest()`（沿用习惯写法）；
  - **控制器方法直接注入 `ServerRequestInterface $request`**（v2.0.1 已支持，经 Invoker + 容器绑定）。

---

## 8. 中间件配置

- 全局中间件栈在配置 `http.middleware`（不同项目配置文件路径略有差异，通常是 `config/` 下返回数组的文件），
  是一个**类名数组**，应用到每个请求；**不是别名注册表**。
- 路由级 `#[Middleware(name: 'alias')]` 写字符串别名会 `cannot be instantiated`。
- 路由级中间件必须写 FQCN 字符串：`#[Middleware(name: \App\Middlewares\AuthMiddleware::class)]`。
- v2.0.1 修复：`Middleware` 注解漏 `IS_REPEATABLE`，同一类 / 方法叠多个 `#[Middleware(...)]` 时，
  反射实例化抛 "must not be repeated"，被 `RouteScanner::attributes()` 的 `catch(Throwable)` 整体吞掉 →
  **该类 / 方法的全部中间件静默丢失**。现已声明 `IS_REPEATABLE`，并把 catch 收窄为抛带上下文的
  `RuntimeException`，让问题显形而非静默。

---

## 9. 命令行（Console）

- 1.x：命令继承 `Sharky\Core\Console`，框架注入 `$this->output` 与 `$this->argv`。
- 2.0：命令用 `#[Command(name, desc)]` 标注，`execute()` 为执行体，由 `ConsoleKernel` + `Invoker` 扫描注册并派发。
- 输出 `Output`：
  - 1.x 由框架注入到 `$this->output`。
  - 2.0 早期需自行 `new Output()`；**v2.0.1 改由框架在命令执行前注入运行时上下文**
    （`ConsoleKernel::setOutput(new Output())`），并通过容器绑定 `Sharky\Console\Context\Output` 暴露。
  - 命令应统一用 `Sharky\Facade\Context::getOutput()` 获取（保留 `?? new Output()` 兜底以兼容非 CLI 上下文）。
- 输入：`Sharky\Facade\Context::getInput()`。

---

## 10. 连接自动注入 + 长连接重连

- 1.x：模型常接收 `Database` 构造参数（如 `new SomeModel($db)`）。
- 2.0：模型不再接收连接构造参数，连接由框架在启动期统一注入
  （v2.0.1：`Application::bootstrap()` 注入；入口文件 `public/index.php` 不再手写）。
- 长连接失效自动重连：v2.0.1 在 `AbstractConnection` 增加 `retryable()` + `isConnectionLost()` 重试；
  **事务中不重连**（避免破坏事务原子性）。PDO 与 MySQLi 两个驱动均已覆盖。
- `Database::connect()` / `Redis::connect()` 改为 `connect(?string $name)`，不写死节点名，默认取 hosts 首节点。

---

## 11. v2.0.1 框架修复清单（迁移相关，无需业务 shim）

1. `ExceptionHandler::render_web`：`realpath` 失败返 `false` 盖住真实异常 → 修复。
2. `ServerRequest::create`：漏 `return` → 修复。
3. `NewCommand`：新增 `copyEnvStub`（脚手架 `new` 命令把 `.env` 模板复制成新项目 `.env`）。
4. `HttpKernel`：CORS 读配置 `http.response.cors.whitelist`（数组 + `*` 通配）。
5. `Controller` 注解 + `RouteScanner`：可选 `prefix`，自动拼到方法路由前。
6. `Middleware` 注解：补 `IS_REPEATABLE`（多注解不再静默丢失）。
7. `Foundation/composer.json`：补声明 `sharkyphp/authorization` 依赖。
8. `Route` / `Router`：支持 `{param}` 占位符（历史路由曾因只认 `(param:rule)` 而 404）。
9. `Model::filterFillable` + `ActiveQuery`：写入按真实表字段裁剪（非表字段不再进 SQL）；补建缺失的 `InsecureExecuteException`。
10. `Application::bootstrap` 注入模型连接；`Database` / `Redis` `connect(?string)`。
11. 连接层长连接失效自动重连（PDO + MySQLi 双驱动）。
12. `join` 解析 Model 类名（跨前缀 join 无需 hack）。
13. `where([])` 静默忽略；新增 `whereIf` / `whereOr`。
14. `RouteScanner` 属性实例化失败由「静默吞」改为「抛带上下文异常」。
15. `Output` 绑定容器 + `ConsoleKernel` 注入。

---

## 12. 通用迁移注意点

- 临时脚本 / 探针 / 一次性迁移脚本必须：
  ```php
  $app = require_once __DIR__ . '/bootstrap.php';
  $app->bootstrap();
  ```
  bootstrap **只能 require 一次**，否则报 `Container instance already exists, cannot overwrite`。
- 框架是上游，不为迁移项目加向后兼容 shim：凡「先在 ActiveQuery 上 `fields()`」等 1.x 写法，一律改原生 `select()`。
- 迁移完成后务必做端到端验收：DB 读写（含跨前缀 join）、Redis、各路由返回 200、命令行命令可运行。
- 建议迁移顺序：① 模型基类 + 前缀/表名核对 → ② ORM 查询改写 → ③ 路由/控制器 → ④ 中间件 → ⑤ 命令行 → ⑥ 连接重连验收。
