<?php
// Migration gets $db (PDO) and $prefix (string) injected by MigrationRunner

$tableName = $prefix . 'users';

$db->exec("CREATE TABLE IF NOT EXISTS `{$tableName}` (
    `id`                        BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `uuid`                      CHAR(36) NOT NULL,
    `first_name`                VARCHAR(100) NOT NULL,
    `last_name`                 VARCHAR(100) NOT NULL,
    `email`                     VARCHAR(191) NOT NULL,
    `password`                  VARCHAR(255) NOT NULL,
    `status`                    ENUM('active','inactive','suspended') NOT NULL DEFAULT 'active',
    `email_verified_at`         TIMESTAMP NULL DEFAULT NULL,
    `last_login_at`             TIMESTAMP NULL DEFAULT NULL,
    `last_login_ip`             VARCHAR(45) NULL DEFAULT NULL,
    `failed_login_attempts`     INT NOT NULL DEFAULT 0,
    `locked_until`              TIMESTAMP NULL DEFAULT NULL,
    `remember_token`            VARCHAR(100) NULL DEFAULT NULL,
    `password_reset_token`      VARCHAR(100) NULL DEFAULT NULL,
    `password_reset_expires_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at`                TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`                TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`                TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_{$tableName}_uuid` (`uuid`),
    UNIQUE KEY `uq_{$tableName}_email` (`email`),
    KEY `idx_{$tableName}_status` (`status`),
    KEY `idx_{$tableName}_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
