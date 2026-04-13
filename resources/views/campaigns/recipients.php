<?php
/** @var array $campaign */
/** @var array $recipients */
/** @var int $total */
/** @var int $page */
/** @var int $perPage */
/** @var int $lastPage */
/** @var string $status */
$campaign   = $campaign   ?? [];
$recipients = $recipients ?? [];
$total      = $total      ?? 0;
$page       = $page       ?? 1;
$perPage    = $perPage    ?? 50;
$lastPage   = $lastPage   ?? 1;
$status     = $status     ?? '';
?>

<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <a href="<?= BASE_PATH ?>/campaigns/<?= (int)$campaign['id'] ?>" class="text-muted small text-decoration-none">
            <i class="bi bi-arrow-left me-1"></i><?= htmlspecialchars($campaign['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
        </a>
        <h1 class="h3 mb-0 fw-bold mt-1">Recipients</h1>
    </div>
</div>

<!-- Filter bar -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-2">
        <form method="GET" class="d-flex align-items-center gap-3 flex-wrap">
            <label class="fw-semibold small mb-0">Filter by status:</label>
            <?php
            $statuses = ['' => 'All', 'sent' => 'Sent', 'opened' => 'Opened', 'clicked' => 'Clicked', 'bounced' => 'Bounced', 'unsubscribed' => 'Unsubscribed', 'failed' => 'Failed'];
            foreach ($statuses as $val => $label):
            ?>
            <div class="form-check form-check-inline mb-0">
                <input class="form-check-input" type="radio" name="status" id="s_<?= $val ?: 'all' ?>"
                    value="<?= htmlspecialchars($val, ENT_QUOTES, 'UTF-8') ?>"
                    <?= $status === $val ? 'checked' : '' ?>
                    onchange="this.form.submit()">
                <label class="form-check-label" for="s_<?= $val ?: 'all' ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></label>
            </div>
            <?php endforeach; ?>
            <span class="ms-auto text-muted small"><?= number_format($total) ?> total</span>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?php if (empty($recipients)): ?>
        <div class="text-center text-muted py-5">
            <i class="bi bi-people fs-1 d-block mb-2 opacity-25"></i>
            No recipients found.
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Email</th>
                        <th>Name</th>
                        <th>Status</th>
                        <th>Sent</th>
                        <th>Opened</th>
                        <th>Clicked</th>
                        <th class="text-end">Opens</th>
                        <th class="text-end">Clicks</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($recipients as $r):
                    $rStatus = $r['status'] ?? '';
                    $rColor  = match ($rStatus) {
                        'sent'          => 'primary',
                        'opened'        => 'info',
                        'clicked'       => 'success',
                        'bounced'       => 'warning',
                        'unsubscribed'  => 'secondary',
                        'failed'        => 'danger',
                        default         => 'light',
                    };
                ?>
                <tr>
                    <td><?= htmlspecialchars($r['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars(trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><span class="badge bg-<?= $rColor ?>"><?= htmlspecialchars(ucfirst($rStatus), ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td class="small text-muted"><?= htmlspecialchars($r['sent_at'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="small text-muted"><?= htmlspecialchars($r['opened_at'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="small text-muted"><?= htmlspecialchars($r['clicked_at'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="text-end"><?= (int)($r['open_count'] ?? 0) ?></td>
                    <td class="text-end"><?= (int)($r['click_count'] ?? 0) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($lastPage > 1): ?>
        <div class="d-flex justify-content-center py-3">
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <?php for ($p = 1; $p <= $lastPage; $p++): ?>
                    <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $p ?><?= $status !== '' ? '&status=' . urlencode($status) : '' ?>"><?= $p ?></a>
                    </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
