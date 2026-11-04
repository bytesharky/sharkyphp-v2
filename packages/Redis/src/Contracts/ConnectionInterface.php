<?php
/**
 * SharkyPHP Redis Library
 *
 * Redis 连接统一契约：所有驱动都必须实现本接口
 *
 * 与 Database 包保持一致：上层只依赖契约，驱动可自由替换（phpredis / predis 等）。
 * 所有键名都会自动拼接连接上的 prefix。
 *
 * 连接管理相关的 3 个方法（isConnected / close / getPrefix）继承
 * Sharky\Contracts\ConnectionInterface，保证与 Database 连接接口一致。
 *
 * @package SharkyPHP\\Redis
 * @author Sharky
 * @copyright 2026 SharkyPHP
 * @license MIT
 * @internal
 */
declare (strict_types = 1);

namespace Sharky\Redis\Contracts;

use Sharky\Contracts\ConnectionInterface as BaseConnectionInterface;
use Sharky\Redis\Exceptions\RedisException;

interface ConnectionInterface extends BaseConnectionInterface
{
    // ------------------------------------------------------------------
    // 连接管理（isConnected / close / getPrefix 继承 Sharky\Contracts\ConnectionInterface）
    // ------------------------------------------------------------------

    /**
     * 底层驱动句柄（懒连接：首次调用时才真正建连）
     */
    public function client(): mixed;

    /**
     * 覆盖键前缀（返回自身，支持链式）
     */
    public function prefix(string $prefix): static;

    // ------------------------------------------------------------------
    // 服务器
    // ------------------------------------------------------------------

    /**
     * 切换数据库
     *
     * @throws RedisException
     */
    public function select(int $db): static;

    /**
     * @throws RedisException
     */
    public function flushDB(): bool;

    /**
     * @throws RedisException
     */
    public function flushAll(): bool;

    /**
     * @return array<string, mixed>
     *
     * @throws RedisException
     */
    public function info(): array;

    // ------------------------------------------------------------------
    // 键
    // ------------------------------------------------------------------

    /**
     * @param int|null $ttl 过期秒数，传 null 表示不过期
     *
     * @throws RedisException
     */
    public function set(string $key, mixed $value, ?int $ttl = null): bool;

    /**
     * @throws RedisException
     */
    public function get(string $key): mixed;

    /**
     * @param string|array<int, string> $key
     *
     * @throws RedisException
     */
    public function delete(string|array $key): int;

    /**
     * @throws RedisException
     */
    public function exists(string $key): bool;

    /**
     * @throws RedisException
     */
    public function expire(string $key, int $ttl): bool;

    /**
     * @throws RedisException
     */
    public function expireAt(string $key, int $timestamp): bool;

    /**
     * 剩余秒数；-1 未设置过期；-2 键不存在
     *
     * @throws RedisException
     */
    public function ttl(string $key): int;

    /**
     * @throws RedisException
     */
    public function persist(string $key): bool;

    /**
     * @throws RedisException
     */
    public function setnx(string $key, mixed $value): bool;

    /**
     * @param array<string, mixed> $items
     *
     * @throws RedisException
     */
    public function mset(array $items): bool;

    /**
     * @param array<int, string> $keys
     *
     * @return array<int, mixed>
     *
     * @throws RedisException
     */
    public function mget(array $keys): array;

    /**
     * @throws RedisException
     */
    public function incr(string $key, int $increment = 1): int;

    /**
     * @throws RedisException
     */
    public function decr(string $key, int $decrement = 1): int;

    // ------------------------------------------------------------------
    // 哈希
    // ------------------------------------------------------------------

    /**
     * @throws RedisException
     */
    public function hSet(string $key, string $field, mixed $value): int;

    /**
     * @throws RedisException
     */
    public function hGet(string $key, string $field): mixed;

    /**
     * @param array<string, mixed> $fields
     *
     * @throws RedisException
     */
    public function hMSet(string $key, array $fields): bool;

    /**
     * @param array<int, string> $fields
     *
     * @return array<int, mixed>
     *
     * @throws RedisException
     */
    public function hMGet(string $key, array $fields): array;

    /**
     * @return array<string, mixed>
     *
     * @throws RedisException
     */
    public function hGetAll(string $key): array;

    /**
     * @param string|array<int, string> $fields
     *
     * @throws RedisException
     */
    public function hDel(string $key, string|array $fields): int;

    /**
     * @throws RedisException
     */
    public function hIncrBy(string $key, string $field, int $increment = 1): int;

    // ------------------------------------------------------------------
    // 列表
    // ------------------------------------------------------------------

    /**
     * @throws RedisException
     */
    public function lPush(string $key, mixed $value): int;

    /**
     * @throws RedisException
     */
    public function rPush(string $key, mixed $value): int;

    /**
     * @throws RedisException
     */
    public function lPop(string $key): mixed;

    /**
     * @throws RedisException
     */
    public function rPop(string $key): mixed;

    /**
     * @throws RedisException
     */
    public function lLen(string $key): int;

    /**
     * @return array<int, mixed>
     *
     * @throws RedisException
     */
    public function lRange(string $key, int $start = 0, int $end = -1): array;

    // ------------------------------------------------------------------
    // 集合
    // ------------------------------------------------------------------

    /**
     * @throws RedisException
     */
    public function sAdd(string $key, mixed $value): int;

    /**
     * @throws RedisException
     */
    public function sRem(string $key, mixed $value): int;

    /**
     * @return array<int, mixed>
     *
     * @throws RedisException
     */
    public function sMembers(string $key): array;

    /**
     * @throws RedisException
     */
    public function sIsMember(string $key, mixed $value): bool;

    // ------------------------------------------------------------------
    // 有序集合
    // ------------------------------------------------------------------

    /**
     * @throws RedisException
     */
    public function zAdd(string $key, float $score, mixed $value): int;

    /**
     * @return array<int, mixed>
     *
     * @throws RedisException
     */
    public function zRange(string $key, int $start = 0, int $end = -1, bool $withScores = false): array;

    /**
     * @return array<int, mixed>
     *
     * @throws RedisException
     */
    public function zRevRange(string $key, int $start = 0, int $end = -1, bool $withScores = false): array;

    // ------------------------------------------------------------------
    // 事务
    // ------------------------------------------------------------------

    /**
     * @throws RedisException
     */
    public function multi(): mixed;

    /**
     * @return array<int, mixed>
     *
     * @throws RedisException
     */
    public function exec(): array;

    /**
     * @throws RedisException
     */
    public function discard(): bool;
}
