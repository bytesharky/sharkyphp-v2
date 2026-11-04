<?php
/**
 * SharkyPHP Foundation Library
 *
 * 路由注解类
 *
 * @package SharkyPHP\\Foundation
 * @author Sharky
 * @copyright 2026 SharkyPHP
 * @license MIT
 * @internal
 */
declare (strict_types = 1);

namespace Sharky\Annotation;

#[\Attribute(\Attribute::TARGET_METHOD)]
class Route extends BaseAnnotation
{
    public function __construct(
        public string $path,
        public array $method = [],
        public array $middleware = []
    ) {}
}
