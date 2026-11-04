<?php
/**
 * SharkyPHP Redis Library
 *
 * Redis 异常
 *
 * @package SharkyPHP\\Redis
 * @author Sharky
 * @copyright 2026 SharkyPHP
 * @license MIT
 * @internal
 */
declare (strict_types = 1);

namespace Sharky\Redis\Exceptions;

use RuntimeException;
use Throwable;

class RedisException extends RuntimeException
{
    /**
     * 把底层驱动抛出的异常统一包装为 RedisException
     */
    public static function transform(Throwable $e, ?string $context = null): self
    {
        $message = $context === null
            ? $e->getMessage()
            : sprintf('%s: %s', $context, $e->getMessage());

        return new self($message, (int) $e->getCode(), $e);
    }
}
