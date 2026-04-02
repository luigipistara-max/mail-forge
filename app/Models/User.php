<?php

declare(strict_types=1);

namespace MailForge\Models;

use MailForge\Core\Database;

class User extends BaseModel
{
    protected static string $table = 'users';
    protected static bool $softDelete = true;
    protected static array $fillable = [
        'email',
        'password_hash',
        'first_name',
        'last_name',
        'role',
        'status',
        'failed_login_attempts',
        'locked_until',
        'last_login_at',
        'last_login_ip',
        'password_reset_token',
        'password_reset_expires_at',
        'double_optin_token',
        'double_optin_expires_at',
    ];

    public function findByEmail(string $email): ?array
    {
        $table = $this->getTable();
        $sql   = "SELECT * FROM `{$table}` WHERE `email` = :email AND `deleted_at` IS NULL LIMIT 1";

        $stmt = $this->executeQuery($sql, [':email' => $email]);
        $row  = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    public function findActive(): array
    {
        $table = $this->getTable();
        $sql   = "SELECT * FROM `{$table}` WHERE `status` = 'active' AND `deleted_at` IS NULL ORDER BY `created_at` DESC";

        return $this->executeQuery($sql)->fetchAll();
    }

    public function verifyPassword(string $plain, string $hash): bool
    {
        return password_verify($plain, $hash);
    }

    public function getRoles(int|string $userId): array
    {
        $rolesTable = Database::getPrefix() . 'user_roles';
        $sql        = "SELECT `role` FROM `{$rolesTable}` WHERE `user_id` = :user_id";

        $stmt = $this->executeQuery($sql, [':user_id' => $userId]);

        return array_column($stmt->fetchAll(), 'role');
    }

    public function hasRole(int|string $userId, string $role): bool
    {
        $user = $this->find($userId);

        if ($user === null) {
            return false;
        }

        // Support both a dedicated role column and a roles table.
        if (isset($user['role']) && $user['role'] === $role) {
            return true;
        }

        return in_array($role, $this->getRoles($userId), true);
    }

    public function updateLastLogin(int|string $userId, string $ip): bool
    {
        $table = $this->getTable();
        $sql   = "UPDATE `{$table}` SET `last_login_at` = NOW(), `last_login_ip` = :ip WHERE `id` = :id";

        $stmt = $this->executeQuery($sql, [':ip' => $ip, ':id' => $userId]);

        return $stmt->rowCount() > 0;
    }

    public function incrementFailedLogins(int|string $userId): bool
    {
        $table = $this->getTable();
        $sql   = "UPDATE `{$table}` SET `failed_login_attempts` = `failed_login_attempts` + 1 WHERE `id` = :id";

        $stmt = $this->executeQuery($sql, [':id' => $userId]);

        return $stmt->rowCount() > 0;
    }

    public function resetFailedLogins(int|string $userId): bool
    {
        $table = $this->getTable();
        $sql   = "UPDATE `{$table}` SET `failed_login_attempts` = 0, `locked_until` = NULL WHERE `id` = :id";

        $stmt = $this->executeQuery($sql, [':id' => $userId]);

        return $stmt->rowCount() > 0;
    }

    public function isLocked(int|string $userId): bool
    {
        $table = $this->getTable();
        $sql   = "SELECT `locked_until` FROM `{$table}` WHERE `id` = :id LIMIT 1";

        $stmt = $this->executeQuery($sql, [':id' => $userId]);
        $row  = $stmt->fetch();

        if ($row === false || $row['locked_until'] === null) {
            return false;
        }

        return strtotime($row['locked_until']) > time();
    }

    public function setPasswordResetToken(int|string $userId, string $token, string $expiresAt): bool
    {
        $table = $this->getTable();
        $sql   = "UPDATE `{$table}` SET `password_reset_token` = :token, `password_reset_expires_at` = :expires_at WHERE `id` = :id";

        $stmt = $this->executeQuery($sql, [
            ':token'      => $token,
            ':expires_at' => $expiresAt,
            ':id'         => $userId,
        ]);

        return $stmt->rowCount() > 0;
    }

    public function clearPasswordResetToken(int|string $userId): bool
    {
        $table = $this->getTable();
        $sql   = "UPDATE `{$table}` SET `password_reset_token` = NULL, `password_reset_expires_at` = NULL WHERE `id` = :id";

        $stmt = $this->executeQuery($sql, [':id' => $userId]);

        return $stmt->rowCount() > 0;
    }

    public function findByResetToken(string $token): ?array
    {
        $table = $this->getTable();
        $sql   = "SELECT * FROM `{$table}` WHERE `password_reset_token` = :token AND `password_reset_expires_at` > NOW() AND `deleted_at` IS NULL LIMIT 1";

        $stmt = $this->executeQuery($sql, [':token' => $token]);
        $row  = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    public function findByDoubleOptinToken(string $token): ?array
    {
        $table = $this->getTable();
        $sql   = "SELECT * FROM `{$table}` WHERE `double_optin_token` = :token AND `deleted_at` IS NULL LIMIT 1";

        $stmt = $this->executeQuery($sql, [':token' => $token]);
        $row  = $stmt->fetch();

        return $row !== false ? $row : null;
    }
}
