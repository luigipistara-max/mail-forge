<?php

declare(strict_types=1);

// ─── Autoloader ──────────────────────────────────────────────────────────────

$autoloader = __DIR__ . '/../vendor/autoload.php';

if (!file_exists($autoloader)) {
    http_response_code(503);
    echo 'Dependencies not installed. Run <code>composer install</code>.';
    exit(1);
}

require_once $autoloader;

// ─── Environment ─────────────────────────────────────────────────────────────

$envFile = __DIR__ . '/../.env';

if (file_exists($envFile)) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}

// ─── Error reporting ─────────────────────────────────────────────────────────

$debug = filter_var($_ENV['APP_DEBUG'] ?? getenv('APP_DEBUG') ?: false, FILTER_VALIDATE_BOOLEAN);

if ($debug) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../storage/logs/php_errors.log');

// ─── Timezone ────────────────────────────────────────────────────────────────

$timezone = $_ENV['DEFAULT_TIMEZONE'] ?? getenv('DEFAULT_TIMEZONE') ?: 'UTC';
date_default_timezone_set($timezone);

// ─── Session ─────────────────────────────────────────────────────────────────

if (session_status() === PHP_SESSION_NONE) {
    $sessionLifetime = (int) ($_ENV['SESSION_LIFETIME'] ?? getenv('SESSION_LIFETIME') ?: 120);
    $sessionSecure   = filter_var($_ENV['SESSION_SECURE'] ?? getenv('SESSION_SECURE') ?: false, FILTER_VALIDATE_BOOLEAN);
    $sessionHttpOnly = filter_var($_ENV['SESSION_HTTPONLY'] ?? getenv('SESSION_HTTPONLY') ?: true, FILTER_VALIDATE_BOOLEAN);
    $sessionSameSite = $_ENV['SESSION_SAMESITE'] ?? getenv('SESSION_SAMESITE') ?: 'Lax';

    session_set_cookie_params([
        'lifetime' => $sessionLifetime * 60,
        'path'     => '/',
        'secure'   => $sessionSecure,
        'httponly' => $sessionHttpOnly,
        'samesite' => $sessionSameSite,
    ]);

    session_start();
}

// ─── Constants ───────────────────────────────────────────────────────────────

$constantsFile = __DIR__ . '/../config/constants.php';
if (file_exists($constantsFile)) {
    require_once $constantsFile;
}

// ─── Application array ───────────────────────────────────────────────────────

$app = [
    'name'          => $_ENV['APP_NAME']      ?? getenv('APP_NAME')      ?: 'Mail Forge',
    'env'           => $_ENV['APP_ENV']       ?? getenv('APP_ENV')       ?: 'production',
    'debug'         => $debug,
    'url'           => $_ENV['APP_URL']       ?? getenv('APP_URL')       ?: 'http://localhost',
    'key'           => $_ENV['APP_KEY']       ?? getenv('APP_KEY')       ?: '',
    'force_https'   => filter_var($_ENV['FORCE_HTTPS'] ?? getenv('FORCE_HTTPS') ?: false, FILTER_VALIDATE_BOOLEAN),
    'timezone'      => $timezone,
    'locale'        => $_ENV['DEFAULT_LANGUAGE'] ?? getenv('DEFAULT_LANGUAGE') ?: 'en',
    'currency'      => $_ENV['DEFAULT_CURRENCY'] ?? getenv('DEFAULT_CURRENCY') ?: 'USD',
    'company_name'  => $_ENV['COMPANY_NAME']  ?? getenv('COMPANY_NAME')  ?: 'Your Company',
    'company_email' => $_ENV['COMPANY_EMAIL'] ?? getenv('COMPANY_EMAIL') ?: 'admin@example.com',
    'installed'     => file_exists(__DIR__ . '/../storage/install.lock'),
    'base_path'     => realpath(__DIR__ . '/..'),
];

return $app;
