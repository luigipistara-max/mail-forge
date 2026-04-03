<?php
// Pre-fill from session if available
$saved = $_SESSION['install_data']['db'] ?? [];

// Persist form values when navigating back (POST action=prev passes form fields)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['db_host'])) {
    $_SESSION['install_data']['db'] = [
        'host'    => trim($_POST['db_host']     ?? 'localhost'),
        'port'    => (int) ($_POST['db_port']   ?? 3306),
        'name'    => trim($_POST['db_name']     ?? ''),
        'user'    => trim($_POST['db_user']     ?? ''),
        'pass'    => $_POST['db_password']      ?? '',
        'prefix'  => trim($_POST['db_prefix']   ?? 'mf_'),
        'charset' => trim($_POST['db_charset']  ?? 'utf8mb4'),
    ];
    $saved = $_SESSION['install_data']['db'];
}

$f = [
    'host'    => $saved['host']    ?? 'localhost',
    'port'    => $saved['port']    ?? 3306,
    'name'    => $saved['name']    ?? '',
    'user'    => $saved['user']    ?? '',
    'pass'    => $saved['pass']    ?? '',
    'prefix'  => $saved['prefix']  ?? 'mf_',
    'charset' => $saved['charset'] ?? 'utf8mb4',
];
?>

<div class="text-center mb-4">
    <div class="step-icon bg-primary bg-opacity-10 mx-auto">
        <i class="bi bi-database text-primary"></i>
    </div>
    <h2 class="fw-bold mb-1">Database Configuration</h2>
    <p class="text-muted">Enter your MySQL database connection details.</p>
</div>

<div id="db-alert" class="alert d-none mb-3" role="alert"></div>

<form method="POST" action="index.php" id="db-form">
    <input type="hidden" name="action" value="next">

    <div class="row g-3 mb-3">
        <div class="col-md-8">
            <label class="form-label" for="db_host">Database Host</label>
            <input type="text" class="form-control" id="db_host" name="db_host"
                   value="<?= htmlspecialchars((string)$f['host']) ?>" required>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="db_port">Port</label>
            <input type="number" class="form-control" id="db_port" name="db_port"
                   value="<?= (int)$f['port'] ?>" required min="1" max="65535">
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label" for="db_name">Database Name</label>
        <input type="text" class="form-control" id="db_name" name="db_name"
               value="<?= htmlspecialchars((string)$f['name']) ?>" required>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <label class="form-label" for="db_user">Username</label>
            <input type="text" class="form-control" id="db_user" name="db_user"
                   value="<?= htmlspecialchars((string)$f['user']) ?>" autocomplete="off" required>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="db_password">Password</label>
            <div class="input-group">
                <input type="password" class="form-control" id="db_password" name="db_password"
                       value="<?= htmlspecialchars((string)$f['pass']) ?>" autocomplete="new-password">
                <button class="btn btn-outline-secondary" type="button" id="toggleDbPass">
                    <i class="bi bi-eye"></i>
                </button>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <label class="form-label" for="db_prefix">Table Prefix</label>
            <input type="text" class="form-control" id="db_prefix" name="db_prefix"
                   value="<?= htmlspecialchars((string)$f['prefix']) ?>">
            <div class="form-text">Default: <code>mf_</code></div>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="db_charset">Character Set</label>
            <input type="text" class="form-control" id="db_charset" name="db_charset"
                   value="<?= htmlspecialchars((string)$f['charset']) ?>">
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center">
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary" id="btn-prev" onclick="goBack()">
                <i class="bi bi-arrow-left me-1"></i> Back
            </button>
            <button type="button" class="btn btn-outline-primary" id="btn-test">
                <span id="test-spinner" class="spinner-border spinner-border-sm d-none me-1"></span>
                <i class="bi bi-plug" id="test-icon"></i> Test Connection
            </button>
        </div>
        <button type="submit" class="btn btn-primary" id="btn-next">
            Next <i class="bi bi-arrow-right ms-1"></i>
        </button>
    </div>
</form>

<form method="POST" action="index.php" id="prev-form">
    <input type="hidden" name="action" value="prev">
</form>

<script>
function goBack() { document.getElementById('prev-form').submit(); }

document.getElementById('toggleDbPass').addEventListener('click', function () {
    const inp = document.getElementById('db_password');
    inp.type = inp.type === 'password' ? 'text' : 'password';
    this.querySelector('i').className = inp.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
});

document.getElementById('btn-test').addEventListener('click', async function () {
    const alert   = document.getElementById('db-alert');
    const spinner = document.getElementById('test-spinner');
    const icon    = document.getElementById('test-icon');
    const form    = document.getElementById('db-form');

    spinner.classList.remove('d-none');
    icon.classList.add('d-none');
    this.disabled = true;

    const body = new FormData();
    body.append('action', 'test_db');
    body.append('db_host',     document.getElementById('db_host').value);
    body.append('db_port',     document.getElementById('db_port').value);
    body.append('db_name',     document.getElementById('db_name').value);
    body.append('db_user',     document.getElementById('db_user').value);
    body.append('db_password', document.getElementById('db_password').value);
    body.append('db_prefix',   document.getElementById('db_prefix').value);
    body.append('db_charset',  document.getElementById('db_charset').value);

    try {
        const res  = await fetch('index.php', { method: 'POST', body });
        const data = await res.json();

        alert.className = 'alert ' + (data.success ? 'alert-success' : 'alert-danger');
        alert.innerHTML = '<i class="bi bi-' + (data.success ? 'check-circle' : 'exclamation-triangle') + '-fill me-2"></i>' + data.message;
        alert.classList.remove('d-none');
    } catch (e) {
        alert.className = 'alert alert-danger';
        alert.innerHTML = 'Request failed: ' + e.message;
        alert.classList.remove('d-none');
    } finally {
        spinner.classList.add('d-none');
        icon.classList.remove('d-none');
        this.disabled = false;
    }
});
</script>
