<?php
/**
 * SharkyPHP Foundation Library
 *
 * 数组工具
 *
 * @package SharkyPHP\\Foundation
 * @author Sharky
 * @copyright 2026 SharkyPHP
 * @license MIT
 * @internal
 */
declare (strict_types = 1);

namespace Sharky\Support;

final class ArrayUtils
{
    /**
     * 深度合并多个数组
     *
     * @param array ...$arrays 要合并的数组列表
     * @return array 合并后的数组
     */
    public static function deepMerge(...$arrays): array
    {
        $result = [];
        foreach ($arrays as $array) {
            if (! is_array($array)) {
                continue;
            }

            foreach ($array as $key => $value) {
                if (is_int($key)) {
                    $result[] = $value;
                } elseif (isset($result[$key]) && is_array($result[$key]) && is_array($value)) {
                    $result[$key] = self::deepMerge($result[$key], $value);
                } else {
                    $result[$key] = $value;
                }
            }
        }

        return $result;
    }

    /**
     * 检查数组是否为关联数组
     *
     * @param array $array 要检查的数组
     * @return bool
     */
    public static function isAssoc(array $array): bool
    {
        if (empty($array)) {
            return false;
        }
        return array_keys($array) !== range(0, count($array) - 1);
    }

    /**
     * 获取数组指定路径的值
     *
     * @param array $array 数组
     * @param string $path 路径
     * @param mixed $default 默认值
     * @return mixed
     */
    public static function get(array $array, string $path, mixed $default = null)
    {
        $paths = explode('.', $path);

        foreach ($paths as $key) {
            if (! is_array($array) || ! array_key_exists($key, $array)) {
                return $default;
            }
            $array = $array[$key];
        }

        return $array;
    }

    /**
     * 设置数组指定路径的值
     *
     * @param array &$array 要修改的数组
     * @param string $path 路径
     * @param mixed $value 要设置的值
     */
    public static function set(array &$array, string $path, mixed $value): void
    {
        $paths = explode('.', $path);

        $current = &$array;
        foreach ($paths as $key) {
            if (! isset($current[$key]) || ! is_array($current[$key])) {
                $current[$key] = [];
            }
            $current = &$current[$key];
        }
        $current = $value;
    }

    /**
     * 检查数组指定路径是否存在
     *
     * @param array &$array 要修改的数组
     * @param string $path 路径
     */
    public static function has(array $array, string $path)
    {
        $paths = explode('.', $path);

        foreach ($paths as $key) {
            if (! is_array($array) || ! array_key_exists($key, $array)) {
                return false;
            }
        }
        return true;
    }
}
