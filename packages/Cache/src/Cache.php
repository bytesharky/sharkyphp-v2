<?php
/**
 * SharkyPHP Cache Library
 *
 * 缓存管理器（PSR-16 Simple Cache 实现）
 *
 * 所有便捷方法统一走「默认驱动」（配置项 driver，缺省 file）。
 * 本类实现 Psr\SimpleCache\CacheInterface，可作为标准 PSR-16 缓存直接使用。
 *
 * @package SharkyPHP\\Cache
 * @author Sharky
 * @copyright 2026 SharkyPHP
 * @license MIT
 * @internal
 */
declare (strict_types = 1);

namespace Sharky\Cache;

use Psr\SimpleCache\CacheInterface;
use RuntimeException;
use Sharky\Cache\Contracts\CacheDriverInterface;
use Sharky\Cache\Exceptions\InvalidArgumentException;
use Sharky\Cache\Factory\DriverFactory;
use Sharky\Redis\Redis as RedisManager;

final class Cache implements CacheInterface
{
    /**
     * 已实例化的驱动（按驱动名缓存，同一驱动只创建一次）
     *
     * @var array<string, CacheDriverInterface>
     */
    private array $drivers = [];

    /**
     * @var array<string, mixed>
     */
    private array $config = [];

    /**
     * @param array<string, mixed> $config 形如 configs/config.php 中 'cache' 节点的内容
     * @param RedisManager|null    $redis  redis 子包管理器，使用 redis 驱动时必传
     */
    public function __construct(array $config = [], private ?RedisManager $redis = null)
    {
        $this->config = $config;
    }

    // ==================================================================
    // 驱动管理
    // ==================================================================

    /**
     * 取指定驱动实例（同一驱动只创建一次）
     */
    public function driver(string $driver): CacheDriverInterface
    {
        if (array_key_exists($driver, $this->drivers)) {
            return $this->drivers[$driver];
        }

        return $this->drivers[$driver] = DriverFactory::make($driver, $this->config, $this->redis);
    }

    /**
     * 已实例化的驱动名
     *
     * @return string[]
     */
    public function getDriverNames(): array
    {
        return array_keys($this->drivers);
    }

    /**
     * 默认驱动（取自配置项 driver）
     */
    private function defaultDriver(): CacheDriverInterface
    {
        $driver = (string) ($this->config['driver'] ?? 'file');

        if ($driver === '') {
            throw new RuntimeException('Cache driver is not configured');
        }

        return $this->driver($driver);
    }

    // ==================================================================
    // PSR-16 实现（统一走默认驱动）
    // ==================================================================

    /**
     * {@inheritDoc}
     *
     * @param string $key    缓存键（仅允许 A-Z a-z 0-9 _ .，长度 >= 1）
     * @param mixed  $default 未命中时返回的值
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $this->validateKey($key);

        return $this->defaultDriver()->get($key, $default);
    }

    /**
     * {@inheritDoc}
     *
     * @param string                 $key   缓存键
     * @param mixed                  $value 可序列化的值
     * @param null|int|\DateInterval $ttl   相对过期秒数；DateInterval 会折算成秒；
     *                                       null/0/负数 视为永不过期（<=0 时按 PSR-16 删除该键）
     */
    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        $this->validateKey($key);

        $seconds = $this->normalizeTtl($ttl);

        // PSR-16：ttl <= 0 必须立即删除该键
        if ($seconds !== null && $seconds <= 0) {
            return $this->delete($key);
        }

        return $this->defaultDriver()->set($key, $value, $seconds);
    }

    /**
     * {@inheritDoc}
     *
     * 删除不存在的键视为成功（幂等），返回 true。
     */
    public function delete(string $key): bool
    {
        $this->validateKey($key);

        if (! $this->defaultDriver()->exists($key)) {
            return true;
        }

        return $this->defaultDriver()->delete($key);
    }

    /**
     * {@inheritDoc}
     */
    public function clear(): bool
    {
        return $this->defaultDriver()->clear();
    }

    /**
     * {@inheritDoc}
     *
     * @param iterable<string> $keys
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $this->validateKeys($keys);

        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->defaultDriver()->get($key, $default);
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     *
     * @param iterable<string, mixed> $values
     */
    public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool
    {
        $seconds = $this->normalizeTtl($ttl);

        // ttl <= 0 时，PSR-16 要求删除对应键（写入空集合即可）
        if ($seconds !== null && $seconds <= 0) {
            return $this->deleteMultiple(array_keys($this->toArray($values)));
        }

        $ok = true;
        foreach ($values as $key => $value) {
            if (! $this->set($key, $value, $seconds)) {
                $ok = false;
            }
        }

        return $ok;
    }

    /**
     * {@inheritDoc}
     *
     * @param iterable<string> $keys
     */
    public function deleteMultiple(iterable $keys): bool
    {
        $this->validateKeys($keys);

        $ok = true;
        foreach ($keys as $key) {
            // 删除不存在的键不算错误，视为成功
            if ($this->defaultDriver()->exists($key) && ! $this->defaultDriver()->delete($key)) {
                $ok = false;
            }
        }

        return $ok;
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $key): bool
    {
        $this->validateKey($key);

        return $this->defaultDriver()->exists($key);
    }

    // ==================================================================
    // 兼容旧 API：exists 是 has 的别名（保留以便既有代码调用）
    // ==================================================================

    /**
     * 缓存是否存在（过期视为不存在）
     */
    public function exists(string $key): bool
    {
        return $this->has($key);
    }

    // ==================================================================
    // 内部辅助
    // ==================================================================

    /**
     * 将 PSR-16 的 ttl（null|int|\DateInterval）折算为相对秒数
     *
     * @return int|null null 表示永不过期
     */
    private function normalizeTtl(null|int|\DateInterval $ttl): ?int
    {
        if ($ttl === null) {
            return null;
        }

        if (is_int($ttl)) {
            return $ttl;
        }

        // \DateInterval 折算为相对 now 的秒数
        $now = new \DateTimeImmutable();

        return $now->add($ttl)->getTimestamp() - $now->getTimestamp();
    }

    /**
     * iterable 转数组（仅用于 setMultiple 在 ttl<=0 时取键名）
     *
     * @param iterable<string, mixed> $values
     * @return array<string, mixed>
     */
    private function toArray(iterable $values): array
    {
        return is_array($values) ? $values : iterator_to_array($values);
    }

    /**
     * 校验单个键是否符合 PSR-16 规范
     *
     * @throws InvalidArgumentException
     */
    private function validateKey(mixed $key): void
    {
        if (! is_string($key)) {
            throw new InvalidArgumentException(sprintf(
                'Cache key must be a string, %s given',
                get_debug_type($key)
            ));
        }

        if ($key === '') {
            throw new InvalidArgumentException('Cache key cannot be empty');
        }

        if (! preg_match('/^[a-zA-Z0-9_.]+$/', $key)) {
            throw new InvalidArgumentException(sprintf(
                "Cache key '%s' contains invalid characters; only A-Z a-z 0-9 _ . are allowed",
                $key
            ));
        }
    }

    /**
     * 校验键集合（要求可遍历且每个键合法）
     *
     * @param iterable<string> $keys
     * @throws InvalidArgumentException
     */
    private function validateKeys(iterable $keys): void
    {
        foreach ($keys as $key) {
            $this->validateKey($key);
        }
    }
}
