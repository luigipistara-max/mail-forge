<?php

declare(strict_types=1);

namespace MailForge\Validators;

use MailForge\Core\Database;
use PDO;
use RuntimeException;

class Validator
{
    /** @var array<string, mixed> */
    private array $data;

    /** @var array<string, string> */
    private array $rules;

    /** @var array<string, string[]> */
    private array $errorMessages = [];

    private bool $validated = false;

    private function __construct(array $data, array $rules)
    {
        $this->data  = $data;
        $this->rules = $rules;
    }

    // ─── Factory ──────────────────────────────────────────────────────────

    /**
     * Create a new Validator instance.
     *
     * @param array<string, mixed>  $data  The data to validate.
     * @param array<string, string> $rules Rule strings, e.g. ['email' => 'required|email|unique:users:email'].
     */
    public static function make(array $data, array $rules): static
    {
        return new static($data, $rules);
    }

    // ─── Run validation ───────────────────────────────────────────────────

    /**
     * Run all rules and return true if every field passes.
     */
    public function validate(): bool
    {
        $this->errorMessages = [];

        foreach ($this->rules as $field => $ruleString) {
            $fieldRules = explode('|', $ruleString);
            $value      = $this->data[$field] ?? null;

            foreach ($fieldRules as $rule) {
                [$ruleName, $ruleParam] = $this->parseRule($rule);

                $error = $this->applyRule($field, $value, $ruleName, $ruleParam);

                if ($error !== null) {
                    $this->errorMessages[$field][] = $error;

                    // Stop evaluating further rules for this field after
                    // a required failure — prevents misleading follow-up errors
                    if ($ruleName === 'required') {
                        break;
                    }
                }
            }
        }

        $this->validated = true;

        return empty($this->errorMessages);
    }

    // ─── Error access ─────────────────────────────────────────────────────

    /**
     * @return array<string, string[]>
     */
    public function errors(): array
    {
        if (!$this->validated) {
            $this->validate();
        }

        return $this->errorMessages;
    }

    /**
     * Return the first error message for a given field, or null.
     */
    public function firstError(string $field): ?string
    {
        return $this->errors()[$field][0] ?? null;
    }

    /**
     * Return a flat list of all error messages.
     *
     * @return string[]
     */
    public function allErrors(): array
    {
        $flat = [];

        foreach ($this->errors() as $messages) {
            foreach ($messages as $message) {
                $flat[] = $message;
            }
        }

        return $flat;
    }

    public function fails(): bool
    {
        return !$this->validate();
    }

    // ─── Private rule engine ─────────────────────────────────────────────

    /** @return array{0: string, 1: string|null} */
    private function parseRule(string $rule): array
    {
        if (str_contains($rule, ':')) {
            [$name, $param] = explode(':', $rule, 2);
            return [trim($name), trim($param)];
        }

        return [trim($rule), null];
    }

    private function applyRule(
        string $field,
        mixed $value,
        string $rule,
        ?string $param
    ): ?string {
        $label = ucfirst(str_replace('_', ' ', $field));

        // Determine if the field has a meaningful value
        $hasValue = $value !== null && $value !== '';

        return match ($rule) {
            'required' => (!$hasValue)
                ? "{$label} is required."
                : null,

            'email' => ($hasValue && !filter_var($value, FILTER_VALIDATE_EMAIL))
                ? "{$label} must be a valid email address."
                : null,

            'url' => ($hasValue && !filter_var($value, FILTER_VALIDATE_URL))
                ? "{$label} must be a valid URL."
                : null,

            'numeric' => ($hasValue && !is_numeric($value))
                ? "{$label} must be numeric."
                : null,

            'alpha' => ($hasValue && !ctype_alpha((string) $value))
                ? "{$label} may only contain letters."
                : null,

            'alphanumeric' => ($hasValue && !ctype_alnum((string) $value))
                ? "{$label} may only contain letters and numbers."
                : null,

            'min' => ($hasValue && $param !== null && strlen((string) $value) < (int) $param)
                ? "{$label} must be at least {$param} characters."
                : null,

            'max' => ($hasValue && $param !== null && strlen((string) $value) > (int) $param)
                ? "{$label} may not exceed {$param} characters."
                : null,

            'between' => $this->validateBetween($label, $value, $param, $hasValue),

            'in' => ($hasValue && $param !== null && !in_array((string) $value, explode(',', $param), true))
                ? "{$label} must be one of: {$param}."
                : null,

            'confirmed' => ($value !== ($this->data["{$field}_confirmation"] ?? null))
                ? "{$label} confirmation does not match."
                : null,

            'unique' => $this->validateUnique($label, $value, $param, $hasValue),

            'regex' => $this->validateRegex($label, $value, $param, $hasValue),

            default => null,
        };
    }

    private function validateBetween(string $label, mixed $value, ?string $param, bool $hasValue): ?string
    {
        if (!$hasValue || $param === null) {
            return null;
        }

        $parts = explode(',', $param, 2);

        if (count($parts) !== 2) {
            throw new RuntimeException("between rule requires two comma-separated values, got: '{$param}'");
        }

        $min = (int) trim($parts[0]);
        $max = (int) trim($parts[1]);
        $len = strlen((string) $value);

        if ($len < $min || $len > $max) {
            return "{$label} must be between {$min} and {$max} characters.";
        }

        return null;
    }

    /**
     * Validate that a value is unique in the database.
     *
     * Param format: "table:column" or "table:column:exceptId".
     */
    private function validateUnique(string $label, mixed $value, ?string $param, bool $hasValue): ?string
    {
        if (!$hasValue || $param === null) {
            return null;
        }

        $parts    = explode(':', $param);
        $table    = Database::table($parts[0] ?? '');
        $column   = $parts[1] ?? 'id';
        $exceptId = isset($parts[2]) ? (int) $parts[2] : null;

        if ($table === '' || $column === '') {
            throw new RuntimeException("unique rule requires 'table:column' param, got: '{$param}'");
        }

        try {
            $pdo = Database::getInstance();

            if ($exceptId !== null) {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM `{$table}` WHERE `{$column}` = ? AND id != ?");
                $stmt->execute([$value, $exceptId]);
            } else {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM `{$table}` WHERE `{$column}` = ?");
                $stmt->execute([$value]);
            }

            $count = (int) $stmt->fetchColumn();

            if ($count > 0) {
                return "{$label} has already been taken.";
            }
        } catch (\Throwable $e) {
            // If the database is unavailable during validation fail gracefully
            return "{$label} could not be verified for uniqueness.";
        }

        return null;
    }

    private function validateRegex(string $label, mixed $value, ?string $param, bool $hasValue): ?string
    {
        if (!$hasValue || $param === null) {
            return null;
        }

        $pattern = $param;

        // If the pattern is not already delimited, wrap it
        if (!preg_match('/^[^a-zA-Z0-9\s\\\\]/', $pattern)) {
            $pattern = '/' . $pattern . '/';
        }

        if (@preg_match($pattern, (string) $value) !== 1) {
            return "{$label} format is invalid.";
        }

        return null;
    }
}
