<?php

declare(strict_types=1);

namespace MailForge\Helpers;

/**
 * Native UUID v4 generator.
 *
 * Replaces ramsey/uuid for environments where Composer is unavailable
 * (e.g. shared hosting like Altervista without SSH).
 *
 * Uses random_bytes() (PHP 7+) for cryptographically secure random data.
 */
class UuidHelper
{
    /**
     * Generate a UUID v4 string.
     *
     * @return string  e.g. "550e8400-e29b-41d4-a716-446655440000"
     */
    public static function generate(): string
    {
        $data    = random_bytes(16);

        // Set version bits (v4)
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        // Set variant bits (RFC 4122)
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
