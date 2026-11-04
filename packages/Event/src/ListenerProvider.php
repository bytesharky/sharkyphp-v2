<?php
/**
 * SharkyPHP Event Library
 *
 * 监听器提供者
 *
 * 同时支持两种匹配方式：
 *   - 事件名（字符串）：事件对象提供 getName() 时按其返回值匹配（推荐，如 app.start）
 *   - 事件类名：事件对象没有 getName() 时退回 get_class() 匹配（PSR-14 默认行为）
 *
 * 监听器可为 callable、[class, method] 数组，或以类名字符串给出的可调用类
 * （若构造时传入容器，则优先从容器解析，支持构造函数依赖注入）。
 *
 * @package SharkyPHP\\Event
 * @author Sharky
 * @copyright 2026 SharkyPHP
 * @license MIT
 * @internal
 */
declare (strict_types = 1);

namespace Sharky\Event;

use Closure;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use ReflectionMethod;
use RuntimeException;

class ListenerProvider implements ListenerProviderInterface
{
    /**
     * 监听器字典，键为事件名或事件类名
     *
     * @var array<string, array<callable|string>>
     */
    protected array $listeners = [];

    public function __construct(private ?ContainerInterface $container = null)
    {}

    public function getListenersForEvent(object $event): iterable
    {
        $storage = $this->getStorage();

        return array_map([$this, 'resolve'], $storage[$this->resolveKey($event)] ?? []);
    }

    /**
     * 注册监听器（键为事件名或事件类名）
     */
    public function on(string $eventName, callable | string $listener): void
    {
        $storage               = $this->getStorage();
        $storage[$eventName][] = $listener;
        $this->setStorage($storage);
    }

    /**
     * 移除指定事件全部监听器
     */
    public function off(string $eventName): void
    {
        $storage = $this->getStorage();
        unset($storage[$eventName]);
        $this->setStorage($storage);
    }

    /**
     * 清空监听器
     */
    public function clear(): void
    {
        $this->setStorage([]);
    }

    /**
     * 事件对应的监听器字典键
     */
    protected function resolveKey(object $event): string
    {
        return method_exists($event, 'getName') ? $event->getName() : get_class($event);
    }

    /**
     * 将字符串监听器解析为可调用对象
     */
    protected function resolve(mixed $listener): callable
    {
        // Class@method 写法预处理
        if (is_string($listener) && str_contains($listener, '@')) {
            [$controller, $method] = explode('@', $listener, 2);
            $listener              = [$controller, $method];
        }

        if (is_array($listener)) {
            [$controller, $method] = $listener;
            if (! method_exists($controller, $method)) {
                throw new RuntimeException(sprintf('Method "%s" does not exist on class "%s".', $method, $controller));
            }
            $reflection = new ReflectionMethod($controller, $method);
            if (! $reflection->isPublic()) {
                throw new RuntimeException(sprintf('Method "%s" is not public.', $method));
            }
            if (! $reflection->isStatic()) {
                $instance = $this->container->get($controller);
                $listener = [$instance, $method];
            }
        } elseif (! is_callable($listener)) {
            throw new RuntimeException(sprintf('Event listener [%s] is not callable.', $listener));
        }

        return Closure::fromCallable($listener);
    }

    protected function getStorage(): array
    {
        return $this->listeners;
    }

    protected function setStorage(array $data): void
    {
        $this->listeners = $data;
    }
}
