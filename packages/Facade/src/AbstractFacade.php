<?php
/**
 * SharkyPHP Foundation Library
 *
 * 门面工具的基类
 *
 * @package SharkyPHP\\Facade
 * @author Sharky
 * @copyright 2026 SharkyPHP
 * @license MIT
 * @internal
 */
declare (strict_types = 1);

namespace Sharky\Facade;

abstract class AbstractFacade
{

    // 静态方法魔术代理
    public static function __callStatic($method, $args)
    {
        $instance = static::getInstance();
        return $instance->$method(...$args);
    }

    // 获取实例    
    public static function getInstance()
    {
        return Container::get(static::getFacadeAccessor());
    }

    // 子类必须实现，返回容器标识字符串
    abstract protected static function getFacadeAccessor();
}
