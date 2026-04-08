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

// Bootstrap application
$app = require_once MAIL_FORGE_ROOT . '/bootstrap/app.php';

use MailForge\Middleware\CsrfMiddleware;
use MailForge\Core\Request;
use MailForge\Core\Session;

// Start session
Session::start();

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
