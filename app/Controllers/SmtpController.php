<?php

declare(strict_types=1);

namespace MailForge\Controllers;

use MailForge\Core\Controller;
use MailForge\Helpers\CsrfHelper;
use MailForge\Models\ActivityLog;
use MailForge\Models\SmtpServer;
use MailForge\Services\EmailService;
use MailForge\Validators\Validator;

class SmtpController extends Controller
{
    private SmtpServer $smtpModel;

    public function __construct()
    {
        parent::__construct();
        $this->smtpModel = new SmtpServer();
    }

    // ─── Index ────────────────────────────────────────────────────────────

    public function index(): never
    {
        $this->requireAuth();

        $servers = $this->smtpModel->findAll(['deleted_at IS NULL']);

        $this->render('smtp/index', [
            'servers' => $servers,
            'success' => $this->getFlash('success'),
            'error'   => $this->getFlash('error'),
        ]);
    }

    // ─── Create ───────────────────────────────────────────────────────────

    public function create(): never
    {
        $this->requireAuth();

        $this->render('smtp/create', [
            'csrf'  => CsrfHelper::getToken(),
            'error' => $this->getFlash('error'),
        ]);
    }

    // ─── Store ────────────────────────────────────────────────────────────

    public function store(): never
    {
        $this->requireAuth();

        $csrfToken = (string) ($this->request->body['_csrf_token'] ?? '');
        if (!CsrfHelper::validate($csrfToken)) {
            $this->flash('error', 'Invalid security token.');
            $this->redirect('/smtp/create');
        }

        $data = $this->extractSmtpData();

        $validator = Validator::make($data, [
            'name'       => 'required|max:200',
            'host'       => 'required|max:253',
            'port'       => 'required|numeric',
            'username'   => 'required|max:191',
            'from_email' => 'required|email',
        ]);

        if ($validator->fails()) {
            $this->flash('error', implode(' ', $validator->allErrors()));
            $this->redirect('/smtp/create');
        }

        if (isset($data['password']) && $data['password'] !== '') {
            $data['password'] = $this->encryptPassword((string) $data['password']);
        }

        $data['created_at'] = date('Y-m-d H:i:s');
        $serverId = $this->smtpModel->create($data);

        (new ActivityLog())->log(
            $this->currentUser()['id'] ?? null,
            'smtp_created',
            'smtp_server',
            $serverId,
            "SMTP server '{$data['name']}' created"
        );

        $this->flash('success', 'SMTP server saved.');
        $this->redirect('/smtp');
    }

    // ─── Edit ─────────────────────────────────────────────────────────────

    public function edit(int|string $id): never
    {
        $this->requireAuth();

        $server = $this->smtpModel->find($id);
        if ($server === null) {
            $this->abort(404, 'SMTP server not found.');
        }

        // Do not expose the encrypted password in the form
        $server['password'] = '';

        $this->render('smtp/edit', [
            'csrf'   => CsrfHelper::getToken(),
            'server' => $server,
            'error'  => $this->getFlash('error'),
        ]);
    }

    // ─── Update ───────────────────────────────────────────────────────────

    public function update(int|string $id): never
    {
        $this->requireAuth();

        $csrfToken = (string) ($this->request->body['_csrf_token'] ?? '');
        if (!CsrfHelper::validate($csrfToken)) {
            $this->flash('error', 'Invalid security token.');
            $this->redirect("/smtp/{$id}/edit");
        }

        $server = $this->smtpModel->find($id);
        if ($server === null) {
            $this->abort(404, 'SMTP server not found.');
        }

        $data = $this->extractSmtpData();

        $validator = Validator::make($data, [
            'name'       => 'required|max:200',
            'host'       => 'required|max:253',
            'port'       => 'required|numeric',
            'username'   => 'required|max:191',
            'from_email' => 'required|email',
        ]);

        if ($validator->fails()) {
            $this->flash('error', implode(' ', $validator->allErrors()));
            $this->redirect("/smtp/{$id}/edit");
        }

        // Only update password if a new one was provided
        $rawPassword = (string) ($data['password'] ?? '');
        if ($rawPassword === '') {
            unset($data['password']);
        } else {
            $data['password'] = $this->encryptPassword($rawPassword);
        }

        $this->smtpModel->update((int) $id, $data);

        (new ActivityLog())->log(
            $this->currentUser()['id'] ?? null,
            'smtp_updated',
            'smtp_server',
            $id,
            "SMTP server '{$data['name']}' updated"
        );

        $this->flash('success', 'SMTP server updated.');
        $this->redirect('/smtp');
    }

    // ─── Delete ───────────────────────────────────────────────────────────

    public function delete(int|string $id): never
    {
        $this->requireAuth();

        $csrfToken = (string) ($this->request->body['_csrf_token'] ?? '');
        if (!CsrfHelper::validate($csrfToken)) {
            $this->flash('error', 'Invalid security token.');
            $this->redirect('/smtp');
        }

        $server = $this->smtpModel->find($id);
        if ($server === null) {
            $this->abort(404, 'SMTP server not found.');
        }

        $this->smtpModel->delete((int) $id);

        (new ActivityLog())->log(
            $this->currentUser()['id'] ?? null,
            'smtp_deleted',
            'smtp_server',
            $id,
            "SMTP server '{$server['name']}' deleted"
        );

        $this->flash('success', 'SMTP server deleted.');
        $this->redirect('/smtp');
    }

    // ─── Test Connection ──────────────────────────────────────────────────

    public function test(int|string $id): never
    {
        $this->requireAuth();

        $csrfToken = (string) ($this->request->body['_csrf_token'] ?? '');
        if (!CsrfHelper::validate($csrfToken)) {
            $this->json(['error' => 'Invalid security token.'], 403);
        }

        $server = $this->smtpModel->find($id);
        if ($server === null) {
            $this->json(['error' => 'SMTP server not found.'], 404);
        }

        $testTo = trim((string) ($this->request->body['test_email'] ?? ($this->currentUser()['email'] ?? '')));

        if (!filter_var($testTo, FILTER_VALIDATE_EMAIL)) {
            $this->json(['error' => 'Provide a valid recipient email for the test.'], 422);
        }

        // Decrypt password for use
        $serverConfig             = $server;
        $serverConfig['password'] = $this->decryptPassword((string) ($server['password'] ?? ''));

        try {
            $emailService = new EmailService($serverConfig);
            $result = $emailService->send(
                $testTo,
                'Mail Forge SMTP Test',
                '<p>This is a test email from Mail Forge to verify your SMTP configuration.</p>',
                'This is a test email from Mail Forge.'
            );

            if ($result) {
                $this->json(['success' => true, 'message' => "Test email sent to {$testTo}"]);
            } else {
                $this->json(['error' => 'SMTP test failed. Check your configuration.'], 500);
            }
        } catch (\Throwable $e) {
            $this->json(['error' => 'Connection failed: ' . $e->getMessage()], 500);
        }
    }

    // ─── Toggle Active ────────────────────────────────────────────────────

    public function toggleActive(int|string $id): never
    {
        $this->requireAuth();

        $csrfToken = (string) ($this->request->body['_csrf_token'] ?? '');
        if (!CsrfHelper::validate($csrfToken)) {
            $this->json(['error' => 'Invalid security token.'], 403);
        }

        $server = $this->smtpModel->find($id);
        if ($server === null) {
            $this->json(['error' => 'SMTP server not found.'], 404);
        }

        $newActive = ((int) ($server['is_active'] ?? 0)) === 1 ? 0 : 1;
        $this->smtpModel->update((int) $id, ['is_active' => $newActive]);

        (new ActivityLog())->log(
            $this->currentUser()['id'] ?? null,
            'smtp_toggled',
            'smtp_server',
            $id,
            "SMTP server '{$server['name']}' " . ($newActive ? 'activated' : 'deactivated')
        );

        if ($this->request->expectsJson()) {
            $this->json(['success' => true, 'is_active' => $newActive]);
        }

        $this->flash('success', 'SMTP server status updated.');
        $this->redirect('/smtp');
    }

    // ─── Private helpers ──────────────────────────────────────────────────

    /** @return array<string, mixed> */
    private function extractSmtpData(): array
    {
        $body = $this->request->body;

        return [
            'name'           => trim((string) ($body['name'] ?? '')),
            'host'           => trim((string) ($body['host'] ?? '')),
            'port'           => (int) ($body['port'] ?? 587),
            'username'       => trim((string) ($body['username'] ?? '')),
            'password'       => (string) ($body['password'] ?? ''),
            'encryption'     => (string) ($body['encryption'] ?? 'tls'),
            'from_name'      => trim((string) ($body['from_name'] ?? '')),
            'from_email'     => trim((string) ($body['from_email'] ?? '')),
            'reply_to'       => trim((string) ($body['reply_to'] ?? '')),
            'is_active'      => isset($body['is_active']) ? 1 : 0,
            'hourly_limit'   => (int) ($body['hourly_limit'] ?? 0),
            'daily_limit'    => (int) ($body['daily_limit'] ?? 0),
        ];
    }

    private function encryptPassword(string $password): string
    {
        $key = $_ENV['APP_KEY'] ?? '';
        if ($key === '') {
            return base64_encode($password);
        }

        $iv         = random_bytes(16);
        $encrypted  = openssl_encrypt($password, 'AES-256-CBC', $key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }

    private function decryptPassword(string $encrypted): string
    {
        $key = $_ENV['APP_KEY'] ?? '';
        if ($key === '') {
            return (string) base64_decode($encrypted);
        }

        $decoded = base64_decode($encrypted);
        if ($decoded === false || strlen($decoded) < 16) {
            return $encrypted;
        }

        $iv   = substr($decoded, 0, 16);
        $data = substr($decoded, 16);

        $decrypted = openssl_decrypt($data, 'AES-256-CBC', $key, 0, $iv);
        return $decrypted !== false ? $decrypted : $encrypted;
    }
}
