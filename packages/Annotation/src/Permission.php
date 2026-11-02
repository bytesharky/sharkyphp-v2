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
 * @version 2.0.0
 * @internal
 */
declare (strict_types = 1);

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class Permission extends BaseAnnotation
{
    public function __construct(public string $rule)
    {}
}
