<?php
/**
 * SharkyPHP Clock Library
 *
 * 系统时钟
 *
 * @package SharkyPHP\\Clock
 * @author Sharky
 * @copyright 2026 SharkyPHP
 * @license MIT
 * @internal
 */
declare (strict_types = 1);

namespace Sharky\Clock;

use DateTimeImmutable;
use DateTimeZone;
use Psr\Clock\ClockInterface;

final class SystemClock implements ClockInterface
{
    public function __construct(private readonly DateTimeZone $timezone)
    {
    }

    public static function fromUTC(): self
    {
        return new self(new DateTimeZone('UTC'));
    }

    public static function fromSystemTimezone(): self
    {
        return new self(new DateTimeZone(date_default_timezone_get()));
    }

    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', $this->timezone);
    }
}
