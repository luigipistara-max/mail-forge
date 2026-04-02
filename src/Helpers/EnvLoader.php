<?php

declare(strict_types=1);

namespace MailForge\Helpers;

/**
 * Loads key=value pairs from a .env file into the environment.
 */
class EnvLoader
{
    /**
     * Parse and load a .env file.
     *
     * Lines starting with # are treated as comments.
     * Quoted values have their surrounding quotes stripped.
     *
     * @param string $path Absolute path to the .env file.
     */
    public static function load(string $path): void
    {
        if (!is_readable($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip comments and invalid lines
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            [$name, $value] = explode('=', $line, 2);

            $name  = trim($name);
            $value = trim($value);

            // Strip surrounding quotes
            if (
                (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))
            ) {
                $value = substr($value, 1, -1);
            }

            // Only set if not already defined
            if (!array_key_exists($name, $_ENV) && getenv($name) === false) {
                putenv("{$name}={$value}");
                $_ENV[$name]    = $value;
                $_SERVER[$name] = $value;
            }
        }
    }

    /**
     * Retrieve a single environment variable with an optional default.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $value = getenv($key);
        return ($value !== false) ? $value : $default;
    }
}
