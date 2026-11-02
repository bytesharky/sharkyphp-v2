<?php
/**
 * SharkyPHP Foundation Library
 *
 * 命令行门面（代理 Sharky\Console\Commanding\Resolver）。
 *
 * @method static \Sharky\Console\Commanding\Command        register(string $command, mixed $handler, string $desc)
 * @method static \Sharky\Console\Commanding\Resolver       registerCommands(array $definitions)
 * @method static \Sharky\Console\Commanding\Resolver       loadCommands(string $path, array $scan = [], bool $refresh = false)
 * @method static \Sharky\Console\Commanding\Resolver       loadCache(string $file = null)
 * @method static \Sharky\Console\Commanding\Resolver       export(string $file = null)
 * @method static array                                     dumpCommands()
 * @method static array                                     getCommands()
 * @method static \Sharky\Console\Commanding\Resolver       flush()
 * @method static \Sharky\Console\Commanding\CommandScanner scanner()
 * @method static \Sharky\Console\Commanding\Command        match(\Sharky\Console\Context\Input $argv)
 * @method static \Sharky\Console\Commanding\Resolver       group(callable $callback)
 * @method static \Sharky\Console\Commanding\Resolver       prefix(string $prefix)
 * @method static \Sharky\Console\Commanding\Resolver       toolkit(string $toolkit)
 * @method static \Sharky\Console\Commanding\Resolver       middleware(array|string $middleware)
 * @method static \Sharky\Console\Commanding\Resolver       withoutMiddleware(array|string $middleware)
 * @method static \Sharky\Console\Commanding\Resolver       appendMiddleware(array|string $middleware)
 * @method static \Sharky\Console\Commanding\Resolver       prependMiddleware(array|string $middleware)
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

use Sharky\Console\Commanding\Resolver;

final class Command extends AbstractFacade
{
    protected static function getFacadeAccessor()
    {
        return Resolver::class;
    }
}
