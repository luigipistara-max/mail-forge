<?php

declare(strict_types=1);

namespace MailForge\Models;

/**
 * Campaign model
 */
class Campaign extends BaseModel
{
    protected string $table = 'campaigns';

    // ----------------------------------------------------------------
    // Status transitions
    // ----------------------------------------------------------------

    public function schedule(int $campaignId, string $scheduledAt): bool
    {
        return $this->update($campaignId, [
            'status'       => CAMPAIGN_STATUS_SCHEDULED,
            'scheduled_at' => $scheduledAt,
        ]);
    }

    public function start(int $campaignId): bool
    {
        return $this->update($campaignId, [
            'status'     => CAMPAIGN_STATUS_RUNNING,
            'started_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function pause(int $campaignId): bool
    {
        // A running campaign can be paused by resetting to scheduled without a time
        return $this->update($campaignId, [
            'status'       => CAMPAIGN_STATUS_SCHEDULED,
            'scheduled_at' => null,
        ]);
    }

    public function cancel(int $campaignId): bool
    {
        return $this->update($campaignId, [
            'status'       => CAMPAIGN_STATUS_CANCELLED,
            'completed_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function complete(int $campaignId): bool
    {
        return $this->update($campaignId, [
            'status'       => CAMPAIGN_STATUS_COMPLETED,
            'completed_at' => date('Y-m-d H:i:s'),
        ]);
    }

    // ----------------------------------------------------------------
    // Recipients
    // ----------------------------------------------------------------

    /**
     * Return all campaign_recipients rows for a campaign.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getRecipients(int $campaignId): array
    {
        return (new CampaignRecipient())->findAll(['campaign_id' => $campaignId]);
    }

    // ----------------------------------------------------------------
    // Statistics
    // ----------------------------------------------------------------

    /**
     * Calculate delivery/engagement statistics for a campaign.
     *
     * @return array{
     *   total: int,
     *   sent: int,
     *   delivered: int,
     *   failed: int,
     *   opens: int,
     *   clicks: int,
     *   bounces: int,
     *   unsubscribes: int,
     *   open_rate: float,
     *   click_rate: float,
     *   bounce_rate: float,
     *   delivery_rate: float
     * }
     */
    public function getStats(int $campaignId): array
    {
        // Recipient counts
        $recipientRows = $this->rawQuery(
            'SELECT status, COUNT(*) as cnt FROM `campaign_recipients` WHERE campaign_id = ? GROUP BY status',
            [$campaignId]
        );

        $sent      = 0;
        $delivered = 0;
        $failed    = 0;

        foreach ($recipientRows as $row) {
            match ($row['status']) {
                RECIPIENT_STATUS_SENT      => $sent      = (int) $row['cnt'],
                RECIPIENT_STATUS_DELIVERED => $delivered = (int) $row['cnt'],
                RECIPIENT_STATUS_FAILED    => $failed    = (int) $row['cnt'],
                default                    => null,
            };
        }

        $total = $sent + $delivered + $failed;

        // Tracking event counts
        $eventRows = $this->rawQuery(
            'SELECT event_type, COUNT(*) as cnt FROM `tracking_events` WHERE campaign_id = ? GROUP BY event_type',
            [$campaignId]
        );

        $opens        = 0;
        $clicks       = 0;
        $bounces      = 0;
        $unsubscribes = 0;

        foreach ($eventRows as $row) {
            match ($row['event_type']) {
                'open'        => $opens        = (int) $row['cnt'],
                'click'       => $clicks       = (int) $row['cnt'],
                'bounce'      => $bounces      = (int) $row['cnt'],
                'unsubscribe' => $unsubscribes = (int) $row['cnt'],
                default       => null,
            };
        }

        $base = $total > 0 ? $total : 1; // avoid division by zero

        return [
            'total'         => $total,
            'sent'          => $sent,
            'delivered'     => $delivered,
            'failed'        => $failed,
            'opens'         => $opens,
            'clicks'        => $clicks,
            'bounces'       => $bounces,
            'unsubscribes'  => $unsubscribes,
            'open_rate'     => round($opens        / $base * 100, 2),
            'click_rate'    => round($clicks       / $base * 100, 2),
            'bounce_rate'   => round($bounces      / $base * 100, 2),
            'delivery_rate' => round($delivered    / $base * 100, 2),
        ];
    }

    // ----------------------------------------------------------------
    // Relationships
    // ----------------------------------------------------------------

    /**
     * @return array<string, mixed>|null
     */
    public function template(int $campaignId): ?array
    {
        $campaign = $this->find($campaignId);
        if ($campaign === null || empty($campaign['template_id'])) {
            return null;
        }
        return (new Template())->find((int) $campaign['template_id']);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function list(int $campaignId): ?array
    {
        $campaign = $this->find($campaignId);
        if ($campaign === null) {
            return null;
        }
        return (new MailingList())->find((int) $campaign['list_id']);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function trackingEvents(int $campaignId): array
    {
        return (new TrackingEvent())->getEventsByCampaign($campaignId);
    }
}
