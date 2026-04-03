<?php

declare(strict_types=1);

namespace MailForge\Controllers;

use MailForge\Core\Controller;
use MailForge\Core\Database;
use MailForge\Helpers\CsrfHelper;
use MailForge\Models\ActivityLog;
use MailForge\Models\Automation;
use MailForge\Models\ContactList;
use MailForge\Models\Template;
use MailForge\Validators\Validator;

class AutomationController extends Controller
{
    private Automation $automationModel;

    public function __construct()
    {
        parent::__construct();
        $this->automationModel = new Automation();
    }

    // ─── Index ────────────────────────────────────────────────────────────

    public function index(): never
    {
        $this->requireAuth();

        $automations = $this->automationModel->findAll(['deleted_at IS NULL'], 'name ASC');

        $this->render('automations/index', [
            'automations' => $automations,
            'success'     => $this->getFlash('success'),
            'error'       => $this->getFlash('error'),
        ]);
    }

    // ─── Show ─────────────────────────────────────────────────────────────

    public function show(int|string $id): never
    {
        $this->requireAuth();

        $automation = $this->automationModel->find($id);
        if ($automation === null) {
            $this->abort(404, 'Automation not found.');
        }

        $steps = $this->automationModel->getSteps((int) $id);

        $page    = max(1, (int) ($this->request->query['page'] ?? 1));
        $perPage = 25;
        $prefix  = Database::getPrefix();

        $runsTable = $prefix . 'automation_runs';
        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM `{$runsTable}` WHERE `automation_id` = ?");
        $countStmt->execute([$id]);
        $totalRuns = (int) $countStmt->fetchColumn();

        $offset   = ($page - 1) * $perPage;
        $runsStmt = $this->db->prepare(
            "SELECT ar.*, co.email, co.first_name, co.last_name
             FROM `{$runsTable}` ar
             LEFT JOIN `{$prefix}contacts` co ON co.id = ar.contact_id
             WHERE ar.automation_id = ?
             ORDER BY ar.started_at DESC
             LIMIT {$perPage} OFFSET {$offset}"
        );
        $runsStmt->execute([$id]);
        $runs = $runsStmt->fetchAll();

        $this->render('automations/show', [
            'automation'      => $automation,
            'steps'           => $steps,
            'runs'            => $runs,
            'runsPagination'  => [
                'total'        => $totalRuns,
                'current_page' => $page,
                'last_page'    => (int) ceil($totalRuns / $perPage),
                'from'         => $totalRuns > 0 ? $offset + 1 : 0,
                'to'           => min($offset + $perPage, $totalRuns),
            ],
        ]);
    }

    // ─── Create ───────────────────────────────────────────────────────────

    public function create(): never
    {
        $this->requireAuth();

        $templateModel = new Template();
        $listModel     = new ContactList();

        $this->render('automations/form', [
            'csrf'      => CsrfHelper::getToken(),
            'templates' => $templateModel->getActive(),
            'lists'     => $listModel->findAll(),
            'error'     => $this->getFlash('error'),
        ]);
    }

    // ─── Store ────────────────────────────────────────────────────────────

    public function store(): never
    {
        $this->requireAuth();

        $csrfToken = (string) ($this->request->body['_csrf_token'] ?? '');
        if (!CsrfHelper::validate($csrfToken)) {
            $this->flash('error', 'Invalid security token.');
            $this->redirect('/automations/create');
        }

        $name        = trim((string) ($this->request->body['name'] ?? ''));
        $description = trim((string) ($this->request->body['description'] ?? ''));
        $triggerType = trim((string) ($this->request->body['trigger_type'] ?? 'subscribe'));
        $triggerData = (string) ($this->request->body['trigger_data'] ?? '{}');

        $validator = Validator::make(
            ['name' => $name, 'trigger_type' => $triggerType],
            ['name' => 'required|max:200', 'trigger_type' => 'required']
        );

        if ($validator->fails()) {
            $this->flash('error', implode(' ', $validator->allErrors()));
            $this->redirect('/automations/create');
        }

        $automationId = $this->automationModel->create([
            'name'         => $name,
            'description'  => $description,
            'trigger_type' => $triggerType,
            'trigger_data' => $triggerData,
            'status'       => 'inactive',
            'created_by'   => $this->currentUser()['id'] ?? null,
            'created_at'   => date('Y-m-d H:i:s'),
        ]);

        $this->saveSteps((int) $automationId);

        (new ActivityLog())->log(
            $this->currentUser()['id'] ?? null,
            'automation_created',
            'automation',
            $automationId,
            "Automation '{$name}' created"
        );

        $this->flash('success', 'Automation created successfully.');
        $this->redirect("/automations/{$automationId}");
    }

    // ─── Edit ─────────────────────────────────────────────────────────────

    public function edit(int|string $id): never
    {
        $this->requireAuth();

        $automation = $this->automationModel->find($id);
        if ($automation === null) {
            $this->abort(404, 'Automation not found.');
        }

        $automation['steps'] = $this->automationModel->getSteps((int) $id);

        $templateModel = new Template();
        $listModel     = new ContactList();

        $this->render('automations/form', [
            'csrf'       => CsrfHelper::getToken(),
            'automation' => $automation,
            'templates'  => $templateModel->getActive(),
            'lists'      => $listModel->findAll(),
            'error'      => $this->getFlash('error'),
        ]);
    }

    // ─── Update ───────────────────────────────────────────────────────────

    public function update(int|string $id): never
    {
        $this->requireAuth();

        $csrfToken = (string) ($this->request->body['_csrf_token'] ?? '');
        if (!CsrfHelper::validate($csrfToken)) {
            $this->flash('error', 'Invalid security token.');
            $this->redirect("/automations/{$id}/edit");
        }

        $automation = $this->automationModel->find($id);
        if ($automation === null) {
            $this->abort(404, 'Automation not found.');
        }

        $name        = trim((string) ($this->request->body['name'] ?? ''));
        $description = trim((string) ($this->request->body['description'] ?? ''));
        $triggerType = trim((string) ($this->request->body['trigger_type'] ?? 'subscribe'));
        $triggerData = (string) ($this->request->body['trigger_data'] ?? '{}');

        $validator = Validator::make(
            ['name' => $name, 'trigger_type' => $triggerType],
            ['name' => 'required|max:200', 'trigger_type' => 'required']
        );

        if ($validator->fails()) {
            $this->flash('error', implode(' ', $validator->allErrors()));
            $this->redirect("/automations/{$id}/edit");
        }

        $this->automationModel->update((int) $id, [
            'name'         => $name,
            'description'  => $description,
            'trigger_type' => $triggerType,
            'trigger_data' => $triggerData,
        ]);

        // Replace steps
        $prefix = Database::getPrefix();
        $this->db->prepare("DELETE FROM `{$prefix}automation_steps` WHERE `automation_id` = ?")->execute([$id]);
        $this->saveSteps((int) $id);

        (new ActivityLog())->log(
            $this->currentUser()['id'] ?? null,
            'automation_updated',
            'automation',
            $id,
            "Automation '{$name}' updated"
        );

        $this->flash('success', 'Automation updated successfully.');
        $this->redirect("/automations/{$id}");
    }

    // ─── Toggle Active ────────────────────────────────────────────────────

    public function toggle(int|string $id): never
    {
        $this->requireAuth();

        $csrfToken = (string) ($this->request->body['_csrf_token'] ?? '');
        if (!CsrfHelper::validate($csrfToken)) {
            $this->flash('error', 'Invalid security token.');
            $this->redirect("/automations/{$id}");
        }

        $automation = $this->automationModel->find($id);
        if ($automation === null) {
            $this->abort(404, 'Automation not found.');
        }

        $newStatus = ($automation['status'] ?? '') === 'active' ? 'inactive' : 'active';
        $this->automationModel->update((int) $id, ['status' => $newStatus]);

        (new ActivityLog())->log(
            $this->currentUser()['id'] ?? null,
            'automation_toggled',
            'automation',
            $id,
            "Automation '{$automation['name']}' set to {$newStatus}"
        );

        $this->flash('success', 'Automation status updated.');
        $this->redirect("/automations/{$id}");
    }

    // ─── Delete ───────────────────────────────────────────────────────────

    public function delete(int|string $id): never
    {
        $this->requireAuth();

        $csrfToken = (string) ($this->request->body['_csrf_token'] ?? '');
        if (!CsrfHelper::validate($csrfToken)) {
            $this->flash('error', 'Invalid security token.');
            $this->redirect('/automations');
        }

        $automation = $this->automationModel->find($id);
        if ($automation === null) {
            $this->abort(404, 'Automation not found.');
        }

        $this->automationModel->delete((int) $id);

        (new ActivityLog())->log(
            $this->currentUser()['id'] ?? null,
            'automation_deleted',
            'automation',
            $id,
            "Automation '{$automation['name']}' deleted"
        );

        $this->flash('success', 'Automation deleted.');
        $this->redirect('/automations');
    }

    // ─── Private helpers ──────────────────────────────────────────────────

    private function saveSteps(int $automationId): void
    {
        $stepsJson = (string) ($this->request->body['steps'] ?? '[]');
        $steps     = json_decode($stepsJson, true);

        if (!is_array($steps)) {
            return;
        }

        foreach ($steps as $order => $step) {
            $type     = trim((string) ($step['type']     ?? ''));
            $config   = is_array($step['config'] ?? null) ? json_encode($step['config']) : '{}';
            $delayVal = max(0, (int) ($step['delay_value'] ?? 0));
            $delayUnit = in_array($step['delay_unit'] ?? 'minutes', ['minutes', 'hours', 'days'], true)
                ? $step['delay_unit']
                : 'minutes';

            if ($type === '') {
                continue;
            }

            $this->automationModel->addStep($automationId, [
                'type'        => $type,
                'config'      => $config,
                'delay_value' => $delayVal,
                'delay_unit'  => $delayUnit,
                'sort_order'  => (int) $order,
                'created_at'  => date('Y-m-d H:i:s'),
            ]);
        }
    }
}
