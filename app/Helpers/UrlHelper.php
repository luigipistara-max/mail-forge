<?php

declare(strict_types=1);

namespace MailForge\Helpers;

use RuntimeException;

class UrlHelper
{
    // ─── Base & asset URLs ────────────────────────────────────────────────

    /**
     * Return the application's base URL with an optional path appended.
     */
    public static function base(string $path = ''): string
    {
        $appUrl = rtrim((string) ($_ENV['APP_URL'] ?? getenv('APP_URL') ?: self::detectBaseUrl()), '/');

        if ($path === '') {
            return $appUrl;
        }

        return $appUrl . '/' . ltrim($path, '/');
    }

    /**
     * Return the full URL to a static asset under /public.
     */
    public static function asset(string $path): string
    {
        return self::base(ltrim($path, '/'));
    }

    // ─── Named route URL generation ───────────────────────────────────────

    /**
     * Build a URL for a named route.
     *
     * Named routes must be registered on a Router instance. This helper
     * delegates to any Router stored in a well-known global, or falls back
     * to building the path from the name string (kebab → /kebab).
     *
     * @param array<string, string|int> $params Route placeholder values.
     */
    public static function route(string $name, array $params = []): string
    {
        // Check if a Router instance has been stored globally
        $router = $GLOBALS['router'] ?? null;

        if ($router instanceof \MailForge\Core\Router) {
            try {
                $path = $router->urlFor($name, $params);
                return self::base($path);
            } catch (RuntimeException) {
                // Fall through to best-effort fallback
            }
        }

        // Best-effort fallback: convert route name to a URL path
        $path = '/' . str_replace('.', '/', $name);

        foreach ($params as $key => $value) {
            $path = str_replace('{' . $key . '}', (string) $value, $path);
        }

        return self::base($path);
    }

    // ─── HTTPS detection & enforcement ───────────────────────────────────

    /**
     * Determine whether the current request is served over HTTPS.
     */
    public static function isHttps(): bool
    {
        if (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
            return true;
        }

        if (($_SERVER['SERVER_PORT'] ?? '') === '443') {
            return true;
        }

        // Respect common reverse-proxy headers
        if (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') {
            return true;
        }

        if (($_SERVER['HTTP_X_FORWARDED_SSL'] ?? '') === 'on') {
            return true;
        }

        return false;
    }

    /**
     * If FORCE_HTTPS is configured and the request is not already HTTPS,
     * redirect to the HTTPS equivalent and terminate.
     */
    public static function forceHttps(): void
    {
        $force = filter_var(
            $_ENV['FORCE_HTTPS'] ?? getenv('FORCE_HTTPS') ?: false,
            FILTER_VALIDATE_BOOLEAN
        );

        if (!$force || self::isHttps()) {
            return;
        }

        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $uri  = $_SERVER['REQUEST_URI'] ?? '/';

        header('Location: https://' . $host . $uri, true, 301);
        exit(0);
    }

    // ─── Private helpers ──────────────────────────────────────────────────

    /**
     * Detect the base URL from the current server environment when APP_URL
     * has not been set.
     */
    private static function detectBaseUrl(): string
    {
        $scheme = self::isHttps() ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';

        return "{$scheme}://{$host}";
    }
}
