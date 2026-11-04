<?php
/**
 * SharkyPHP Foundation Library
 *
 * 上下文管理器门面（代理当前运行时上下文 Sharky\Foundation\Runtime\Context）。
 *
 * 本门面重写了 getInstance()，返回 ContextStoreInterface::current() 得到的
 * 运行时上下文对象（默认实现为 Sharky\Foundation\Runtime\Context，
 * Swoole 环境下为 Sharky\Runtime\Storage\SwooleStorage 提供的协程上下文）。
 *
 * @method static mixed get(string $key, mixed $default = null)
 * @method static void  set(string $key, mixed $value)
 * @method static void  setRequest(\Psr\Http\Message\ServerRequestInterface $request)
 * @method static \Psr\Http\Message\ServerRequestInterface|null getRequest()
 * @method static void  setInput(\Sharky\Console\Context\Input $argvInput)
 * @method static \Sharky\Console\Context\Input|null           getInput()
 * @method static void  setOutput(\Sharky\Console\Context\Output $output)
 * @method static \Sharky\Console\Context\Output|null          getOutput()
 * @method static void  cancel()
 * @method static void  onCancel(callable $cb)
 * @method static bool  isCancelled()
 *
 * @package SharkyPHP\\Facade
 * @author Sharky
 * @copyright 2026 SharkyPHP
 * @license MIT
 * @internal
 */
declare (strict_types = 1);

namespace Sharky\Facade;

use Sharky\Contracts\ContextStoreInterface;

final class Context extends AbstractFacade
{
    // 获取当前协程上下文
    public static function getInstance()
    {
        $store = Container::get(static::getFacadeAccessor());
        return $store->current();
    }

    // 子类必须实现，返回容器标识字符串
    protected static function getFacadeAccessor()
    {
        return ContextStoreInterface::class;
    }

}
