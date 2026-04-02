<?php

class DefaultSeeder
{
    private PDO $db;
    private string $prefix;

    public function __construct(PDO $db, string $prefix)
    {
        $this->db     = $db;
        $this->prefix = $prefix;
    }

    /**
     * Seeds the database with default roles, settings, and a placeholder SMTP
     * server required for a clean installation.
     */
    public function run(): void
    {
        $this->seedRoles();
        $this->seedSettings();
        $this->seedSmtpServer();
    }

    // -------------------------------------------------------------------------
    // Private seeders
    // -------------------------------------------------------------------------

    private function seedRoles(): void
    {
        $table = $this->prefix . 'roles';

        $roles = [
            [
                'name'         => 'super_admin',
                'display_name' => 'Super Administrator',
                'description'  => 'Full unrestricted access to all platform features and settings.',
            ],
            [
                'name'         => 'admin',
                'display_name' => 'Administrator',
                'description'  => 'Administrative access with the ability to manage users and platform configuration.',
            ],
            [
                'name'         => 'marketer',
                'display_name' => 'Marketer',
                'description'  => 'Can create and send campaigns, manage contacts, lists, and automations.',
            ],
            [
                'name'         => 'analyst',
                'display_name' => 'Analyst',
                'description'  => 'Read-only access to reports and campaign statistics.',
            ],
            [
                'name'         => 'viewer',
                'display_name' => 'Viewer',
                'description'  => 'Read-only access to the platform.',
            ],
        ];

        $stmt = $this->db->prepare(
            "INSERT IGNORE INTO `{$table}` (`name`, `display_name`, `description`)
             VALUES (:name, :display_name, :description)"
        );

        foreach ($roles as $role) {
            $stmt->execute($role);
        }
    }

    private function seedSettings(): void
    {
        $table = $this->prefix . 'settings';

        $settings = [
            // General
            ['key' => 'app_name',           'value' => 'Mail Forge',                   'type' => 'string',  'group' => 'general',  'description' => 'The name of the application shown in the UI and emails.'],
            ['key' => 'app_url',            'value' => 'http://localhost',             'type' => 'string',  'group' => 'general',  'description' => 'The public base URL of the application.'],
            ['key' => 'company_name',       'value' => 'My Company',                   'type' => 'string',  'group' => 'general',  'description' => 'Company name shown in email footers.'],
            ['key' => 'company_address',    'value' => '',                             'type' => 'text',    'group' => 'general',  'description' => 'Physical mailing address required by CAN-SPAM / GDPR.'],
            ['key' => 'company_website',    'value' => '',                             'type' => 'string',  'group' => 'general',  'description' => 'Company website URL.'],
            ['key' => 'support_email',      'value' => 'support@example.com',          'type' => 'string',  'group' => 'general',  'description' => 'Support email address.'],
            ['key' => 'timezone',           'value' => 'UTC',                          'type' => 'string',  'group' => 'general',  'description' => 'Default application timezone.'],
            ['key' => 'date_format',        'value' => 'Y-m-d',                        'type' => 'string',  'group' => 'general',  'description' => 'PHP date format string for display.'],
            ['key' => 'time_format',        'value' => 'H:i',                          'type' => 'string',  'group' => 'general',  'description' => 'PHP time format string for display.'],

            // Email
            ['key' => 'default_from_name',  'value' => 'Mail Forge',                   'type' => 'string',  'group' => 'email',    'description' => 'Default sender name used when no list override is set.'],
            ['key' => 'default_from_email', 'value' => 'noreply@example.com',          'type' => 'string',  'group' => 'email',    'description' => 'Default sender email address.'],
            ['key' => 'default_reply_to',   'value' => '',                             'type' => 'string',  'group' => 'email',    'description' => 'Default reply-to address. Leave blank to use from_email.'],
            ['key' => 'unsubscribe_page',   'value' => '',                             'type' => 'string',  'group' => 'email',    'description' => 'Custom URL for the unsubscribe landing page.'],
            ['key' => 'track_opens',        'value' => '1',                            'type' => 'boolean', 'group' => 'email',    'description' => 'Enable open tracking by default for new campaigns.'],
            ['key' => 'track_clicks',       'value' => '1',                            'type' => 'boolean', 'group' => 'email',    'description' => 'Enable click tracking by default for new campaigns.'],

            // Queue / sending
            ['key' => 'default_batch_size',             'value' => '100',  'type' => 'integer', 'group' => 'queue',    'description' => 'Number of emails sent per batch.'],
            ['key' => 'default_batch_interval_minutes', 'value' => '10',   'type' => 'integer', 'group' => 'queue',    'description' => 'Minutes to wait between batches.'],
            ['key' => 'queue_worker_timeout',           'value' => '300',  'type' => 'integer', 'group' => 'queue',    'description' => 'Seconds before a running queue job is considered stalled.'],
            ['key' => 'max_job_attempts',               'value' => '3',    'type' => 'integer', 'group' => 'queue',    'description' => 'Maximum number of retry attempts for a failed job.'],

            // Security
            ['key' => 'login_max_attempts',    'value' => '5',    'type' => 'integer', 'group' => 'security', 'description' => 'Number of failed logins before account lockout.'],
            ['key' => 'login_lockout_minutes', 'value' => '30',   'type' => 'integer', 'group' => 'security', 'description' => 'Minutes an account remains locked after too many failed attempts.'],
            ['key' => 'session_lifetime',      'value' => '120',  'type' => 'integer', 'group' => 'security', 'description' => 'Session lifetime in minutes.'],
            ['key' => 'password_reset_expiry', 'value' => '60',   'type' => 'integer', 'group' => 'security', 'description' => 'Minutes before a password reset token expires.'],
        ];

        $stmt = $this->db->prepare(
            "INSERT IGNORE INTO `{$table}` (`key`, `value`, `type`, `group`, `description`)
             VALUES (:key, :value, :type, :group, :description)"
        );

        foreach ($settings as $setting) {
            $stmt->execute($setting);
        }
    }

    private function seedSmtpServer(): void
    {
        $table = $this->prefix . 'smtp_servers';

        $stmt = $this->db->prepare(
            "INSERT IGNORE INTO `{$table}`
                (`name`, `host`, `port`, `username`, `password`, `encryption`,
                 `from_email`, `from_name`, `reply_to`, `is_active`, `priority`)
             VALUES
                (:name, :host, :port, :username, :password, :encryption,
                 :from_email, :from_name, :reply_to, :is_active, :priority)"
        );

        $stmt->execute([
            ':name'       => 'Default SMTP Server',
            ':host'       => 'smtp.example.com',
            ':port'       => 587,
            ':username'   => 'smtp_user@example.com',
            ':password'   => '',   // Placeholder – must be configured by the administrator.
            ':encryption' => 'tls',
            ':from_email' => 'noreply@example.com',
            ':from_name'  => 'Mail Forge',
            ':reply_to'   => null,
            ':is_active'  => 0,    // Disabled until properly configured.
            ':priority'   => 10,
        ]);
    }
}
