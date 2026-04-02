<?php
// Migration gets $db (PDO) and $prefix (string) injected by MigrationRunner

$automationsTable     = $prefix . 'automations';
$automationStepsTable = $prefix . 'automation_steps';
$automationRunsTable  = $prefix . 'automation_runs';
$listsTable           = $prefix . 'lists';
$usersTable           = $prefix . 'users';
$contactsTable        = $prefix . 'contacts';

$db->exec("CREATE TABLE IF NOT EXISTS `{$automationsTable}` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `uuid`           CHAR(36) NOT NULL,
    `name`           VARCHAR(200) NOT NULL,
    `description`    TEXT NULL DEFAULT NULL,
    `status`         ENUM('active','paused','draft') NOT NULL DEFAULT 'draft',
    `trigger_type`   ENUM('list_subscribe','tag_added','date_anniversary','manual') NOT NULL DEFAULT 'list_subscribe',
    `trigger_config` JSON NULL DEFAULT NULL,
    `list_id`        BIGINT UNSIGNED NULL DEFAULT NULL,
    `created_by`     BIGINT UNSIGNED NULL DEFAULT NULL,
    `created_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`     TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_{$automationsTable}_uuid` (`uuid`),
    KEY `idx_{$automationsTable}_status` (`status`),
    KEY `idx_{$automationsTable}_list_id` (`list_id`),
    KEY `idx_{$automationsTable}_created_by` (`created_by`),
    KEY `idx_{$automationsTable}_deleted_at` (`deleted_at`),
    CONSTRAINT `fk_{$automationsTable}_list_id`
        FOREIGN KEY (`list_id`) REFERENCES `{$listsTable}` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_{$automationsTable}_created_by`
        FOREIGN KEY (`created_by`) REFERENCES `{$usersTable}` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

$db->exec("CREATE TABLE IF NOT EXISTS `{$automationStepsTable}` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `automation_id` BIGINT UNSIGNED NOT NULL,
    `type`          ENUM('email','delay','condition','tag_add','tag_remove') NOT NULL DEFAULT 'email',
    `config`        JSON NOT NULL,
    `sort_order`    INT NOT NULL DEFAULT 0,
    `created_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_{$automationStepsTable}_automation_id` (`automation_id`),
    KEY `idx_{$automationStepsTable}_sort_order` (`sort_order`),
    CONSTRAINT `fk_{$automationStepsTable}_automation_id`
        FOREIGN KEY (`automation_id`) REFERENCES `{$automationsTable}` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

$db->exec("CREATE TABLE IF NOT EXISTS `{$automationRunsTable}` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `automation_id` BIGINT UNSIGNED NOT NULL,
    `contact_id`    BIGINT UNSIGNED NOT NULL,
    `current_step`  INT NOT NULL DEFAULT 0,
    `status`        ENUM('running','completed','failed','paused') NOT NULL DEFAULT 'running',
    `started_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `completed_at`  TIMESTAMP NULL DEFAULT NULL,
    `last_run_at`   TIMESTAMP NULL DEFAULT NULL,
    `error_message` TEXT NULL DEFAULT NULL,
    `created_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_{$automationRunsTable}_automation_id` (`automation_id`),
    KEY `idx_{$automationRunsTable}_contact_id` (`contact_id`),
    KEY `idx_{$automationRunsTable}_status` (`status`),
    CONSTRAINT `fk_{$automationRunsTable}_automation_id`
        FOREIGN KEY (`automation_id`) REFERENCES `{$automationsTable}` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_{$automationRunsTable}_contact_id`
        FOREIGN KEY (`contact_id`) REFERENCES `{$contactsTable}` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
