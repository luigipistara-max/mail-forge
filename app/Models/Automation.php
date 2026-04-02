<?php

declare(strict_types=1);

namespace MailForge\Models;

use MailForge\Core\Database;

class Automation extends BaseModel
{
    protected static string $table = 'automations';
    protected static bool $softDelete = true;
    protected static array $fillable = [
        'name',
        'description',
        'trigger_type',
        'trigger_data',
        'status',
        'created_by',
    ];

    public function getActive(): array
    {
        $table = $this->getTable();
        $sql   = "SELECT * FROM `{$table}` WHERE `status` = 'active' AND `deleted_at` IS NULL ORDER BY `name` ASC";

        return $this->executeQuery($sql)->fetchAll();
    }

    public function getSteps(int|string $automationId): array
    {
        $stepsTable = Database::getPrefix() . 'automation_steps';
        $sql        = "SELECT * FROM `{$stepsTable}` WHERE `automation_id` = :automation_id ORDER BY `sort_order` ASC";

        return $this->executeQuery($sql, [':automation_id' => $automationId])->fetchAll();
    }

    public function addStep(int|string $automationId, array $stepData): int|string
    {
        $stepsTable                     = Database::getPrefix() . 'automation_steps';
        $stepData['automation_id']      = $automationId;

        $columns      = array_keys($stepData);
        $placeholders = array_map(fn(string $c) => ":{$c}", $columns);
        $colList      = implode(', ', array_map(fn(string $c) => "`{$c}`", $columns));
        $valList      = implode(', ', $placeholders);
        $sql          = "INSERT INTO `{$stepsTable}` ({$colList}) VALUES ({$valList})";
        $bindings     = array_combine($placeholders, array_values($stepData));

        $this->executeQuery($sql, $bindings);

        return $this->db->lastInsertId();
    }

    /**
     * Create an automation run for a specific contact and return the run ID.
     */
    public function triggerForContact(int|string $automationId, int|string $contactId): int|string
    {
        $runsTable = Database::getPrefix() . 'automation_runs';

        // Check if an active run already exists for this contact.
        $existingSql = "SELECT `id` FROM `{$runsTable}`
                        WHERE `automation_id` = :automation_id
                          AND `contact_id` = :contact_id
                          AND `status` IN ('pending', 'running')
                        LIMIT 1";

        $existing = $this->executeQuery($existingSql, [
            ':automation_id' => $automationId,
            ':contact_id'    => $contactId,
        ])->fetchColumn();

        if ($existing !== false) {
            return (int) $existing;
        }

        $sql = "INSERT INTO `{$runsTable}` (`automation_id`, `contact_id`, `status`, `current_step`, `started_at`)
                VALUES (:automation_id, :contact_id, 'pending', 0, NOW())";

        $this->executeQuery($sql, [
            ':automation_id' => $automationId,
            ':contact_id'    => $contactId,
        ]);

        return $this->db->lastInsertId();
    }

    public function getRuns(int|string $automationId): array
    {
        $runsTable = Database::getPrefix() . 'automation_runs';
        $sql       = "SELECT * FROM `{$runsTable}` WHERE `automation_id` = :automation_id ORDER BY `started_at` DESC";

        return $this->executeQuery($sql, [':automation_id' => $automationId])->fetchAll();
    }

    public function getContactRun(int|string $automationId, int|string $contactId): ?array
    {
        $runsTable = Database::getPrefix() . 'automation_runs';
        $sql       = "SELECT * FROM `{$runsTable}`
                      WHERE `automation_id` = :automation_id AND `contact_id` = :contact_id
                      ORDER BY `started_at` DESC
                      LIMIT 1";

        $stmt = $this->executeQuery($sql, [
            ':automation_id' => $automationId,
            ':contact_id'    => $contactId,
        ]);

        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }
}
