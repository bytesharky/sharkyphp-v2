<?php
/**
 * SharkyPHP Http Library
 *
 * WEB 中间件洋葱管道
 *
 * @package SharkyPHP\\Http
 * @author Sharky
 * @copyright 2026 SharkyPHP
 * @license MIT
 * @internal
 */
declare (strict_types = 1);

namespace Sharky\Pipeline;

use Sharky\Contracts\MiddlewareInterface as BuiltInMiddleware;

// 中间件管道类
trait CollectTrait
{
    private array $middlewares = [];

    // 覆盖当前的中间件列表
    public function middleware(array | string $middleware): self
    {
        if (! is_array($middleware)) {
            $middleware = [$middleware];
        }
        $this->middlewares = $middleware;
        return $this;
    }

    // 从当前列表移除中间件
    public function withoutMiddleware(array | string $middleware): self
    {
        if (! is_array($middleware)) {
            $middleware = [$middleware];
        }
        $this->middlewares = array_diff($this->middlewares, $middleware);
        $this->middlewares = array_values($this->middlewares);
        return $this;
    }

    // 添加中间件到列表的末尾
    public function appendMiddleware(array | string $middleware): static
    {
        if (! is_array($middleware)) {
            $middleware = [$middleware];
        }
        $this->middlewares = array_merge($this->middlewares, $middleware);
        return $this;
    }

    // 将中间件添加到列表的开头
    public function prependMiddleware(array | string $middleware): self
    {
        if (! is_array($middleware)) {
            $middleware = [$middleware];
        }
        $this->middlewares = array_merge($middleware, $this->middlewares);
        return $this;
    }

    // 返回中间件列表
    public function getMiddleware(): array
    {
        return $this->middlewares;
    }

}
