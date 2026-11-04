<?php
/**
 * SharkyPHP Console Library
 *
 * 命令行缓存文件（项目 runtime/commands.php）
 *
 * 生产环境优先加载缓存，避免解析命令声明文件与扫描注解；
 * 缓存内容是编译后的命令表，序列化后 base64 落盘，由 command:compile 生成。
 *
 * @package SharkyPHP\\Console
 * @author Sharky
 * @copyright 2026 SharkyPHP
 * @license MIT
 * @internal
 */
declare (strict_types = 1);

namespace Sharky\Console\Commanding;

use RuntimeException;
use Sharky\Facade\Config;
use Sharky\Facade\Container;
use Sharky\Support\Helper;
use Throwable;

final class CommandCache
{
    // 缓存文件名
    public const FILE_NAME = 'commands.php';

    // 默认注解扫描：命名空间 => 相对项目目录
    public const SCAN_NAMESPACE = 'App\\Console\\Toolkits';
    public const SCAN_DIRECTORY = 'app/Console/Toolkits';

    /**
     * 缓存文件绝对路径
     *
     * 优先读取 console.commands.cache 配置，缺省落在项目 runtime 目录下。
     */
    public static function file(?string $file = null): string
    {
        if ($file === null) {
            $configured = Config::get('console.commands.cache');
            $file       = is_string($configured) ? $configured : '';
        }

        if ($file !== '') {
            return Helper::realath($file);
        }

        return Helper::realath(self::directory() . '/' . self::FILE_NAME);
    }

    // 项目 runtime 目录
    public static function directory(): string
    {
        return Helper::realath(self::projectDirectory() . '/runtime');
    }

    // 项目根目录（容器门面）
    public static function projectDirectory(): string
    {
        try {
            $directory = (string) Container::get('project.directory');
        } catch (Throwable) {
            $directory = '';
        }

        if ($directory === '') {
            throw new RuntimeException('Project directory is missing, container binding [project.directory] is not available.');
        }

        return $directory;
    }

    // 缓存文件是否存在
    public static function exists(?string $file = null): bool
    {
        return is_file(self::file($file));
    }

    /**
     * 读取缓存内容
     *
     * 文件返回 `base64(serialize([...]))` 字符串，这里解码成命令表数组。
     *
     * @return array<string, mixed>
     */
    public static function load(?string $file = null): array
    {
        $file = self::file($file);

        if (! is_file($file)) {
            return [];
        }

        $data = require $file;

        // 字符串载荷，解码后才是数据（损坏时 unserialize 会告警，这里统一转成可读异常）
        if (is_string($data)) {
            $raw = base64_decode($data, true);

            try {
                $data = $raw === false ? false : unserialize($raw, ['allowed_classes' => false]);
            } catch (Throwable) {
                $data = false;
            }
        }

        if (! is_array($data)) {
            throw new RuntimeException(sprintf(
                'Command cache is corrupted: %s. Please run "php sharky command:compile" to rebuild it.',
                $file
            ));
        }

        return $data;
    }

    // 删除缓存文件
    public static function flush(?string $file = null): bool
    {
        $file = self::file($file);

        return is_file($file) ? @unlink($file) : false;
    }

    /**
     * 导出命令行缓存（原子写入）
     *
     * 内容为编译后的命令表 `['commands' => [name => 条目]]`，序列化后 base64
     * 落成 PHP 文件：`require` 拿到字符串 → `unserialize` 还原数组，比逐字面量
     * 编译数组快一倍。装载时直接赋值给 Resolver，不需要还原 Command 实例。
     *
     * @param array<string, mixed> $payload 传 `['commands' => ...]`
     * @return string 缓存文件绝对路径
     */
    public static function dump(array $payload, ?string $file = null): string
    {
        $file = self::file($file);
        $dir  = dirname($file);

        if (! is_dir($dir) && ! @mkdir($dir, 0755, true) && ! is_dir($dir)) {
            throw new RuntimeException(sprintf('Command cache directory is not writable: %s', $dir));
        }

        self::validate($payload);

        $body = base64_encode(serialize($payload));

        // 先写临时文件，写完再替换，避免生成过程中被读到半截内容
        $tempFile = sprintf('%s.%s.tmp', $file, bin2hex(random_bytes(4)));
        $fp       = @fopen($tempFile, 'wb');

        if ($fp === false) {
            throw new RuntimeException(sprintf('Unable to write command cache file: %s', $file));
        }

        $header = "<?php\n"
            . "/**\n"
            . " * SharkyPHP 命令行缓存文件\n"
            . " *\n"
            . " * 该文件由 command:compile 命令生成，请勿手动修改；\n"
            . " * 内容为编译后的命令表（base64 + serialize），仅供 Resolver 装载使用。\n"
            . " */\n\n"
            . "declare (strict_types = 1);\n\n"
            . "return '";

        fwrite($fp, $header);
        fwrite($fp, $body);
        fwrite($fp, "';\n");
        fclose($fp);

        // Windows 下 rename 不允许覆盖已存在文件
        if (is_file($file)) {@unlink($file);}

        if (! @rename($tempFile, $file)) {
            @unlink($tempFile);
            throw new RuntimeException(sprintf('Unable to replace command cache file: %s', $file));
        }

        return $file;
    }

    /**
     * 命令注解扫描目录 [命名空间 => 目录]
     *
     * 合并两个来源（同命名空间合并目录列表，重复目录去重）：
     *  - 项目配置 console.commands.scan；
     *  - 子包 sharky-config.php 的 console.scan（包内命令自动暴露）。
     *
     * @return array<string, array<int, string>>
     */
    public static function defaultScan(): array
    {
        $scan = [];

        $configured = Config::get('console.commands.scan', []);

        if (is_array($configured)) {
            foreach ($configured as $namespace => $directories) {
                foreach ((array) $directories as $directory) {
                    self::pushScan($scan, (string) $namespace, (string) $directory);
                }
            }
        }

        foreach (self::packageScan() as $namespace => $directories) {
            foreach ($directories as $directory) {
                self::pushScan($scan, $namespace, $directory);
            }
        }

        return $scan !== []
            ? $scan
            : [self::SCAN_NAMESPACE => [Helper::realath(self::projectDirectory() . '/' . self::SCAN_DIRECTORY)]];
    }

    /**
     * 子包声明的扫描目录 [命名空间 => [目录, ...]]
     *
     * @return array<string, array<int, string>>
     */
    public static function packageScan(): array
    {
        try {
            $scans = Container::get('foundation.console.scans');
        } catch (Throwable) {
            return [];
        }

        $result = [];

        foreach ((array) $scans as $namespace => $directories) {
            $namespace = trim((string) $namespace, '\\');

            if ($namespace === '') {continue;}

            foreach ((array) $directories as $directory) {
                if (! is_string($directory) || $directory === '' || ! is_dir($directory)) {continue;}
                $result[$namespace][] = Helper::realath($directory);
            }
        }

        return $result;
    }

    // 追加扫描目录（命名空间分组 + 去重）
    private static function pushScan(array &$scan, string $namespace, string $directory): void
    {
        $namespace = trim($namespace, '\\');
        $directory = Helper::realath($directory);

        if ($namespace === '' || $directory === '' || ! is_dir($directory)) {return;}

        $directories = $scan[$namespace] ?? [];

        if (! in_array($directory, $directories, true)) {
            $directories[] = $directory;
        }

        $scan[$namespace] = $directories;
    }

    /**
     * 校验命令是否可被缓存导出
     *
     * 闭包/对象实例处理器无法写入 PHP 缓存文件，直接给出可读的错误。
     *
     * @param array<string, mixed> $payload
     */
    private static function validate(array $payload): void
    {
        $commands = $payload['commands'] ?? [];

        if (! is_array($commands)) {
            throw new RuntimeException('Command cache payload must contain a [commands] array.');
        }

        foreach ($commands as $name => $command) {
            $name = (string) $name;

            if (! is_array($command)) {
                throw new RuntimeException(sprintf(
                    'Command [%s] cannot be cached, entry must be an array, [%s] given.',
                    $name,
                    get_debug_type($command)
                ));
            }

            $handler = $command['finalHandler'] ?? null;

            if (! self::isExportable($handler)) {
                throw new RuntimeException(sprintf(
                    'Command [%s] cannot be cached, handler must be a string or [class, method] array, [%s] given.',
                    $name,
                    get_debug_type($handler)
                ));
            }

            foreach ((array) ($command['middlewares'] ?? []) as $middleware) {
                if (! is_string($middleware)) {
                    throw new RuntimeException(sprintf(
                        'Command [%s] cannot be cached, middleware must be a string, [%s] given.',
                        $name,
                        get_debug_type($middleware)
                    ));
                }
            }
        }
    }

    // 处理器是否可以导出（字符串或 [类, 方法]）
    private static function isExportable(mixed $handler): bool
    {
        if (is_string($handler)) {
            return true;
        }

        return is_array($handler)
            && array_is_list($handler)
            && count($handler) === 2
            && is_string($handler[0])
            && is_string($handler[1]);
    }
}
