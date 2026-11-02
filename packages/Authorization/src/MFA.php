<?php
/**
 * SharkyPHP Authorization Library
 *
 * MultifactorAuthentication
 *
 * @package SharkyPHP\\Authorization
 * @author Sharky
 * @copyright 2026 SharkyPHP
 * @license MIT
 * @version 2.0.0
 * @internal
 */
declare (strict_types = 1);

namespace Sharky\Authorization;

use InvalidArgumentException;

class MFA
{
    private int $period;
    private int $digits;
    private string $algo;
    private string $secret;
    private string $secretBits;
    private const ISSUER = "Sharky";

    /**
     * @param string $secret Base32 编码密钥
     * @param int    $period   TOTP 时间周期(秒)，RFC‑6238 period
     * @param int    $digits   验证码位数，RFC‑6238 digits
     * @param string $algo     哈希算法：sha1/sha256/sha512
     * @throws InvalidArgumentException
     */
    public function __construct(string $secret, int $period = 30, int $digits = 6, string $algo = 'sha1')
    {
        $this->secretBits = $this->base32Decode($secret);
        if ($this->secretBits === false) {
            throw new InvalidArgumentException(sprintf("Secret (shared key) must be a valid Base32 string"));
        }
        if (! is_int($period) || $period < 10 || $period > 60) {
            throw new InvalidArgumentException(sprintf("Period must be an integer between 10 and 60"));
        }
        if (! is_int($digits) || $digits < 6 || $digits > 8) {
            throw new InvalidArgumentException(sprintf("Digits only allow values from 6 to 8"));
        }
        $lowerAlgo    = strtolower($algo);
        $allowedAlgos = implode(', ', ['sha1', 'sha256', 'sha512']);
        if (! in_array($lowerAlgo, ['sha1', 'sha256', 'sha512'], true)) {
            throw new InvalidArgumentException(sprintf("Unsupported hash algorithm '%s'. Allowed algorithms: %s", $algo, $allowedAlgos));
        }

        $this->secret = $secret;
        $this->period = $period;
        $this->digits = $digits;
        $this->algo   = $lowerAlgo;
    }

    /**
     * 生成一个随机的 Base32 编码共享密钥。
     * @param int $length base32字符长度 默认16
     * @return string Base32 编码的共享密钥
     * @throws InvalidArgumentException
     */
    public static function generateSharedSecret(int $length = 16): string
    {
        if ($length < 16) {
            throw new InvalidArgumentException(
                sprintf("Secret length cannot be less than 16 Base32 characters, given: %d", $length)
            );
        }
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret   = '';
        for ($i = 0; $i < $length; $i++) {
            $secret .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        return $secret;
    }

    /**
     * 静态生成otpauth URI
     * @param string $account
     * @param string $secret
     * @param string $issuer
     * @param int $digits
     * @param int $period
     * @param string $algo
     * @return string
     */
    public static function makeQRCodeUrl(
        string $account,
        string $secret,
        string $issuer = self::ISSUER,
        int $digits = 6,
        int $period = 30,
        string $algo = 'sha1'
    ): string {
        $issuer  = rawurlencode($issuer);
        $account = rawurlencode($account);
        $label   = "{$issuer}:{$account}";
        $params  = [
            'secret' => $secret,
            'issuer' => $issuer,
        ];
        // 非默认参数才追加
        if ($digits !== 6) {
            $params['digits'] = $digits;
        }
        if ($period !== 30) {
            $params['period'] = $period;
        }
        $algoUpper = strtolower($algo);
        if ($algoUpper !== 'sha1') {
            $params['algorithm'] = $algoUpper;
        }
        $query = http_build_query($params);
        return "otpauth://totp/{$label}?{$query}";
    }

    /**
     * 实例获取二维码URI
     * @param string $account
     * @param string $issuer
     * @return string
     */
    public function getQRCodeUrl(string $account, string $issuer = self::ISSUER): string
    {
        return self::makeQRCodeUrl(
            $account,
            $this->secret,
            $issuer,
            $this->digits,
            $this->period,
            $this->algo
        );
    }

    /**
     * Base32解码，严格校验，比特数模8不为0直接返回false
     * @param string $secret
     * @return string|false
     */
    private static function base32Decode(string $secret): string | false
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret   = str_replace('=', '', strtoupper($secret));
        $len      = strlen($secret);
        if (! preg_match('/^[A-Z2-7]+$/', $secret)) {
            return false;
        }
        $bits = '';
        for ($i = 0; $i < $len; $i++) {
            $ch    = $secret[$i];
            $idx   = strpos($alphabet, $ch);
            $bits .= str_pad(base_convert((string) $idx, 10, 2), 5, '0', STR_PAD_LEFT);
        }
        $totalBits = strlen($bits);
        if ($totalBits % 8 !== 0) {
            return false;
        }
        $byteCount = intdiv($totalBits, 8);
        $binary    = '';
        for ($i = 0; $i < $byteCount; $i++) {
            $chunk   = substr($bits, $i * 8, 8);
            $binary .= chr((int) base_convert($chunk, 2, 10));
        }
        return $binary;
    }

    /**
     * HOTP 内部私有方法
     * @param int $counter
     * @return string 补齐前导零的验证码字符串
     */
    private function getHOTPToken(int $counter): string
    {
        $binCounter = pack('J', $counter);
        $hmacRaw    = hash_hmac($this->algo, $binCounter, $this->secretBits, true);
        $offset     = ord($hmacRaw[-1]) & 0x0f;

        $binChunk = substr($hmacRaw, $offset, 4);
        $int      = unpack('N', $binChunk)[1] & 0x7FFFFFFF;
        $mod      = 10 ** $this->digits;
        $tokenInt = $int % $mod;
        return str_pad((string) $tokenInt, $this->digits, '0', STR_PAD_LEFT);
    }

    /**
     * 获取当前窗口内全部TOTP
     * @param int $faultTol 容错窗口
     * @return array{totps: string[], reset: int}
     * @throws InvalidArgumentException
     */
    public function getTOTPToken(int $faultTol = 1): array
    {
        if (abs($faultTol) > 5) {
            throw new InvalidArgumentException(
                sprintf("Fault tolerance must not exceed 5, given: %d", $faultTol)
            );
        }
        $ctime   = time();
        $counter = intdiv($ctime, $this->period);
        $reset   = $this->period - ($ctime % $this->period);
        $totps   = [];
        $range   = abs($faultTol);
        for ($i = -$range; $i <= $range; $i++) {
            $totps[] = $this->getHOTPToken($counter + $i);
        }
        return [
            "totps" => $totps,
            "reset" => $reset,
        ];
    }

    /**
     * 校验用户输入验证码
     * @param string $inputCode 用户输入
     * @param int $faultTol
     * @return bool
     */
    public function verify(string $inputCode, int $faultTol = 1): bool
    {
        $info = $this->getTOTPToken($faultTol);
        return in_array($inputCode, $info['totps']);
    }
}
