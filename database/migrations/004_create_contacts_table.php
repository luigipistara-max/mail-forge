<?php
// Migration gets $db (PDO) and $prefix (string) injected by MigrationRunner

$tableName = $prefix . 'contacts';

$db->exec("CREATE TABLE IF NOT EXISTS `{$tableName}` (
    `id`                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `uuid`                  CHAR(36) NOT NULL,
    `email`                 VARCHAR(191) NOT NULL,
    `first_name`            VARCHAR(100) NULL DEFAULT NULL,
    `last_name`             VARCHAR(100) NULL DEFAULT NULL,
    `phone`                 VARCHAR(50) NULL DEFAULT NULL,
    `company`               VARCHAR(200) NULL DEFAULT NULL,
    `address`               TEXT NULL DEFAULT NULL,
    `city`                  VARCHAR(100) NULL DEFAULT NULL,
    `country`               VARCHAR(100) NULL DEFAULT NULL,
    `timezone`              VARCHAR(50) NULL DEFAULT NULL,
    `language`              VARCHAR(10) NULL DEFAULT NULL,
    `status`                ENUM('subscribed','unsubscribed','bounced','complained','pending','cleaned') NOT NULL DEFAULT 'pending',
    `optin_ip`              VARCHAR(45) NULL DEFAULT NULL,
    `optin_source`          VARCHAR(100) NULL DEFAULT NULL,
    `optin_confirmed_at`    TIMESTAMP NULL DEFAULT NULL,
    `double_optin_token`    VARCHAR(100) NULL DEFAULT NULL,
    `double_optin_sent_at`  TIMESTAMP NULL DEFAULT NULL,
    `unsubscribed_at`       TIMESTAMP NULL DEFAULT NULL,
    `unsubscribe_reason`    TEXT NULL DEFAULT NULL,
    `bounced_at`            TIMESTAMP NULL DEFAULT NULL,
    `bounce_type`           VARCHAR(50) NULL DEFAULT NULL,
    `complained_at`         TIMESTAMP NULL DEFAULT NULL,
    `notes`                 TEXT NULL DEFAULT NULL,
    `created_at`            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`            TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`            TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_{$tableName}_uuid` (`uuid`),
    UNIQUE KEY `uq_{$tableName}_email` (`email`),
    KEY `idx_{$tableName}_status` (`status`),
    KEY `idx_{$tableName}_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
