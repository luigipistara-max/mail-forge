<?php
/** @var array $contact */
/** @var array $lists */
/** @var array $tags */
/** @var array $activityLogs */
/** @var array $customFieldValues */
$contact           = $contact           ?? [];
$lists             = $lists             ?? [];
$tags              = $tags              ?? [];
$activityLogs      = $activityLogs      ?? [];
$customFieldValues = $customFieldValues ?? [];

$statusColors = [
    'subscribed'   => 'success',
    'unsubscribed' => 'secondary',
    'bounced'      => 'danger',
    'complained'   => 'warning',
    'pending'      => 'info',
];
$status      = $contact['status'] ?? 'pending';
$badgeColor  = $statusColors[$status] ?? 'secondary';
$fullName    = trim(($contact['first_name'] ?? '') . ' ' . ($contact['last_name'] ?? ''));
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h6 class="fw-bold mb-1"><?= htmlspecialchars($contact['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></h6>
        <span class="badge bg-<?= $badgeColor ?>"><?= htmlspecialchars(ucfirst($status), ENT_QUOTES, 'UTF-8') ?></span>
        <?php if ($fullName): ?>
        <span class="text-muted small ms-2"><?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?></span>
        <?php endif; ?>
    </div>
    <div class="d-flex gap-2">
        <a href="/contacts/<?= (int)($contact['id'] ?? 0) ?>/edit" class="btn btn-primary btn-sm">
            <i class="bi bi-pencil me-1"></i>Edit
        </a>
        <button type="button" class="btn btn-outline-danger btn-sm"
            data-bs-toggle="modal" data-bs-target="#deleteModal">
            <i class="bi bi-trash me-1"></i>Delete
        </button>
    </div>
</div>

<div class="row g-4">

    <!-- Left column -->
    <div class="col-lg-8">

        <!-- Contact Info -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent fw-semibold">Contact Information</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Email</dt>
                    <dd class="col-sm-8">
                        <a href="mailto:<?= htmlspecialchars($contact['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($contact['email'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    </dd>

                    <dt class="col-sm-4">First Name</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars($contact['first_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?></dd>

                    <dt class="col-sm-4">Last Name</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars($contact['last_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?></dd>

                    <dt class="col-sm-4">Phone</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars($contact['phone'] ?? '—', ENT_QUOTES, 'UTF-8') ?></dd>

                    <dt class="col-sm-4">Company</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars($contact['company'] ?? '—', ENT_QUOTES, 'UTF-8') ?></dd>

                    <dt class="col-sm-4">Country</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars($contact['country'] ?? '—', ENT_QUOTES, 'UTF-8') ?></dd>

                    <dt class="col-sm-4">Status</dt>
                    <dd class="col-sm-8">
                        <span class="badge bg-<?= $badgeColor ?>"><?= htmlspecialchars(ucfirst($status), ENT_QUOTES, 'UTF-8') ?></span>
                    </dd>

                    <dt class="col-sm-4">Created</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars($contact['created_at'] ?? '—', ENT_QUOTES, 'UTF-8') ?></dd>

                    <dt class="col-sm-4">Last Activity</dt>
                    <dd class="col-sm-8 mb-0"><?= htmlspecialchars($contact['last_activity_at'] ?? $contact['updated_at'] ?? '—', ENT_QUOTES, 'UTF-8') ?></dd>
                </dl>
            </div>
        </div>

        <!-- Custom Field Values -->
        <?php if (!empty($customFieldValues)): ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent fw-semibold">Custom Fields</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <?php foreach ($customFieldValues as $label => $value): ?>
                    <dt class="col-sm-4"><?= htmlspecialchars(is_string($label) ? $label : ($value['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></dt>
                    <dd class="col-sm-8"><?= htmlspecialchars(is_array($value) ? ($value['value'] ?? '—') : ($value ?: '—'), ENT_QUOTES, 'UTF-8') ?></dd>
                    <?php endforeach; ?>
                </dl>
            </div>
        </div>
        <?php endif; ?>

        <!-- Activity Log -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent fw-semibold">Activity Log</div>
            <div class="card-body p-0">
                <?php if (empty($activityLogs)): ?>
                <div class="text-center text-muted py-4">
                    <i class="bi bi-clock-history fs-2 d-block mb-2 opacity-25"></i>
                    No activity recorded yet.
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Action</th>
                                <th>Description</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($activityLogs as $log): ?>
                        <tr>
                            <td class="text-muted small text-nowrap"><?= htmlspecialchars($log['created_at'] ?? $log['date'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    <?= htmlspecialchars($log['action'] ?? $log['type'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td class="text-muted small"><?= htmlspecialchars($log['description'] ?? $log['message'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="text-muted small font-monospace"><?= htmlspecialchars($log['ip_address'] ?? $log['ip'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- Right column -->
    <div class="col-lg-4">

        <!-- Lists Membership -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent fw-semibold d-flex align-items-center justify-content-between">
                <span>Lists</span>
                <span class="badge bg-primary rounded-pill"><?= count($lists) ?></span>
            </div>
            <div class="card-body">
                <?php if (empty($lists)): ?>
                <p class="text-muted small mb-0">Not subscribed to any lists.</p>
                <?php else: ?>
                <ul class="list-unstyled mb-0">
                    <?php foreach ($lists as $list): ?>
                    <?php
                    $listStatus = $list['pivot']['status'] ?? $list['status'] ?? 'subscribed';
                    $listBadge  = $statusColors[$listStatus] ?? 'secondary';
                    ?>
                    <li class="d-flex align-items-center justify-content-between py-2 border-bottom">
                        <div>
                            <a href="/lists/<?= (int)($list['id'] ?? 0) ?>" class="text-decoration-none fw-semibold small">
                                <?= htmlspecialchars($list['name'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        </div>
                        <span class="badge bg-<?= $listBadge ?>"><?= htmlspecialchars(ucfirst($listStatus), ENT_QUOTES, 'UTF-8') ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tags -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent fw-semibold">Tags</div>
            <div class="card-body">
                <?php if (empty($tags)): ?>
                <p class="text-muted small mb-0">No tags assigned.</p>
                <?php else: ?>
                <div class="d-flex flex-wrap gap-1">
                    <?php foreach ($tags as $tag): ?>
                    <span class="badge bg-secondary">
                        <?= htmlspecialchars(is_array($tag) ? ($tag['name'] ?? '') : $tag, ENT_QUOTES, 'UTF-8') ?>
                    </span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger"><i class="bi bi-trash me-2"></i>Delete Contact</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">Delete <strong><?= htmlspecialchars($contact['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong>? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" action="/contacts/<?= (int)($contact['id'] ?? 0) ?>">
                    <?= \MailForge\Helpers\CsrfHelper::field() ?>
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>
