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

cronLog('process_bounces started.');

$queueService = new QueueService();

// Acquire distributed lock (5-minute expiry)
$lockAcquired = false;
try {
    $lockAcquired = $queueService->acquireLock('process_bounces', 300);
} catch (\Throwable $e) {
    cronLog('Warning: could not acquire lock (' . $e->getMessage() . '). Continuing without lock.');
    $lockAcquired = true;
}

if (!$lockAcquired) {
    cronLog('Lock busy — another worker is running. Exiting.');
    exit(0);
}

try {
    $db     = \MailForge\Core\Database::getInstance();
    $prefix = \MailForge\Core\Database::getPrefix();

    // Find distinct emails from campaign_recipients with status='bounced'
    // where the corresponding contact is not yet marked as bounced
    $stmt = $db->prepare(
        "SELECT DISTINCT cr.`email`
         FROM `{$prefix}campaign_recipients` cr
         INNER JOIN `{$prefix}contacts` ct ON ct.`email` = cr.`email`
         WHERE cr.`status` = 'bounced'
           AND ct.`status` != 'bounced'"
    );
    $stmt->execute();
    $bouncedEmails = $stmt->fetchAll(\PDO::FETCH_COLUMN);

    $updatedCount = 0;

    if (!empty($bouncedEmails)) {
        $placeholders = implode(',', array_fill(0, count($bouncedEmails), '?'));

        $updateStmt = $db->prepare(
            "UPDATE `{$prefix}contacts`
             SET `status` = 'bounced', `updated_at` = NOW()
             WHERE `email` IN ({$placeholders})
               AND `status` != 'bounced'"
        );
        $updateStmt->execute($bouncedEmails);
        $updatedCount = $updateStmt->rowCount();
    }

    cronLog("Contacts updated to bounced status: {$updatedCount}.");
} catch (\Throwable $e) {
    cronLog('Fatal error: ' . $e->getMessage());
} finally {
    try {
        $queueService->releaseLock('process_bounces');
    } catch (\Throwable) {
        // Lock table may not exist; ignore
    }
}

cronLog('process_bounces finished.');
