<?php
// Migration gets $db (PDO) and $prefix (string) injected by MigrationRunner

$campaignsTable          = $prefix . 'campaigns';
$campaignRecipientsTable = $prefix . 'campaign_recipients';
$campaignLinksTable      = $prefix . 'campaign_links';
$listsTable              = $prefix . 'lists';
$segmentsTable           = $prefix . 'segments';
$templatesTable          = $prefix . 'templates';
$usersTable              = $prefix . 'users';
$contactsTable           = $prefix . 'contacts';

$db->exec("CREATE TABLE IF NOT EXISTS `{$campaignsTable}` (
    `id`                      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `uuid`                    CHAR(36) NOT NULL,
    `name`                    VARCHAR(200) NOT NULL,
    `subject`                 VARCHAR(500) NOT NULL,
    `preheader`               VARCHAR(500) NULL DEFAULT NULL,
    `from_name`               VARCHAR(200) NULL DEFAULT NULL,
    `from_email`              VARCHAR(191) NULL DEFAULT NULL,
    `reply_to`                VARCHAR(191) NULL DEFAULT NULL,
    `status`                  ENUM('draft','scheduled','queued','sending','completed','paused','cancelled','failed') NOT NULL DEFAULT 'draft',
    `type`                    ENUM('regular','automated','ab_test') NOT NULL DEFAULT 'regular',
    `list_id`                 BIGINT UNSIGNED NULL DEFAULT NULL,
    `segment_id`              BIGINT UNSIGNED NULL DEFAULT NULL,
    `template_id`             BIGINT UNSIGNED NULL DEFAULT NULL,
    `html_content`            LONGTEXT NULL DEFAULT NULL,
    `text_content`            LONGTEXT NULL DEFAULT NULL,
    `track_opens`             TINYINT(1) NOT NULL DEFAULT 1,
    `track_clicks`            TINYINT(1) NOT NULL DEFAULT 1,
    `scheduled_at`            TIMESTAMP NULL DEFAULT NULL,
    `started_at`              TIMESTAMP NULL DEFAULT NULL,
    `completed_at`            TIMESTAMP NULL DEFAULT NULL,
    `last_batch_at`           TIMESTAMP NULL DEFAULT NULL,
    `next_batch_at`           TIMESTAMP NULL DEFAULT NULL,
    `batch_size`              INT NOT NULL DEFAULT 100,
    `batch_interval_minutes`  INT NOT NULL DEFAULT 10,
    `total_recipients`        INT NOT NULL DEFAULT 0,
    `sent_count`              INT NOT NULL DEFAULT 0,
    `failed_count`            INT NOT NULL DEFAULT 0,
    `pending_count`           INT NOT NULL DEFAULT 0,
    `opened_count`            INT NOT NULL DEFAULT 0,
    `clicked_count`           INT NOT NULL DEFAULT 0,
    `bounced_count`           INT NOT NULL DEFAULT 0,
    `unsubscribed_count`      INT NOT NULL DEFAULT 0,
    `complained_count`        INT NOT NULL DEFAULT 0,
    `created_by`              BIGINT UNSIGNED NULL DEFAULT NULL,
    `created_at`              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`              TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`              TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_{$campaignsTable}_uuid` (`uuid`),
    KEY `idx_{$campaignsTable}_status` (`status`),
    KEY `idx_{$campaignsTable}_list_id` (`list_id`),
    KEY `idx_{$campaignsTable}_segment_id` (`segment_id`),
    KEY `idx_{$campaignsTable}_template_id` (`template_id`),
    KEY `idx_{$campaignsTable}_created_by` (`created_by`),
    KEY `idx_{$campaignsTable}_scheduled_at` (`scheduled_at`),
    KEY `idx_{$campaignsTable}_deleted_at` (`deleted_at`),
    CONSTRAINT `fk_{$campaignsTable}_list_id`
        FOREIGN KEY (`list_id`) REFERENCES `{$listsTable}` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_{$campaignsTable}_segment_id`
        FOREIGN KEY (`segment_id`) REFERENCES `{$segmentsTable}` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_{$campaignsTable}_template_id`
        FOREIGN KEY (`template_id`) REFERENCES `{$templatesTable}` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_{$campaignsTable}_created_by`
        FOREIGN KEY (`created_by`) REFERENCES `{$usersTable}` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

$db->exec("CREATE TABLE IF NOT EXISTS `{$campaignRecipientsTable}` (
    `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `campaign_id`     BIGINT UNSIGNED NOT NULL,
    `contact_id`      BIGINT UNSIGNED NOT NULL,
    `email`           VARCHAR(191) NOT NULL,
    `status`          ENUM('pending','processing','sent','failed','bounced','skipped','unsubscribed','deferred') NOT NULL DEFAULT 'pending',
    `sent_at`         TIMESTAMP NULL DEFAULT NULL,
    `opened_at`       TIMESTAMP NULL DEFAULT NULL,
    `clicked_at`      TIMESTAMP NULL DEFAULT NULL,
    `open_count`      INT NOT NULL DEFAULT 0,
    `click_count`     INT NOT NULL DEFAULT 0,
    `error_message`   TEXT NULL DEFAULT NULL,
    `smtp_server_id`  BIGINT UNSIGNED NULL DEFAULT NULL,
    `message_id`      VARCHAR(255) NULL DEFAULT NULL,
    `created_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_{$campaignRecipientsTable}_campaign_contact` (`campaign_id`, `contact_id`),
    KEY `idx_{$campaignRecipientsTable}_status` (`status`),
    KEY `idx_{$campaignRecipientsTable}_email` (`email`),
    KEY `idx_{$campaignRecipientsTable}_contact_id` (`contact_id`),
    KEY `idx_{$campaignRecipientsTable}_smtp_server_id` (`smtp_server_id`),
    CONSTRAINT `fk_{$campaignRecipientsTable}_campaign_id`
        FOREIGN KEY (`campaign_id`) REFERENCES `{$campaignsTable}` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_{$campaignRecipientsTable}_contact_id`
        FOREIGN KEY (`contact_id`) REFERENCES `{$contactsTable}` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

$db->exec("CREATE TABLE IF NOT EXISTS `{$campaignLinksTable}` (
    `id`                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `campaign_id`        BIGINT UNSIGNED NOT NULL,
    `original_url`       TEXT NOT NULL,
    `tracking_code`      VARCHAR(50) NOT NULL,
    `click_count`        INT NOT NULL DEFAULT 0,
    `unique_click_count` INT NOT NULL DEFAULT 0,
    `created_at`         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_{$campaignLinksTable}_tracking_code` (`tracking_code`),
    KEY `idx_{$campaignLinksTable}_campaign_id` (`campaign_id`),
    CONSTRAINT `fk_{$campaignLinksTable}_campaign_id`
        FOREIGN KEY (`campaign_id`) REFERENCES `{$campaignsTable}` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
