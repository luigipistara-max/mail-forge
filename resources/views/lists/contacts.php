<?php
/** @var array $list */
/** @var array $contacts */
/** @var int $total */
/** @var int $page */
/** @var int $perPage */
/** @var int $lastPage */
/** @var string $search */
$list     = $list     ?? [];
$contacts = $contacts ?? [];
$total    = $total    ?? 0;
$page     = $page     ?? 1;
$perPage  = $perPage  ?? 25;
$lastPage = $lastPage ?? 1;
$search   = $search   ?? '';
?>

<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <a href="/lists/<?= (int)$list['id'] ?>" class="text-muted small text-decoration-none">
            <i class="bi bi-arrow-left me-1"></i><?= htmlspecialchars($list['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
        </a>
        <h1 class="h3 mb-0 fw-bold mt-1">Contacts in List</h1>
    </div>
    <span class="badge bg-primary fs-6"><?= number_format($total) ?> contacts</span>
</div>

<!-- Search -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-2">
        <form method="GET" class="d-flex gap-2">
            <input type="text" name="search" class="form-control form-control-sm" style="max-width:300px"
                placeholder="Search email or name…"
                value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
            <button class="btn btn-sm btn-primary" type="submit"><i class="bi bi-search"></i></button>
            <?php if ($search !== ''): ?>
            <a href="?" class="btn btn-sm btn-outline-secondary">Clear</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?php if (empty($contacts)): ?>
        <div class="text-center text-muted py-5">
            <i class="bi bi-people fs-1 d-block mb-2 opacity-25"></i>
            No contacts in this list yet.
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
                <tr>
                    <td>
                        <a href="/contacts/<?= (int)$contact['id'] ?>" class="text-decoration-none">
                            <?= htmlspecialchars($contact['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    </td>
                    <td><?= htmlspecialchars(trim(($contact['first_name'] ?? '') . ' ' . ($contact['last_name'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <?php
                        $cs = $contact['status'] ?? '';
                        $cc = match ($cs) { 'subscribed' => 'success', 'unsubscribed' => 'secondary', 'bounced' => 'warning', 'complained' => 'danger', default => 'light' };
                        ?>
                        <span class="badge bg-<?= $cc ?>"><?= htmlspecialchars(ucfirst($cs), ENT_QUOTES, 'UTF-8') ?></span>
                    </td>
                    <td class="small text-muted"><?= htmlspecialchars($contact['subscribed_at'] ?? $contact['created_at'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="text-end">
                        <form method="POST" action="/lists/<?= (int)$list['id'] ?>/contacts/<?= (int)$contact['id'] ?>/remove"
                            onsubmit="return confirm('Remove this contact from the list?')">
                            <?= \MailForge\Helpers\CsrfHelper::field() ?>
                            <button type="submit" class="btn btn-outline-danger btn-sm" title="Remove from list">
                                <i class="bi bi-person-dash"></i>
                            </button>
                        </form>
                    </td>
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
                        <a class="page-link" href="?page=<?= $p ?><?= $search !== '' ? '&search=' . urlencode($search) : '' ?>"><?= $p ?></a>
                    </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
