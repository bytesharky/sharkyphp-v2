<?php
/**
 * SharkyPHP Foundation Library
 *
 * I18n 多语言
 *
 * @package SharkyPHP\\Foundation
 * @author Sharky
 * @copyright 2026 SharkyPHP
 * @license MIT
 * @version 2.0.0
 * @internal
 */
declare (strict_types = 1);

namespace Sharky\Support;

use InvalidArgumentException;

class I18n
{
    /**
     * 语言文件根目录
     * @var string
     */
    protected string $langRoot;

    /**
     * 可用语言tag映射 [tag => true]
     * @var array<string,bool>
     */
    protected array $available = [];

    /**
     * 默认语言tag
     * @var string
     */
    protected string $fallbackTag;

    /**
     * 当前生效语言
     * @var string
     */
    protected string $currentTag;

    /**
     * 已加载语言包缓存 [tag => array]
     * @var array<string,array>
     */
    protected array $packages = [];

    /**
     * @param string $langRoot 语言目录
     * @param array<string> $availableTags 期望启用的语言列表
     * @param string $fallbackTag 默认语言
     * @throws InvalidArgumentException
     */
    public function __construct(string $langRoot, array $availableTags, string $fallbackTag)
    {
        $this->langRoot = rtrim($langRoot, DIRECTORY_SEPARATOR);

        if (empty($availableTags)) {
            throw new InvalidArgumentException('available language cannot be empty');
        }

        // 校验：只有文件真实存在，才算有效语言
        foreach ($availableTags as $rawTag) {
            $tag  = strtolower($rawTag);
            $file = $this->getLangFilePath($tag);
            if (is_file($file)) {
                $this->available[$tag] = true;
            }
        }

        if (empty($this->available)) {
            throw new InvalidArgumentException('no valid language file found');
        }

        $fallbackTag = strtolower($fallbackTag);
        if (! isset($this->available[$fallbackTag])) {
            throw new InvalidArgumentException("fallback language {$fallbackTag} invalid or file missing");
        }

        $this->fallbackTag = $fallbackTag;
        $this->currentTag  = $this->fallbackTag;
    }

    /**
     * 语言协商，设置当前语言
     * @param array<string,float> $acceptParsed
     * @return string
     */
    public function negotiate(array $acceptParsed): self
    {
        $selected = $this->fallbackTag;
        foreach ($acceptParsed as $rawTag => $_q) {
            $parts = explode('-', strtolower($rawTag));
            while (! empty($parts)) {
                $tryTag = implode('-', $parts);
                if (isset($this->available[$tryTag])) {
                    $selected = $tryTag;
                    break 2;
                }
                array_pop($parts);
            }
        }

        $this->currentTag = $selected;
        return $this;
    }

    /**
     * 手动设置当前语言
     * @param string $tag
     * @return $this
     * @throws InvalidArgumentException
     */
    public function setLang(string $tag): self
    {
        $tag = strtolower($tag);
        if (! isset($this->available[$tag])) {
            throw new InvalidArgumentException("language {$tag} not available");
        }
        $this->currentTag = $tag;
        return $this;
    }

    public function getLang(): string
    {
        return $this->currentTag;
    }

    /**
     * 获取翻译，优先当前语言，找不到回退fallback
     * @param string $key
     * @param array $vars
     * @return string
     */
    public function trans(string $key, array $vars = []): string
    {
        $text = $this->getPackageText($this->currentTag, $key) ?? $this->getPackageText($this->fallbackTag, $key) ?? $key;

        // 占位符替换
        foreach ($vars as $k => $v) {
            $text = str_replace(':' . $k, (string) $v, $text);
        }
        return $text;
    }

    /**
     * 懒加载语言包 & 读取key
     */
    protected function getPackageText(string $tag, string $key): ?string
    {
        if (! isset($this->packages[$tag])) {
            $this->loadPackage($tag);
        }
        return $this->packages[$tag][$key] ?? null;
    }

    /**
     * 加载语言文件
     * @throws InvalidArgumentException
     */
    protected function loadPackage(string $tag): void
    {
        $file = $this->getLangFilePath($tag);
        if (! is_file($file)) {
            throw new InvalidArgumentException("lang file {$file} missing");
        }
        $data = require $file;
        if (! is_array($data)) {
            throw new InvalidArgumentException("lang file {$file} must return array");
        }
        $this->packages[$tag] = $data;
    }

    /**
     * 获取语言文件路径
     * @return string
     */    
    protected function getLangFilePath(string $tag): string
    {
        return $this->langRoot . DIRECTORY_SEPARATOR . $tag . '.php';
    }

    /**
     * 扫描目录自动发现语言文件（工具方法）
     * @return array<string>
     */
    public static function scanDir(string $langRoot): array
    {
        $root = rtrim($langRoot, DIRECTORY_SEPARATOR);
        $list = [];
        $glob = glob($root . DIRECTORY_SEPARATOR . '*.php');
        if (! $glob) {
            return [];
        }
        foreach ($glob as $f) {
            $name   = basename($f, '.php');
            $list[] = strtolower($name);
        }
        return $list;
    }

    /**
     * 解析Accept-Language header
     */
    public static function parseAcceptHeader(string $header): array
    {
        $result = [];
        $items  = explode(',', $header);
        foreach ($items as $item) {
            $item = trim($item);
            if ($item === '') {
                continue;
            }

            $seg  = explode(';', $item);
            $lang = trim($seg[0]);
            $q    = 1.0;
            if (isset($seg[1]) && str_starts_with(trim($seg[1]), 'q=')) {
                $q = (float) substr(trim($seg[1]), 2);
            }
            $result[$lang] = $q;
        }
        arsort($result);
        return $result;
    }
}
