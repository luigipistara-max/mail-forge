<?php
/** @var array $lists */
$lists = $lists ?? [];
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h6 class="fw-bold mb-0">Mailing Lists</h6>
    <a href="<?= BASE_PATH ?>/lists/create" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>New List
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?php if (empty($lists)): ?>
        <div class="text-center text-muted py-5">
            <i class="bi bi-list-ul fs-1 d-block mb-2 opacity-25"></i>
            No mailing lists yet. <a href="<?= BASE_PATH ?>/lists/create">Create your first list</a>.
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Subscribers</th>
                        <th>From Email</th>
                        <th>Double Opt-in</th>
                        <th>Created</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($lists as $list): ?>
                <tr>
                    <td>
                        <a href="<?= BASE_PATH ?>/lists/<?= (int)$list['id'] ?>" class="text-decoration-none fw-semibold">
                            <?= htmlspecialchars($list['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                        </a>
                        <?php if (!empty($list['description'])): ?>
                        <div class="text-muted small"><?= htmlspecialchars($list['description'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge bg-light text-dark border">
                            <?= number_format((int)($list['subscribers_count'] ?? 0)) ?>
                        </span>
                    </td>
                    <td class="text-muted small"><?= htmlspecialchars($list['from_email'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <?php if (!empty($list['double_optin'])): ?>
                        <span class="badge bg-success"><i class="bi bi-check-lg me-1"></i>Enabled</span>
                        <?php else: ?>
                        <span class="badge bg-secondary">Disabled</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-muted small"><?= htmlspecialchars($list['created_at'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            <a href="<?= BASE_PATH ?>/lists/<?= (int)$list['id'] ?>" class="btn btn-outline-secondary" title="View"><i class="bi bi-eye"></i></a>
                            <a href="<?= BASE_PATH ?>/lists/<?= (int)$list['id'] ?>/edit" class="btn btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                            <button type="button" class="btn btn-outline-danger" title="Delete"
                                data-bs-toggle="modal" data-bs-target="#deleteModal"
                                data-list-id="<?= (int)$list['id'] ?>"
                                data-list-name="<?= htmlspecialchars($list['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger"><i class="bi bi-trash me-2"></i>Delete List</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">Delete list <strong id="deleteListName"></strong>? All subscriber associations will be removed. This cannot be undone.</p>
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
    document.getElementById('deleteListName').textContent = btn.dataset.listName;
    document.getElementById('deleteForm').action = '<?= BASE_PATH ?>/lists/' + btn.dataset.listId;
});
</script>
