<?php

declare(strict_types=1);

namespace MailForge\Middleware;

use Closure;
use MailForge\Core\Request;
use MailForge\Helpers\CsrfHelper;

class CsrfMiddleware
{
    /** HTTP methods that do not require a CSRF token. */
    private const SAFE_METHODS = ['GET', 'HEAD', 'OPTIONS'];

    /**
     * Handle the incoming request.
     *
     * Safe HTTP methods (GET, HEAD, OPTIONS) pass straight through.
     * All other methods require a valid CSRF token either in the request
     * body (field: _csrf_token) or in the X-CSRF-Token header.
     *
     * On failure:
     *  - JSON / AJAX requests receive a 403 JSON response.
     *  - All other requests are redirected back to the previous page.
     */
    public function handle(Request $request, Closure $next): void
    {
        if ($this->isSafeMethod($request->method)) {
            $next($request);
            return;
        }

        if ($this->isExcluded($request->uri)) {
            $next($request);
            return;
        }

        $token = $this->extractToken($request);

        if ($token === null || !CsrfHelper::validate($token)) {
            $this->fail($request);
            return;
        }

        $next($request);
    }

    // ─── Private helpers ──────────────────────────────────────────────────

    private function isSafeMethod(string $method): bool
    {
        return in_array(strtoupper($method), self::SAFE_METHODS, true);
    }

    /**
     * Allow specific URI prefixes to skip CSRF checks (e.g. webhook endpoints).
     */
    private function isExcluded(string $uri): bool
    {
        $excluded = [
            '/api/webhooks/',
            '/api/v1/',
        ];

        foreach ($excluded as $prefix) {
            if (str_starts_with($uri, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function extractToken(Request $request): ?string
    {
        // Check form body first
        $fromBody = $request->body[CsrfHelper::fieldName()] ?? null;
        if (is_string($fromBody) && $fromBody !== '') {
            return $fromBody;
        }

        // Fall back to request header (AJAX)
        $fromHeader = $request->headers[CsrfHelper::headerName()] ?? null;
        if (is_string($fromHeader) && $fromHeader !== '') {
            return $fromHeader;
        }

        return null;
    }

    private function fail(Request $request): never
    {
        if ($request->expectsJson()) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'error'   => 'CSRF token mismatch.',
                'status'  => 403,
            ]);
            exit(0);
        }

        // For regular form submissions redirect back with a flash error
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['_flash']['error'] = 'Your session has expired. Please try again.';
        }

        $referer = $request->headers['Referer'] ?? '/';
        http_response_code(302);
        header("Location: {$referer}");
        exit(0);
    }
}
