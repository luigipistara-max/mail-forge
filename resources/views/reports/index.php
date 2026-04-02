<?php
/** @var array $globalStats */
/** @var array $topCampaigns */
/** @var array $dailyStats */
$globalStats  = $globalStats  ?? [];
$topCampaigns = $topCampaigns ?? [];
$dailyStats   = $dailyStats   ?? [];

$sent       = (int)($globalStats['sent']          ?? 0);
$delivered  = (int)($globalStats['delivered']     ?? 0);
$failed     = (int)($globalStats['failed']        ?? 0);
$bounced    = (int)($globalStats['bounced']       ?? 0);
$uniqOpens  = (int)($globalStats['unique_opens']  ?? 0);
$uniqClicks = (int)($globalStats['unique_clicks'] ?? 0);

$openRate  = $sent > 0 ? round($uniqOpens  / $sent * 100, 1) : 0;
$clickRate = $sent > 0 ? round($uniqClicks / $sent * 100, 1) : 0;
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 fw-bold">Reports</h1>
    <a href="/reports/export" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-download me-1"></i>Export CSV
    </a>
</div>

<!-- Stat cards -->
<div class="row g-3 mb-4">
    <?php
    $statCards = [
        ['label' => 'Total Sent',     'value' => $sent,       'icon' => 'bi-send',            'color' => 'primary'],
        ['label' => 'Delivered',       'value' => $delivered,  'icon' => 'bi-envelope-check',  'color' => 'success'],
        ['label' => 'Failed',          'value' => $failed,     'icon' => 'bi-envelope-x',      'color' => 'danger'],
        ['label' => 'Bounced',         'value' => $bounced,    'icon' => 'bi-envelope-dash',   'color' => 'warning'],
        ['label' => 'Unique Opens',    'value' => $uniqOpens,  'icon' => 'bi-eye',             'color' => 'info'],
        ['label' => 'Unique Clicks',   'value' => $uniqClicks, 'icon' => 'bi-cursor',          'color' => 'secondary'],
    ];
    foreach ($statCards as $card):
    ?>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card border-0 shadow-sm h-100 text-center py-3">
            <i class="bi <?= $card['icon'] ?> fs-3 text-<?= $card['color'] ?> mb-1"></i>
            <div class="h4 mb-0 fw-bold"><?= number_format($card['value']) ?></div>
            <div class="small text-muted"><?= $card['label'] ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Rate cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <i class="bi bi-eye-fill fs-3 text-info mb-1"></i>
            <div class="h3 mb-0 fw-bold"><?= number_format($openRate, 1) ?>%</div>
            <div class="small text-muted">Open Rate</div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <i class="bi bi-cursor-fill fs-3 text-success mb-1"></i>
            <div class="h3 mb-0 fw-bold"><?= number_format($clickRate, 1) ?>%</div>
            <div class="small text-muted">Click Rate</div>
        </div>
    </div>
</div>

<!-- Line chart -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent fw-semibold">
        <i class="bi bi-graph-up me-2"></i>Sends Over Time
    </div>
    <div class="card-body">
        <canvas id="dailyChart" height="80"></canvas>
    </div>
</div>

<!-- Top Campaigns -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent fw-semibold">
        <i class="bi bi-trophy me-2"></i>Top Campaigns
    </div>
    <div class="card-body p-0">
        <?php if (empty($topCampaigns)): ?>
        <div class="text-center text-muted py-4">
            <i class="bi bi-megaphone fs-2 d-block mb-2 opacity-25"></i>
            No campaign data available.
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Campaign</th>
                        <th class="text-end">Sent</th>
                        <th class="text-end">Opens</th>
                        <th class="text-end">Open Rate</th>
                        <th class="text-end">Clicks</th>
                        <th class="text-end">Click Rate</th>
                        <th class="text-end">Report</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($topCampaigns as $campaign):
                    $cSent   = (int)($campaign['sent']          ?? 0);
                    $cOpens  = (int)($campaign['opens']         ?? 0);
                    $cClicks = (int)($campaign['clicks']        ?? 0);
                    $cOpenRate  = $cSent > 0 ? round($cOpens  / $cSent * 100, 1) : 0;
                    $cClickRate = $cSent > 0 ? round($cClicks / $cSent * 100, 1) : 0;
                ?>
                <tr>
                    <td class="fw-semibold">
                        <?= htmlspecialchars($campaign['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                    </td>
                    <td class="text-end"><?= number_format($cSent) ?></td>
                    <td class="text-end"><?= number_format($cOpens) ?></td>
                    <td class="text-end">
                        <span class="badge bg-info text-dark"><?= number_format($cOpenRate, 1) ?>%</span>
                    </td>
                    <td class="text-end"><?= number_format($cClicks) ?></td>
                    <td class="text-end">
                        <span class="badge bg-success"><?= number_format($cClickRate, 1) ?>%</span>
                    </td>
                    <td class="text-end">
                        <a href="/reports/campaign/<?= (int)$campaign['id'] ?>" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-bar-chart"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {
    const labels = <?= json_encode(array_column($dailyStats, 'date')) ?>;
    const sent   = <?= json_encode(array_map('intval', array_column($dailyStats, 'sent'))) ?>;
    const opens  = <?= json_encode(array_map('intval', array_column($dailyStats, 'opens'))) ?>;
    const clicks = <?= json_encode(array_map('intval', array_column($dailyStats, 'clicks'))) ?>;

    new Chart(document.getElementById('dailyChart'), {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Sent',
                    data: sent,
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13,110,253,.08)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: 3,
                },
                {
                    label: 'Opens',
                    data: opens,
                    borderColor: '#0dcaf0',
                    backgroundColor: 'rgba(13,202,240,.08)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: 3,
                },
                {
                    label: 'Clicks',
                    data: clicks,
                    borderColor: '#198754',
                    backgroundColor: 'rgba(25,135,84,.08)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: 3,
                },
            ],
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'top' },
            },
            scales: {
                x: { grid: { display: false } },
                y: { beginAtZero: true },
            },
        },
    });
})();
</script>
