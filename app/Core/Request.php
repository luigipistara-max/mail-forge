<?php

declare(strict_types=1);

namespace MailForge\Core;

class Request
{
    public string $method;
    public string $uri;

    /** @var array<string, string> Named URL parameters captured by the router */
    public array $params = [];

    /** @var array<string, mixed> POST body */
    public array $body = [];

    /** @var array<string, mixed> GET query string */
    public array $query = [];

    /** @var array<string, array<string, mixed>> Uploaded files ($_FILES) */
    public array $files = [];

    /** @var array<string, string> HTTP headers */
    public array $headers = [];

    public string $ip;

    private function __construct()
    {
        $this->method  = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $this->uri     = $_SERVER['REQUEST_URI'] ?? '/';
        $this->query   = $_GET ?? [];
        $this->files   = $_FILES ?? [];
        $this->ip      = $this->resolveIp();
        $this->headers = $this->parseHeaders();
        $this->body    = $this->parseBody();
    }

    // ─── Factory ──────────────────────────────────────────────────────────

    public static function make(): static
    {
        return new static();
    }

    // ─── Input access ─────────────────────────────────────────────────────

    /**
     * Retrieve a value from the body (POST) or query string (GET).
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    /**
     * Alias for get() — checks both POST body and GET query.
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->get($key, $default);
    }

    /**
     * Return all merged input (body + query, body takes precedence).
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return array_merge($this->query, $this->body);
    }

    /**
     * Retrieve an uploaded file entry from $_FILES.
     *
     * @return array<string, mixed>|null
     */
    public function file(string $key): ?array
    {
        $file = $this->files[$key] ?? null;

        if (!is_array($file) || !isset($file['tmp_name'])) {
            return null;
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        return $file;
    }

    // ─── Method checks ────────────────────────────────────────────────────

    public function isGet(): bool
    {
        return $this->method === 'GET';
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    public function isPut(): bool
    {
        return $this->method === 'PUT';
    }

    public function isPatch(): bool
    {
        return $this->method === 'PATCH';
    }

    public function isDelete(): bool
    {
        return $this->method === 'DELETE';
    }

    public function isAjax(): bool
    {
        return ($this->headers['X-Requested-With'] ?? '') === 'XMLHttpRequest';
    }

    public function isJson(): bool
    {
        $contentType = $this->headers['Content-Type'] ?? '';
        return str_contains($contentType, 'application/json');
    }

    public function expectsJson(): bool
    {
        $accept = $this->headers['Accept'] ?? '';
        return str_contains($accept, 'application/json') || $this->isAjax();
    }

    // ─── Validation ───────────────────────────────────────────────────────

    /**
     * Validate the request input against a set of rules.
     * Returns an array of validation errors keyed by field name.
     * An empty array means validation passed.
     *
     * Rules support: required, email, min:n, max:n, numeric, alpha, alphanumeric,
     *                in:a,b,c, url, confirmed
     *
     * @param  array<string, string> $rules  e.g. ['email' => 'required|email']
     * @return array<string, string[]>
     */
    public function validate(array $rules): array
    {
        $errors = [];
        $data   = $this->all();

        foreach ($rules as $field => $ruleString) {
            $fieldRules = explode('|', $ruleString);
            $value      = $data[$field] ?? null;

            foreach ($fieldRules as $rule) {
                [$ruleName, $ruleParam] = $this->parseRule($rule);

                $error = $this->applyRule($field, $value, $ruleName, $ruleParam, $data);

                if ($error !== null) {
                    $errors[$field][] = $error;
                }
            }
        }

        return $errors;
    }

    // ─── Private helpers ──────────────────────────────────────────────────

    private function parseBody(): array
    {
        if ($this->isJson()) {
            $raw = file_get_contents('php://input');
            if ($raw !== false && $raw !== '') {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    return $decoded;
                }
            }
            return [];
        }

        return $_POST ?? [];
    }

    private function parseHeaders(): array
    {
        $headers = [];

        if (function_exists('getallheaders')) {
            foreach (getallheaders() as $name => $value) {
                $headers[$name] = $value;
            }
            return $headers;
        }

        // Fallback for environments where getallheaders() is unavailable
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace('_', '-', ucwords(strtolower(substr($key, 5)), '_'));
                $headers[$name] = $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'], true)) {
                $name = str_replace('_', '-', ucwords(strtolower($key), '_'));
                $headers[$name] = $value;
            }
        }

        return $headers;
    }

    private function resolveIp(): string
    {
        $candidates = [
            'HTTP_CF_CONNECTING_IP',   // Cloudflare
            'HTTP_X_REAL_IP',
            'HTTP_X_FORWARDED_FOR',
            'REMOTE_ADDR',
        ];

        foreach ($candidates as $key) {
            $value = $_SERVER[$key] ?? '';
            if ($value !== '') {
                // X-Forwarded-For may be a comma-separated list; take the first
                $ip = trim(explode(',', $value)[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }

    /** @return array{0: string, 1: string|null} */
    private function parseRule(string $rule): array
    {
        if (str_contains($rule, ':')) {
            [$name, $param] = explode(':', $rule, 2);
            return [$name, $param];
        }

        return [$rule, null];
    }

    private function applyRule(
        string $field,
        mixed $value,
        string $rule,
        ?string $param,
        array $data
    ): ?string {
        $label = ucfirst(str_replace('_', ' ', $field));

        return match ($rule) {
            'required'     => ($value === null || $value === '')
                                ? "{$label} is required."
                                : null,
            'email'        => ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL))
                                ? "{$label} must be a valid email address."
                                : null,
            'url'          => ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_URL))
                                ? "{$label} must be a valid URL."
                                : null,
            'numeric'      => ($value !== null && $value !== '' && !is_numeric($value))
                                ? "{$label} must be numeric."
                                : null,
            'alpha'        => ($value !== null && $value !== '' && !ctype_alpha((string) $value))
                                ? "{$label} may only contain letters."
                                : null,
            'alphanumeric' => ($value !== null && $value !== '' && !ctype_alnum((string) $value))
                                ? "{$label} may only contain letters and numbers."
                                : null,
            'min'          => ($value !== null && $value !== '' && $param !== null && strlen((string) $value) < (int) $param)
                                ? "{$label} must be at least {$param} characters."
                                : null,
            'max'          => ($value !== null && $value !== '' && $param !== null && strlen((string) $value) > (int) $param)
                                ? "{$label} may not exceed {$param} characters."
                                : null,
            'in'           => ($value !== null && $value !== '' && $param !== null && !in_array($value, explode(',', $param), true))
                                ? "{$label} must be one of: {$param}."
                                : null,
            'confirmed'    => ($value !== ($data["{$field}_confirmation"] ?? null))
                                ? "{$label} confirmation does not match."
                                : null,
            default        => null,
        };
    }
}
