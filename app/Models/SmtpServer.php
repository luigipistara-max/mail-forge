<?php

declare(strict_types=1);

namespace MailForge\Models;

use RuntimeException;

class SmtpServer extends BaseModel
{
    protected static string $table = 'smtp_servers';
    protected static array $fillable = [
        'name',
        'host',
        'port',
        'encryption',
        'username',
        'password',
        'from_name',
        'from_email',
        'status',
        'priority',
        'hourly_limit',
        'daily_limit',
        'sent_today',
        'sent_this_hour',
        'last_reset_at',
    ];

    public function getActive(): array
    {
        $table = $this->getTable();
        $sql   = "SELECT * FROM `{$table}` WHERE `status` = 'active' ORDER BY `priority` ASC";

        return $this->executeQuery($sql)->fetchAll();
    }

    public function getPrimary(): ?array
    {
        $table = $this->getTable();
        $sql   = "SELECT * FROM `{$table}` WHERE `status` = 'active' ORDER BY `priority` ASC LIMIT 1";

        $stmt = $this->executeQuery($sql);
        $row  = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    public function testConnection(int|string $id): bool
    {
        $server = $this->find($id);

        if ($server === null) {
            return false;
        }

        try {
            $context  = stream_context_create();
            $protocol = match (strtolower($server['encryption'] ?? '')) {
                'ssl'  => 'ssl',
                'tls'  => 'tcp',
                default => 'tcp',
            };
            $socket   = stream_socket_client(
                "{$protocol}://{$server['host']}:{$server['port']}",
                $errno,
                $errstr,
                10,
                STREAM_CLIENT_CONNECT,
                $context
            );

            if ($socket === false) {
                return false;
            }

            fclose($socket);

            return true;
        } catch (RuntimeException) {
            return false;
        }
    }

    public function incrementSentCount(int|string $id): bool
    {
        $table = $this->getTable();
        $sql   = "UPDATE `{$table}`
                  SET `sent_today`      = `sent_today` + 1,
                      `sent_this_hour`  = `sent_this_hour` + 1
                  WHERE `id` = :id";

        $stmt = $this->executeQuery($sql, [':id' => $id]);

        return $stmt->rowCount() > 0;
    }

    public function resetHourlyCounts(): int
    {
        $table = $this->getTable();
        $sql   = "UPDATE `{$table}`
                  SET `sent_this_hour` = 0,
                      `last_reset_at`  = NOW()
                  WHERE `last_reset_at` < DATE_SUB(NOW(), INTERVAL 1 HOUR)
                     OR `last_reset_at` IS NULL";

        $stmt = $this->executeQuery($sql);

        return $stmt->rowCount();
    }

    public function canSend(int|string $id): bool
    {
        $server = $this->find($id);

        if ($server === null || $server['status'] !== 'active') {
            return false;
        }

        if (
            isset($server['hourly_limit'])
            && $server['hourly_limit'] > 0
            && (int) $server['sent_this_hour'] >= (int) $server['hourly_limit']
        ) {
            return false;
        }

        if (
            isset($server['daily_limit'])
            && $server['daily_limit'] > 0
            && (int) $server['sent_today'] >= (int) $server['daily_limit']
        ) {
            return false;
        }

        return true;
    }
}
