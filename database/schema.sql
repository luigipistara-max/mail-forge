-- MailForge Database Schema
-- MySQL/MariaDB 5.7+
-- Charset: utf8mb4

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------
-- Table: users
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `name`          VARCHAR(255)    NOT NULL,
    `email`         VARCHAR(255)    NOT NULL,
    `password_hash` VARCHAR(255)    NOT NULL,
    `role`          ENUM('admin','editor','viewer') NOT NULL DEFAULT 'viewer',
    `api_key`       VARCHAR(64)     DEFAULT NULL,
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_email` (`email`),
    KEY `idx_users_role` (`role`),
    KEY `idx_users_api_key` (`api_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: lists
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lists` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`     INT UNSIGNED NOT NULL,
    `name`        VARCHAR(255) NOT NULL,
    `description` TEXT         DEFAULT NULL,
    `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_lists_user_id` (`user_id`),
    CONSTRAINT `fk_lists_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: contacts
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `contacts` (
    `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`         INT UNSIGNED NOT NULL,
    `email`           VARCHAR(255) NOT NULL,
    `first_name`      VARCHAR(100) DEFAULT NULL,
    `last_name`       VARCHAR(100) DEFAULT NULL,
    `phone`           VARCHAR(30)  DEFAULT NULL,
    `status`          ENUM('active','inactive','archived') NOT NULL DEFAULT 'active',
    `custom_fields`   JSON         DEFAULT NULL,
    `subscribed_at`   DATETIME     DEFAULT NULL,
    `unsubscribed_at` DATETIME     DEFAULT NULL,
    `created_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_contacts_user_email` (`user_id`, `email`),
    KEY `idx_contacts_user_id` (`user_id`),
    KEY `idx_contacts_status` (`status`),
    KEY `idx_contacts_email` (`email`),
    CONSTRAINT `fk_contacts_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: contact_list  (pivot)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `contact_list` (
    `contact_id` INT UNSIGNED NOT NULL,
    `list_id`    INT UNSIGNED NOT NULL,
    `added_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`contact_id`, `list_id`),
    KEY `idx_contact_list_list_id` (`list_id`),
    CONSTRAINT `fk_cl_contact_id` FOREIGN KEY (`contact_id`) REFERENCES `contacts` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_cl_list_id`    FOREIGN KEY (`list_id`)    REFERENCES `lists`    (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: templates
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `templates` (
    `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`      INT UNSIGNED NOT NULL,
    `name`         VARCHAR(255) NOT NULL,
    `subject`      VARCHAR(255) NOT NULL,
    `html_content` LONGTEXT     NOT NULL,
    `text_content` LONGTEXT     DEFAULT NULL,
    `category`     VARCHAR(100) DEFAULT NULL,
    `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_templates_user_id` (`user_id`),
    KEY `idx_templates_category` (`category`),
    CONSTRAINT `fk_templates_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: campaigns
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `campaigns` (
    `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`      INT UNSIGNED NOT NULL,
    `name`         VARCHAR(255) NOT NULL,
    `subject`      VARCHAR(255) NOT NULL,
    `from_name`    VARCHAR(255) NOT NULL,
    `from_email`   VARCHAR(255) NOT NULL,
    `reply_to`     VARCHAR(255) DEFAULT NULL,
    `template_id`  INT UNSIGNED DEFAULT NULL,
    `html_content` LONGTEXT     DEFAULT NULL,
    `text_content` LONGTEXT     DEFAULT NULL,
    `status`       ENUM('scheduled','running','completed','cancelled') NOT NULL DEFAULT 'scheduled',
    `list_id`      INT UNSIGNED NOT NULL,
    `scheduled_at` DATETIME     DEFAULT NULL,
    `started_at`   DATETIME     DEFAULT NULL,
    `completed_at` DATETIME     DEFAULT NULL,
    `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_campaigns_user_id`     (`user_id`),
    KEY `idx_campaigns_status`      (`status`),
    KEY `idx_campaigns_list_id`     (`list_id`),
    KEY `idx_campaigns_template_id` (`template_id`),
    KEY `idx_campaigns_scheduled_at`(`scheduled_at`),
    CONSTRAINT `fk_campaigns_user_id`     FOREIGN KEY (`user_id`)     REFERENCES `users`     (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_campaigns_list_id`     FOREIGN KEY (`list_id`)     REFERENCES `lists`     (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_campaigns_template_id` FOREIGN KEY (`template_id`) REFERENCES `templates` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: campaign_recipients
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `campaign_recipients` (
    `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `campaign_id`    INT UNSIGNED NOT NULL,
    `contact_id`     INT UNSIGNED NOT NULL,
    `status`         ENUM('sent','delivered','failed') NOT NULL DEFAULT 'sent',
    `sent_at`        DATETIME     DEFAULT NULL,
    `delivered_at`   DATETIME     DEFAULT NULL,
    `failed_at`      DATETIME     DEFAULT NULL,
    `failure_reason` TEXT         DEFAULT NULL,
    `created_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_cr_campaign_contact` (`campaign_id`, `contact_id`),
    KEY `idx_cr_contact_id`  (`contact_id`),
    KEY `idx_cr_status`      (`status`),
    CONSTRAINT `fk_cr_campaign_id` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_cr_contact_id`  FOREIGN KEY (`contact_id`)  REFERENCES `contacts`  (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: tracking_events
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tracking_events` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `campaign_id` INT UNSIGNED NOT NULL,
    `contact_id`  INT UNSIGNED NOT NULL,
    `event_type`  ENUM('open','click','bounce','unsubscribe','complaint') NOT NULL,
    `event_data`  JSON         DEFAULT NULL,
    `ip_address`  VARCHAR(45)  DEFAULT NULL,
    `user_agent`  VARCHAR(500) DEFAULT NULL,
    `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_te_campaign_id`  (`campaign_id`),
    KEY `idx_te_contact_id`   (`contact_id`),
    KEY `idx_te_event_type`   (`event_type`),
    KEY `idx_te_created_at`   (`created_at`),
    CONSTRAINT `fk_te_campaign_id` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_te_contact_id`  FOREIGN KEY (`contact_id`)  REFERENCES `contacts`  (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: automations
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `automations` (
    `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`        INT UNSIGNED NOT NULL,
    `name`           VARCHAR(255) NOT NULL,
    `description`    TEXT         DEFAULT NULL,
    `trigger_type`   ENUM('signup','event','date','manual') NOT NULL DEFAULT 'manual',
    `trigger_config` JSON         DEFAULT NULL,
    `status`         ENUM('active','paused','stopped') NOT NULL DEFAULT 'stopped',
    `created_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_automations_user_id` (`user_id`),
    KEY `idx_automations_status`  (`status`),
    CONSTRAINT `fk_automations_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: automation_steps
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `automation_steps` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `automation_id` INT UNSIGNED NOT NULL,
    `step_order`    INT UNSIGNED NOT NULL DEFAULT 0,
    `action_type`   ENUM('send_email','wait','condition') NOT NULL,
    `action_config` JSON         DEFAULT NULL,
    `template_id`   INT UNSIGNED DEFAULT NULL,
    `delay_minutes` INT UNSIGNED DEFAULT 0,
    `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_as_automation_id` (`automation_id`),
    KEY `idx_as_template_id`   (`template_id`),
    CONSTRAINT `fk_as_automation_id` FOREIGN KEY (`automation_id`) REFERENCES `automations` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_as_template_id`   FOREIGN KEY (`template_id`)   REFERENCES `templates`   (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: automation_logs
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `automation_logs` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `automation_id` INT UNSIGNED NOT NULL,
    `contact_id`    INT UNSIGNED NOT NULL,
    `step_id`       INT UNSIGNED NOT NULL,
    `status`        VARCHAR(50)  NOT NULL,
    `executed_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `result`        JSON         DEFAULT NULL,
    `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_al_automation_id` (`automation_id`),
    KEY `idx_al_contact_id`    (`contact_id`),
    KEY `idx_al_step_id`       (`step_id`),
    CONSTRAINT `fk_al_automation_id` FOREIGN KEY (`automation_id`) REFERENCES `automations`      (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_al_contact_id`    FOREIGN KEY (`contact_id`)    REFERENCES `contacts`         (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_al_step_id`       FOREIGN KEY (`step_id`)       REFERENCES `automation_steps` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: api_keys
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `api_keys` (
    `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`      INT UNSIGNED NOT NULL,
    `key_hash`     VARCHAR(255) NOT NULL,
    `name`         VARCHAR(255) NOT NULL,
    `permissions`  JSON         DEFAULT NULL,
    `last_used_at` DATETIME     DEFAULT NULL,
    `expires_at`   DATETIME     DEFAULT NULL,
    `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_api_keys_user_id`   (`user_id`),
    KEY `idx_api_keys_key_hash`  (`key_hash`),
    KEY `idx_api_keys_expires_at`(`expires_at`),
    CONSTRAINT `fk_api_keys_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: settings
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `settings` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `key`        VARCHAR(100) NOT NULL,
    `value`      TEXT         DEFAULT NULL,
    `group_name` VARCHAR(100) DEFAULT NULL,
    `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_settings_key` (`key`),
    KEY `idx_settings_group_name` (`group_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
