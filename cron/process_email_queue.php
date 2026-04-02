#!/usr/bin/env php
<?php

declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    exit(1);
}

require __DIR__ . '/../bootstrap/app.php';

use MailForge\Services\QueueService;

function cronLog(string $message): void
{
    echo '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
}

cronLog('process_email_queue started.');

$queueService = new QueueService();

// Acquire distributed lock (5-minute expiry)
$lockAcquired = false;
try {
    $lockAcquired = $queueService->acquireLock('process_email_queue', 300);
} catch (\Throwable $e) {
    cronLog('Warning: could not acquire lock (' . $e->getMessage() . '). Continuing without lock.');
    $lockAcquired = true; // proceed anyway
}

if (!$lockAcquired) {
    cronLog('Lock busy — another worker is running. Exiting.');
    exit(0);
}

try {
    $db     = \MailForge\Core\Database::getInstance();
    $prefix = \MailForge\Core\Database::getPrefix();

    // Find campaigns ready for batch processing
    $stmt = $db->prepare(
        "SELECT c.*, ss.`host`, ss.`port`, ss.`username`, ss.`password`,
                ss.`encryption`, ss.`from_name`, ss.`from_email`, ss.`reply_to`
         FROM `{$prefix}campaigns` c
         LEFT JOIN `{$prefix}smtp_servers` ss ON ss.`id` = c.`smtp_server_id`
         WHERE c.`status` IN ('queued', 'sending')
           AND (c.`next_batch_at` IS NULL OR c.`next_batch_at` <= NOW())"
    );
    $stmt->execute();
    $campaigns = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    cronLog('Campaigns ready to process: ' . count($campaigns));

    $totalSent   = 0;
    $totalFailed = 0;

    foreach ($campaigns as $campaign) {
        $campaignId = (int) $campaign['id'];
        $batchSize  = max(1, (int) ($campaign['batch_size'] ?? 50));
        $intervalMins = max(1, (int) ($campaign['batch_interval_minutes'] ?? 5));

        cronLog("Processing campaign #{$campaignId} (batch_size={$batchSize}).");

        // Lock individual recipients to this batch
        $selectStmt = $db->prepare(
            "SELECT `id`, `contact_id`, `email`
             FROM `{$prefix}campaign_recipients`
             WHERE `campaign_id` = ? AND `status` = 'pending'
             LIMIT ?"
        );
        $selectStmt->execute([$campaignId, $batchSize]);
        $recipients = $selectStmt->fetchAll(\PDO::FETCH_ASSOC);

        if (empty($recipients)) {
            // Check if campaign is fully done
            $pendingCount = (int) $db->query(
                "SELECT COUNT(*) FROM `{$prefix}campaign_recipients`
                 WHERE `campaign_id` = {$campaignId}
                   AND `status` IN ('pending', 'processing')"
            )->fetchColumn();

            if ($pendingCount === 0) {
                $db->prepare(
                    "UPDATE `{$prefix}campaigns`
                     SET `status` = 'completed', `sent_at` = NOW()
                     WHERE `id` = ?"
                )->execute([$campaignId]);
                cronLog("Campaign #{$campaignId} marked completed.");
            }
            continue;
        }

        $recipientIds = array_column($recipients, 'id');
        $placeholders = implode(',', array_fill(0, count($recipientIds), '?'));

        // Mark as processing
        $updateStmt = $db->prepare(
            "UPDATE `{$prefix}campaign_recipients`
             SET `status` = 'processing'
             WHERE `id` IN ({$placeholders}) AND `status` = 'pending'"
        );
        $updateStmt->execute($recipientIds);

        $smtpConfig = [
            'host'       => $campaign['host']       ?? '',
            'port'       => (int) ($campaign['port'] ?? 587),
            'username'   => $campaign['username']   ?? '',
            'password'   => $campaign['password']   ?? '',
            'encryption' => $campaign['encryption'] ?? 'tls',
            'from_name'  => $campaign['from_name']  ?? '',
            'from_email' => $campaign['from_email'] ?? '',
            'reply_to'   => $campaign['reply_to']   ?? '',
        ];

        $batchSent   = 0;
        $batchFailed = 0;

        foreach ($recipients as $recipient) {
            $recipientId = (int) $recipient['id'];
            $email       = (string) $recipient['email'];

            try {
                // Fetch full contact record for merge tags
                $contactStmt = $db->prepare(
                    "SELECT * FROM `{$prefix}contacts` WHERE `id` = ? LIMIT 1"
                );
                $contactStmt->execute([(int) $recipient['contact_id']]);
                $contact = $contactStmt->fetch(\PDO::FETCH_ASSOC) ?: ['id' => $recipient['contact_id'], 'email' => $email];

                $emailService = new \MailForge\Services\EmailService($smtpConfig);
                $result       = $emailService->sendCampaignMessage($campaign, $contact, $smtpConfig);

                if ($result['success']) {
                    $db->prepare(
                        "UPDATE `{$prefix}campaign_recipients`
                         SET `status` = 'sent', `sent_at` = NOW(), `message_id` = ?
                         WHERE `id` = ?"
                    )->execute([$result['message_id'] ?? '', $recipientId]);
                    $batchSent++;
                } else {
                    $db->prepare(
                        "UPDATE `{$prefix}campaign_recipients`
                         SET `status` = 'failed', `error_message` = ?
                         WHERE `id` = ?"
                    )->execute([$result['error'] ?? 'Unknown error', $recipientId]);
                    $batchFailed++;
                }
            } catch (\Throwable $e) {
                $db->prepare(
                    "UPDATE `{$prefix}campaign_recipients`
                     SET `status` = 'failed', `error_message` = ?
                     WHERE `id` = ?"
                )->execute([substr($e->getMessage(), 0, 500), $recipientId]);
                $batchFailed++;
                cronLog("Error sending to {$email}: " . $e->getMessage());
            }
        }

        // Update campaign counters and schedule next batch
        $db->prepare(
            "UPDATE `{$prefix}campaigns`
             SET `sent_count`   = `sent_count`   + ?,
                 `failed_count` = `failed_count` + ?,
                 `status`       = 'sending',
                 `next_batch_at` = DATE_ADD(NOW(), INTERVAL ? MINUTE)
             WHERE `id` = ?"
        )->execute([$batchSent, $batchFailed, $intervalMins, $campaignId]);

        $totalSent   += $batchSent;
        $totalFailed += $batchFailed;

        cronLog("Campaign #{$campaignId}: sent={$batchSent}, failed={$batchFailed}.");

        // Check completion after this batch
        $remainingCount = (int) $db->query(
            "SELECT COUNT(*) FROM `{$prefix}campaign_recipients`
             WHERE `campaign_id` = {$campaignId}
               AND `status` IN ('pending', 'processing')"
        )->fetchColumn();

        if ($remainingCount === 0) {
            $db->prepare(
                "UPDATE `{$prefix}campaigns`
                 SET `status` = 'completed', `sent_at` = NOW()
                 WHERE `id` = ?"
            )->execute([$campaignId]);
            cronLog("Campaign #{$campaignId} marked completed.");
            continue;
        }

        // Pause campaign if failure rate > 50% with at least 10 attempts
        $triedTotal = $batchSent + $batchFailed;
        if ($triedTotal >= 10 && $batchFailed / $triedTotal > 0.5) {
            $db->prepare(
                "UPDATE `{$prefix}campaigns` SET `status` = 'paused' WHERE `id` = ?"
            )->execute([$campaignId]);
            cronLog("Campaign #{$campaignId} paused — high failure rate ({$batchFailed}/{$triedTotal}).");
        }
    }

    cronLog("Summary: total_sent={$totalSent}, total_failed={$totalFailed}.");
} catch (\Throwable $e) {
    cronLog('Fatal error: ' . $e->getMessage());
} finally {
    try {
        $queueService->releaseLock('process_email_queue');
    } catch (\Throwable) {
        // Lock table may not exist; ignore
    }
}

cronLog('process_email_queue finished.');
