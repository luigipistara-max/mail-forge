<?php

declare(strict_types=1);

namespace MailForge\Controllers;

use MailForge\Core\Controller;
use MailForge\Core\Database;
use MailForge\Helpers\CsrfHelper;
use MailForge\Models\ActivityLog;
use MailForge\Models\Segment;
use MailForge\Validators\Validator;

class SegmentController extends Controller
{
    private Segment $segmentModel;

    public function __construct()
    {
        parent::__construct();
        $this->segmentModel = new Segment();
    }

    // ─── Index ────────────────────────────────────────────────────────────

    public function index(): never
    {
        $this->requireAuth();

        $segments = $this->segmentModel->findAll();

        $this->render('segments/index', [
            'segments' => $segments,
            'success'  => $this->getFlash('success'),
            'error'    => $this->getFlash('error'),
        ]);
    }

    // ─── Create ───────────────────────────────────────────────────────────

    public function create(): never
    {
        $this->requireAuth();

        $filterFields = $this->getAvailableFilterFields();

        $this->render('segments/form', [
            'csrf'         => CsrfHelper::getToken(),
            'filterFields' => $filterFields,
            'error'        => $this->getFlash('error'),
        ]);
    }

    // ─── Store ────────────────────────────────────────────────────────────

    public function store(): never
    {
        $this->requireAuth();

        $csrfToken = (string) ($this->request->body['_csrf_token'] ?? '');
        if (!CsrfHelper::validate($csrfToken)) {
            $this->flash('error', 'Invalid security token.');
            $this->redirect('/segments/create');
        }

        $name      = trim((string) ($this->request->body['name'] ?? ''));
        $matchType = (string) ($this->request->body['match_type'] ?? 'all');

        $validator = Validator::make(
            ['name' => $name, 'match_type' => $matchType],
            ['name' => 'required|max:200', 'match_type' => 'required|in:all,any']
        );

        if ($validator->fails()) {
            $this->flash('error', implode(' ', $validator->allErrors()));
            $this->redirect('/segments/create');
        }

        $segmentId = $this->segmentModel->create([
            'name'           => $name,
            'description'    => trim((string) ($this->request->body['description'] ?? '')),
            'match_type'     => $matchType,
            'uuid'           => \Ramsey\Uuid\Uuid::uuid4()->toString(),
            'created_by'     => $this->currentUser()['id'] ?? null,
            'created_at'     => date('Y-m-d H:i:s'),
        ]);

        $this->saveRules((int) $segmentId);

        $contacts = $this->segmentModel->calculateContacts((int) $segmentId);
        $this->segmentModel->update((int) $segmentId, [
            'estimated_count' => count($contacts),
        ]);

        (new ActivityLog())->log(
            $this->currentUser()['id'] ?? null,
            'segment_created',
            'segment',
            $segmentId,
            "Segment '{$name}' created"
        );

        $this->flash('success', 'Segment created successfully.');
        $this->redirect('/segments');
    }

    // ─── Edit ─────────────────────────────────────────────────────────────

    public function edit(int|string $id): never
    {
        $this->requireAuth();

        $segment = $this->segmentModel->find($id);
        if ($segment === null) {
            $this->abort(404, 'Segment not found.');
        }

        $rules        = $this->segmentModel->getRules($id);
        $filterFields = $this->getAvailableFilterFields();

        $this->render('segments/form', [
            'csrf'         => CsrfHelper::getToken(),
            'segment'      => $segment,
            'rules'        => $rules,
            'filterFields' => $filterFields,
            'error'        => $this->getFlash('error'),
        ]);
    }

    // ─── Update ───────────────────────────────────────────────────────────

    public function update(int|string $id): never
    {
        $this->requireAuth();

        $csrfToken = (string) ($this->request->body['_csrf_token'] ?? '');
        if (!CsrfHelper::validate($csrfToken)) {
            $this->flash('error', 'Invalid security token.');
            $this->redirect("/segments/{$id}/edit");
        }

        $segment = $this->segmentModel->find($id);
        if ($segment === null) {
            $this->abort(404, 'Segment not found.');
        }

        $name      = trim((string) ($this->request->body['name'] ?? ''));
        $matchType = (string) ($this->request->body['match_type'] ?? 'all');

        $validator = Validator::make(
            ['name' => $name, 'match_type' => $matchType],
            ['name' => 'required|max:200', 'match_type' => 'required|in:all,any']
        );

        if ($validator->fails()) {
            $this->flash('error', implode(' ', $validator->allErrors()));
            $this->redirect("/segments/{$id}/edit");
        }

        $this->segmentModel->update((int) $id, [
            'name'        => $name,
            'description' => trim((string) ($this->request->body['description'] ?? '')),
            'match_type'  => $matchType,
        ]);

        $this->saveRules((int) $id, true);

        $contacts = $this->segmentModel->calculateContacts((int) $id);
        $this->segmentModel->update((int) $id, ['estimated_count' => count($contacts)]);

        (new ActivityLog())->log(
            $this->currentUser()['id'] ?? null,
            'segment_updated',
            'segment',
            $id,
            "Segment '{$name}' updated"
        );

        $this->flash('success', 'Segment updated.');
        $this->redirect('/segments');
    }

    // ─── Delete ───────────────────────────────────────────────────────────

    public function delete(int|string $id): never
    {
        $this->requireAuth();

        $csrfToken = (string) ($this->request->body['_csrf_token'] ?? '');
        if (!CsrfHelper::validate($csrfToken)) {
            $this->flash('error', 'Invalid security token.');
            $this->redirect('/segments');
        }

        $segment = $this->segmentModel->find($id);
        if ($segment === null) {
            $this->abort(404, 'Segment not found.');
        }

        $this->segmentModel->delete((int) $id);

        (new ActivityLog())->log(
            $this->currentUser()['id'] ?? null,
            'segment_deleted',
            'segment',
            $id,
            "Segment '{$segment['name']}' deleted"
        );

        $this->flash('success', 'Segment deleted.');
        $this->redirect('/segments');
    }

    // ─── Preview (AJAX) ───────────────────────────────────────────────────

    public function preview(int|string $id): never
    {
        $this->requireAuth();

        $segment = $this->segmentModel->find($id);
        if ($segment === null) {
            $this->json(['error' => 'Segment not found.'], 404);
        }

        $contacts = $this->segmentModel->calculateContacts((int) $id);
        $sample   = array_slice($contacts, 0, 10);

        $this->json([
            'count'   => count($contacts),
            'sample'  => array_map(fn(array $c) => [
                'id'    => $c['id'],
                'email' => $c['email'],
                'name'  => trim(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? '')),
            ], $sample),
        ]);
    }

    // ─── Calculate ────────────────────────────────────────────────────────

    public function calculate(int|string $id): never
    {
        $this->requireAuth();

        $segment = $this->segmentModel->find($id);
        if ($segment === null) {
            $this->abort(404, 'Segment not found.');
        }

        $contacts = $this->segmentModel->calculateContacts((int) $id);
        $count    = count($contacts);

        $this->segmentModel->update((int) $id, ['estimated_count' => $count]);

        if ($this->request->expectsJson()) {
            $this->json(['count' => $count]);
        }

        $this->flash('success', "Segment recalculated: {$count} contacts.");
        $this->redirect('/segments');
    }

    // ─── Private helpers ──────────────────────────────────────────────────

    /** @return array<int, array<string, string>> */
    private function getAvailableFilterFields(): array
    {
        return [
            ['field' => 'email',        'label' => 'Email',            'type' => 'text'],
            ['field' => 'first_name',   'label' => 'First Name',       'type' => 'text'],
            ['field' => 'last_name',    'label' => 'Last Name',        'type' => 'text'],
            ['field' => 'status',       'label' => 'Status',           'type' => 'select',
             'options' => ['subscribed','unsubscribed','bounced','complained']],
            ['field' => 'country',      'label' => 'Country',          'type' => 'text'],
            ['field' => 'city',         'label' => 'City',             'type' => 'text'],
            ['field' => 'created_at',   'label' => 'Date Subscribed',  'type' => 'date'],
        ];
    }

    private function saveRules(int $segmentId, bool $replace = false): void
    {
        if ($replace) {
            $prefix = Database::getPrefix();
            $this->db->prepare(
                "DELETE FROM `{$prefix}segment_rules` WHERE `segment_id` = ?"
            )->execute([$segmentId]);
        }

        $allowedFields = array_column($this->getAvailableFilterFields(), 'field');
        $allowedOps    = ['equals', 'not_equals', 'contains', 'not_contains', 'starts_with',
                          'ends_with', 'greater_than', 'less_than', 'is_null', 'is_not_null'];

        $fields    = (array) ($this->request->body['rule_field'] ?? []);
        $operators = (array) ($this->request->body['rule_operator'] ?? []);
        $values    = (array) ($this->request->body['rule_value'] ?? []);

        foreach ($fields as $i => $field) {
            $field    = trim((string) $field);
            $operator = trim((string) ($operators[$i] ?? 'equals'));
            $value    = trim((string) ($values[$i] ?? ''));

            if ($field === '' || !in_array($field, $allowedFields, true)) {
                continue;
            }

            if (!in_array($operator, $allowedOps, true)) {
                $operator = 'equals';
            }

            $noValueOps = ['is_null', 'is_not_null'];
            if (!in_array($operator, $noValueOps, true) && $value === '') {
                continue;
            }

            $this->segmentModel->addRule($segmentId, [
                'field'      => $field,
                'operator'   => $operator,
                'value'      => $value,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }
}
