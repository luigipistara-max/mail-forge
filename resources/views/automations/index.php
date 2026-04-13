<?php
/** @var array $automations */
$automations = $automations ?? [];
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 fw-bold">Automations</h1>
    <a href="<?= BASE_PATH ?>/automations/create" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>New Automation
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?php if (empty($automations)): ?>
        <div class="text-center text-muted py-5">
            <i class="bi bi-robot fs-1 d-block mb-2 opacity-25"></i>
            No automations yet. <a href="<?= BASE_PATH ?>/automations/create">Create your first automation</a>.
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Status</th>
                        <th>Trigger</th>
                        <th class="text-end">Total Runs</th>
                        <th>Last Run</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($automations as $automation): ?>
                <tr>
                    <td class="fw-semibold">
                        <a href="<?= BASE_PATH ?>/automations/<?= (int)$automation['id'] ?>" class="text-decoration-none">
                            <?= htmlspecialchars($automation['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    </td>
                    <td>
                        <?php if (($automation['status'] ?? '') === 'active'): ?>
                        <span class="badge bg-success"><i class="bi bi-check-lg me-1"></i>Active</span>
                        <?php else: ?>
                        <span class="badge bg-secondary"><i class="bi bi-pause me-1"></i>Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php
                        $triggerLabels = [
                            'list_subscribe'   => 'List Subscribe',
                            'list_unsubscribe' => 'List Unsubscribe',
                            'date_based'       => 'Date Based',
                            'manual'           => 'Manual',
                        ];
                        $trigger = $automation['trigger_type'] ?? '';
                        ?>
                        <span class="badge bg-light text-dark border">
                            <?= htmlspecialchars($triggerLabels[$trigger] ?? ucfirst(str_replace('_', ' ', $trigger)), ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </td>
                    <td class="text-end"><?= number_format((int)($automation['total_runs'] ?? 0)) ?></td>
                    <td>
                        <?php if (!empty($automation['last_run_at'])): ?>
                            <span class="text-muted small">
                                <?= htmlspecialchars($automation['last_run_at'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        <?php else: ?>
                            <span class="text-muted small">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            <a href="<?= BASE_PATH ?>/automations/<?= (int)$automation['id'] ?>" class="btn btn-outline-secondary" title="View">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="<?= BASE_PATH ?>/automations/<?= (int)$automation['id'] ?>/edit" class="btn btn-outline-primary" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <button type="button" class="btn btn-outline-danger" title="Delete"
                                data-bs-toggle="modal" data-bs-target="#deleteModal"
                                data-automation-id="<?= (int)$automation['id'] ?>"
                                data-automation-name="<?= htmlspecialchars($automation['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
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
                <h5 class="modal-title text-danger">
                    <i class="bi bi-trash me-2"></i>Delete Automation
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">Delete <strong id="deleteAutomationName"></strong>? This cannot be undone.</p>
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
    document.getElementById('deleteAutomationName').textContent = btn.dataset.automationName;
    document.getElementById('deleteForm').action = '<?= BASE_PATH ?>/automations/' + btn.dataset.automationId;
});
</script>
