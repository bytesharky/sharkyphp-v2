<?php
/**
 * SharkyPHP Redis Library
 *
 * phpredis 扩展驱动
 *
 * 依赖 ext-redis（phpredis）扩展，所有命令都是懒执行：首次调用命令时才真正建连。
 *
 * @package SharkyPHP\\Redis
 * @author Sharky
 * @copyright 2026 SharkyPHP
 * @license MIT
 * @internal
 */
declare (strict_types = 1);

namespace Sharky\Redis\Driver;

use Redis as PhpRedis;
use Sharky\Redis\Exceptions\RedisException;

class PhpRedisConnection extends AbstractConnection
{
    protected function connect(): void
    {
        $host       = (string) ($this->config['host'] ?? '127.0.0.1');
        $port       = (int) ($this->config['port'] ?? 6379);
        $timeout    = (float) ($this->config['timeout'] ?? 0);
        $persistent = (bool) ($this->config['persistent'] ?? false);
        $password   = $this->config['password'] ?? null;
        $database   = (int) ($this->config['database'] ?? 0);

        $redis = new PhpRedis();

        try {
            if ($persistent) {
                $persistentId = $this->config['persistentId'] ?? null;
                $ok           = $redis->pconnect($host, $port, $timeout, $persistentId);
            } else {
                $ok = $redis->connect($host, $port, $timeout);
            }

            if (! $ok) {
                throw new RedisException(sprintf('Unable to connect to Redis at %s:%d', $host, $port));
            }

            if ($password !== null && $password !== '') {
                $redis->auth($password);
            }

            if ($database > 0) {
                $redis->select($database);
            }
        } catch (RedisException $e) {
            throw $e;
        } catch (\Throwable $th) {
            throw RedisException::transform($th, sprintf('Redis connect failed (%s:%d)', $host, $port));
        }

        $this->handle = $redis;
    }

    protected function disconnect(): void
    {
        if ($this->handle instanceof PhpRedis) {
            // 长连接不主动断开，交给 PHP 进程管理
            if (! ($this->config['persistent'] ?? false)) {
                try {
                    $this->handle->close();
                } catch (\Throwable) {
                    // 忽略关闭异常
                }
            }
        }
    }

    // ------------------------------------------------------------------
    // 服务器
    // ------------------------------------------------------------------

    public function select(int $db): static
    {
        $this->call('select', fn() => $this->handle->select($db));

        return $this;
    }

    public function flushDB(): bool
    {
        return (bool) $this->call('flushDB', fn() => $this->handle->flushDB());
    }

    public function flushAll(): bool
    {
        return (bool) $this->call('flushAll', fn() => $this->handle->flushAll());
    }

    public function info(): array
    {
        return (array) $this->call('info', fn() => $this->handle->info());
    }

    // ------------------------------------------------------------------
    // 键
    // ------------------------------------------------------------------

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        return (bool) $this->call('set', function () use ($key, $value, $ttl) {
            if ($ttl !== null) {
                return $this->handle->setex($this->key($key), $ttl, $value);
            }

            return $this->handle->set($this->key($key), $value);
        });
    }

    public function get(string $key): mixed
    {
        return $this->call('get', function () use ($key) {
            if (! $this->exists($key)) {
                return null;
            }

            return $this->handle->get($this->key($key));
        });
    }

    public function delete(string|array $key): int
    {
        return (int) $this->call('del', function () use ($key) {
            if (is_array($key)) {
                return $this->handle->del($this->keys($key));
            }

            return $this->handle->del($this->key($key));
        });
    }

    public function exists(string $key): bool
    {
        return (bool) $this->call('exists', fn() => $this->handle->exists($this->key($key)));
    }

    public function expire(string $key, int $ttl): bool
    {
        return (bool) $this->call('expire', fn() => $this->handle->expire($this->key($key), $ttl));
    }

    public function expireAt(string $key, int $timestamp): bool
    {
        return (bool) $this->call('expireAt', fn() => $this->handle->expireAt($this->key($key), $timestamp));
    }

    public function ttl(string $key): int
    {
        return (int) $this->call('ttl', fn() => $this->handle->ttl($this->key($key)));
    }

    public function persist(string $key): bool
    {
        return (bool) $this->call('persist', fn() => $this->handle->persist($this->key($key)));
    }

    public function setnx(string $key, mixed $value): bool
    {
        return (bool) $this->call('setnx', fn() => $this->handle->setnx($this->key($key), $value));
    }

    public function mset(array $items): bool
    {
        return (bool) $this->call('mset', function () use ($items) {
            $prefixed = [];
            foreach ($items as $k => $v) {
                $prefixed[$this->key((string) $k)] = $v;
            }

            return $this->handle->mset($prefixed);
        });
    }

    public function mget(array $keys): array
    {
        return (array) $this->call('mget', fn() => $this->handle->mget($this->keys($keys)));
    }

    public function incr(string $key, int $increment = 1): int
    {
        return (int) $this->call('incr', function () use ($key, $increment) {
            if ($increment === 1) {
                return $this->handle->incr($this->key($key));
            }

            return $this->handle->incrBy($this->key($key), $increment);
        });
    }

    public function decr(string $key, int $decrement = 1): int
    {
        return (int) $this->call('decr', function () use ($key, $decrement) {
            if ($decrement === 1) {
                return $this->handle->decr($this->key($key));
            }

            return $this->handle->decrBy($this->key($key), $decrement);
        });
    }

    // ------------------------------------------------------------------
    // 哈希
    // ------------------------------------------------------------------

    public function hSet(string $key, string $field, mixed $value): int
    {
        return (int) $this->call('hSet', fn() => $this->handle->hSet($this->key($key), $field, $value));
    }

    public function hGet(string $key, string $field): mixed
    {
        return $this->call('hGet', fn() => $this->handle->hGet($this->key($key), $field));
    }

    public function hMSet(string $key, array $fields): bool
    {
        return (bool) $this->call('hMSet', fn() => $this->handle->hMSet($this->key($key), $fields));
    }

    public function hMGet(string $key, array $fields): array
    {
        return (array) $this->call('hMGet', fn() => $this->handle->hMGet($this->key($key), $fields));
    }

    public function hGetAll(string $key): array
    {
        return (array) $this->call('hGetAll', fn() => $this->handle->hGetAll($this->key($key)));
    }

    public function hDel(string $key, string|array $fields): int
    {
        return (int) $this->call('hDel', fn() => $this->handle->hDel($this->key($key), ...(array) $fields));
    }

    public function hIncrBy(string $key, string $field, int $increment = 1): int
    {
        return (int) $this->call('hIncrBy', fn() => $this->handle->hIncrBy($this->key($key), $field, $increment));
    }

    // ------------------------------------------------------------------
    // 列表
    // ------------------------------------------------------------------

    public function lPush(string $key, mixed $value): int
    {
        return (int) $this->call('lPush', fn() => $this->handle->lPush($this->key($key), $value));
    }

    public function rPush(string $key, mixed $value): int
    {
        return (int) $this->call('rPush', fn() => $this->handle->rPush($this->key($key), $value));
    }

    public function lPop(string $key): mixed
    {
        return $this->call('lPop', fn() => $this->handle->lPop($this->key($key)));
    }

    public function rPop(string $key): mixed
    {
        return $this->call('rPop', fn() => $this->handle->rPop($this->key($key)));
    }

    public function lLen(string $key): int
    {
        return (int) $this->call('lLen', fn() => $this->handle->lLen($this->key($key)));
    }

    public function lRange(string $key, int $start = 0, int $end = -1): array
    {
        return (array) $this->call('lRange', fn() => $this->handle->lRange($this->key($key), $start, $end));
    }

    // ------------------------------------------------------------------
    // 集合
    // ------------------------------------------------------------------

    public function sAdd(string $key, mixed $value): int
    {
        return (int) $this->call('sAdd', fn() => $this->handle->sAdd($this->key($key), $value));
    }

    public function sRem(string $key, mixed $value): int
    {
        return (int) $this->call('sRem', fn() => $this->handle->sRem($this->key($key), $value));
    }

    public function sMembers(string $key): array
    {
        return (array) $this->call('sMembers', fn() => $this->handle->sMembers($this->key($key)));
    }

    public function sIsMember(string $key, mixed $value): bool
    {
        return (bool) $this->call('sIsMember', fn() => $this->handle->sIsMember($this->key($key), $value));
    }

    // ------------------------------------------------------------------
    // 有序集合
    // ------------------------------------------------------------------

    public function zAdd(string $key, float $score, mixed $value): int
    {
        return (int) $this->call('zAdd', fn() => $this->handle->zAdd($this->key($key), $score, $value));
    }

    public function zRange(string $key, int $start = 0, int $end = -1, bool $withScores = false): array
    {
        return (array) $this->call('zRange', fn() => $this->handle->zRange($this->key($key), $start, $end, $withScores));
    }

    public function zRevRange(string $key, int $start = 0, int $end = -1, bool $withScores = false): array
    {
        return (array) $this->call('zRevRange', fn() => $this->handle->zRevRange($this->key($key), $start, $end, $withScores));
    }

    // ------------------------------------------------------------------
    // 事务
    // ------------------------------------------------------------------

    public function multi(): mixed
    {
        return $this->call('multi', fn() => $this->handle->multi());
    }

    public function exec(): array
    {
        return (array) $this->call('exec', fn() => $this->handle->exec());
    }

    public function discard(): bool
    {
        return (bool) $this->call('discard', fn() => $this->handle->discard());
    }
}
