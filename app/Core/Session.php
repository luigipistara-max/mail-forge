<?php

declare(strict_types=1);

namespace MailForge\Core;

use RuntimeException;

class Session
{
    private const FLASH_KEY     = '_flash';
    private const USER_KEY      = '_user';
    private const OLD_INPUT_KEY = '_old_input';

    // ─── Lifecycle ────────────────────────────────────────────────────────

    /**
     * Start the session with secure, recommended settings.
     * Safe to call multiple times — no-op if already started.
     */
    public function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $lifetime = (int) ($_ENV['SESSION_LIFETIME'] ?? getenv('SESSION_LIFETIME') ?: 120);
        $secure   = filter_var($_ENV['SESSION_SECURE']   ?? getenv('SESSION_SECURE')   ?: false, FILTER_VALIDATE_BOOLEAN);
        $httpOnly = filter_var($_ENV['SESSION_HTTPONLY']  ?? getenv('SESSION_HTTPONLY')  ?: true,  FILTER_VALIDATE_BOOLEAN);
        $sameSite = (string) ($_ENV['SESSION_SAMESITE']  ?? getenv('SESSION_SAMESITE')  ?: 'Lax');

        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params([
                'lifetime' => $lifetime * 60,
                'path'     => '/',
                'secure'   => $secure,
                'httponly' => $httpOnly,
                'samesite' => $sameSite,
            ]);

            ini_set('session.use_strict_mode', '1');
            ini_set('session.use_only_cookies', '1');

            session_start();
        }
    }

    public function regenerate(bool $deleteOld = true): void
    {
        $this->requireStarted();
        session_regenerate_id($deleteOld);
    }

    public function destroy(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                [
                    'expires'  => time() - 42000,
                    'path'     => $params['path'],
                    'domain'   => $params['domain'],
                    'secure'   => $params['secure'],
                    'httponly' => $params['httponly'],
                    'samesite' => $params['samesite'] ?? 'Lax',
                ]
            );
        }

        session_destroy();
    }

    // ─── Basic get / set / has / remove ───────────────────────────────────

    public function set(string $key, mixed $value): void
    {
        $this->requireStarted();
        $_SESSION[$key] = $value;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->requireStarted();
        return $_SESSION[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        $this->requireStarted();
        return isset($_SESSION[$key]);
    }

    public function remove(string $key): void
    {
        $this->requireStarted();
        unset($_SESSION[$key]);
    }

    // ─── Flash messages ───────────────────────────────────────────────────

    /**
     * Store a flash value. It will be available for exactly one read via getFlash().
     */
    public function flash(string $key, mixed $value): void
    {
        $this->requireStarted();
        $flash        = $_SESSION[self::FLASH_KEY] ?? [];
        $flash[$key]  = $value;
        $_SESSION[self::FLASH_KEY] = $flash;
    }

    /**
     * Read and remove a flash value.
     */
    public function getFlash(string $key, mixed $default = null): mixed
    {
        $this->requireStarted();
        $flash = $_SESSION[self::FLASH_KEY] ?? [];
        $value = $flash[$key] ?? $default;
        unset($_SESSION[self::FLASH_KEY][$key]);

        return $value;
    }

    public function hasFlash(string $key): bool
    {
        $this->requireStarted();
        return isset($_SESSION[self::FLASH_KEY][$key]);
    }

    // ─── Old input (for form repopulation after validation failure) ───────

    public function flashOldInput(array $data): void
    {
        $this->set(self::OLD_INPUT_KEY, $data);
    }

    public function getOld(string $key, mixed $default = ''): mixed
    {
        $this->requireStarted();
        $old = $_SESSION[self::OLD_INPUT_KEY] ?? [];
        return $old[$key] ?? $default;
    }

    public function clearOldInput(): void
    {
        $this->remove(self::OLD_INPUT_KEY);
    }

    // ─── User ─────────────────────────────────────────────────────────────

    /**
     * @param array<string, mixed> $user
     */
    public function setUser(array $user): void
    {
        $this->requireStarted();
        $_SESSION[self::USER_KEY] = $user;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getUser(): ?array
    {
        $this->requireStarted();
        $user = $_SESSION[self::USER_KEY] ?? null;
        return is_array($user) ? $user : null;
    }

    public function isLoggedIn(): bool
    {
        return $this->getUser() !== null;
    }

    public function logout(): void
    {
        $this->requireStarted();
        unset($_SESSION[self::USER_KEY]);
    }

    // ─── Private helpers ──────────────────────────────────────────────────

    private function requireStarted(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            throw new RuntimeException(
                'Session has not been started. Call Session::start() first.'
            );
        }
    }
}
