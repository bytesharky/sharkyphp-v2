<?php
/**
 * 上下文存储契约
 *
 * 定义“取当前上下文 / 清空上下文”两个动作，具体上下文对象由运行时实现决定，
 * 契约层不绑定任何实现类。
 *
 * @package SharkyPHP\\Contracts
 * @author Sharky
 * @copyright 2026 SharkyPHP
 * @license MIT
 */
declare (strict_types = 1);

namespace Sharky\Contracts;

interface ContextStoreInterface
{
    // 获取当前上下文（实现方可声明更具体的返回类型）
    public function current(): object;

    // 清空当前上下文
    public function clear(): void;
}
