<?php
/**
 * SharkyPHP Redis Library
 *
 * 驱动公共基类：负责配置解析、懒连接、键前缀与异常包装
 *
 * @package SharkyPHP\\Redis
 * @author Sharky
 * @copyright 2026 SharkyPHP
 * @license MIT
 * @internal
 */
declare (strict_types = 1);

namespace Sharky\Redis\Driver;

use Sharky\Redis\Contracts\ConnectionInterface;
use Sharky\Redis\Exceptions\RedisException;
use Throwable;

abstract class AbstractConnection implements ConnectionInterface
{
    /** @var array<string, mixed> */
    protected array $config;

    /** 底层驱动句柄，null 表示尚未连接（懒连接） */
    protected mixed $handle = null;

    protected string $prefix;

    /**
     * @param array<string, mixed> $config 已合并主机节点的完整配置
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->prefix = (string) ($config['prefix'] ?? '');
    }

    // ------------------------------------------------------------------
    // 由具体驱动实现
    // ------------------------------------------------------------------

    /**
     * 真正建立连接，成功后必须给 $this->handle 赋值
     *
     * @throws RedisException
     */
    abstract protected function connect(): void;

    /**
     * 断开底层连接
     */
    abstract protected function disconnect(): void;

    // ------------------------------------------------------------------
    // 连接管理
    // ------------------------------------------------------------------

    public function isConnected(): bool
    {
        return $this->handle !== null;
    }

    /**
     * @throws RedisException
     */
    protected function ensureConnection(): void
    {
        if ($this->handle === null) {
            $this->connect();
        }
    }

    /**
     * @throws RedisException
     */
    public function client(): mixed
    {
        $this->ensureConnection();

        return $this->handle;
    }

    public function close(): void
    {
        if ($this->handle === null) {
            return;
        }

        try {
            $this->disconnect();
        } catch (Throwable) {
            // 关闭失败不向外抛出
        } finally {
            $this->handle = null;
        }
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    public function prefix(string $prefix): static
    {
        $this->prefix = $prefix;

        return $this;
    }

    public function __destruct()
    {
        $this->close();
    }

    // ------------------------------------------------------------------
    // 工具方法
    // ------------------------------------------------------------------

    /**
     * 给键加上前缀
     */
    protected function key(string $key): string
    {
        return $this->prefix . $key;
    }

    /**
     * 批量加前缀
     *
     * @param array<int, string> $keys
     *
     * @return array<int, string>
     */
    protected function keys(array $keys): array
    {
        return array_map(fn(string $k): string => $this->key($k), $keys);
    }

    /**
     * 统一执行底层命令：保证先建连，并把驱动异常包装为 RedisException
     *
     * @param callable(): mixed $command
     *
     * @throws RedisException
     */
    protected function call(string $name, callable $command): mixed
    {
        try {
            $this->ensureConnection();

            return $command();
        } catch (Throwable $th) {
            throw RedisException::transform($th, sprintf("Redis command '%s' failed", $name));
        }
    }
}
