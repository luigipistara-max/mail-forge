<?php

declare(strict_types=1);

namespace MailForge\Models;

use MailForge\Core\Database;

class Tag extends BaseModel
{
    protected static string $table = 'tags';
    protected static array $fillable = [
        'name',
        'slug',
        'color',
        'contact_count',
    ];

    public function addToContact(int|string $tagId, int|string $contactId): bool
    {
        $pivotTable = Database::getPrefix() . 'contact_tags';
        $sql        = "INSERT IGNORE INTO `{$pivotTable}` (`tag_id`, `contact_id`, `created_at`)
                       VALUES (:tag_id, :contact_id, NOW())";

        $stmt = $this->executeQuery($sql, [
            ':tag_id'     => $tagId,
            ':contact_id' => $contactId,
        ]);

        if ($stmt->rowCount() > 0) {
            $this->updateCount($tagId);
        }

        return $stmt->rowCount() > 0;
    }

    public function removeFromContact(int|string $tagId, int|string $contactId): bool
    {
        $pivotTable = Database::getPrefix() . 'contact_tags';
        $sql        = "DELETE FROM `{$pivotTable}` WHERE `tag_id` = :tag_id AND `contact_id` = :contact_id";

        $stmt = $this->executeQuery($sql, [
            ':tag_id'     => $tagId,
            ':contact_id' => $contactId,
        ]);

        $this->updateCount($tagId);

        return $stmt->rowCount() > 0;
    }

    public function getContactTags(int|string $contactId): array
    {
        $table      = $this->getTable();
        $pivotTable = Database::getPrefix() . 'contact_tags';

        $sql = "SELECT t.* FROM `{$table}` t
                INNER JOIN `{$pivotTable}` ct ON ct.`tag_id` = t.`id`
                WHERE ct.`contact_id` = :contact_id
                ORDER BY t.`name` ASC";

        return $this->executeQuery($sql, [':contact_id' => $contactId])->fetchAll();
    }

    public function getTagContacts(int|string $tagId): array
    {
        $contactTable = Database::getPrefix() . 'contacts';
        $pivotTable   = Database::getPrefix() . 'contact_tags';

        $sql = "SELECT c.* FROM `{$contactTable}` c
                INNER JOIN `{$pivotTable}` ct ON ct.`contact_id` = c.`id`
                WHERE ct.`tag_id` = :tag_id AND c.`deleted_at` IS NULL
                ORDER BY c.`email` ASC";

        return $this->executeQuery($sql, [':tag_id' => $tagId])->fetchAll();
    }

    public function updateCount(int|string $tagId): bool
    {
        $table      = $this->getTable();
        $pivotTable = Database::getPrefix() . 'contact_tags';

        $sql = "UPDATE `{$table}` t
                SET t.`contact_count` = (
                    SELECT COUNT(*) FROM `{$pivotTable}` ct WHERE ct.`tag_id` = t.`id`
                )
                WHERE t.`id` = :tag_id";

        $stmt = $this->executeQuery($sql, [':tag_id' => $tagId]);

        return $stmt->rowCount() > 0;
    }

    public function findOrCreate(string $name): array
    {
        $table    = $this->getTable();
        $existing = $this->findBy('name', $name);

        if ($existing !== null) {
            return $existing;
        }

        $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9-]+/', '-', $name), '-'));
        $id   = $this->create(['name' => $name, 'slug' => $slug, 'contact_count' => 0]);

        return $this->find($id) ?? ['id' => $id, 'name' => $name, 'slug' => $slug];
    }
}
