<?php

declare(strict_types=1);

namespace MailForge\Models;

/**
 * Contact model
 */
class Contact extends BaseModel
{
    protected string $table = 'contacts';

    // ----------------------------------------------------------------
    // Lookup
    // ----------------------------------------------------------------

    /**
     * Find a contact by email within a specific user scope.
     *
     * @return array<string, mixed>|null
     */
    public function findByEmail(string $email, int $userId): ?array
    {
        return $this->findOne(['email' => strtolower(trim($email)), 'user_id' => $userId]);
    }

    // ----------------------------------------------------------------
    // Subscription management
    // ----------------------------------------------------------------

    public function subscribe(int $contactId): bool
    {
        return $this->update($contactId, [
            'status'        => CONTACT_STATUS_ACTIVE,
            'subscribed_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function unsubscribe(int $contactId): bool
    {
        return $this->update($contactId, [
            'status'           => CONTACT_STATUS_INACTIVE,
            'unsubscribed_at'  => date('Y-m-d H:i:s'),
        ]);
    }

    // ----------------------------------------------------------------
    // Import / Export
    // ----------------------------------------------------------------

    /**
     * Import contacts from CSV data (array of associative rows).
     *
     * Expected columns: email, first_name, last_name, phone (all optional except email).
     *
     * @param array<int, array<string, mixed>> $rows
     * @return array{imported: int, skipped: int, errors: array<string>}
     */
    public function import(array $rows, int $userId): array
    {
        $result = ['imported' => 0, 'skipped' => 0, 'errors' => []];

        foreach ($rows as $index => $row) {
            $email = strtolower(trim($row['email'] ?? ''));

            if ($email === '') {
                $result['skipped']++;
                $result['errors'][] = "Row {$index}: missing email.";
                continue;
            }

            // Skip duplicates
            if ($this->findByEmail($email, $userId) !== null) {
                $result['skipped']++;
                continue;
            }

            $customFields = [];
            $knownCols    = ['email', 'first_name', 'last_name', 'phone'];
            foreach ($row as $key => $val) {
                if (!in_array($key, $knownCols, true)) {
                    $customFields[$key] = $val;
                }
            }

            try {
                $this->create([
                    'user_id'       => $userId,
                    'email'         => $email,
                    'first_name'    => $row['first_name'] ?? null,
                    'last_name'     => $row['last_name']  ?? null,
                    'phone'         => $row['phone']      ?? null,
                    'status'        => CONTACT_STATUS_ACTIVE,
                    'custom_fields' => !empty($customFields) ? json_encode($customFields) : null,
                    'subscribed_at' => date('Y-m-d H:i:s'),
                ]);
                $result['imported']++;
            } catch (\Exception $e) {
                $result['skipped']++;
                $result['errors'][] = "Row {$index}: " . $e->getMessage();
            }
        }

        return $result;
    }

    /**
     * Export all active contacts for a user as an array of rows.
     *
     * @return array<int, array<string, mixed>>
     */
    public function export(int $userId): array
    {
        return $this->findAll(['user_id' => $userId, 'status' => CONTACT_STATUS_ACTIVE], 'email');
    }

    // ----------------------------------------------------------------
    // Custom fields
    // ----------------------------------------------------------------

    /**
     * Return decoded custom fields for a contact.
     *
     * @return array<string, mixed>
     */
    public function getCustomFields(int $contactId): array
    {
        $contact = $this->find($contactId);
        if ($contact === null || empty($contact['custom_fields'])) {
            return [];
        }
        return json_decode($contact['custom_fields'], true) ?? [];
    }

    /**
     * Merge new values into a contact's custom fields.
     *
     * @param array<string, mixed> $fields
     */
    public function setCustomFields(int $contactId, array $fields): bool
    {
        $existing = $this->getCustomFields($contactId);
        $merged   = array_merge($existing, $fields);
        return $this->update($contactId, ['custom_fields' => json_encode($merged)]);
    }

    // ----------------------------------------------------------------
    // Relationships
    // ----------------------------------------------------------------

    /**
     * Return all mailing lists a contact belongs to.
     *
     * @return array<int, array<string, mixed>>
     */
    public function lists(int $contactId): array
    {
        return $this->rawQuery(
            'SELECT l.* FROM `lists` l
             INNER JOIN `contact_list` cl ON cl.list_id = l.id
             WHERE cl.contact_id = ?
             ORDER BY l.name ASC',
            [$contactId]
        );
    }

    /**
     * Return tracking events for a contact.
     *
     * @return array<int, array<string, mixed>>
     */
    public function trackingEvents(int $contactId): array
    {
        return (new TrackingEvent())->getEventsByContact($contactId);
    }
}
