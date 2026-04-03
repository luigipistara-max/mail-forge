<?php
/** @var array $servers */
$servers = $servers ?? [];
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 fw-bold">SMTP Servers</h1>
    <a href="/smtp-servers/create" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>Add Server
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <?php if (empty($servers)): ?>
        <div class="text-center text-muted py-5">
            <i class="bi bi-envelope-at fs-1 d-block mb-2 opacity-25"></i>
            No SMTP servers yet. <a href="/smtp-servers/create">Add your first server</a>.
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Host : Port</th>
                        <th>Encryption</th>
                        <th>From Email</th>
                        <th>Active</th>
                        <th class="text-end">Priority</th>
                        <th class="text-end">Sent/Hour</th>
                        <th class="text-end">Sent/Day</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($servers as $server): ?>
                <tr>
                    <td class="fw-semibold">
                        <?= htmlspecialchars($server['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                    </td>
                    <td class="font-monospace small">
                        <?= htmlspecialchars($server['host'] ?? '', ENT_QUOTES, 'UTF-8') ?>:<strong><?= (int)($server['port'] ?? 587) ?></strong>
                    </td>
                    <td>
                        <?php
                        $encColors = ['tls' => 'primary', 'ssl' => 'success', 'none' => 'secondary'];
                        $enc = strtolower($server['encryption'] ?? 'none');
                        ?>
                        <span class="badge bg-<?= $encColors[$enc] ?? 'secondary' ?> text-uppercase">
                            <?= htmlspecialchars($enc ?: 'none', ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </td>
                    <td class="small">
                        <?= htmlspecialchars($server['from_email'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                    </td>
                    <td>
                        <?php if (!empty($server['is_active'])): ?>
                        <span class="badge bg-success"><i class="bi bi-check-lg me-1"></i>Yes</span>
                        <?php else: ?>
                        <span class="badge bg-secondary">No</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end"><?= (int)($server['priority'] ?? 1) ?></td>
                    <td class="text-end"><?= number_format((int)($server['sent_per_hour'] ?? 0)) ?></td>
                    <td class="text-end"><?= number_format((int)($server['sent_per_day'] ?? 0)) ?></td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-info btn-test-smtp" title="Test Connection"
                                data-server-id="<?= (int)$server['id'] ?>">
                                <i class="bi bi-plug"></i>
                            </button>
                            <a href="/smtp-servers/<?= (int)$server['id'] ?>/edit" class="btn btn-outline-primary" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <button type="button" class="btn btn-outline-danger" title="Delete"
                                data-bs-toggle="modal" data-bs-target="#deleteModal"
                                data-server-id="<?= (int)$server['id'] ?>"
                                data-server-name="<?= htmlspecialchars($server['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Test result toast -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index:1100">
    <div id="testToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            <i class="bi bi-plug me-2" id="testToastIcon"></i>
            <strong class="me-auto">SMTP Test</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body" id="testToastBody"></div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger">
                    <i class="bi bi-trash me-2"></i>Delete SMTP Server
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">Delete <strong id="deleteServerName"></strong>? This cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteForm" method="POST" action="">
                    <?= \MailForge\Helpers\CsrfHelper::field() ?>
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('deleteModal')?.addEventListener('show.bs.modal', function (e) {
    const btn = e.relatedTarget;
    document.getElementById('deleteServerName').textContent = btn.dataset.serverName;
    document.getElementById('deleteForm').action = '/smtp-servers/' + btn.dataset.serverId;
});

document.querySelectorAll('.btn-test-smtp').forEach(function (btn) {
    btn.addEventListener('click', function () {
        const serverId = btn.dataset.serverId;
        const icon = btn.querySelector('i');
        icon.className = 'bi bi-arrow-clockwise spin';

        fetch('/smtp-servers/' + serverId + '/test', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
        })
        .then(r => r.json())
        .then(function (data) {
            icon.className = 'bi bi-plug';
            const toastBody = document.getElementById('testToastBody');
            const toastIcon = document.getElementById('testToastIcon');
            if (data.success) {
                toastIcon.className = 'bi bi-check-circle-fill text-success me-2';
                toastBody.textContent = data.message || 'Connection successful!';
            } else {
                toastIcon.className = 'bi bi-x-circle-fill text-danger me-2';
                toastBody.textContent = data.message || 'Connection failed.';
            }
            new bootstrap.Toast(document.getElementById('testToast')).show();
        })
        .catch(function () {
            icon.className = 'bi bi-plug';
            document.getElementById('testToastIcon').className = 'bi bi-x-circle-fill text-danger me-2';
            document.getElementById('testToastBody').textContent = 'Request failed.';
            new bootstrap.Toast(document.getElementById('testToast')).show();
        });
    });
});
</script>

<style>
@keyframes spin { to { transform: rotate(360deg); } }
.spin { display: inline-block; animation: spin .8s linear infinite; }
</style>
