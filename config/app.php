<?php

declare(strict_types=1);

/**
 * Application configuration
 */

require_once __DIR__ . '/../src/Helpers/EnvLoader.php';

use MailForge\Helpers\EnvLoader;

if (!getenv('APP_ENV')) {
    $envFile = file_exists(__DIR__ . '/../.env')
        ? __DIR__ . '/../.env'
        : __DIR__ . '/../.env.example';

    EnvLoader::load($envFile);
}

/** @var array<string, mixed> $appConfig */
$appConfig = [
    // Core
    'name'     => getenv('APP_NAME')     ?: 'MailForge',
    'env'      => getenv('APP_ENV')      ?: 'production',
    'debug'    => filter_var(getenv('APP_DEBUG') ?: false, FILTER_VALIDATE_BOOLEAN),
    'url'      => getenv('APP_URL')      ?: 'http://localhost',
    'timezone' => getenv('APP_TIMEZONE') ?: 'UTC',

    // Session
    'session' => [
        'name'      => 'mailforge_session',
        'lifetime'  => 7200,   // seconds
        'secure'    => false,  // set true when using HTTPS
        'http_only' => true,
        'same_site' => 'Lax',
    ],

    // File uploads
    'upload' => [
        'max_size'      => 10 * 1024 * 1024, // 10 MB in bytes
        'allowed_types' => ['image/jpeg', 'image/png', 'image/gif', 'text/csv', 'application/zip'],
        'upload_dir'    => __DIR__ . '/../storage/uploads',
    ],

    // Pagination
    'pagination' => [
        'per_page' => 25,
    ],
];

// Apply timezone
date_default_timezone_set($appConfig['timezone']);
