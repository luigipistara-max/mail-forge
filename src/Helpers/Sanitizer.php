<?php

declare(strict_types=1);

namespace MailForge\Helpers;

/**
 * Input sanitization helper.
 *
 * Protects against XSS and ensures all database interactions go through
 * PDO prepared statements (enforced in BaseModel).
 */
class Sanitizer
{
    /**
     * Strip tags and encode HTML special chars to prevent XSS.
     */
    public static function string(string $value): string
    {
        return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Sanitize an email address.
     */
    public static function email(string $value): string
    {
        $sanitized = filter_var(trim($value), FILTER_SANITIZE_EMAIL);
        return is_string($sanitized) ? strtolower($sanitized) : '';
    }

    /**
     * Sanitize a URL.
     */
    public static function url(string $value): string
    {
        $sanitized = filter_var(trim($value), FILTER_SANITIZE_URL);
        return is_string($sanitized) ? $sanitized : '';
    }

    /**
     * Sanitize an integer value.
     */
    public static function int(mixed $value): int
    {
        return (int) filter_var($value, FILTER_SANITIZE_NUMBER_INT);
    }

    /**
     * Sanitize a float value.
     */
    public static function float(mixed $value): float
    {
        return (float) filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }

    /**
     * Sanitize a filename (strip path traversal characters).
     */
    public static function filename(string $value): string
    {
        $value = basename($value);
        return preg_replace('/[^a-zA-Z0-9._\-]/', '_', $value) ?? '_';
    }

    /**
     * Recursively sanitize all string values in an array.
     *
     * @param array<mixed> $data
     * @return array<mixed>
     */
    public static function array(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = self::string($value);
            } elseif (is_array($value)) {
                $data[$key] = self::array($value);
            }
        }
        return $data;
    }

    /**
     * Remove all HTML tags from a string (useful for plain-text email body).
     */
    public static function stripHtml(string $value): string
    {
        return trim(strip_tags($value));
    }

    /**
     * Sanitize HTML content while allowing safe tags (basic allow-list).
     * For full HTML sanitization consider a dedicated library in production.
     */
    public static function html(string $value): string
    {
        $allowedTags = '<p><br><strong><em><ul><ol><li><a><h1><h2><h3><h4><h5><h6><span><div><table><tr><td><th><thead><tbody><img>';
        return strip_tags($value, $allowedTags);
    }
}
