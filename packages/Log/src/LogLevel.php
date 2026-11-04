<?php
/**
 * SharkyPHP Log Library
 *
 * PSR-3 日志级别常量与严重度排序。
 * 取值与 Psr\Log\LogLevel 完全一致，并额外提供严重度比较能力。
 *
 * @package SharkyPHP\\Log
 * @author Sharky
 * @copyright 2026 SharkyPHP
 * @license MIT
 * @internal
 */
declare (strict_types = 1);

namespace Sharky\Log;

use Sharky\Log\Exceptions\InvalidArgumentException;

final class LogLevel
{
    public const EMERGENCY = 'emergency';
    public const ALERT     = 'alert';
    public const CRITICAL  = 'critical';
    public const ERROR     = 'error';
    public const WARNING   = 'warning';
    public const NOTICE    = 'notice';
    public const INFO      = 'info';
    public const DEBUG     = 'debug';

    /**
     * 严重度（数值越大越严重）
     *
     * @var array<string, int>
     */
    private const SEVERITY = [
        self::DEBUG     => 0,
        self::INFO      => 1,
        self::NOTICE    => 2,
        self::WARNING   => 3,
        self::ERROR     => 4,
        self::CRITICAL  => 5,
        self::ALERT     => 6,
        self::EMERGENCY => 7,
    ];

    /**
     * 取得某级别的严重度数值
     *
     * @throws InvalidArgumentException 级别未定义时抛出
     */
    public static function severity(string $level): int
    {
        if (! isset(self::SEVERITY[$level])) {
            throw new InvalidArgumentException(sprintf('Unknown log level: "%s"', $level));
        }

        return self::SEVERITY[$level];
    }

    /**
     * 级别是否合法
     */
    public static function isValid(string $level): bool
    {
        return isset(self::SEVERITY[$level]);
    }

    /**
     * 全部合法级别（小写名）
     *
     * @return string[]
     */
    public static function levels(): array
    {
        return array_keys(self::SEVERITY);
    }
}
