<?php
/**
 * SharkyPHP Clock Library
 *
 * 冻结时钟
 *
 * @package SharkyPHP\\Clock
 * @author Sharky
 * @copyright 2026 SharkyPHP
 * @license MIT
 * @internal
 */
declare (strict_types = 1);

namespace Sharky\Clock;

use DateMalformedStringException;
use DateTimeImmutable;
use DateTimeZone;
use Psr\Clock\ClockInterface;

final class FrozenClock implements ClockInterface
{
    public function __construct(private DateTimeImmutable $now)
    {}

    public static function fromUTC(): self
    {
        return new self(new DateTimeImmutable('now', new DateTimeZone('UTC')));
    }

    public static function fromSystemTime(): self
    {
        return new self(new DateTimeImmutable('now', new DateTimeZone(date_default_timezone_get())));
    }

    public function setTo(DateTimeImmutable $now): void
    {
        $this->now = $now;
    }

    public function adjustTime(string $modifier): void
    {
        $modifiedTime = @$this->now->modify($modifier);

        // PHP < 8.3 won't throw exceptions on invalid modifiers
        if ($modifiedTime === false) {
            throw new DateMalformedStringException('The given modifier is invalid');
        }

        $this->now = $modifiedTime;
    }

    public function now(): DateTimeImmutable
    {
        return $this->now;
    }
}
