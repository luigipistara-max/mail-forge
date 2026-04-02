<?php

declare(strict_types=1);

namespace MailForge\Models;

use MailForge\Core\Database;

class ContactList extends BaseModel
{
    protected static string $table = 'lists';
    protected static bool $softDelete = true;
    protected static array $fillable = [
        'name',
        'description',
        'from_name',
        'from_email',
        'reply_to',
        'double_optin',
        'subscriber_count',
        'status',
    ];

    public function getWithCounts(): array
    {
        $table      = $this->getTable();
        $pivotTable = Database::getPrefix() . 'list_contacts';

        $sql = "SELECT l.*,
                    COUNT(CASE WHEN lc.`status` = 'subscribed' THEN 1 END) AS `subscriber_count`,
                    COUNT(CASE WHEN lc.`status` = 'unsubscribed' THEN 1 END) AS `unsubscribed_count`
                FROM `{$table}` l
                LEFT JOIN `{$pivotTable}` lc ON lc.`list_id` = l.`id`
                WHERE l.`deleted_at` IS NULL
                GROUP BY l.`id`
                ORDER BY l.`created_at` DESC";

        return $this->executeQuery($sql)->fetchAll();
    }

    public function addContact(int|string $listId, int|string $contactId, string $status = 'subscribed'): bool
    {
        $pivotTable = Database::getPrefix() . 'list_contacts';
        $sql        = "INSERT INTO `{$pivotTable}` (`list_id`, `contact_id`, `status`, `subscribed_at`)
                       VALUES (:list_id, :contact_id, :status, NOW())
                       ON DUPLICATE KEY UPDATE `status` = :status_upd, `subscribed_at` = IF(`status` != 'subscribed' AND :status_upd2 = 'subscribed', NOW(), `subscribed_at`)";

        $stmt = $this->executeQuery($sql, [
            ':list_id'     => $listId,
            ':contact_id'  => $contactId,
            ':status'      => $status,
            ':status_upd'  => $status,
            ':status_upd2' => $status,
        ]);

        if ($status === 'subscribed') {
            $this->updateSubscriberCount($listId);
        }

        return $stmt->rowCount() > 0;
    }

    public function removeContact(int|string $listId, int|string $contactId): bool
    {
        $pivotTable = Database::getPrefix() . 'list_contacts';
        $sql        = "DELETE FROM `{$pivotTable}` WHERE `list_id` = :list_id AND `contact_id` = :contact_id";

        $stmt = $this->executeQuery($sql, [
            ':list_id'    => $listId,
            ':contact_id' => $contactId,
        ]);

        $this->updateSubscriberCount($listId);

        return $stmt->rowCount() > 0;
    }

    public function getContacts(
        int|string $listId,
        ?string $status = null,
        ?int $limit = null,
        ?int $offset = null
    ): array {
        $contactTable = Database::getPrefix() . 'contacts';
        $pivotTable   = Database::getPrefix() . 'list_contacts';

        $sql      = "SELECT c.*, lc.`status` AS `list_status`, lc.`subscribed_at`
                     FROM `{$contactTable}` c
                     INNER JOIN `{$pivotTable}` lc ON lc.`contact_id` = c.`id`
                     WHERE lc.`list_id` = :list_id AND c.`deleted_at` IS NULL";
        $bindings = [':list_id' => $listId];

        if ($status !== null) {
            $sql              .= " AND lc.`status` = :status";
            $bindings[':status'] = $status;
        }

        $sql .= ' ORDER BY lc.`subscribed_at` DESC';

        if ($limit !== null) {
            $sql .= " LIMIT {$limit}";
        }

        if ($offset !== null) {
            $sql .= " OFFSET {$offset}";
        }

        return $this->executeQuery($sql, $bindings)->fetchAll();
    }

    public function getContactCount(int|string $listId, ?string $status = null): int
    {
        $pivotTable = Database::getPrefix() . 'list_contacts';
        $sql        = "SELECT COUNT(*) FROM `{$pivotTable}` WHERE `list_id` = :list_id";
        $bindings   = [':list_id' => $listId];

        if ($status !== null) {
            $sql              .= ' AND `status` = :status';
            $bindings[':status'] = $status;
        }

        return (int) $this->executeQuery($sql, $bindings)->fetchColumn();
    }

    public function updateSubscriberCount(int|string $listId): bool
    {
        $table      = $this->getTable();
        $pivotTable = Database::getPrefix() . 'list_contacts';

        $sql = "UPDATE `{$table}` l
                SET l.`subscriber_count` = (
                    SELECT COUNT(*) FROM `{$pivotTable}` lc
                    WHERE lc.`list_id` = l.`id` AND lc.`status` = 'subscribed'
                )
                WHERE l.`id` = :list_id";

        $stmt = $this->executeQuery($sql, [':list_id' => $listId]);

        return $stmt->rowCount() > 0;
    }

    public function isSubscribed(int|string $listId, int|string $contactId): bool
    {
        $pivotTable = Database::getPrefix() . 'list_contacts';
        $sql        = "SELECT COUNT(*) FROM `{$pivotTable}`
                       WHERE `list_id` = :list_id AND `contact_id` = :contact_id AND `status` = 'subscribed'";

        $count = (int) $this->executeQuery($sql, [
            ':list_id'    => $listId,
            ':contact_id' => $contactId,
        ])->fetchColumn();

        return $count > 0;
    }
}
