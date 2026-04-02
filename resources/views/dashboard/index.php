<?php
/** @var array $stats */
/** @var array $recentCampaigns */
/** @var array $recentActivity */
$stats = $stats ?? [];
$recentCampaigns = $recentCampaigns ?? [];
$recentActivity = $recentActivity ?? [];

function campaignStatusBadge(string $status): string {
    $map = [
        'draft'     => 'secondary',
        'scheduled' => 'info',
        'queued'    => 'warning',
        'sending'   => 'primary',
        'completed' => 'success',
        'failed'    => 'danger',
        'paused'    => 'warning',
        'cancelled' => 'dark',
    ];
    $color = $map[$status] ?? 'secondary';
    return '<span class="badge bg-' . $color . '">' . htmlspecialchars(ucfirst($status), ENT_QUOTES, 'UTF-8') . '</span>';
}
?>

<div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 bg-primary bg-opacity-10 p-3">
                    <i class="bi bi-people-fill fs-4 text-primary"></i>
                </div>
                <div>
                    <div class="text-muted small">Total Contacts</div>
                    <div class="fs-4 fw-bold"><?= number_format((int)($stats['total_contacts'] ?? 0)) ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 bg-success bg-opacity-10 p-3">
                    <i class="bi bi-person-check-fill fs-4 text-success"></i>
                </div>
                <div>
                    <div class="text-muted small">Active Subscribers</div>
                    <div class="fs-4 fw-bold"><?= number_format((int)($stats['active_subscribers'] ?? 0)) ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 bg-info bg-opacity-10 p-3">
                    <i class="bi bi-megaphone-fill fs-4 text-info"></i>
                </div>
                <div>
                    <div class="text-muted small">Campaigns Sent (Month)</div>
                    <div class="fs-4 fw-bold"><?= number_format((int)($stats['campaigns_sent_month'] ?? 0)) ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 bg-warning bg-opacity-10 p-3">
                    <i class="bi bi-envelope-open-fill fs-4 text-warning"></i>
                </div>
                <div>
                    <div class="text-muted small">Avg Open Rate</div>
                    <div class="fs-4 fw-bold"><?= number_format((float)($stats['avg_open_rate'] ?? 0), 1) ?>%</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h6 class="fw-bold mb-0">Quick Actions</h6>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="/campaigns/create" class="btn btn-primary">
                <i class="bi bi-megaphone me-2"></i>New Campaign
            </a>
            <a href="/contacts/import" class="btn btn-outline-secondary">
                <i class="bi bi-upload me-2"></i>Import Contacts
            </a>
            <a href="/templates/create" class="btn btn-outline-secondary">
                <i class="bi bi-file-earmark-plus me-2"></i>New Template
            </a>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex align-items-center justify-content-between py-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-megaphone me-2 text-primary"></i>Recent Campaigns</h6>
                <a href="/campaigns" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recentCampaigns)): ?>
                <div class="text-center text-muted py-5">
                    <i class="bi bi-megaphone fs-1 d-block mb-2 opacity-25"></i>
                    No campaigns yet. <a href="/campaigns/create">Create your first campaign</a>.
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Campaign</th>
                                <th>Status</th>
                                <th class="text-end">Sent</th>
                                <th class="text-end">Open Rate</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($recentCampaigns as $campaign): ?>
                            <tr>
                                <td>
                                    <a href="/campaigns/<?= (int)($campaign['id'] ?? 0) ?>" class="fw-semibold text-decoration-none text-dark">
                                        <?= htmlspecialchars($campaign['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                    <?php if (!empty($campaign['subject'])): ?>
                                    <div class="text-muted small"><?= htmlspecialchars($campaign['subject'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><?= campaignStatusBadge($campaign['status'] ?? 'draft') ?></td>
                                <td class="text-end"><?= number_format((int)($campaign['sent_count'] ?? 0)) ?></td>
                                <td class="text-end"><?= number_format((float)($campaign['open_rate'] ?? 0), 1) ?>%</td>
                                <td class="text-muted small"><?= htmlspecialchars($campaign['sent_at'] ?? $campaign['created_at'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white d-flex align-items-center justify-content-between py-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-clock-history me-2 text-secondary"></i>Recent Activity</h6>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recentActivity)): ?>
                <div class="text-center text-muted py-5">
                    <i class="bi bi-clock-history fs-1 d-block mb-2 opacity-25"></i>
                    No recent activity.
                </div>
                <?php else: ?>
                <ul class="list-group list-group-flush">
                <?php foreach ($recentActivity as $activity): ?>
                    <li class="list-group-item px-3 py-2">
                        <div class="d-flex align-items-start gap-2">
                            <i class="bi bi-circle-fill text-primary mt-1" style="font-size:.45rem;"></i>
                            <div class="flex-grow-1 overflow-hidden">
                                <div class="small fw-semibold text-truncate"><?= htmlspecialchars($activity['action'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="text-muted" style="font-size:.78rem;"><?= htmlspecialchars($activity['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="d-flex justify-content-between mt-1">
                                    <span class="text-muted" style="font-size:.72rem;"><?= htmlspecialchars($activity['user'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                                    <span class="text-muted" style="font-size:.72rem;"><?= htmlspecialchars($activity['date'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
