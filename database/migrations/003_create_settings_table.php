<?php
// Migration gets $db (PDO) and $prefix (string) injected by MigrationRunner

$tableName = $prefix . 'settings';

$db->exec("CREATE TABLE IF NOT EXISTS `{$tableName}` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `key`         VARCHAR(100) NOT NULL,
    `value`       TEXT NULL DEFAULT NULL,
    `type`        ENUM('string','integer','boolean','json','text') NOT NULL DEFAULT 'string',
    `group`       VARCHAR(50) NOT NULL DEFAULT 'general',
    `description` TEXT NULL DEFAULT NULL,
    `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_{$tableName}_key` (`key`),
    KEY `idx_{$tableName}_group` (`group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
