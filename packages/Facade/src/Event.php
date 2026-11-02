<?php
/**
 * SharkyPHP Foundation Library
 *
 * 事件门面
 *
 * @method static object dispatch(string|object $event, array $payload = [])
 * @method static void   listen(string $eventName, callable|string $listener)
 * @method static void   on(string $eventName, callable|string $listener)
 * @method static void   off(string $eventName)
 * @method static void   clear()
 *
 * @package SharkyPHP\\Facade\\Events
 * @author Sharky
 * @copyright 2026 SharkyPHP
 * @license MIT
 * @version 2.0.0
 * @internal
 */
declare (strict_types = 1);

namespace Sharky\Facade;

use Sharky\Event\EventManager;

class Event extends AbstractFacade
{
    protected static function getFacadeAccessor(): string
    {
        return EventManager::class;
    }
}
