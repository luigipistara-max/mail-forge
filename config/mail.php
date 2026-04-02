<?php

declare(strict_types=1);

/**
 * Mail configuration
 */

require_once __DIR__ . '/../src/Helpers/EnvLoader.php';

use MailForge\Helpers\EnvLoader;

if (!getenv('MAIL_HOST')) {
    $envFile = file_exists(__DIR__ . '/../.env')
        ? __DIR__ . '/../.env'
        : __DIR__ . '/../.env.example';

    EnvLoader::load($envFile);
}

/** @var array<string, mixed> $mailConfig */
$mailConfig = [
    // SMTP connection
    'driver'     => getenv('MAIL_DRIVER')     ?: 'smtp',
    'host'       => getenv('MAIL_HOST')       ?: 'localhost',
    'port'       => (int) (getenv('MAIL_PORT') ?: 587),
    'username'   => getenv('MAIL_USERNAME')   ?: '',
    'password'   => getenv('MAIL_PASSWORD')   ?: '',
    'encryption' => getenv('MAIL_ENCRYPTION') ?: 'tls',

    // Sender defaults
    'from_name'  => getenv('MAIL_FROM_NAME')  ?: 'MailForge',
    'from_email' => getenv('MAIL_FROM_EMAIL') ?: 'noreply@mailforge.local',

    // Rate limiting
    'rate_limit' => [
        'max_per_hour' => (int) (getenv('MAIL_MAX_PER_HOUR') ?: 500),
        'max_per_day'  => (int) (getenv('MAIL_MAX_PER_DAY')  ?: 5000),
    ],

    // Retry policy
    'retry' => [
        'max_retries'    => 3,
        'delay_seconds'  => 60,
    ],
];
