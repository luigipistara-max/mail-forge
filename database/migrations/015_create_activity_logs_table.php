<?php
// Migration gets $db (PDO) and $prefix (string) injected by MigrationRunner

$tableName  = $prefix . 'activity_logs';
$usersTable = $prefix . 'users';

$db->exec("CREATE TABLE IF NOT EXISTS `{$tableName}` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`     BIGINT UNSIGNED NULL DEFAULT NULL,
    `action`      VARCHAR(100) NOT NULL,
    `entity_type` VARCHAR(100) NULL DEFAULT NULL,
    `entity_id`   BIGINT UNSIGNED NULL DEFAULT NULL,
    `description` TEXT NULL DEFAULT NULL,
    `ip`          VARCHAR(45) NULL DEFAULT NULL,
    `user_agent`  TEXT NULL DEFAULT NULL,
    `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_{$tableName}_user_id` (`user_id`),
    KEY `idx_{$tableName}_action` (`action`),
    KEY `idx_{$tableName}_entity_type` (`entity_type`),
    KEY `idx_{$tableName}_entity_id` (`entity_id`),
    KEY `idx_{$tableName}_entity_type_id` (`entity_type`, `entity_id`),
    CONSTRAINT `fk_{$tableName}_user_id`
        FOREIGN KEY (`user_id`) REFERENCES `{$usersTable}` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
