<?php
/** @var array $campaign */
/** @var array $stats */
/** @var array $links */
/** @var array $recipients */
/** @var array $recipientPagination */
/** @var array $filters */
$campaign           = $campaign           ?? [];
$stats              = $stats              ?? [];
$links              = $links              ?? [];
$recipients         = $recipients         ?? [];
$recipientPagination = $recipientPagination ?? [];
$filters            = $filters            ?? [];

$sent         = (int)($stats['sent']          ?? 0);
$opens        = (int)($stats['opens']         ?? 0);
$clicks       = (int)($stats['clicks']        ?? 0);
$bounces      = (int)($stats['bounces']       ?? 0);
$unsubscribes = (int)($stats['unsubscribes']  ?? 0);

$openRate  = $sent > 0 ? round($opens  / $sent * 100, 1) : 0;
$clickRate = $sent > 0 ? round($clicks / $sent * 100, 1) : 0;

$statusFilter = $filters['status'] ?? '';
?>

<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <a href="<?= BASE_PATH ?>/reports" class="text-muted small text-decoration-none">
            <i class="bi bi-arrow-left me-1"></i>Reports
        </a>
        <h1 class="h3 mb-0 fw-bold mt-1">
            <?= htmlspecialchars($campaign['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
        </h1>
    </div>
    <a href="<?= BASE_PATH ?>/reports/campaigns/<?= (int)$campaign['id'] ?>/export" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-download me-1"></i>Export CSV
    </a>
</div>

<!-- Stats row -->
<div class="row g-3 mb-4">
    <?php
    $statCards = [
        ['label' => 'Sent',          'value' => number_format($sent),         'icon' => 'bi-send',           'color' => 'primary'],
        ['label' => 'Opens',         'value' => number_format($opens),        'icon' => 'bi-eye',            'color' => 'info'],
        ['label' => 'Clicks',        'value' => number_format($clicks),       'icon' => 'bi-cursor',         'color' => 'success'],
        ['label' => 'Bounces',       'value' => number_format($bounces),      'icon' => 'bi-envelope-dash',  'color' => 'warning'],
        ['label' => 'Unsubscribes',  'value' => number_format($unsubscribes), 'icon' => 'bi-person-dash',    'color' => 'danger'],
        ['label' => 'Open Rate',     'value' => number_format($openRate, 1) . '%',  'icon' => 'bi-eye-fill',   'color' => 'info'],
        ['label' => 'Click Rate',    'value' => number_format($clickRate, 1) . '%', 'icon' => 'bi-cursor-fill', 'color' => 'success'],
    ];
    foreach ($statCards as $card):
    ?>
    <div class="col-6 col-sm-4 col-md-3 col-lg-auto flex-lg-fill">
        <div class="card border-0 shadow-sm h-100 text-center py-3 px-2">
            <i class="bi <?= $card['icon'] ?> fs-4 text-<?= $card['color'] ?> mb-1"></i>
            <div class="h5 mb-0 fw-bold"><?= $card['value'] ?></div>
            <div class="small text-muted"><?= $card['label'] ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Top Clicked Links -->
<?php if (!empty($links)): ?>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent fw-semibold">
        <i class="bi bi-link-45deg me-2"></i>Top Clicked Links
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>URL</th>
                        <th class="text-end">Clicks</th>
                        <th class="text-end">Unique Clicks</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($links as $link): ?>
                <tr>
                    <td class="small">
                        <a href="<?= htmlspecialchars($link['url'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            target="_blank" rel="noopener noreferrer" class="text-truncate d-inline-block" style="max-width:400px;">
                            <?= htmlspecialchars($link['url'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                            <i class="bi bi-box-arrow-up-right ms-1 small"></i>
                        </a>
                    </td>
                    <td class="text-end"><?= number_format((int)($link['clicks'] ?? 0)) ?></td>
                    <td class="text-end"><?= number_format((int)($link['unique_clicks'] ?? 0)) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Recipients table -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent d-flex align-items-center justify-content-between flex-wrap gap-2">
        <span class="fw-semibold"><i class="bi bi-people me-2"></i>Recipients</span>
        <div class="d-flex gap-1 flex-wrap">
            <?php
            $statusOptions = [
                ''          => 'All',
                'sent'      => 'Sent',
                'opened'    => 'Opened',
                'clicked'   => 'Clicked',
                'bounced'   => 'Bounced',
                'failed'    => 'Failed',
            ];
            foreach ($statusOptions as $val => $label):
            ?>
            <a href="?status=<?= urlencode($val) ?>"
                class="btn btn-sm <?= $statusFilter === $val ? 'btn-primary' : 'btn-outline-secondary' ?>">
                <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="card-body p-0">
        <?php if (empty($recipients)): ?>
        <div class="text-center text-muted py-4">
            <i class="bi bi-people fs-2 d-block mb-2 opacity-25"></i>
            No recipients found for the selected filter.
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Email</th>
                        <th>Name</th>
                        <th>Status</th>
                        <th>Opened</th>
                        <th>Clicked</th>
                        <th>Sent At</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($recipients as $recipient):
                    $rStatusColors = [
                        'sent'    => 'primary',
                        'opened'  => 'info',
                        'clicked' => 'success',
                        'bounced' => 'warning',
                        'failed'  => 'danger',
                    ];
                    $rColor = $rStatusColors[$recipient['status'] ?? ''] ?? 'secondary';
                ?>
                <tr>
                    <td class="small fw-semibold">
                        <?= htmlspecialchars($recipient['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                    </td>
                    <td class="small">
                        <?= htmlspecialchars(trim(($recipient['first_name'] ?? '') . ' ' . ($recipient['last_name'] ?? '')), ENT_QUOTES, 'UTF-8') ?: '—' ?>
                    </td>
                    <td>
                        <span class="badge bg-<?= $rColor ?>">
                            <?= htmlspecialchars(ucfirst($recipient['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </td>
                    <td class="small text-muted">
                        <?= htmlspecialchars($recipient['opened_at'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                    </td>
                    <td class="small text-muted">
                        <?= htmlspecialchars($recipient['clicked_at'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                    </td>
                    <td class="small text-muted">
                        <?= htmlspecialchars($recipient['sent_at'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if (!empty($recipientPagination) && ($recipientPagination['last_page'] ?? 1) > 1): ?>
        <div class="d-flex align-items-center justify-content-between px-3 py-3 border-top">
            <small class="text-muted">
                Showing <?= number_format((int)($recipientPagination['from'] ?? 0)) ?>–<?= number_format((int)($recipientPagination['to'] ?? 0)) ?>
                of <?= number_format((int)($recipientPagination['total'] ?? 0)) ?> recipients
            </small>
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <?php if (($recipientPagination['current_page'] ?? 1) > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= (int)$recipientPagination['current_page'] - 1 ?>&<?= http_build_query(array_filter($filters)) ?>">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php for ($p = max(1, ($recipientPagination['current_page'] ?? 1) - 2); $p <= min(($recipientPagination['last_page'] ?? 1), ($recipientPagination['current_page'] ?? 1) + 2); $p++): ?>
                    <li class="page-item <?= $p === ($recipientPagination['current_page'] ?? 1) ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $p ?>&<?= http_build_query(array_filter($filters)) ?>"><?= $p ?></a>
                    </li>
                    <?php endfor; ?>
                    <?php if (($recipientPagination['current_page'] ?? 1) < ($recipientPagination['last_page'] ?? 1)): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= (int)$recipientPagination['current_page'] + 1 ?>&<?= http_build_query(array_filter($filters)) ?>">
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
