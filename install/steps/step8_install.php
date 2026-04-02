<?php
$data     = $_SESSION['install_data'] ?? [];
$db       = $data['db']       ?? [];
$url      = $data['url']      ?? [];
$smtp     = $data['smtp']     ?? [];
$platform = $data['platform'] ?? [];
$admin    = $data['admin']    ?? [];

function maskPassword(string $p): string
{
    return $p === '' ? '<em class="text-muted">not set</em>' : str_repeat('●', min(strlen($p), 8));
}
?>

<div class="text-center mb-4">
    <div class="step-icon bg-warning bg-opacity-15 mx-auto">
        <i class="bi bi-rocket-takeoff text-warning"></i>
    </div>
    <h2 class="fw-bold mb-1">Ready to Install</h2>
    <p class="text-muted">Review your configuration then click <strong>Install Mail Forge</strong> to proceed.</p>
</div>

<div id="install-alert" class="alert d-none mb-3" role="alert"></div>

<!-- Summary -->
<div class="accordion mb-4" id="summaryAccordion">

    <!-- Database -->
    <div class="accordion-item">
        <h2 class="accordion-header">
            <button class="accordion-button py-2" type="button" data-bs-toggle="collapse" data-bs-target="#sumDb">
                <i class="bi bi-database me-2 text-primary"></i> Database
            </button>
        </h2>
        <div id="sumDb" class="accordion-collapse collapse show" data-bs-parent="#summaryAccordion">
            <div class="accordion-body py-2 small">
                <table class="table table-sm table-borderless mb-0">
                    <tr><th class="w-40 text-muted fw-normal">Host</th><td><?= htmlspecialchars((string)($db['host'] ?? '-')) ?></td></tr>
                    <tr><th class="text-muted fw-normal">Port</th><td><?= htmlspecialchars((string)($db['port'] ?? '-')) ?></td></tr>
                    <tr><th class="text-muted fw-normal">Database</th><td><?= htmlspecialchars((string)($db['name'] ?? '-')) ?></td></tr>
                    <tr><th class="text-muted fw-normal">User</th><td><?= htmlspecialchars((string)($db['user'] ?? '-')) ?></td></tr>
                    <tr><th class="text-muted fw-normal">Password</th><td><?= maskPassword((string)($db['pass'] ?? '')) ?></td></tr>
                    <tr><th class="text-muted fw-normal">Prefix</th><td><?= htmlspecialchars((string)($db['prefix'] ?? 'mf_')) ?></td></tr>
                </table>
            </div>
        </div>
    </div>

    <!-- URL -->
    <div class="accordion-item">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse" data-bs-target="#sumUrl">
                <i class="bi bi-link-45deg me-2 text-primary"></i> Site URL
            </button>
        </h2>
        <div id="sumUrl" class="accordion-collapse collapse" data-bs-parent="#summaryAccordion">
            <div class="accordion-body py-2 small">
                <table class="table table-sm table-borderless mb-0">
                    <tr><th class="w-40 text-muted fw-normal">URL</th><td><?= htmlspecialchars((string)($url['app_url'] ?? '-')) ?></td></tr>
                    <tr><th class="text-muted fw-normal">Force HTTPS</th><td><?= !empty($url['force_https']) ? 'Yes' : 'No' ?></td></tr>
                </table>
            </div>
        </div>
    </div>

    <!-- SMTP -->
    <div class="accordion-item">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse" data-bs-target="#sumSmtp">
                <i class="bi bi-send me-2 text-primary"></i> SMTP
            </button>
        </h2>
        <div id="sumSmtp" class="accordion-collapse collapse" data-bs-parent="#summaryAccordion">
            <div class="accordion-body py-2 small">
                <table class="table table-sm table-borderless mb-0">
                    <tr><th class="w-40 text-muted fw-normal">Host</th><td><?= htmlspecialchars((string)($smtp['host'] ?? '-')) ?></td></tr>
                    <tr><th class="text-muted fw-normal">Port</th><td><?= htmlspecialchars((string)($smtp['port'] ?? '-')) ?></td></tr>
                    <tr><th class="text-muted fw-normal">Encryption</th><td><?= htmlspecialchars(strtoupper((string)($smtp['encryption'] ?? '-'))) ?></td></tr>
                    <tr><th class="text-muted fw-normal">From</th><td><?= htmlspecialchars((string)($smtp['fromEmail'] ?? '-')) ?></td></tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Platform -->
    <div class="accordion-item">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse" data-bs-target="#sumPlatform">
                <i class="bi bi-gear me-2 text-primary"></i> Platform
            </button>
        </h2>
        <div id="sumPlatform" class="accordion-collapse collapse" data-bs-parent="#summaryAccordion">
            <div class="accordion-body py-2 small">
                <table class="table table-sm table-borderless mb-0">
                    <tr><th class="w-40 text-muted fw-normal">App Name</th><td><?= htmlspecialchars((string)($platform['app_name'] ?? '-')) ?></td></tr>
                    <tr><th class="text-muted fw-normal">Company</th><td><?= htmlspecialchars((string)($platform['company_name'] ?? '-')) ?></td></tr>
                    <tr><th class="text-muted fw-normal">Language</th><td><?= htmlspecialchars(strtoupper((string)($platform['default_language'] ?? '-'))) ?></td></tr>
                    <tr><th class="text-muted fw-normal">Timezone</th><td><?= htmlspecialchars((string)($platform['default_timezone'] ?? '-')) ?></td></tr>
                    <tr><th class="text-muted fw-normal">Double Opt-In</th><td><?= !empty($platform['double_opt_in']) ? 'Enabled' : 'Disabled' ?></td></tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Admin -->
    <div class="accordion-item">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse" data-bs-target="#sumAdmin">
                <i class="bi bi-person me-2 text-primary"></i> Admin Account
            </button>
        </h2>
        <div id="sumAdmin" class="accordion-collapse collapse" data-bs-parent="#summaryAccordion">
            <div class="accordion-body py-2 small">
                <table class="table table-sm table-borderless mb-0">
                    <tr><th class="w-40 text-muted fw-normal">Name</th><td><?= htmlspecialchars(trim(($admin['first_name'] ?? '') . ' ' . ($admin['last_name'] ?? ''))) ?></td></tr>
                    <tr><th class="text-muted fw-normal">Email</th><td><?= htmlspecialchars((string)($admin['email'] ?? '-')) ?></td></tr>
                    <tr><th class="text-muted fw-normal">Password</th><td><?= maskPassword((string)($admin['password'] ?? '')) ?></td></tr>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Install button -->
<div id="install-section">
    <div class="d-flex justify-content-between align-items-center">
        <button type="button" class="btn btn-outline-secondary" id="btn-prev-install" onclick="document.getElementById('prev-form-8').submit()">
            <i class="bi bi-arrow-left me-1"></i> Back
        </button>
        <button type="button" class="btn btn-success btn-lg px-5" id="btn-install">
            <span id="install-spinner" class="spinner-border spinner-border-sm d-none me-2"></span>
            <i class="bi bi-rocket-takeoff me-1" id="install-icon"></i>
            Install Mail Forge
        </button>
    </div>
</div>

<form method="POST" action="index.php" id="prev-form-8">
    <input type="hidden" name="action" value="prev">
</form>

<script>
document.getElementById('btn-install').addEventListener('click', async function () {
    const alertEl  = document.getElementById('install-alert');
    const spinner  = document.getElementById('install-spinner');
    const icon     = document.getElementById('install-icon');
    const btn      = this;

    btn.disabled = true;
    spinner.classList.remove('d-none');
    icon.classList.add('d-none');

    alertEl.className = 'alert alert-info d-flex gap-2 align-items-center';
    alertEl.innerHTML = '<div class="spinner-border spinner-border-sm me-2"></div> Running installation… this may take a moment.';
    alertEl.classList.remove('d-none');

    const body = new FormData();
    body.append('action', 'run_install');

    try {
        const res  = await fetch('index.php', { method: 'POST', body });
        const data = await res.json();

        if (data.success) {
            alertEl.className = 'alert alert-success d-flex gap-2 align-items-center';
            alertEl.innerHTML = '<i class="bi bi-check-circle-fill me-2"></i><strong>' + data.message + '</strong> Redirecting…';
            setTimeout(() => { window.location.href = 'index.php'; }, 1500);
        } else {
            alertEl.className = 'alert alert-danger';
            let html = '<strong>' + data.message + '</strong>';
            if (data.errors && data.errors.length) {
                html += '<ul class="mb-0 mt-2 ps-3">' + data.errors.map(e => '<li>' + e + '</li>').join('') + '</ul>';
            }
            alertEl.innerHTML = html;
            btn.disabled = false;
            spinner.classList.add('d-none');
            icon.classList.remove('d-none');
        }
    } catch (e) {
        alertEl.className = 'alert alert-danger';
        alertEl.innerHTML = 'Request failed: ' + e.message;
        btn.disabled = false;
        spinner.classList.add('d-none');
        icon.classList.remove('d-none');
    }
});
</script>
