#!/usr/bin/env php
<?php

declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    exit(1);
}

require __DIR__ . '/../bootstrap/app.php';

use MailForge\Services\AutomationService;
use MailForge\Services\QueueService;

function cronLog(string $message): void
{
    echo '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
}

cronLog('process_automations started.');

$queueService = new QueueService();

// Acquire distributed lock (10-minute expiry)
$lockAcquired = false;
try {
    $lockAcquired = $queueService->acquireLock('process_automations', 600);
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

    $automationService = new AutomationService();

    // Count pending runs before processing for reporting
    $pendingBefore = (int) $db->query(
        "SELECT COUNT(*)
         FROM `{$prefix}automation_runs`
         WHERE `status` IN ('pending', 'running')
           AND (`next_step_at` IS NULL OR `next_step_at` <= NOW())"
    )->fetchColumn();

    cronLog("Pending automation runs to process: {$pendingBefore}.");

    $automationService->processAllPendingRuns();

    $pendingAfter = (int) $db->query(
        "SELECT COUNT(*)
         FROM `{$prefix}automation_runs`
         WHERE `status` IN ('pending', 'running')
           AND (`next_step_at` IS NULL OR `next_step_at` <= NOW())"
    )->fetchColumn();

    $processed = max(0, $pendingBefore - $pendingAfter);
    cronLog("Automation runs processed: {$processed}. Remaining: {$pendingAfter}.");
} catch (\Throwable $e) {
    cronLog('Fatal error: ' . $e->getMessage());
} finally {
    try {
        $queueService->releaseLock('process_automations');
    } catch (\Throwable) {
        // Lock table may not exist; ignore
    }
}

cronLog('process_automations finished.');
