<?php

declare(strict_types=1);

namespace MailForge\Controllers;

use MailForge\Core\Controller;
use MailForge\Core\Database;
use MailForge\Models\ActivityLog;
use MailForge\Models\Campaign;
use MailForge\Models\Contact;

class DashboardController extends Controller
{
    public function index(): never
    {
        $this->requireAuth();

        $contactModel  = new Contact();
        $campaignModel = new Campaign();
        $activityLog   = new ActivityLog();
        $prefix        = Database::getPrefix();

        // ── Totals ────────────────────────────────────────────────────────
        $totalContacts      = $contactModel->count();
        $activeSubscribers  = $contactModel->count(['status' => 'subscribed']);

        // ── Campaigns sent this month ─────────────────────────────────────
        $monthStart = date('Y-m-01 00:00:00');
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM `{$prefix}campaigns`
             WHERE `status` IN ('completed','sending')
               AND `started_at` >= ?
               AND `deleted_at` IS NULL"
        );
        $stmt->execute([$monthStart]);
        $campaignsSentThisMonth = (int) $stmt->fetchColumn();

        // ── Average open / click rates ────────────────────────────────────
        $stmt = $this->db->query(
            "SELECT
                AVG(CASE WHEN `total_recipients` > 0
                         THEN (`opened_count` / `total_recipients`) * 100
                         ELSE 0 END) AS avg_open_rate,
                AVG(CASE WHEN `total_recipients` > 0
                         THEN (`clicked_count` / `total_recipients`) * 100
                         ELSE 0 END) AS avg_click_rate
             FROM `{$prefix}campaigns`
             WHERE `status` = 'completed' AND `deleted_at` IS NULL"
        );
        $rates         = $stmt->fetch();
        $avgOpenRate   = round((float) ($rates['avg_open_rate'] ?? 0), 2);
        $avgClickRate  = round((float) ($rates['avg_click_rate'] ?? 0), 2);

        // ── Recent campaigns ──────────────────────────────────────────────
        $stmt = $this->db->prepare(
            "SELECT c.*, l.name AS list_name
             FROM `{$prefix}campaigns` c
             LEFT JOIN `{$prefix}lists` l ON l.id = c.list_id
             WHERE c.`deleted_at` IS NULL
             ORDER BY c.`created_at` DESC
             LIMIT 5"
        );
        $stmt->execute();
        $recentCampaigns = $stmt->fetchAll();

        // ── Recent activity ───────────────────────────────────────────────
        $recentActivity = $activityLog->getRecent(10);

        // ── Charts: campaigns per month (last 12 months) ──────────────────
        $stmt = $this->db->query(
            "SELECT
                DATE_FORMAT(`created_at`, '%Y-%m') AS month,
                COUNT(*) AS total
             FROM `{$prefix}campaigns`
             WHERE `created_at` >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
               AND `deleted_at` IS NULL
             GROUP BY month
             ORDER BY month ASC"
        );
        $campaignsPerMonth = $stmt->fetchAll();

        // ── Charts: opens / clicks per month (last 12 months) ────────────
        $stmt = $this->db->query(
            "SELECT
                DATE_FORMAT(`created_at`, '%Y-%m') AS month,
                SUM(`opened_count`)  AS opens,
                SUM(`clicked_count`) AS clicks
             FROM `{$prefix}campaigns`
             WHERE `created_at` >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
               AND `deleted_at` IS NULL
             GROUP BY month
             ORDER BY month ASC"
        );
        $openClickTrends = $stmt->fetchAll();

        $this->render('dashboard/index', [
            'totalContacts'          => $totalContacts,
            'activeSubscribers'      => $activeSubscribers,
            'campaignsSentThisMonth' => $campaignsSentThisMonth,
            'avgOpenRate'            => $avgOpenRate,
            'avgClickRate'           => $avgClickRate,
            'recentCampaigns'        => $recentCampaigns,
            'recentActivity'         => $recentActivity,
            'campaignsPerMonth'      => $campaignsPerMonth,
            'openClickTrends'        => $openClickTrends,
        ]);
    }
}
