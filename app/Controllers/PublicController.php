<?php

declare(strict_types=1);

namespace MailForge\Controllers;

use MailForge\Core\Controller;
use MailForge\Core\Database;
use MailForge\Helpers\CsrfHelper;
use MailForge\Models\Campaign;
use MailForge\Models\Contact;
use MailForge\Models\ContactList;
use MailForge\Models\Setting;
use MailForge\Models\SmtpServer;
use MailForge\Models\Template;
use MailForge\Services\EmailService;
use MailForge\Validators\Validator;

class PublicController extends Controller
{
    // ─── Public Subscribe Form ────────────────────────────────────────────

    public function subscribe(int|string $listId): never
    {
        $listModel = new ContactList();
        $list      = $listModel->find($listId);

        if ($list === null || ($list['deleted_at'] ?? null) !== null) {
            $this->abort(404, 'Subscription list not found.');
        }

        if ($this->request->method === 'POST') {
            $csrfToken = (string) ($this->request->body['_csrf_token'] ?? '');
            if (!CsrfHelper::validate($csrfToken)) {
                $this->flash('error', 'Invalid request. Please try again.');
                $this->redirect("/subscribe/{$listId}");
            }

            $email     = trim((string) ($this->request->body['email'] ?? ''));
            $firstName = trim((string) ($this->request->body['first_name'] ?? ''));
            $lastName  = trim((string) ($this->request->body['last_name'] ?? ''));

            $validator = Validator::make(
                ['email' => $email],
                ['email' => 'required|email']
            );

            if ($validator->fails()) {
                $this->flash('error', $validator->firstError('email'));
                $this->redirect("/subscribe/{$listId}");
            }

            $contactModel = new Contact();
            $contact      = $contactModel->findByEmail($email);

            if ($contact === null) {
                $doubleOptinToken = null;
                $status           = 'subscribed';

                if ($list['double_optin'] ?? false) {
                    $doubleOptinToken = bin2hex(random_bytes(32));
                    $status           = 'unconfirmed';
                }

                $contactId = $contactModel->create([
                    'uuid'               => \MailForge\Helpers\UuidHelper::generate(),
                    'email'              => $email,
                    'first_name'         => $firstName,
                    'last_name'          => $lastName,
                    'status'             => $status,
                    'double_optin_token' => $doubleOptinToken,
                    'subscribed_at'      => date('Y-m-d H:i:s'),
                    'created_at'         => date('Y-m-d H:i:s'),
                    'ip_address'         => $this->request->ip,
                ]);

                $contact = $contactModel->find($contactId);

                if ($doubleOptinToken !== null) {
                    $this->sendDoubleOptinEmail($contact, $doubleOptinToken);
                }
            } else {
                $contactId = $contact['id'];
            }

            if (!$listModel->isSubscribed($listId, $contactId)) {
                $listModel->addContact($listId, $contactId);
                $listModel->updateSubscriberCount($listId);
            }

            $this->render('public/subscribed', [
                'list'        => $list,
                'doubleOptin' => (bool) ($list['double_optin'] ?? false),
            ]);
        }

        $this->render('public/subscribe', [
            'csrf' => CsrfHelper::getToken(),
            'list' => $list,
        ]);
    }

    // ─── Confirm Double Opt-in ────────────────────────────────────────────

    public function confirmDoubleOptin(string $token): never
    {
        $contactModel = new Contact();
        $contact      = $contactModel->confirmDoubleOptin($token);

        if ($contact === null) {
            $this->render('public/optin-invalid', []);
        }

        $this->render('public/optin-confirmed', [
            'contact' => $contact,
        ]);
    }

    // ─── Unsubscribe Page ─────────────────────────────────────────────────

    public function unsubscribePage(string $token): never
    {
        [$contactId, $campaignId] = $this->decodeToken($token);

        $contactModel = new Contact();
        $contact      = $contactModel->find($contactId);

        if ($contact === null) {
            $this->abort(404, 'Invalid unsubscribe link.');
        }

        $campaignModel = new Campaign();
        $campaign      = $campaignId ? $campaignModel->find($campaignId) : null;

        $this->render('public/unsubscribe', [
            'csrf'     => CsrfHelper::getToken(),
            'contact'  => $contact,
            'campaign' => $campaign,
            'token'    => $token,
        ]);
    }

    // ─── Unsubscribe Confirm ──────────────────────────────────────────────

    public function unsubscribeConfirm(string $token): never
    {
        $csrfToken = (string) ($this->request->body['_csrf_token'] ?? '');
        if (!CsrfHelper::validate($csrfToken)) {
            $this->abort(400, 'Invalid request.');
        }

        [$contactId, $campaignId] = $this->decodeToken($token);

        $contactModel = new Contact();
        $contact      = $contactModel->find($contactId);

        if ($contact === null) {
            $this->abort(404, 'Invalid unsubscribe link.');
        }

        $contactModel->unsubscribe((int) $contactId, 'email_link');

        if ($campaignId) {
            $prefix = Database::getPrefix();
            $stmt   = $this->db->prepare(
                "UPDATE `{$prefix}campaign_recipients`
                 SET `status` = 'unsubscribed', `updated_at` = NOW()
                 WHERE `campaign_id` = ? AND `contact_id` = ?"
            );
            $stmt->execute([$campaignId, $contactId]);

            $this->db->prepare(
                "UPDATE `{$prefix}campaigns`
                 SET `unsubscribed_count` = `unsubscribed_count` + 1, `updated_at` = NOW()
                 WHERE `id` = ?"
            )->execute([$campaignId]);
        }

        $this->render('public/unsubscribed', [
            'contact' => $contact,
        ]);
    }

    // ─── Web View ─────────────────────────────────────────────────────────

    public function webview(int|string $campaignId, string $contactToken): never
    {
        $campaignModel = new Campaign();
        $campaign      = $campaignModel->find($campaignId);

        if ($campaign === null) {
            $this->abort(404, 'Email not found.');
        }

        [$contactId] = $this->decodeToken($contactToken);
        $contactModel = new Contact();
        $contact      = $contactId ? $contactModel->find($contactId) : null;

        $templateModel = new Template();
        $html = $templateModel->replaceMergeTags(
            (string) ($campaign['html_content'] ?? ''),
            $contact ?? ['email' => '', 'first_name' => '', 'last_name' => ''],
            $campaign
        );

        http_response_code(200);
        header('Content-Type: text/html; charset=UTF-8');
        echo $html;
        exit(0);
    }

    // ─── Track Open (1×1 pixel) ───────────────────────────────────────────

    public function trackOpen(int|string $campaignId, string $contactToken): never
    {
        // Respond immediately with a transparent 1×1 GIF
        $pixel = base64_decode(
            'R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'
        );

        header('Content-Type: image/gif');
        header('Content-Length: ' . strlen($pixel));
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        echo $pixel;

        // Record the open asynchronously after response is flushed
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }

        try {
            [$contactId] = $this->decodeToken($contactToken);
            if (!$contactId) {
                exit(0);
            }

            $prefix = Database::getPrefix();

            // Record in campaign_recipients
            $stmt = $this->db->prepare(
                "UPDATE `{$prefix}campaign_recipients`
                 SET `opened_at` = COALESCE(`opened_at`, NOW()),
                     `open_count` = `open_count` + 1,
                     `status` = CASE WHEN `status` = 'sent' THEN 'sent' ELSE `status` END,
                     `updated_at` = NOW()
                 WHERE `campaign_id` = ? AND `contact_id` = ?"
            );
            $stmt->execute([$campaignId, $contactId]);

            // Increment campaign opened_count on first open
            $this->db->prepare(
                "UPDATE `{$prefix}campaigns`
                 SET `opened_count` = `opened_count` + 1, `updated_at` = NOW()
                 WHERE `id` = ?
                   AND EXISTS (
                       SELECT 1 FROM `{$prefix}campaign_recipients` cr
                       WHERE cr.campaign_id = `{$prefix}campaigns`.id
                         AND cr.contact_id = ? AND cr.open_count = 1
                   )"
            )->execute([$campaignId, $contactId]);

            // Insert into email_opens tracking table if it exists
            $this->db->prepare(
                "INSERT IGNORE INTO `{$prefix}email_opens`
                 (`campaign_id`, `contact_id`, `opened_at`, `ip_address`, `user_agent`)
                 VALUES (?, ?, NOW(), ?, ?)"
            )->execute([
                $campaignId,
                $contactId,
                $this->request->ip ?? '',
                $_SERVER['HTTP_USER_AGENT'] ?? '',
            ]);
        } catch (\Throwable) {
            // Tracking errors must never surface to the user
        }

        exit(0);
    }

    // ─── Track Click ──────────────────────────────────────────────────────

    public function trackClick(string $trackingCode, string $contactToken): never
    {
        $prefix = Database::getPrefix();

        // Resolve the original URL
        $stmt = $this->db->prepare(
            "SELECT * FROM `{$prefix}campaign_links` WHERE `tracking_code` = ? LIMIT 1"
        );
        $stmt->execute([$trackingCode]);
        $link = $stmt->fetch();

        if ($link === false) {
            $this->abort(404, 'Link not found.');
        }

        $redirectUrl = (string) ($link['original_url'] ?? '/');

        // Redirect immediately
        http_response_code(302);
        header("Location: {$redirectUrl}");
        echo '';

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }

        // Record click
        try {
            [$contactId] = $this->decodeToken($contactToken);
            if (!$contactId) {
                exit(0);
            }

            $campaignId = $link['campaign_id'];

            // Increment link totals
            $this->db->prepare(
                "UPDATE `{$prefix}campaign_links`
                 SET `click_count` = `click_count` + 1, `updated_at` = NOW()
                 WHERE `id` = ?"
            )->execute([$link['id']]);

            // Mark recipient as clicked
            $stmt = $this->db->prepare(
                "UPDATE `{$prefix}campaign_recipients`
                 SET `clicked_at` = COALESCE(`clicked_at`, NOW()),
                     `click_count` = `click_count` + 1,
                     `updated_at` = NOW()
                 WHERE `campaign_id` = ? AND `contact_id` = ?"
            );
            $stmt->execute([$campaignId, $contactId]);

            // Increment campaign clicked_count (first click only)
            $this->db->prepare(
                "UPDATE `{$prefix}campaigns`
                 SET `clicked_count` = `clicked_count` + 1, `updated_at` = NOW()
                 WHERE `id` = ?
                   AND EXISTS (
                       SELECT 1 FROM `{$prefix}campaign_recipients` cr
                       WHERE cr.campaign_id = `{$prefix}campaigns`.id
                         AND cr.contact_id = ? AND cr.click_count = 1
                   )"
            )->execute([$campaignId, $contactId]);

            // Insert into link_clicks table if it exists
            $this->db->prepare(
                "INSERT IGNORE INTO `{$prefix}link_clicks`
                 (`campaign_link_id`, `contact_id`, `campaign_id`, `clicked_at`, `ip_address`)
                 VALUES (?, ?, ?, NOW(), ?)"
            )->execute([
                $link['id'],
                $contactId,
                $campaignId,
                $this->request->ip ?? '',
            ]);
        } catch (\Throwable) {
            // Tracking errors must never surface
        }

        exit(0);
    }

    // ─── Private helpers ──────────────────────────────────────────────────

    /**
     * Decode a contact tracking token.
     * Format: base64(contactId:campaignId:hmac)
     *
     * @return array{0: int|null, 1: int|null}
     */
    private function decodeToken(string $token): array
    {
        $decoded = base64_decode(strtr($token, '-_', '+/'), true);
        if ($decoded === false) {
            return [null, null];
        }

        $parts = explode(':', $decoded);
        return [
            isset($parts[0]) && is_numeric($parts[0]) ? (int) $parts[0] : null,
            isset($parts[1]) && is_numeric($parts[1]) ? (int) $parts[1] : null,
        ];
    }

    private function sendDoubleOptinEmail(array $contact, string $token): void
    {
        $settingModel = new Setting();
        $appUrl       = rtrim((string) $settingModel->get('app_url', $_ENV['APP_URL'] ?? 'http://localhost'), '/');
        $confirmUrl   = "{$appUrl}/confirm-optin/{$token}";

        $smtpModel  = new SmtpServer();
        $smtpConfig = $smtpModel->getPrimary();

        if ($smtpConfig === null) {
            return;
        }

        $emailService = new EmailService($smtpConfig);
        $emailService->send(
            $contact['email'],
            'Please confirm your subscription',
            "<p>Thank you for subscribing!</p>"
                . "<p>Please confirm your subscription by clicking the link below:</p>"
                . "<p><a href=\"{$confirmUrl}\">Confirm Subscription</a></p>",
            "Please confirm your subscription: {$confirmUrl}"
        );
    }
}
