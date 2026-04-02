<?php

declare(strict_types=1);

namespace MailForge\Helpers;

use RuntimeException;

class CsrfHelper
{
    private const SESSION_KEY = '_csrf_token';
    private const FIELD_NAME  = '_csrf_token';
    private const HEADER_NAME = 'X-CSRF-Token';

    // ─── Token management ─────────────────────────────────────────────────

    /**
     * Generate a new CSRF token, store it in the session and return it.
     */
    public static function generate(): string
    {
        self::requireSession();

        $token = bin2hex(random_bytes(32));
        $_SESSION[self::SESSION_KEY] = $token;

        return $token;
    }

    /**
     * Return the current CSRF token, generating one if none exists.
     */
    public static function getToken(): string
    {
        self::requireSession();

        if (empty($_SESSION[self::SESSION_KEY])) {
            return self::generate();
        }

        return (string) $_SESSION[self::SESSION_KEY];
    }

    /**
     * Validate a token value against the session token using a timing-safe comparison.
     */
    public static function validate(string $token): bool
    {
        self::requireSession();

        $stored = $_SESSION[self::SESSION_KEY] ?? '';

        if ($stored === '' || $token === '') {
            return false;
        }

        return hash_equals($stored, $token);
    }

    // ─── Output helpers ───────────────────────────────────────────────────

    /**
     * Return an HTML hidden input carrying the CSRF token.
     */
    public static function field(): string
    {
        $token = htmlspecialchars(self::getToken(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $name  = htmlspecialchars(self::FIELD_NAME, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return "<input type=\"hidden\" name=\"{$name}\" value=\"{$token}\">";
    }

    /**
     * Return the token intended for use in an AJAX request header.
     */
    public static function header(): string
    {
        return self::getToken();
    }

    /**
     * Return the header name that clients should use when sending the token.
     */
    public static function headerName(): string
    {
        return self::HEADER_NAME;
    }

    /**
     * Return the form field name used for the hidden input.
     */
    public static function fieldName(): string
    {
        return self::FIELD_NAME;
    }

    // ─── Private helpers ──────────────────────────────────────────────────

    private static function requireSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            throw new RuntimeException(
                'A PHP session must be active before using CsrfHelper.'
            );
        }
    }
}
