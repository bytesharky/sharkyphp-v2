← [English version](./08-console.md)

# 命令行（Console）

SharkyPHP 用同一内核处理 CLI 命令。命令由「工具集类（Toolkit）+ 注册文件」组成，入口约定为 `php sharky <command>`。

## 定义工具集

工具集是普通类，公开方法即命令动作：

```php
namespace App\Console\Toolkits;

use Sharky\Console\Context\Output;

class DemoToolkit
{
    public function helloWord()
    {
        $out = new Output();
        $out->infoLn('Hello, SharkyPHP!');
    }
}
```

## 注册命令

在 `app/Console/Definitions/` 下的任意 `*.php` 中注册（`ConsoleKernel` 启动时会自动加载该目录）：

```php
use App\Console\Toolkits\DemoToolkit;
use App\Console\Toolkits\DatabaseToolkit;
use Sharky\Facade\Command;

Command::toolkit(DemoToolkit::class)
    ->register('helloWord', 'helloWord', 'hello word');

Command::toolkit(DatabaseToolkit::class)
    ->register('db:test', 'test', 'database / model 全链路测试');

// 等价写法（直接传处理器）：
// Command::register('helloWord', [DemoToolkit::class, 'helloWord'], 'hello word');
```

- `Command::toolkit(Class::class)->register($command, $method, $desc)`：`$command` 为命令名，`$method` 为类中的公开方法，`$desc` 为说明。
- `Command::register($command, $handler, $desc)`：处理器可写 `[Class::class, 'method']` 或 `'Class@method'`。

## 命令注解

`app/Console/Toolkits/` 下的类也可以用属性注解声明命令，启动时由 `Sharky\Console\Commanding\CommandScanner` 扫描：

```php
use Sharky\Annotation\Command;
use Sharky\Annotation\Middleware;

#[Middleware(\App\Console\Middleware\AuditMiddleware::class)]      // 类级：该类下全部命令生效
#[Command(name: 'demo:report', desc: '生成报表')]                    // 类级：整类一个命令，执行 execute()
class ReportToolkit
{
    public function execute(Output $output): void { /* ... */ }

    #[Command(name: 'demo:report:today', desc: '只跑今天')]
    #[Middleware(\App\Console\Middleware\EchoMiddleware::class)]     // 方法级：仅该命令生效
    public function today(Output $output): void { /* ... */ }
}
```

- 类级 `#[Command]` 的 `execute` 指定执行方法（默认 `execute`）；方法级注解直接以该方法为处理器。
- 中间件按「类级 → 方法级」合并去重。
- 命令名与 `register()` 一样会规范化：驼峰转冒号小写（`MakeController` → `make:controller`）。
- 抽象类 / 接口 / 枚举、继承来的方法与构造方法不参与扫描。

## 子包命令自动暴露

除了项目自己的 `configs/console.php`，子包还能在自己的 `sharky-config.php` 里声明注解扫描目录，启动时由 `Application` 收集为 `foundation.console.scans`，再与项目配置合并（同命名空间合并目录列表、重复目录去重）：

```php
// framework/packages/Skeleton/sharky-config.php
return [
    'console' => [
        'scan' => [
            'Sharky\\Skeleton\\Toolkits' => __DIR__ . '/src/Toolkits',
        ],
    ],
];
```

目录内的 `#[Command]` 类在开发环境启动即被扫描暴露；`php sharky command:compile` 会把它们一并写入命令缓存，生产环境只读缓存时同样可用。包无需在 `commands` 段逐条登记命令名。

## make 生成器

脚手架包自带 5 个生成器，靠上面的自扫描暴露：

| 命令 | 默认命名空间 | 落盘目录 |
| --- | --- | --- |
| `make:controller` | `App\Http\Controllers` | `app/Http/Controllers` |
| `make:middleware` | `App\Http\Middleware` | `app/Http/Middleware` |
| `make:console:middleware` | `App\Console\Middleware` | `app/Console/Middleware` |
| `make:model` | `App\Http\Models` | `app/Http/Models` |
| `make:command` | `App\Console\Toolkits` | `app/Console/Toolkits` |

```bash
php sharky make:controller Demo/UserProfile    # => App\Http\Controllers\Demo\UserProfile
php sharky make:command SendMail               # 生成后即刻注册为 send:mail
php sharky make:model UserProfile --force      # 覆盖同名文件
php sharky make:middleware AuthCheck           # HTTP 中间件
php sharky make:middleware AuthCheck --console # 命令行中间件（等价于下一行）
php sharky make:console:middleware AuthCheck   # 命令行中间件
```

- 第一个参数既是类名也是相对路径（`Demo/user_profile` => `Demo\UserProfile`）；以 `\` 开头视为完整命名空间，不再拼接默认命名空间。
- 命名空间按 PSR-4 落盘：根命名空间小写（`App\` => `app/`），其余段保持原样。
- 生成的命令类带 `#[Command]` 注解，落在扫描目录内即刻可执行；生成的模型带 `TABLE` 常量（类名转下划线，如 `user_profile`）供修改。
- 文件已存在时默认报错退出，`--force` 才会覆盖。

### 两种中间件

HTTP 与 Console 中间件是两套东西：入参不同（`ServerRequestInterface` / `Input`）、所处链路不同、注册位置也不同（`configs/middleware.php` 的 `http` / `console` 段）。生成器按目标形态选模板和默认命名空间，`--console` 只是切换开关：

| 形态 | 实现的接口 | 注册位置 |
| --- | --- | --- |
| HTTP | `Sharky\Http\Middleware\MiddlewareInterface`，`process(ServerRequestInterface $request, callable $next)` 返回 `ResponseInterface` | 路由 `middleware()`、`configs/middleware.php` 的 `http` 段、`#[Middleware]` 注解 |
| Console | `Sharky\Console\Middleware\MiddlewareInterface`，`process(Input $argvInput, callable $next)` 返回 `mixed` | `configs/middleware.php` 的 `console` 段、命令类/方法上的 `#[Middleware]` 注解 |

两个接口都只声明 `process` 签名、不含任何实现，并共同继承空标记接口 `Sharky\Contracts\MiddlewareInterface`——中间件管道据此把实现类识别为内置中间件（而不是 PSR-15 的 `Psr\Http\Server\MiddlewareInterface`）。生成器会自动写入正确的 `use` 与 `implements`，不需要项目再定义抽象基类。

`--console` 是开关，请写在类名之后；写成 `make:middleware --console Foo` 也能用（会把 `Foo` 当类名并给出提示）。

## 命令缓存与加载规则

`ConsoleKernel` 启动时执行 `Command::loadCommands(base_path('app/Console/Definitions'))`，规则与路由一致：

| 环境 | 行为 |
| --- | --- |
| `APP_ENV=production` | **优先加载缓存** `runtime/commands.php`；命中后不再加载命令声明文件、也不再扫描注解 |
| 其他环境 | 忽略缓存：加载 `app/Console/Definitions/*.php` + 扫描工具包注解，两者合并注册 |

合并时以**同名命令**判定冲突（命令名即命令表唯一键）：命令名相同但处理器不同，抛 `Sharky\Console\Exception\CommandConflictException`；命令名与处理器都相同（例如包在 `commands` 段声明、注解又扫描到同一实现）视为重复声明，直接复用已有条目。

缓存文件由脚手架命令 `php sharky command:compile`（`Sharky\Skeleton\Toolkits\CommandCompile`）生成：强制忽略已有缓存重新编译。路径由容器门面推导项目根目录：

```php
$directory = Container::get('project.directory');   // 项目根目录
// 缓存文件：{$directory}/runtime/commands.php
```

可在 `configs/console.php` 覆盖缓存位置与注解扫描目录：

```php
'commands' => [
    // 命令行缓存文件（生产环境优先加载）
    'cache' => Helper::base_path('runtime/commands.php'),

    // 命令/中间件注解扫描目录 [命名空间 => 目录]
    'scan'  => [
        'App\\Console\\Toolkits' => Helper::base_path('app/Console/Toolkits'),
    ],
],
```

> 闭包 / 对象处理器无法写入缓存文件，编译时会直接报错。

缓存内容是 `base64(serialize(...))` 的命令表，装载就是一次赋值：不还原 `Command` 实例（匹配阶段只读，`$command['finalHandler']` 对实例与数组同样成立）、不做冲突检测。包级命令（如 `route:compile` / `command:compile`）在包的 `sharky-config.php` 的 `commands` 段声明，由内核在加载声明文件/缓存**之前**注册，因此生产环境只读缓存时依然可用。

## 运行

```bash
php sharky helloWord
php sharky db:test
php sharky            # 未带命令：列出全部可用命令，退出码 0
php sharky list       # 同上（显式别名）
php sharky help       # 同上（显式别名）
php sharky nope       # 命令不存在：提示 + 列出可用命令，退出码 255
```

> 退出码约定：`list` / `help` / 空命令属于**正常展示**，返回 `0`；未知命令返回 `255`，便于脚本与 CI 判定。

参数解析（`Sharky\Console\Context\Input`，由内核处理）：支持 `--key=val`、`--flag`、`-abc`（短参合并）、`--` 之后视为位置参数。处理器方法直接声明 `Input $argvInput` / `Output $output` 参数即可，`Invoker` 会从容器注入：

```php
public function execute(Input $argvInput, Output $output): void
{
    $name = $argvInput->getArgument(0);       // 位置参数（无前导 --）
    $flag = $argvInput->getOption('force');   // 选项键不带 --，不存在时返回 null
}
```

## 终端输出（`Sharky\Console\Context\Output`）

```php
$out = new Output();
$out->setVerbosity(Output::VERBOSITY_DEBUG);   // QUIET / NORMAL / VERBOSE / DEBUG

$out->info('普通信息');        $out->infoLn('普通信息');
$out->success('成功');         $out->successLn('成功');
$out->warn('警告');            $out->warnLn('警告');
$out->error('错误');           $out->errorLn('错误');
$out->debug('调试');           $out->debugLn('调试');

$out->write('原始输出', false);     // 不换行
$out->writeln('原始输出');          // 换行
```

- 级别阈值：默认 `NORMAL` 下输出 success/warn/error；`info` 需 `VERBOSE`，`debug` 需 `DEBUG`；`QUIET` 静默。
- 在 TTY 终端自动上色（蓝/绿/橙/红/紫），重定向到文件时自动去除颜色标签。
- 错误类（warn/error）输出到 `stderr`，其余到 `stdout`。

## 属性注解方式

除「Definitions 文件 + 注册」外，工具包类也可以用 `#[Command]` / `#[Middleware]` 注解声明命令（见上节「命令注解」与[属性注解](./11-annotations.md)）。两种方式可混用，同名命令会作为冲突抛出异常。

---

← [返回文档索引](./README_zh.md)
