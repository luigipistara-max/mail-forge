<?php
// Migration gets $db (PDO) and $prefix (string) injected by MigrationRunner

$tagsTable        = $prefix . 'tags';
$contactTagsTable = $prefix . 'contact_tags';
$contactsTable    = $prefix . 'contacts';

$db->exec("CREATE TABLE IF NOT EXISTS `{$tagsTable}` (
    `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`           VARCHAR(100) NOT NULL,
    `slug`           VARCHAR(100) NOT NULL,
    `color`          VARCHAR(7) NOT NULL DEFAULT '#6c757d',
    `contacts_count` INT NOT NULL DEFAULT 0,
    `created_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_{$tagsTable}_slug` (`slug`),
    KEY `idx_{$tagsTable}_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

$db->exec("CREATE TABLE IF NOT EXISTS `{$contactTagsTable}` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `contact_id` BIGINT UNSIGNED NOT NULL,
    `tag_id`     INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_{$contactTagsTable}_contact_tag` (`contact_id`, `tag_id`),
    KEY `idx_{$contactTagsTable}_tag_id` (`tag_id`),
    CONSTRAINT `fk_{$contactTagsTable}_contact_id`
        FOREIGN KEY (`contact_id`) REFERENCES `{$contactsTable}` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_{$contactTagsTable}_tag_id`
        FOREIGN KEY (`tag_id`) REFERENCES `{$tagsTable}` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
