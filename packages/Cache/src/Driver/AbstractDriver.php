<?php
/**
 * SharkyPHP Cache Library
 *
 * 缓存驱动公共基类：负责键前缀与序列化
 *
 * @package SharkyPHP\\Cache
 * @author Sharky
 * @copyright 2026 SharkyPHP
 * @license MIT
 * @internal
 */
declare (strict_types = 1);

namespace Sharky\Cache\Driver;

use Sharky\Cache\Contracts\CacheDriverInterface;

abstract class AbstractDriver implements CacheDriverInterface
{
    protected string $prefix;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(protected array $config = [])
    {
        $this->prefix = (string) ($config['prefix'] ?? '');
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * 覆盖键前缀（返回自身，支持链式）
     */
    public function prefix(string $prefix): static
    {
        $this->prefix = $prefix;

        return $this;
    }

    /**
     * 给键加上前缀
     */
    protected function key(string $key): string
    {
        return $this->prefix . $key;
    }

    /**
     * 序列化缓存值
     */
    protected function serialize(mixed $value): string
    {
        return serialize($value);
    }

    /**
     * 反序列化缓存值
     *
     * 禁用 allowed_classes，避免反序列化意外还原对象（对象注入防护）
     */
    protected function unserialize(string $raw): mixed
    {
        return unserialize($raw, ['allowed_classes' => false]);
    }
}
