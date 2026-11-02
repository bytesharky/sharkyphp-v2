<?php
/**
 * SharkyPHP Console Library
 *
 * 命令行中间件接口
 *
 * 只约束 process 的方法签名，不提供任何实现；
 * 继承 Sharky\Contracts\MiddlewareInterface，中间件管道据此把它当作内置中间件。
 *
 * @package SharkyPHP\\Console
 * @author Sharky
 * @copyright 2026 SharkyPHP
 * @license MIT
 * @version 2.0.0
 */
declare (strict_types = 1);

namespace Sharky\Console\Middleware;

use Sharky\Console\Context\Input;
use Sharky\Contracts\MiddlewareInterface as Contract;

interface MiddlewareInterface extends Contract
{
    /**
     * 处理命令输入
     *
     * 前置逻辑 -> $next($argvInput) -> 后置逻辑；直接 return 即可中断命令执行。
     *
     * @param Input $argvInput 解析后的命令行输入
     * @param callable $next 下游调用（下一个中间件 / 命令处理器）
     * @return mixed 命令处理器的返回值
     */
    public function process(Input $argvInput, callable $next);
}
