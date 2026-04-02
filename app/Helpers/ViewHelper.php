<?php

declare(strict_types=1);

namespace MailForge\Helpers;

use RuntimeException;

class ViewHelper
{
    // ─── View rendering ───────────────────────────────────────────────────

    /**
     * Render a view file, optionally wrapping it in a layout.
     *
     * The rendered view HTML is made available inside the layout as $content.
     *
     * @param array<string, mixed> $data
     */
    public static function render(string $template, array $data = [], string $layout = 'main'): string
    {
        $content = self::renderPartial($template, $data);

        $layoutFile = self::resolvePath("layouts/{$layout}");

        if (!file_exists($layoutFile)) {
            return $content;
        }

        extract($data, EXTR_SKIP);

        ob_start();
        require $layoutFile;
        return (string) ob_get_clean();
    }

    /**
     * Render a view partial and return the HTML string without a layout.
     *
     * @param array<string, mixed> $data
     */
    public static function renderPartial(string $template, array $data = []): string
    {
        $file = self::resolvePath($template);

        if (!file_exists($file)) {
            throw new RuntimeException("View partial not found: {$file}");
        }

        extract($data, EXTR_SKIP);

        ob_start();
        require $file;
        return (string) ob_get_clean();
    }

    // ─── Output escaping ──────────────────────────────────────────────────

    /**
     * Escape a string for safe HTML output.
     */
    public static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    // ─── Form helpers ─────────────────────────────────────────────────────

    /**
     * Retrieve an old form input value (stored in the session after a failed
     * validation redirect).
     */
    public static function old(string $key, string $default = ''): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return $default;
        }

        $old = $_SESSION['_old_input'] ?? [];
        return self::escape((string) ($old[$key] ?? $default));
    }

    /**
     * Retrieve a validation error message for a field.
     *
     * Returns an escaped string or an empty string when no error exists.
     */
    public static function error(string $key): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return '';
        }

        $errors = $_SESSION['_errors'] ?? [];

        if (!isset($errors[$key])) {
            return '';
        }

        $message = is_array($errors[$key]) ? ($errors[$key][0] ?? '') : $errors[$key];
        return self::escape((string) $message);
    }

    /**
     * Read and remove a flash message from the session.
     */
    public static function flash(string $key): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return '';
        }

        $flash = $_SESSION['_flash'] ?? [];
        $value = $flash[$key] ?? '';
        unset($_SESSION['_flash'][$key]);

        return self::escape((string) $value);
    }

    // ─── CSRF ─────────────────────────────────────────────────────────────

    /**
     * Render the CSRF hidden input field.
     */
    public static function csrf(): string
    {
        return CsrfHelper::field();
    }

    // ─── URL helpers ──────────────────────────────────────────────────────

    /**
     * Return the URL to a static asset.
     */
    public static function asset(string $path): string
    {
        return UrlHelper::asset($path);
    }

    /**
     * Return the full application URL for a given path.
     */
    public static function url(string $path = ''): string
    {
        return UrlHelper::base($path);
    }

    // ─── Private helpers ──────────────────────────────────────────────────

    private static function resolvePath(string $template): string
    {
        $base = dirname(__DIR__, 2) . '/resources/views';
        $path = str_replace('.', '/', $template);
        return "{$base}/{$path}.php";
    }
}
