<?php
/**
 * SharkyPHP Foundation Library
 *
 * WEB 路由门面（代理 Sharky\Http\Routing\Router）。
 *
 * @method static \Sharky\Http\Routing\Route   addRoute(array|string $method, string $path, mixed $handler)
 * @method static \Sharky\Http\Routing\Router  loadRoutes(string $path)
 * @method static \Sharky\Http\Routing\Router  fallback(mixed $handler)
 * @method static array                         dispatch(\Psr\Http\Message\ServerRequestInterface $request, callable $onMismatch = null)
 * @method static array                         options(\Psr\Http\Message\ServerRequestInterface $request)
 * @method static \Sharky\Http\Routing\Router  group(callable $callback)
 * @method static \Sharky\Http\Routing\Router  prefix(string $prefix)
 * @method static \Sharky\Http\Routing\Router  controller(string $controller)
 * @method static \Sharky\Http\Routing\Router  middleware(array|string $middleware)
 * @method static \Sharky\Http\Routing\Router  withoutMiddleware(array|string $middleware)
 * @method static \Sharky\Http\Routing\Router  appendMiddleware(array|string $middleware)
 * @method static \Sharky\Http\Routing\Router  prependMiddleware(array|string $middleware)
 *
 * @package SharkyPHP\\Facade
 * @author Sharky
 * @copyright 2026 SharkyPHP
 * @license MIT
 * @version 2.0.0
 * @internal
 */
declare (strict_types = 1);

namespace Sharky\Facade;

use Sharky\Http\Routing\Router;

class Route extends AbstractFacade
{
    protected static function getFacadeAccessor()
    {
        return Router::class;
    }
}
