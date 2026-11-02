<?php
/**
 * SharkyPHP Http Library
 *
 * WEB 路由上下文
 *
 * @package SharkyPHP\\Http
 * @author Sharky
 * @copyright 2026 SharkyPHP
 * @license MIT
 * @version 2.0.0
 * @internal
 */
declare (strict_types = 1);

namespace Sharky\Console\Commanding;

use Sharky\Pipeline\CollectTrait;

final class Context
{
    use CollectTrait;

    public string $prefix  = '';
    public string $toolkit = '';

    // 添加路由前缀（拼接追加）
    public function prefix(string $prefix): self
    {
        $this->prefix .= $prefix;
        return $this;
    }

    // 设置套件包
    public function toolkit(string $toolkit): self
    {
        $this->toolkit = $toolkit;
        return $this;
    }

    // 获取当前上下文快照
    public function getSnapshot(): self
    {
        return clone $this;
    }
}
