<?php
/**
 * SharkyPHP Console Library
 *
 * 命令行/中间件注解扫描器
 *
 * 扫描指定目录下的类，读取 PHP8 原生属性注解：
 *  - #[Command(name, desc, execute)] 类级：类整体作为一个命令，默认执行 execute 方法
 *  - #[Command(name, desc)]          方法级：该方法注册为命令，命令名取注解声明
 *  - #[Middleware(name)]             类级：对该类下全部命令生效；方法级：仅对该命令生效
 *
 * 扫描结果统一为命令声明数组，交给 Resolver::registerCommands() 合并注册。
 *
 * @package SharkyPHP\\Console
 * @author Sharky
 * @copyright 2026 SharkyPHP
 * @license MIT
 * @version 2.0.0
 * @internal
 */
declare (strict_types = 1);

namespace Sharky\Console\Commanding;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use Sharky\Annotation\Command as CommandAnnotation;
use Sharky\Annotation\Middleware;
use Throwable;

final class CommandScanner
{
    // 类级注解未声明 execute 时的默认执行方法
    public const DEFAULT_EXECUTE = 'execute';

    /**
     * 扫描目录集合
     *
     * @param array $sources [命名空间 => 目录] 例如 ['App\\Console\\Toolkits' => '/project/app/Console/Toolkits']
     * @return array<int, array{
     *     command:string, handler:array{0:string,1:string},
     *     middlewares:array<int,string>, desc:string
     * }>
     */
    public function scan(array $sources): array
    {
        $definitions = [];

        foreach ($sources as $namespace => $directory) {
            $namespace   = trim((string) $namespace, '\\');
            $directories = is_array($directory) ? $directory : [$directory];

            foreach ($directories as $dir) {
                if (! is_dir((string) $dir)) {continue;}

                foreach ($this->collectFiles((string) $dir) as $file) {
                    $class = $this->resolveClass($file, (string) $dir, $namespace);
                    if ($class === null) {continue;}

                    foreach ($this->scanClass($class) as $definition) {
                        $definitions[] = $definition;
                    }
                }
            }
        }

        return $definitions;
    }

    /**
     * 扫描单个类的命令行/中间件注解
     *
     * @return array<int, array{command:string, handler:array{0:string,1:string}, middlewares:array<int,string>, desc:string}>
     */
    public function scanClass(string $class): array
    {
        try {
            $refClass = new ReflectionClass($class);
        } catch (Throwable) {
            return [];
        }

        // 抽象类 / 接口 / 枚举 / Trait 不参与扫描
        if ($refClass->isAbstract() || $refClass->isInterface() || $refClass->isEnum()) {
            return [];
        }

        // 类级中间件（对类下全部命令生效）
        $classMiddlewares = $this->middlewareNames($refClass);

        $definitions = [];

        // 类级命令注解：方法由注解的 execute 决定
        foreach ($this->commandAnnotations($refClass) as $annotation) {
            $execute = $annotation->execute !== '' ? $annotation->execute : self::DEFAULT_EXECUTE;

            $definitions[] = [
                'command'     => $annotation->name,
                'handler'     => [$refClass->getName(), $execute],
                'middlewares' => $classMiddlewares,
                'desc'        => $annotation->desc,
            ];
        }

        foreach ($refClass->getMethods(ReflectionMethod::IS_PUBLIC) as $refMethod) {

            // 跳过继承来的方法与构造/析构方法
            if ($refMethod->isConstructor() || $refMethod->isDestructor()) {continue;}
            if ($refMethod->getDeclaringClass()->getName() !== $refClass->getName()) {continue;}

            // 方法级命令注解，命令名取注解声明，执行方法即该方法本身
            $annotations = $this->commandAnnotations($refMethod);
            if (empty($annotations)) {continue;}

            // 方法级中间件（类级在外层，方法级在内层）
            $methodMiddlewares = $this->middlewareNames($refMethod);

            foreach ($annotations as $annotation) {
                $definitions[] = [
                    'command'     => $annotation->name,
                    'handler'     => [$refClass->getName(), $refMethod->getName()],
                    'middlewares' => $this->mergeMiddleware($classMiddlewares, $methodMiddlewares),
                    'desc'        => $annotation->desc,
                ];
            }
        }

        return $definitions;
    }

    // 递归收集目录下的 php 文件
    private function collectFiles(string $directory): array
    {
        $files    = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && strtolower($file->getExtension()) === 'php') {
                $files[] = $file->getPathname();
            }
        }

        // 保证扫描顺序稳定
        sort($files);

        return $files;
    }

    // 根据文件相对路径推导类名
    private function resolveClass(string $file, string $directory, string $namespace): ?string
    {
        $root     = rtrim(strtr($directory, '\\', '/'), '/');
        $target   = strtr($file, '\\', '/');
        $relative = str_starts_with($target, $root) ? substr($target, strlen($root)) : $target;
        $relative = preg_replace('/\.php$/', '', ltrim($relative, '/'));
        $segments = array_filter(explode('/', (string) $relative), static fn($s) => $s !== '');

        if (empty($segments)) {return null;}

        $class = ($namespace === '')
            ? implode('\\', $segments)
            : $namespace . '\\' . implode('\\', $segments);

        return class_exists($class) ? $class : null;
    }

    // 读取命令注解实例
    private function commandAnnotations(ReflectionClass | ReflectionMethod $reflection): array
    {
        return $this->attributes($reflection, CommandAnnotation::class);
    }

    // 读取中间件注解名称（类级/方法级）
    private function middlewareNames(ReflectionClass | ReflectionMethod $reflection): array
    {
        $middlewares = [];
        foreach ($this->attributes($reflection, Middleware::class) as $instance) {
            if ($instance->name !== '') {$middlewares[] = $instance->name;}
        }
        return $middlewares;
    }

    // 实例化注解（IS_INSTANCEOF：命中子类）
    private function attributes(ReflectionClass | ReflectionMethod $reflection, string $annotation): array
    {
        $instances = [];
        foreach ($reflection->getAttributes($annotation, ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
            try {
                $instances[] = $attribute->newInstance();
            } catch (Throwable) {continue;}
        }
        return $instances;
    }

    // 合并中间件：类级 -> 方法级（去重）
    private function mergeMiddleware(array ...$groups): array
    {
        $middlewares = [];
        foreach ($groups as $group) {
            foreach ((array) $group as $name) {
                $name = (string) $name;
                if ($name !== '' && ! in_array($name, $middlewares, true)) {
                    $middlewares[] = $name;
                }
            }
        }
        return $middlewares;
    }
}
