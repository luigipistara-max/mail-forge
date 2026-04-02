<?php

declare(strict_types=1);

namespace MailForge\Services;

use MailForge\Core\Database;
use MailForge\Models\Campaign;
use MailForge\Models\Setting;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

class EmailService
{
    private array $smtpConfig;
    private string $appUrl;

    /**
     * @param array<string, mixed> $smtpConfig  Row from smtp_servers table (already decrypted).
     */
    public function __construct(array $smtpConfig)
    {
        $this->smtpConfig = $smtpConfig;

        $settingModel  = new Setting();
        $this->appUrl  = rtrim(
            (string) $settingModel->get('app_url', $_ENV['APP_URL'] ?? 'http://localhost'),
            '/'
        );
    }

    // ─── Core send ────────────────────────────────────────────────────────

    /**
     * Send an email.
     *
     * @param string|array<int, string> $to       Single address or array of addresses.
     * @param array<string, mixed>      $options  Optional keys: cc, bcc, replyTo, attachments, headers.
     *
     * @return array{success: bool, message_id: string, error: string}
     */
    public function send(
        string|array $to,
        string $subject,
        string $htmlBody,
        string $textBody = '',
        array $options = []
    ): array {
        $mailer = $this->createMailer();

        try {
            // Recipients
            $toList = is_array($to) ? $to : [$to];
            foreach ($toList as $address) {
                $mailer->addAddress($address);
            }

            foreach ((array) ($options['cc'] ?? []) as $cc) {
                $mailer->addCC($cc);
            }

            foreach ((array) ($options['bcc'] ?? []) as $bcc) {
                $mailer->addBCC($bcc);
            }

            if (!empty($options['replyTo'])) {
                $mailer->addReplyTo((string) $options['replyTo']);
            }

            // Custom headers
            foreach ((array) ($options['headers'] ?? []) as $name => $value) {
                $mailer->addCustomHeader((string) $name, (string) $value);
            }

            // Attachments
            foreach ((array) ($options['attachments'] ?? []) as $attachment) {
                $mailer->addAttachment(
                    $attachment['path'],
                    $attachment['name'] ?? ''
                );
            }

            $mailer->Subject = $subject;
            $mailer->Body    = $htmlBody;
            $mailer->AltBody = $textBody !== '' ? $textBody : strip_tags($htmlBody);

            $mailer->send();

            return [
                'success'    => true,
                'message_id' => $mailer->getLastMessageID(),
                'error'      => '',
            ];
        } catch (PHPMailerException $e) {
            return [
                'success'    => false,
                'message_id' => '',
                'error'      => $e->getMessage(),
            ];
        }
    }

    // ─── Campaign send ────────────────────────────────────────────────────

    /**
     * Send a campaign email to a single contact with full tracking and merge tags.
     *
     * @param array<string, mixed> $campaign   Campaign row from DB.
     * @param array<string, mixed> $contact    Contact row from DB.
     * @param array<string, mixed> $smtpServer Decrypted SMTP row from DB.
     *
     * @return array{success: bool, message_id: string, error: string}
     */
    public function sendCampaignMessage(array $campaign, array $contact, array $smtpServer): array
    {
        $contactToken = $this->encodeContactToken((int) $contact['id'], (int) $campaign['id']);

        // ── Build HTML ────────────────────────────────────────────────────
        $html = (string) ($campaign['html_content'] ?? '');

        // Replace merge tags
        $html = $this->replaceMergeTags($html, $contact, $campaign, $contactToken);

        // Inject tracking pixel
        if ($campaign['track_opens'] ?? false) {
            $trackingUrl = "{$this->appUrl}/track/open/{$campaign['id']}/{$contactToken}";
            $html        = $this->injectTrackingPixel($html, $trackingUrl);
        }

        // Inject link tracking
        if ($campaign['track_clicks'] ?? false) {
            $html = $this->injectTrackingLinks($html, (int) $campaign['id'], $contactToken);
        }

        // ── Build plain text ──────────────────────────────────────────────
        $text = (string) ($campaign['text_content'] ?? '');
        if ($text !== '') {
            $text = $this->replaceMergeTags($text, $contact, $campaign, $contactToken);
        }

        // ── Unsubscribe URL ───────────────────────────────────────────────
        $unsubscribeUrl = $this->generateUnsubscribeUrl((int) $campaign['id'], $contactToken);
        $webviewUrl     = $this->generateWebviewUrl((int) $campaign['id'], $contactToken);

        // ── Options ───────────────────────────────────────────────────────
        $fromName  = (string) ($campaign['from_name']  ?: $smtpServer['from_name']  ?? '');
        $fromEmail = (string) ($campaign['from_email'] ?: $smtpServer['from_email'] ?? '');
        $replyTo   = (string) ($campaign['reply_to']   ?: $smtpServer['reply_to']   ?? '');

        $options = [
            'headers' => [
                'List-Unsubscribe'       => "<{$unsubscribeUrl}>",
                'List-Unsubscribe-Post'  => 'List-Unsubscribe=One-Click',
                'X-Mailer'               => 'Mail Forge',
                'X-Campaign-ID'          => (string) $campaign['id'],
                'X-Webview-URL'          => $webviewUrl,
            ],
        ];

        if ($replyTo !== '') {
            $options['replyTo'] = $replyTo;
        }

        // ── Override sender on mailer ─────────────────────────────────────
        $mailer = $this->createMailer($smtpServer);

        if ($fromEmail !== '') {
            $mailer->setFrom($fromEmail, $fromName);
        }

        try {
            $mailer->addAddress((string) $contact['email']);

            foreach ((array) ($options['headers'] ?? []) as $name => $value) {
                $mailer->addCustomHeader((string) $name, (string) $value);
            }

            if (!empty($options['replyTo'])) {
                $mailer->addReplyTo((string) $options['replyTo']);
            }

            $mailer->Subject = (string) ($campaign['subject'] ?? '');
            $mailer->Body    = $html;
            $mailer->AltBody = $text !== '' ? $text : strip_tags($html);

            if (!empty($campaign['preheader'])) {
                $preheader    = htmlspecialchars((string) $campaign['preheader'], ENT_QUOTES, 'UTF-8');
                $previewSpan  = "<span style=\"display:none;max-height:0;overflow:hidden;\">"
                    . $preheader . "</span>";
                $mailer->Body = str_replace('<body', $previewSpan . '<body', $mailer->Body);
            }

            $mailer->send();

            return [
                'success'    => true,
                'message_id' => $mailer->getLastMessageID(),
                'error'      => '',
            ];
        } catch (PHPMailerException $e) {
            return [
                'success'    => false,
                'message_id' => '',
                'error'      => $e->getMessage(),
            ];
        }
    }

    // ─── Test send ────────────────────────────────────────────────────────

    /**
     * Send a test/preview email.
     */
    public function sendTestEmail(
        string $to,
        string $subject,
        string $html,
        string $text = ''
    ): array {
        return $this->send($to, $subject, $html, $text, [
            'headers' => ['X-Mailer' => 'Mail Forge', 'X-Test-Email' => '1'],
        ]);
    }

    // ─── Tracking helpers ─────────────────────────────────────────────────

    /**
     * Append a 1×1 transparent tracking pixel to the HTML body.
     */
    public function injectTrackingPixel(string $html, string $trackingUrl): string
    {
        $pixel = '<img src="' . htmlspecialchars($trackingUrl, ENT_QUOTES, 'UTF-8')
            . '" width="1" height="1" border="0" alt="" style="display:block;width:1px;height:1px;">';

        // Insert just before closing body tag if present, otherwise append
        if (stripos($html, '</body>') !== false) {
            return str_ireplace('</body>', $pixel . '</body>', $html);
        }

        return $html . $pixel;
    }

    /**
     * Replace all href values in anchor tags with tracked redirect URLs,
     * preserving unsubscribe and mailto links.
     */
    public function injectTrackingLinks(
        string $html,
        int $campaignId,
        string $contactToken
    ): string {
        $prefix = Database::getPrefix();
        $db     = Database::getInstance();

        $campaignModel = new Campaign();

        return (string) preg_replace_callback(
            '/<a\s+([^>]*?)href=["\']([^"\']+)["\'](.*?)>/i',
            function (array $matches) use ($campaignId, $contactToken, $db, $prefix, $campaignModel): string {
                $originalUrl = $matches[2];

                // Do not track mailto, unsubscribe, or anchor links
                if (
                    str_starts_with($originalUrl, 'mailto:')
                    || str_starts_with($originalUrl, '#')
                    || str_contains($originalUrl, 'unsubscribe')
                    || str_contains($originalUrl, 'track/')
                ) {
                    return $matches[0];
                }

                // Get or create tracking code for this URL
                $trackingCode = $campaignModel->addLink($campaignId, $originalUrl);

                $trackedUrl = "{$this->appUrl}/track/click/{$trackingCode}/{$contactToken}";

                return '<a ' . $matches[1] . 'href="'
                    . htmlspecialchars($trackedUrl, ENT_QUOTES, 'UTF-8')
                    . '"' . $matches[3] . '>';
            },
            $html
        ) ?? $html;
    }

    // ─── URL generators ───────────────────────────────────────────────────

    public function generateUnsubscribeUrl(int $campaignId, string $contactToken): string
    {
        return "{$this->appUrl}/unsubscribe/{$contactToken}";
    }

    public function generateWebviewUrl(int $campaignId, string $contactToken): string
    {
        return "{$this->appUrl}/webview/{$campaignId}/{$contactToken}";
    }

    // ─── Private helpers ──────────────────────────────────────────────────

    private function createMailer(?array $config = null): PHPMailer
    {
        $cfg    = $config ?? $this->smtpConfig;
        $mailer = new PHPMailer(true);

        $mailer->isSMTP();
        $mailer->Host       = (string) ($cfg['host'] ?? 'localhost');
        $mailer->Port       = (int)    ($cfg['port'] ?? 587);
        $mailer->SMTPAuth   = true;
        $mailer->Username   = (string) ($cfg['username'] ?? '');
        $mailer->Password   = (string) ($cfg['password'] ?? '');
        $mailer->SMTPSecure = $this->resolveEncryption((string) ($cfg['encryption'] ?? 'tls'));
        $mailer->CharSet    = PHPMailer::CHARSET_UTF8;
        $mailer->Timeout    = (int) ($cfg['timeout'] ?? 30);
        $mailer->isHTML(true);

        $fromEmail = (string) ($cfg['from_email'] ?? '');
        $fromName  = (string) ($cfg['from_name']  ?? '');

        if ($fromEmail !== '') {
            $mailer->setFrom($fromEmail, $fromName);
        }

        return $mailer;
    }

    private function resolveEncryption(string $encryption): string
    {
        return match (strtolower($encryption)) {
            'ssl'  => PHPMailer::ENCRYPTION_SMTPS,
            'tls'  => PHPMailer::ENCRYPTION_STARTTLS,
            default => '',
        };
    }

    /**
     * Replace {{merge_tags}} in content with contact/campaign values.
     *
     * @param array<string, mixed> $contact
     * @param array<string, mixed> $campaign
     */
    private function replaceMergeTags(
        string $content,
        array $contact,
        array $campaign,
        string $contactToken
    ): string {
        $unsubscribeUrl = $this->generateUnsubscribeUrl((int) ($campaign['id'] ?? 0), $contactToken);
        $webviewUrl     = $this->generateWebviewUrl((int) ($campaign['id'] ?? 0), $contactToken);

        $tags = [
            '{{email}}'            => htmlspecialchars((string) ($contact['email'] ?? ''), ENT_QUOTES, 'UTF-8'),
            '{{first_name}}'       => htmlspecialchars((string) ($contact['first_name'] ?? ''), ENT_QUOTES, 'UTF-8'),
            '{{last_name}}'        => htmlspecialchars((string) ($contact['last_name'] ?? ''), ENT_QUOTES, 'UTF-8'),
            '{{full_name}}'        => htmlspecialchars(
                trim(($contact['first_name'] ?? '') . ' ' . ($contact['last_name'] ?? '')),
                ENT_QUOTES,
                'UTF-8'
            ),
            '{{unsubscribe_link}}' => $unsubscribeUrl,
            '{{webview_link}}'     => $webviewUrl,
            '{{campaign_name}}'    => htmlspecialchars((string) ($campaign['name'] ?? ''), ENT_QUOTES, 'UTF-8'),
            '{{subject}}'          => htmlspecialchars((string) ($campaign['subject'] ?? ''), ENT_QUOTES, 'UTF-8'),
        ];

        return str_replace(array_keys($tags), array_values($tags), $content);
    }

    /**
     * Encode a contact token for tracking URLs.
     * Format: base64url(contactId:campaignId)
     */
    private function encodeContactToken(int $contactId, int $campaignId): string
    {
        return rtrim(strtr(base64_encode("{$contactId}:{$campaignId}"), '+/', '-_'), '=');
    }
}
