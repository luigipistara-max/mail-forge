<?php

declare(strict_types=1);

namespace MailForge\Models;

/**
 * Automation model  (maps to the `automations` table)
 */
class Automation extends BaseModel
{
    protected string $table = 'automations';

    // ----------------------------------------------------------------
    // Status transitions
    // ----------------------------------------------------------------

    public function activate(int $automationId): bool
    {
        return $this->update($automationId, ['status' => AUTOMATION_STATUS_ACTIVE]);
    }

    public function pause(int $automationId): bool
    {
        return $this->update($automationId, ['status' => AUTOMATION_STATUS_PAUSED]);
    }

    public function stop(int $automationId): bool
    {
        return $this->update($automationId, ['status' => AUTOMATION_STATUS_STOPPED]);
    }

    // ----------------------------------------------------------------
    // Steps
    // ----------------------------------------------------------------

    /**
     * Add a step to the automation and return the new step ID.
     *
     * @param array<string, mixed> $stepData
     */
    public function addStep(int $automationId, array $stepData): int
    {
        $stepData['automation_id'] = $automationId;

        // Auto-assign step_order if not provided
        if (!isset($stepData['step_order'])) {
            $maxOrder = $this->rawQueryOne(
                'SELECT MAX(step_order) as max_order FROM `automation_steps` WHERE automation_id = ?',
                [$automationId]
            );
            $stepData['step_order'] = (int) ($maxOrder['max_order'] ?? 0) + 1;
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO `automation_steps`
             (automation_id, step_order, action_type, action_config, template_id, delay_minutes)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $stepData['automation_id'],
            $stepData['step_order'],
            $stepData['action_type']   ?? 'send_email',
            isset($stepData['action_config']) ? json_encode($stepData['action_config']) : null,
            $stepData['template_id']   ?? null,
            $stepData['delay_minutes'] ?? 0,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Return all steps for an automation, ordered by step_order.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getSteps(int $automationId): array
    {
        return $this->rawQuery(
            'SELECT * FROM `automation_steps` WHERE automation_id = ? ORDER BY step_order ASC',
            [$automationId]
        );
    }

    // ----------------------------------------------------------------
    // Execution
    // ----------------------------------------------------------------

    /**
     * Execute the next pending step for a contact in this automation.
     *
     * In a real deployment this would be called by a cron job / queue worker.
     *
     * @return array{step_id: int, action_type: string, status: string}|null
     */
    public function execute(int $automationId, int $contactId): ?array
    {
        $automation = $this->find($automationId);

        if ($automation === null || $automation['status'] !== AUTOMATION_STATUS_ACTIVE) {
            return null;
        }

        $steps = $this->getSteps($automationId);

        // Find the last executed step for this contact
        $lastLog = $this->rawQueryOne(
            'SELECT step_id FROM `automation_logs`
             WHERE automation_id = ? AND contact_id = ?
             ORDER BY executed_at DESC LIMIT 1',
            [$automationId, $contactId]
        );

        $nextStep = null;

        if ($lastLog === null) {
            $nextStep = $steps[0] ?? null;
        } else {
            foreach ($steps as $idx => $step) {
                if ((int) $step['id'] === (int) $lastLog['step_id']) {
                    $nextStep = $steps[$idx + 1] ?? null;
                    break;
                }
            }
        }

        if ($nextStep === null) {
            return null; // automation complete for this contact
        }

        // Log the execution
        $this->rawExecute(
            'INSERT INTO `automation_logs` (automation_id, contact_id, step_id, status, executed_at, result)
             VALUES (?, ?, ?, ?, NOW(), ?)',
            [
                $automationId,
                $contactId,
                $nextStep['id'],
                'executed',
                json_encode(['action_type' => $nextStep['action_type']]),
            ]
        );

        return [
            'step_id'     => (int) $nextStep['id'],
            'action_type' => $nextStep['action_type'],
            'status'      => 'executed',
        ];
    }

    // ----------------------------------------------------------------
    // Relationships
    // ----------------------------------------------------------------

    /**
     * @return array<int, array<string, mixed>>
     */
    public function logs(int $automationId): array
    {
        return $this->rawQuery(
            'SELECT * FROM `automation_logs` WHERE automation_id = ? ORDER BY executed_at DESC',
            [$automationId]
        );
    }
}
