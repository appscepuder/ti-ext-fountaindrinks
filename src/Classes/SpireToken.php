<?php

declare(strict_types=1);

namespace Cepuder\FountainDrinks\Classes;

use InvalidArgumentException;
use RuntimeException;

/**
 * Spire drink tokens (V1.3 format) and the store licence.
 *
 * Must match generate_token() in the Cepuder Generator and the validator on the Spire:
 *   plaintext = TIME|ID|size|sugar|flavors
 *   AES-256-CBC with the store key/iv, zero padding to 16 bytes (no PKCS7), base64.
 * The base64 string is the QR payload. Pure PHP + openssl, so tests/SpireTokenTest.php runs it standalone.
 */
class SpireToken
{
    public const array SIZES = ['Small', 'Medium', 'Large'];

    /** Default tiers from config.tiers.json; the Spires enforce them too. Only the sizes matter here. */
    public const array TIER_SIZES = [
        'loss_prevention' => ['Small'],
        'portion_control' => ['Small', 'Medium', 'Large'],
        'revenue_management' => ['Small', 'Medium', 'Large'],
        'employee_benefit' => ['Small'],
    ];

    protected const string ID_ALPHABET = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

    /**
     * Mint one token: the QR payload, plus the ID and time the Spire logs on each pour.
     *
     * @return array{payload: string, token_id: string, timestamp: int}
     */
    public static function mint(string $keyHex, string $ivHex, string $size, bool $sugar = false, bool $flavors = false, ?int $now = null): array
    {
        if (!in_array($size, self::SIZES, true)) {
            throw new InvalidArgumentException('Unknown Spire size '.$size);
        }
        $tokenId = self::randomId();
        $timestamp = $now ?? time();
        $text = $timestamp.'|'.$tokenId.'|'.$size.'|'.($sugar ? 'TRUE' : 'FALSE').'|'.($flavors ? 'TRUE' : 'FALSE');

        return ['payload' => self::encrypt($keyHex, $ivHex, $text), 'token_id' => $tokenId, 'timestamp' => $timestamp];
    }

    /** AES-256-CBC, zero padded to 16 bytes, base64. */
    public static function encrypt(string $keyHex, string $ivHex, string $text): string
    {
        [$key, $iv] = self::keyIv($keyHex, $ivHex);
        $text .= str_repeat("\0", (16 - strlen($text) % 16) % 16);
        $out = openssl_encrypt($text, 'aes-256-cbc', $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv);
        if ($out === false) {
            throw new RuntimeException('openssl_encrypt failed');
        }

        return base64_encode($out);
    }

    /** Inverse of encrypt(), for tests and diagnostics. */
    public static function decrypt(string $keyHex, string $ivHex, string $payload): string
    {
        [$key, $iv] = self::keyIv($keyHex, $ivHex);
        $out = openssl_decrypt((string)base64_decode($payload), 'aes-256-cbc', $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv);

        return rtrim((string)$out, "\0");
    }

    /**
     * Parse a store's config.license.json (from licenseGenerator.sh). Only the first terminal secret is
     * used: every device at a store shares the same key/iv. Errors are language keys.
     *
     * @return array{key: string, iv: string, tier: string, customer: string, secret_id: string}
     */
    public static function parseLicense(string $json): array
    {
        $data = json_decode($json, true);
        if (!is_array($data)) {
            throw new InvalidArgumentException('error_licence_not_json');
        }
        if (empty($data['terminalSecrets']) || !is_array($data['terminalSecrets'])) {
            throw new InvalidArgumentException('error_licence_no_secrets');
        }
        $terminal = $data['terminalSecrets'][0];
        $key = strtolower(trim((string)($terminal['key'] ?? '')));
        $iv = strtolower(trim((string)($terminal['iv'] ?? '')));
        if (!preg_match('/^[0-9a-f]{64}$/', $key) || !preg_match('/^[0-9a-f]{32}$/', $iv)) {
            throw new InvalidArgumentException('error_licence_malformed');
        }

        return [
            'key' => $key,
            'iv' => $iv,
            'tier' => (string)($terminal['tier'] ?? '') ?: 'unlicensed',
            'customer' => (string)($terminal['customer'] ?? ''),
            // Last 2 characters of the key, as printed by the Generator
            'secret_id' => substr($key, -2),
        ];
    }

    /** Sizes a tier may mint; empty for an unknown or unlicensed tier. */
    public static function allowedSizes(string $tier): array
    {
        return self::TIER_SIZES[$tier] ?? [];
    }

    /** @return array{0: string, 1: string} Binary key and iv */
    protected static function keyIv(string $keyHex, string $ivHex): array
    {
        if (!preg_match('/^[0-9a-fA-F]{64}$/', $keyHex) || !preg_match('/^[0-9a-fA-F]{32}$/', $ivHex)) {
            throw new InvalidArgumentException('Spire key must be 64 hex and iv 32 hex');
        }

        return [hex2bin($keyHex), hex2bin($ivHex)];
    }

    /** 24 random alphanumerics; the Spire's ID regex accepts nothing else. */
    protected static function randomId(): string
    {
        $id = '';
        while (strlen($id) < 24) {
            $id .= self::ID_ALPHABET[random_int(0, 61)];
        }

        return $id;
    }
}
