<?php

declare(strict_types=1);

return [
    'name'            => $_ENV['APP_NAME']        ?? getenv('APP_NAME')        ?: 'Mail Forge',
    'env'             => $_ENV['APP_ENV']         ?? getenv('APP_ENV')         ?: 'production',
    'debug'           => filter_var($_ENV['APP_DEBUG'] ?? getenv('APP_DEBUG') ?: false, FILTER_VALIDATE_BOOLEAN),
    'url'             => $_ENV['APP_URL']         ?? getenv('APP_URL')         ?: 'http://localhost',
    'key'             => $_ENV['APP_KEY']         ?? getenv('APP_KEY')         ?: '',
    'force_https'     => filter_var($_ENV['FORCE_HTTPS'] ?? getenv('FORCE_HTTPS') ?: false, FILTER_VALIDATE_BOOLEAN),
    'timezone'        => $_ENV['DEFAULT_TIMEZONE'] ?? getenv('DEFAULT_TIMEZONE') ?: 'UTC',
    'locale'          => $_ENV['DEFAULT_LANGUAGE'] ?? getenv('DEFAULT_LANGUAGE') ?: 'en',
    'currency'        => $_ENV['DEFAULT_CURRENCY'] ?? getenv('DEFAULT_CURRENCY') ?: 'USD',
    'company_name'    => $_ENV['COMPANY_NAME']    ?? getenv('COMPANY_NAME')    ?: 'Your Company',
    'company_email'   => $_ENV['COMPANY_EMAIL']   ?? getenv('COMPANY_EMAIL')   ?: 'admin@example.com',
    'session_lifetime'=> (int) ($_ENV['SESSION_LIFETIME'] ?? getenv('SESSION_LIFETIME') ?: 120),
    'tracking_opens'  => filter_var($_ENV['TRACKING_OPENS']  ?? getenv('TRACKING_OPENS')  ?: true,  FILTER_VALIDATE_BOOLEAN),
    'tracking_clicks' => filter_var($_ENV['TRACKING_CLICKS'] ?? getenv('TRACKING_CLICKS') ?: true,  FILTER_VALIDATE_BOOLEAN),
    'double_optin'    => filter_var($_ENV['DOUBLE_OPTIN']    ?? getenv('DOUBLE_OPTIN')    ?: true,  FILTER_VALIDATE_BOOLEAN),
];
