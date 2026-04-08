<?php

declare(strict_types=1);

namespace MailForge\Controllers;

use MailForge\Core\Controller;
use MailForge\Core\Database;
use MailForge\Helpers\CsrfHelper;
use MailForge\Models\ActivityLog;
use MailForge\Models\Campaign;
use MailForge\Models\ContactList;
use MailForge\Models\Segment;
use MailForge\Models\SmtpServer;
use MailForge\Models\Template;
use MailForge\Services\EmailService;
use MailForge\Services\QueueService;
use MailForge\Validators\Validator;

class CampaignController extends Controller
{
    private Campaign $campaignModel;

    public function __construct()
    {
        parent::__construct();
        $this->campaignModel = new Campaign();
    }

    // ─── Index ────────────────────────────────────────────────────────────

    public function index(): never
    {
        $this->requireAuth();

        $page    = max(1, (int) ($this->request->query['page'] ?? 1));
        $perPage = 20;
        $status  = (string) ($this->request->query['status'] ?? '');
        $prefix  = Database::getPrefix();

        $where    = '`c`.`deleted_at` IS NULL';
        $bindings = [];

        if ($status !== '') {
            $where .= ' AND `c`.`status` = :status';
            $bindings[':status'] = $status;
        }

        $countStmt = $this->db->prepare(
            "SELECT COUNT(*) FROM `{$prefix}campaigns` c WHERE {$where}"
        );
        $countStmt->execute($bindings);
        $total = (int) $countStmt->fetchColumn();

        $offset   = ($page - 1) * $perPage;
        $dataStmt = $this->db->prepare(
            "SELECT c.*, l.name AS list_name
             FROM `{$prefix}campaigns` c
             LEFT JOIN `{$prefix}lists` l ON l.id = c.list_id
             WHERE {$where}
             ORDER BY c.`created_at` DESC
             LIMIT {$perPage} OFFSET {$offset}"
        );
        $dataStmt->execute($bindings);
        $campaigns = $dataStmt->fetchAll();

        $this->render('campaigns/index', [
            'campaigns' => $campaigns,
            'total'     => $total,
            'page'      => $page,
            'perPage'   => $perPage,
            'lastPage'  => (int) ceil($total / $perPage),
            'status'    => $status,
            'success'   => $this->getFlash('success'),
            'error'     => $this->getFlash('error'),
        ]);
    }

    // ─── Show / Report ────────────────────────────────────────────────────

    public function show(int|string $id): never
    {
        $this->requireAuth();

        $campaign = $this->campaignModel->find($id);
        if ($campaign === null) {
            $this->abort(404, 'Campaign not found.');
        }

        $stats     = $this->campaignModel->getStats((int) $id);
        $links     = $this->campaignModel->getLinks((int) $id);
        $prefix    = Database::getPrefix();

        $stmt = $this->db->prepare(
            "SELECT cr.status, COUNT(*) AS total
             FROM `{$prefix}campaign_recipients` cr
             WHERE cr.campaign_id = ?
             GROUP BY cr.status"
        );
        $stmt->execute([$id]);
        $recipientSummary = $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);

        $this->render('campaigns/show', [
            'campaign'         => $campaign,
            'stats'            => $stats,
            'links'            => $links,
            'recipientSummary' => $recipientSummary,
        ]);
    }

    // ─── Create ───────────────────────────────────────────────────────────

    public function create(): never
    {
        $this->requireAuth();

        $listModel     = new ContactList();
        $segmentModel  = new Segment();
        $templateModel = new Template();
        $smtpModel     = new SmtpServer();

        $this->render('campaigns/form', [
            'csrf'        => CsrfHelper::getToken(),
            'lists'       => $listModel->findAll(),
            'segments'    => $segmentModel->findAll(),
            'templates'   => $templateModel->getActive(),
            'smtpServers' => $smtpModel->getActive(),
            'error'       => $this->getFlash('error'),
        ]);
    }

    // ─── Store ────────────────────────────────────────────────────────────

    public function store(): never
    {
        $this->requireAuth();

        $csrfToken = (string) ($this->request->body['_csrf_token'] ?? '');
        if (!CsrfHelper::validate($csrfToken)) {
            $this->flash('error', 'Invalid security token.');
            $this->redirect('/campaigns/create');
        }

        $data = $this->extractCampaignData();

        $validator = Validator::make($data, [
            'name'    => 'required|max:200',
            'subject' => 'required|max:500',
        ]);

        if ($validator->fails()) {
            $this->flash('error', implode(' ', $validator->allErrors()));
            $this->redirect('/campaigns/create');
        }

        $data['uuid']       = \MailForge\Helpers\UuidHelper::generate();
        $data['status']     = 'draft';
        $data['created_by'] = $this->currentUser()['id'] ?? null;
        $data['created_at'] = date('Y-m-d H:i:s');

        $campaignId = $this->campaignModel->create($data);

        (new ActivityLog())->log(
            $this->currentUser()['id'] ?? null,
            'campaign_created',
            'campaign',
            $campaignId,
            "Campaign '{$data['name']}' created"
        );

        $this->flash('success', 'Campaign created.');
        $this->redirect("/campaigns/{$campaignId}/edit");
    }

    // ─── Edit ─────────────────────────────────────────────────────────────

    public function edit(int|string $id): never
    {
        $this->requireAuth();

        $campaign = $this->campaignModel->find($id);
        if ($campaign === null) {
            $this->abort(404, 'Campaign not found.');
        }

        if (!in_array($campaign['status'], ['draft', 'scheduled'], true)) {
            $this->flash('error', 'Only draft or scheduled campaigns can be edited.');
            $this->redirect("/campaigns/{$id}");
        }

        $listModel     = new ContactList();
        $segmentModel  = new Segment();
        $templateModel = new Template();
        $smtpModel     = new SmtpServer();

        $this->render('campaigns/form', [
            'csrf'        => CsrfHelper::getToken(),
            'campaign'    => $campaign,
            'lists'       => $listModel->findAll(),
            'segments'    => $segmentModel->findAll(),
            'templates'   => $templateModel->getActive(),
            'smtpServers' => $smtpModel->getActive(),
            'error'       => $this->getFlash('error'),
            'success'     => $this->getFlash('success'),
        ]);
    }

    // ─── Update ───────────────────────────────────────────────────────────

    public function update(int|string $id): never
    {
        $this->requireAuth();

        $csrfToken = (string) ($this->request->body['_csrf_token'] ?? '');
        if (!CsrfHelper::validate($csrfToken)) {
            $this->flash('error', 'Invalid security token.');
            $this->redirect("/campaigns/{$id}/edit");
        }

        $campaign = $this->campaignModel->find($id);
        if ($campaign === null) {
            $this->abort(404, 'Campaign not found.');
        }

        if (!in_array($campaign['status'], ['draft', 'scheduled'], true)) {
            $this->flash('error', 'Only draft or scheduled campaigns can be updated.');
            $this->redirect("/campaigns/{$id}");
        }

        $data = $this->extractCampaignData();

        $validator = Validator::make($data, [
            'name'    => 'required|max:200',
            'subject' => 'required|max:500',
        ]);

        if ($validator->fails()) {
            $this->flash('error', implode(' ', $validator->allErrors()));
            $this->redirect("/campaigns/{$id}/edit");
        }

        $this->campaignModel->update((int) $id, $data);

        (new ActivityLog())->log(
            $this->currentUser()['id'] ?? null,
            'campaign_updated',
            'campaign',
            $id,
            "Campaign '{$data['name']}' updated"
        );

        $this->flash('success', 'Campaign saved.');
        $this->redirect("/campaigns/{$id}/edit");
    }

    // ─── Delete ───────────────────────────────────────────────────────────

    public function delete(int|string $id): never
    {
        $this->requireAuth();

        $csrfToken = (string) ($this->request->body['_csrf_token'] ?? '');
        if (!CsrfHelper::validate($csrfToken)) {
            $this->flash('error', 'Invalid security token.');
            $this->redirect('/campaigns');
        }

        $campaign = $this->campaignModel->find($id);
        if ($campaign === null) {
            $this->abort(404, 'Campaign not found.');
        }

        if (in_array($campaign['status'], ['sending', 'queued'], true)) {
            $this->flash('error', 'Cannot delete a campaign that is currently sending.');
            $this->redirect('/campaigns');
        }

        $this->campaignModel->delete((int) $id);

        (new ActivityLog())->log(
            $this->currentUser()['id'] ?? null,
            'campaign_deleted',
            'campaign',
            $id,
            "Campaign '{$campaign['name']}' deleted"
        );

        $this->flash('success', 'Campaign deleted.');
        $this->redirect('/campaigns');
    }

    // ─── Schedule ─────────────────────────────────────────────────────────

    public function schedule(int|string $id): never
    {
        $this->requireAuth();

        $csrfToken = (string) ($this->request->body['_csrf_token'] ?? '');
        if (!CsrfHelper::validate($csrfToken)) {
            $this->flash('error', 'Invalid security token.');
            $this->redirect("/campaigns/{$id}");
        }

        $campaign = $this->campaignModel->find($id);
        if ($campaign === null) {
            $this->abort(404, 'Campaign not found.');
        }

        if (!in_array($campaign['status'], ['draft', 'scheduled'], true)) {
            $this->flash('error', 'Campaign cannot be scheduled in its current state.');
            $this->redirect("/campaigns/{$id}");
        }

        $scheduledAt = trim((string) ($this->request->body['scheduled_at'] ?? ''));

        $validator = Validator::make(
            ['scheduled_at' => $scheduledAt],
            ['scheduled_at' => 'required']
        );

        if ($validator->fails()) {
            $this->flash('error', 'Please provide a valid schedule date and time.');
            $this->redirect("/campaigns/{$id}/edit");
        }

        $this->campaignModel->update((int) $id, [
            'status'       => 'scheduled',
            'scheduled_at' => date('Y-m-d H:i:s', strtotime($scheduledAt)),
        ]);

        (new ActivityLog())->log(
            $this->currentUser()['id'] ?? null,
            'campaign_scheduled',
            'campaign',
            $id,
            "Campaign '{$campaign['name']}' scheduled for {$scheduledAt}"
        );

        $this->flash('success', "Campaign scheduled for {$scheduledAt}.");
        $this->redirect("/campaigns/{$id}");
    }

    // ─── Queue immediately ────────────────────────────────────────────────

    public function queue(int|string $id): never
    {
        $this->requireAuth();

        $csrfToken = (string) ($this->request->body['_csrf_token'] ?? '');
        if (!CsrfHelper::validate($csrfToken)) {
            $this->flash('error', 'Invalid security token.');
            $this->redirect("/campaigns/{$id}");
        }

        $campaign = $this->campaignModel->find($id);
        if ($campaign === null) {
            $this->abort(404, 'Campaign not found.');
        }

        if (!in_array($campaign['status'], ['draft', 'scheduled'], true)) {
            $this->flash('error', 'Campaign cannot be queued in its current state.');
            $this->redirect("/campaigns/{$id}");
        }

        // Prepare recipients before queuing
        $queueService = new QueueService();
        $queueService->prepareCampaignRecipients((int) $id);
        $queueService->skipIneligibleContacts((int) $id);

        $this->campaignModel->update((int) $id, [
            'status'        => 'queued',
            'scheduled_at'  => null,
            'next_batch_at' => date('Y-m-d H:i:s'),
        ]);

        (new ActivityLog())->log(
            $this->currentUser()['id'] ?? null,
            'campaign_queued',
            'campaign',
            $id,
            "Campaign '{$campaign['name']}' queued for immediate send"
        );

        $this->flash('success', 'Campaign queued for sending.');
        $this->redirect("/campaigns/{$id}");
    }

    // ─── Pause ────────────────────────────────────────────────────────────

    public function pause(int|string $id): never
    {
        $this->requireAuth();

        $csrfToken = (string) ($this->request->body['_csrf_token'] ?? '');
        if (!CsrfHelper::validate($csrfToken)) {
            $this->flash('error', 'Invalid security token.');
            $this->redirect("/campaigns/{$id}");
        }

        $campaign = $this->campaignModel->find($id);
        if ($campaign === null) {
            $this->abort(404, 'Campaign not found.');
        }

        if (!in_array($campaign['status'], ['queued', 'sending'], true)) {
            $this->flash('error', 'Only queued or sending campaigns can be paused.');
            $this->redirect("/campaigns/{$id}");
        }

        $this->campaignModel->update((int) $id, ['status' => 'paused']);

        (new ActivityLog())->log(
            $this->currentUser()['id'] ?? null,
            'campaign_paused',
            'campaign',
            $id,
            "Campaign '{$campaign['name']}' paused"
        );

        $this->flash('success', 'Campaign paused.');
        $this->redirect("/campaigns/{$id}");
    }

    // ─── Cancel ───────────────────────────────────────────────────────────

    public function cancel(int|string $id): never
    {
        $this->requireAuth();

        $csrfToken = (string) ($this->request->body['_csrf_token'] ?? '');
        if (!CsrfHelper::validate($csrfToken)) {
            $this->flash('error', 'Invalid security token.');
            $this->redirect("/campaigns/{$id}");
        }

        $campaign = $this->campaignModel->find($id);
        if ($campaign === null) {
            $this->abort(404, 'Campaign not found.');
        }

        if (in_array($campaign['status'], ['completed', 'cancelled'], true)) {
            $this->flash('error', 'Campaign is already completed or cancelled.');
            $this->redirect("/campaigns/{$id}");
        }

        $this->campaignModel->update((int) $id, ['status' => 'cancelled']);

        (new ActivityLog())->log(
            $this->currentUser()['id'] ?? null,
            'campaign_cancelled',
            'campaign',
            $id,
            "Campaign '{$campaign['name']}' cancelled"
        );

        $this->flash('success', 'Campaign cancelled.');
        $this->redirect("/campaigns/{$id}");
    }

    // ─── Duplicate ────────────────────────────────────────────────────────

    public function duplicate(int|string $id): never
    {
        $this->requireAuth();

        $campaign = $this->campaignModel->find($id);
        if ($campaign === null) {
            $this->abort(404, 'Campaign not found.');
        }

        $newData = [
            'uuid'                   => \MailForge\Helpers\UuidHelper::generate(),
            'name'                   => 'Copy of ' . $campaign['name'],
            'subject'                => $campaign['subject'],
            'preheader'              => $campaign['preheader'],
            'from_name'              => $campaign['from_name'],
            'from_email'             => $campaign['from_email'],
            'reply_to'               => $campaign['reply_to'],
            'html_content'           => $campaign['html_content'],
            'text_content'           => $campaign['text_content'],
            'template_id'            => $campaign['template_id'],
            'list_id'                => $campaign['list_id'],
            'segment_id'             => $campaign['segment_id'],
            'track_opens'            => $campaign['track_opens'],
            'track_clicks'           => $campaign['track_clicks'],
            'batch_size'             => $campaign['batch_size'],
            'batch_interval_minutes' => $campaign['batch_interval_minutes'],
            'status'                 => 'draft',
            'created_by'             => $this->currentUser()['id'] ?? null,
            'created_at'             => date('Y-m-d H:i:s'),
        ];

        $newId = $this->campaignModel->create($newData);

        (new ActivityLog())->log(
            $this->currentUser()['id'] ?? null,
            'campaign_duplicated',
            'campaign',
            $newId,
            "Campaign '{$campaign['name']}' duplicated"
        );

        $this->flash('success', 'Campaign duplicated. You can now edit the copy.');
        $this->redirect("/campaigns/{$newId}/edit");
    }

    // ─── Preview ──────────────────────────────────────────────────────────

    public function preview(int|string $id): never
    {
        $this->requireAuth();

        $campaign = $this->campaignModel->find($id);
        if ($campaign === null) {
            $this->abort(404, 'Campaign not found.');
        }

        $dummyContact = [
            'email'      => 'preview@example.com',
            'first_name' => 'John',
            'last_name'  => 'Doe',
        ];

        $templateModel = new Template();
        $html = $templateModel->replaceMergeTags(
            (string) ($campaign['html_content'] ?? ''),
            $dummyContact,
            $campaign
        );

        http_response_code(200);
        header('Content-Type: text/html; charset=UTF-8');
        echo $html;
        exit(0);
    }

    // ─── Recipients ───────────────────────────────────────────────────────

    public function recipients(int|string $id): never
    {
        $this->requireAuth();

        $campaign = $this->campaignModel->find($id);
        if ($campaign === null) {
            $this->abort(404, 'Campaign not found.');
        }

        $status   = (string) ($this->request->query['status'] ?? '');
        $page     = max(1, (int) ($this->request->query['page'] ?? 1));
        $perPage  = 50;
        $prefix   = Database::getPrefix();

        $where    = 'cr.campaign_id = :campaign_id';
        $bindings = [':campaign_id' => $id];

        if ($status !== '') {
            $where .= ' AND cr.status = :status';
            $bindings[':status'] = $status;
        }

        $countStmt = $this->db->prepare(
            "SELECT COUNT(*) FROM `{$prefix}campaign_recipients` cr WHERE {$where}"
        );
        $countStmt->execute($bindings);
        $total = (int) $countStmt->fetchColumn();

        $offset   = ($page - 1) * $perPage;
        $dataStmt = $this->db->prepare(
            "SELECT cr.*, co.first_name, co.last_name
             FROM `{$prefix}campaign_recipients` cr
             LEFT JOIN `{$prefix}contacts` co ON co.id = cr.contact_id
             WHERE {$where}
             ORDER BY cr.created_at DESC
             LIMIT {$perPage} OFFSET {$offset}"
        );
        $dataStmt->execute($bindings);
        $recipients = $dataStmt->fetchAll();

        $this->render('campaigns/recipients', [
            'campaign'   => $campaign,
            'recipients' => $recipients,
            'total'      => $total,
            'page'       => $page,
            'perPage'    => $perPage,
            'lastPage'   => (int) ceil($total / $perPage),
            'status'     => $status,
        ]);
    }

    // ─── Send Test ────────────────────────────────────────────────────────

    public function sendTest(int|string $id): never
    {
        $this->requireAuth();

        $csrfToken = (string) ($this->request->body['_csrf_token'] ?? '');
        if (!CsrfHelper::validate($csrfToken)) {
            $this->json(['error' => 'Invalid security token.'], 403);
        }

        $campaign = $this->campaignModel->find($id);
        if ($campaign === null) {
            $this->json(['error' => 'Campaign not found.'], 404);
        }

        $to = trim((string) ($this->request->body['test_email'] ?? ''));

        if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->json(['error' => 'Invalid email address.'], 422);
        }

        $smtpModel  = new SmtpServer();
        $smtpConfig = $smtpModel->getPrimary();

        if ($smtpConfig === null) {
            $this->json(['error' => 'No active SMTP server configured.'], 500);
        }

        try {
            $emailService = new EmailService($smtpConfig);
            $emailService->sendTestEmail(
                $to,
                '[Test] ' . ($campaign['subject'] ?? ''),
                (string) ($campaign['html_content'] ?? ''),
                (string) ($campaign['text_content'] ?? '')
            );

            (new ActivityLog())->log(
                $this->currentUser()['id'] ?? null,
                'campaign_test_sent',
                'campaign',
                $id,
                "Test email for campaign '{$campaign['name']}' sent to {$to}"
            );

            $this->json(['success' => true, 'message' => "Test email sent to {$to}"]);
        } catch (\Throwable $e) {
            $this->json(['error' => 'Send failed: ' . $e->getMessage()], 500);
        }
    }

    // ─── Private helpers ──────────────────────────────────────────────────

    /** @return array<string, mixed> */
    private function extractCampaignData(): array
    {
        $body = $this->request->body;

        return [
            'name'                   => trim((string) ($body['name'] ?? '')),
            'subject'                => trim((string) ($body['subject'] ?? '')),
            'preheader'              => trim((string) ($body['preheader'] ?? '')),
            'from_name'              => trim((string) ($body['from_name'] ?? '')),
            'from_email'             => trim((string) ($body['from_email'] ?? '')),
            'reply_to'               => trim((string) ($body['reply_to'] ?? '')),
            'html_content'           => (string) ($body['html_content'] ?? ''),
            'text_content'           => (string) ($body['text_content'] ?? ''),
            'template_id'            => ($body['template_id'] ?? '') !== '' ? (int) $body['template_id'] : null,
            'list_id'                => ($body['list_id'] ?? '') !== '' ? (int) $body['list_id'] : null,
            'segment_id'             => ($body['segment_id'] ?? '') !== '' ? (int) $body['segment_id'] : null,
            'smtp_server_id'         => ($body['smtp_server_id'] ?? '') !== '' ? (int) $body['smtp_server_id'] : null,
            'track_opens'            => isset($body['track_opens']) ? 1 : 0,
            'track_clicks'           => isset($body['track_clicks']) ? 1 : 0,
            'batch_size'             => max(1, (int) ($body['batch_size'] ?? 100)),
            'batch_interval_minutes' => max(1, (int) ($body['batch_interval_minutes'] ?? 10)),
        ];
    }
}
