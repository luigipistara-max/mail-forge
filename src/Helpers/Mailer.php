<?php

declare(strict_types=1);

namespace MailForge\Helpers;

/**
 * SMTP mailer wrapper.
 *
 * Sends HTML and/or plain-text emails via PHP's native socket SMTP connection.
 * Uses the configuration from config/mail.php.
 * Supports attachments and honours the configured rate limits.
 */
class Mailer
{
    /** @var array<string, mixed> */
    private array $config;

    /** Sent-email counters stored in a simple file-based rate-limit cache. */
    private string $cacheDir;

    public function __construct()
    {
        global $mailConfig;

        // Allow standalone instantiation without the global being pre-loaded
        if (empty($mailConfig)) {
            require_once __DIR__ . '/../../config/mail.php';
        }

        $this->config   = $mailConfig;
        $this->cacheDir = sys_get_temp_dir() . '/mailforge_rate';

        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0700, true);
        }
    }

    // ----------------------------------------------------------------
    // Public API
    // ----------------------------------------------------------------

    /**
     * Send an email message.
     *
     * @param string               $to          Recipient email address.
     * @param string               $subject     Email subject line.
     * @param string               $htmlBody    HTML version of the message body.
     * @param string               $textBody    Plain-text fallback.
     * @param array<string, mixed> $options     Optional: 'from_name', 'from_email', 'reply_to',
     *                                          'cc', 'bcc', 'attachments'.
     * @throws \RuntimeException   on rate-limit exceeded or SMTP failure.
     */
    public function send(
        string $to,
        string $subject,
        string $htmlBody,
        string $textBody = '',
        array $options = []
    ): bool {
        $this->checkRateLimit();

        $fromName  = $options['from_name']  ?? $this->config['from_name'];
        $fromEmail = $options['from_email'] ?? $this->config['from_email'];
        $replyTo   = $options['reply_to']   ?? $fromEmail;

        $boundary  = '----=_Part_' . md5(uniqid('', true));
        $headers   = $this->buildHeaders($fromName, $fromEmail, $to, $replyTo, $boundary, $options);
        $body      = $this->buildBody($htmlBody, $textBody, $boundary, $options['attachments'] ?? []);

        $sent = $this->smtpSend($to, $fromEmail, $subject, $headers, $body);

        if ($sent) {
            $this->incrementCounter();
        }

        return $sent;
    }

    // ----------------------------------------------------------------
    // Message construction
    // ----------------------------------------------------------------

    /**
     * @param array<mixed> $options
     */
    private function buildHeaders(
        string $fromName,
        string $fromEmail,
        string $to,
        string $replyTo,
        string $boundary,
        array $options
    ): string {
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "From: {$fromName} <{$fromEmail}>\r\n";
        $headers .= "Reply-To: {$replyTo}\r\n";
        $headers .= "To: {$to}\r\n";

        if (!empty($options['cc'])) {
            $headers .= "Cc: {$options['cc']}\r\n";
        }
        if (!empty($options['bcc'])) {
            $headers .= "Bcc: {$options['bcc']}\r\n";
        }

        $headers .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";
        $headers .= "X-Mailer: MailForge/1.0\r\n";
        $headers .= "Date: " . date('r') . "\r\n";

        return $headers;
    }

    /**
     * @param array<mixed> $attachments  Each item: ['path'=>'...', 'name'=>'...', 'type'=>'...']
     */
    private function buildBody(string $htmlBody, string $textBody, string $boundary, array $attachments): string
    {
        $altBoundary = '----=_Alt_' . md5(uniqid('', true));
        $body = '';

        // Multipart/alternative part (text + html)
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: multipart/alternative; boundary=\"{$altBoundary}\"\r\n\r\n";

        if ($textBody !== '') {
            $body .= "--{$altBoundary}\r\n";
            $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $body .= chunk_split(base64_encode($textBody)) . "\r\n";
        }

        $body .= "--{$altBoundary}\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode($htmlBody)) . "\r\n";

        $body .= "--{$altBoundary}--\r\n";

        // Attachments
        foreach ($attachments as $attachment) {
            if (empty($attachment['path']) || !is_readable($attachment['path'])) {
                continue;
            }

            $fileName    = $attachment['name'] ?? basename($attachment['path']);
            $mimeType    = $attachment['type'] ?? 'application/octet-stream';
            $fileContent = file_get_contents($attachment['path']);

            if ($fileContent === false) {
                continue;
            }

            $body .= "--{$boundary}\r\n";
            $body .= "Content-Type: {$mimeType}; name=\"{$fileName}\"\r\n";
            $body .= "Content-Transfer-Encoding: base64\r\n";
            $body .= "Content-Disposition: attachment; filename=\"{$fileName}\"\r\n\r\n";
            $body .= chunk_split(base64_encode($fileContent)) . "\r\n";
        }

        $body .= "--{$boundary}--\r\n";

        return $body;
    }

    // ----------------------------------------------------------------
    // SMTP transport
    // ----------------------------------------------------------------

    private function smtpSend(
        string $to,
        string $from,
        string $subject,
        string $headers,
        string $body
    ): bool {
        $host       = $this->config['host'];
        $port       = $this->config['port'];
        $username   = $this->config['username'];
        $password   = $this->config['password'];
        $encryption = strtolower($this->config['encryption']);

        $socketHost = ($encryption === 'ssl') ? "ssl://{$host}" : $host;

        $errno  = 0;
        $errStr = '';

        $socket = @fsockopen($socketHost, $port, $errno, $errStr, 30);
        if ($socket === false) {
            throw new \RuntimeException("SMTP connection failed [{$errno}]: {$errStr}");
        }

        stream_set_timeout($socket, 30);

        $this->smtpExpect($socket, '220');

        // EHLO
        $this->smtpSendLine($socket, "EHLO " . ($this->config['from_email'] !== '' ? parse_url('http://' . $this->config['from_email'], PHP_URL_HOST) ?? 'localhost' : 'localhost'));
        $ehloResponse = $this->smtpReadAll($socket);

        // STARTTLS
        if ($encryption === 'tls') {
            $this->smtpSendLine($socket, 'STARTTLS');
            $this->smtpExpect($socket, '220');

            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                fclose($socket);
                throw new \RuntimeException('STARTTLS handshake failed.');
            }

            $this->smtpSendLine($socket, "EHLO " . ($this->config['from_email'] !== '' ? (parse_url('http://' . $this->config['from_email'], PHP_URL_HOST) ?? 'localhost') : 'localhost'));
            $ehloResponse = $this->smtpReadAll($socket);
        }

        // AUTH LOGIN
        if ($username !== '' && str_contains($ehloResponse, 'AUTH')) {
            $this->smtpSendLine($socket, 'AUTH LOGIN');
            $this->smtpExpect($socket, '334');
            $this->smtpSendLine($socket, base64_encode($username));
            $this->smtpExpect($socket, '334');
            $this->smtpSendLine($socket, base64_encode($password));
            $this->smtpExpect($socket, '235');
        }

        $this->smtpSendLine($socket, "MAIL FROM:<{$from}>");
        $this->smtpExpect($socket, '250');

        $this->smtpSendLine($socket, "RCPT TO:<{$to}>");
        $this->smtpExpect($socket, '250');

        $this->smtpSendLine($socket, 'DATA');
        $this->smtpExpect($socket, '354');

        $message  = "Subject: {$subject}\r\n";
        $message .= $headers . "\r\n";
        $message .= $body;
        $message .= "\r\n.";

        $this->smtpSendLine($socket, $message);
        $this->smtpExpect($socket, '250');

        $this->smtpSendLine($socket, 'QUIT');
        fclose($socket);

        return true;
    }

    /** @param resource $socket */
    private function smtpSendLine($socket, string $line): void
    {
        fwrite($socket, $line . "\r\n");
    }

    /** @param resource $socket */
    private function smtpExpect($socket, string $code): string
    {
        $response = fgets($socket, 515);
        if ($response === false || !str_starts_with(trim($response), $code)) {
            throw new \RuntimeException("Unexpected SMTP response (expected {$code}): {$response}");
        }
        return trim($response);
    }

    /** @param resource $socket */
    private function smtpReadAll($socket): string
    {
        $data = '';
        while ($line = fgets($socket, 515)) {
            $data .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        return $data;
    }

    // ----------------------------------------------------------------
    // Rate limiting (file-based counters)
    // ----------------------------------------------------------------

    private function checkRateLimit(): void
    {
        $hourFile = $this->cacheDir . '/hour_' . date('YmdH') . '.count';
        $dayFile  = $this->cacheDir . '/day_' . date('Ymd') . '.count';

        $hourCount = (int) @file_get_contents($hourFile);
        $dayCount  = (int) @file_get_contents($dayFile);

        $maxHour = (int) $this->config['rate_limit']['max_per_hour'];
        $maxDay  = (int) $this->config['rate_limit']['max_per_day'];

        if ($maxHour > 0 && $hourCount >= $maxHour) {
            throw new \RuntimeException("Hourly email rate limit ({$maxHour}) exceeded.");
        }

        if ($maxDay > 0 && $dayCount >= $maxDay) {
            throw new \RuntimeException("Daily email rate limit ({$maxDay}) exceeded.");
        }
    }

    private function incrementCounter(): void
    {
        $hourFile = $this->cacheDir . '/hour_' . date('YmdH') . '.count';
        $dayFile  = $this->cacheDir . '/day_' . date('Ymd') . '.count';

        file_put_contents($hourFile, ((int) @file_get_contents($hourFile)) + 1);
        file_put_contents($dayFile,  ((int) @file_get_contents($dayFile))  + 1);
    }
}
