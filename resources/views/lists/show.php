<?php
/** @var array $list */
/** @var array $contacts */
/** @var array $pagination */
$list       = $list       ?? [];
$contacts   = $contacts   ?? [];
$pagination = $pagination ?? [];

$statusColors = [
    'subscribed'   => 'success',
    'unsubscribed' => 'secondary',
    'bounced'      => 'danger',
    'complained'   => 'warning',
    'pending'      => 'info',
];
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h6 class="fw-bold mb-0"><?= htmlspecialchars($list['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></h6>
        <div class="text-muted small mt-1">
            <span class="me-3"><i class="bi bi-people me-1"></i><?= number_format((int)($list['subscribers_count'] ?? 0)) ?> subscribers</span>
            <?php if (!empty($list['double_optin'])): ?>
            <span class="badge bg-success"><i class="bi bi-shield-check me-1"></i>Double Opt-in</span>
            <?php else: ?>
            <span class="badge bg-secondary">Single Opt-in</span>
            <?php endif; ?>
        </div>
    </div>
    <a href="/lists/<?= (int)($list['id'] ?? 0) ?>/edit" class="btn btn-primary btn-sm">
        <i class="bi bi-pencil me-1"></i>Edit List
    </a>
</div>

<ul class="nav nav-tabs mb-4" id="listTabs">
    <li class="nav-item">
        <a class="nav-link active" data-bs-toggle="tab" href="#contacts-tab">
            <i class="bi bi-people me-1"></i>Contacts
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#stats-tab">
            <i class="bi bi-bar-chart me-1"></i>Stats
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#settings-tab">
            <i class="bi bi-gear me-1"></i>Settings
        </a>
    </li>
</ul>

<div class="tab-content">
    <!-- Contacts Tab -->
    <div class="tab-pane fade show active" id="contacts-tab">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <form method="GET" action="/lists/<?= (int)($list['id'] ?? 0) ?>" class="row g-2 align-items-end">
                    <div class="col-sm-6 col-md-5">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" name="search" class="form-control" placeholder="Search contacts..."
                                value="<?= htmlspecialchars($_GET['search'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary btn-sm">Search</button>
                        <a href="/lists/<?= (int)($list['id'] ?? 0) ?>" class="btn btn-outline-secondary btn-sm ms-1">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <?php if (empty($contacts)): ?>
                <div class="text-center text-muted py-5">
                    <i class="bi bi-person-x fs-1 d-block mb-2 opacity-25"></i>
                    No contacts in this list.
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Email</th>
                                <th>Name</th>
                                <th>Status</th>
                                <th>Subscribed</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($contacts as $contact): ?>
                        <?php $badge = $statusColors[$contact['status'] ?? 'pending'] ?? 'secondary'; ?>
                        <tr>
                            <td>
                                <a href="/contacts/<?= (int)$contact['id'] ?>" class="text-decoration-none fw-semibold">
                                    <?= htmlspecialchars($contact['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            </td>
                            <td><?= htmlspecialchars(trim(($contact['first_name'] ?? '') . ' ' . ($contact['last_name'] ?? '')), ENT_QUOTES, 'UTF-8') ?: '<span class="text-muted">—</span>' ?></td>
                            <td><span class="badge bg-<?= $badge ?>"><?= ucfirst(htmlspecialchars($contact['status'] ?? '', ENT_QUOTES, 'UTF-8')) ?></span></td>
                            <td class="text-muted small"><?= htmlspecialchars($contact['subscribed_at'] ?? $contact['created_at'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="text-end">
                                <form method="POST" action="/lists/<?= (int)($list['id'] ?? 0) ?>/contacts/<?= (int)$contact['id'] ?>/remove"
                                    onsubmit="return confirm('Remove this contact from the list?')">
                                    <?= \MailForge\Helpers\CsrfHelper::field() ?>
                                    <input type="hidden" name="_method" value="DELETE">
                                    <button type="submit" class="btn btn-outline-danger btn-sm" title="Remove">
                                        <i class="bi bi-person-dash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if (!empty($pagination) && ($pagination['last_page'] ?? 1) > 1): ?>
                <div class="d-flex align-items-center justify-content-between px-3 py-3 border-top">
                    <small class="text-muted">
                        Showing <?= number_format((int)($pagination['from'] ?? 0)) ?>–<?= number_format((int)($pagination['to'] ?? 0)) ?>
                        of <?= number_format((int)($pagination['total'] ?? 0)) ?> contacts
                    </small>
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <?php if (($pagination['current_page'] ?? 1) > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= (int)$pagination['current_page'] - 1 ?>"><i class="bi bi-chevron-left"></i></a>
                            </li>
                            <?php endif; ?>
                            <?php for ($p = max(1, ($pagination['current_page'] ?? 1) - 2); $p <= min(($pagination['last_page'] ?? 1), ($pagination['current_page'] ?? 1) + 2); $p++): ?>
                            <li class="page-item <?= $p === ($pagination['current_page'] ?? 1) ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $p ?>"><?= $p ?></a>
                            </li>
                            <?php endfor; ?>
                            <?php if (($pagination['current_page'] ?? 1) < ($pagination['last_page'] ?? 1)): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= (int)$pagination['current_page'] + 1 ?>"><i class="bi bi-chevron-right"></i></a>
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

    <!-- Stats Tab -->
    <div class="tab-pane fade" id="stats-tab">
        <div class="row g-3">
            <?php
            $statsItems = [
                ['label' => 'Total Subscribers', 'value' => number_format((int)($list['subscribers_count'] ?? 0)), 'icon' => 'people', 'color' => 'primary'],
                ['label' => 'Active',             'value' => number_format((int)($list['active_count'] ?? 0)),      'icon' => 'person-check', 'color' => 'success'],
                ['label' => 'Unsubscribed',       'value' => number_format((int)($list['unsubscribed_count'] ?? 0)),'icon' => 'person-dash',  'color' => 'secondary'],
                ['label' => 'Bounced',            'value' => number_format((int)($list['bounced_count'] ?? 0)),     'icon' => 'exclamation-circle', 'color' => 'danger'],
            ];
            foreach ($statsItems as $stat):
            ?>
            <div class="col-sm-6 col-lg-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-<?= $stat['color'] ?> bg-opacity-10 rounded p-2">
                                <i class="bi bi-<?= $stat['icon'] ?> fs-4 text-<?= $stat['color'] ?>"></i>
                            </div>
                            <div>
                                <div class="fs-5 fw-bold"><?= $stat['value'] ?></div>
                                <div class="text-muted small"><?= $stat['label'] ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Settings Tab -->
    <div class="tab-pane fade" id="settings-tab">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-3">Name</dt>
                    <dd class="col-sm-9"><?= htmlspecialchars($list['name'] ?? '—', ENT_QUOTES, 'UTF-8') ?></dd>
                    <dt class="col-sm-3">Description</dt>
                    <dd class="col-sm-9"><?= htmlspecialchars($list['description'] ?? '—', ENT_QUOTES, 'UTF-8') ?></dd>
                    <dt class="col-sm-3">From Name</dt>
                    <dd class="col-sm-9"><?= htmlspecialchars($list['from_name'] ?? '—', ENT_QUOTES, 'UTF-8') ?></dd>
                    <dt class="col-sm-3">From Email</dt>
                    <dd class="col-sm-9"><?= htmlspecialchars($list['from_email'] ?? '—', ENT_QUOTES, 'UTF-8') ?></dd>
                    <dt class="col-sm-3">Reply-To</dt>
                    <dd class="col-sm-9"><?= htmlspecialchars($list['reply_to'] ?? '—', ENT_QUOTES, 'UTF-8') ?></dd>
                    <dt class="col-sm-3">Double Opt-in</dt>
                    <dd class="col-sm-9"><?= !empty($list['double_optin']) ? '<span class="badge bg-success">Enabled</span>' : '<span class="badge bg-secondary">Disabled</span>' ?></dd>
                    <dt class="col-sm-3">Public</dt>
                    <dd class="col-sm-9"><?= !empty($list['is_public']) ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>' ?></dd>
                    <dt class="col-sm-3">Subscribe Page</dt>
                    <dd class="col-sm-9"><?= !empty($list['subscribe_page_enabled']) ? '<span class="badge bg-success">Enabled</span>' : '<span class="badge bg-secondary">Disabled</span>' ?></dd>
                    <dt class="col-sm-3">Created</dt>
                    <dd class="col-sm-9"><?= htmlspecialchars($list['created_at'] ?? '—', ENT_QUOTES, 'UTF-8') ?></dd>
                </dl>
            </div>
        </div>
    </div>
</div>
