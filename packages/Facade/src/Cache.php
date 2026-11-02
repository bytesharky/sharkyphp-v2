<?php
/**
 * SharkyPHP Foundation Library
 *
 * 缓存门面
 *
 * @method static \Sharky\Cache\Contracts\CacheDriverInterface driver(string $driver)
 * @method static mixed                                   get(string $key, mixed $default = null)
 * @method static iterable                                getMultiple(iterable $keys, mixed $default = null)
 * @method static bool                                    set(string $key, mixed $value, null|int|\DateInterval $ttl = null)
 * @method static bool                                    setMultiple(iterable $values, null|int|\DateInterval $ttl = null)
 * @method static bool                                    delete(string $key)
 * @method static bool                                    deleteMultiple(iterable $keys)
 * @method static bool                                    exists(string $key)
 * @method static bool                                    clear()
 * @method static string[]                                getDriverNames()
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

use Sharky\Cache\Cache as CacheService;

class Cache extends AbstractFacade
{
    protected static function getFacadeAccessor(): string
    {
        return CacheService::class;
    }
}
