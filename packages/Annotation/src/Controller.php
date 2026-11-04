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

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class Controller extends BaseAnnotation
{
    /**
     * @param string      $name   控制器名称（仅作元数据，不影响路径）
     * @param string|null $prefix 路由前缀：加在类下所有方法级路由 path 之前（如 '/boss/v1/master'）
     */
    public function __construct(
        public string $name = '',
        public ?string $prefix = null,
    ) {}
}
