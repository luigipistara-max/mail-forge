<?php
// Migration gets $db (PDO) and $prefix (string) injected by MigrationRunner

$smtpServersTable = $prefix . 'smtp_servers';
$smtpLogsTable    = $prefix . 'smtp_logs';
$campaignsTable   = $prefix . 'campaigns';
$contactsTable    = $prefix . 'contacts';

$db->exec("CREATE TABLE IF NOT EXISTS `{$smtpServersTable}` (
    `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`              VARCHAR(200) NOT NULL,
    `host`              VARCHAR(255) NOT NULL,
    `port`              INT NOT NULL DEFAULT 587,
    `username`          VARCHAR(255) NOT NULL,
    `password`          VARCHAR(255) NOT NULL COMMENT 'Stored encrypted',
    `encryption`        ENUM('none','tls','ssl') NOT NULL DEFAULT 'tls',
    `from_email`        VARCHAR(191) NOT NULL,
    `from_name`         VARCHAR(200) NOT NULL,
    `reply_to`          VARCHAR(191) NULL DEFAULT NULL,
    `is_active`         TINYINT(1) NOT NULL DEFAULT 1,
    `priority`          INT NOT NULL DEFAULT 10,
    `max_per_minute`    INT NOT NULL DEFAULT 60,
    `max_per_hour`      INT NOT NULL DEFAULT 1000,
    `max_per_day`       INT NOT NULL DEFAULT 10000,
    `sent_today`        INT NOT NULL DEFAULT 0,
    `sent_this_hour`    INT NOT NULL DEFAULT 0,
    `last_reset_at`     TIMESTAMP NULL DEFAULT NULL,
    `created_at`        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_{$smtpServersTable}_is_active` (`is_active`),
    KEY `idx_{$smtpServersTable}_priority` (`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

$db->exec("CREATE TABLE IF NOT EXISTS `{$smtpLogsTable}` (
    `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `campaign_id`     BIGINT UNSIGNED NULL DEFAULT NULL,
    `contact_id`      BIGINT UNSIGNED NULL DEFAULT NULL,
    `smtp_server_id`  BIGINT UNSIGNED NULL DEFAULT NULL,
    `to_email`        VARCHAR(191) NOT NULL,
    `subject`         VARCHAR(500) NULL DEFAULT NULL,
    `status`          ENUM('sent','failed','bounced') NOT NULL DEFAULT 'sent',
    `message_id`      VARCHAR(255) NULL DEFAULT NULL,
    `error_message`   TEXT NULL DEFAULT NULL,
    `response`        TEXT NULL DEFAULT NULL,
    `sent_at`         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_{$smtpLogsTable}_campaign_id` (`campaign_id`),
    KEY `idx_{$smtpLogsTable}_contact_id` (`contact_id`),
    KEY `idx_{$smtpLogsTable}_smtp_server_id` (`smtp_server_id`),
    KEY `idx_{$smtpLogsTable}_status` (`status`),
    KEY `idx_{$smtpLogsTable}_to_email` (`to_email`),
    CONSTRAINT `fk_{$smtpLogsTable}_campaign_id`
        FOREIGN KEY (`campaign_id`) REFERENCES `{$campaignsTable}` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_{$smtpLogsTable}_contact_id`
        FOREIGN KEY (`contact_id`) REFERENCES `{$contactsTable}` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_{$smtpLogsTable}_smtp_server_id`
        FOREIGN KEY (`smtp_server_id`) REFERENCES `{$smtpServersTable}` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
