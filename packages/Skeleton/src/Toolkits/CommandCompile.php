<?php
/**
 * SharkyPHP Skeleton Library
 *
 * 编译命令行缓存
 *
 * 流程：
 *  1. 强制忽略已有缓存，加载 app/Console/Definitions 下的命令声明文件；
 *  2. 扫描工具包中 #[Command] 与 #[Middleware] 注解，与声明文件合并
 *     （同名命令冲突时抛异常）；
 *  3. 导出缓存文件到项目 runtime 目录。
 *
 * @package SharkyPHP\\Skeleton
 * @author Sharky
 * @copyright 2026 SharkyPHP
 * @license MIT
 * @internal
 */
declare (strict_types = 1);

namespace Sharky\Skeleton\Toolkits;

use Sharky\Annotation\Command;
use Sharky\Console\Commanding\Resolver;
use Sharky\Console\Context\Output;
use Sharky\Facade\Container;
use Sharky\Support\Helper;
use Throwable;

final class CommandCompile
{
    // 命令声明文件目录（相对项目根目录）
    public const DEFINITIONS_PATH = 'app/Console/Definitions';

    #[Command(name: 'command:compile', desc: 'Generate compiled command cache')]
    public function compile(Output $output): void
    {
        try {
            // 1+2. 忽略缓存，加载声明文件并扫描注解（冲突直接抛出）
            $resolver = Container::get(Resolver::class);
            $resolver->flush()->loadCommands(Helper::base_path(self::DEFINITIONS_PATH), refresh: true);

            // 3. 导出缓存文件
            $total = count($resolver->getCommands());
            $file  = $resolver->export();

            $output->successLn(sprintf('Command cache compiled: %d command(s).', $total));
            $output->writeln(sprintf('FilePath:  %s', $file));
        } catch (Throwable $e) {
            $output->errorLn(sprintf('Command compile failed: %s', $e->getMessage()));
            exit(255);
        }
    }
}
