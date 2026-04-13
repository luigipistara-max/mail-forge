<?php

declare(strict_types=1);

namespace MailForge\Core;

use PDO;
use RuntimeException;

abstract class Controller
{
    protected Request $request;
    protected PDO $db;
    protected Session $session;

    public function __construct()
    {
        $this->request = Request::make();
        $this->db      = Database::getInstance();
        $this->session = new Session();

        if (session_status() !== PHP_SESSION_ACTIVE) {
            $this->session->start();
        }
    }

    // ─── View rendering ───────────────────────────────────────────────────

    /**
     * Render a view wrapped in a layout and send the response.
     *
     * @param array<string, mixed> $data
     */
    protected function render(string $view, array $data = [], string $layout = 'main'): never
    {
        $content = $this->renderPartial($view, $data);

        $layoutFile = $this->resolveViewPath("layouts/{$layout}");

        if (!file_exists($layoutFile)) {
            // No layout found — emit content directly
            http_response_code(200);
            header('Content-Type: text/html; charset=UTF-8');
            echo $content;
            exit(0);
        }

        extract($data, EXTR_SKIP);

        ob_start();
        require $layoutFile;
        $output = (string) ob_get_clean();

        http_response_code(200);
        header('Content-Type: text/html; charset=UTF-8');
        echo $output;
        exit(0);
    }

    /**
     * Render a view partial and return the rendered HTML string.
     *
     * @param array<string, mixed> $data
     */
    protected function renderPartial(string $view, array $data = []): string
    {
        $file = $this->resolveViewPath($view);

        if (!file_exists($file)) {
            throw new RuntimeException("View not found: {$file}");
        }

        extract($data, EXTR_SKIP);

        ob_start();
        require $file;
        return (string) ob_get_clean();
    }

    // ─── Response helpers ─────────────────────────────────────────────────

    /**
     * Send a JSON response and terminate.
     */
    protected function json(mixed $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit(0);
    }

    /**
     * Redirect to a URL and terminate.
     * Relative URLs (starting with /) are automatically prefixed with BASE_PATH.
     */
    protected function redirect(string $url, int $status = 302): never
    {
        // Auto-prefix base path for relative URLs in subdirectory installations
        if (str_starts_with($url, '/') && !str_starts_with($url, '//')) {
            $basePath = defined('BASE_PATH') ? BASE_PATH : '';
            $url = $basePath . $url;
        }
        http_response_code($status);
        header("Location: {$url}");
        exit(0);
    }

    /**
     * Abort with an HTTP error code.
     */
    protected function abort(int $code, string $message = ''): never
    {
        http_response_code($code);

        if ($this->request->expectsJson()) {
            header('Content-Type: application/json');
            echo json_encode(['error' => $message ?: $this->defaultMessage($code), 'status' => $code]);
            exit(0);
        }

        $errorView = $this->resolveViewPath("errors/{$code}");

        if (file_exists($errorView)) {
            require $errorView;
        } else {
            echo "<h1>HTTP {$code}</h1><p>" . htmlspecialchars($message ?: $this->defaultMessage($code)) . '</p>';
        }

        exit(0);
    }

    // ─── Flash helpers ────────────────────────────────────────────────────

    protected function flash(string $key, mixed $value): void
    {
        $this->session->flash($key, $value);
    }

    protected function getFlash(string $key, mixed $default = null): mixed
    {
        return $this->session->getFlash($key, $default);
    }

    // ─── Auth helpers ─────────────────────────────────────────────────────

    /**
     * @return array<string, mixed>|null
     */
    protected function currentUser(): ?array
    {
        return $this->session->getUser();
    }

    /**
     * Redirect to /login if the user is not authenticated.
     */
    protected function requireAuth(): void
    {
        if (!$this->session->isLoggedIn()) {
            $this->session->flash('intended_url', $this->request->uri);
            $this->redirect('/login');
        }
    }

    /**
     * Abort with 403 if the current user does not have the required role.
     */
    protected function requireRole(string $role): void
    {
        $this->requireAuth();

        $user = $this->currentUser();

        if (($user['role'] ?? '') !== $role) {
            $this->abort(403, 'Insufficient permissions.');
        }
    }

    // ─── Private helpers ──────────────────────────────────────────────────

    private function resolveViewPath(string $view): string
    {
        $base = dirname(__DIR__, 2) . '/resources/views';
        $path = str_replace('.', '/', $view);
        return "{$base}/{$path}.php";
    }

    private function defaultMessage(int $code): string
    {
        return match ($code) {
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            422 => 'Unprocessable Entity',
            429 => 'Too Many Requests',
            500 => 'Internal Server Error',
            503 => 'Service Unavailable',
            default => 'HTTP Error',
        };
    }
}
