<?php
/**
 * SharkyPHP Skeleton Library
 *
 * 创建一个控制器
 *
 * @package SharkyPHP\\Skeleton
 * @author Sharky
 * @copyright 2026 SharkyPHP
 * @license MIT
 * @version 2.0.0
 * @internal
 */
declare (strict_types = 1);

namespace Sharky\Skeleton\Toolkits;

use Sharky\Annotation\Command;

#[Command(name: 'MakeController', desc: 'Generate controller class')]
final class MakeController extends GeneratorMake
{
    protected function getStub(): string
    {
        return __DIR__ . '/stubs/controller.stub';
    }

    protected function getDefaultNamespace(): string
    {
        return 'App\Http\Controllers';
    }
}
