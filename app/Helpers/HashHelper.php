<?php

declare(strict_types=1);

namespace MailForge\Helpers;

use RuntimeException;

class HashHelper
{
    private const BCRYPT_COST    = 12;
    private const CIPHER_METHOD  = 'AES-256-CBC';

    // ─── Password hashing ─────────────────────────────────────────────────

    /**
     * Hash a plain-text password using bcrypt.
     */
    public static function password(string $plain): string
    {
        $hash = password_hash($plain, PASSWORD_BCRYPT, ['cost' => self::BCRYPT_COST]);

        if ($hash === false) {
            throw new RuntimeException('Password hashing failed.');
        }

        return $hash;
    }

    /**
     * Verify a plain-text password against a stored bcrypt hash.
     */
    public static function verifyPassword(string $plain, string $hash): bool
    {
        return password_verify($plain, $hash);
    }

    // ─── Random tokens ────────────────────────────────────────────────────

    /**
     * Generate a cryptographically secure random hex token.
     *
     * @param int $length Number of bytes (the returned string is twice this length in hex).
     */
    public static function token(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }

    // ─── UUID v4 ──────────────────────────────────────────────────────────

    /**
     * Generate a UUID v4 string.
     */
    public static function uuid(): string
    {
        $data = random_bytes(16);

        // Set version to 0100 (4)
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        // Set bits 6-7 of clock_seq_hi to 10
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    // ─── Symmetric encryption / decryption ───────────────────────────────

    /**
     * Encrypt arbitrary data with AES-256-CBC.
     *
     * The returned value is a base64-encoded string containing the IV prepended
     * to the ciphertext, separated by "::".
     *
     * @param mixed  $data  Any scalar or array (arrays are JSON-encoded).
     * @param string $key   32-byte (256-bit) encryption key.
     */
    public static function encrypt(mixed $data, string $key): string
    {
        $payload = is_array($data) ? json_encode($data, JSON_THROW_ON_ERROR) : (string) $data;

        $ivLength = openssl_cipher_iv_length(self::CIPHER_METHOD);

        if ($ivLength === false) {
            throw new RuntimeException('Could not determine IV length for cipher: ' . self::CIPHER_METHOD);
        }

        $iv         = random_bytes($ivLength);
        $ciphertext = openssl_encrypt($payload, self::CIPHER_METHOD, $key, OPENSSL_RAW_DATA, $iv);

        if ($ciphertext === false) {
            throw new RuntimeException('Encryption failed.');
        }

        return base64_encode($iv . '::' . $ciphertext);
    }

    /**
     * Decrypt a value previously encrypted with encrypt().
     *
     * Returns the original string, or the decoded array if the original
     * value was an array.
     */
    public static function decrypt(string $data, string $key): mixed
    {
        $decoded = base64_decode($data, strict: true);

        if ($decoded === false) {
            throw new RuntimeException('Decryption failed: invalid base64 data.');
        }

        $parts = explode('::', $decoded, 2);

        if (count($parts) !== 2) {
            throw new RuntimeException('Decryption failed: malformed payload.');
        }

        [$iv, $ciphertext] = $parts;

        $plain = openssl_decrypt($ciphertext, self::CIPHER_METHOD, $key, OPENSSL_RAW_DATA, $iv);

        if ($plain === false) {
            throw new RuntimeException('Decryption failed: openssl_decrypt returned false.');
        }

        // If the original value was JSON-encoded, decode it back to an array
        $decoded = json_decode($plain, true);

        return ($decoded !== null && json_last_error() === JSON_ERROR_NONE) ? $decoded : $plain;
    }
}
