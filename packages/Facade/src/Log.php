<?php
/**
 * SharkyPHP Foundation Library
 *
 * 日志门面（代理 Sharky\Log\Logger，遵循 PSR-3 psr/log）。
 *
 * 用法：Log::info('user {id} login', ['id' => 42]);
 * 依赖容器中存在以 Sharky\Log\Logger::class 为标识的实例，
 * 且该实例已配置好 Handler（如 StreamHandler / ErrorLogHandler）。
 *
 * @method static void                              emergency(string|\Stringable $message, array $context = [])
 * @method static void                              alert(string|\Stringable $message, array $context = [])
 * @method static void                              critical(string|\Stringable $message, array $context = [])
 * @method static void                              error(string|\Stringable $message, array $context = [])
 * @method static void                              warning(string|\Stringable $message, array $context = [])
 * @method static void                              notice(string|\Stringable $message, array $context = [])
 * @method static void                              info(string|\Stringable $message, array $context = [])
 * @method static void                              debug(string|\Stringable $message, array $context = [])
 * @method static void                              log($level, string|\Stringable $message, array $context = [])
 * @method static \Sharky\Log\Logger                pushHandler(\Sharky\Log\Handler\HandlerInterface $handler)
 * @method static \Sharky\Log\Handler\HandlerInterface[] getHandlers()
 * @method static string                            getName()
 * @method static \Sharky\Log\Logger                setLevel(string $level)
 * @method static string                            getLevel()
 * @method static \Sharky\Log\Logger                withName(string $name)
 *
 * @package SharkyPHP\\Facade
 * @author Sharky
 * @copyright 2026 SharkyPHP
 * @license MIT
 * @internal
 */
declare (strict_types = 1);

namespace Sharky\Facade;

use Sharky\Log\Logger as LogService;

/**
 * 日志门面
 */
class Log extends AbstractFacade
{
    /**
     * 容器标识：以 Sharky\Log\Logger 的类名为键注册实例
     */
    protected static function getFacadeAccessor(): string
    {
        return LogService::class;
    }
}
