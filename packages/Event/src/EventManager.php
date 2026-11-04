<?php
/**
 * SharkyPHP Event Library
 *
 * 事件管理器
 *
 * 对 PSR-14 事件调度器的友好封装：
 *  - dispatch(string $name, array $payload) 按事件名派发（自动包装为 Event 对象）
 *  - dispatch(object $event)                派发已有事件对象
 *  - listen/on/off/clear                    注册 / 移除 / 清空监听器
 *
 * @package SharkyPHP\\Event
 * @author Sharky
 * @copyright 2026 SharkyPHP
 * @license MIT
 * @internal
 */
declare (strict_types = 1);

namespace Sharky\Event;

use Psr\EventDispatcher\EventDispatcherInterface;

class EventManager
{
    public function __construct(
        private EventDispatcherInterface $dispatcher,
        private ListenerProvider $provider
    ) {}

    /**
     * 派发事件
     *
     * @return object 派发后的事件对象
     */
    public function dispatch(string|object $event, array $payload = []): object
    {
        if (is_string($event)) {
            $event = new Event($event, $payload);
        }

        return $this->dispatcher->dispatch($event);
    }

    /**
     * 注册监听器（按事件名）
     */
    public function listen(string $eventName, callable|string $listener): void
    {
        $this->provider->on($eventName, $listener);
    }

    /**
     * listen 的别名
     */
    public function on(string $eventName, callable|string $listener): void
    {
        $this->listen($eventName, $listener);
    }

    /**
     * 移除指定事件的全部监听器
     */
    public function off(string $eventName): void
    {
        $this->provider->off($eventName);
    }

    /**
     * 清空全部监听器
     */
    public function clear(): void
    {
        $this->provider->clear();
    }
}
