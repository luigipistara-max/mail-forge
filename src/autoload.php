<?php

declare(strict_types=1);

/**
 * PSR-4 autoloader for the MailForge\  namespace.
 *
 * Class to file mapping:
 *   MailForge\Models\User  -> src/Models/User.php
 *   MailForge\Helpers\Mailer -> src/Helpers/Mailer.php
 *   … etc.
 */

spl_autoload_register(static function (string $class): void {
    $prefix   = 'MailForge\\';
    $baseDir  = __DIR__ . '/';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file     = $baseDir . str_replace('\\', '/', $relative) . '.php';

    if (is_readable($file)) {
        require_once $file;
    }
});
