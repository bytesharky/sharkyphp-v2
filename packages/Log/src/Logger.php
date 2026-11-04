<?php
/**
 * SharkyPHP Log Library
 *
 * PSR-3 (psr/log) 标准日志实现。
 *
 * 设计要点：
 *  - 继承 Psr\Log\AbstractLogger，8 个标准级别方法（emergency/alert/.../debug）
 *    来自 Psr\Log\LoggerTrait，统一转发到 log()，本类仅实现 log() 即可。
 *  - log() 负责：级别校验、全局级别门槛过滤、消息占位符插值、exception 上下文附加、
 *    构建记录并分发给所有「可处理该级别」的 Handler。
 *  - 提供可插拔 Handler（StreamHandler / ErrorLogHandler / TestHandler 等）。
 *  - 支持频道名（channel），withName() 返回带新频道名的不可变副本。
 *
 * @package SharkyPHP\\Log
 * @author Sharky
 * @copyright 2026 SharkyPHP
 * @license MIT
 * @internal
 */
declare (strict_types = 1);

namespace Sharky\Log;

use Psr\Log\AbstractLogger;
use Sharky\Log\Exceptions\InvalidArgumentException;
use Sharky\Log\Contracts\HandlerInterface;

class Logger extends AbstractLogger
{
    /**
     * @var HandlerInterface[] 已注册处理器
     */
    private array $handlers = [];

    private string $name;

    private string $level;

    /**
     * @var \Closure|null 返回 \DateTimeImmutable 的闭包，便于测试注入时间
     */
    private ?\Closure $timeProvider;

    /**
     * @param HandlerInterface|HandlerInterface[] $handlers 一个或多个处理器
     * @param string                             $name     频道名
     * @param string                             $level    全局最低记录级别（低于此级别的日志被丢弃）
     * @param callable|null                      $timeProvider 返回 \DateTimeImmutable 的闭包
     */
    public function __construct(
        HandlerInterface|array $handlers = [],
        string $name = 'app',
        string $level = LogLevel::DEBUG,
        ?callable $timeProvider = null
    ) {
        $this->handlers = is_array($handlers) ? $handlers : [$handlers];
        $this->name = $name;
        $this->level = $level;
        $this->timeProvider = $timeProvider === null ? null : \Closure::fromCallable($timeProvider);
    }

    // ==================================================================
    // PSR-3 实现
    // ==================================================================

    /**
     * {@inheritDoc}
     *
     * @param mixed               $level
     * @param string|\Stringable  $message
     * @param mixed[]             $context
     *
     * @throws InvalidArgumentException 级别未定义时抛出（遵循 PSR-3）
     */
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $level = (string) $level;

        if (! LogLevel::isValid($level)) {
            throw new InvalidArgumentException(sprintf(
                'Logger level "%s" is not defined, use one of: %s',
                $level,
                implode(', ', LogLevel::levels())
            ));
        }

        // 全局级别门槛：严重度不足直接丢弃
        if (LogLevel::severity($level) < LogLevel::severity($this->level)) {
            return;
        }

        $record = $this->buildRecord($level, $message, $context);

        foreach ($this->handlers as $handler) {
            if ($handler->isHandling($level)) {
                $handler->handle($record);
            }
        }
    }

    // ==================================================================
    // 配置 / 辅助
    // ==================================================================

    /**
     * 追加一个处理器（返回自身，便于链式）
     */
    public function pushHandler(HandlerInterface $handler): self
    {
        $this->handlers[] = $handler;

        return $this;
    }

    /**
     * @return HandlerInterface[]
     */
    public function getHandlers(): array
    {
        return $this->handlers;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * 设置全局最低记录级别
     *
     * @throws InvalidArgumentException 级别未定义时抛出
     */
    public function setLevel(string $level): self
    {
        if (! LogLevel::isValid($level)) {
            throw new InvalidArgumentException(sprintf('Unknown log level: "%s"', $level));
        }

        $this->level = $level;

        return $this;
    }

    public function getLevel(): string
    {
        return $this->level;
    }

    /**
     * 返回带新频道名的不可变副本（共享同一组处理器）
     */
    public function withName(string $name): self
    {
        $clone = clone $this;
        $clone->name = $name;

        return $clone;
    }

    // ==================================================================
    // 内部辅助
    // ==================================================================

    /**
     * 构建一条日志记录
     *
     * @param mixed[] $context
     * @return array<string, mixed>
     */
    private function buildRecord(string $level, string|\Stringable $message, array $context): array
    {
        $message = $this->interpolate($message, $context);

        // PSR-3：context['exception'] 用于产生堆栈跟踪，附加到消息末尾
        if (isset($context['exception']) && $context['exception'] instanceof \Throwable) {
            $message .= "\n" . $context['exception']->__toString();
        }

        $now = $this->timeProvider === null
            ? new \DateTimeImmutable()
            : ($this->timeProvider)();

        return [
            'level'      => $level,
            'level_name' => strtoupper($level),
            'message'    => $message,
            'context'    => $context,
            'channel'    => $this->name,
            'datetime'   => $now,
        ];
    }

    /**
     * 按 PSR-3 规范对消息占位符 {key} 进行插值
     *
     * @param mixed[] $context
     */
    private function interpolate(string|\Stringable $message, array $context): string
    {
        $msg = (string) $message;

        if ($context === [] || ! str_contains($msg, '{')) {
            return $msg;
        }

        $replace = [];
        foreach ($context as $key => $value) {
            if (! is_array($value) && (! is_object($value) || $value instanceof \Stringable)) {
                $replace['{' . $key . '}'] = (string) $value;
            } else {
                $replace['{' . $key . '}'] = (string) json_encode(
                    $value,
                    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
                );
            }
        }

        return strtr($msg, $replace);
    }
}
