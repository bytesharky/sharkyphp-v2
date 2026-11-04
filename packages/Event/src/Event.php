<?php
/**
 * SharkyPHP Event Library
 *
 * 事件对象
 *
 * 携带事件名（字符串）与负载数据，并支持停止传播（StoppableEvent）。
 * 监听器优先按事件名匹配，因此同一事件名可由任意事件类承载。
 *
 * @package SharkyPHP\\Event
 * @author Sharky
 * @copyright 2026 SharkyPHP
 * @license MIT
 * @internal
 */
declare (strict_types = 1);

namespace Sharky\Event;

use Psr\EventDispatcher\StoppableEventInterface;

class Event implements StoppableEventInterface
{
    use StoppableEvent;

    public function __construct(
        protected string $name,
        protected array $payload = []
    ) {}

    /**
     * 事件名，监听器据此匹配
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * 全部负载
     *
     * @return array<string, mixed>
     */
    public function getPayload(): array
    {
        return $this->payload;
    }

    /**
     * 读取单个负载项
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->payload[$key] ?? $default;
    }

    /**
     * 写入单个负载项（支持链式）
     */
    public function set(string $key, mixed $value): static
    {
        $this->payload[$key] = $value;

        return $this;
    }
}
