<?php
/** @var array $monthlyGrowth */
/** @var array $stats */
$monthlyGrowth = $monthlyGrowth ?? [];
$stats         = $stats         ?? [];

$total        = (int)($stats['total']        ?? 0);
$subscribed   = (int)($stats['subscribed']   ?? 0);
$unsubscribed = (int)($stats['unsubscribed'] ?? 0);
$bounced      = (int)($stats['bounced']      ?? 0);
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <a href="/reports" class="text-muted small text-decoration-none">
            <i class="bi bi-arrow-left me-1"></i>Reports
        </a>
        <h1 class="h3 mb-0 fw-bold mt-1">Contact Growth</h1>
    </div>
</div>

<!-- Summary cards -->
<div class="row g-3 mb-4">
    <?php
    $cards = [
        ['label' => 'Total Contacts',  'value' => number_format($total),        'icon' => 'bi-people',       'color' => 'primary'],
        ['label' => 'Subscribed',      'value' => number_format($subscribed),    'icon' => 'bi-check-circle', 'color' => 'success'],
        ['label' => 'Unsubscribed',    'value' => number_format($unsubscribed),  'icon' => 'bi-person-dash',  'color' => 'secondary'],
        ['label' => 'Bounced',         'value' => number_format($bounced),       'icon' => 'bi-envelope-dash','color' => 'warning'],
    ];
    foreach ($cards as $card):
    ?>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100 text-center py-3 px-2">
            <i class="bi <?= $card['icon'] ?> fs-4 text-<?= $card['color'] ?> mb-1"></i>
            <div class="h5 mb-0 fw-bold"><?= $card['value'] ?></div>
            <div class="small text-muted"><?= $card['label'] ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Monthly growth table -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent fw-semibold">
        <i class="bi bi-graph-up me-2"></i>Monthly Contact Growth (Last 24 Months)
    </div>
    <div class="card-body p-0">
        <?php if (empty($monthlyGrowth)): ?>
        <div class="text-center text-muted py-5">
            <i class="bi bi-graph-up fs-1 d-block mb-2 opacity-25"></i>
            No data available yet.
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Month</th>
                        <th class="text-end">New Contacts</th>
                        <th class="text-end">Subscribed</th>
                        <th class="text-end">Unsubscribed</th>
                        <th class="text-end">Bounced</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach (array_reverse($monthlyGrowth) as $row): ?>
                <tr>
                    <td class="fw-semibold"><?= htmlspecialchars($row['month'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="text-end"><?= number_format((int)($row['new_contacts'] ?? 0)) ?></td>
                    <td class="text-end text-success"><?= number_format((int)($row['subscribed'] ?? 0)) ?></td>
                    <td class="text-end text-secondary"><?= number_format((int)($row['unsubscribed'] ?? 0)) ?></td>
                    <td class="text-end text-warning"><?= number_format((int)($row['bounced'] ?? 0)) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
