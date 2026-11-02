<?php
/**
 * SharkyPHP Log Library
 *
 * 测试 / 调试处理器：把记录收集到内存数组，便于断言与排错。
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

use Sharky\Log\LogLevel;

/**
 * 内存收集处理器（测试 / 调试用）
 */
class TestHandler extends AbstractHandler
{
    /**
     * @var array<int, array> 原始记录
     */
    private array $records = [];

    /**
     * @var array<int, string> 已格式化文本（便于包含性断言）
     */
    private array $recordsFormatted = [];

    /**
     * {@inheritDoc}
     */
    public function handle(array $record): void
    {
        $this->records[] = $record;
        $this->recordsFormatted[] = $this->format($record);
    }

    /**
     * @return array<int, array>
     */
    public function getRecords(): array
    {
        return $this->records;
    }

    public function hasRecords(): bool
    {
        return $this->records !== [];
    }

    /**
     * 是否存在某条「已格式化文本包含指定子串」的记录
     */
    public function hasRecordThatContains(string $text): bool
    {
        foreach ($this->recordsFormatted as $line) {
            if (str_contains($line, $text)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 清空已收集记录
     */
    public function clear(): void
    {
        $this->records = [];
        $this->recordsFormatted = [];
    }

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
