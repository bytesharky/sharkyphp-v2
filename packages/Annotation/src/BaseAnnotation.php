<?php
/**
 * SharkyPHP Foundation Library
 *
 * 注解类
 *
 * @package SharkyPHP\\Foundation
 * @author Sharky
 * @copyright 2026 SharkyPHP
 * @license MIT
 * @internal
 */
declare (strict_types = 1);

namespace Sharky\Annotation;
use Sharky\Contracts\ArrayableInterface;

abstract class BaseAnnotation implements ArrayableInterface
{
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
