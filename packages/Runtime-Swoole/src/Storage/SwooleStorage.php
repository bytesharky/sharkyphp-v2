<?php
/**
 * SharkyPHP Runtime-Swoole Library
 *
 * Swoole 上下文管理器(协程版)
 *
 * @package SharkyPHP\\Runtime-Swoole
 * @author Sharky
 * @copyright 2026 SharkyPHP
 * @license MIT
 * @version 2.0.0
 * @internal
 */
declare (strict_types = 1);

namespace Sharky\Runtime\Storage;
use Sharky\Foundation\Runtime\Context;
use Sharky\Contracts\ContextStoreInterface;
use Swoole\Coroutine;

class SwooleStorage implements ContextStoreInterface
{
    private array $map = [];

    public function current(): Context
    {
        $cid = Coroutine::getCid();
        if (!isset($this->map[$cid])) {
            $this->map[$cid] = new Context();
        }
        return $this->map[$cid];
    }

    public function clear(): void
    {
        $cid = Coroutine::getCid();
        unset($this->map[$cid]);
    }
}
