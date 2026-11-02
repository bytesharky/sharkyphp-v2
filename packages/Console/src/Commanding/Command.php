<?php
/**
 * SharkyPHP Console Library
 *
 * CLI 命令条目
 *
 * 命令表在声明注册阶段存本类实例（可继续 middleware() 调整）；
 * 缓存装载阶段直接存数组，匹配阶段只读，两者统一走 ArrayAccess 取值。
 *
 * @package SharkyPHP\\Console
 * @author Sharky
 * @copyright 2026 SharkyPHP
 * @license MIT
 * @version 2.0.0
 * @internal
 */
declare (strict_types = 1);

namespace Sharky\Console\Commanding;

use ArrayAccess;
use InvalidArgumentException;
use RuntimeException;
use Sharky\Contracts\ArrayableInterface;
use Sharky\Pipeline\CollectTrait;

final class Command implements ArrayAccess, ArrayableInterface
{
    use CollectTrait;
    public readonly string $command;
    public readonly mixed $finalHandler;
    public readonly string $desc;

    public function __construct(string $command, mixed $finalHandler, array $middlewares, string $desc)
    {
        $this->command      = $command;
        $this->finalHandler = $finalHandler;
        $this->middlewares  = $middlewares;
        $this->desc         = $desc;
    }

    // ---------------- ArrayAccess 实现 ----------------
    public function offsetExists(mixed $offset): bool
    {
        return property_exists($this, $offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        if (! property_exists($this, $offset)) {
            throw new InvalidArgumentException("Undefined Command property key: {$offset}");
        }
        return $this->$offset;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new RuntimeException('Command readonly on match stage. Build by property.');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new RuntimeException('Command readonly.');
    }

    // ---------------- toArray 导出（仅编译命令调用） ----------------
    public function toArray(): array
    {
        return [
            'command'      => $this->command,
            'finalHandler' => $this->finalHandler,
            'middlewares'  => $this->middlewares,
            'desc'         => $this->desc,
        ];
    }

}
