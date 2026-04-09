<?php

declare(strict_types=1);

namespace MailForge\Controllers;

use MailForge\Core\Controller;
use MailForge\Core\Database;
use MailForge\Helpers\CsrfHelper;
use MailForge\Models\ActivityLog;
use MailForge\Models\Contact;
use MailForge\Models\ContactList;
use MailForge\Validators\Validator;

class ListController extends Controller
{
    private ContactList $listModel;

    public function __construct()
    {
        parent::__construct();
        $this->listModel = new ContactList();
    }

    // ─── Index ────────────────────────────────────────────────────────────

    public function index(): never
    {
        $this->requireAuth();

        $lists = $this->listModel->getWithCounts();

        $this->render('lists/index', [
            'lists'   => $lists,
            'success' => $this->getFlash('success'),
            'error'   => $this->getFlash('error'),
        ]);
    }

    // ─── Show ─────────────────────────────────────────────────────────────

    public function show(int|string $id): never
    {
        $this->requireAuth();

        $list = $this->listModel->find($id);
        if ($list === null) {
            $this->abort(404, 'List not found.');
        }

        $page    = max(1, (int) ($this->request->query['page'] ?? 1));
        $perPage = 25;
        $search  = trim((string) ($this->request->query['search'] ?? ''));

        $offset   = ($page - 1) * $perPage;
        $contacts = $this->listModel->getContacts((int) $id, null, $perPage, $offset);
        $total    = $this->listModel->getContactCount((int) $id);

        $prefix = Database::getPrefix();
        $stmt   = $this->db->prepare(
            "SELECT COUNT(*) FROM `{$prefix}campaign_recipients` cr
             JOIN `{$prefix}campaigns` c ON c.id = cr.campaign_id
             WHERE c.list_id = ?"
        );
        $stmt->execute([$id]);
        $totalSent = (int) $stmt->fetchColumn();

        $this->render('lists/show', [
            'list'      => $list,
            'contacts'  => $contacts,
            'total'     => $total,
            'page'      => $page,
            'perPage'   => $perPage,
            'lastPage'  => (int) ceil($total / $perPage),
            'search'    => $search,
            'totalSent' => $totalSent,
            'success'   => $this->getFlash('success'),
            'error'     => $this->getFlash('error'),
        ]);
    }

    // ─── Create ───────────────────────────────────────────────────────────

    public function create(): never
    {
        $this->requireAuth();

        $this->render('lists/form', [
            'csrf'  => CsrfHelper::getToken(),
            'old'   => $this->session->getOld('list', []),
            'error' => $this->getFlash('error'),
        ]);
    }

    // ─── Store ────────────────────────────────────────────────────────────

    public function store(): never
    {
        $this->requireAuth();

        $csrfToken = (string) ($this->request->body['_csrf_token'] ?? '');
        if (!CsrfHelper::validate($csrfToken)) {
            $this->flash('error', 'Invalid security token.');
            $this->redirect('/lists/create');
        }

        $data = [
            'name'              => trim((string) ($this->request->body['name'] ?? '')),
            'description'       => trim((string) ($this->request->body['description'] ?? '')),
            'from_name'         => trim((string) ($this->request->body['from_name'] ?? '')),
            'from_email'        => trim((string) ($this->request->body['from_email'] ?? '')),
            'double_optin'      => isset($this->request->body['double_optin']) ? 1 : 0,
            'welcome_email'     => isset($this->request->body['welcome_email']) ? 1 : 0,
        ];

        $validator = Validator::make($data, [
            'name'       => 'required|max:200',
            'from_email' => 'email',
        ]);

        if ($validator->fails()) {
            $this->session->flashOldInput(['list' => $data]);
            $this->flash('error', implode(' ', $validator->allErrors()));
            $this->redirect('/lists/create');
        }

        $data['uuid']       = \MailForge\Helpers\UuidHelper::generate();
        $data['created_at'] = date('Y-m-d H:i:s');

        $listId = $this->listModel->create($data);

        (new ActivityLog())->log(
            $this->currentUser()['id'] ?? null,
            'list_created',
            'list',
            $listId,
            "List '{$data['name']}' created"
        );

        $this->flash('success', 'List created successfully.');
        $this->redirect("/lists/{$listId}");
    }

    // ─── Edit ─────────────────────────────────────────────────────────────

    public function edit(int|string $id): never
    {
        $this->requireAuth();

        $list = $this->listModel->find($id);
        if ($list === null) {
            $this->abort(404, 'List not found.');
        }

        $this->render('lists/form', [
            'csrf'  => CsrfHelper::getToken(),
            'list'  => $list,
            'error' => $this->getFlash('error'),
        ]);
    }

    // ─── Update ───────────────────────────────────────────────────────────

    public function update(int|string $id): never
    {
        $this->requireAuth();

        $csrfToken = (string) ($this->request->body['_csrf_token'] ?? '');
        if (!CsrfHelper::validate($csrfToken)) {
            $this->flash('error', 'Invalid security token.');
            $this->redirect("/lists/{$id}/edit");
        }

        $list = $this->listModel->find($id);
        if ($list === null) {
            $this->abort(404, 'List not found.');
        }

        $data = [
            'name'         => trim((string) ($this->request->body['name'] ?? '')),
            'description'  => trim((string) ($this->request->body['description'] ?? '')),
            'from_name'    => trim((string) ($this->request->body['from_name'] ?? '')),
            'from_email'   => trim((string) ($this->request->body['from_email'] ?? '')),
            'double_optin' => isset($this->request->body['double_optin']) ? 1 : 0,
            'welcome_email'=> isset($this->request->body['welcome_email']) ? 1 : 0,
        ];

        $validator = Validator::make($data, [
            'name'       => 'required|max:200',
            'from_email' => 'email',
        ]);

        if ($validator->fails()) {
            $this->flash('error', implode(' ', $validator->allErrors()));
            $this->redirect("/lists/{$id}/edit");
        }

        $this->listModel->update((int) $id, $data);

        (new ActivityLog())->log(
            $this->currentUser()['id'] ?? null,
            'list_updated',
            'list',
            $id,
            "List '{$data['name']}' updated"
        );

        $this->flash('success', 'List updated successfully.');
        $this->redirect("/lists/{$id}");
    }

    // ─── Delete ───────────────────────────────────────────────────────────

    public function delete(int|string $id): never
    {
        $this->requireAuth();

        $csrfToken = (string) ($this->request->body['_csrf_token'] ?? '');
        if (!CsrfHelper::validate($csrfToken)) {
            $this->flash('error', 'Invalid security token.');
            $this->redirect('/lists');
        }

        $list = $this->listModel->find($id);
        if ($list === null) {
            $this->abort(404, 'List not found.');
        }

        $this->listModel->delete((int) $id);

        (new ActivityLog())->log(
            $this->currentUser()['id'] ?? null,
            'list_deleted',
            'list',
            $id,
            "List '{$list['name']}' deleted"
        );

        $this->flash('success', 'List deleted.');
        $this->redirect('/lists');
    }

    // ─── Contacts for a list ──────────────────────────────────────────────

    public function contacts(int|string $id): never
    {
        $this->requireAuth();

        $list = $this->listModel->find($id);
        if ($list === null) {
            $this->abort(404, 'List not found.');
        }

        $page    = max(1, (int) ($this->request->query['page'] ?? 1));
        $perPage = 25;
        $search  = trim((string) ($this->request->query['search'] ?? ''));

        $offset   = ($page - 1) * $perPage;
        $contacts = $this->listModel->getContacts((int) $id, null, $perPage, $offset);
        $total    = $this->listModel->getContactCount((int) $id);

        $this->render('lists/contacts', [
            'list'     => $list,
            'contacts' => $contacts,
            'total'    => $total,
            'page'     => $page,
            'perPage'  => $perPage,
            'lastPage' => (int) ceil($total / $perPage),
            'search'   => $search,
            'csrf'     => CsrfHelper::getToken(),
            'success'  => $this->getFlash('success'),
            'error'    => $this->getFlash('error'),
        ]);
    }

    // ─── Remove contact from list ─────────────────────────────────────────

    public function removeContact(int|string $listId, int|string $contactId): never
    {
        $this->requireAuth();

        $csrfToken = (string) ($this->request->body['_csrf_token'] ?? '');
        if (!CsrfHelper::validate($csrfToken)) {
            $this->flash('error', 'Invalid security token.');
            $this->redirect("/lists/{$listId}/contacts");
        }

        $list = $this->listModel->find($listId);
        if ($list === null) {
            $this->abort(404, 'List not found.');
        }

        $this->listModel->removeContact((int) $listId, (int) $contactId);
        $this->listModel->updateSubscriberCount((int) $listId);

        (new ActivityLog())->log(
            $this->currentUser()['id'] ?? null,
            'contact_removed_from_list',
            'list',
            $listId,
            "Contact {$contactId} removed from list {$listId}"
        );

        if ($this->request->expectsJson()) {
            $this->json(['success' => true]);
        }

        $this->flash('success', 'Contact removed from list.');
        $this->redirect("/lists/{$listId}/contacts");
    }
}
