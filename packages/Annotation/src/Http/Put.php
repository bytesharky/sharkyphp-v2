<?php
/**
 * SharkyPHP Foundation Library
 *
 * 路由注解类[Put]
 *
 * @package SharkyPHP\\Foundation
 * @author Sharky
 * @copyright 2026 SharkyPHP
 * @license MIT
 * @internal
 */
declare (strict_types = 1);

namespace Sharky\Annotation\Http;

use Sharky\Annotation\Route;

#[\Attribute(\Attribute::TARGET_METHOD)]
class Put extends Route
{
    public array $method = ['Put'];

    public function __construct(
        public string $path,
        public array $middleware = []
    ) {}
}
