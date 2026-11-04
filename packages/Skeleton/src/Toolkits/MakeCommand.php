<?php
/**
 * SharkyPHP Skeleton Library
 *
 * 创建一个控制台命令
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

#[Command(name: 'MakeCommand', desc: 'Generate Foundation custom command class')]
final class MakeCommand extends GeneratorMake
{
    protected function getStub(): string
    {
        return __DIR__ . '/stubs/command.stub';
    }

    protected function getDefaultNamespace(): string
    {
        return 'App\Console\Toolkits';
    }
}
