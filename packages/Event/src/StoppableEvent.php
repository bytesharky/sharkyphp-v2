<?php
/**
 * SharkyPHP Database Library
 *
 * 可停止事件
 *
 * @package SharkyPHP\\Database
 * @author Sharky
 * @copyright 2026 SharkyPHP
 * @license MIT
 * @version 2.0.0
 * @internal
 */
declare (strict_types = 1);

namespace Sharky\Event;

use Psr\EventDispatcher\StoppableEventInterface;

trait StoppableEvent
{
    protected bool $propagationStopped = false;

    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }

    public function stopPropagation(): void
    {
        $this->propagationStopped = true;
    }
}
