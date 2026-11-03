<?php
/**
 * SharkyPHP Redis Library
 *
 * Redis 连接管理器
 *
 * 用法（与 Database 包完全一致）：
 *   $redis = new Redis(Config::get('redis.default'));
 *   $conn  = $redis->connect();           // ConnectionInterface，默认取 hosts 第一个节点
 *   $conn->set('foo', 'bar', 60);
 *
 * 不做自动主从路由：所有便捷方法统一走「默认连接」，
 * 默认连接 = hosts 配置中的第一个节点（配置里 master 排第一，即默认全走主）。
 * 需要显式访问其它节点时用 connect('节点名')。
 *
 * @package SharkyPHP\\Redis
 * @author Sharky
 * @copyright 2026 SharkyPHP
 * @license MIT
 * @version 2.0.0
 * @internal
 */
declare (strict_types = 1);

namespace Sharky\Redis;

use InvalidArgumentException;
use RuntimeException;
use Sharky\Redis\Contracts\ConnectionInterface;
use Sharky\Redis\Factory\ConnectionFactory;

final class Redis
{
    /**
     * 已建立的连接（按节点名缓存，同一节点只创建一次）
     *
     * @var array<string, ConnectionInterface>
     */
    private array $connects = [];

    /**
     * @var array<string, mixed>
     */
    private array $config = [];

    /**
     * @param array<string, mixed> $config 形如 configs/redis.php 中 'default' 节点的内容
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    // ==================================================================
    // 连接管理
    // ==================================================================

    /**
     * 取指定节点的连接实例（懒连接，同一节点复用同一个实例）
     *
     * @throws InvalidArgumentException 节点未在 hosts 中定义
     */
    public function connect(?string $connect = null): ConnectionInterface
    {
        if ($connect === null) {
            $connect = array_key_first($this->config['hosts'] ?? []);
            if ($connect === null) {
                throw new RuntimeException('Redis hosts configuration is empty, cannot resolve default connection');
            }
        }

        if (array_key_exists($connect, $this->connects)) {
            return $this->connects[$connect];
        }

        // 浅拷贝总配置
        $config = $this->config;

        // 节点是否存在
        if (! isset($config['hosts'][$connect]) || ! is_array($config['hosts'][$connect])) {
            throw new InvalidArgumentException(
                sprintf('Redis connection node [%s] is not defined in hosts config', $connect)
            );
        }

        // 合并主机配置，移除 hosts
        $nodeConfig = $config['hosts'][$connect];
        unset($config['hosts']);
        $config = [ ...$config, ...$nodeConfig];

        // 可选参数补默认值
        $config['persistent'] = $config['persistent'] ?? false;
        $config['driver']     = $config['driver'] ?? 'phpredis';

        $connection               = ConnectionFactory::make($config);
        $this->connects[$connect] = $connection;

        return $connection;
    }

    /**
     * 关闭全部连接
     */
    public function closeAll(): void
    {
        foreach ($this->connects as $connection) {
            $connection->close();
        }

        $this->connects = [];
    }

    /**
     * 关闭并移除指定连接
     */
    public function disconnect(string $connect): void
    {
        if (! array_key_exists($connect, $this->connects)) {
            return;
        }

        $this->connects[$connect]->close();
        unset($this->connects[$connect]);
    }

    /**
     * 已连接节点名
     *
     * @return string[]
     */
    public function getConnectedNames(): array
    {
        return array_keys($this->connects);
    }

    /**
     * 默认连接（取 hosts 第一个节点）
     */
    private function defaultConnect(): ConnectionInterface
    {
        return $this->connect();
    }

    // ==================================================================
    // 写命令（统一走默认连接）
    // ==================================================================

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        return $this->defaultConnect()->set($key, $value, $ttl);
    }

    public function delete(string|array $key): int
    {
        return $this->defaultConnect()->delete($key);
    }

    public function expire(string $key, int $ttl): bool
    {
        return $this->defaultConnect()->expire($key, $ttl);
    }

    public function expireAt(string $key, int $timestamp): bool
    {
        return $this->defaultConnect()->expireAt($key, $timestamp);
    }

    public function persist(string $key): bool
    {
        return $this->defaultConnect()->persist($key);
    }

    public function setnx(string $key, mixed $value): bool
    {
        return $this->defaultConnect()->setnx($key, $value);
    }

    public function mset(array $items): bool
    {
        return $this->defaultConnect()->mset($items);
    }

    public function incr(string $key, int $increment = 1): int
    {
        return $this->defaultConnect()->incr($key, $increment);
    }

    public function decr(string $key, int $decrement = 1): int
    {
        return $this->defaultConnect()->decr($key, $decrement);
    }

    public function hSet(string $key, string $field, mixed $value): int
    {
        return $this->defaultConnect()->hSet($key, $field, $value);
    }

    public function hMSet(string $key, array $fields): bool
    {
        return $this->defaultConnect()->hMSet($key, $fields);
    }

    public function hDel(string $key, string|array $fields): int
    {
        return $this->defaultConnect()->hDel($key, $fields);
    }

    public function hIncrBy(string $key, string $field, int $increment = 1): int
    {
        return $this->defaultConnect()->hIncrBy($key, $field, $increment);
    }

    public function lPush(string $key, mixed $value): int
    {
        return $this->defaultConnect()->lPush($key, $value);
    }

    public function rPush(string $key, mixed $value): int
    {
        return $this->defaultConnect()->rPush($key, $value);
    }

    public function lPop(string $key): mixed
    {
        return $this->defaultConnect()->lPop($key);
    }

    public function rPop(string $key): mixed
    {
        return $this->defaultConnect()->rPop($key);
    }

    public function sAdd(string $key, mixed $value): int
    {
        return $this->defaultConnect()->sAdd($key, $value);
    }

    public function sRem(string $key, mixed $value): int
    {
        return $this->defaultConnect()->sRem($key, $value);
    }

    public function zAdd(string $key, float $score, mixed $value): int
    {
        return $this->defaultConnect()->zAdd($key, $score, $value);
    }

    public function flushDB(): bool
    {
        return $this->defaultConnect()->flushDB();
    }

    public function flushAll(): bool
    {
        return $this->defaultConnect()->flushAll();
    }

    // ==================================================================
    // 读命令（统一走默认连接，不再做主从路由）
    // ==================================================================

    public function get(string $key): mixed
    {
        return $this->defaultConnect()->get($key);
    }

    public function exists(string $key): bool
    {
        return $this->defaultConnect()->exists($key);
    }

    public function ttl(string $key): int
    {
        return $this->defaultConnect()->ttl($key);
    }

    public function mget(array $keys): array
    {
        return $this->defaultConnect()->mget($keys);
    }

    public function hGet(string $key, string $field): mixed
    {
        return $this->defaultConnect()->hGet($key, $field);
    }

    public function hMGet(string $key, array $fields): array
    {
        return $this->defaultConnect()->hMGet($key, $fields);
    }

    public function hGetAll(string $key): array
    {
        return $this->defaultConnect()->hGetAll($key);
    }

    public function lLen(string $key): int
    {
        return $this->defaultConnect()->lLen($key);
    }

    public function lRange(string $key, int $start = 0, int $end = -1): array
    {
        return $this->defaultConnect()->lRange($key, $start, $end);
    }

    public function sMembers(string $key): array
    {
        return $this->defaultConnect()->sMembers($key);
    }

    public function sIsMember(string $key, mixed $value): bool
    {
        return $this->defaultConnect()->sIsMember($key, $value);
    }

    public function zRange(string $key, int $start = 0, int $end = -1, bool $withScores = false): array
    {
        return $this->defaultConnect()->zRange($key, $start, $end, $withScores);
    }

    public function zRevRange(string $key, int $start = 0, int $end = -1, bool $withScores = false): array
    {
        return $this->defaultConnect()->zRevRange($key, $start, $end, $withScores);
    }

    public function info(): array
    {
        return $this->defaultConnect()->info();
    }

    // ==================================================================
    // 事务与服务器（统一走默认连接）
    // ==================================================================

    public function multi(): mixed
    {
        return $this->defaultConnect()->multi();
    }

    public function exec(): array
    {
        return $this->defaultConnect()->exec();
    }

    public function discard(): bool
    {
        return $this->defaultConnect()->discard();
    }

    public function select(int $db): ConnectionInterface
    {
        return $this->defaultConnect()->select($db);
    }
}
