<?php
/** @var array $campaign */
/** @var array $stats */
/** @var array $links */
/** @var array $recipientsByStatus */
$campaign          = $campaign          ?? [];
$stats             = $stats             ?? [];
$links             = $links             ?? [];
$recipientsByStatus = $recipientsByStatus ?? [];

$cStatus = $campaign['status'] ?? 'draft';
$statusColors = [
    'draft'     => 'secondary',
    'scheduled' => 'info',
    'queued'    => 'warning',
    'sending'   => 'primary',
    'completed' => 'success',
    'paused'    => 'warning',
    'cancelled' => 'dark',
];
$cColor = $statusColors[$cStatus] ?? 'secondary';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div class="d-flex align-items-center gap-3">
        <div>
            <h6 class="fw-bold mb-1"><?= htmlspecialchars($campaign['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></h6>
            <span class="badge bg-<?= $cColor ?>"><?= htmlspecialchars(ucfirst($cStatus), ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <?php if ($cStatus === 'draft' || $cStatus === 'scheduled'): ?>
        <a href="<?= BASE_PATH ?>/campaigns/<?= (int)$campaign['id'] ?>/edit" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-pencil me-1"></i>Edit
        </a>
        <?php endif; ?>

        <?php if ($cStatus === 'sending'): ?>
        <form method="POST" action="<?= BASE_PATH ?>/campaigns/<?= (int)$campaign['id'] ?>/pause" class="d-inline">
            <?= \MailForge\Helpers\CsrfHelper::field() ?>
            <button type="submit" class="btn btn-outline-warning btn-sm">
                <i class="bi bi-pause-fill me-1"></i>Pause
            </button>
        </form>
        <?php endif; ?>

        <?php if ($cStatus === 'paused'): ?>
        <form method="POST" action="<?= BASE_PATH ?>/campaigns/<?= (int)$campaign['id'] ?>/queue" class="d-inline">
            <?= \MailForge\Helpers\CsrfHelper::field() ?>
            <button type="submit" class="btn btn-outline-success btn-sm">
                <i class="bi bi-play-fill me-1"></i>Resume
            </button>
        </form>
        <?php endif; ?>

        <?php if (in_array($cStatus, ['sending', 'queued', 'paused', 'scheduled'], true)): ?>
        <form method="POST" action="<?= BASE_PATH ?>/campaigns/<?= (int)$campaign['id'] ?>/cancel" class="d-inline">
            <?= \MailForge\Helpers\CsrfHelper::field() ?>
            <button type="submit" class="btn btn-outline-danger btn-sm"
                onclick="return confirm('Cancel this campaign?')">
                <i class="bi bi-x-circle me-1"></i>Cancel
            </button>
        </form>
        <?php endif; ?>

        <a href="<?= BASE_PATH ?>/campaigns/<?= (int)$campaign['id'] ?>/preview" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-eye me-1"></i>Preview
        </a>
        <a href="<?= BASE_PATH ?>/campaigns" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
    </div>
</div>

<?php if (!empty($campaign['subject'])): ?>
<p class="text-muted mb-4">
    <i class="bi bi-envelope me-1"></i>
    Subject: <?= htmlspecialchars($campaign['subject'], ENT_QUOTES, 'UTF-8') ?>
    <?php if (!empty($campaign['list_name'])): ?>
    &nbsp;·&nbsp;
    <i class="bi bi-people me-1"></i><?= htmlspecialchars($campaign['list_name'], ENT_QUOTES, 'UTF-8') ?>
    <?php endif; ?>
</p>
<?php endif; ?>

<!-- Stats Cards -->
<div class="row g-3 mb-4">
    <?php
    $statCards = [
        ['label' => 'Sent',         'key' => 'sent_count',        'icon' => 'bi-send-fill',            'color' => 'primary'],
        ['label' => 'Opened',       'key' => 'open_count',        'icon' => 'bi-envelope-open-fill',   'color' => 'info'],
        ['label' => 'Clicked',      'key' => 'click_count',       'icon' => 'bi-cursor-fill',          'color' => 'success'],
        ['label' => 'Bounced',      'key' => 'bounce_count',      'icon' => 'bi-envelope-x-fill',      'color' => 'warning'],
        ['label' => 'Unsubscribed', 'key' => 'unsubscribe_count', 'icon' => 'bi-person-dash-fill',     'color' => 'danger'],
        ['label' => 'Failed',       'key' => 'failed_count',      'icon' => 'bi-exclamation-triangle-fill', 'color' => 'dark'],
    ];
    foreach ($statCards as $card):
    ?>
    <div class="col-sm-6 col-xl-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 bg-<?= $card['color'] ?> bg-opacity-10 p-3">
                    <i class="bi <?= $card['icon'] ?> fs-5 text-<?= $card['color'] ?>"></i>
                </div>
                <div>
                    <div class="text-muted small"><?= $card['label'] ?></div>
                    <div class="fs-5 fw-bold"><?= number_format((int)($stats[$card['key']] ?? 0)) ?></div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Rate Cards -->
<?php
$sentCount = (int)($stats['sent_count'] ?? 0);
$openRate  = $sentCount > 0 ? round((int)($stats['open_count'] ?? 0) / $sentCount * 100, 1) : 0;
$clickRate = $sentCount > 0 ? round((int)($stats['click_count'] ?? 0) / $sentCount * 100, 1) : 0;
?>
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-md-3">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body">
                <div class="text-muted small mb-1">Open Rate</div>
                <div class="fs-3 fw-bold text-info"><?= number_format($openRate, 1) ?>%</div>
                <div class="progress mt-2" style="height:4px;">
                    <div class="progress-bar bg-info" style="width:<?= min($openRate, 100) ?>%"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-md-3">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body">
                <div class="text-muted small mb-1">Click Rate</div>
                <div class="fs-3 fw-bold text-success"><?= number_format($clickRate, 1) ?>%</div>
                <div class="progress mt-2" style="height:4px;">
                    <div class="progress-bar bg-success" style="width:<?= min($clickRate, 100) ?>%"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Top Clicked Links -->
<?php if (!empty($links)): ?>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent fw-semibold">
        <i class="bi bi-link-45deg me-2 text-primary"></i>Top Clicked Links
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
                    <td class="text-muted small" style="max-width:400px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                        <a href="<?= htmlspecialchars($link['url'] ?? '', ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="text-decoration-none">
                            <?= htmlspecialchars($link['url'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    </td>
                    <td class="text-end"><?= number_format((int)($link['click_count'] ?? 0)) ?></td>
                    <td class="text-end"><?= number_format((int)($link['unique_clicks'] ?? 0)) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Recipients by Status -->
<?php if (!empty($recipientsByStatus)): ?>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent fw-semibold">
        <i class="bi bi-people me-2 text-secondary"></i>Recipients by Status
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Status</th>
                        <th class="text-end">Count</th>
                        <th class="text-end">Percentage</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $recipientTotal = array_sum(array_column($recipientsByStatus, 'count'));
                $recipientStatusColors = [
                    'sent'        => 'primary',
                    'opened'      => 'info',
                    'clicked'     => 'success',
                    'bounced'     => 'warning',
                    'unsubscribed'=> 'danger',
                    'failed'      => 'dark',
                    'pending'     => 'secondary',
                ];
                foreach ($recipientsByStatus as $row):
                    $rColor = $recipientStatusColors[$row['status'] ?? ''] ?? 'secondary';
                    $pct = $recipientTotal > 0 ? round((int)($row['count'] ?? 0) / $recipientTotal * 100, 1) : 0;
                ?>
                <tr>
                    <td>
                        <span class="badge bg-<?= $rColor ?>">
                            <?= htmlspecialchars(ucfirst($row['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </td>
                    <td class="text-end"><?= number_format((int)($row['count'] ?? 0)) ?></td>
                    <td class="text-end"><?= number_format($pct, 1) ?>%</td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>
