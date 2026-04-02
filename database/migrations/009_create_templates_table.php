<?php
// Migration gets $db (PDO) and $prefix (string) injected by MigrationRunner

$tableName  = $prefix . 'templates';
$usersTable = $prefix . 'users';

$db->exec("CREATE TABLE IF NOT EXISTS `{$tableName}` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `uuid`         CHAR(36) NOT NULL,
    `name`         VARCHAR(200) NOT NULL,
    `description`  TEXT NULL DEFAULT NULL,
    `category`     VARCHAR(100) NULL DEFAULT NULL,
    `subject`      VARCHAR(500) NULL DEFAULT NULL,
    `preheader`    VARCHAR(500) NULL DEFAULT NULL,
    `html_content` LONGTEXT NOT NULL,
    `text_content` LONGTEXT NULL DEFAULT NULL,
    `is_active`    TINYINT(1) NOT NULL DEFAULT 1,
    `created_by`   BIGINT UNSIGNED NULL DEFAULT NULL,
    `created_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`   TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_{$tableName}_uuid` (`uuid`),
    KEY `idx_{$tableName}_category` (`category`),
    KEY `idx_{$tableName}_is_active` (`is_active`),
    KEY `idx_{$tableName}_created_by` (`created_by`),
    KEY `idx_{$tableName}_deleted_at` (`deleted_at`),
    CONSTRAINT `fk_{$tableName}_created_by`
        FOREIGN KEY (`created_by`) REFERENCES `{$usersTable}` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
