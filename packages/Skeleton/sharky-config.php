<?php
/**
 * SharkyPHP Skeleton Library
 *
 * 脚手架包：提供路由/命令编译与 make 生成器
 * 命令随源码注解自动暴露，生产环境随命令缓存一并加载
 *
 * @package SharkyPHP\\Skeleton
 * @author Sharky
 * @copyright 2026 SharkyPHP
 * @license MIT
 * @internal
 */
declare (strict_types = 1);

return [
    'bindings' => [],
    'listener' => [],

    // 本包提供的命令行（由 ConsoleKernel 启动时注册）
    // 命令注解随源码保留，此处显式声明可保证「生产环境只读缓存」时依然可用
    'commands' => [
        'command:compile' => [
            'handler' => [\Sharky\Skeleton\Toolkits\CommandCompile::class, 'compile'],
            'desc'    => 'Generate compiled command cache',
        ],
        'route:compile'   => [
            'handler' => [\Sharky\Skeleton\Toolkits\RouteCompile::class, 'compile'],
            'desc'    => 'Generate compiled route cache',
        ],
    ],

    // 本包命令的注解扫描目录（随脚手架交付，自动暴露 make:* 等生成器命令）
    // 与项目 configs/console.php 的 commands.scan 合并，无需手工登记命令名
    'console' => [
        'scan' => [
            'Sharky\\Skeleton\\Toolkits' => __DIR__ . '/src/Toolkits',
        ],
    ],
];
