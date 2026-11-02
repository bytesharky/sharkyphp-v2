<?php
/**
 * SharkyPHP Console Library
 *
 * 命令冲突异常（同名命令重复注册）
 *
 * @package SharkyPHP\\Console
 * @author Sharky
 * @copyright 2026 SharkyPHP
 * @license MIT
 * @version 2.0.0
 */
declare (strict_types = 1);

namespace Sharky\Console\Exception;

use RuntimeException;
use Throwable;

class CommandConflictException extends RuntimeException
{
    public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    // 构造冲突信息：命令名已被 X 注册，无法再注册给 Y
    public static function make(string $command, string $exists, string $incoming): self
    {
        return new self(sprintf(
            'Command conflict: [%s] already registered to [%s], cannot register to [%s].',
            $command,
            $exists,
            $incoming
        ));
    }
}
