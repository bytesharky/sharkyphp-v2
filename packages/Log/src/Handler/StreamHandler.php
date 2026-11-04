<?php
/**
 * SharkyPHP Log Library
 *
 * 流处理器：将日志记录写入任意流（文件、php://stdout、php://stderr 等）。
 * 既接受已打开的流资源，也接受文件路径（惰性打开、追加写入）。
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
 * 流 / 文件处理器
 */
class StreamHandler extends AbstractHandler
{
    /**
     * @var resource|null 已打开的流资源（传入资源时）
     */
    private $stream = null;

    /**
     * @var string|null 文件路径（传入字符串时），用于惰性打开与关闭
     */
    private ?string $url = null;

    /**
     * 行格式占位符：{datetime} {channel} {level} {level_name} {message} {context}
     */
    private string $format;

    /**
     * @param string|resource $stream 文件路径或已打开的流资源
     *                                （resource 在 PHP 中不可作为联合类型，故参数采用 mixed）
     * @param string          $level  最低处理级别
     * @param string          $format 行格式
     */
    public function __construct(mixed $stream, string $level = LogLevel::DEBUG, string $format = '[{datetime}] {channel}.{level_name}: {message} {context}')
    {
        parent::__construct($level);
        $this->format = $format;

        if (is_resource($stream)) {
            $this->stream = $stream;
        } else {
            $this->url = $stream;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function handle(array $record): void
    {
        $this->write($this->format($record) . PHP_EOL);
    }

    /**
     * 把记录格式化为单行文本
     */
    protected function format(array $record): string
    {
        $ctxJson = $this->contextToJson($record['context'] ?? []);

        $replace = [
            '{datetime}'   => $record['datetime']->format('Y-m-d H:i:s'),
            '{channel}'    => $record['channel'],
            '{level}'      => $record['level'],
            '{level_name}' => $record['level_name'],
            '{message}'    => $record['message'],
            '{context}'    => $ctxJson,
        ];

        return rtrim(strtr($this->format, $replace), ' ');
    }

    /**
     * 上下文序列化为 JSON（供 {context} 占位符使用）
     *
     * @param array<string, mixed> $context
     */
    private function contextToJson(array $context): string
    {
        if ($context === []) {
            return '';
        }

        return (string) json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * 写出（文件场合惰性打开）
     */
    private function write(string $line): void
    {
        if ($this->stream === null) {
            $this->stream = @fopen($this->url, 'a');
            if ($this->stream === false) {
                throw new \RuntimeException(sprintf('Unable to open log stream: %s', $this->url));
            }
        }

        fwrite($this->stream, $line);
    }

    /**
     * 关闭由本处理器打开的文件流（传入的资源不在此关闭，归调用方所有）
     */
    public function close(): void
    {
        if ($this->url !== null && is_resource($this->stream)) {
            fclose($this->stream);
            $this->stream = null;
        }
    }

    public function __destruct()
    {
        $this->close();
    }
}
