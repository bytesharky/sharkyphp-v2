<?php
/**
 * SharkyPHP Invoker Library
 *
 * 调度器
 *
 * @package SharkyPHP\\Invoker
 * @author Sharky
 * @copyright 2026 SharkyPHP
 * @license MIT
 * @internal
 */
declare (strict_types = 1);

namespace Sharky\Invoker;

use BadMethodCallException;
use Closure;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;
use RuntimeException;

class Invoker
{
    public function __construct(
        private ContainerInterface $container
    ) {}

    public function invoke(mixed $finalHandler, array $provided): mixed
    {
        $reflection = null;

        // Class@method 路由写法预处理
        if (is_string($finalHandler) && str_contains($finalHandler, '@')) {
            [$controller, $method] = explode('@', $finalHandler, 2);
            $finalHandler          = [$controller, $method];
        }

        try {
            if (is_array($finalHandler)) {
                [$controller, $method] = $finalHandler;
                if (! method_exists($controller, $method)) {
                    throw new BadMethodCallException(sprintf('Method "%s" does not exist on class "%s".', $method, $controller));
                }
                $reflection = new ReflectionMethod($controller, $method);
                if (! $reflection->isPublic()) {
                    throw new BadMethodCallException(sprintf('Method "%s" is not public.', $method));
                }
                if (! $reflection->isStatic()) {
                    $instance     = $this->container->get($controller);
                    $finalHandler = [$instance, $method];
                }
            } elseif ($finalHandler instanceof Closure) {
                $reflection = new ReflectionFunction($finalHandler);
            } elseif (is_string($finalHandler)) {
                if (! function_exists($finalHandler)) {
                    throw new BadMethodCallException(sprintf('Function "%s" does not exist.', $finalHandler));
                }
                $reflection = new ReflectionFunction($finalHandler);
            } else {
                throw new InvalidArgumentException('Unsupported handler type.');
            }

            $params = $reflection->getParameters();
            $args   = $this->parameterResolver($params, $provided);

            return call_user_func_array($finalHandler, $args);
        } catch (ReflectionException $e) {
            throw new BadMethodCallException(sprintf('Handler resolve error: %s', $e->getMessage()), 0, $e);
        }
    }

    private function parameterResolver(array $params, array $provided): array
    {
        $args = [];
        foreach ($params as $param) {
            $name = $param->getName();
            $type = $param->getType();

            if (array_key_exists($name, $provided)) {
                $args[$name] = $provided[$name];

            } elseif ($param->isDefaultValueAvailable()) {
                $args[$name] = $param->getDefaultValue();

            } elseif ($type !== null
                && $type instanceof ReflectionNamedType
                && ! $type->isBuiltin()
            ) {

                $TypeName = $type->getName();

                $args[$name] = $this->container->get($TypeName);

            } elseif ($param->allowsNull()) {
                $args[$name] = null;

            } elseif (! $param->isOptional()) {
                throw new RuntimeException(sprintf("Missing parameter '%s'", $name));
            }
        }

        return $args;
    }
}
