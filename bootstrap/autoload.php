<?php

declare(strict_types=1);

/**
 * Mail Forge – Native PSR-4 autoloader
 *
 * Used as a fallback when vendor/autoload.php (Composer) is not available,
 * e.g. on shared hosting (Altervista) without SSH access.
 *
 * Namespace → directory mappings:
 *   MailForge\         → <root>/app/
 *   PHPMailer\PHPMailer\ → <root>/lib/PHPMailer/
 */

(static function (): void {
    $root = dirname(__DIR__);

    $namespaces = [
        'PHPMailer\\PHPMailer\\' => $root . '/lib/PHPMailer/',
        'MailForge\\'            => $root . '/app/',
    ];

    spl_autoload_register(static function (string $class) use ($namespaces): void {
        foreach ($namespaces as $prefix => $baseDir) {
            if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
                continue;
            }

            $relative = substr($class, strlen($prefix));
            $file     = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relative) . '.php';

            if (file_exists($file)) {
                require_once $file;
            }

            return;
        }
    });
})();
