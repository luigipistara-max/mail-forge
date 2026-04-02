<?php
// Migration gets $db (PDO) and $prefix (string) injected by MigrationRunner

$rolesTable     = $prefix . 'roles';
$userRolesTable = $prefix . 'user_roles';
$usersTable     = $prefix . 'users';

$db->exec("CREATE TABLE IF NOT EXISTS `{$rolesTable}` (
    `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`         VARCHAR(50) NOT NULL,
    `display_name` VARCHAR(100) NOT NULL,
    `description`  TEXT NULL DEFAULT NULL,
    `created_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_{$rolesTable}_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

$db->exec("CREATE TABLE IF NOT EXISTS `{$userRolesTable}` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`    BIGINT UNSIGNED NOT NULL,
    `role_id`    INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_{$userRolesTable}_user_role` (`user_id`, `role_id`),
    KEY `idx_{$userRolesTable}_role_id` (`role_id`),
    CONSTRAINT `fk_{$userRolesTable}_user_id`
        FOREIGN KEY (`user_id`) REFERENCES `{$usersTable}` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_{$userRolesTable}_role_id`
        FOREIGN KEY (`role_id`) REFERENCES `{$rolesTable}` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
