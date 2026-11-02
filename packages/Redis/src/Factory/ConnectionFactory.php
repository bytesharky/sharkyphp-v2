<?php
/**
 * SharkyPHP Redis Library
 *
 * Redis 连接工厂
 *
 * @package SharkyPHP\\Redis
 * @author Sharky
 * @copyright 2026 SharkyPHP
 * @license MIT
 * @version 2.0.0
 * @internal
 */
declare (strict_types = 1);

namespace Sharky\Redis\Factory;

use InvalidArgumentException;
use Sharky\Redis\Contracts\ConnectionInterface;
use Sharky\Redis\Driver\PhpRedisConnection;

final class ConnectionFactory
{
    /** @var array<string, class-string<ConnectionInterface>> */
    private const DRIVERS = [
        'phpredis' => PhpRedisConnection::class,
    ];

    /** @var array<string, class-string<ConnectionInterface>> */
    private static array $drivers = self::DRIVERS;

    private function __construct()
    {
    }

    /**
     * 根据配置创建连接（懒连接，此处不会真正发起网络请求）
     *
     * @param array<string, mixed> $config 已合并主机节点的配置
     */
    public static function make(array $config): ConnectionInterface
    {
        $driver = strtolower((string) ($config['driver'] ?? 'phpredis'));

        $concrete = self::driver($driver);

        return new $concrete($config);
    }

    /**
     * 注册自定义驱动，可在不修改本类的前提下接入新驱动
     *
     * @param class-string<ConnectionInterface> $concrete
     */
    public static function extend(string $driver, string $concrete): void
    {
        self::$drivers[strtolower($driver)] = $concrete;
    }

    /**
     * 支持的驱动列表
     *
     * @return string[]
     */
    public static function supported(): array
    {
        return array_keys(self::$drivers);
    }

    /**
     * @return class-string<ConnectionInterface>
     */
    private static function driver(string $driver): string
    {
        if (! isset(self::$drivers[$driver])) {
            throw new InvalidArgumentException(sprintf(
                "Unsupported redis driver '%s'. Allowed drivers: %s",
                $driver,
                implode(', ', array_keys(self::$drivers))
            ));
        }

        return self::$drivers[$driver];
    }
}
