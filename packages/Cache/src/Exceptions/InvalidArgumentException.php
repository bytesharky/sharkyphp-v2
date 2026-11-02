<?php
/**
 * SharkyPHP Cache Library
 *
 * 缓存非法参数异常（实现 PSR-16 Psr\SimpleCache\InvalidArgumentException）
 *
 * @package SharkyPHP\\Cache
 * @author Sharky
 * @copyright 2026 SharkyPHP
 * @license MIT
 * @version 2.0.0
 * @internal
 */
declare (strict_types = 1);

namespace Sharky\Cache\Exceptions;

use Psr\SimpleCache\InvalidArgumentException as PsrInvalidArgumentException;

/**
 * 键非法 / 参数非法时抛出。同时是 PHP 内置 \InvalidArgumentException 的子类，
 * 因此调用方既可按 PSR-16 接口捕获，也可按内置异常类型捕获。
 */
class InvalidArgumentException extends \InvalidArgumentException implements PsrInvalidArgumentException
{
}
