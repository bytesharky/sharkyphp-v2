<?php
/**
 * SharkyPHP Foundation Library
 *
 * 会话门面
 *
 * @method static \Sharky\Session\Contracts\SessionDriverInterface driver(string $driver)
 * @method static string[]                               getDriverNames()
 * @method static void                                     start()
 * @method static bool                                     isStarted()
 * @method static string                                   getId()
 * @method static string                                   regenerate(bool $deleteOld = true)
 * @method static bool                                     save()
 * @method static void                                     destroy()
 * @method static int                                      gc()
 * @method static void                                     set(string $key, mixed $value)
 * @method static mixed                                    get(string $key, mixed $default = null)
 * @method static bool                                     has(string $key)
 * @method static void                                     delete(string $key)
 * @method static array<string, mixed>                     all()
 * @method static void                                     clear()
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

use Sharky\Session\Session as SessionService;

class Session extends AbstractFacade
{
    protected static function getFacadeAccessor(): string
    {
        return SessionService::class;
    }
}
