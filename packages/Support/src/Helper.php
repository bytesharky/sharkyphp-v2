<?php
/**
 * SharkyPHP Foundation Library
 *
 * 助手函数工具
 *
 * @package SharkyPHP\\Foundation
 * @author Sharky
 * @copyright 2026 SharkyPHP
 * @license MIT
 * @internal
 */
declare (strict_types = 1);

namespace Sharky\Support;

final class Helper
{
    // 项目根目录（由框架核心在启动时注入）
    private static string $basePath = '';

    // 设置项目根目录
    public static function setBasePath(string $path): void
    {
        self::$basePath = rtrim(strtr($path, '\\', '/'), '/');
    }

    // 获取环境变量
    public static function env(string $key, mixed $default = null, callable $callback = null): mixed
    {
        $value = (array_key_exists($key, $_ENV) ? $_ENV[$key] : getenv($key)) ?: $default;
        if (is_callable($callback)) {
            return $callback($value);
        }
        return $value;
    }

    // 是否生产环境（APP_ENV=production）
    public static function isProduction(): bool
    {
        return strtolower(trim((string) static::env('APP_ENV', ''))) === 'production';
    }

    // 加载环境变量
    public static function loadEnv(string $filePath = null): void
    {
        if (! is_readable($filePath)) {
            return;
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key           = trim($key);
            $value         = trim($value);

            $_ENV[$key] = $value;
        }
    }

    // 相对路径转真实路径
    public static function realath(string $base = '', string $path = ''): string
    {
        $path = strtr($path, '\\', '/');
        $base = strtr($base, '\\', '/');

        $isWindows  = str_starts_with(strtolower(PHP_OS), 'win');
        $isAbsolute = false;

        if ($isWindows) {
            // Windows：识别盘符开头 C: C:/
            $isAbsolute = preg_match('#^[a-zA-Z]:($|/)#', $path);
        } else {
            // Linux/Unix：只有 / 开头才算绝对路径，C: 只是普通目录名
            $isAbsolute = str_starts_with($path, '/');
        }

        if ($isAbsolute) {
            $full = $path;
        } else {
            $base = rtrim($base, '/');
            $full = $base . '/' . $path;
        }

        $segments = explode('/', $full);
        $out      = [];

        foreach ($segments as $seg) {
            if ($seg === '' || $seg === '.') {
                continue;
            }
            if ($seg === '..') {
                if (! empty($out)) {
                    array_pop($out);
                }
                continue;
            }
            $out[] = $seg;
        }

        // 拼接前缀
        $firstSeg = $segments[0] ?? '';
        $result   = implode('/', $out);
        if ($isWindows) {
            $result = implode('/', $out);
        } elseif ($firstSeg === '') {
            $result = '/' . implode('/', $out);
        }

        // 最后统一替换为系统原生分隔符
        $result = strtr($result, '/', DIRECTORY_SEPARATOR);

        return rtrim($result, DIRECTORY_SEPARATOR);
    }

    // 项目根目录（启动后由 Application 注入，未注入时退回当前工作目录）
    public static function base_path(string $suffix = '')
    {
        $directory = self::$basePath !== '' ? self::$basePath : strtr((string) getcwd(), '\\', '/');

        return self::realath("{$directory}/{$suffix}");
    }

    // 导出 PHP 文件
    public static function export_php(mixed $value, &$fp, int $indentLevel = 0): void
    {
        $indentChar  = str_repeat(' ', 4);
        $indent      = str_repeat($indentChar, $indentLevel);
        $innerIndent = str_repeat($indentChar, $indentLevel + 1);

        if (is_array($value)) {
            fwrite($fp, '[');
            $keys   = array_keys($value);
            $isList = $keys === array_keys($keys);

            if (! empty($value)) {
                fwrite($fp, "\n");
                $first = true;
                foreach ($value as $k => $v) {
                    if (! $first) {
                        fwrite($fp, ",\n");
                    }
                    $first = false;
                    fwrite($fp, $innerIndent);

                    if (! $isList) {
                        if (is_int($k)) {
                            fwrite($fp, (string) $k);
                        } else {
                            fwrite($fp, var_export($k, true));
                        }
                        fwrite($fp, ' => ');
                    }

                    self::export_php($v, $fp, $indentLevel + 1);
                }
                fwrite($fp, "\n" . $indent);
            }
            fwrite($fp, ']');
        } elseif (is_null($value)) {
            fwrite($fp, 'null');
        } elseif (is_bool($value)) {
            fwrite($fp, $value ? 'true' : 'false');
        } elseif (is_int($value) || is_float($value)) {
            fwrite($fp, (string) $value);
        } elseif (is_string($value)) {
            fwrite($fp, var_export($value, true));
        } else {
            throw new \RuntimeException('不支持导出该类型');
        }
    }

}
