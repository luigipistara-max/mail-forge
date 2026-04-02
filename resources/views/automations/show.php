<?php
/** @var array $automation */
/** @var array $steps */
/** @var array $runs */
/** @var array $runsPagination */
$automation     = $automation     ?? [];
$steps          = $steps          ?? [];
$runs           = $runs           ?? [];
$runsPagination = $runsPagination ?? [];

$isActive = ($automation['status'] ?? '') === 'active';
?>

<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div class="d-flex align-items-center gap-2">
        <h1 class="h3 mb-0 fw-bold">
            <?= htmlspecialchars($automation['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
        </h1>
        <?php if ($isActive): ?>
        <span class="badge bg-success"><i class="bi bi-check-lg me-1"></i>Active</span>
        <?php else: ?>
        <span class="badge bg-secondary"><i class="bi bi-pause me-1"></i>Inactive</span>
        <?php endif; ?>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <!-- Activate / Deactivate toggle -->
        <form method="POST" action="/automations/<?= (int)$automation['id'] ?>/toggle">
            <?= \MailForge\Helpers\CsrfHelper::field() ?>
            <input type="hidden" name="_method" value="PUT">
            <?php if ($isActive): ?>
            <button type="submit" class="btn btn-outline-warning btn-sm">
                <i class="bi bi-pause me-1"></i>Deactivate
            </button>
            <?php else: ?>
            <button type="submit" class="btn btn-outline-success btn-sm">
                <i class="bi bi-play me-1"></i>Activate
            </button>
            <?php endif; ?>
        </form>
        <a href="/automations/<?= (int)$automation['id'] ?>/edit" class="btn btn-primary btn-sm">
            <i class="bi bi-pencil me-1"></i>Edit
        </a>
        <a href="/automations" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
    </div>
</div>

<!-- Summary row -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-md-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="h4 mb-0 fw-bold"><?= number_format((int)($automation['total_runs'] ?? 0)) ?></div>
            <div class="small text-muted">Total Runs</div>
        </div>
    </div>
    <div class="col-sm-6 col-md-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="h4 mb-0 fw-bold"><?= count($steps) ?></div>
            <div class="small text-muted">Steps</div>
        </div>
    </div>
    <div class="col-sm-6 col-md-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <?php
            $triggerLabels = [
                'list_subscribe'   => 'List Subscribe',
                'list_unsubscribe' => 'List Unsubscribe',
                'date_based'       => 'Date Based',
                'manual'           => 'Manual',
            ];
            $trigger = $automation['trigger_type'] ?? '';
            ?>
            <div class="h6 mb-0 fw-bold">
                <?= htmlspecialchars($triggerLabels[$trigger] ?? ucfirst(str_replace('_', ' ', $trigger)), ENT_QUOTES, 'UTF-8') ?>
            </div>
            <div class="small text-muted">Trigger</div>
        </div>
    </div>
    <div class="col-sm-6 col-md-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="h6 mb-0 fw-bold">
                <?= !empty($automation['last_run_at']) ? htmlspecialchars($automation['last_run_at'], ENT_QUOTES, 'UTF-8') : '—' ?>
            </div>
            <div class="small text-muted">Last Run</div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Steps -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent fw-semibold">
                <i class="bi bi-diagram-3 me-2"></i>Steps
            </div>
            <div class="card-body">
                <?php if (empty($steps)): ?>
                <p class="text-muted mb-0 text-center py-3">No steps configured.</p>
                <?php else: ?>
                <ol class="list-group list-group-flush" style="padding-left:0; list-style:none;">
                    <?php
                    $stepTypeLabels = [
                        'send_email'  => ['label' => 'Send Email',  'icon' => 'bi-envelope',      'color' => 'primary'],
                        'wait'        => ['label' => 'Wait',        'icon' => 'bi-clock',         'color' => 'warning'],
                        'condition'   => ['label' => 'Condition',   'icon' => 'bi-signpost-split', 'color' => 'info'],
                        'tag_add'     => ['label' => 'Add Tag',     'icon' => 'bi-tag',           'color' => 'success'],
                        'tag_remove'  => ['label' => 'Remove Tag',  'icon' => 'bi-tag-fill',      'color' => 'secondary'],
                    ];
                    foreach ($steps as $stepNum => $step):
                        $st = $stepTypeLabels[$step['type'] ?? ''] ?? ['label' => ucfirst($step['type'] ?? ''), 'icon' => 'bi-gear', 'color' => 'secondary'];
                        $config = $step['config'] ?? [];
                        if (is_string($config)) {
                            $config = json_decode($config, true) ?? [];
                        }
                        $summary = [];
                        foreach (array_slice($config, 0, 3) as $k => $v) {
                            $summary[] = '<strong>' . htmlspecialchars($k, ENT_QUOTES, 'UTF-8') . '</strong>: ' . htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
                        }
                    ?>
                    <li class="list-group-item px-0">
                        <div class="d-flex align-items-start gap-3">
                            <span class="badge bg-<?= $st['color'] ?> rounded-circle p-2 fs-6 mt-1" style="min-width:2rem;text-align:center;">
                                <?= $stepNum + 1 ?>
                            </span>
                            <div>
                                <div class="fw-semibold">
                                    <i class="bi <?= $st['icon'] ?> me-1"></i><?= $st['label'] ?>
                                </div>
                                <?php if (!empty($summary)): ?>
                                <div class="small text-muted mt-1"><?= implode(' &middot; ', $summary) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ol>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Recent Runs -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent fw-semibold">
                <i class="bi bi-clock-history me-2"></i>Recent Runs
            </div>
            <div class="card-body p-0">
                <?php if (empty($runs)): ?>
                <div class="text-center text-muted py-4">
                    <i class="bi bi-inbox fs-2 d-block mb-2 opacity-25"></i>
                    No runs yet.
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Contact</th>
                                <th>Status</th>
                                <th>Started</th>
                                <th>Completed</th>
                                <th>Step</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($runs as $run):
                            $runStatusColors = [
                                'completed' => 'success',
                                'running'   => 'primary',
                                'failed'    => 'danger',
                                'pending'   => 'secondary',
                                'paused'    => 'warning',
                            ];
                            $runColor = $runStatusColors[$run['status'] ?? ''] ?? 'secondary';
                        ?>
                        <tr>
                            <td>
                                <span class="fw-semibold small">
                                    <?= htmlspecialchars($run['contact_email'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?= $runColor ?>">
                                    <?= htmlspecialchars(ucfirst($run['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td class="small text-muted">
                                <?= htmlspecialchars($run['started_at'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td class="small text-muted">
                                <?= htmlspecialchars($run['completed_at'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td class="small text-muted">
                                <?= htmlspecialchars((string)($run['current_step'] ?? '—'), ENT_QUOTES, 'UTF-8') ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if (!empty($runsPagination) && ($runsPagination['last_page'] ?? 1) > 1): ?>
                <div class="d-flex align-items-center justify-content-between px-3 py-3 border-top">
                    <small class="text-muted">
                        Showing <?= number_format((int)($runsPagination['from'] ?? 0)) ?>–<?= number_format((int)($runsPagination['to'] ?? 0)) ?>
                        of <?= number_format((int)($runsPagination['total'] ?? 0)) ?> runs
                    </small>
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <?php if (($runsPagination['current_page'] ?? 1) > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= (int)$runsPagination['current_page'] - 1 ?>">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>
                            <?php endif; ?>
                            <?php for ($p = max(1, ($runsPagination['current_page'] ?? 1) - 2); $p <= min(($runsPagination['last_page'] ?? 1), ($runsPagination['current_page'] ?? 1) + 2); $p++): ?>
                            <li class="page-item <?= $p === ($runsPagination['current_page'] ?? 1) ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $p ?>"><?= $p ?></a>
                            </li>
                            <?php endfor; ?>
                            <?php if (($runsPagination['current_page'] ?? 1) < ($runsPagination['last_page'] ?? 1)): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= (int)$runsPagination['current_page'] + 1 ?>">
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
    </div>
</div>
