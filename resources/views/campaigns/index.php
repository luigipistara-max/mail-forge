<?php
/** @var array $campaigns */
/** @var int $total */
/** @var int $page */
/** @var int $perPage */
/** @var int $lastPage */
/** @var string $status */
$campaigns = $campaigns ?? [];
$total     = (int)($total    ?? 0);
$page      = (int)($page     ?? 1);
$perPage   = (int)($perPage  ?? 20);
$lastPage  = (int)($lastPage ?? 1);
$status    = $status ?? '';

$statusTabs = [
    ''          => 'All',
    'draft'     => 'Draft',
    'scheduled' => 'Scheduled',
    'queued'    => 'Queued',
    'sending'   => 'Sending',
    'completed' => 'Completed',
    'paused'    => 'Paused',
    'cancelled' => 'Cancelled',
];

$statusColors = [
    'draft'     => 'secondary',
    'scheduled' => 'info',
    'queued'    => 'warning',
    'sending'   => 'primary',
    'completed' => 'success',
    'paused'    => 'warning',
    'cancelled' => 'dark',
];
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h6 class="fw-bold mb-0">Campaigns</h6>
    <a href="/campaigns/create" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>New Campaign
    </a>
</div>

<!-- Status Tabs -->
<ul class="nav nav-tabs mb-0" style="border-bottom:none;">
    <?php foreach ($statusTabs as $val => $label): ?>
    <li class="nav-item">
        <a class="nav-link <?= $status === $val ? 'active' : '' ?>"
            href="/campaigns<?= $val !== '' ? '?status=' . urlencode($val) : '' ?>">
            <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
        </a>
    </li>
    <?php endforeach; ?>
</ul>

<div class="card border-0 shadow-sm" style="border-top-left-radius:0;">
    <div class="card-body p-0">
        <?php if (empty($campaigns)): ?>
        <div class="text-center text-muted py-5">
            <i class="bi bi-megaphone fs-1 d-block mb-2 opacity-25"></i>
            No campaigns found. <a href="/campaigns/create">Create your first campaign</a>.
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name / Subject</th>
                        <th>Status</th>
                        <th>List</th>
                        <th class="text-end">Sent</th>
                        <th class="text-end">Open Rate</th>
                        <th>Created</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($campaigns as $campaign):
                    $cStatus = $campaign['status'] ?? 'draft';
                    $cColor  = $statusColors[$cStatus] ?? 'secondary';
                    $isDraftOrScheduled = in_array($cStatus, ['draft', 'scheduled'], true);
                ?>
                <tr>
                    <td>
                        <a href="/campaigns/<?= (int)$campaign['id'] ?>" class="fw-semibold text-decoration-none text-dark">
                            <?= htmlspecialchars($campaign['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                        </a>
                        <?php if (!empty($campaign['subject'])): ?>
                        <div class="text-muted small"><?= htmlspecialchars($campaign['subject'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge bg-<?= $cColor ?>">
                            <?= htmlspecialchars(ucfirst($cStatus), ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </td>
                    <td class="text-muted small"><?= htmlspecialchars($campaign['list_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="text-end"><?= number_format((int)($campaign['sent_count'] ?? 0)) ?></td>
                    <td class="text-end">
                        <?php $openRate = (float)($campaign['open_rate'] ?? 0); ?>
                        <?= $openRate > 0 ? number_format($openRate, 1) . '%' : '—' ?>
                    </td>
                    <td class="text-muted small"><?= htmlspecialchars($campaign['created_at'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            <a href="/campaigns/<?= (int)$campaign['id'] ?>" class="btn btn-outline-secondary" title="View">
                                <i class="bi bi-eye"></i>
                            </a>
                            <?php if ($isDraftOrScheduled): ?>
                            <a href="/campaigns/<?= (int)$campaign['id'] ?>/edit" class="btn btn-outline-primary" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <?php endif; ?>
                            <form method="POST" action="/campaigns/<?= (int)$campaign['id'] ?>/duplicate" class="d-inline">
                                <?= \MailForge\Helpers\CsrfHelper::field() ?>
                                <button type="submit" class="btn btn-outline-secondary" title="Duplicate">
                                    <i class="bi bi-copy"></i>
                                </button>
                            </form>
                            <button type="button" class="btn btn-outline-danger" title="Delete"
                                data-bs-toggle="modal" data-bs-target="#deleteModal"
                                data-campaign-id="<?= (int)$campaign['id'] ?>"
                                data-campaign-name="<?= htmlspecialchars($campaign['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($lastPage > 1): ?>
        <div class="d-flex align-items-center justify-content-between px-3 py-3 border-top">
            <div class="text-muted small">Showing <?= number_format($perPage * ($page - 1) + 1) ?>–<?= number_format(min($perPage * $page, $total)) ?> of <?= number_format($total) ?></div>
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $page - 1 ?><?= $status ? '&status=' . urlencode($status) : '' ?>">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>
                    <?php for ($p = max(1, $page - 2); $p <= min($lastPage, $page + 2); $p++): ?>
                    <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $p ?><?= $status ? '&status=' . urlencode($status) : '' ?>"><?= $p ?></a>
                    </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $page >= $lastPage ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $page + 1 ?><?= $status ? '&status=' . urlencode($status) : '' ?>">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
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
                <h5 class="modal-title text-danger"><i class="bi bi-trash me-2"></i>Delete Campaign</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">Delete campaign <strong id="deleteCampaignName"></strong>? This cannot be undone.</p>
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
    document.getElementById('deleteCampaignName').textContent = btn.dataset.campaignName;
    document.getElementById('deleteForm').action = '/campaigns/' + btn.dataset.campaignId;
});
</script>
