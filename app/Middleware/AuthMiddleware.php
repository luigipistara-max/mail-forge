<?php

declare(strict_types=1);

namespace MailForge\Middleware;

use Closure;
use MailForge\Core\Request;
use MailForge\Core\Session;

class AuthMiddleware
{
    private const LOGIN_PATH = '/login';

    private Session $session;

    public function __construct()
    {
        $this->session = new Session();

        if (session_status() !== PHP_SESSION_ACTIVE) {
            $this->session->start();
        }
    }

    /**
     * Handle the incoming request.
     *
     * If the user is not authenticated the request is redirected to /login.
     * If a required role is specified and the user does not hold it, a 403
     * response is returned.
     *
     * @param Request        $request
     * @param Closure        $next     The next middleware / controller action.
     * @param string|null    $role     Optional role the user must hold.
     */
    public function handle(Request $request, Closure $next, ?string $role = null): void
    {
        if (!$this->session->isLoggedIn()) {
            $this->session->flash('intended_url', $request->uri);
            $this->redirectToLogin($request);
            return;
        }

        if ($role !== null && !$this->userHasRole($role)) {
            $this->forbidden($request);
            return;
        }

        $next($request);
    }

    // ─── Private helpers ──────────────────────────────────────────────────

    private function userHasRole(string $requiredRole): bool
    {
        $user = $this->session->getUser();

        if ($user === null) {
            return false;
        }

        $userRole = (string) ($user['role'] ?? '');

        // Support both exact match and a comma-separated list of allowed roles
        $allowed = array_map('trim', explode(',', $requiredRole));

        return in_array($userRole, $allowed, true);
    }

    private function redirectToLogin(Request $request): never
    {
        http_response_code(302);
        header('Location: ' . self::LOGIN_PATH);
        exit(0);
    }

    private function forbidden(Request $request): never
    {
        if ($request->expectsJson()) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Forbidden', 'status' => 403]);
            exit(0);
        }

        http_response_code(403);
        header('Content-Type: text/html; charset=UTF-8');

        $errorView = dirname(__DIR__, 2) . '/resources/views/errors/403.php';

        if (file_exists($errorView)) {
            require $errorView;
        } else {
            echo '<h1>403 Forbidden</h1><p>You do not have permission to access this page.</p>';
        }

        exit(0);
    }
}
