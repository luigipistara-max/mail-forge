<?php

define('MAIL_FORGE_ROOT', dirname(__DIR__));
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
