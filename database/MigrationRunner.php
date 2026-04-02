<?php

class MigrationRunner
{
    private PDO $db;
    private string $prefix;
    private string $migrationsDir;
    private string $migrationsTable;

    public function __construct(PDO $db, string $prefix)
    {
        $this->db              = $db;
        $this->prefix          = $prefix;
        $this->migrationsDir   = __DIR__ . '/migrations';
        $this->migrationsTable = $prefix . 'migrations';

        $this->ensureMigrationsTable();
    }

    /**
     * Returns sorted list of migration file paths found in the migrations directory.
     */
    public function getFiles(): array
    {
        $files = glob($this->migrationsDir . '/*.php');

        if ($files === false) {
            return [];
        }

        sort($files);

        return $files;
    }

    /**
     * Runs all pending migrations in order, skipping those already recorded.
     */
    public function run(): void
    {
        $ran = $this->getRanMigrations();

        foreach ($this->getFiles() as $filePath) {
            $migration = basename($filePath);

            if (in_array($migration, $ran, true)) {
                continue;
            }

            $this->runFile($filePath, $migration);
        }
    }

    /**
     * Drops all tables that carry the configured prefix, then clears the
     * migrations tracking table (which is itself prefixed).
     */
    public function rollback(): void
    {
        $this->db->exec('SET FOREIGN_KEY_CHECKS = 0');

        $stmt = $this->db->prepare(
            "SELECT table_name
               FROM information_schema.tables
              WHERE table_schema = DATABASE()
                AND table_name LIKE :prefix"
        );
        $stmt->execute([':prefix' => $this->prefix . '%']);
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            $this->db->exec("DROP TABLE IF EXISTS `{$table}`");
        }

        $this->db->exec('SET FOREIGN_KEY_CHECKS = 1');
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Creates the migrations tracking table if it does not already exist.
     */
    private function ensureMigrationsTable(): void
    {
        $table = $this->migrationsTable;

        $this->db->exec("CREATE TABLE IF NOT EXISTS `{$table}` (
            `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `migration`  VARCHAR(255) NOT NULL,
            `ran_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_{$table}_migration` (`migration`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    }

    /**
     * Returns the list of migration filenames that have already been executed.
     *
     * @return string[]
     */
    private function getRanMigrations(): array
    {
        $table = $this->migrationsTable;
        $stmt  = $this->db->query("SELECT `migration` FROM `{$table}`");

        return $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];
    }

    /**
     * Includes a single migration file, making $db and $prefix available to it,
     * then records it as completed.
     */
    private function runFile(string $filePath, string $migration): void
    {
        $db     = $this->db;
        $prefix = $this->prefix;

        require $filePath;

        $table = $this->migrationsTable;
        $stmt  = $this->db->prepare(
            "INSERT INTO `{$table}` (`migration`) VALUES (:migration)"
        );
        $stmt->execute([':migration' => $migration]);
    }
}
