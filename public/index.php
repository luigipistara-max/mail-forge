<?php

define('MAIL_FORGE_ROOT', dirname(__DIR__));
define('APP_START', microtime(true));

// Detect base path for subdirectory installations
// public/index.php is one level deeper than the app root
if (!defined('BASE_PATH')) {
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $detectedBase = rtrim(dirname(dirname($scriptName)), '/\\');
    define('BASE_PATH', $detectedBase === '.' ? '' : $detectedBase);
}

// Redirect to installer if not yet installed
if (!file_exists(MAIL_FORGE_ROOT . '/storage/install.lock') && !file_exists(MAIL_FORGE_ROOT . '/.env')) {
    header('Location: ' . BASE_PATH . '/install/');
    exit;
}

use MailForge\Middleware\CsrfMiddleware;
use MailForge\Core\Request;

try {
    // Bootstrap application (also starts the session)
    $app = require_once MAIL_FORGE_ROOT . '/bootstrap/app.php';

    // Force HTTPS if configured
    if (getenv('FORCE_HTTPS') === 'true' && !isset($_SERVER['HTTPS'])) {
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        header("Location: https://{$host}{$uri}", true, 301);
        exit;
    }

    // Build request object
    $request = Request::make();

    // Apply CSRF middleware for non-GET requests
    $csrf = new CsrfMiddleware();
    $csrf->handle($request, function() {});

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
