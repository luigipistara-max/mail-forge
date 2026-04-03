<?php
/** @var array $templates */
/** @var array $pagination */
/** @var array $filters */
$templates  = $templates  ?? [];
$pagination = $pagination ?? [];
$filters    = $filters    ?? [];
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h6 class="fw-bold mb-0">Templates</h6>
    <a href="/templates/create" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>New Template
    </a>
</div>

<!-- Filters -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <form method="GET" action="/templates" class="row g-2 align-items-end">
            <div class="col-sm-5">
                <label class="form-label fw-semibold small mb-1">Search</label>
                <input type="text" name="search" class="form-control form-control-sm"
                    placeholder="Name or subject…"
                    value="<?= htmlspecialchars($filters['search'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-sm-3">
                <label class="form-label fw-semibold small mb-1">Category</label>
                <select name="category" class="form-select form-select-sm">
                    <option value="">All Categories</option>
                    <?php foreach (['newsletter' => 'Newsletter', 'promotional' => 'Promotional', 'transactional' => 'Transactional', 'automated' => 'Automated'] as $val => $label): ?>
                    <option value="<?= $val ?>" <?= ($filters['category'] ?? '') === $val ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-2">
                <button type="submit" class="btn btn-outline-secondary btn-sm w-100">
                    <i class="bi bi-search me-1"></i>Filter
                </button>
            </div>
            <?php if (!empty($filters['search']) || !empty($filters['category'])): ?>
            <div class="col-sm-2">
                <a href="/templates" class="btn btn-link btn-sm text-muted">Clear</a>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?php if (empty($templates)): ?>
        <div class="text-center text-muted py-5">
            <i class="bi bi-file-earmark-richtext fs-1 d-block mb-2 opacity-25"></i>
            No templates found. <a href="/templates/create">Create your first template</a>.
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Subject</th>
                        <th>Last Modified</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $categoryBadge = [
                    'newsletter'    => 'primary',
                    'promotional'   => 'warning',
                    'transactional' => 'info',
                    'automated'     => 'secondary',
                ];
                foreach ($templates as $tpl):
                    $catColor = $categoryBadge[$tpl['category'] ?? ''] ?? 'secondary';
                ?>
                <tr>
                    <td>
                        <span class="fw-semibold"><?= htmlspecialchars($tpl['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                    </td>
                    <td>
                        <span class="badge bg-<?= $catColor ?>">
                            <?= htmlspecialchars(ucfirst($tpl['category'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </td>
                    <td class="text-muted small"><?= htmlspecialchars($tpl['subject'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="text-muted small"><?= htmlspecialchars($tpl['updated_at'] ?? $tpl['created_at'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            <a href="/templates/<?= (int)$tpl['id'] ?>" class="btn btn-outline-secondary" title="Preview">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="/templates/<?= (int)$tpl['id'] ?>/edit" class="btn btn-outline-primary" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form method="POST" action="/templates/<?= (int)$tpl['id'] ?>/duplicate" class="d-inline">
                                <?= \MailForge\Helpers\CsrfHelper::field() ?>
                                <button type="submit" class="btn btn-outline-secondary" title="Duplicate">
                                    <i class="bi bi-copy"></i>
                                </button>
                            </form>
                            <button type="button" class="btn btn-outline-danger" title="Delete"
                                data-bs-toggle="modal" data-bs-target="#deleteModal"
                                data-tpl-id="<?= (int)$tpl['id'] ?>"
                                data-tpl-name="<?= htmlspecialchars($tpl['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
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
        <div class="d-flex justify-content-center py-3">
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <?php for ($p = 1; $p <= (int)$pagination['last_page']; $p++): ?>
                    <li class="page-item <?= $p === (int)($pagination['current_page'] ?? 1) ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $p ?><?= !empty($filters['search']) ? '&search=' . urlencode($filters['search']) : '' ?><?= !empty($filters['category']) ? '&category=' . urlencode($filters['category']) : '' ?>"><?= $p ?></a>
                    </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
        <?php endif; ?>

        <?php endif; ?>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger"><i class="bi bi-trash me-2"></i>Delete Template</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">Delete template <strong id="deleteTplName"></strong>? This cannot be undone.</p>
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
    document.getElementById('deleteTplName').textContent = btn.dataset.tplName;
    document.getElementById('deleteForm').action = '/templates/' + btn.dataset.tplId;
});
</script>
