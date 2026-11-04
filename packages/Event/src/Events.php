<?php
/**
 * SharkyPHP Event Library
 *
 * 框架内置事件名常量
 *
 * 生命周期事件分为：应用(app)、内核(kernel，HTTP 与 Console 两类内核均会触发)、
 * HTTP 请求(request)、异常(exception)、命令行(console)。
 *
 * @package SharkyPHP\\Event
 * @author Sharky
 * @copyright 2026 SharkyPHP
 * @license MIT
 * @internal
 */
declare (strict_types = 1);

namespace Sharky\Event;

final class Events
{
    // 应用
    public const APP_START = 'app.start';

    // 内核（HTTP / Console 两类内核都会触发）
    public const KERNEL_BOOTING      = 'kernel.booting';
    public const KERNEL_BOOTED       = 'kernel.booted';
    public const KERNEL_TERMINATING  = 'kernel.terminating';
    public const KERNEL_WORKER_START = 'kernel.worker_start';
    public const KERNEL_WORKER_STOP  = 'kernel.worker_stop';

    // HTTP 请求生命周期
    public const REQUEST_RECEIVE       = 'request.receive';
    public const REQUEST_ROUTE         = 'request.route';
    public const REQUEST_MIDDLEWARE    = 'request.middleware';
    public const REQUEST_CONTROLLER    = 'request.controller';
    public const REQUEST_RESPONSE      = 'request.response';
    public const REQUEST_RESPONSE_SENT = 'request.response_sent';
    public const REQUEST_ERROR         = 'request.error';
    public const REQUEST_FINISH        = 'request.finish';

    // 异常
    public const EXCEPTION_THROW  = 'exception.throw';
    public const EXCEPTION_RENDER = 'exception.render';

    // 命令行生命周期
    public const CONSOLE_RECEIVE = 'console.receive';
    public const CONSOLE_MATCH   = 'console.match';
    public const CONSOLE_START   = 'console.start';
    public const CONSOLE_FINISH  = 'console.finish';
    public const CONSOLE_ERROR   = 'console.error';
}
