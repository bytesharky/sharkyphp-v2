<?php
/**
 * SharkyPHP Foundation Library
 *
 * Redis 门面
 *
 * @method static \Sharky\Redis\Contracts\ConnectionInterface connect(string $connect)
 * @method static void                                       closeAll()
 * @method static void                                       disconnect(string $connect)
 * @method static string[]                                   getConnectedNames()
 * @method static bool                                       set(string $key, mixed $value, ?int $ttl = null)
 * @method static int                                        delete(string|array $key)
 * @method static bool                                       expire(string $key, int $ttl)
 * @method static bool                                       expireAt(string $key, int $timestamp)
 * @method static bool                                       persist(string $key)
 * @method static bool                                       setnx(string $key, mixed $value)
 * @method static bool                                       mset(array $items)
 * @method static int                                        incr(string $key, int $increment = 1)
 * @method static int                                        decr(string $key, int $decrement = 1)
 * @method static int                                        hSet(string $key, string $field, mixed $value)
 * @method static bool                                       hMSet(string $key, array $fields)
 * @method static int                                        hDel(string $key, string|array $fields)
 * @method static int                                        hIncrBy(string $key, string $field, int $increment = 1)
 * @method static int                                        lPush(string $key, mixed $value)
 * @method static int                                        rPush(string $key, mixed $value)
 * @method static mixed                                      lPop(string $key)
 * @method static mixed                                      rPop(string $key)
 * @method static int                                        sAdd(string $key, mixed $value)
 * @method static int                                        sRem(string $key, mixed $value)
 * @method static int                                        zAdd(string $key, float $score, mixed $value)
 * @method static bool                                       flushDB()
 * @method static bool                                       flushAll()
 * @method static mixed                                      get(string $key)
 * @method static bool                                       exists(string $key)
 * @method static int                                        ttl(string $key)
 * @method static array                                      mget(array $keys)
 * @method static mixed                                      hGet(string $key, string $field)
 * @method static array                                      hMGet(string $key, array $fields)
 * @method static array                                      hGetAll(string $key)
 * @method static int                                        lLen(string $key)
 * @method static array                                      lRange(string $key, int $start = 0, int $end = -1)
 * @method static array                                      sMembers(string $key)
 * @method static bool                                       sIsMember(string $key, mixed $value)
 * @method static array                                      zRange(string $key, int $start = 0, int $end = -1, bool $withScores = false)
 * @method static array                                      zRevRange(string $key, int $start = 0, int $end = -1, bool $withScores = false)
 * @method static array                                      info()
 * @method static mixed                                      multi()
 * @method static array                                      exec()
 * @method static bool                                       discard()
 * @method static \Sharky\Redis\Contracts\ConnectionInterface select(int $db)
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

use Sharky\Redis\Redis as RedisService;

class Redis extends AbstractFacade
{
    protected static function getFacadeAccessor(): string
    {
        return RedisService::class;
    }
}
