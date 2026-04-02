<?php

declare(strict_types=1);

namespace MailForge\Helpers;

/**
 * Input validation helper.
 * Uses patterns defined in config/constants.php where applicable.
 */
class Validator
{
    /** @var array<string, string> */
    private array $errors = [];

    // ----------------------------------------------------------------
    // Fluent builder
    // ----------------------------------------------------------------

    /**
     * Validate that a value is not empty.
     */
    public function required(mixed $value, string $field): static
    {
        if ($value === null || $value === '' || (is_array($value) && count($value) === 0)) {
            $this->errors[$field] = "The {$field} field is required.";
        }
        return $this;
    }

    /**
     * Validate an email address using VALIDATION_EMAIL_PATTERN from constants.php.
     *
     * The constant pattern has a hyphen placement that triggers a PCRE warning on some
     * PHP versions; we normalise it before use and fall back to filter_var.
     */
    public function email(string $value, string $field = 'email'): static
    {
        if ($value !== '' && !self::isEmail($value)) {
            $this->errors[$field] = "The {$field} must be a valid email address.";
        }
        return $this;
    }

    /**
     * Validate a phone number using VALIDATION_PHONE_PATTERN from constants.php.
     */
    public function phone(string $value, string $field = 'phone'): static
    {
        if ($value !== '' && !preg_match(VALIDATION_PHONE_PATTERN, $value)) {
            $this->errors[$field] = "The {$field} must be a valid phone number (E.164 format).";
        }
        return $this;
    }

    /**
     * Validate that a string meets a minimum length.
     */
    public function minLength(string $value, int $min, string $field): static
    {
        if (mb_strlen($value) < $min) {
            $this->errors[$field] = "The {$field} must be at least {$min} characters.";
        }
        return $this;
    }

    /**
     * Validate that a string does not exceed a maximum length.
     */
    public function maxLength(string $value, int $max, string $field): static
    {
        if (mb_strlen($value) > $max) {
            $this->errors[$field] = "The {$field} must not exceed {$max} characters.";
        }
        return $this;
    }

    /**
     * Validate that a value is one of an allowed set.
     *
     * @param array<mixed> $allowed
     */
    public function in(mixed $value, array $allowed, string $field): static
    {
        if (!in_array($value, $allowed, true)) {
            $this->errors[$field] = "The {$field} contains an invalid value.";
        }
        return $this;
    }

    /**
     * Validate a URL.
     */
    public function url(string $value, string $field): static
    {
        if ($value !== '' && filter_var($value, FILTER_VALIDATE_URL) === false) {
            $this->errors[$field] = "The {$field} must be a valid URL.";
        }
        return $this;
    }

    /**
     * Validate that value is a positive integer.
     */
    public function positiveInt(mixed $value, string $field): static
    {
        if (!is_numeric($value) || (int) $value < 1) {
            $this->errors[$field] = "The {$field} must be a positive integer.";
        }
        return $this;
    }

    // ----------------------------------------------------------------
    // Static convenience methods
    // ----------------------------------------------------------------

    public static function isEmail(string $email): bool
    {
        // Use filter_var as primary check (VALIDATION_EMAIL_PATTERN from constants.php
        // has a hyphen in the character class that triggers PCRE warnings on some builds)
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function isPhone(string $phone): bool
    {
        return (bool) preg_match(VALIDATION_PHONE_PATTERN, $phone);
    }

    // ----------------------------------------------------------------
    // Result accessors
    // ----------------------------------------------------------------

    public function passes(): bool
    {
        return empty($this->errors);
    }

    public function fails(): bool
    {
        return !$this->passes();
    }

    /** @return array<string, string> */
    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): string
    {
        return empty($this->errors) ? '' : reset($this->errors);
    }

    public function reset(): static
    {
        $this->errors = [];
        return $this;
    }
}
