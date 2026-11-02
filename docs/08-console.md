← [中文版本](./08-console_zh.md)

# Command Line (Console)

SharkyPHP handles CLI commands with the same kernel. A command consists of a "Toolkit class + registration file", and the entry point convention is `php sharky <command>`.

## Defining a Toolkit

A toolkit is an ordinary class; its public methods are the command actions:

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

## Registering Commands

Register in any `*.php` under `app/Console/Definitions/` (`ConsoleKernel` automatically loads this directory on startup):

```php
use App\Console\Toolkits\DemoToolkit;
use App\Console\Toolkits\DatabaseToolkit;
use Sharky\Facade\Command;

Command::toolkit(DemoToolkit::class)
    ->register('helloWord', 'helloWord', 'hello word');

Command::toolkit(DatabaseToolkit::class)
    ->register('db:test', 'test', 'database / model full-chain test');

// Equivalent form (passing the handler directly):
// Command::register('helloWord', [DemoToolkit::class, 'helloWord'], 'hello word');
```

- `Command::toolkit(Class::class)->register($command, $method, $desc)`: `$command` is the command name, `$method` is the public method in the class, and `$desc` is the description.
- `Command::register($command, $handler, $desc)`: the handler can be written as `[Class::class, 'method']` or `'Class@method'`.

## Command Annotations

Classes under `app/Console/Toolkits/` can also declare commands through attributes; they are scanned by `Sharky\Console\Commanding\CommandScanner` on startup:

```php
use Sharky\Annotation\Command;
use Sharky\Annotation\Middleware;

#[Middleware(\App\Console\Middleware\AuditMiddleware::class)]      // class level: applies to every command in this class
#[Command(name: 'demo:report', desc: 'Generate report')]            // class level: whole class is one command, runs execute()
class ReportToolkit
{
    public function execute(Output $output): void { /* ... */ }

    #[Command(name: 'demo:report:today', desc: 'Run today only')]
    #[Middleware(\App\Console\Middleware\EchoMiddleware::class)]     // method level: this command only
    public function today(Output $output): void { /* ... */ }
}
```

- The class-level `#[Command]` `execute` option names the handler method (defaults to `execute`); a method-level annotation uses that method itself as the handler.
- Middleware is merged in the order "class level → method level", de-duplicated.
- Command names are normalized exactly like `register()`: camel case becomes colon lowercase (`MakeController` → `make:controller`).
- Abstract classes / interfaces / enums, inherited methods and constructors are not scanned.

## Auto-exposing Package Commands

Besides the project's own `configs/console.php`, a package can declare annotation scan directories in its own `sharky-config.php`. `Application` collects them as `foundation.console.scans` and merges them with the project config (same namespace merges directory lists, duplicates are removed):

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

`#[Command]` classes in those directories are scanned and exposed on startup in the development environment, and `php sharky command:compile` writes them into the command cache as well, so they remain available in production where only the cache is read. The package does not need to list every command name in the `commands` section.

## make Generators

The scaffold package ships 5 generators, exposed through the self-scan above:

| Command | Default namespace | Target directory |
| --- | --- | --- |
| `make:controller` | `App\Http\Controllers` | `app/Http/Controllers` |
| `make:middleware` | `App\Http\Middleware` | `app/Http/Middleware` |
| `make:console:middleware` | `App\Console\Middleware` | `app/Console/Middleware` |
| `make:model` | `App\Http\Models` | `app/Http/Models` |
| `make:command` | `App\Console\Toolkits` | `app/Console/Toolkits` |

```bash
php sharky make:controller Demo/UserProfile     # => App\Http\Controllers\Demo\UserProfile
php sharky make:command SendMail                # registered as send:mail right away
php sharky make:model UserProfile --force       # overwrite an existing file
php sharky make:middleware AuthCheck            # HTTP middleware
php sharky make:middleware AuthCheck --console  # console middleware (same as the next line)
php sharky make:console:middleware AuthCheck    # console middleware
```

- The first argument is both the class name and the relative path (`Demo/user_profile` => `Demo\UserProfile`); a leading `\` means a full namespace and skips the default namespace.
- Namespaces map to PSR-4 paths: the root namespace is lower-cased (`App\` => `app/`), remaining segments keep their case.
- Generated command classes carry a `#[Command]` annotation, so they run as soon as they land in a scanned directory; generated models carry a `TABLE` constant (class name in snake case, e.g. `user_profile`) for you to adjust.
- Existing files fail by default; only `--force` overwrites.

### Two kinds of middleware

HTTP and console middleware are separate things: different inputs (`ServerRequestInterface` / `Input`), different pipelines, and different registration slots (the `http` / `console` sections of `configs/middleware.php`). The generator picks the stub and default namespace per target; `--console` is only a switch:

| Kind | Interface to implement | Registered via |
| --- | --- | --- |
| HTTP | `Sharky\Http\Middleware\MiddlewareInterface` — `process(ServerRequestInterface $request, callable $next)` returning `ResponseInterface` | route `middleware()`, the `http` section of `configs/middleware.php`, `#[Middleware]` |
| Console | `Sharky\Console\Middleware\MiddlewareInterface` — `process(Input $argvInput, callable $next)` returning `mixed` | the `console` section of `configs/middleware.php`, `#[Middleware]` on a command class/method |

Both interfaces only declare the `process` signature with no implementation, and both extend the empty marker `Sharky\Contracts\MiddlewareInterface` — that is how the pipeline recognises an implementation as a built-in middleware (rather than a PSR-15 `Psr\Http\Server\MiddlewareInterface`). The generator writes the correct `use` and `implements` for you; projects no longer need their own abstract base class.

`--console` is a switch, so put it after the class name; `make:middleware --console Foo` still works (the value is treated as the class name and a hint is printed).

## Command Cache and Loading Rules

`ConsoleKernel` runs `Command::loadCommands(base_path('app/Console/Definitions'))` on startup, following the same rules as routing:

| Environment | Behaviour |
| --- | --- |
| `APP_ENV=production` | **Loads the cache first** (`runtime/commands.php`); on a hit, declaration files are not loaded and annotations are not scanned |
| Anything else | Ignores the cache: loads `app/Console/Definitions/*.php` + scans toolkit annotations, merging the two registries |

Merging treats **the same command name** as a conflict (the name is the unique key of the command table): the same name with a different handler throws `Sharky\Console\Exception\CommandConflictException`, while the same name *and* the same handler (e.g. a package declares a command in `commands` and the annotation scan hits the same implementation) counts as a duplicate declaration and reuses the existing entry.

The cache file is produced by the scaffold command `php sharky command:compile` (`Sharky\Skeleton\Toolkits\CommandCompile`), which ignores any existing cache and rebuilds from source. The location is derived from the project root through the container facade:

```php
$directory = Container::get('project.directory');   // project root
// cache file: {$directory}/runtime/commands.php
```

You can override both the cache location and the annotation scan directories in `configs/console.php`:

```php
'commands' => [
    // command cache file (loaded first in production)
    'cache' => Helper::base_path('runtime/commands.php'),

    // command/middleware annotation scan directories [namespace => directory]
    'scan'  => [
        'App\\Console\\Toolkits' => Helper::base_path('app/Console/Toolkits'),
    ],
],
```

> Closure / object handlers cannot be written into the cache file and will fail the compilation.

The cache holds `base64(serialize(...))` of the command table, so loading is a single assignment: no `Command` instances are rebuilt (the matching stage is read-only and `$command['finalHandler']` works the same for instances and arrays) and no conflict detection runs. Package-level commands (`route:compile` / `command:compile`) are declared in the package `sharky-config.php` under `commands` and registered by the kernel **before** declarations/cache are loaded, so they stay available in production when only the cache is read.

## Running

```bash
php sharky helloWord
php sharky db:test
php sharky            # no command: list all available commands, exit code 0
php sharky list       # same as above (explicit alias)
php sharky help       # same as above (explicit alias)
php sharky nope       # unknown command: message + available commands, exit code 255
```

> Exit codes: `list` / `help` / empty command are **normal listings** and return `0`; an unknown command returns `255`, so scripts and CI can tell the two apart.

Argument parsing (`Sharky\Console\Context\Input`, handled by the kernel): supports `--key=val`, `--flag`, `-abc` (merged short options), and anything after `--` is treated as positional arguments. Handler methods can simply declare `Input $argvInput` / `Output $output` parameters — `Invoker` injects them from the container:

```php
public function execute(Input $argvInput, Output $output): void
{
    $name = $argvInput->getArgument(0);       // positional argument (no leading --)
    $flag = $argvInput->getOption('force');   // option key without --, null when absent
}
```

## Terminal Output (`Sharky\Console\Context\Output`)

```php
$out = new Output();
$out->setVerbosity(Output::VERBOSITY_DEBUG);   // QUIET / NORMAL / VERBOSE / DEBUG

$out->info('normal info');        $out->infoLn('normal info');
$out->success('success');         $out->successLn('success');
$out->warn('warning');            $out->warnLn('warning');
$out->error('error');             $out->errorLn('error');
$out->debug('debug');             $out->debugLn('debug');

$out->write('raw output', false);     // no newline
$out->writeln('raw output');          // newline
```

- Level threshold: by default at `NORMAL`, success/warn/error are output; `info` requires `VERBOSE`, `debug` requires `DEBUG`; `QUIET` is silent.
- Colors are applied automatically in a TTY terminal (blue/green/orange/red/purple), and color tags are automatically stripped when redirected to a file.
- Error-level output (warn/error) goes to `stderr`, everything else to `stdout`.

## Attribute Annotation Approach

Besides "Definitions file + registration", toolkit classes can declare commands with the `#[Command]` / `#[Middleware]` attributes (see "Command Annotations" above and [Attribute Annotations](./11-annotations.md)). Both approaches can be mixed; a duplicated command name is reported as a conflict.

---

← [Back to Documentation Index](./README.md)
