<?php
/**
 * SharkyPHP Authorization Library
 *
 * JSON Web Token (JWT)
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

use JsonSerializable;
use UnexpectedValueException;

class JWT implements JsonSerializable
{
    private $secretKey;
    private $algorithm;
    private $payload;
    private $header;

    public function __construct($secretKey, $algorithm = 'HS256')
    {
        $algorithm  = strtoupper($algorithm);
        $algorithms = [
            'HS256'  => 'sha256',
            'HS384'  => 'sha384',
            'HS512'  => 'sha512',
            'SHA256' => 'sha256',
            'SHA384' => 'sha384',
            'SHA512' => 'sha512',
        ];
        $allowed = 'HS256, HS384, HS512';
        if (! isset($algorithms[$algorithm])) {
            throw new UnexpectedValueException(sprintf("Unsupported type '%s'. Allowed types: %s", $algorithm, $allowed));
        }

        $this->secretKey = $secretKey;
        $this->algorithm = $algorithms[$algorithm];
    }

    public function issue($payload, $timeout = 3600): static
    {
        $this->header  = ['typ' => 'JWT', 'alg' => $this->algorithm];
        $this->payload = array_merge($payload, [
            'exp' => time() + $timeout,
        ]);
        return $this;
    }

    public function verify(string $token): static
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            throw new InvalidArgumentException(sprintf("Invalid JWT token, expected 3 segments"));
        }
        $header  = json_decode(self::base64UrlDecode($parts[0]), true);
        $payload = json_decode(self::base64UrlDecode($parts[1]), true);
        if ($parts[2] !== self::sign("{$parts[0]}.{$parts[1]}", $this->secretKey, $header['alg'])) {
            throw new UnexpectedValueException(sprintf("Invalid JWT token signature"));
        }

        if ($payload['exp'] < time()) {
            throw new UnexpectedValueException(sprintf("JWT token has expired"));
        }

        if (isset($payload['nbf']) && $now < $payload['nbf']) {
            throw new UnexpectedValueException(sprintf('JWT token not yet valid'));
        }

        $jwt          = new self($this->secretKey, $header['alg']);
        $jwt->header  = $header;
        $jwt->payload = $payload;
        return $jwt;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function getHeader(): array
    {
        return $this->header;
    }

    private static function sign($data, $secretKey, $algorithm): string
    {
        return self::base64UrlEncode(hash_hmac($algorithm, $data, $secretKey, true));
    }

    private static function base64UrlEncode($data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode($data): bool | string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }

    public function __toString(): string
    {
        $header    = self::base64UrlEncode(json_encode($this->header));
        $payload   = self::base64UrlEncode(json_encode($this->payload));
        $signature = self::sign("{$header}.{$payload}", $this->secretKey, $this->algorithm);

        return "{$header}.{$payload}.{$signature}";
    }

    public function jsonSerialize(): mixed
    {
        return $this->__toString();
    }

}
