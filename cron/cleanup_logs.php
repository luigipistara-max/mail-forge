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

cronLog('cleanup_logs started.');

$queueService = new QueueService();

// Acquire distributed lock (10-minute expiry)
$lockAcquired = false;
try {
    $lockAcquired = $queueService->acquireLock('cleanup_logs', 600);
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

    // Retrieve retention period from settings (default: 90 days)
    $retentionDays = 90;
    try {
        $settingStmt = $db->prepare(
            "SELECT `value` FROM `{$prefix}settings` WHERE `key` = 'log_retention_days' LIMIT 1"
        );
        $settingStmt->execute();
        $settingValue = $settingStmt->fetchColumn();
        if ($settingValue !== false && is_numeric($settingValue)) {
            $retentionDays = max(1, (int) $settingValue);
        }
    } catch (\Throwable $e) {
        cronLog('Warning: could not read settings (' . $e->getMessage() . '). Using default retention.');
    }

    cronLog("Log retention period: {$retentionDays} days.");

    // Delete old activity logs
    $activityDeleted = 0;
    try {
        $stmt = $db->prepare(
            "DELETE FROM `{$prefix}activity_logs`
             WHERE `created_at` < DATE_SUB(NOW(), INTERVAL ? DAY)"
        );
        $stmt->execute([$retentionDays]);
        $activityDeleted = $stmt->rowCount();
        cronLog("Deleted {$activityDeleted} row(s) from activity_logs.");
    } catch (\Throwable $e) {
        cronLog('Warning: could not clean activity_logs (' . $e->getMessage() . ').');
    }

    // Delete old SMTP logs (table may not exist)
    $smtpDeleted = 0;
    try {
        $stmt = $db->prepare(
            "DELETE FROM `{$prefix}smtp_logs`
             WHERE `created_at` < DATE_SUB(NOW(), INTERVAL ? DAY)"
        );
        $stmt->execute([$retentionDays]);
        $smtpDeleted = $stmt->rowCount();
        cronLog("Deleted {$smtpDeleted} row(s) from smtp_logs.");
    } catch (\Throwable $e) {
        cronLog('Warning: smtp_logs not cleaned (' . $e->getMessage() . ').');
    }

    // Delete temp files older than 24 hours from storage/temp/
    $tempDir     = dirname(__DIR__) . '/storage/temp';
    $tempDeleted = 0;
    if (is_dir($tempDir)) {
        $cutoff = time() - 86400; // 24 hours
        $files  = new \DirectoryIterator($tempDir);
        foreach ($files as $file) {
            if ($file->isDot() || !$file->isFile()) {
                continue;
            }
            if ($file->getMTime() < $cutoff) {
                if (@unlink($file->getPathname())) {
                    $tempDeleted++;
                }
            }
        }
        cronLog("Deleted {$tempDeleted} temp file(s) from storage/temp/.");
    } else {
        cronLog('storage/temp/ directory not found — skipping.');
    }

    // Delete expired session files from storage/sessions/ if it exists
    $sessionsDir     = dirname(__DIR__) . '/storage/sessions';
    $sessionsDeleted = 0;
    if (is_dir($sessionsDir)) {
        $sessionCutoff = time() - 86400;
        $sessionFiles  = new \DirectoryIterator($sessionsDir);
        foreach ($sessionFiles as $file) {
            if ($file->isDot() || !$file->isFile()) {
                continue;
            }
            if ($file->getMTime() < $sessionCutoff) {
                if (@unlink($file->getPathname())) {
                    $sessionsDeleted++;
                }
            }
        }
        cronLog("Deleted {$sessionsDeleted} expired session file(s) from storage/sessions/.");
    }

    cronLog(
        "Summary: activity_logs={$activityDeleted}, smtp_logs={$smtpDeleted}, "
        . "temp_files={$tempDeleted}, session_files={$sessionsDeleted}."
    );
} catch (\Throwable $e) {
    cronLog('Fatal error: ' . $e->getMessage());
} finally {
    try {
        $queueService->releaseLock('cleanup_logs');
    } catch (\Throwable) {
        // Lock table may not exist; ignore
    }
}

cronLog('cleanup_logs finished.');
