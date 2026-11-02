<?php
/**
 * SharkyPHP Skeleton Library
 *
 * 编译路由缓存
 *
 * 流程：
 *  1. 强制忽略已有缓存，加载 app/Http/Routes 下的路由声明文件；
 *  2. 扫描控制器中 #[Route]/#[Get]/... 与 #[Middleware] 注解，与声明文件合并
 *     （同 path + 同 method 冲突时抛异常）；
 *  3. 导出 PHP 数组缓存文件到项目 runtime 目录。
 *
 * @package SharkyPHP\\Skeleton
 * @author Sharky
 * @copyright 2026 SharkyPHP
 * @license MIT
 * @version 2.0.0
 * @internal
 */
declare (strict_types = 1);

namespace Sharky\Skeleton\Toolkits;

use Sharky\Annotation\Command;
use Sharky\Console\Context\Output;
use Sharky\Http\Routing\Router;
use Sharky\Facade\Container;
use Sharky\Support\Helper;
use Throwable;

final class RouteCompile
{
    // 路由声明文件目录（相对项目根目录）
    public const ROUTES_PATH = 'app/Http/Routes';

    #[Command(name: 'route:compile', desc: 'Generate compiled route cache')]
    public function compile(Output $output): void
    {
        try {
            // 1+2. 忽略缓存，加载声明文件并扫描注解（冲突直接抛出）
            $router = Container::get(Router::class);
            $router->flush()->loadRoutes(Helper::base_path(self::ROUTES_PATH), refresh: true);

            // 3. 导出缓存文件
            $total = count($router->getRoutes());
            $file  = $router->export();

            $output->successLn(sprintf('Route cache compiled: %d route(s).', $total));
            $output->writeln(sprintf('FilePath:  %s', $file));
        } catch (Throwable $e) {
            $output->errorLn(sprintf('Route compile failed: %s', $e->getMessage()));
            exit(255);
        }
    }
}
