<?php

declare(strict_types=1);

namespace MailForge\Models;

use MailForge\Core\Database;

class Contact extends BaseModel
{
    protected static string $table = 'contacts';
    protected static bool $softDelete = true;
    protected static array $fillable = [
        'email',
        'first_name',
        'last_name',
        'phone',
        'status',
        'source',
        'ip_address',
        'double_optin_token',
        'double_optin_confirmed_at',
        'unsubscribed_at',
        'unsubscribe_reason',
        'bounced_at',
        'bounce_type',
        'bounce_message',
        'complained_at',
    ];

    public function findByEmail(string $email): ?array
    {
        $table = $this->getTable();
        $sql   = "SELECT * FROM `{$table}` WHERE `email` = :email AND `deleted_at` IS NULL LIMIT 1";

        $stmt = $this->executeQuery($sql, [':email' => $email]);
        $row  = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    public function findSubscribed(?int $listId = null): array
    {
        $table = $this->getTable();

        if ($listId !== null) {
            $pivotTable = Database::getPrefix() . 'list_contacts';
            $sql        = "SELECT c.* FROM `{$table}` c
                           INNER JOIN `{$pivotTable}` lc ON lc.`contact_id` = c.`id`
                           WHERE lc.`list_id` = :list_id
                             AND lc.`status` = 'subscribed'
                             AND c.`status` = 'subscribed'
                             AND c.`deleted_at` IS NULL";

            return $this->executeQuery($sql, [':list_id' => $listId])->fetchAll();
        }

        $sql = "SELECT * FROM `{$table}` WHERE `status` = 'subscribed' AND `deleted_at` IS NULL ORDER BY `id` DESC";

        return $this->executeQuery($sql)->fetchAll();
    }

    public function updateStatus(int|string $id, string $status): bool
    {
        $table = $this->getTable();
        $sql   = "UPDATE `{$table}` SET `status` = :status WHERE `id` = :id AND `deleted_at` IS NULL";

        $stmt = $this->executeQuery($sql, [':status' => $status, ':id' => $id]);

        return $stmt->rowCount() > 0;
    }

    public function markBounced(int|string $id, string $bounceType, string $message): bool
    {
        $table = $this->getTable();
        $sql   = "UPDATE `{$table}`
                  SET `status` = 'bounced',
                      `bounce_type` = :bounce_type,
                      `bounce_message` = :message,
                      `bounced_at` = NOW()
                  WHERE `id` = :id AND `deleted_at` IS NULL";

        $stmt = $this->executeQuery($sql, [
            ':bounce_type' => $bounceType,
            ':message'     => $message,
            ':id'          => $id,
        ]);

        return $stmt->rowCount() > 0;
    }

    public function markComplained(int|string $id): bool
    {
        $table = $this->getTable();
        $sql   = "UPDATE `{$table}`
                  SET `status` = 'complained',
                      `complained_at` = NOW()
                  WHERE `id` = :id AND `deleted_at` IS NULL";

        $stmt = $this->executeQuery($sql, [':id' => $id]);

        return $stmt->rowCount() > 0;
    }

    public function unsubscribe(int|string $id, string $reason = ''): bool
    {
        $table = $this->getTable();
        $sql   = "UPDATE `{$table}`
                  SET `status` = 'unsubscribed',
                      `unsubscribed_at` = NOW(),
                      `unsubscribe_reason` = :reason
                  WHERE `id` = :id AND `deleted_at` IS NULL";

        $stmt = $this->executeQuery($sql, [':reason' => $reason, ':id' => $id]);

        return $stmt->rowCount() > 0;
    }

    public function confirmDoubleOptin(string $token): ?array
    {
        $table = $this->getTable();
        $sql   = "SELECT * FROM `{$table}` WHERE `double_optin_token` = :token AND `deleted_at` IS NULL LIMIT 1";

        $stmt = $this->executeQuery($sql, [':token' => $token]);
        $row  = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        $updateSql = "UPDATE `{$table}`
                      SET `status` = 'subscribed',
                          `double_optin_confirmed_at` = NOW(),
                          `double_optin_token` = NULL
                      WHERE `id` = :id";

        $this->executeQuery($updateSql, [':id' => $row['id']]);

        return $this->find($row['id']);
    }

    public function getCustomFieldValues(int|string $contactId): array
    {
        $cfTable = Database::getPrefix() . 'contact_custom_field_values';
        $fTable  = Database::getPrefix() . 'custom_fields';

        $sql = "SELECT cfv.*, cf.`name`, cf.`type`
                FROM `{$cfTable}` cfv
                INNER JOIN `{$fTable}` cf ON cf.`id` = cfv.`field_id`
                WHERE cfv.`contact_id` = :contact_id";

        return $this->executeQuery($sql, [':contact_id' => $contactId])->fetchAll();
    }

    public function setCustomFieldValue(int|string $contactId, int|string $fieldId, mixed $value): bool
    {
        $cfTable = Database::getPrefix() . 'contact_custom_field_values';
        $sql     = "INSERT INTO `{$cfTable}` (`contact_id`, `field_id`, `value`)
                    VALUES (:contact_id, :field_id, :value)
                    ON DUPLICATE KEY UPDATE `value` = :value_upd";

        $stmt = $this->executeQuery($sql, [
            ':contact_id' => $contactId,
            ':field_id'   => $fieldId,
            ':value'      => $value,
            ':value_upd'  => $value,
        ]);

        return $stmt->rowCount() > 0;
    }

    public function search(string $term, int $limit = 20): array
    {
        $table = $this->getTable();
        $like  = '%' . $term . '%';
        $sql   = "SELECT * FROM `{$table}`
                  WHERE `deleted_at` IS NULL
                    AND (`email` LIKE :like OR `first_name` LIKE :like2 OR `last_name` LIKE :like3)
                  ORDER BY `email` ASC
                  LIMIT {$limit}";

        return $this->executeQuery($sql, [':like' => $like, ':like2' => $like, ':like3' => $like])->fetchAll();
    }

    /**
     * Bulk-insert contacts with email-based deduplication.
     * Each row in $data must be an associative array of column => value.
     * Returns ['inserted' => int, 'skipped' => int].
     */
    public function importCsv(array $data): array
    {
        $table    = $this->getTable();
        $inserted = 0;
        $skipped  = 0;

        $fillable = static::$fillable;

        foreach ($data as $row) {
            if (empty($row['email'])) {
                $skipped++;
                continue;
            }

            $existing = $this->findByEmail($row['email']);

            if ($existing !== null) {
                $skipped++;
                continue;
            }

            $filteredRow = !empty($fillable)
                ? array_intersect_key($row, array_flip($fillable))
                : $row;

            if (empty($filteredRow)) {
                $skipped++;
                continue;
            }

            $columns      = array_keys($filteredRow);
            $placeholders = array_map(fn(string $c) => ":{$c}", $columns);
            $colList      = implode(', ', array_map(fn(string $c) => "`{$c}`", $columns));
            $valList      = implode(', ', $placeholders);
            $sql          = "INSERT INTO `{$table}` ({$colList}) VALUES ({$valList})";
            $bindings     = array_combine($placeholders, array_values($filteredRow));

            $this->executeQuery($sql, $bindings);
            $inserted++;
        }

        return ['inserted' => $inserted, 'skipped' => $skipped];
    }

    public function getStats(): array
    {
        $table = $this->getTable();
        $sql   = "SELECT `status`, COUNT(*) AS `count`
                  FROM `{$table}`
                  WHERE `deleted_at` IS NULL
                  GROUP BY `status`";

        $rows  = $this->executeQuery($sql)->fetchAll();
        $stats = [];

        foreach ($rows as $row) {
            $stats[$row['status']] = (int) $row['count'];
        }

        return $stats;
    }
}
