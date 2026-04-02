<?php

declare(strict_types=1);

namespace MailForge\Models;

/**
 * TrackingEvent model  (maps to the `tracking_events` table)
 */
class TrackingEvent extends BaseModel
{
    protected string $table = 'tracking_events';

    // ----------------------------------------------------------------
    // Event logging helpers
    // ----------------------------------------------------------------

    /**
     * @param array<string, mixed> $eventData
     */
    public function logOpen(int $campaignId, int $contactId, array $eventData = [], string $ipAddress = '', string $userAgent = ''): int
    {
        return $this->logEvent($campaignId, $contactId, 'open', $eventData, $ipAddress, $userAgent);
    }

    /**
     * @param array<string, mixed> $eventData  Should include 'url' key.
     */
    public function logClick(int $campaignId, int $contactId, array $eventData = [], string $ipAddress = '', string $userAgent = ''): int
    {
        return $this->logEvent($campaignId, $contactId, 'click', $eventData, $ipAddress, $userAgent);
    }

    /**
     * @param array<string, mixed> $eventData
     */
    public function logBounce(int $campaignId, int $contactId, array $eventData = []): int
    {
        return $this->logEvent($campaignId, $contactId, 'bounce', $eventData);
    }

    /**
     * @param array<string, mixed> $eventData
     */
    public function logUnsubscribe(int $campaignId, int $contactId, array $eventData = [], string $ipAddress = ''): int
    {
        return $this->logEvent($campaignId, $contactId, 'unsubscribe', $eventData, $ipAddress);
    }

    // ----------------------------------------------------------------
    // Queries
    // ----------------------------------------------------------------

    /**
     * Return all tracking events for a campaign.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getEventsByCampaign(int $campaignId): array
    {
        return $this->findAll(['campaign_id' => $campaignId], 'created_at', 'DESC');
    }

    /**
     * Return all tracking events for a contact.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getEventsByContact(int $contactId): array
    {
        return $this->findAll(['contact_id' => $contactId], 'created_at', 'DESC');
    }

    // ----------------------------------------------------------------
    // Internal
    // ----------------------------------------------------------------

    /**
     * @param array<string, mixed> $eventData
     */
    private function logEvent(
        int $campaignId,
        int $contactId,
        string $eventType,
        array $eventData = [],
        string $ipAddress = '',
        string $userAgent = ''
    ): int {
        return $this->create([
            'campaign_id' => $campaignId,
            'contact_id'  => $contactId,
            'event_type'  => $eventType,
            'event_data'  => !empty($eventData) ? json_encode($eventData) : null,
            'ip_address'  => $ipAddress ?: null,
            'user_agent'  => $userAgent ?: null,
        ]);
    }
}
