<?php
/**
 * SharkyPHP Cache Library
 *
 * 缓存驱动工厂
 *
 * @package SharkyPHP\\Cache
 * @author Sharky
 * @copyright 2026 SharkyPHP
 * @license MIT
 * @internal
 */
declare (strict_types = 1);

namespace Sharky\Cache\Factory;

use InvalidArgumentException;
use Sharky\Cache\Driver\File;
use Sharky\Cache\Driver\Redis;
use Sharky\Cache\Contracts\CacheDriverInterface;
use Sharky\Redis\Redis as RedisManager;

final class DriverFactory
{
    /** @var array<string, class-string<CacheDriverInterface>> */
    private const DRIVERS = [
        'file'  => File::class,
        'redis' => Redis::class,
    ];

    /**
     * 需要注入 Redis 子包管理器的驱动
     *
     * @var array<class-string<CacheDriverInterface>>
     */
    private const REQUIRES_REDIS = [
        Redis::class,
    ];

    /** @var array<string, class-string<CacheDriverInterface>> */
    private static array $drivers = self::DRIVERS;

    private function __construct()
    {
    }

    /**
     * 创建缓存驱动
     *
     * @param array<string, mixed> $config 驱动配置（file 驱动取其中的 path）
     * @param RedisManager|null    $redis  redis 子包管理器，redis 驱动必传
     */
    public static function make(string $driver, array $config = [], ?RedisManager $redis = null): CacheDriverInterface
    {
        $concrete = self::driver(strtolower($driver));

        if (in_array($concrete, self::REQUIRES_REDIS, true)) {
            if (! $redis instanceof RedisManager) {
                throw new InvalidArgumentException(sprintf(
                    "Cache driver '%s' requires a %s instance",
                    $driver,
                    RedisManager::class
                ));
            }

            return new $concrete($redis, $config);
        }

        return new $concrete($config);
    }

    /**
     * 注册自定义驱动
     *
     * @param class-string<CacheDriverInterface> $concrete
     */
    public static function extend(string $driver, string $concrete): void
    {
        self::$drivers[strtolower($driver)] = $concrete;
    }

    /**
     * @return string[]
     */
    public static function supported(): array
    {
        return array_keys(self::$drivers);
    }

    /**
     * @return class-string<CacheDriverInterface>
     */
    private static function driver(string $driver): string
    {
        if (! isset(self::$drivers[$driver])) {
            throw new InvalidArgumentException(sprintf(
                "Unsupported cache driver '%s'. Allowed drivers: %s",
                $driver,
                implode(', ', array_keys(self::$drivers))
            ));
        }

        return self::$drivers[$driver];
    }
}
