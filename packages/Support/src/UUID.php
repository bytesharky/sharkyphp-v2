<?php
/**
 * SharkyPHP Support Library
 *
 * UUID生成器
 *
 * @package SharkyPHP\\Support
 * @author Sharky
 * @copyright 2026 SharkyPHP
 * @license MIT
 * @version 2.0.0
 * @internal
 */
declare (strict_types = 1);

namespace Sharky\Support;

use InvalidArgumentException;

final class UUID
{
    // UUID v1: 基于时间 + 随机节点 + clock序列
    public static function UUIDv1()
    {
        $time    = (int) (microtime(true) * 10000000) + 0x01B21DD213814000;
        $timeHex = str_pad(dechex($time), 16, '0', STR_PAD_LEFT);

        $timeLow          = substr($timeHex, 8, 8);
        $timeMid          = substr($timeHex, 4, 4);
        $timeHiAndVersion = sprintf('%04x', (hexdec(substr($timeHex, 0, 4)) & 0x0fff) | 0x1000); // version 1

        $clockSeq              = random_int(0, 0x3fff);
        $clockSeqHiAndReserved = sprintf('%04x', ($clockSeq & 0x3fff) | 0x8000); // variant 10

        $node = bin2hex(random_bytes(6));

        return sprintf(
            '%s-%s-%s-%s-%s',
            $timeLow,
            $timeMid,
            $timeHiAndVersion,
            $clockSeqHiAndReserved,
            $node
        );
    }

    // UUID v2: 简化版（非标准）
    public static function UUIDv2(int $localDomain, $localIdentifier)
    {
        if ($localDomain < 0 || $localDomain > 255) {
            throw new InvalidArgumentException(
                sprintf("localDomain must be between 0 and 255, given: %s", $localDomain)
            );
        }

        $time    = (int) (microtime(true) * 10000000) + 0x01B21DD213814000;
        $timeHex = str_pad(dechex($time), 16, '0', STR_PAD_LEFT);

        $clockSeq = random_int(0, 0x3fff);
        $node     = str_pad(dechex($localDomain), 2, '0', STR_PAD_LEFT) . substr(str_pad(dechex((int) $localIdentifier), 10, '0', STR_PAD_LEFT), -10);

        return sprintf(
            '%08s-%04s-%04x-%04x-%012s',
            substr($timeHex, 0, 8),
            substr($timeHex, 8, 4),
            (hexdec(substr($timeHex, 12, 4)) & 0x0fff) | 0x2000, // version 2
            ($clockSeq & 0x3fff) | 0x8000,                       // variant 10
            $node
        );
    }

    // UUID v3: 基于命名空间的MD5哈希
    public static function UUIDv3($namespace, $name)
    {
        if (! self::isUUID($namespace)) {
            throw new InvalidArgumentException(
                sprintf("Namespace must be a valid UUID, given: '%s'", $namespace)
            );
        }

        $namespaceBin = hex2bin(str_replace(['-', '{', '}'], '', $namespace));
        $hash         = md5($namespaceBin . $name);

        return sprintf(
            '%08s-%04s-%04x-%04x-%12s',
            substr($hash, 0, 8),
            substr($hash, 8, 4),
            (hexdec(substr($hash, 12, 4)) & 0x0fff) | 0x3000, // version 3
            (hexdec(substr($hash, 16, 4)) & 0x3fff) | 0x8000, // variant 10
            substr($hash, 20, 12)
        );
    }

    // UUID v4: 完全随机
    public static function UUIDv4()
    {
        $data    = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40); // version 4
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80); // variant 10

        return sprintf(
            '%08s-%04s-%04s-%04s-%12s',
            bin2hex(substr($data, 0, 4)),
            bin2hex(substr($data, 4, 2)),
            bin2hex(substr($data, 6, 2)),
            bin2hex(substr($data, 8, 2)),
            bin2hex(substr($data, 10, 6))
        );
    }

    // UUID v5: 基于命名空间的SHA1哈希
    public static function UUIDv5($namespace, $name)
    {
        if (! self::isUUID($namespace)) {
            throw new InvalidArgumentException(
                sprintf("Namespace must be a valid UUID, given: '%s'", $namespace)
            );
        }

        $namespaceBin = hex2bin(str_replace(['-', '{', '}'], '', $namespace));
        $hash         = sha1($namespaceBin . $name);

        return sprintf(
            '%08s-%04s-%04x-%04x-%12s',
            substr($hash, 0, 8),
            substr($hash, 8, 4),
            (hexdec(substr($hash, 12, 4)) & 0x0fff) | 0x5000, // version 5
            (hexdec(substr($hash, 16, 4)) & 0x3fff) | 0x8000, // variant 10
            substr($hash, 20, 12)
        );
    }

    // UUID v6: 时间优先 + 随机
    public static function UUIDv6()
    {
        $time = (int) (microtime(true) * 10000);

        $timeHigh = ($time >> 28) & 0xFFFFFFFF;
        $timeMid  = ($time >> 12) & 0xFFFF;
        $timeLow  = ($time & 0x0FFF) | 0x6000;      // version 5
        $clockSeq = random_int(0, 0x3fff) | 0x8000; // variant 10

        $node = bin2hex(random_bytes(6));

        return sprintf(
            '%08x-%04x-%04x-%04x-%012s',
            $timeHigh,
            $timeMid,
            $timeLow,
            $clockSeq,
            $node
        );
    }

    // UUID v7: 基于Unix时间戳的UUID
    public static function UUIDv7()
    {
        $time    = (int) (microtime(true) * 1000);
        $timeHex = str_pad(dechex($time), 12, '0', STR_PAD_LEFT);

        $random = bin2hex(random_bytes(10));

        return sprintf(
            '%08s-%04s-%04x-%04x-%12s',
            substr($timeHex, 0, 8),
            substr($timeHex, 8, 4),
            (hexdec(substr($random, 0, 4)) & 0x0fff) | 0x7000, // version 7
            (hexdec(substr($random, 4, 4)) & 0x3fff) | 0x8000, // variant 10
            substr($random, 8, 12)
        );
    }

    // UUID v8: 基于自定义数据的UUID
    public static function UUIDv8($customA = '', $customB = '', $customC = '')
    {

        $customA = substr(bin2hex($customA), 0, 12);
        $customB = substr(bin2hex($customB), 0, 4);
        $customC = substr(bin2hex($customC), 0, 16);
        $hex     = $customA . $customB . $customC;

        if (strlen($hex) < 32) {
            $hex .= bin2hex(random_bytes(intdiv(32 - strlen($hex), 2)));
        }

        $data    = hex2bin($hex);
        $data[6] = chr((ord($data[6]) & 0x0F) | 0x80);
        $data[8] = chr((ord($data[8]) & 0x3F) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    // 检查是否符合UUID格式
    public static function isUUID($uuid)
    {
        if (
            (bool) preg_match(
                '/^[0-9a-f]{8}-[0-9a-f]{4}-([1-8])[0-9a-f]{3}-([0-9a-f])[0-9a-f]{3}-[0-9a-f]{12}$/i',
                $uuid,
                $match
            )
        ) {
            $uuid    = $match[0];
            $version = $match[1];
            $variant = hexdec($match[2]);
            if (($variant & 0b1000) === 0b0000) {
                // 0xxx
                $variantDesc = "Variant 0 (NCS Reserved)";
            } elseif (($variant & 0b1100) === 0b1000) {
                // 10xx
                $variantDesc = "Variant 1 (RFC 4122 / RFC 9562)";
            } elseif (($variant & 0b1110) === 0b1100) {
                // 110x
                $variantDesc = "Variant 2 (Microsoft GUID)";
            } elseif (($variant & 0b1110) === 0b1110) {
                // 111x
                $variantDesc = "Variant 3 (Reserved for Future)";
            } else {
                $variant     = -1;
                $variantDesc = "Unknown Variant";
            }
            return [
                "uuid"    => $uuid,
                "version" => $version,
                "variant" => $variantDesc,
            ];
        } else {
            return false;
        }
    }
}
