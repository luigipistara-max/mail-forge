<?php
// Migration gets $db (PDO) and $prefix (string) injected by MigrationRunner

$unsubscribesTable  = $prefix . 'unsubscribes';
$suppressionsTable  = $prefix . 'suppressions';
$bouncesTable       = $prefix . 'bounces';
$complaintsTable    = $prefix . 'complaints';
$contactsTable      = $prefix . 'contacts';
$campaignsTable     = $prefix . 'campaigns';
$listsTable         = $prefix . 'lists';

$db->exec("CREATE TABLE IF NOT EXISTS `{$unsubscribesTable}` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `contact_id`    BIGINT UNSIGNED NOT NULL,
    `campaign_id`   BIGINT UNSIGNED NULL DEFAULT NULL,
    `list_id`       BIGINT UNSIGNED NULL DEFAULT NULL,
    `email`         VARCHAR(191) NOT NULL,
    `reason`        TEXT NULL DEFAULT NULL,
    `ip`            VARCHAR(45) NULL DEFAULT NULL,
    `user_agent`    TEXT NULL DEFAULT NULL,
    `created_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_{$unsubscribesTable}_contact_id` (`contact_id`),
    KEY `idx_{$unsubscribesTable}_campaign_id` (`campaign_id`),
    KEY `idx_{$unsubscribesTable}_list_id` (`list_id`),
    KEY `idx_{$unsubscribesTable}_email` (`email`),
    CONSTRAINT `fk_{$unsubscribesTable}_contact_id`
        FOREIGN KEY (`contact_id`) REFERENCES `{$contactsTable}` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_{$unsubscribesTable}_campaign_id`
        FOREIGN KEY (`campaign_id`) REFERENCES `{$campaignsTable}` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_{$unsubscribesTable}_list_id`
        FOREIGN KEY (`list_id`) REFERENCES `{$listsTable}` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

$db->exec("CREATE TABLE IF NOT EXISTS `{$suppressionsTable}` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `email`       VARCHAR(191) NOT NULL,
    `reason`      ENUM('unsubscribe','bounce','complaint','manual') NOT NULL DEFAULT 'manual',
    `campaign_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_{$suppressionsTable}_email` (`email`),
    KEY `idx_{$suppressionsTable}_reason` (`reason`),
    KEY `idx_{$suppressionsTable}_campaign_id` (`campaign_id`),
    CONSTRAINT `fk_{$suppressionsTable}_campaign_id`
        FOREIGN KEY (`campaign_id`) REFERENCES `{$campaignsTable}` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

$db->exec("CREATE TABLE IF NOT EXISTS `{$bouncesTable}` (
    `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `contact_id`      BIGINT UNSIGNED NOT NULL,
    `campaign_id`     BIGINT UNSIGNED NULL DEFAULT NULL,
    `email`           VARCHAR(191) NOT NULL,
    `bounce_type`     ENUM('hard','soft','block') NOT NULL DEFAULT 'hard',
    `error_code`      VARCHAR(10) NULL DEFAULT NULL,
    `error_message`   TEXT NULL DEFAULT NULL,
    `smtp_response`   TEXT NULL DEFAULT NULL,
    `created_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_{$bouncesTable}_contact_id` (`contact_id`),
    KEY `idx_{$bouncesTable}_campaign_id` (`campaign_id`),
    KEY `idx_{$bouncesTable}_email` (`email`),
    KEY `idx_{$bouncesTable}_bounce_type` (`bounce_type`),
    CONSTRAINT `fk_{$bouncesTable}_contact_id`
        FOREIGN KEY (`contact_id`) REFERENCES `{$contactsTable}` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_{$bouncesTable}_campaign_id`
        FOREIGN KEY (`campaign_id`) REFERENCES `{$campaignsTable}` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

$db->exec("CREATE TABLE IF NOT EXISTS `{$complaintsTable}` (
    `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `contact_id`       BIGINT UNSIGNED NOT NULL,
    `campaign_id`      BIGINT UNSIGNED NULL DEFAULT NULL,
    `email`            VARCHAR(191) NOT NULL,
    `complaint_type`   VARCHAR(50) NULL DEFAULT NULL,
    `feedback_id`      VARCHAR(255) NULL DEFAULT NULL,
    `created_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_{$complaintsTable}_contact_id` (`contact_id`),
    KEY `idx_{$complaintsTable}_campaign_id` (`campaign_id`),
    KEY `idx_{$complaintsTable}_email` (`email`),
    CONSTRAINT `fk_{$complaintsTable}_contact_id`
        FOREIGN KEY (`contact_id`) REFERENCES `{$contactsTable}` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_{$complaintsTable}_campaign_id`
        FOREIGN KEY (`campaign_id`) REFERENCES `{$campaignsTable}` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
