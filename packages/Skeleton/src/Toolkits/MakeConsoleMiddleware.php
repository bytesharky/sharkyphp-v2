<?php
/**
 * SharkyPHP Skeleton Library
 *
 * 创建一个命令行中间件
 *
 * 等价于 php sharky make:middleware <name> --console
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

#[Command(name: 'MakeConsoleMiddleware', desc: 'Generate console middleware class')]
final class MakeConsoleMiddleware extends MakeMiddleware
{
    protected string $target = 'console';
}
