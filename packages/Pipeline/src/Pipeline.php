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

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Sharky\Contracts\MiddlewareInterface as BuiltInMiddleware;

// 中间件管道类
final class Pipeline
{
    use CollectTrait;

    public function __construct(
        private ContainerInterface $container
    ) {}

    // 打包中间件链
    public function makeChain(callable $finalHandler): callable
    {
        // 从最后往前组装，洋葱内层先打包
        $pipeline = $finalHandler;

        // 倒序遍历中间件
        foreach (array_reverse($this->middlewares) as $mwClass) {
            $next = $pipeline;
            $inst = $this->container->get($mwClass);

            if ($inst instanceof BuiltInMiddleware) {
                // 内置中间件组件[http、console]
                $pipeline = function (mixed $delivery) use ($inst, $next): mixed {
                    return $inst->process($delivery, $next);
                };

            } elseif ($inst instanceof MiddlewareInterface) {
                // 兼容PSR中间件组件[只支持http]
                $pipeline = function ($request, callable $next) use ($inst) {
                    $handler = new class($next) implements RequestHandlerInterface
                    {
                        private $next;

                        public function handle(ServerRequestInterface $request)
                        {
                            return ($this->next)($request);
                        }
                    };
                    return $inst->process($request, $handler);
                };
            }
        }

        // 只调用一次最外层
        return $pipeline;
    }
}
