<?php
/**
 * SharkyPHP Cache Library
 *
 * Redis 缓存驱动（依赖 sharkyphp/redis 子包）
 *
 * 直接使用 Redis 子包管理器的便捷方法（默认连接），不感知主从细节。
 *
 * @package SharkyPHP\\Cache
 * @author Sharky
 * @copyright 2026 SharkyPHP
 * @license MIT
 * @version 2.0.0
 * @internal
 */
declare (strict_types = 1);

namespace Sharky\Cache\Driver;

use Sharky\Redis\Redis as RedisManager;

class Redis extends AbstractDriver
{
    public function __construct(
        private RedisManager $redis,
        array $config = []
    ) {
        parent::__construct($config);
    }

    public function set(string $key, mixed $value, ?int $expire = null): bool
    {
        return $this->redis->set($this->key($key), $this->serialize($value), $expire);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $raw = $this->redis->get($this->key($key));

        if ($raw === null || $raw === false || $raw === '') {
            return $default;
        }

        $value = $this->unserialize((string) $raw);

        return $value === false && $raw !== serialize(false) ? $default : $value;
    }

    public function delete(string $key): bool
    {
        return $this->redis->delete($this->key($key)) > 0;
    }

    public function exists(string $key): bool
    {
        return $this->redis->exists($this->key($key));
    }

    /**
     * 清空缓存
     *
     * @warning Redis 无法按前缀高效枚举，这里会清空当前数据库（FLUSHDB）。
     *          生产环境建议给缓存分配独立的 database。
     */
    public function clear(): bool
    {
        return $this->redis->flushDB();
    }

    /**
     * 底层 Redis 子包管理器
     */
    public function manager(): RedisManager
    {
        return $this->redis;
    }
}
