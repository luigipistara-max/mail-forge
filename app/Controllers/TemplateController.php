<?php

declare(strict_types=1);

namespace MailForge\Controllers;

use MailForge\Core\Controller;
use MailForge\Helpers\CsrfHelper;
use MailForge\Models\ActivityLog;
use MailForge\Models\SmtpServer;
use MailForge\Models\Template;
use MailForge\Services\EmailService;
use MailForge\Validators\Validator;

class TemplateController extends Controller
{
    private Template $templateModel;

    public function __construct()
    {
        parent::__construct();
        $this->templateModel = new Template();
    }

    // ─── Index ────────────────────────────────────────────────────────────

    public function index(): never
    {
        $this->requireAuth();

        $page     = max(1, (int) ($this->request->query['page'] ?? 1));
        $perPage  = 20;
        $search   = trim((string) ($this->request->query['search'] ?? ''));
        $category = trim((string) ($this->request->query['category'] ?? ''));
        $prefix   = \MailForge\Core\Database::getPrefix();

        $where    = '`deleted_at` IS NULL';
        $bindings = [];

        if ($search !== '') {
            $where .= ' AND `name` LIKE :search';
            $bindings[':search'] = "%{$search}%";
        }

        if ($category !== '') {
            $where .= ' AND `category` = :category';
            $bindings[':category'] = $category;
        }

        $countStmt = $this->db->prepare(
            "SELECT COUNT(*) FROM `{$prefix}templates` WHERE {$where}"
        );
        $countStmt->execute($bindings);
        $total = (int) $countStmt->fetchColumn();

        $offset   = ($page - 1) * $perPage;
        $dataStmt = $this->db->prepare(
            "SELECT * FROM `{$prefix}templates` WHERE {$where}
             ORDER BY `created_at` DESC LIMIT {$perPage} OFFSET {$offset}"
        );
        $dataStmt->execute($bindings);
        $templates = $dataStmt->fetchAll();

        $categories = $this->templateModel->getCategories();

        $this->render('templates/index', [
            'templates'  => $templates,
            'pagination' => [
                'total'        => $total,
                'per_page'     => $perPage,
                'current_page' => $page,
                'last_page'    => (int) ceil($total / $perPage),
            ],
            'categories' => $categories,
            'search'     => $search,
            'category'   => $category,
            'success'    => $this->getFlash('success'),
            'error'      => $this->getFlash('error'),
        ]);
    }

    // ─── Create ───────────────────────────────────────────────────────────

    public function create(): never
    {
        $this->requireAuth();

        $categories = $this->templateModel->getCategories();

        $this->render('templates/form', [
            'csrf'       => CsrfHelper::getToken(),
            'categories' => $categories,
            'error'      => $this->getFlash('error'),
        ]);
    }

    // ─── Store ────────────────────────────────────────────────────────────

    public function store(): never
    {
        $this->requireAuth();

        $csrfToken = (string) ($this->request->body['_csrf_token'] ?? '');
        if (!CsrfHelper::validate($csrfToken)) {
            $this->flash('error', 'Invalid security token.');
            $this->redirect('/templates/create');
        }

        $data = [
            'name'         => trim((string) ($this->request->body['name'] ?? '')),
            'subject'      => trim((string) ($this->request->body['subject'] ?? '')),
            'html_content' => (string) ($this->request->body['html_content'] ?? ''),
            'text_content' => (string) ($this->request->body['text_content'] ?? ''),
            'category'     => trim((string) ($this->request->body['category'] ?? '')),
        ];

        $validator = Validator::make($data, [
            'name'         => 'required|max:200',
            'subject'      => 'required|max:500',
            'html_content' => 'required',
        ]);

        if ($validator->fails()) {
            $this->flash('error', implode(' ', $validator->allErrors()));
            $this->redirect('/templates/create');
        }

        $data['uuid']       = \Ramsey\Uuid\Uuid::uuid4()->toString();
        $data['created_by'] = $this->currentUser()['id'] ?? null;
        $data['created_at'] = date('Y-m-d H:i:s');

        $templateId = $this->templateModel->create($data);

        (new ActivityLog())->log(
            $this->currentUser()['id'] ?? null,
            'template_created',
            'template',
            $templateId,
            "Template '{$data['name']}' created"
        );

        $this->flash('success', 'Template created successfully.');
        $this->redirect("/templates/{$templateId}/edit");
    }

    // ─── Edit ─────────────────────────────────────────────────────────────

    public function edit(int|string $id): never
    {
        $this->requireAuth();

        $template = $this->templateModel->find($id);
        if ($template === null) {
            $this->abort(404, 'Template not found.');
        }

        $categories = $this->templateModel->getCategories();

        $this->render('templates/form', [
            'csrf'       => CsrfHelper::getToken(),
            'template'   => $template,
            'categories' => $categories,
            'error'      => $this->getFlash('error'),
            'success'    => $this->getFlash('success'),
        ]);
    }

    // ─── Update ───────────────────────────────────────────────────────────

    public function update(int|string $id): never
    {
        $this->requireAuth();

        $csrfToken = (string) ($this->request->body['_csrf_token'] ?? '');
        if (!CsrfHelper::validate($csrfToken)) {
            $this->flash('error', 'Invalid security token.');
            $this->redirect("/templates/{$id}/edit");
        }

        $template = $this->templateModel->find($id);
        if ($template === null) {
            $this->abort(404, 'Template not found.');
        }

        $data = [
            'name'         => trim((string) ($this->request->body['name'] ?? '')),
            'subject'      => trim((string) ($this->request->body['subject'] ?? '')),
            'html_content' => (string) ($this->request->body['html_content'] ?? ''),
            'text_content' => (string) ($this->request->body['text_content'] ?? ''),
            'category'     => trim((string) ($this->request->body['category'] ?? '')),
        ];

        $validator = Validator::make($data, [
            'name'         => 'required|max:200',
            'subject'      => 'required|max:500',
            'html_content' => 'required',
        ]);

        if ($validator->fails()) {
            $this->flash('error', implode(' ', $validator->allErrors()));
            $this->redirect("/templates/{$id}/edit");
        }

        $this->templateModel->update((int) $id, $data);

        (new ActivityLog())->log(
            $this->currentUser()['id'] ?? null,
            'template_updated',
            'template',
            $id,
            "Template '{$data['name']}' updated"
        );

        $this->flash('success', 'Template saved.');
        $this->redirect("/templates/{$id}/edit");
    }

    // ─── Delete ───────────────────────────────────────────────────────────

    public function delete(int|string $id): never
    {
        $this->requireAuth();

        $csrfToken = (string) ($this->request->body['_csrf_token'] ?? '');
        if (!CsrfHelper::validate($csrfToken)) {
            $this->flash('error', 'Invalid security token.');
            $this->redirect('/templates');
        }

        $template = $this->templateModel->find($id);
        if ($template === null) {
            $this->abort(404, 'Template not found.');
        }

        $this->templateModel->delete((int) $id);

        (new ActivityLog())->log(
            $this->currentUser()['id'] ?? null,
            'template_deleted',
            'template',
            $id,
            "Template '{$template['name']}' deleted"
        );

        $this->flash('success', 'Template deleted.');
        $this->redirect('/templates');
    }

    // ─── Duplicate ────────────────────────────────────────────────────────

    public function duplicate(int|string $id): never
    {
        $this->requireAuth();

        $template = $this->templateModel->find($id);
        if ($template === null) {
            $this->abort(404, 'Template not found.');
        }

        $newId = $this->templateModel->duplicate((int) $id);

        if ($newId === null) {
            $this->flash('error', 'Failed to duplicate template.');
            $this->redirect('/templates');
        }

        (new ActivityLog())->log(
            $this->currentUser()['id'] ?? null,
            'template_duplicated',
            'template',
            $newId,
            "Template '{$template['name']}' duplicated"
        );

        $this->flash('success', 'Template duplicated. You can now edit the copy.');
        $this->redirect("/templates/{$newId}/edit");
    }

    // ─── Preview ──────────────────────────────────────────────────────────

    public function preview(int|string $id): never
    {
        $this->requireAuth();

        $template = $this->templateModel->find($id);
        if ($template === null) {
            $this->abort(404, 'Template not found.');
        }

        $dummyContact = [
            'email'      => 'preview@example.com',
            'first_name' => 'John',
            'last_name'  => 'Doe',
        ];

        $html = $this->templateModel->replaceMergeTags(
            (string) ($template['html_content'] ?? ''),
            $dummyContact
        );

        http_response_code(200);
        header('Content-Type: text/html; charset=UTF-8');
        echo $html;
        exit(0);
    }

    // ─── Send Test ────────────────────────────────────────────────────────

    public function sendTest(int|string $id): never
    {
        $this->requireAuth();

        $csrfToken = (string) ($this->request->body['_csrf_token'] ?? '');
        if (!CsrfHelper::validate($csrfToken)) {
            $this->json(['error' => 'Invalid security token.'], 403);
        }

        $template = $this->templateModel->find($id);
        if ($template === null) {
            $this->json(['error' => 'Template not found.'], 404);
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
                '[Test] ' . ($template['subject'] ?? 'Template Preview'),
                (string) ($template['html_content'] ?? ''),
                (string) ($template['text_content'] ?? '')
            );

            $this->json(['success' => true, 'message' => "Test email sent to {$to}"]);
        } catch (\Throwable $e) {
            $this->json(['error' => 'Failed to send: ' . $e->getMessage()], 500);
        }
    }
}
