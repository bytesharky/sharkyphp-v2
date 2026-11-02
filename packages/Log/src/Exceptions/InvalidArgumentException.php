<?php
/**
 * SharkyPHP Log Library
 *
 * 日志非法参数异常。
 *
 * PSR-3 规定 log() 在收到未定义级别时必须抛出 Psr\Log\InvalidArgumentException。
 * 该类型在 psr/log 中是一个「类」（而非接口），因此本类直接继承它，
 * 调用方既可按 PSR-3 规范捕获，也可按内置 \InvalidArgumentException 捕获。
 *
 * @package SharkyPHP\\Log
 * @author Sharky
 * @copyright 2026 SharkyPHP
 * @license MIT
 * @version 2.0.0
 * @internal
 */
declare (strict_types = 1);

namespace Sharky\Log\Exceptions;

use Psr\Log\InvalidArgumentException as PsrInvalidArgumentException;

class InvalidArgumentException extends PsrInvalidArgumentException
{
}
