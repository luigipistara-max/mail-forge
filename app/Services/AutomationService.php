<?php

declare(strict_types=1);

namespace MailForge\Services;

use MailForge\Core\Database;
use MailForge\Models\Automation;
use MailForge\Models\Contact;
use MailForge\Models\Setting;
use MailForge\Models\SmtpServer;
use MailForge\Models\Tag;
use MailForge\Models\Template;
use PDO;
use Throwable;

class AutomationService
{
    private PDO    $db;
    private string $prefix;

    public function __construct()
    {
        $this->db     = Database::getInstance();
        $this->prefix = Database::getPrefix();
    }

    // ─── Trigger ─────────────────────────────────────────────────────────

    /**
     * Find all active automations matching the event type and start runs for the contact.
     *
     * @param array<string, mixed> $data  Event context data.
     */
    public function triggerByEvent(string $eventType, int $contactId, array $data = []): void
    {
        $automationModel = new Automation();
        $automations     = $automationModel->getActive();

        foreach ($automations as $automation) {
            if ($automation['trigger_type'] !== $eventType) {
                continue;
            }

            // For list_subscribe triggers, check list_id matches
            if ($eventType === 'list_subscribe' && isset($automation['list_id']) && isset($data['list_id'])) {
                if ((int) $automation['list_id'] !== (int) $data['list_id']) {
                    continue;
                }
            }

            // For tag_added triggers, check the tag matches
            if ($eventType === 'tag_added' && isset($data['tag_name'])) {
                $triggerConfig = json_decode((string) ($automation['trigger_config'] ?? '{}'), true);
                if (isset($triggerConfig['tag_name']) && $triggerConfig['tag_name'] !== $data['tag_name']) {
                    continue;
                }
            }

            $automationModel->triggerForContact((int) $automation['id'], $contactId);
        }
    }

    // ─── Run processing ───────────────────────────────────────────────────

    /**
     * Advance a single automation run to its next step.
     */
    public function processRun(int $runId): void
    {
        $runsTable = $this->prefix . 'automation_runs';

        $stmt = $this->db->prepare(
            "SELECT r.*, a.`status` AS automation_status
             FROM `{$runsTable}` r
             JOIN `{$this->prefix}automations` a ON a.id = r.automation_id
             WHERE r.`id` = ? FOR UPDATE"
        );
        $stmt->execute([$runId]);
        $run = $stmt->fetch();

        if ($run === false) {
            return;
        }

        // Skip if automation is paused/inactive or run already done
        if (
            !in_array($run['status'], ['pending', 'running'], true)
            || $run['automation_status'] !== 'active'
        ) {
            return;
        }

        $automationModel = new Automation();
        $steps           = $automationModel->getSteps((int) $run['automation_id']);

        if (empty($steps)) {
            $this->markRunCompleted($runId);
            return;
        }

        $currentStep = (int) ($run['current_step'] ?? 0);

        if (!isset($steps[$currentStep])) {
            $this->markRunCompleted($runId);
            return;
        }

        $step = $steps[$currentStep];

        // Mark run as running
        $this->db->prepare(
            "UPDATE `{$runsTable}` SET `status` = 'running', `last_run_at` = NOW() WHERE `id` = ?"
        )->execute([$runId]);

        $contactModel = new Contact();
        $contact      = $contactModel->find((int) $run['contact_id']);

        if ($contact === null) {
            $this->markRunFailed($runId, 'Contact not found.');
            return;
        }

        try {
            $result = $this->executeStep($run, $step, $contact);

            if ($result === 'wait') {
                // Step set a wait — stop processing this run for now
                return;
            }

            $nextStep = $currentStep + 1;

            if (isset($steps[$nextStep])) {
                $this->db->prepare(
                    "UPDATE `{$runsTable}`
                     SET `current_step` = ?, `status` = 'running', `last_run_at` = NOW()
                     WHERE `id` = ?"
                )->execute([$nextStep, $runId]);
            } else {
                $this->markRunCompleted($runId);
            }
        } catch (Throwable $e) {
            $this->markRunFailed($runId, $e->getMessage());
        }
    }

    // ─── Step execution ───────────────────────────────────────────────────

    /**
     * Execute a single automation step.
     * Returns 'done' normally, or 'wait' if the step needs to delay.
     *
     * @param array<string, mixed> $run
     * @param array<string, mixed> $step
     * @param array<string, mixed> $contact
     */
    public function executeStep(array $run, array $step, array $contact): string
    {
        $config = json_decode((string) ($step['config'] ?? '{}'), true) ?? [];

        return match ($step['type']) {
            'email'      => $this->executeEmailStep($run, $config, $contact),
            'delay'      => $this->executeDelayStep($run, $config),
            'condition'  => $this->executeConditionStep($run, $config, $contact),
            'tag_add'    => $this->executeTagAddStep($contact, $config),
            'tag_remove' => $this->executeTagRemoveStep($contact, $config),
            default      => 'done',
        };
    }

    /**
     * Process all pending and ready automation runs.
     */
    public function processAllPendingRuns(): void
    {
        $runsTable = $this->prefix . 'automation_runs';

        $stmt = $this->db->query(
            "SELECT r.`id`
             FROM `{$runsTable}` r
             JOIN `{$this->prefix}automations` a ON a.`id` = r.`automation_id`
             WHERE r.`status` IN ('pending', 'running')
               AND a.`status` = 'active'
               AND (r.`last_run_at` IS NULL OR r.`last_run_at` < DATE_SUB(NOW(), INTERVAL 1 MINUTE))
             ORDER BY r.`started_at` ASC
             LIMIT 200"
        );
        $runs = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($runs as $runId) {
            try {
                $this->processRun((int) $runId);
            } catch (Throwable) {
                // Errors in one run must not stop others
            }
        }
    }

    // ─── Private step handlers ────────────────────────────────────────────

    /**
     * @param array<string, mixed> $run
     * @param array<string, mixed> $config
     * @param array<string, mixed> $contact
     */
    private function executeEmailStep(array $run, array $config, array $contact): string
    {
        $templateId = (int) ($config['template_id'] ?? 0);
        $subject    = (string) ($config['subject'] ?? '');

        if ($templateId === 0 && $subject === '') {
            return 'done'; // No email configured
        }

        $templateModel = new Template();
        $smtpModel     = new SmtpServer();
        $smtpServer    = $smtpModel->getPrimary();

        if ($smtpServer === null) {
            throw new \RuntimeException('No active SMTP server configured for automation email.');
        }

        $settingModel = new Setting();
        $smtpServer['password'] = $this->decryptSmtpPassword((string) ($smtpServer['password'] ?? ''));

        $html = '';
        $text = '';

        if ($templateId > 0) {
            $template = $templateModel->find($templateId);
            if ($template !== null) {
                $html    = (string) ($template['html_content'] ?? '');
                $text    = (string) ($template['text_content'] ?? '');
                $subject = $subject ?: (string) ($template['subject'] ?? '');
            }
        } else {
            $html = (string) ($config['html_content'] ?? '');
            $text = (string) ($config['text_content'] ?? '');
        }

        // Replace merge tags
        if ($html !== '') {
            $html = $templateModel->replaceMergeTags($html, $contact);
        }

        $emailService = new EmailService($smtpServer);
        $result       = $emailService->send(
            (string) $contact['email'],
            $subject,
            $html,
            $text
        );

        if (!$result['success']) {
            throw new \RuntimeException('Automation email send failed: ' . $result['error']);
        }

        // Log the sent email
        $this->db->prepare(
            "INSERT INTO `{$this->prefix}activity_logs`
             (`user_id`, `action`, `entity_type`, `entity_id`, `description`, `created_at`)
             VALUES (NULL, 'automation_email_sent', 'contact', ?, ?, NOW())"
        )->execute([
            $contact['id'],
            "Automation run #{$run['id']} sent email to {$contact['email']}: {$subject}",
        ]);

        return 'done';
    }

    /**
     * @param array<string, mixed> $run
     * @param array<string, mixed> $config
     */
    private function executeDelayStep(array $run, array $config): string
    {
        $delayValue = (int) ($config['delay_value'] ?? 0);
        $delayUnit  = (string) ($config['delay_unit'] ?? 'minutes');

        if ($delayValue <= 0) {
            return 'done';
        }

        $seconds = match ($delayUnit) {
            'minutes' => $delayValue * 60,
            'hours'   => $delayValue * 3600,
            'days'    => $delayValue * 86400,
            'weeks'   => $delayValue * 604800,
            default   => $delayValue * 60,
        };

        $resumeAt  = date('Y-m-d H:i:s', time() + $seconds);
        $runsTable = $this->prefix . 'automation_runs';

        // Set last_run_at to a future time so processAllPendingRuns won't re-pick this run
        $this->db->prepare(
            "UPDATE `{$runsTable}` SET `last_run_at` = ? WHERE `id` = ?"
        )->execute([$resumeAt, $run['id']]);

        return 'wait';
    }

    /**
     * @param array<string, mixed> $run
     * @param array<string, mixed> $config
     * @param array<string, mixed> $contact
     */
    private function executeConditionStep(array $run, array $config, array $contact): string
    {
        $field    = (string) ($config['field'] ?? '');
        $operator = (string) ($config['operator'] ?? 'equals');
        $value    = (string) ($config['value'] ?? '');

        if ($field === '') {
            return 'done';
        }

        $contactValue = (string) ($contact[$field] ?? '');
        $passes       = match ($operator) {
            'equals'        => $contactValue === $value,
            'not_equals'    => $contactValue !== $value,
            'contains'      => str_contains($contactValue, $value),
            'not_contains'  => !str_contains($contactValue, $value),
            'starts_with'   => str_starts_with($contactValue, $value),
            'ends_with'     => str_ends_with($contactValue, $value),
            'greater_than'  => is_numeric($contactValue) && (float) $contactValue > (float) $value,
            'less_than'     => is_numeric($contactValue) && (float) $contactValue < (float) $value,
            default         => false,
        };

        if (!$passes) {
            // Condition not met: advance past this step by returning done
            // The calling code will proceed to next step
        }

        return 'done';
    }

    /**
     * @param array<string, mixed> $contact
     * @param array<string, mixed> $config
     */
    private function executeTagAddStep(array $contact, array $config): string
    {
        $tagName = trim((string) ($config['tag_name'] ?? ''));

        if ($tagName === '') {
            return 'done';
        }

        $tagModel = new Tag();
        $tag      = $tagModel->findOrCreate($tagName);
        $tagModel->addToContact((int) $tag['id'], (int) $contact['id']);

        return 'done';
    }

    /**
     * @param array<string, mixed> $contact
     * @param array<string, mixed> $config
     */
    private function executeTagRemoveStep(array $contact, array $config): string
    {
        $tagName = trim((string) ($config['tag_name'] ?? ''));

        if ($tagName === '') {
            return 'done';
        }

        $tagModel = new Tag();
        $tag      = $tagModel->findBy('name', $tagName);

        if ($tag !== null) {
            $tagModel->removeFromContact((int) $tag['id'], (int) $contact['id']);
        }

        return 'done';
    }

    // ─── Run state helpers ────────────────────────────────────────────────

    private function markRunCompleted(int $runId): void
    {
        $this->db->prepare(
            "UPDATE `{$this->prefix}automation_runs`
             SET `status` = 'completed', `completed_at` = NOW(), `last_run_at` = NOW()
             WHERE `id` = ?"
        )->execute([$runId]);
    }

    private function markRunFailed(int $runId, string $errorMessage): void
    {
        $this->db->prepare(
            "UPDATE `{$this->prefix}automation_runs`
             SET `status` = 'failed', `error_message` = ?, `last_run_at` = NOW()
             WHERE `id` = ?"
        )->execute([$errorMessage, $runId]);
    }

    private function decryptSmtpPassword(string $encrypted): string
    {
        $key = $_ENV['APP_KEY'] ?? '';
        if ($key === '') {
            return (string) base64_decode($encrypted);
        }

        $decoded = base64_decode($encrypted);
        if ($decoded === false || strlen($decoded) < 16) {
            return $encrypted;
        }

        $iv        = substr($decoded, 0, 16);
        $data      = substr($decoded, 16);
        $decrypted = openssl_decrypt($data, 'AES-256-CBC', $key, 0, $iv);

        return $decrypted !== false ? $decrypted : $encrypted;
    }
}
