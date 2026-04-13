<?php
/** @var array $contacts */
/** @var array $pagination */
/** @var array $filters */
$contacts   = $contacts   ?? [];
$pagination = $pagination ?? [];
$filters    = $filters    ?? [];

$statusColors = [
    'subscribed'   => 'success',
    'unsubscribed' => 'secondary',
    'bounced'      => 'danger',
    'complained'   => 'warning',
    'pending'      => 'info',
];
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h6 class="fw-bold mb-0">All Contacts</h6>
    <div class="d-flex gap-2">
        <a href="<?= BASE_PATH ?>/contacts/import" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-upload me-1"></i>Import
        </a>
        <a href="<?= BASE_PATH ?>/contacts/create" class="btn btn-primary btn-sm">
            <i class="bi bi-person-plus me-1"></i>New Contact
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="<?= BASE_PATH ?>/contacts" class="row g-2 align-items-end">
            <div class="col-sm-6 col-md-5">
                <label for="search" class="form-label small fw-semibold">Search</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" id="search" name="search" class="form-control"
                        placeholder="Email, name, company..."
                        value="<?= htmlspecialchars($filters['search'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>
            </div>
            <div class="col-sm-4 col-md-3">
                <label for="status" class="form-label small fw-semibold">Status</label>
                <select id="status" name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <?php foreach (['subscribed','unsubscribed','bounced','complained','pending'] as $s): ?>
                    <option value="<?= $s ?>" <?= ($filters['status'] ?? '') === $s ? 'selected' : '' ?>>
                        <?= ucfirst($s) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                <a href="<?= BASE_PATH ?>/contacts" class="btn btn-outline-secondary btn-sm ms-1">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?php if (empty($contacts)): ?>
        <div class="text-center text-muted py-5">
            <i class="bi bi-people fs-1 d-block mb-2 opacity-25"></i>
            No contacts found. <a href="<?= BASE_PATH ?>/contacts/create">Add your first contact</a> or <a href="<?= BASE_PATH ?>/contacts/import">import from CSV</a>.
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th><input type="checkbox" class="form-check-input" id="selectAll"></th>
                        <th>Email</th>
                        <th>Name</th>
                        <th>Status</th>
                        <th>Lists</th>
                        <th>Tags</th>
                        <th>Created</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($contacts as $contact): ?>
                    <?php
                    $status = $contact['status'] ?? 'pending';
                    $badgeColor = $statusColors[$status] ?? 'secondary';
                    ?>
                    <tr>
                        <td><input type="checkbox" class="form-check-input contact-check" value="<?= (int)$contact['id'] ?>"></td>
                        <td>
                            <a href="<?= BASE_PATH ?>/contacts/<?= (int)$contact['id'] ?>" class="text-decoration-none fw-semibold">
                                <?= htmlspecialchars($contact['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars(trim(($contact['first_name'] ?? '') . ' ' . ($contact['last_name'] ?? '')), ENT_QUOTES, 'UTF-8') ?: '<span class="text-muted">—</span>' ?></td>
                        <td><span class="badge bg-<?= $badgeColor ?>"><?= htmlspecialchars(ucfirst($status), ENT_QUOTES, 'UTF-8') ?></span></td>
                        <td>
                            <?php if (!empty($contact['lists_count'])): ?>
                            <span class="badge bg-light text-dark border"><?= (int)$contact['lists_count'] ?></span>
                            <?php else: ?>
                            <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($contact['tags'])): ?>
                            <?php foreach (array_slice((array)$contact['tags'], 0, 3) as $tag): ?>
                            <span class="badge bg-secondary me-1"><?= htmlspecialchars($tag, ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endforeach; ?>
                            <?php if (count((array)$contact['tags']) > 3): ?>
                            <span class="badge bg-light text-muted border">+<?= count((array)$contact['tags']) - 3 ?></span>
                            <?php endif; ?>
                            <?php else: ?>
                            <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted small"><?= htmlspecialchars($contact['created_at'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="<?= BASE_PATH ?>/contacts/<?= (int)$contact['id'] ?>" class="btn btn-outline-secondary" title="View"><i class="bi bi-eye"></i></a>
                                <a href="<?= BASE_PATH ?>/contacts/<?= (int)$contact['id'] ?>/edit" class="btn btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                                <button type="button" class="btn btn-outline-danger" title="Delete"
                                    data-bs-toggle="modal" data-bs-target="#deleteModal"
                                    data-contact-id="<?= (int)$contact['id'] ?>"
                                    data-contact-email="<?= htmlspecialchars($contact['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if (!empty($pagination) && ($pagination['last_page'] ?? 1) > 1): ?>
        <div class="d-flex align-items-center justify-content-between px-3 py-3 border-top">
            <small class="text-muted">
                Showing <?= number_format((int)($pagination['from'] ?? 0)) ?>–<?= number_format((int)($pagination['to'] ?? 0)) ?>
                of <?= number_format((int)($pagination['total'] ?? 0)) ?> contacts
            </small>
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <?php if (($pagination['current_page'] ?? 1) > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= (int)$pagination['current_page'] - 1 ?>&<?= http_build_query(array_filter($filters)) ?>">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php for ($p = max(1, ($pagination['current_page'] ?? 1) - 2); $p <= min(($pagination['last_page'] ?? 1), ($pagination['current_page'] ?? 1) + 2); $p++): ?>
                    <li class="page-item <?= $p === ($pagination['current_page'] ?? 1) ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $p ?>&<?= http_build_query(array_filter($filters)) ?>"><?= $p ?></a>
                    </li>
                    <?php endfor; ?>
                    <?php if (($pagination['current_page'] ?? 1) < ($pagination['last_page'] ?? 1)): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= (int)$pagination['current_page'] + 1 ?>&<?= http_build_query(array_filter($filters)) ?>">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger"><i class="bi bi-trash me-2"></i>Delete Contact</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">Delete <strong id="deleteContactEmail"></strong>? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteForm" method="POST" action="">
                    <?= \MailForge\Helpers\CsrfHelper::field() ?>
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('deleteModal')?.addEventListener('show.bs.modal', function (e) {
    const btn = e.relatedTarget;
    document.getElementById('deleteContactEmail').textContent = btn.dataset.contactEmail;
    document.getElementById('deleteForm').action = '<?= BASE_PATH ?>/contacts/' + btn.dataset.contactId;
});
document.getElementById('selectAll')?.addEventListener('change', function () {
    document.querySelectorAll('.contact-check').forEach(cb => cb.checked = this.checked);
});
</script>
