<?php
/**
 * SharkyPHP Foundation Library
 *
 * 容器门面：只读代理 Psr\Container\ContainerInterface。
 *
 * 仅暴露 PSR-11 的读取接口（get / has），不暴露任何写入能力；
 * 容器实例由框架核心在启动时通过 setInstance() 注入，业务代码只读取。
 *
 * @method static mixed get(string $id)  返回容器中 id 对应的条目；不存在抛 Psr\Container\NotFoundExceptionInterface
 * @method static bool  has(string $id)  判断容器中是否存在 id 对应的条目
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

use Psr\Container\ContainerInterface;
use RuntimeException;

final class Container
{
    // 保持一个实例
    private static ?ContainerInterface $instance = null;

    // 获取实例
    public static function getInstance(): ContainerInterface
    {
        if (! self::$instance) {
            throw new RuntimeException('Container instance has not been set.');
        }
        return self::$instance;
    }

    // 框架核心设置容器
    public static function setInstance(ContainerInterface $instance): void
    {
        if (self::$instance) {
            throw new RuntimeException('Container instance already exists, cannot overwrite.');
        }
        self::$instance = $instance;
    }

    // 静态方法魔术代理
    public static function __callStatic($method, $args)
    {
        return self::getInstance()->$method(...$args);
    }
}
