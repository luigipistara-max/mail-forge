#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * MailForge migration runner
 *
 * Usage:
 *   php database/migrate.php           # run schema only
 *   php database/migrate.php --seed    # run schema + seeders
 */

define('ROOT_DIR', dirname(__DIR__));

require_once ROOT_DIR . '/src/Helpers/EnvLoader.php';
require_once ROOT_DIR . '/config/constants.php';

use MailForge\Helpers\EnvLoader;

// ----------------------------------------------------------------
// Terminal colour helpers
// ----------------------------------------------------------------

function color(string $text, string $code): string
{
    return "\033[{$code}m{$text}\033[0m";
}

function info(string $msg): void  { echo color($msg, '36') . "\n"; }
function ok(string $msg): void    { echo color($msg, '32') . "\n"; }
function warn(string $msg): void  { echo color($msg, '33') . "\n"; }
function fail(string $msg): void  { echo color($msg, '31') . "\n"; }
function heading(string $msg): void
{
    echo "\n" . color('=== ' . $msg . ' ===', '1;34') . "\n\n";
}

// ----------------------------------------------------------------
// Bootstrap
// ----------------------------------------------------------------

heading('MailForge Migration Runner');

$envFile = file_exists(ROOT_DIR . '/.env')
    ? ROOT_DIR . '/.env'
    : ROOT_DIR . '/.env.example';

EnvLoader::load($envFile);
info("Using env file: {$envFile}");

// Load database config (defines getDbConnection())
require_once ROOT_DIR . '/config/database.php';

// ----------------------------------------------------------------
// Connect
// ----------------------------------------------------------------

try {
    $pdo = getDbConnection();
    ok('Database connection established.');
} catch (\PDOException $e) {
    fail('Connection failed: ' . $e->getMessage());
    exit(1);
}

// ----------------------------------------------------------------
// Execute schema
// ----------------------------------------------------------------

heading('Running schema.sql');

$schemaFile = ROOT_DIR . '/database/schema.sql';

if (!is_readable($schemaFile)) {
    fail("Schema file not found: {$schemaFile}");
    exit(1);
}

$sql = file_get_contents($schemaFile);

if ($sql === false) {
    fail('Could not read schema file.');
    exit(1);
}

try {
    // Split on semicolons to execute statement by statement
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        static fn (string $s) => $s !== ''
    );

    foreach ($statements as $statement) {
        $pdo->exec($statement);
    }

    ok('Schema executed successfully.');
} catch (\PDOException $e) {
    fail('Schema error: ' . $e->getMessage());
    exit(1);
}

// ----------------------------------------------------------------
// Optionally run seeders
// ----------------------------------------------------------------

$runSeed = in_array('--seed', $argv ?? [], true);

if ($runSeed) {
    heading('Running seeders');

    require_once ROOT_DIR . '/src/autoload.php';
    require_once ROOT_DIR . '/database/seeders/DatabaseSeeder.php';

    try {
        $seeder = new DatabaseSeeder();
        $seeder->run();
        ok('Seeding completed.');
    } catch (\Throwable $e) {
        fail('Seeder error: ' . $e->getMessage());
        exit(1);
    }
} else {
    warn('Skipping seeders. Run with --seed to populate demo data.');
}

heading('Done');
ok('Migration finished successfully.');
