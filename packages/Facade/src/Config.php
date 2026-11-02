<?php
/**
 * SharkyPHP Foundation Library
 *
 * 配置管理器门面（代理 Sharky\Foundation\ConfigManager）。
 *
 * @method static \Sharky\Foundation\ConfigManager loadConfigs(string $directory = null)
 * @method static mixed                           get(string $path, mixed $default = null)
 * @method static bool                            has(string $path)
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

use Sharky\Foundation\ConfigManager;

final class Config extends AbstractFacade
{
    protected static function getFacadeAccessor()
    {
        return ConfigManager::class;
    }
}
