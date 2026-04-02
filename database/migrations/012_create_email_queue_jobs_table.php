<?php
// Migration gets $db (PDO) and $prefix (string) injected by MigrationRunner

$emailQueueJobsTable = $prefix . 'email_queue_jobs';
$cronLocksTable      = $prefix . 'cron_locks';
$campaignsTable      = $prefix . 'campaigns';

$db->exec("CREATE TABLE IF NOT EXISTS `{$emailQueueJobsTable}` (
    `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `campaign_id`     BIGINT UNSIGNED NULL DEFAULT NULL,
    `type`            VARCHAR(100) NOT NULL DEFAULT 'campaign_batch',
    `payload`         JSON NOT NULL,
    `status`          ENUM('queued','running','completed','failed') NOT NULL DEFAULT 'queued',
    `attempts`        INT NOT NULL DEFAULT 0,
    `max_attempts`    INT NOT NULL DEFAULT 3,
    `available_at`    TIMESTAMP NULL DEFAULT NULL,
    `started_at`      TIMESTAMP NULL DEFAULT NULL,
    `completed_at`    TIMESTAMP NULL DEFAULT NULL,
    `failed_at`       TIMESTAMP NULL DEFAULT NULL,
    `error_message`   TEXT NULL DEFAULT NULL,
    `created_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_{$emailQueueJobsTable}_status` (`status`),
    KEY `idx_{$emailQueueJobsTable}_campaign_id` (`campaign_id`),
    KEY `idx_{$emailQueueJobsTable}_available_at` (`available_at`),
    KEY `idx_{$emailQueueJobsTable}_type` (`type`),
    CONSTRAINT `fk_{$emailQueueJobsTable}_campaign_id`
        FOREIGN KEY (`campaign_id`) REFERENCES `{$campaignsTable}` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

$db->exec("CREATE TABLE IF NOT EXISTS `{$cronLocksTable}` (
    `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `lock_name`    VARCHAR(100) NOT NULL,
    `locked_by`    VARCHAR(100) NOT NULL,
    `locked_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `expires_at`   TIMESTAMP NOT NULL,
    `released_at`  TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_{$cronLocksTable}_lock_name` (`lock_name`),
    KEY `idx_{$cronLocksTable}_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
