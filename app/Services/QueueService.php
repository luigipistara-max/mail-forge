<?php

declare(strict_types=1);

namespace MailForge\Services;

use MailForge\Core\Database;
use MailForge\Models\Campaign;
use MailForge\Models\Contact;
use MailForge\Models\Setting;
use MailForge\Models\SmtpServer;
use PDO;
use Throwable;

class QueueService
{
    private PDO    $db;
    private string $prefix;
    private string $lockHost;

    public function __construct()
    {
        $this->db       = Database::getInstance();
        $this->prefix   = Database::getPrefix();
        $this->lockHost = gethostname() ?: 'queue-worker';
    }

    // ─── Locking ──────────────────────────────────────────────────────────

    /**
     * Acquire a distributed lock using the cron_locks table.
     * Returns true on success, false if lock is already held.
     */
    public function acquireLock(string $lockName, int $lockTimeoutSeconds = 600): bool
    {
        $this->cleanExpiredLocks();

        $table     = $this->prefix . 'cron_locks';
        $expiresAt = date('Y-m-d H:i:s', time() + $lockTimeoutSeconds);
        $lockedBy  = $this->lockHost . ':' . getmypid();

        try {
            $stmt = $this->db->prepare(
                "INSERT INTO `{$table}` (`lock_name`, `locked_by`, `locked_at`, `expires_at`)
                 VALUES (:lock_name, :locked_by, NOW(), :expires_at)"
            );
            $stmt->execute([
                ':lock_name'  => $lockName,
                ':locked_by'  => $lockedBy,
                ':expires_at' => $expiresAt,
            ]);

            return $stmt->rowCount() === 1;
        } catch (\PDOException) {
            // Duplicate key means lock is already held
            return false;
        }
    }

    /**
     * Release a named lock.
     */
    public function releaseLock(string $lockName): void
    {
        $table = $this->prefix . 'cron_locks';
        $this->db->prepare("DELETE FROM `{$table}` WHERE `lock_name` = ?")
                 ->execute([$lockName]);
    }

    /**
     * Clean up locks that have passed their expiry time.
     */
    public function cleanExpiredLocks(): void
    {
        $table = $this->prefix . 'cron_locks';
        $this->db->exec("DELETE FROM `{$table}` WHERE `expires_at` < NOW()");
    }

    // ─── Campaign batch processing ────────────────────────────────────────

    /**
     * Process one sending batch for the given campaign.
     */
    public function processCampaignBatch(int $campaignId): void
    {
        $lockName = "campaign_batch_{$campaignId}";

        if (!$this->acquireLock($lockName, 600)) {
            return; // Another worker is processing this campaign
        }

        try {
            $this->runBatch($campaignId);
        } finally {
            $this->releaseLock($lockName);
        }
    }

    /**
     * Loop through all campaigns that need batch processing and dispatch them.
     */
    public function processAllPendingCampaigns(): void
    {
        $lockName = 'queue_processor';

        if (!$this->acquireLock($lockName, 300)) {
            return;
        }

        try {
            $campaignModel = new Campaign();
            $pending       = $campaignModel->getPendingBatches();

            foreach ($pending as $campaign) {
                $this->processCampaignBatch((int) $campaign['id']);
            }
        } finally {
            $this->releaseLock($lockName);
        }
    }

    // ─── Recipient preparation ────────────────────────────────────────────

    /**
     * Populate campaign_recipients from the campaign's list or segment.
     * Safe to call multiple times — uses INSERT IGNORE.
     */
    public function prepareCampaignRecipients(int $campaignId): int
    {
        $campaignModel = new Campaign();
        $campaign      = $campaignModel->find($campaignId);

        if ($campaign === null) {
            return 0;
        }

        $recipientsTable = $this->prefix . 'campaign_recipients';
        $contactsTable   = $this->prefix . 'contacts';
        $listContacts    = $this->prefix . 'list_contacts';

        $listId    = $campaign['list_id']    ? (int) $campaign['list_id']    : null;
        $segmentId = $campaign['segment_id'] ? (int) $campaign['segment_id'] : null;

        if ($listId !== null) {
            // Insert all subscribed contacts from the list
            $stmt = $this->db->prepare(
                "INSERT IGNORE INTO `{$recipientsTable}` (`campaign_id`, `contact_id`, `email`, `created_at`)
                 SELECT :campaign_id, c.`id`, c.`email`, NOW()
                 FROM `{$contactsTable}` c
                 INNER JOIN `{$listContacts}` lc ON lc.`contact_id` = c.`id`
                 WHERE lc.`list_id` = :list_id
                   AND lc.`status` = 'subscribed'
                   AND c.`status` = 'subscribed'
                   AND c.`deleted_at` IS NULL"
            );
            $stmt->execute([':campaign_id' => $campaignId, ':list_id' => $listId]);
            $inserted = $stmt->rowCount();
        } elseif ($segmentId !== null) {
            // Resolve segment contacts and insert
            $segmentService = new \MailForge\Models\Segment();
            $contactIds     = $segmentService->calculateContacts($segmentId);
            $inserted       = 0;

            foreach ($contactIds as $contactId) {
                // Fetch email for each contact
                $emailStmt = $this->db->prepare(
                    "SELECT `email` FROM `{$contactsTable}` WHERE `id` = ? AND `deleted_at` IS NULL LIMIT 1"
                );
                $emailStmt->execute([$contactId]);
                $email = $emailStmt->fetchColumn();

                if ($email === false) {
                    continue;
                }

                $insertStmt = $this->db->prepare(
                    "INSERT IGNORE INTO `{$recipientsTable}`
                     (`campaign_id`, `contact_id`, `email`, `created_at`)
                     VALUES (?, ?, ?, NOW())"
                );
                $insertStmt->execute([$campaignId, $contactId, $email]);
                $inserted += $insertStmt->rowCount();
            }
        } else {
            $inserted = 0;
        }

        // Update total_recipients count
        $countStmt = $this->db->prepare(
            "SELECT COUNT(*) FROM `{$recipientsTable}` WHERE `campaign_id` = ?"
        );
        $countStmt->execute([$campaignId]);
        $total = (int) $countStmt->fetchColumn();

        $this->db->prepare(
            "UPDATE `{$this->prefix}campaigns`
             SET `total_recipients` = ?, `pending_count` = ?, `updated_at` = NOW()
             WHERE `id` = ?"
        )->execute([$total, $total, $campaignId]);

        return $inserted;
    }

    /**
     * Mark recipients that are ineligible (bounced, unsubscribed, suppressed) as skipped.
     */
    public function skipIneligibleContacts(int $campaignId): int
    {
        $recipientsTable = $this->prefix . 'campaign_recipients';
        $contactsTable   = $this->prefix . 'contacts';

        $stmt = $this->db->prepare(
            "UPDATE `{$recipientsTable}` cr
             JOIN `{$contactsTable}` c ON c.`id` = cr.`contact_id`
             SET cr.`status` = 'skipped', cr.`updated_at` = NOW()
             WHERE cr.`campaign_id` = ?
               AND cr.`status` = 'pending'
               AND c.`status` NOT IN ('subscribed', 'active')"
        );
        $stmt->execute([$campaignId]);

        $skipped = $stmt->rowCount();

        if ($skipped > 0) {
            $this->db->prepare(
                "UPDATE `{$this->prefix}campaigns`
                 SET `pending_count` = GREATEST(0, `pending_count` - ?), `updated_at` = NOW()
                 WHERE `id` = ?"
            )->execute([$skipped, $campaignId]);
        }

        return $skipped;
    }

    // ─── Internal batch runner ────────────────────────────────────────────

    private function runBatch(int $campaignId): void
    {
        $campaignModel = new Campaign();
        $campaign      = $campaignModel->find($campaignId);

        if ($campaign === null) {
            return;
        }

        // Only process queued or sending campaigns
        if (!in_array($campaign['status'], ['queued', 'sending'], true)) {
            return;
        }

        // Respect next_batch_at throttle
        $nextBatchAt = $campaign['next_batch_at'] ?? null;
        if ($nextBatchAt !== null && strtotime($nextBatchAt) > time()) {
            return;
        }

        // Mark as 'sending' if still 'queued'
        if ($campaign['status'] === 'queued') {
            $campaignModel->markSending($campaignId);
            $campaign['status'] = 'sending';
        }

        $batchSize       = max(1, (int) ($campaign['batch_size'] ?? 100));
        $recipientsTable = $this->prefix . 'campaign_recipients';

        // Atomically lock a batch of pending recipients
        $lockId = uniqid('batch_', true);
        $this->db->prepare(
            "UPDATE `{$recipientsTable}`
             SET `status` = 'processing', `updated_at` = NOW()
             WHERE `campaign_id` = ?
               AND `status` = 'pending'
             LIMIT ?"
        )->execute([$campaignId, $batchSize]);

        // Fetch the locked batch
        $stmt = $this->db->prepare(
            "SELECT cr.*, c.email, c.first_name, c.last_name, c.status AS contact_status
             FROM `{$recipientsTable}` cr
             JOIN `{$this->prefix}contacts` c ON c.id = cr.contact_id
             WHERE cr.`campaign_id` = ? AND cr.`status` = 'processing'
             LIMIT ?"
        );
        $stmt->execute([$campaignId, $batchSize]);
        $batch = $stmt->fetchAll();

        if (empty($batch)) {
            // Check if all done
            $this->checkCampaignCompletion($campaignId, $campaignModel);
            return;
        }

        // Get SMTP server
        $smtpModel  = new SmtpServer();
        $smtpId     = $campaign['smtp_server_id'] ?? null;
        $smtpServer = $smtpId ? $smtpModel->find($smtpId) : $smtpModel->getPrimary();

        if ($smtpServer === null) {
            // Can't send — requeue the batch as pending and pause
            $this->db->prepare(
                "UPDATE `{$recipientsTable}`
                 SET `status` = 'pending', `updated_at` = NOW()
                 WHERE `campaign_id` = ? AND `status` = 'processing'"
            )->execute([$campaignId]);

            $campaignModel->update($campaignId, ['status' => 'paused']);
            return;
        }

        // Decrypt SMTP password
        $smtpServer['password'] = $this->decryptSmtpPassword((string) ($smtpServer['password'] ?? ''));

        $emailService = new EmailService($smtpServer);
        $sentCount    = 0;
        $failedCount  = 0;

        foreach ($batch as $recipient) {
            // Skip ineligible contacts
            if (!in_array($recipient['contact_status'], ['subscribed', 'active'], true)) {
                $campaignModel->updateRecipientStatus(
                    $campaignId,
                    (int) $recipient['contact_id'],
                    'skipped'
                );
                continue;
            }

            $contact = [
                'id'         => $recipient['contact_id'],
                'email'      => $recipient['email'],
                'first_name' => $recipient['first_name'],
                'last_name'  => $recipient['last_name'],
            ];

            $result = $emailService->sendCampaignMessage($campaign, $contact, $smtpServer);

            if ($result['success']) {
                $campaignModel->updateRecipientStatus(
                    $campaignId,
                    (int) $recipient['contact_id'],
                    'sent',
                    ['message_id' => $result['message_id'], 'sent_at' => date('Y-m-d H:i:s')]
                );
                $smtpModel->incrementSentCount((int) $smtpServer['id']);
                $sentCount++;
            } else {
                $campaignModel->updateRecipientStatus(
                    $campaignId,
                    (int) $recipient['contact_id'],
                    'failed',
                    ['error_message' => $result['error']]
                );
                $failedCount++;
            }
        }

        // Update campaign counts
        $pendingDelta = -($sentCount + $failedCount);
        $campaignModel->updateCounts($campaignId, $sentCount, $failedCount, $pendingDelta);

        // Check if too many failures — pause campaign
        $settingModel     = new Setting();
        $maxFailureRate   = (int) $settingModel->get('queue.max_failures_before_pause', 50);
        $updatedCampaign  = $campaignModel->find($campaignId);

        if ($updatedCampaign !== null && (int) ($updatedCampaign['failed_count'] ?? 0) >= $maxFailureRate) {
            $campaignModel->update($campaignId, ['status' => 'paused']);
            return;
        }

        // Schedule next batch
        $intervalMinutes = max(1, (int) ($campaign['batch_interval_minutes'] ?? 10));
        $nextBatchAt     = date('Y-m-d H:i:s', time() + $intervalMinutes * 60);
        $campaignModel->updateBatchTime($campaignId, $nextBatchAt);

        // Check completion
        $this->checkCampaignCompletion($campaignId, $campaignModel);
    }

    private function checkCampaignCompletion(int $campaignId, Campaign $campaignModel): void
    {
        $recipientsTable = $this->prefix . 'campaign_recipients';

        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM `{$recipientsTable}`
             WHERE `campaign_id` = ? AND `status` IN ('pending', 'processing')"
        );
        $stmt->execute([$campaignId]);
        $remaining = (int) $stmt->fetchColumn();

        if ($remaining === 0) {
            $campaignModel->markCompleted($campaignId);
        }
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
