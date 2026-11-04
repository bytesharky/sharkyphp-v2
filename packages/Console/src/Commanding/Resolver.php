<?php
/**
 * SharkyPHP Console Library
 *
 * CLI 命令管理器
 *
 * @package SharkyPHP\\Console
 * @author Sharky
 * @copyright 2026 SharkyPHP
 * @license MIT
 * @internal
 */
declare (strict_types = 1);

namespace Sharky\Console\Commanding;

use Sharky\Console\Context\Input;
use Sharky\Console\Context\Output;
use Sharky\Console\Exception\CommandConflictException;
use Sharky\Support\Helper;

class Resolver
{
    private const COMMAND = 'sharky';

    /**
     * 命令表
     *
     * 声明注册阶段存 Command 实例（可继续 middleware() 调整）；
     * 缓存装载阶段直接存数组，匹配阶段只读，统一走 ArrayAccess 取值。
     *
     * @var array<string, Command|array<string, mixed>>
     */
    private array $commands = [];

    private Context $context;
    private Context $baseSnapshot;

    // 缓存条目的结构兜底（缓存由本框架生成，此处只补缺失键）
    private const COMMAND_DEFAULTS = [
        'command'      => '',
        'finalHandler' => null,
        'middlewares'  => [],
        'desc'         => '',
    ];

    public function __construct()
    {
        // 初始化空上下文
        $this->context = new Context();
        // 克隆一份作为初始基线快照
        $this->baseSnapshot = $this->context->getSnapshot();
    }

    // 清空命令表与上下文
    public function flush(): self
    {
        $this->commands     = [];
        $this->context      = new Context();
        $this->baseSnapshot = $this->context->getSnapshot();

        return $this;
    }

    /**
     * 注册命令行
     *
     * 同名命令视为冲突并抛异常（命令名是命令表的唯一键）。
     */
    public function register(string $command, mixed $handler, string $desc): Command
    {
        // 组装路径/回调
        $prefix      = $this->context->prefix;
        $toolkit     = $this->context->toolkit;
        $middlewares = $this->context->getMiddleware();
        $fullCommand = $this->normalize("{$prefix}{$command}");

        if (is_string($handler) && str_contains($handler, '@')) {
            $finalHandler = explode('@', $handler, 2);

        } elseif ($toolkit !== '' && is_string($handler)) {
            $finalHandler = [$toolkit, $handler];

        } else {
            $finalHandler = $handler;
        }

        // 冲突检测：同一命令名
        if (isset($this->commands[$fullCommand])) {
            $existing = $this->commands[$fullCommand];

            // 同名同处理器：包声明与注解扫描指向同一实现，属重复声明，直接复用
            if (($existing['finalHandler'] ?? null) === $finalHandler) {
                $this->context = $this->baseSnapshot->getSnapshot();

                return $existing;
            }

            throw CommandConflictException::make(
                $fullCommand,
                $this->describeHandler($existing['finalHandler']),
                $this->describeHandler($finalHandler)
            );
        }

        // 创建命令
        $newCommand = new Command(
            $fullCommand,
            $finalHandler,
            $middlewares,
            $desc
        );

        // 恢复到当前组的基准快照
        $this->context = $this->baseSnapshot->getSnapshot();

        // 添加到命令表
        $this->commands[$fullCommand] = $newCommand;

        return $newCommand;
    }

    /**
     * 加载命令行
     *
     * 加载规则：
     *  - 生产环境（APP_ENV=production）：优先加载缓存文件，命中后
     *    不再加载命令声明文件、也不扫描命令注解；
     *  - 其他环境：忽略缓存，加载命令声明文件并扫描命令/中间件注解，
     *    两者合并注册，同名命令视为冲突并抛异常。
     *
     * 注意：命令声明文件内部通过 Command 门面注册，写入的是容器中的共享实例，
     * 因此请经由 `Command::loadCommands()` 调用（或直接使用容器中的同一实例）。
     *
     * @param string $path    命令声明文件目录
     * @param array  $scan    注解扫描目录 [命名空间 => 目录]，留空取 console.commands.scan 配置
     * @param bool   $refresh true = 忽略缓存强制重建（脚手架编译命令使用）
     */
    public function loadCommands(string $path, array $scan = [], bool $refresh = false): self
    {
        $cacheFile = CommandCache::file();

        // 生产环境：优先加载缓存
        if (! $refresh && Helper::isProduction() && CommandCache::exists($cacheFile)) {
            return $this->loadCache($cacheFile);
        }

        // 其他环境：忽略缓存，加载命令声明文件
        $this->loadDeclaration($path);

        // 扫描命令/中间件注解，与声明文件合并（冲突抛异常）
        $this->registerCommands(
            $this->scanner()->scan($scan ?: CommandCache::defaultScan())
        );

        return $this;
    }

    // 加载命令声明文件
    private function loadDeclaration(string $path): void
    {
        $files = glob($path . DIRECTORY_SEPARATOR . '*.php') ?: [];

        // 保证加载顺序稳定
        sort($files);

        // 使用 require（而非 require_once）：编译命令可 flush() 后重复加载
        foreach ($files as $file) {
            require $file;
        }
    }

    /**
     * 批量注册命令声明（由注解扫描产出，含冲突检测）
     *
     * @param array<int, array<string, mixed>> $definitions
     */
    public function registerCommands(array $definitions): self
    {
        foreach ($definitions as $definition) {
            if (! is_array($definition)) {continue;}

            $command = (string) ($definition['command'] ?? '');
            $handler = $definition['handler'] ?? null;

            if ($command === '' || $handler === null) {continue;}

            $entry = $this->register($command, $handler, (string) ($definition['desc'] ?? ''));

            // 注解声明的中间件追加在当前上下文中间件之后（去重）
            $middlewares = array_values(array_filter(
                array_unique(array_merge($entry['middlewares'], (array) ($definition['middlewares'] ?? []))),
                static fn($name) => is_string($name) && $name !== ''
            ));

            if ($middlewares !== [] && $entry instanceof Command) {
                $entry->middleware($middlewares);
            }
        }

        return $this;
    }

    // 获取注解扫描器
    public function scanner(): CommandScanner
    {
        return new CommandScanner();
    }

    /**
     * 加载命令行缓存
     *
     * 缓存里存的已是编译好的命令表，装载就是一次赋值：
     * 不还原 Command 实例、不做冲突检测。匹配阶段只读且全程走 ArrayAccess，
     * 数组形态与实例等价。
     */
    public function loadCache(?string $file = null): self
    {
        $payload = CommandCache::load($file);

        // 兼容旧版：载荷直接就是命令表
        $commands = $payload['commands'] ?? $payload;

        if (! is_array($commands)) {
            return $this;
        }

        foreach ($commands as $name => $command) {
            if (! is_array($command)) {continue;}

            $command['command'] ??= (string) $name;

            $this->commands[(string) $name] = $command + self::COMMAND_DEFAULTS;
        }

        return $this;
    }

    /**
     * 导出命令行缓存文件
     *
     * @return string 缓存文件绝对路径
     */
    public function export(?string $file = null): string
    {
        return CommandCache::dump(['commands' => $this->dumpCommands()], $file);
    }

    /**
     * 导出可缓存的命令表
     *
     * @return array<string, array<string, mixed>>
     */
    public function dumpCommands(): array
    {
        $commands = [];
        foreach ($this->getCommands() as $name => $command) {
            $commands[(string) $name] = $command instanceof Command ? $command->toArray() : $command;
        }

        return $commands;
    }

    /**
     * 获取全部命令（声明注册为 Command 实例，缓存装载为数组）
     *
     * @return array<string, Command|array<string, mixed>>
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    // 命令名规范化：驼峰转冒号小写（MakeController => make:controller）
    private function normalize(string $command): string
    {
        return strtolower((string) preg_replace('/([A-Z])/', ':$1', lcfirst($command)));
    }

    // 处理器可读描述（用于冲突提示）
    private function describeHandler(mixed $handler): string
    {
        if (is_array($handler)) {
            $parts = array_map(
                static fn($part) => is_string($part) ? $part : get_debug_type($part),
                $handler
            );
            return implode('@', $parts);
        }

        return is_string($handler) ? $handler : get_debug_type($handler);
    }

    // 派遣命令
    public function match(Input $argv): Command | array
    {
        // 获取第一个参数，当作命令行
        $cmd = $argv->getCommand();

        // 空命令 / list / help：列出可用命令并以成功状态退出
        if ($cmd === '' || $cmd === 'list' || $cmd === 'help') {
            $this->listCommands();
            exit(0);
        }

        if (! isset($this->commands[$cmd])) {
            $stream = new Output();
            $stream->errorLn("Command not found: {$cmd}");
            $this->listCommands();
            exit(255);
        }

        return $this->commands[$cmd];
    }

    // 输出命令清单
    private function listCommands(): void
    {
        $stream = new Output();

        if (empty($this->commands)) {
            $stream->errorLn('No available commands');
            return;
        }

        $stream->writeln('Available commands:');
        $maxLen = max(array_map(fn($k) => strlen($k), array_keys($this->commands)));

        foreach ($this->commands as $c => $v) {
            $name = str_pad($c, $maxLen + 12, ' ');
            $stream->writeln(sprintf("  php %s %s # %s", self::COMMAND, $name, $v['desc'] ?? ''));
        }
    }

    // 注册命令行分组
    public function group(callable $callback): self
    {
        // 完整克隆实例
        $subResolver = clone $this;

        // 上下文单独克隆切断引用
        $subResolver->context = clone $this->context;

        // 命令组基准快照
        $subResolver->baseSnapshot = clone $subResolver->context;

        // 回调传入克隆实例
        $callback($subResolver);

        // 向上覆盖命令行表
        $this->commands = $subResolver->commands;

        // 组结束：恢复当前上下文，避免前缀/中间件外泄到后续注册
        $this->context = $this->baseSnapshot->getSnapshot();

        return $this;
    }

    // 代理上下文
    // 添加命令前缀（拼接追加）
    public function prefix(string $prefix): self
    {
        $this->context->prefix($prefix);
        return $this;
    }

    // 设置控制器
    public function toolkit(string $toolkit): self
    {
        $this->context->toolkit($toolkit);
        return $this;
    }

    // 覆盖当前的中间件列表
    public function middleware(array | string $middleware): self
    {
        $this->context->middleware($middleware);
        return $this;
    }

    // 从当前列表移除中间件
    public function withoutMiddleware(array | string $middleware): self
    {
        $this->context->withoutMiddleware($middleware);
        return $this;
    }

    // 添加中间件到列表的末尾
    public function appendMiddleware(array | string $middleware): static
    {
        $this->context->appendMiddleware($middleware);
        return $this;
    }

    // 将中间件添加到列表的开头
    public function prependMiddleware(array | string $middleware): self
    {
        $this->context->prependMiddleware($middleware);
        return $this;
    }

}
