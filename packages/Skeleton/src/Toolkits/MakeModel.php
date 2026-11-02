<?php
/**
 * SharkyPHP Skeleton Library
 *
 * 创建一个数据库模型
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

#[Command(name: 'MakeModel', desc: 'Generate model class')]
final class MakeModel extends GeneratorMake
{
    protected function getStub(): string
    {
        return __DIR__ . '/stubs/model.stub';
    }

    protected function getDefaultNamespace(): string
    {
        return 'App\Http\Models';
    }
}
