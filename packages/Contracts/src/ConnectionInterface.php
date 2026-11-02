<?php
/**
 * SharkyPHP Contracts Library
 *
 * 连接契约公共基类：Database 与 Redis 的连接接口都继承本接口，
 * 约定两者在「连接管理」上必须保持一致的 3 个方法。
 *
 * @package SharkyPHP\\Contracts
 * @author Sharky
 * @copyright 2026 SharkyPHP
 * @license MIT
 * @version 2.0.0
 * @internal
 */
declare (strict_types = 1);

namespace Sharky\Contracts;

interface ConnectionInterface
{
    /**
     * 连接是否已建立
     */
    public function isConnected(): bool;

    /**
     * 关闭连接（幂等，多次调用安全）
     */
    public function close(): void;

    /**
     * 表/键前缀（如 'sharky_'），空字符串表示无前缀
     */
    public function getPrefix(): string;
}
