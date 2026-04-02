<?php

declare(strict_types=1);

namespace MailForge\Models;

use MailForge\Core\Database;

class Campaign extends BaseModel
{
    protected static string $table = 'campaigns';
    protected static bool $softDelete = true;
    protected static array $fillable = [
        'name',
        'subject',
        'from_name',
        'from_email',
        'reply_to',
        'html_content',
        'plain_content',
        'template_id',
        'status',
        'scheduled_at',
        'sent_at',
        'completed_at',
        'next_batch_at',
        'batch_size',
        'total_recipients',
        'sent_count',
        'failed_count',
        'pending_count',
        'smtp_server_id',
        'list_id',
        'segment_id',
        'created_by',
    ];

    public function getByStatus(string $status): array
    {
        $table = $this->getTable();
        $sql   = "SELECT * FROM `{$table}` WHERE `status` = :status AND `deleted_at` IS NULL ORDER BY `created_at` DESC";

        return $this->executeQuery($sql, [':status' => $status])->fetchAll();
    }

    public function updateStatus(int|string $id, string $status): bool
    {
        $table = $this->getTable();
        $sql   = "UPDATE `{$table}` SET `status` = :status WHERE `id` = :id AND `deleted_at` IS NULL";

        $stmt = $this->executeQuery($sql, [':status' => $status, ':id' => $id]);

        return $stmt->rowCount() > 0;
    }

    public function getRecipients(int|string $campaignId, ?string $status = null): array
    {
        $recipTable = Database::getPrefix() . 'campaign_recipients';
        $sql        = "SELECT * FROM `{$recipTable}` WHERE `campaign_id` = :campaign_id";
        $bindings   = [':campaign_id' => $campaignId];

        if ($status !== null) {
            $sql              .= ' AND `status` = :status';
            $bindings[':status'] = $status;
        }

        $sql .= ' ORDER BY `id` ASC';

        return $this->executeQuery($sql, $bindings)->fetchAll();
    }

    public function addRecipient(int|string $campaignId, int|string $contactId, string $email): int|string
    {
        $recipTable = Database::getPrefix() . 'campaign_recipients';
        $sql        = "INSERT IGNORE INTO `{$recipTable}` (`campaign_id`, `contact_id`, `email`, `status`, `created_at`)
                       VALUES (:campaign_id, :contact_id, :email, 'pending', NOW())";

        $this->executeQuery($sql, [
            ':campaign_id' => $campaignId,
            ':contact_id'  => $contactId,
            ':email'       => $email,
        ]);

        return $this->db->lastInsertId();
    }

    /**
     * Batch-insert recipients. Each item: ['contact_id' => int, 'email' => string].
     */
    public function addRecipientsBatch(int|string $campaignId, array $contacts): int
    {
        if (empty($contacts)) {
            return 0;
        }

        $recipTable = Database::getPrefix() . 'campaign_recipients';
        $inserted   = 0;
        $chunkSize  = 500;

        foreach (array_chunk($contacts, $chunkSize) as $chunk) {
            $placeholderGroups = [];
            $bindings          = [];

            foreach ($chunk as $i => $contact) {
                $placeholderGroups[] = "(:campaign_id_{$i}, :contact_id_{$i}, :email_{$i}, 'pending', NOW())";
                $bindings[":campaign_id_{$i}"] = $campaignId;
                $bindings[":contact_id_{$i}"]  = $contact['contact_id'];
                $bindings[":email_{$i}"]        = $contact['email'];
            }

            $values = implode(', ', $placeholderGroups);
            $sql    = "INSERT IGNORE INTO `{$recipTable}` (`campaign_id`, `contact_id`, `email`, `status`, `created_at`) VALUES {$values}";

            $stmt     = $this->executeQuery($sql, $bindings);
            $inserted += $stmt->rowCount();
        }

        return $inserted;
    }

    public function updateRecipientStatus(
        int|string $campaignId,
        int|string $contactId,
        string $status,
        array $data = []
    ): bool {
        $recipTable = Database::getPrefix() . 'campaign_recipients';
        $setClauses = ['`status` = :status'];
        $bindings   = [
            ':status'      => $status,
            ':campaign_id' => $campaignId,
            ':contact_id'  => $contactId,
        ];

        foreach ($data as $col => $val) {
            $placeholder          = ":data_{$col}";
            $setClauses[]         = "`{$col}` = {$placeholder}";
            $bindings[$placeholder] = $val;
        }

        $setStr = implode(', ', $setClauses);
        $sql    = "UPDATE `{$recipTable}` SET {$setStr}
                   WHERE `campaign_id` = :campaign_id AND `contact_id` = :contact_id";

        $stmt = $this->executeQuery($sql, $bindings);

        return $stmt->rowCount() > 0;
    }

    public function getStats(int|string $campaignId): array
    {
        $recipTable = Database::getPrefix() . 'campaign_recipients';
        $linksTable = Database::getPrefix() . 'campaign_links';

        $statsSql = "SELECT
                         COUNT(*) AS total,
                         COUNT(CASE WHEN `status` = 'sent'       THEN 1 END) AS sent,
                         COUNT(CASE WHEN `status` = 'failed'     THEN 1 END) AS failed,
                         COUNT(CASE WHEN `status` = 'pending'    THEN 1 END) AS pending,
                         COUNT(CASE WHEN `opened_at` IS NOT NULL THEN 1 END) AS opened,
                         COUNT(CASE WHEN `status` = 'bounced'    THEN 1 END) AS bounced,
                         COUNT(CASE WHEN `status` = 'complained' THEN 1 END) AS complained,
                         COUNT(CASE WHEN `unsubscribed_at` IS NOT NULL THEN 1 END) AS unsubscribed
                     FROM `{$recipTable}`
                     WHERE `campaign_id` = :campaign_id";

        $stats = $this->executeQuery($statsSql, [':campaign_id' => $campaignId])->fetch();

        $clicksSql = "SELECT COALESCE(SUM(`click_count`), 0) AS total_clicks
                      FROM `{$linksTable}` WHERE `campaign_id` = :campaign_id";

        $clicks = $this->executeQuery($clicksSql, [':campaign_id' => $campaignId])->fetchColumn();

        if ($stats === false) {
            $stats = [];
        }

        $stats['total_clicks'] = (int) $clicks;

        $sent = (int) ($stats['sent'] ?? 0);

        $stats['open_rate']  = $sent > 0 ? round(((int) ($stats['opened'] ?? 0)) / $sent * 100, 2) : 0.0;
        $stats['click_rate'] = $sent > 0 ? round((int) $clicks / $sent * 100, 2) : 0.0;

        return $stats;
    }

    public function getPendingBatches(): array
    {
        $table = $this->getTable();
        $sql   = "SELECT * FROM `{$table}`
                  WHERE `status` = 'sending'
                    AND `next_batch_at` <= NOW()
                    AND `deleted_at` IS NULL
                  ORDER BY `next_batch_at` ASC";

        return $this->executeQuery($sql)->fetchAll();
    }

    public function markSending(int|string $id): bool
    {
        $table = $this->getTable();
        $sql   = "UPDATE `{$table}` SET `status` = 'sending', `sent_at` = COALESCE(`sent_at`, NOW()) WHERE `id` = :id";

        $stmt = $this->executeQuery($sql, [':id' => $id]);

        return $stmt->rowCount() > 0;
    }

    public function markCompleted(int|string $id): bool
    {
        $table = $this->getTable();
        $sql   = "UPDATE `{$table}` SET `status` = 'completed', `completed_at` = NOW() WHERE `id` = :id";

        $stmt = $this->executeQuery($sql, [':id' => $id]);

        return $stmt->rowCount() > 0;
    }

    public function updateBatchTime(int|string $id, string $nextBatchAt): bool
    {
        $table = $this->getTable();
        $sql   = "UPDATE `{$table}` SET `next_batch_at` = :next_batch_at WHERE `id` = :id";

        $stmt = $this->executeQuery($sql, [':next_batch_at' => $nextBatchAt, ':id' => $id]);

        return $stmt->rowCount() > 0;
    }

    public function updateCounts(int|string $id, int $sentDelta, int $failedDelta, int $pendingDelta): bool
    {
        $table = $this->getTable();
        $sql   = "UPDATE `{$table}`
                  SET `sent_count`    = `sent_count`    + :sent,
                      `failed_count`  = `failed_count`  + :failed,
                      `pending_count` = GREATEST(0, `pending_count` + :pending)
                  WHERE `id` = :id";

        $stmt = $this->executeQuery($sql, [
            ':sent'    => $sentDelta,
            ':failed'  => $failedDelta,
            ':pending' => $pendingDelta,
            ':id'      => $id,
        ]);

        return $stmt->rowCount() > 0;
    }

    public function getLinks(int|string $campaignId): array
    {
        $linksTable = Database::getPrefix() . 'campaign_links';
        $sql        = "SELECT * FROM `{$linksTable}` WHERE `campaign_id` = :campaign_id ORDER BY `click_count` DESC";

        return $this->executeQuery($sql, [':campaign_id' => $campaignId])->fetchAll();
    }

    public function addLink(int|string $campaignId, string $url): string
    {
        $linksTable    = Database::getPrefix() . 'campaign_links';
        $trackingCode  = bin2hex(random_bytes(8));

        $sql = "INSERT INTO `{$linksTable}` (`campaign_id`, `url`, `tracking_code`, `click_count`, `created_at`)
                VALUES (:campaign_id, :url, :tracking_code, 0, NOW())
                ON DUPLICATE KEY UPDATE `tracking_code` = `tracking_code`";

        $this->executeQuery($sql, [
            ':campaign_id'    => $campaignId,
            ':url'            => $url,
            ':tracking_code'  => $trackingCode,
        ]);

        // Return the tracking code for the existing or newly inserted row.
        $fetchSql = "SELECT `tracking_code` FROM `{$linksTable}`
                     WHERE `campaign_id` = :campaign_id AND `url` = :url LIMIT 1";

        $result = $this->executeQuery($fetchSql, [
            ':campaign_id' => $campaignId,
            ':url'         => $url,
        ])->fetchColumn();

        return $result !== false ? (string) $result : $trackingCode;
    }

    public function incrementLinkClick(string $trackingCode): bool
    {
        $linksTable = Database::getPrefix() . 'campaign_links';
        $sql        = "UPDATE `{$linksTable}` SET `click_count` = `click_count` + 1 WHERE `tracking_code` = :tracking_code";

        $stmt = $this->executeQuery($sql, [':tracking_code' => $trackingCode]);

        return $stmt->rowCount() > 0;
    }
}
