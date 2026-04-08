<?php

declare(strict_types=1);

namespace MailForge\Helpers;

/**
 * Native .env file parser.
 *
 * Replaces vlucas/phpdotenv for environments where Composer is unavailable
 * (e.g. shared hosting like Altervista without SSH).
 *
 * Behaviour:
 *  - Reads the file line by line
 *  - Ignores blank lines and comments (# …)
 *  - Parses KEY=VALUE pairs
 *  - Strips surrounding single or double quotes from values
 *  - Populates $_ENV and calls putenv()
 *  - Does NOT override already-set environment variables (immutable behaviour)
 */
class EnvLoader
{
    /**
     * Load a .env file into the environment.
     *
     * @param string $directory  Directory that contains the .env file.
     * @param string $filename   Filename (default: .env).
     */
    public static function load(string $directory, string $filename = '.env'): void
    {
        $path = rtrim($directory, '/\\') . DIRECTORY_SEPARATOR . $filename;

        if (!file_exists($path) || !is_readable($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip comments and blank lines
            if ($line === '' || $line[0] === '#') {
                continue;
            }

            // Must contain an = sign
            $eqPos = strpos($line, '=');
            if ($eqPos === false) {
                continue;
            }

            $key   = trim(substr($line, 0, $eqPos));
            $value = trim(substr($line, $eqPos + 1));

            // Strip inline comments (value not quoted)
            if ($value !== '' && $value[0] !== '"' && $value[0] !== "'") {
                $commentPos = strpos($value, ' #');
                if ($commentPos !== false) {
                    $value = trim(substr($value, 0, $commentPos));
                }
            }

            // Strip surrounding quotes and unescape inner quotes
            if (
                strlen($value) >= 2
                && (
                    ($value[0] === '"'  && $value[-1] === '"')
                    || ($value[0] === "'" && $value[-1] === "'")
                )
            ) {
                $quote = $value[0];
                $value = substr($value, 1, -1);
                $value = str_replace('\\' . $quote, $quote, $value);
            }

            // Do not override variables already set in the environment (immutable)
            if (isset($_ENV[$key]) || getenv($key) !== false) {
                continue;
            }

            $_ENV[$key] = $value;
            putenv("{$key}={$value}");
        }
    }
}
