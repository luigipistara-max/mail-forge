<?php

declare(strict_types=1);

namespace MailForge\Controllers;

use MailForge\Core\Controller;
use MailForge\Core\Database;
use MailForge\Models\Campaign;
use MailForge\Models\Contact;

class ReportController extends Controller
{
    // ─── Overview ─────────────────────────────────────────────────────────

    public function index(): never
    {
        $this->requireAuth();

        $prefix = Database::getPrefix();

        // Global campaign stats
        $stmt = $this->db->query(
            "SELECT
                COUNT(*) AS total_campaigns,
                SUM(`total_recipients`) AS total_sent,
                SUM(`opened_count`) AS total_opens,
                SUM(`clicked_count`) AS total_clicks,
                SUM(`bounced_count`) AS total_bounces,
                SUM(`unsubscribed_count`) AS total_unsubscribes,
                AVG(CASE WHEN `total_recipients` > 0
                         THEN (`opened_count`/`total_recipients`)*100
                         ELSE 0 END) AS avg_open_rate,
                AVG(CASE WHEN `total_recipients` > 0
                         THEN (`clicked_count`/`total_recipients`)*100
                         ELSE 0 END) AS avg_click_rate
             FROM `{$prefix}campaigns`
             WHERE `status` = 'completed' AND `deleted_at` IS NULL"
        );
        $globalStats = $stmt->fetch();

        // Contact growth per month
        $stmt = $this->db->query(
            "SELECT DATE_FORMAT(`created_at`, '%Y-%m') AS month, COUNT(*) AS total
             FROM `{$prefix}contacts`
             WHERE `created_at` >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
               AND `deleted_at` IS NULL
             GROUP BY month ORDER BY month ASC"
        );
        $contactGrowth = $stmt->fetchAll();

        // Top campaigns by open rate
        $stmt = $this->db->query(
            "SELECT `name`, `subject`, `total_recipients`, `opened_count`, `clicked_count`,
                    ROUND((`opened_count`/GREATEST(`total_recipients`,1))*100, 2) AS open_rate,
                    ROUND((`clicked_count`/GREATEST(`total_recipients`,1))*100, 2) AS click_rate
             FROM `{$prefix}campaigns`
             WHERE `status` = 'completed' AND `total_recipients` > 0 AND `deleted_at` IS NULL
             ORDER BY open_rate DESC
             LIMIT 10"
        );
        $topCampaigns = $stmt->fetchAll();

        $contactModel      = new Contact();
        $totalContacts     = $contactModel->count();
        $activeSubscribers = $contactModel->count(['status' => 'subscribed']);

        $this->render('reports/index', [
            'globalStats'      => $globalStats,
            'contactGrowth'    => $contactGrowth,
            'topCampaigns'     => $topCampaigns,
            'totalContacts'    => $totalContacts,
            'activeSubscribers'=> $activeSubscribers,
        ]);
    }

    // ─── Campaign Detail ──────────────────────────────────────────────────

    public function campaign(int|string $id): never
    {
        $this->requireAuth();

        $campaignModel = new Campaign();
        $campaign      = $campaignModel->find($id);

        if ($campaign === null) {
            $this->abort(404, 'Campaign not found.');
        }

        $prefix = Database::getPrefix();
        $stats  = $campaignModel->getStats((int) $id);
        $links  = $campaignModel->getLinks((int) $id);

        // Hourly open distribution
        $stmt = $this->db->prepare(
            "SELECT HOUR(`opened_at`) AS hour, COUNT(*) AS opens
             FROM `{$prefix}campaign_recipients`
             WHERE `campaign_id` = ? AND `opened_at` IS NOT NULL
             GROUP BY hour ORDER BY hour ASC"
        );
        $stmt->execute([$id]);
        $opensByHour = $stmt->fetchAll();

        // Opens over time (by day)
        $stmt = $this->db->prepare(
            "SELECT DATE(`opened_at`) AS day, COUNT(*) AS opens
             FROM `{$prefix}campaign_recipients`
             WHERE `campaign_id` = ? AND `opened_at` IS NOT NULL
             GROUP BY day ORDER BY day ASC"
        );
        $stmt->execute([$id]);
        $opensByDay = $stmt->fetchAll();

        // Recipient status breakdown
        $stmt = $this->db->prepare(
            "SELECT `status`, COUNT(*) AS total
             FROM `{$prefix}campaign_recipients`
             WHERE `campaign_id` = ?
             GROUP BY `status`"
        );
        $stmt->execute([$id]);
        $statusBreakdown = $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);

        // Recent bounces / unsubscribes
        $stmt = $this->db->prepare(
            "SELECT cr.email, cr.status, cr.error_message, cr.updated_at
             FROM `{$prefix}campaign_recipients` cr
             WHERE cr.campaign_id = ? AND cr.status IN ('bounced','unsubscribed','failed')
             ORDER BY cr.updated_at DESC
             LIMIT 50"
        );
        $stmt->execute([$id]);
        $problems = $stmt->fetchAll();

        $this->render('reports/campaign', [
            'campaign'        => $campaign,
            'stats'           => $stats,
            'links'           => $links,
            'opensByHour'     => $opensByHour,
            'opensByDay'      => $opensByDay,
            'statusBreakdown' => $statusBreakdown,
            'problems'        => $problems,
        ]);
    }

    // ─── Export Campaign Report CSV ───────────────────────────────────────

    public function export(int|string $id): never
    {
        $this->requireAuth();

        $campaignModel = new Campaign();
        $campaign      = $campaignModel->find($id);

        if ($campaign === null) {
            $this->abort(404, 'Campaign not found.');
        }

        $prefix = Database::getPrefix();
        $stmt   = $this->db->prepare(
            "SELECT cr.email, cr.status,
                    cr.sent_at, cr.opened_at, cr.clicked_at,
                    cr.open_count, cr.click_count, cr.error_message,
                    co.first_name, co.last_name
             FROM `{$prefix}campaign_recipients` cr
             LEFT JOIN `{$prefix}contacts` co ON co.id = cr.contact_id
             WHERE cr.campaign_id = ?
             ORDER BY cr.email ASC"
        );
        $stmt->execute([$id]);
        $recipients = $stmt->fetchAll();

        $filename = 'campaign-report-' . preg_replace('/[^a-z0-9]+/', '-', strtolower((string) $campaign['name']))
            . '-' . date('Y-m-d') . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        header('Cache-Control: no-cache, no-store, must-revalidate');

        $out = fopen('php://output', 'w');
        fputcsv($out, [
            'email', 'first_name', 'last_name', 'status',
            'sent_at', 'opened_at', 'clicked_at',
            'open_count', 'click_count', 'error_message',
        ]);

        foreach ($recipients as $row) {
            fputcsv($out, [
                $row['email'],
                $row['first_name'],
                $row['last_name'],
                $row['status'],
                $row['sent_at'],
                $row['opened_at'],
                $row['clicked_at'],
                $row['open_count'],
                $row['click_count'],
                $row['error_message'],
            ]);
        }

        fclose($out);
        exit(0);
    }

    // ─── Contact Growth ───────────────────────────────────────────────────

    public function contacts(): never
    {
        $this->requireAuth();

        $prefix = Database::getPrefix();

        $stmt = $this->db->query(
            "SELECT DATE_FORMAT(`created_at`, '%Y-%m') AS month,
                    COUNT(*) AS new_contacts,
                    SUM(CASE WHEN `status` = 'subscribed' THEN 1 ELSE 0 END) AS subscribed,
                    SUM(CASE WHEN `status` = 'unsubscribed' THEN 1 ELSE 0 END) AS unsubscribed,
                    SUM(CASE WHEN `status` = 'bounced' THEN 1 ELSE 0 END) AS bounced
             FROM `{$prefix}contacts`
             WHERE `created_at` >= DATE_SUB(NOW(), INTERVAL 24 MONTH)
               AND `deleted_at` IS NULL
             GROUP BY month ORDER BY month ASC"
        );
        $monthlyGrowth = $stmt->fetchAll();

        $contactModel = new Contact();
        $stats        = $contactModel->getStats();

        $this->render('reports/contacts', [
            'monthlyGrowth' => $monthlyGrowth,
            'stats'         => $stats,
        ]);
    }

    // ─── Link Click Report ────────────────────────────────────────────────

    public function links(int|string $campaignId): never
    {
        $this->requireAuth();

        $campaignModel = new Campaign();
        $campaign      = $campaignModel->find($campaignId);

        if ($campaign === null) {
            $this->abort(404, 'Campaign not found.');
        }

        $links  = $campaignModel->getLinks((int) $campaignId);
        $prefix = Database::getPrefix();

        // Clicks per link with contact details
        $clickDetails = [];
        foreach ($links as $link) {
            $stmt = $this->db->prepare(
                "SELECT co.email, co.first_name, co.last_name, lc.clicked_at
                 FROM `{$prefix}link_clicks` lc
                 JOIN `{$prefix}contacts` co ON co.id = lc.contact_id
                 WHERE lc.campaign_link_id = ?
                 ORDER BY lc.clicked_at DESC
                 LIMIT 100"
            );
            $stmt->execute([$link['id']]);
            $clickDetails[$link['id']] = $stmt->fetchAll();
        }

        $this->render('reports/links', [
            'campaign'     => $campaign,
            'links'        => $links,
            'clickDetails' => $clickDetails,
        ]);
    }
}
