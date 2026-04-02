<?php
// Migration gets $db (PDO) and $prefix (string) injected by MigrationRunner

$listsTable        = $prefix . 'lists';
$listContactsTable = $prefix . 'list_contacts';
$contactsTable     = $prefix . 'contacts';
$templatesTable    = $prefix . 'templates';

$db->exec("CREATE TABLE IF NOT EXISTS `{$listsTable}` (
    `id`                      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `uuid`                    CHAR(36) NOT NULL,
    `name`                    VARCHAR(200) NOT NULL,
    `description`             TEXT NULL DEFAULT NULL,
    `from_name`               VARCHAR(200) NULL DEFAULT NULL,
    `from_email`              VARCHAR(191) NULL DEFAULT NULL,
    `reply_to`                VARCHAR(191) NULL DEFAULT NULL,
    `is_public`               TINYINT(1) NOT NULL DEFAULT 0,
    `subscribe_page_enabled`  TINYINT(1) NOT NULL DEFAULT 1,
    `double_optin`            TINYINT(1) NOT NULL DEFAULT 1,
    `welcome_email_enabled`   TINYINT(1) NOT NULL DEFAULT 0,
    `welcome_template_id`     BIGINT UNSIGNED NULL DEFAULT NULL,
    `unsubscribe_page_text`   TEXT NULL DEFAULT NULL,
    `subscriber_count`        INT NOT NULL DEFAULT 0,
    `created_at`              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`              TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`              TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_{$listsTable}_uuid` (`uuid`),
    KEY `idx_{$listsTable}_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

$db->exec("CREATE TABLE IF NOT EXISTS `{$listContactsTable}` (
    `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `list_id`         BIGINT UNSIGNED NOT NULL,
    `contact_id`      BIGINT UNSIGNED NOT NULL,
    `status`          ENUM('subscribed','unsubscribed','pending','cleaned') NOT NULL DEFAULT 'pending',
    `subscribed_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `unsubscribed_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_{$listContactsTable}_list_contact` (`list_id`, `contact_id`),
    KEY `idx_{$listContactsTable}_status` (`status`),
    KEY `idx_{$listContactsTable}_contact_id` (`contact_id`),
    CONSTRAINT `fk_{$listContactsTable}_list_id`
        FOREIGN KEY (`list_id`) REFERENCES `{$listsTable}` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_{$listContactsTable}_contact_id`
        FOREIGN KEY (`contact_id`) REFERENCES `{$contactsTable}` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
