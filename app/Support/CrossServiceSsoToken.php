<?php

namespace App\Support;

use InvalidArgumentException;
use RuntimeException;

class CrossServiceSsoToken
{
    /**
     * @param  array<string, mixed>  $claims
     */
    public static function issue(array $claims, string $secret, int $ttlSeconds): string
    {
        $secret = trim($secret);
        if ($secret === '') {
            throw new RuntimeException('CROSS_SERVICE_SSO_SECRET is not configured.');
        }

        $now = time();
        $payload = array_merge($claims, [
            'iat' => $now,
            'exp' => $now + max(30, $ttlSeconds),
            'nonce' => bin2hex(random_bytes(16)),
        ]);

        $encodedPayload = static::base64UrlEncode(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $signature = hash_hmac('sha256', $encodedPayload, $secret, true);

        return $encodedPayload . '.' . static::base64UrlEncode($signature);
    }

    /**
     * @return array<string, mixed>
     */
    public static function validate(string $token, string $secret): array
    {
        $secret = trim($secret);
        if ($secret === '') {
            throw new RuntimeException('CROSS_SERVICE_SSO_SECRET is not configured.');
        }

        $parts = explode('.', trim($token), 2);
        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
            throw new InvalidArgumentException('Malformed SSO token.');
        }

        [$encodedPayload, $encodedSignature] = $parts;
        $expectedSignature = static::base64UrlEncode(hash_hmac('sha256', $encodedPayload, $secret, true));

        if (! hash_equals($expectedSignature, $encodedSignature)) {
            throw new InvalidArgumentException('Invalid SSO token signature.');
        }

        $payloadJson = static::base64UrlDecode($encodedPayload);
        $payload = json_decode($payloadJson, true);

        if (! is_array($payload)) {
            throw new InvalidArgumentException('Invalid SSO token payload.');
        }

        $exp = isset($payload['exp']) ? (int) $payload['exp'] : 0;
        if ($exp <= 0 || $exp < time()) {
            throw new InvalidArgumentException('Expired SSO token.');
        }

        return $payload;
    }

    private static function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $value): string
    {
        $padding = strlen($value) % 4;
        if ($padding > 0) {
            $value .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode(strtr($value, '-_', '+/'), true);
        if ($decoded === false) {
            throw new InvalidArgumentException('Invalid base64url token value.');
        }

        return $decoded;
    }
}
