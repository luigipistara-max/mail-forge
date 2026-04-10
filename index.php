<?php

/**
 * Mail Forge – Root entry point
 *
 * Allows the site to be served directly from the project root
 * (e.g. https://example.com/) without requiring the web-server's
 * document root to be pointed at public/.
 *
 * The public/index.php front-controller already resolves every path
 * relative to MAIL_FORGE_ROOT (dirname(__DIR__) from inside public/),
 * which equals the project root – so no path adjustments are needed here.
 */

define('MAIL_FORGE_ROOT', __DIR__);
define('APP_START', microtime(true));

// Redirect to installer if not yet installed
if (!file_exists(MAIL_FORGE_ROOT . '/storage/install.lock') && !file_exists(MAIL_FORGE_ROOT . '/.env')) {
    header('Location: /install/');
    exit;
}

use MailForge\Middleware\CsrfMiddleware;
use MailForge\Core\Request;

try {
    // Bootstrap application (also starts the session)
    $app = require_once MAIL_FORGE_ROOT . '/bootstrap/app.php';

    // Force HTTPS if configured
    if (getenv('FORCE_HTTPS') === 'true' && (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off')) {
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $uri  = $_SERVER['REQUEST_URI'] ?? '/';
        header("Location: https://{$host}{$uri}", true, 301);
        exit;
    }

    // Build request object
    $request = Request::make();

    // Apply CSRF middleware for non-GET requests
    $csrf = new CsrfMiddleware();
    $csrf->handle($request, function () {});

    // Load routes and dispatch
    $router = require_once MAIL_FORGE_ROOT . '/routes/web.php';
    $router->dispatch($request);
} catch (Throwable $e) {
    http_response_code(500);
    // Check debug flag directly — bootstrap may not have run yet.
    // WARNING: never set APP_DEBUG=true in production; stack traces expose
    // sensitive information such as file paths and configuration details.
    $debug = getenv('APP_DEBUG') === 'true' || ($_ENV['APP_DEBUG'] ?? '') === 'true';
    if ($debug) {
        echo '<h1>Error</h1><pre>' . htmlspecialchars($e->getMessage()) . "\n" . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    } else {
        echo '<h1>500 Internal Server Error</h1><p>Something went wrong. Enable APP_DEBUG=true in .env for details.</p>';
    }
}
