<?php

declare(strict_types=1);

namespace MailForge\Core;

use InvalidArgumentException;

class Response
{
    private int $statusCode = 200;

    /** @var array<string, string> */
    private array $headers = [];

    private string $body = '';

    // ─── Fluent header / status setters ──────────────────────────────────

    public function setHeader(string $key, string $value): static
    {
        $this->headers[$key] = $value;
        return $this;
    }

    public function setStatus(int $code): static
    {
        if ($code < 100 || $code > 599) {
            throw new InvalidArgumentException("Invalid HTTP status code: {$code}");
        }

        $this->statusCode = $code;
        return $this;
    }

    // ─── Response factories ───────────────────────────────────────────────

    /**
     * Send a JSON response and terminate execution.
     */
    public function json(mixed $data, int $status = 200): never
    {
        $this->setStatus($status)
             ->setHeader('Content-Type', 'application/json');

        $encoded = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($encoded === false) {
            $encoded = json_encode(['error' => 'JSON encoding failed']);
        }

        $this->body = (string) $encoded;
        $this->send();
    }

    /**
     * Issue an HTTP redirect and terminate execution.
     */
    public function redirect(string $url, int $status = 302): never
    {
        if ($status < 300 || $status > 399) {
            throw new InvalidArgumentException("Redirect status must be 3xx, got {$status}.");
        }

        $this->setStatus($status)
             ->setHeader('Location', $url);

        $this->body = '';
        $this->send();
    }

    /**
     * Render a view template and send the response.
     *
     * @param array<string, mixed> $data
     */
    public function view(string $template, array $data = [], int $status = 200): never
    {
        $this->setStatus($status)
             ->setHeader('Content-Type', 'text/html; charset=UTF-8');

        $this->body = $this->renderView($template, $data);
        $this->send();
    }

    // ─── Send ─────────────────────────────────────────────────────────────

    /**
     * Emit all headers and the response body, then terminate.
     */
    public function send(): never
    {
        if (!headers_sent()) {
            http_response_code($this->statusCode);

            foreach ($this->headers as $key => $value) {
                header("{$key}: {$value}");
            }
        }

        echo $this->body;
        exit(0);
    }

    // ─── Private helpers ──────────────────────────────────────────────────

    /**
     * @param array<string, mixed> $data
     */
    private function renderView(string $template, array $data): string
    {
        $viewsPath = dirname(__DIR__, 2) . '/resources/views';
        $file      = $viewsPath . '/' . str_replace('.', '/', $template) . '.php';

        if (!file_exists($file)) {
            // Return a simple error page rather than throwing — safe fallback
            return "<h1>View not found</h1><p>{$file}</p>";
        }

        extract($data, EXTR_SKIP);

        ob_start();
        require $file;
        return (string) ob_get_clean();
    }
}
