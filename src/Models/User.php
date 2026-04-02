<?php

declare(strict_types=1);

namespace MailForge\Models;

/**
 * User model
 */
class User extends BaseModel
{
    protected string $table = 'users';

    // ----------------------------------------------------------------
    // Lookup methods
    // ----------------------------------------------------------------

    /**
     * Find a user by email address.
     *
     * @return array<string, mixed>|null
     */
    public function findByEmail(string $email): ?array
    {
        return $this->findOne(['email' => strtolower(trim($email))]);
    }

    // ----------------------------------------------------------------
    // Authentication
    // ----------------------------------------------------------------

    /**
     * Verify email + password and return the user row on success.
     *
     * @return array<string, mixed>|null
     */
    public function authenticate(string $email, string $password): ?array
    {
        $user = $this->findByEmail($email);

        if ($user === null) {
            return null;
        }

        if (!$this->verifyPassword($password, $user['password_hash'])) {
            return null;
        }

        return $user;
    }

    /**
     * Hash a plain-text password using bcrypt.
     */
    public function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    /**
     * Verify a plain-text password against its bcrypt hash.
     */
    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    // ----------------------------------------------------------------
    // API keys
    // ----------------------------------------------------------------

    /**
     * Generate a secure API key, persist it, and return the plain-text token.
     */
    public function generateApiKey(int $userId): string
    {
        $apiKey = bin2hex(random_bytes(32)); // 64-char hex string
        $this->update($userId, ['api_key' => $apiKey]);
        return $apiKey;
    }

    /**
     * Find a user by their API key.
     *
     * @return array<string, mixed>|null
     */
    public function findByApiKey(string $apiKey): ?array
    {
        return $this->findOne(['api_key' => $apiKey]);
    }

    // ----------------------------------------------------------------
    // Relationships (return related model instances / data)
    // ----------------------------------------------------------------

    /**
     * Return all campaigns owned by the user.
     *
     * @return array<int, array<string, mixed>>
     */
    public function campaigns(int $userId): array
    {
        return (new Campaign())->findAll(['user_id' => $userId]);
    }

    /**
     * Return all contacts owned by the user.
     *
     * @return array<int, array<string, mixed>>
     */
    public function contacts(int $userId): array
    {
        return (new Contact())->findAll(['user_id' => $userId]);
    }

    /**
     * Return all mailing lists owned by the user.
     *
     * @return array<int, array<string, mixed>>
     */
    public function lists(int $userId): array
    {
        return (new MailingList())->findAll(['user_id' => $userId]);
    }

    // ----------------------------------------------------------------
    // Override create to auto-hash password when provided as plain text
    // ----------------------------------------------------------------

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): int
    {
        if (isset($data['password'])) {
            $data['password_hash'] = $this->hashPassword($data['password']);
            unset($data['password']);
        }

        if (isset($data['email'])) {
            $data['email'] = strtolower(trim($data['email']));
        }

        return parent::create($data);
    }
}
