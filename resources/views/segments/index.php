<?php
/** @var array $segments */
$segments = $segments ?? [];
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h6 class="fw-bold mb-0">Segments</h6>
    <a href="/segments/create" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>New Segment
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?php if (empty($segments)): ?>
        <div class="text-center text-muted py-5">
            <i class="bi bi-funnel fs-1 d-block mb-2 opacity-25"></i>
            No segments yet. <a href="/segments/create">Create your first segment</a>.
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Estimated Count</th>
                        <th>Match Type</th>
                        <th>Rules</th>
                        <th>Last Calculated</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($segments as $segment): ?>
                <tr>
                    <td>
                        <span class="fw-semibold"><?= htmlspecialchars($segment['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                        <?php if (!empty($segment['description'])): ?>
                        <div class="text-muted small"><?= htmlspecialchars($segment['description'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge bg-light text-dark border">
                            <?= number_format((int)($segment['estimated_count'] ?? 0)) ?>
                        </span>
                    </td>
                    <td>
                        <?php $mt = $segment['match_type'] ?? 'all'; ?>
                        <span class="badge bg-<?= $mt === 'all' ? 'primary' : 'info' ?>">
                            <?= $mt === 'all' ? 'All Rules' : 'Any Rule' ?>
                        </span>
                    </td>
                    <td class="text-muted small"><?= (int)($segment['rules_count'] ?? 0) ?> rule(s)</td>
                    <td class="text-muted small"><?= htmlspecialchars($segment['last_calculated_at'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            <a href="/segments/<?= (int)$segment['id'] ?>/edit" class="btn btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                            <button type="button" class="btn btn-outline-danger" title="Delete"
                                data-bs-toggle="modal" data-bs-target="#deleteModal"
                                data-segment-id="<?= (int)$segment['id'] ?>"
                                data-segment-name="<?= htmlspecialchars($segment['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
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
                <h5 class="modal-title text-danger"><i class="bi bi-trash me-2"></i>Delete Segment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">Delete segment <strong id="deleteSegmentName"></strong>? This cannot be undone.</p>
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
    document.getElementById('deleteSegmentName').textContent = btn.dataset.segmentName;
    document.getElementById('deleteForm').action = '/segments/' + btn.dataset.segmentId;
});
</script>
