<?php
/**
 * SharkyPHP Cache Library
 *
 * 缓存驱动统一契约：文件/Redis 等驱动都必须实现本接口
 *
 * @package SharkyPHP\\Cache
 * @author Sharky
 * @copyright 2026 SharkyPHP
 * @license MIT
 * @internal
 */
declare (strict_types = 1);

namespace Sharky\Cache\Contracts;

interface CacheDriverInterface
{
    /**
     * 写入缓存
     *
     * @param int|null $expire 过期秒数；null 或 0 表示永不过期
     */
    public function set(string $key, mixed $value, ?int $expire = null): bool;

    /**
     * 读取缓存，不存在或已过期时返回 $default
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * 删除缓存
     */
    public function delete(string $key): bool;

    /**
     * 缓存是否存在（过期视为不存在）
     */
    public function exists(string $key): bool;

    /**
     * 清空该驱动下的全部缓存
     */
    public function clear(): bool;
}
