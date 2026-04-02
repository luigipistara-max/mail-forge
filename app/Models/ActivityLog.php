<?php

declare(strict_types=1);

namespace MailForge\Models;

class ActivityLog extends BaseModel
{
    protected static string $table = 'activity_logs';
    protected static array $fillable = [
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'description',
        'ip_address',
        'created_at',
    ];

    public function log(
        int|string|null $userId,
        string $action,
        ?string $entityType = null,
        int|string|null $entityId = null,
        ?string $description = null,
        ?string $ip = null
    ): int|string {
        return $this->create([
            'user_id'     => $userId,
            'action'      => $action,
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'description' => $description,
            'ip_address'  => $ip,
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
    }

    public function getRecent(int $limit = 50): array
    {
        $table = $this->getTable();
        $sql   = "SELECT * FROM `{$table}` ORDER BY `created_at` DESC LIMIT {$limit}";

        return $this->executeQuery($sql)->fetchAll();
    }

    public function getForUser(int|string $userId, int $limit = 50): array
    {
        $table = $this->getTable();
        $sql   = "SELECT * FROM `{$table}` WHERE `user_id` = :user_id ORDER BY `created_at` DESC LIMIT {$limit}";

        return $this->executeQuery($sql, [':user_id' => $userId])->fetchAll();
    }

    public function getForEntity(string $entityType, int|string $entityId): array
    {
        $table = $this->getTable();
        $sql   = "SELECT * FROM `{$table}` WHERE `entity_type` = :entity_type AND `entity_id` = :entity_id ORDER BY `created_at` DESC";

        return $this->executeQuery($sql, [
            ':entity_type' => $entityType,
            ':entity_id'   => $entityId,
        ])->fetchAll();
    }
}
