<?php
// Migration gets $db (PDO) and $prefix (string) injected by MigrationRunner

$segmentsTable     = $prefix . 'segments';
$segmentRulesTable = $prefix . 'segment_rules';
$listsTable        = $prefix . 'lists';

$db->exec("CREATE TABLE IF NOT EXISTS `{$segmentsTable}` (
    `id`                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `uuid`                CHAR(36) NOT NULL,
    `name`                VARCHAR(200) NOT NULL,
    `description`         TEXT NULL DEFAULT NULL,
    `list_id`             BIGINT UNSIGNED NULL DEFAULT NULL,
    `match_type`          ENUM('all','any') NOT NULL DEFAULT 'all',
    `estimated_count`     INT NOT NULL DEFAULT 0,
    `last_calculated_at`  TIMESTAMP NULL DEFAULT NULL,
    `created_at`          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`          TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_{$segmentsTable}_uuid` (`uuid`),
    KEY `idx_{$segmentsTable}_list_id` (`list_id`),
    KEY `idx_{$segmentsTable}_deleted_at` (`deleted_at`),
    CONSTRAINT `fk_{$segmentsTable}_list_id`
        FOREIGN KEY (`list_id`) REFERENCES `{$listsTable}` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

$db->exec("CREATE TABLE IF NOT EXISTS `{$segmentRulesTable}` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `segment_id`  BIGINT UNSIGNED NOT NULL,
    `field_type`  ENUM('contact','tag','custom','campaign_open','campaign_click') NOT NULL DEFAULT 'contact',
    `field`       VARCHAR(100) NOT NULL,
    `operator`    ENUM('equals','not_equals','contains','not_contains','starts_with','ends_with','greater_than','less_than','is_empty','is_not_empty','in','not_in') NOT NULL DEFAULT 'equals',
    `value`       TEXT NULL DEFAULT NULL,
    `sort_order`  INT NOT NULL DEFAULT 0,
    `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_{$segmentRulesTable}_segment_id` (`segment_id`),
    KEY `idx_{$segmentRulesTable}_sort_order` (`sort_order`),
    CONSTRAINT `fk_{$segmentRulesTable}_segment_id`
        FOREIGN KEY (`segment_id`) REFERENCES `{$segmentsTable}` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
