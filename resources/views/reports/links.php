<?php
/** @var array $campaign */
/** @var array $links */
/** @var array $clickDetails */
$campaign     = $campaign     ?? [];
$links        = $links        ?? [];
$clickDetails = $clickDetails ?? [];
?>

<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <a href="<?= BASE_PATH ?>/reports/campaigns/<?= (int)$campaign['id'] ?>" class="text-muted small text-decoration-none">
            <i class="bi bi-arrow-left me-1"></i><?= htmlspecialchars($campaign['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
        </a>
        <h1 class="h3 mb-0 fw-bold mt-1">Link Click Report</h1>
    </div>
</div>

<?php if (empty($links)): ?>
<div class="card border-0 shadow-sm">
    <div class="text-center text-muted py-5">
        <i class="bi bi-link-45deg fs-1 d-block mb-2 opacity-25"></i>
        No tracked links found for this campaign.
    </div>
</div>
<?php else: ?>

<?php foreach ($links as $link):
    $linkId     = (int)$link['id'];
    $clicks     = (int)($link['click_count'] ?? 0);
    $details    = $clickDetails[$linkId] ?? [];
?>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent d-flex align-items-center justify-content-between gap-2 flex-wrap">
        <div class="d-flex align-items-center gap-2 overflow-hidden">
            <i class="bi bi-link-45deg text-primary flex-shrink-0"></i>
            <span class="text-truncate" title="<?= htmlspecialchars($link['original_url'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars($link['original_url'] ?? '', ENT_QUOTES, 'UTF-8') ?>
            </span>
        </div>
        <span class="badge bg-primary flex-shrink-0"><?= number_format($clicks) ?> clicks</span>
    </div>

    <?php if (!empty($details)): ?>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Email</th>
                        <th>Name</th>
                        <th>Clicked At</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($details as $d): ?>
                <tr>
                    <td><?= htmlspecialchars($d['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars(trim(($d['first_name'] ?? '') . ' ' . ($d['last_name'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="small text-muted"><?= htmlspecialchars($d['clicked_at'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php endforeach; ?>
<?php endif; ?>
