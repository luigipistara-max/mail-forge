<?php

declare(strict_types=1);

namespace MailForge\Controllers;

use MailForge\Core\Controller;
use MailForge\Core\Database;
use MailForge\Helpers\CsrfHelper;
use MailForge\Models\ActivityLog;
use MailForge\Models\Contact;
use MailForge\Models\ContactList;
use MailForge\Models\Tag;
use MailForge\Validators\Validator;

class ContactController extends Controller
{
    private Contact $contactModel;

    public function __construct()
    {
        parent::__construct();
        $this->contactModel = new Contact();
    }

    // ─── List ─────────────────────────────────────────────────────────────

    public function index(): never
    {
        $this->requireAuth();

        $page    = max(1, (int) ($this->request->query['page'] ?? 1));
        $perPage = 25;
        $search  = trim((string) ($this->request->query['search'] ?? ''));
        $status  = (string) ($this->request->query['status'] ?? '');
        $listId  = (string) ($this->request->query['list_id'] ?? '');

        $prefix     = Database::getPrefix();
        $conditions = ["`c`.`deleted_at` IS NULL"];
        $bindings   = [];

        if ($search !== '') {
            $conditions[] = "(`c`.`email` LIKE :search OR `c`.`first_name` LIKE :search OR `c`.`last_name` LIKE :search)";
            $bindings[':search'] = "%{$search}%";
        }

        if ($status !== '') {
            $conditions[] = "`c`.`status` = :status";
            $bindings[':status'] = $status;
        }

        if ($listId !== '') {
            $conditions[] = "EXISTS (
                SELECT 1 FROM `{$prefix}list_contacts` lc
                WHERE lc.`contact_id` = `c`.`id` AND lc.`list_id` = :list_id
            )";
            $bindings[':list_id'] = $listId;
        }

        $where      = implode(' AND ', $conditions);
        $countStmt  = $this->db->prepare("SELECT COUNT(*) FROM `{$prefix}contacts` c WHERE {$where}");
        $countStmt->execute($bindings);
        $total = (int) $countStmt->fetchColumn();

        $offset   = ($page - 1) * $perPage;
        $dataStmt = $this->db->prepare(
            "SELECT c.* FROM `{$prefix}contacts` c
             WHERE {$where}
             ORDER BY c.`created_at` DESC
             LIMIT {$perPage} OFFSET {$offset}"
        );
        $dataStmt->execute($bindings);
        $contacts = $dataStmt->fetchAll();

        $listModel = new ContactList();
        $lists     = $listModel->findAll();

        $this->render('contacts/index', [
            'contacts'    => $contacts,
            'lists'       => $lists,
            'total'       => $total,
            'page'        => $page,
            'perPage'     => $perPage,
            'search'      => $search,
            'status'      => $status,
            'listId'      => $listId,
            'lastPage'    => (int) ceil($total / $perPage),
            'success'     => $this->getFlash('success'),
            'error'       => $this->getFlash('error'),
        ]);
    }

    // ─── Show ─────────────────────────────────────────────────────────────

    public function show(int|string $id): never
    {
        $this->requireAuth();

        $contact = $this->contactModel->find($id);
        if ($contact === null) {
            $this->abort(404, 'Contact not found.');
        }

        $prefix  = Database::getPrefix();
        $tagModel = new Tag();
        $tags     = $tagModel->getContactTags($id);

        $listModel    = new ContactList();
        $prefix       = Database::getPrefix();
        $stmt         = $this->db->prepare(
            "SELECT l.* FROM `{$prefix}lists` l
             JOIN `{$prefix}list_contacts` lc ON lc.list_id = l.id
             WHERE lc.contact_id = ? AND l.deleted_at IS NULL"
        );
        $stmt->execute([$id]);
        $lists = $stmt->fetchAll();

        // Campaign history
        $stmt = $this->db->prepare(
            "SELECT cr.*, c.name AS campaign_name, c.subject
             FROM `{$prefix}campaign_recipients` cr
             JOIN `{$prefix}campaigns` c ON c.id = cr.campaign_id
             WHERE cr.contact_id = ?
             ORDER BY cr.created_at DESC
             LIMIT 50"
        );
        $stmt->execute([$id]);
        $campaignHistory = $stmt->fetchAll();

        $customFields = $this->contactModel->getCustomFieldValues($id);

        $activityLog = new ActivityLog();
        $activity    = $activityLog->getForEntity('contact', $id);

        $this->render('contacts/show', [
            'contact'         => $contact,
            'tags'            => $tags,
            'lists'           => $lists,
            'campaignHistory' => $campaignHistory,
            'customFields'    => $customFields,
            'activity'        => $activity,
        ]);
    }

    // ─── Create ───────────────────────────────────────────────────────────

    public function create(): never
    {
        $this->requireAuth();

        $listModel = new ContactList();
        $lists     = $listModel->findAll();

        $prefix = Database::getPrefix();
        $stmt   = $this->db->query("SELECT * FROM `{$prefix}custom_fields` ORDER BY `sort_order` ASC");
        $customFields = $stmt->fetchAll();

        $tagModel = new Tag();
        $tags     = $tagModel->findAll();

        $this->render('contacts/create', [
            'csrf'         => CsrfHelper::getToken(),
            'lists'        => $lists,
            'customFields' => $customFields,
            'tags'         => $tags,
            'old'          => $this->session->getOld('contact', []),
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
            $this->redirect('/contacts/create');
        }

        $data = [
            'email'      => trim((string) ($this->request->body['email'] ?? '')),
            'first_name' => trim((string) ($this->request->body['first_name'] ?? '')),
            'last_name'  => trim((string) ($this->request->body['last_name'] ?? '')),
            'phone'      => trim((string) ($this->request->body['phone'] ?? '')),
            'status'     => 'subscribed',
        ];

        $validator = Validator::make($data, [
            'email'      => 'required|email|unique:contacts:email',
            'first_name' => 'max:100',
            'last_name'  => 'max:100',
        ]);

        if ($validator->fails()) {
            $this->session->flashOldInput(['contact' => $data]);
            $this->flash('error', implode(' ', $validator->allErrors()));
            $this->redirect('/contacts/create');
        }

        $data['uuid']       = \Ramsey\Uuid\Uuid::uuid4()->toString();
        $data['created_at'] = date('Y-m-d H:i:s');

        $contactId = $this->contactModel->create($data);

        // Assign to lists
        $listIds   = (array) ($this->request->body['list_ids'] ?? []);
        $listModel = new ContactList();
        foreach ($listIds as $listId) {
            $listModel->addContact((int) $listId, (int) $contactId);
        }

        // Add tags
        $tagNames = array_filter(array_map('trim', explode(',', (string) ($this->request->body['tags'] ?? ''))));
        $tagModel = new Tag();
        foreach ($tagNames as $tagName) {
            if ($tagName !== '') {
                $tag = $tagModel->findOrCreate($tagName);
                $tagModel->addToContact((int) $tag['id'], (int) $contactId);
            }
        }

        // Custom field values
        $customValues = (array) ($this->request->body['custom_fields'] ?? []);
        foreach ($customValues as $fieldId => $value) {
            if ($value !== '') {
                $this->contactModel->setCustomFieldValue((int) $contactId, (int) $fieldId, $value);
            }
        }

        (new ActivityLog())->log(
            $this->currentUser()['id'] ?? null,
            'contact_created',
            'contact',
            $contactId,
            "Contact {$data['email']} created"
        );

        $this->flash('success', 'Contact created successfully.');
        $this->redirect("/contacts/{$contactId}");
    }

    // ─── Edit ─────────────────────────────────────────────────────────────

    public function edit(int|string $id): never
    {
        $this->requireAuth();

        $contact = $this->contactModel->find($id);
        if ($contact === null) {
            $this->abort(404, 'Contact not found.');
        }

        $listModel    = new ContactList();
        $lists        = $listModel->findAll();
        $contactLists = $listModel->getContacts(0, 1, 1000);

        $prefix = Database::getPrefix();
        $stmt   = $this->db->query("SELECT * FROM `{$prefix}custom_fields` ORDER BY `sort_order` ASC");
        $customFields  = $stmt->fetchAll();
        $customValues  = $this->contactModel->getCustomFieldValues($id);

        $tagModel    = new Tag();
        $allTags     = $tagModel->findAll();
        $contactTags = $tagModel->getContactTags($id);

        $this->render('contacts/edit', [
            'csrf'         => CsrfHelper::getToken(),
            'contact'      => $contact,
            'lists'        => $lists,
            'customFields' => $customFields,
            'customValues' => $customValues,
            'allTags'      => $allTags,
            'contactTags'  => $contactTags,
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
            $this->redirect("/contacts/{$id}/edit");
        }

        $contact = $this->contactModel->find($id);
        if ($contact === null) {
            $this->abort(404, 'Contact not found.');
        }

        $data = [
            'first_name' => trim((string) ($this->request->body['first_name'] ?? '')),
            'last_name'  => trim((string) ($this->request->body['last_name'] ?? '')),
            'phone'      => trim((string) ($this->request->body['phone'] ?? '')),
        ];

        $newEmail = trim((string) ($this->request->body['email'] ?? ''));
        if ($newEmail !== $contact['email']) {
            $validator = Validator::make(
                ['email' => $newEmail],
                ['email' => "required|email|unique:contacts:email:{$id}"]
            );
            if ($validator->fails()) {
                $this->flash('error', $validator->firstError('email'));
                $this->redirect("/contacts/{$id}/edit");
            }
            $data['email'] = $newEmail;
        }

        $this->contactModel->update((int) $id, $data);

        // Custom field values
        $customValues = (array) ($this->request->body['custom_fields'] ?? []);
        foreach ($customValues as $fieldId => $value) {
            $this->contactModel->setCustomFieldValue((int) $id, (int) $fieldId, $value);
        }

        // Tags: remove all, re-add
        $tagModel    = new Tag();
        $existingTags = $tagModel->getContactTags($id);
        foreach ($existingTags as $t) {
            $tagModel->removeFromContact((int) $t['id'], (int) $id);
        }
        $tagNames = array_filter(array_map('trim', explode(',', (string) ($this->request->body['tags'] ?? ''))));
        foreach ($tagNames as $tagName) {
            if ($tagName !== '') {
                $tag = $tagModel->findOrCreate($tagName);
                $tagModel->addToContact((int) $tag['id'], (int) $id);
            }
        }

        (new ActivityLog())->log(
            $this->currentUser()['id'] ?? null,
            'contact_updated',
            'contact',
            $id,
            "Contact updated"
        );

        $this->flash('success', 'Contact updated successfully.');
        $this->redirect("/contacts/{$id}");
    }

    // ─── Delete ───────────────────────────────────────────────────────────

    public function delete(int|string $id): never
    {
        $this->requireAuth();

        $csrfToken = (string) ($this->request->body['_csrf_token'] ?? '');
        if (!CsrfHelper::validate($csrfToken)) {
            $this->json(['error' => 'Invalid security token.'], 403);
        }

        $contact = $this->contactModel->find($id);
        if ($contact === null) {
            $this->json(['error' => 'Contact not found.'], 404);
        }

        $this->contactModel->delete((int) $id);

        (new ActivityLog())->log(
            $this->currentUser()['id'] ?? null,
            'contact_deleted',
            'contact',
            $id,
            "Contact {$contact['email']} deleted"
        );

        if ($this->request->expectsJson()) {
            $this->json(['success' => true]);
        }

        $this->flash('success', 'Contact deleted.');
        $this->redirect('/contacts');
    }

    // ─── CSV Import ───────────────────────────────────────────────────────

    public function importForm(): never
    {
        $this->requireAuth();

        $listModel = new ContactList();
        $lists     = $listModel->findAll();

        $this->render('contacts/import', [
            'csrf'    => CsrfHelper::getToken(),
            'lists'   => $lists,
            'error'   => $this->getFlash('error'),
            'success' => $this->getFlash('success'),
        ]);
    }

    public function import(): never
    {
        $this->requireAuth();

        $csrfToken = (string) ($this->request->body['_csrf_token'] ?? '');
        if (!CsrfHelper::validate($csrfToken)) {
            $this->flash('error', 'Invalid security token.');
            $this->redirect('/contacts/import');
        }

        $file = $this->request->files['csv_file'] ?? null;

        if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $this->flash('error', 'Please select a valid CSV file.');
            $this->redirect('/contacts/import');
        }

        $tmpPath = $file['tmp_name'];
        if (!is_file($tmpPath)) {
            $this->flash('error', 'File upload failed.');
            $this->redirect('/contacts/import');
        }

        $fh = fopen($tmpPath, 'r');
        if ($fh === false) {
            $this->flash('error', 'Unable to read uploaded file.');
            $this->redirect('/contacts/import');
        }

        $header = fgetcsv($fh);
        if ($header === false) {
            fclose($fh);
            $this->flash('error', 'CSV file is empty or invalid.');
            $this->redirect('/contacts/import');
        }

        $header = array_map('strtolower', array_map('trim', $header));

        if (!in_array('email', $header, true)) {
            fclose($fh);
            $this->flash('error', 'CSV must contain an "email" column.');
            $this->redirect('/contacts/import');
        }

        $rows = [];
        while (($row = fgetcsv($fh)) !== false) {
            $mapped = [];
            foreach ($header as $i => $col) {
                $mapped[$col] = $row[$i] ?? '';
            }
            $rows[] = $mapped;
        }
        fclose($fh);

        $result  = $this->contactModel->importCsv($rows);
        $listIds = (array) ($this->request->body['list_ids'] ?? []);

        if (!empty($listIds) && !empty($result['imported_ids'])) {
            $listModel = new ContactList();
            foreach ($listIds as $listId) {
                foreach ($result['imported_ids'] as $contactId) {
                    $listModel->addContact((int) $listId, (int) $contactId);
                }
            }
        }

        (new ActivityLog())->log(
            $this->currentUser()['id'] ?? null,
            'contacts_imported',
            null,
            null,
            "Imported {$result['imported']} contacts, {$result['skipped']} skipped"
        );

        $this->flash('success', "Import complete: {$result['imported']} imported, {$result['skipped']} skipped.");
        $this->redirect('/contacts');
    }

    // ─── CSV Export ───────────────────────────────────────────────────────

    public function exportCsv(): never
    {
        $this->requireAuth();

        $status = (string) ($this->request->query['status'] ?? '');
        $listId = (string) ($this->request->query['list_id'] ?? '');
        $prefix = Database::getPrefix();

        $conditions = ['`c`.`deleted_at` IS NULL'];
        $bindings   = [];

        if ($status !== '') {
            $conditions[] = '`c`.`status` = :status';
            $bindings[':status'] = $status;
        }

        if ($listId !== '') {
            $conditions[] = "EXISTS (
                SELECT 1 FROM `{$prefix}list_contacts` lc
                WHERE lc.`contact_id` = `c`.`id` AND lc.`list_id` = :list_id
            )";
            $bindings[':list_id'] = $listId;
        }

        $where = implode(' AND ', $conditions);
        $stmt  = $this->db->prepare(
            "SELECT c.`email`, c.`first_name`, c.`last_name`, c.`phone`, c.`status`, c.`created_at`
             FROM `{$prefix}contacts` c WHERE {$where} ORDER BY c.`email` ASC"
        );
        $stmt->execute($bindings);
        $contacts = $stmt->fetchAll();

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="contacts-' . date('Y-m-d') . '.csv"');
        header('Cache-Control: no-cache, no-store, must-revalidate');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['email', 'first_name', 'last_name', 'phone', 'status', 'created_at']);
        foreach ($contacts as $row) {
            fputcsv($out, [
                $row['email'],
                $row['first_name'],
                $row['last_name'],
                $row['phone'],
                $row['status'],
                $row['created_at'],
            ]);
        }
        fclose($out);
        exit(0);
    }

    // ─── Subscribe / Unsubscribe ──────────────────────────────────────────

    public function subscribe(int|string $id): never
    {
        $this->requireAuth();

        $csrfToken = (string) ($this->request->body['_csrf_token'] ?? '');
        if (!CsrfHelper::validate($csrfToken)) {
            $this->flash('error', 'Invalid security token.');
            $this->redirect("/contacts/{$id}");
        }

        $contact = $this->contactModel->find($id);
        if ($contact === null) {
            $this->abort(404, 'Contact not found.');
        }

        $this->contactModel->updateStatus((int) $id, 'subscribed');

        (new ActivityLog())->log(
            $this->currentUser()['id'] ?? null,
            'contact_subscribed',
            'contact',
            $id,
            "Contact re-subscribed"
        );

        $this->flash('success', 'Contact subscribed.');
        $this->redirect("/contacts/{$id}");
    }

    public function unsubscribe(int|string $id): never
    {
        $this->requireAuth();

        $csrfToken = (string) ($this->request->body['_csrf_token'] ?? '');
        if (!CsrfHelper::validate($csrfToken)) {
            $this->flash('error', 'Invalid security token.');
            $this->redirect("/contacts/{$id}");
        }

        $contact = $this->contactModel->find($id);
        if ($contact === null) {
            $this->abort(404, 'Contact not found.');
        }

        $this->contactModel->unsubscribe((int) $id, 'admin_action');

        (new ActivityLog())->log(
            $this->currentUser()['id'] ?? null,
            'contact_unsubscribed',
            'contact',
            $id,
            "Contact unsubscribed by admin"
        );

        $this->flash('success', 'Contact unsubscribed.');
        $this->redirect("/contacts/{$id}");
    }
}
