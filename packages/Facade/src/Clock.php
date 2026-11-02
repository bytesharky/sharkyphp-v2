<?php
/**
 * SharkyPHP Foundation Library
 *
 * 时钟门面（代理 Psr\Clock\ClockInterface，只读，仅暴露 now()）。
 *
 * @method static \DateTimeImmutable now()  返回当前不可变时间
 *
 * @package SharkyPHP\\Facade
 * @author Sharky
 * @copyright 2026 SharkyPHP
 * @license MIT
 * @version 2.0.0
 * @internal
 */
declare (strict_types = 1);

namespace Sharky\Facade;


use Psr\Clock\ClockInterface;

class Clock extends AbstractFacade
{
    protected static function getFacadeAccessor()
    {
        return ClockInterface::class;
    }
}
