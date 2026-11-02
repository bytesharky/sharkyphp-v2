<?php
/**
 * SharkyPHP Log Library
 *
 * 日志处理器接口：决定一条日志记录如何写出。
 *
 * @package SharkyPHP\\Log
 * @author Sharky
 * @copyright 2026 SharkyPHP
 * @license MIT
 * @version 2.0.0
 * @internal
 */
declare (strict_types = 1);

namespace Sharky\Log\Contracts;

/**
 * 日志处理器接口
 */
interface HandlerInterface
{
    /**
     * 处理一条日志记录
     *
     * @param array $record 记录结构：
     *     [
     *       'level'       => string,           // 级别名（小写，如 "info"）
     *       'level_name'  => string,           // 级别名（大写，如 "INFO"）
     *       'message'     => string,           // 已按 context 插值后的消息
     *       'context'     => array,            // 原始上下文（未经插值）
     *       'channel'     => string,           // 频道名
     *       'datetime'    => \DateTimeImmutable,
     *     ]
     * @return void
     */
    public function handle(array $record): void;

    /**
     * 该处理器是否处理指定级别（级别门槛过滤）
     */
    public function isHandling(string $level): bool;
}
