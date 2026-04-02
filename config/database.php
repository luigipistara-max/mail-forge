<?php

declare(strict_types=1);

/**
 * Database configuration
 *
 * Reads connection parameters from the .env file (or .env.example as fallback)
 * and exposes a PDO singleton via getDbConnection().
 */

require_once __DIR__ . '/../src/Helpers/EnvLoader.php';

use MailForge\Helpers\EnvLoader;

// Load environment variables if not already loaded
if (!getenv('DB_HOST')) {
    $envFile = file_exists(__DIR__ . '/../.env')
        ? __DIR__ . '/../.env'
        : __DIR__ . '/../.env.example';

    EnvLoader::load($envFile);
}

/** @var array<string, mixed> $dbConfig */
$dbConfig = [
    'driver'   => getenv('DB_CONNECTION') ?: 'mysql',
    'host'     => getenv('DB_HOST')       ?: '127.0.0.1',
    'port'     => (int) (getenv('DB_PORT') ?: 3306),
    'database' => getenv('DB_DATABASE')   ?: 'mailforge',
    'username' => getenv('DB_USERNAME')   ?: 'root',
    'password' => getenv('DB_PASSWORD')   ?: '',
    'charset'  => 'utf8mb4',
    'options'  => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ],
];

/**
 * Returns a singleton PDO connection.
 *
 * @throws \PDOException if the connection cannot be established.
 */
function getDbConnection(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        global $dbConfig;

        $dsn = sprintf(
            '%s:host=%s;port=%d;dbname=%s;charset=%s',
            $dbConfig['driver'],
            $dbConfig['host'],
            $dbConfig['port'],
            $dbConfig['database'],
            $dbConfig['charset']
        );

        $pdo = new PDO(
            $dsn,
            $dbConfig['username'],
            $dbConfig['password'],
            $dbConfig['options']
        );
    }

    return $pdo;
}
