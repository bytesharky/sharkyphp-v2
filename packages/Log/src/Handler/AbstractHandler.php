<?php
/**
 * SharkyPHP Log Library
 *
 * 处理器基类：持有级别门槛并实现 isHandling() 比较逻辑。
 *
 * @package SharkyPHP\\Log
 * @author Sharky
 * @copyright 2026 SharkyPHP
 * @license MIT
 * @version 2.0.0
 * @internal
 */
declare (strict_types = 1);

namespace Sharky\Log\Handler;

use Sharky\Log\Contracts\HandlerInterface;
use Sharky\Log\LogLevel;

/**
 * 处理器抽象基类
 */
abstract class AbstractHandler implements HandlerInterface
{
    /**
     * @param string $level 最低处理级别（低于此级别的记录被忽略）
     */
    public function __construct(protected string $level = LogLevel::DEBUG)
    {
    }

    /**
     * 仅当记录级别严重度 >= 本处理器门槛时返回 true
     */
    public function isHandling(string $level): bool
    {
        return LogLevel::severity($level) >= LogLevel::severity($this->level);
    }

    /**
     * 子类实现具体的写出逻辑
     *
     * @return void
     */
    abstract public function handle(array $record): void;
}
