<?php
/**
 * SharkyPHP Cache Library
 *
 * 文件缓存驱动
 *
 * @package SharkyPHP\\Cache
 * @author Sharky
 * @copyright 2026 SharkyPHP
 * @license MIT
 * @internal
 */
declare (strict_types = 1);

namespace Sharky\Cache\Driver;

class File extends AbstractDriver
{
    private string $path;

    /**
     * @param array<string, mixed> $config 需包含 path（缓存目录）
     */
    public function __construct(array $config = [])
    {
        parent::__construct($config);

        $this->path = (string) ($config['path'] ?? (sys_get_temp_dir() . '/sharky_cache'));

        $this->ensureDirectory();
    }

    public function set(string $key, mixed $value, ?int $expire = null): bool
    {
        $payload = [
            'value'      => $value,
            'expires_at' => $expire === null || $expire <= 0 ? 0 : time() + $expire,
        ];

        return file_put_contents(
            $this->filePath($key),
            $this->serialize($payload),
            LOCK_EX
        ) !== false;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $file = $this->filePath($key);

        if (! is_file($file)) {
            return $default;
        }

        $raw = file_get_contents($file);
        if ($raw === false || $raw === '') {
            return $default;
        }

        $payload = $this->unserialize($raw);
        if (! is_array($payload)) {
            return $default;
        }

        // 过期则删除并返回默认值
        $expiresAt = (int) ($payload['expires_at'] ?? 0);
        if ($expiresAt > 0 && time() > $expiresAt) {
            @unlink($file);

            return $default;
        }

        return $payload['value'] ?? $default;
    }

    public function delete(string $key): bool
    {
        $file = $this->filePath($key);

        if (! is_file($file)) {
            return false;
        }

        return @unlink($file);
    }

    public function exists(string $key): bool
    {
        $file = $this->filePath($key);

        if (! is_file($file)) {
            return false;
        }

        $raw = file_get_contents($file);
        if ($raw === false || $raw === '') {
            return false;
        }

        $payload = $this->unserialize($raw);

        if (! is_array($payload)) {
            return false;
        }

        $expiresAt = (int) ($payload['expires_at'] ?? 0);

        // 过期视为不存在
        return ! ($expiresAt > 0 && time() > $expiresAt);
    }

    public function clear(): bool
    {
        $files = glob($this->path . '/*.cache');

        if ($files === false) {
            return false;
        }

        $ok = true;
        foreach ($files as $file) {
            if (is_file($file) && ! @unlink($file)) {
                $ok = false;
            }
        }

        return $ok;
    }

    /**
     * 缓存目录（不存在时自动创建）
     */
    public function getPath(): string
    {
        return $this->path;
    }

    private function ensureDirectory(): void
    {
        if (! is_dir($this->path)) {
            @mkdir($this->path, 0755, true);
        }
    }

    private function filePath(string $key): string
    {
        $this->ensureDirectory();

        return sprintf('%s/%s.cache', $this->path, md5($this->key($key)));
    }
}
