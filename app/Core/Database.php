<?php

declare(strict_types=1);

namespace MailForge\Core;

use PDO;
use PDOException;
use RuntimeException;

class Database
{
    private static ?Database $instance = null;
    private PDO $pdo;
    private string $prefix;

    private function __construct()
    {
        $config = $this->loadConfig();
        $this->prefix = (string) ($config['prefix'] ?? 'mf_');
        $this->pdo = $this->createConnection($config);
    }

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance->pdo;
    }

    public static function getPrefix(): string
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance->prefix;
    }

    public static function table(string $name): string
    {
        return self::getPrefix() . $name;
    }

    private function loadConfig(): array
    {
        $configPath = dirname(__DIR__, 2) . '/config/database.php';

        if (!file_exists($configPath)) {
            throw new RuntimeException("Database config file not found: {$configPath}");
        }

        $config = require $configPath;

        if (!is_array($config)) {
            throw new RuntimeException('Database config must return an array.');
        }

        return $config;
    }

    private function createConnection(array $config): PDO
    {
        $dsn = $this->buildDsn($config);

        $username = (string) ($config['username'] ?? 'root');
        $password = (string) ($config['password'] ?? '');
        $options  = $config['options'] ?? [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, $username, $password, $options);

            $charset   = (string) ($config['charset']   ?? 'utf8mb4');
            $collation = (string) ($config['collation'] ?? 'utf8mb4_unicode_ci');
            $pdo->exec("SET NAMES '{$charset}' COLLATE '{$collation}'");

            return $pdo;
        } catch (PDOException $e) {
            throw new RuntimeException(
                'Database connection failed: ' . $e->getMessage(),
                (int) $e->getCode(),
                $e
            );
        }
    }

    private function buildDsn(array $config): string
    {
        $host     = (string) ($config['host']     ?? '127.0.0.1');
        $port     = (int)    ($config['port']     ?? 3306);
        $database = (string) ($config['database'] ?? 'mailforge');
        $charset  = (string) ($config['charset']  ?? 'utf8mb4');

        return "mysql:host={$host};port={$port};dbname={$database};charset={$charset}";
    }

    /** Prevent cloning of the singleton instance. */
    private function __clone() {}
}
