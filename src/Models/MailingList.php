<?php

declare(strict_types=1);

namespace MailForge\Models;

/**
 * MailingList model  (maps to the `lists` table)
 */
class MailingList extends BaseModel
{
    protected string $table = 'lists';

    // ----------------------------------------------------------------
    // Contact management
    // ----------------------------------------------------------------

    /**
     * Add a contact to this list (pivot insert).
     */
    public function addContact(int $listId, int $contactId): bool
    {
        // Ignore duplicate entries
        try {
            $this->rawExecute(
                'INSERT IGNORE INTO `contact_list` (contact_id, list_id) VALUES (?, ?)',
                [$contactId, $listId]
            );
            return true;
        } catch (\PDOException) {
            return false;
        }
    }

    /**
     * Remove a contact from this list.
     */
    public function removeContact(int $listId, int $contactId): bool
    {
        $affected = $this->rawExecute(
            'DELETE FROM `contact_list` WHERE contact_id = ? AND list_id = ?',
            [$contactId, $listId]
        );
        return $affected > 0;
    }

    /**
     * Return all contacts in a list.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getContacts(int $listId): array
    {
        return $this->rawQuery(
            'SELECT c.* FROM `contacts` c
             INNER JOIN `contact_list` cl ON cl.contact_id = c.id
             WHERE cl.list_id = ?
             ORDER BY c.email ASC',
            [$listId]
        );
    }

    /**
     * Return only active contacts in a list.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getActiveContacts(int $listId): array
    {
        return $this->rawQuery(
            'SELECT c.* FROM `contacts` c
             INNER JOIN `contact_list` cl ON cl.contact_id = c.id
             WHERE cl.list_id = ? AND c.status = ?
             ORDER BY c.email ASC',
            [$listId, CONTACT_STATUS_ACTIVE]
        );
    }

    /**
     * Return the total number of contacts in a list.
     */
    public function getContactCount(int $listId): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM `contact_list` WHERE list_id = ?'
        );
        $stmt->execute([$listId]);
        return (int) $stmt->fetchColumn();
    }

    // ----------------------------------------------------------------
    // Relationships
    // ----------------------------------------------------------------

    /**
     * Return campaigns that use this list.
     *
     * @return array<int, array<string, mixed>>
     */
    public function campaigns(int $listId): array
    {
        return (new Campaign())->findAll(['list_id' => $listId]);
    }
}
