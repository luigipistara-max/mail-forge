<?php

declare(strict_types=1);

namespace MailForge\Models;

/**
 * CampaignRecipient model  (maps to the `campaign_recipients` table)
 */
class CampaignRecipient extends BaseModel
{
    protected string $table = 'campaign_recipients';

    // ----------------------------------------------------------------
    // Status helpers
    // ----------------------------------------------------------------

    public function markAsSent(int $recipientId): bool
    {
        return $this->update($recipientId, [
            'status'  => RECIPIENT_STATUS_SENT,
            'sent_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function markAsDelivered(int $recipientId): bool
    {
        return $this->update($recipientId, [
            'status'       => RECIPIENT_STATUS_DELIVERED,
            'delivered_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function markAsFailed(int $recipientId, string $reason = ''): bool
    {
        return $this->update($recipientId, [
            'status'         => RECIPIENT_STATUS_FAILED,
            'failed_at'      => date('Y-m-d H:i:s'),
            'failure_reason' => $reason,
        ]);
    }
}
