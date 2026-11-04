<?php
/**
 * SharkyPHP Log Library
 *
 * 错误日志处理器：通过 PHP error_log() 输出（常用于 FPM / CLI 的标准错误）。
 *
 * @package SharkyPHP\\Log
 * @author Sharky
 * @copyright 2026 SharkyPHP
 * @license MIT
 * @internal
 */
declare (strict_types = 1);

namespace Sharky\Log\Handler;

use Sharky\Log\LogLevel;

/**
 * error_log() 处理器
 */
class ErrorLogHandler extends AbstractHandler
{
    /**
     * {@inheritDoc}
     */
    public function handle(array $record): void
    {
        error_log($this->format($record));
    }

    /**
     * 紧凑单行格式
     */
    protected function format(array $record): string
    {
        return sprintf(
            '[%s] %s.%s: %s',
            $record['datetime']->format('Y-m-d H:i:s'),
            $record['channel'],
            $record['level_name'],
            $record['message']
        );
    }
}
