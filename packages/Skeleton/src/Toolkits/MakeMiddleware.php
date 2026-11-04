<?php
/**
 * SharkyPHP Skeleton Library
 *
 * 创建中间件（HTTP / Console 两种形态）
 *
 * 用法：
 *   php sharky make:middleware AuthCheck            # HTTP 中间件
 *   php sharky make:middleware AuthCheck --console  # 命令行中间件
 *   php sharky make:console:middleware AuthCheck    # 同上，语义更明确
 *
 * 两种形态各自实现框架提供的接口：
 *   Sharky\Http\Middleware\MiddlewareInterface     process(ServerRequestInterface, callable)
 *   Sharky\Console\Middleware\MiddlewareInterface  process(Input, callable)
 *
 * @package SharkyPHP\\Skeleton
 * @author Sharky
 * @copyright 2026 SharkyPHP
 * @license MIT
 * @internal
 */
declare (strict_types = 1);

namespace Sharky\Skeleton\Toolkits;

use Sharky\Annotation\Command;
use Sharky\Console\Context\Input;
use Sharky\Console\Context\Output;
use Sharky\Console\Middleware\MiddlewareInterface as ConsoleMiddlewareInterface;
use Sharky\Http\Middleware\MiddlewareInterface as HttpMiddlewareInterface;

#[Command(name: 'MakeMiddleware', desc: 'Generate HTTP middleware class (--console for CLI)')]
class MakeMiddleware extends GeneratorMake
{
    // 中间件形态 => 模板 / 默认命名空间 / 需要实现的接口
    protected const TARGETS = [
        'http'    => [
            'stub'      => 'middleware.stub',
            'namespace' => 'App\Http\Middleware',
            'interface' => HttpMiddlewareInterface::class,
        ],
        'console' => [
            'stub'      => 'console-middleware.stub',
            'namespace' => 'App\Console\Middleware',
            'interface' => ConsoleMiddlewareInterface::class,
        ],
    ];

    // 默认生成 HTTP 中间件
    protected string $target = 'http';

    public function execute(Input $argvInput, Output $output): void
    {
        // --console 是开关，正常情况下写在类名之后
        $flag = $argvInput->getOption('console');

        if ($flag !== null) {
            $this->target = 'console';
        }

        // 容错：`make:middleware --console Foo` 时 Input 会把 Foo 读成选项的值，
        // 这里把它还原成类名，避免莫名其妙的 Missing class name
        if (is_string($flag) && $flag !== '' && $argvInput->getArgument(0) === null) {
            $output->warnLn('Note: --console is a switch, not an option with value; treating the value as the class name.');

            $this->handle($flag, $output, (bool) $argvInput->getOption('force', false));

            return;
        }

        parent::execute($argvInput, $output);
    }

    protected function getStub(): string
    {
        return __DIR__ . '/stubs/' . self::TARGETS[$this->target]['stub'];
    }

    protected function getDefaultNamespace(): string
    {
        return self::TARGETS[$this->target]['namespace'];
    }

    protected function stubVariables(string $namespace, string $className): array
    {
        return parent::stubVariables($namespace, $className) + [
            'uses' => 'use ' . self::TARGETS[$this->target]['interface'] . ';',
        ];
    }

    protected function afterGenerate(string $fqcn, string $filePath, Output $output): void
    {
        $output->writeln(sprintf(
            "Register:  configs/middleware.php -> '%s' => [%s::class]",
            $this->target,
            $fqcn
        ));
    }
}
