<?php

declare(strict_types=1);

/**
 * DatabaseSeeder — populates the database with demo data.
 *
 * Run via:  php database/migrate.php --seed
 */

require_once __DIR__ . '/../../src/autoload.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';

use MailForge\Models\User;
use MailForge\Models\Contact;
use MailForge\Models\MailingList;
use MailForge\Models\Template;
use MailForge\Models\Campaign;

class DatabaseSeeder
{
    private User        $users;
    private Contact     $contacts;
    private MailingList $lists;
    private Template    $templates;
    private Campaign    $campaigns;

    public function __construct()
    {
        $this->users     = new User();
        $this->contacts  = new Contact();
        $this->lists     = new MailingList();
        $this->templates = new Template();
        $this->campaigns = new Campaign();
    }

    public function run(): void
    {
        $this->seedUsers();
        $this->seedLists();
        $this->seedContacts();
        $this->seedTemplates();
        $this->seedCampaigns();
    }

    // ----------------------------------------------------------------
    // Seed methods
    // ----------------------------------------------------------------

    private function seedUsers(): void
    {
        // Check if admin already exists
        if ($this->users->findByEmail('admin@mailforge.local') !== null) {
            echo "  [skip] Admin user already exists.\n";
            return;
        }

        $this->users->create([
            'name'          => 'Admin User',
            'email'         => 'admin@mailforge.local',
            'password_hash' => password_hash('admin123', PASSWORD_BCRYPT, ['cost' => 12]),
            'role'          => USER_ROLE_ADMIN,
        ]);

        echo "  [ok]   Admin user created (admin@mailforge.local / admin123).\n";
    }

    private function seedLists(): void
    {
        $admin = $this->users->findByEmail('admin@mailforge.local');
        if ($admin === null) {
            echo "  [err]  Cannot seed lists — admin user not found.\n";
            return;
        }

        $lists = [
            ['name' => 'Newsletter Subscribers', 'description' => 'Users who opted in to the monthly newsletter.'],
            ['name' => 'Product Updates',        'description' => 'Customers interested in product announcements.'],
            ['name' => 'VIP Customers',          'description' => 'High-value customers with special offers.'],
        ];

        foreach ($lists as $list) {
            // Skip duplicates
            if (!empty($this->lists->findOne(['name' => $list['name'], 'user_id' => $admin['id']]))) {
                echo "  [skip] List \"{$list['name']}\" already exists.\n";
                continue;
            }

            $this->lists->create([
                'user_id'     => (int) $admin['id'],
                'name'        => $list['name'],
                'description' => $list['description'],
            ]);
            echo "  [ok]   List \"{$list['name']}\" created.\n";
        }
    }

    private function seedContacts(): void
    {
        $admin = $this->users->findByEmail('admin@mailforge.local');
        if ($admin === null) {
            return;
        }

        $userId = (int) $admin['id'];

        $sampleContacts = [
            ['first_name' => 'Alice',    'last_name' => 'Johnson',   'email' => 'alice.johnson@example.com',   'phone' => '+12025550101'],
            ['first_name' => 'Bob',      'last_name' => 'Smith',     'email' => 'bob.smith@example.com',       'phone' => '+12025550102'],
            ['first_name' => 'Carol',    'last_name' => 'Williams',  'email' => 'carol.williams@example.com',  'phone' => '+12025550103'],
            ['first_name' => 'David',    'last_name' => 'Brown',     'email' => 'david.brown@example.com',     'phone' => '+12025550104'],
            ['first_name' => 'Eva',      'last_name' => 'Davis',     'email' => 'eva.davis@example.com',       'phone' => '+12025550105'],
            ['first_name' => 'Frank',    'last_name' => 'Miller',    'email' => 'frank.miller@example.com',    'phone' => '+12025550106'],
            ['first_name' => 'Grace',    'last_name' => 'Wilson',    'email' => 'grace.wilson@example.com',    'phone' => '+12025550107'],
            ['first_name' => 'Henry',    'last_name' => 'Moore',     'email' => 'henry.moore@example.com',     'phone' => '+12025550108'],
            ['first_name' => 'Irene',    'last_name' => 'Taylor',    'email' => 'irene.taylor@example.com',    'phone' => '+12025550109'],
            ['first_name' => 'Jack',     'last_name' => 'Anderson',  'email' => 'jack.anderson@example.com',   'phone' => '+12025550110'],
            ['first_name' => 'Karen',    'last_name' => 'Thomas',    'email' => 'karen.thomas@example.com',    'phone' => '+12025550111'],
            ['first_name' => 'Leo',      'last_name' => 'Jackson',   'email' => 'leo.jackson@example.com',     'phone' => '+12025550112'],
            ['first_name' => 'Mia',      'last_name' => 'White',     'email' => 'mia.white@example.com',       'phone' => '+12025550113'],
            ['first_name' => 'Nate',     'last_name' => 'Harris',    'email' => 'nate.harris@example.com',     'phone' => '+12025550114'],
            ['first_name' => 'Olivia',   'last_name' => 'Martin',    'email' => 'olivia.martin@example.com',   'phone' => '+12025550115'],
            ['first_name' => 'Paul',     'last_name' => 'Garcia',    'email' => 'paul.garcia@example.com',     'phone' => '+12025550116'],
            ['first_name' => 'Quinn',    'last_name' => 'Martinez',  'email' => 'quinn.martinez@example.com',  'phone' => '+12025550117'],
            ['first_name' => 'Rachel',   'last_name' => 'Robinson',  'email' => 'rachel.robinson@example.com', 'phone' => '+12025550118'],
            ['first_name' => 'Sam',      'last_name' => 'Clark',     'email' => 'sam.clark@example.com',       'phone' => '+12025550119'],
            ['first_name' => 'Tina',     'last_name' => 'Rodriguez', 'email' => 'tina.rodriguez@example.com',  'phone' => '+12025550120'],
        ];

        $newsletterList = $this->lists->findOne(['name' => 'Newsletter Subscribers', 'user_id' => $userId]);

        foreach ($sampleContacts as $contactData) {
            if ($this->contacts->findByEmail($contactData['email'], $userId) !== null) {
                echo "  [skip] Contact {$contactData['email']} already exists.\n";
                continue;
            }

            $contactId = $this->contacts->create(array_merge($contactData, [
                'user_id'       => $userId,
                'status'        => CONTACT_STATUS_ACTIVE,
                'subscribed_at' => date('Y-m-d H:i:s'),
            ]));

            if ($newsletterList !== null) {
                $this->lists->addContact((int) $newsletterList['id'], $contactId);
            }

            echo "  [ok]   Contact {$contactData['email']} created.\n";
        }
    }

    private function seedTemplates(): void
    {
        $admin = $this->users->findByEmail('admin@mailforge.local');
        if ($admin === null) {
            return;
        }

        $userId = (int) $admin['id'];

        $templates = [
            [
                'name'     => 'Welcome Email',
                'subject'  => 'Welcome to MailForge, {{first_name}}!',
                'category' => 'transactional',
                'html_content' => <<<HTML
                    <html>
                    <body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
                      <h1 style="color: #2563eb;">Welcome, {{first_name}}!</h1>
                      <p>Thanks for joining us. Your account has been created successfully.</p>
                      <p>If you have any questions, just reply to this email — we're always happy to help.</p>
                      <p>Best regards,<br>The MailForge Team</p>
                      <hr>
                      <p style="font-size: 12px; color: #666;">
                        You received this email because you signed up at our platform.
                        <a href="{{unsubscribe_url}}">Unsubscribe</a>
                      </p>
                    </body>
                    </html>
                    HTML,
                'text_content' => "Welcome, {{first_name}}!\n\nThanks for joining us.\n\nBest regards,\nThe MailForge Team\n\nTo unsubscribe: {{unsubscribe_url}}",
            ],
            [
                'name'     => 'Monthly Newsletter',
                'subject'  => 'Your Monthly Update — {{month}}',
                'category' => 'newsletter',
                'html_content' => <<<HTML
                    <html>
                    <body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
                      <h1 style="color: #2563eb;">Monthly Newsletter</h1>
                      <p>Hi {{first_name}},</p>
                      <p>Here is what's new this month at MailForge:</p>
                      <ul>
                        <li>New campaign analytics dashboard</li>
                        <li>Improved deliverability rates</li>
                        <li>Automation workflow builder</li>
                      </ul>
                      <p>Read the full update on our blog.</p>
                      <p>Best regards,<br>The MailForge Team</p>
                      <hr>
                      <p style="font-size: 12px; color: #666;">
                        <a href="{{unsubscribe_url}}">Unsubscribe</a> from this newsletter.
                      </p>
                    </body>
                    </html>
                    HTML,
                'text_content' => "Monthly Newsletter\n\nHi {{first_name}},\n\nHere is what's new this month.\n\nUnsubscribe: {{unsubscribe_url}}",
            ],
        ];

        foreach ($templates as $tplData) {
            if (!empty($this->templates->findOne(['name' => $tplData['name'], 'user_id' => $userId]))) {
                echo "  [skip] Template \"{$tplData['name']}\" already exists.\n";
                continue;
            }

            $this->templates->create(array_merge($tplData, ['user_id' => $userId]));
            echo "  [ok]   Template \"{$tplData['name']}\" created.\n";
        }
    }

    private function seedCampaigns(): void
    {
        $admin = $this->users->findByEmail('admin@mailforge.local');
        if ($admin === null) {
            return;
        }

        $userId = (int) $admin['id'];

        $list     = $this->lists->findOne(['name' => 'Newsletter Subscribers', 'user_id' => $userId]);
        $template = $this->templates->findOne(['name' => 'Monthly Newsletter', 'user_id' => $userId]);

        if ($list === null || $template === null) {
            echo "  [skip] Cannot seed campaign — list or template missing.\n";
            return;
        }

        $name = 'April 2026 Newsletter';

        if (!empty($this->campaigns->findOne(['name' => $name, 'user_id' => $userId]))) {
            echo "  [skip] Campaign \"{$name}\" already exists.\n";
            return;
        }

        $this->campaigns->create([
            'user_id'     => $userId,
            'name'        => $name,
            'subject'     => 'Your Monthly Update — April',
            'from_name'   => 'MailForge Team',
            'from_email'  => 'newsletter@mailforge.local',
            'reply_to'    => 'support@mailforge.local',
            'template_id' => (int) $template['id'],
            'status'      => CAMPAIGN_STATUS_SCHEDULED,
            'list_id'     => (int) $list['id'],
            'scheduled_at'=> date('Y-m-d H:i:s', strtotime('+7 days')),
        ]);

        echo "  [ok]   Campaign \"{$name}\" created.\n";
    }
}
