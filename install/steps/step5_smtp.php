<?php
// Persist form values on next
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['smtp_host']) && ($_POST['action'] ?? '') === 'next') {
    $_SESSION['install_data']['smtp'] = [
        'host'       => trim($_POST['smtp_host']       ?? ''),
        'port'       => (int) ($_POST['smtp_port']     ?? 587),
        'user'       => trim($_POST['smtp_user']       ?? ''),
        'pass'       => $_POST['smtp_password']        ?? '',
        'encryption' => trim($_POST['smtp_encryption'] ?? 'tls'),
        'fromEmail'  => trim($_POST['smtp_from_email'] ?? ''),
        'fromName'   => trim($_POST['smtp_from_name']  ?? 'Mail Forge'),
    ];
}

$saved = $_SESSION['install_data']['smtp'] ?? [];
$f = [
    'host'       => $saved['host']       ?? '',
    'port'       => $saved['port']       ?? 587,
    'user'       => $saved['user']       ?? '',
    'pass'       => $saved['pass']       ?? '',
    'encryption' => $saved['encryption'] ?? 'tls',
    'fromEmail'  => $saved['fromEmail']  ?? '',
    'fromName'   => $saved['fromName']   ?? 'Mail Forge',
];
?>

<div class="text-center mb-4">
    <div class="step-icon bg-primary bg-opacity-10 mx-auto">
        <i class="bi bi-send text-primary"></i>
    </div>
    <h2 class="fw-bold mb-1">SMTP Configuration</h2>
    <p class="text-muted">Configure outgoing email delivery for Mail Forge.</p>
</div>

<div id="smtp-alert" class="alert d-none mb-3" role="alert"></div>

<form method="POST" action="index.php" id="smtp-form">
    <input type="hidden" name="action" value="next">

    <div class="row g-3 mb-3">
        <div class="col-md-8">
            <label class="form-label" for="smtp_host">SMTP Host</label>
            <input type="text" class="form-control" id="smtp_host" name="smtp_host"
                   value="<?= htmlspecialchars((string)$f['host']) ?>"
                   placeholder="smtp.example.com" required>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="smtp_port">Port</label>
            <input type="number" class="form-control" id="smtp_port" name="smtp_port"
                   value="<?= (int)$f['port'] ?>" min="1" max="65535" required>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <label class="form-label" for="smtp_user">SMTP Username</label>
            <input type="text" class="form-control" id="smtp_user" name="smtp_user"
                   value="<?= htmlspecialchars((string)$f['user']) ?>" autocomplete="off">
        </div>
        <div class="col-md-6">
            <label class="form-label" for="smtp_password">SMTP Password</label>
            <div class="input-group">
                <input type="password" class="form-control" id="smtp_password" name="smtp_password"
                       value="<?= htmlspecialchars((string)$f['pass']) ?>" autocomplete="new-password">
                <button class="btn btn-outline-secondary" type="button" id="toggleSmtpPass">
                    <i class="bi bi-eye"></i>
                </button>
            </div>
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label" for="smtp_encryption">Encryption</label>
        <select class="form-select" id="smtp_encryption" name="smtp_encryption">
            <option value="tls"  <?= $f['encryption'] === 'tls'  ? 'selected' : '' ?>>TLS (STARTTLS) – port 587</option>
            <option value="ssl"  <?= $f['encryption'] === 'ssl'  ? 'selected' : '' ?>>SSL/TLS – port 465</option>
            <option value="none" <?= $f['encryption'] === 'none' ? 'selected' : '' ?>>None – port 25</option>
        </select>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <label class="form-label" for="smtp_from_email">From Email Address</label>
            <input type="email" class="form-control" id="smtp_from_email" name="smtp_from_email"
                   value="<?= htmlspecialchars((string)$f['fromEmail']) ?>"
                   placeholder="noreply@example.com" required>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="smtp_from_name">From Name</label>
            <input type="text" class="form-control" id="smtp_from_name" name="smtp_from_name"
                   value="<?= htmlspecialchars((string)$f['fromName']) ?>">
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center">
        <div class="d-flex gap-2">
            <button type="submit" name="action" value="prev" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back
            </button>
            <button type="button" class="btn btn-outline-primary" id="btn-test-smtp">
                <span id="smtp-spinner" class="spinner-border spinner-border-sm d-none me-1"></span>
                <i class="bi bi-plug" id="smtp-test-icon"></i> Test Connection
            </button>
        </div>
        <button type="submit" class="btn btn-primary">
            Next <i class="bi bi-arrow-right ms-1"></i>
        </button>
    </div>
</form>

<script>
document.getElementById('toggleSmtpPass').addEventListener('click', function () {
    const inp = document.getElementById('smtp_password');
    inp.type = inp.type === 'password' ? 'text' : 'password';
    this.querySelector('i').className = inp.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
});

document.getElementById('btn-test-smtp').addEventListener('click', async function () {
    const alertEl = document.getElementById('smtp-alert');
    const spinner = document.getElementById('smtp-spinner');
    const icon    = document.getElementById('smtp-test-icon');

    spinner.classList.remove('d-none');
    icon.classList.add('d-none');
    this.disabled = true;

    const body = new FormData();
    body.append('action',            'test_smtp');
    body.append('smtp_host',         document.getElementById('smtp_host').value);
    body.append('smtp_port',         document.getElementById('smtp_port').value);
    body.append('smtp_user',         document.getElementById('smtp_user').value);
    body.append('smtp_password',     document.getElementById('smtp_password').value);
    body.append('smtp_encryption',   document.getElementById('smtp_encryption').value);
    body.append('smtp_from_email',   document.getElementById('smtp_from_email').value);
    body.append('smtp_from_name',    document.getElementById('smtp_from_name').value);

    try {
        const res  = await fetch('index.php', { method: 'POST', body });
        const data = await res.json();
        alertEl.className = 'alert ' + (data.success ? 'alert-success' : 'alert-danger');
        alertEl.innerHTML = '<i class="bi bi-' + (data.success ? 'check-circle' : 'exclamation-triangle') + '-fill me-2"></i>' + data.message;
        alertEl.classList.remove('d-none');
    } catch (e) {
        alertEl.className = 'alert alert-danger';
        alertEl.innerHTML = 'Request failed: ' + e.message;
        alertEl.classList.remove('d-none');
    } finally {
        spinner.classList.add('d-none');
        icon.classList.remove('d-none');
        this.disabled = false;
    }
});

// Auto-update port suggestion when encryption changes
document.getElementById('smtp_encryption').addEventListener('change', function () {
    const portMap = { tls: 587, ssl: 465, none: 25 };
    document.getElementById('smtp_port').value = portMap[this.value] ?? 587;
});
</script>
