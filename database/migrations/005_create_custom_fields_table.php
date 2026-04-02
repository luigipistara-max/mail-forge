<?php
// Migration gets $db (PDO) and $prefix (string) injected by MigrationRunner

$customFieldsTable       = $prefix . 'custom_fields';
$contactCustomValuesTable = $prefix . 'contact_custom_values';
$contactsTable           = $prefix . 'contacts';

$db->exec("CREATE TABLE IF NOT EXISTS `{$customFieldsTable}` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`        VARCHAR(100) NOT NULL,
    `slug`        VARCHAR(100) NOT NULL,
    `type`        ENUM('text','number','date','boolean','select','multiselect') NOT NULL DEFAULT 'text',
    `options`     JSON NULL DEFAULT NULL,
    `is_required` TINYINT(1) NOT NULL DEFAULT 0,
    `sort_order`  INT NOT NULL DEFAULT 0,
    `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_{$customFieldsTable}_slug` (`slug`),
    KEY `idx_{$customFieldsTable}_sort_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

$db->exec("CREATE TABLE IF NOT EXISTS `{$contactCustomValuesTable}` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `contact_id` BIGINT UNSIGNED NOT NULL,
    `field_id`   INT UNSIGNED NOT NULL,
    `value`      TEXT NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_{$contactCustomValuesTable}_contact_field` (`contact_id`, `field_id`),
    KEY `idx_{$contactCustomValuesTable}_field_id` (`field_id`),
    CONSTRAINT `fk_{$contactCustomValuesTable}_contact_id`
        FOREIGN KEY (`contact_id`) REFERENCES `{$contactsTable}` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_{$contactCustomValuesTable}_field_id`
        FOREIGN KEY (`field_id`) REFERENCES `{$customFieldsTable}` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
