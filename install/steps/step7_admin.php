<?php
$errors = [];

// Validate & persist on next
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_email']) && ($_POST['action'] ?? '') === 'next') {
    $firstName = trim($_POST['admin_first_name']     ?? '');
    $lastName  = trim($_POST['admin_last_name']      ?? '');
    $email     = trim($_POST['admin_email']          ?? '');
    $password  = $_POST['admin_password']            ?? '';
    $confirm   = $_POST['admin_password_confirm']    ?? '';

    if ($firstName === '')                                     $errors[] = 'First name is required.';
    if ($lastName  === '')                                     $errors[] = 'Last name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))            $errors[] = 'A valid email address is required.';
    if (strlen($password) < 8)                                 $errors[] = 'Password must be at least 8 characters.';
    if ($password !== $confirm)                                $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
        $_SESSION['install_data']['admin'] = [
            'first_name' => $firstName,
            'last_name'  => $lastName,
            'email'      => $email,
            'password'   => $password,
        ];
    }
}

$saved = $_SESSION['install_data']['admin'] ?? [];
$f = [
    'first_name' => $saved['first_name'] ?? '',
    'last_name'  => $saved['last_name']  ?? '',
    'email'      => $saved['email']      ?? '',
];
?>

<div class="text-center mb-4">
    <div class="step-icon bg-primary bg-opacity-10 mx-auto">
        <i class="bi bi-person-check text-primary"></i>
    </div>
    <h2 class="fw-bold mb-1">Admin Account</h2>
    <p class="text-muted">Create the administrator account for Mail Forge.</p>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger mb-3">
    <ul class="mb-0 ps-3">
        <?php foreach ($errors as $err): ?>
        <li><?= htmlspecialchars($err) ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<form method="POST" action="index.php" id="admin-form" novalidate>
    <input type="hidden" name="action" value="next">

    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <label class="form-label" for="admin_first_name">First Name</label>
            <input type="text" class="form-control" id="admin_first_name" name="admin_first_name"
                   value="<?= htmlspecialchars((string)$f['first_name']) ?>" required autofocus>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="admin_last_name">Last Name</label>
            <input type="text" class="form-control" id="admin_last_name" name="admin_last_name"
                   value="<?= htmlspecialchars((string)$f['last_name']) ?>" required>
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label" for="admin_email">Email Address</label>
        <input type="email" class="form-control" id="admin_email" name="admin_email"
               value="<?= htmlspecialchars((string)$f['email']) ?>"
               placeholder="admin@example.com" required>
    </div>

    <div class="mb-2">
        <label class="form-label" for="admin_password">Password <small class="text-muted">(min. 8 characters)</small></label>
        <div class="input-group">
            <input type="password" class="form-control" id="admin_password" name="admin_password"
                   autocomplete="new-password" minlength="8" required>
            <button class="btn btn-outline-secondary" type="button" id="toggleAdminPass">
                <i class="bi bi-eye"></i>
            </button>
        </div>
        <!-- Password strength indicator -->
        <div class="mt-2">
            <div class="progress" style="height:6px">
                <div id="strength-bar" class="progress-bar" role="progressbar" style="width:0%"></div>
            </div>
            <small id="strength-label" class="text-muted"></small>
        </div>
    </div>

    <div class="mb-4">
        <label class="form-label" for="admin_password_confirm">Confirm Password</label>
        <div class="input-group">
            <input type="password" class="form-control" id="admin_password_confirm" name="admin_password_confirm"
                   autocomplete="new-password" required>
            <button class="btn btn-outline-secondary" type="button" id="toggleAdminPassConfirm">
                <i class="bi bi-eye"></i>
            </button>
        </div>
        <small id="match-label" class="d-none"></small>
    </div>

    <div class="d-flex justify-content-between mt-2">
        <button type="submit" name="action" value="prev" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </button>
        <button type="submit" class="btn btn-primary" id="btn-next-admin">
            Next <i class="bi bi-arrow-right ms-1"></i>
        </button>
    </div>
</form>

<script>
// Toggle password visibility
function makeToggle(btnId, inputId) {
    document.getElementById(btnId).addEventListener('click', function () {
        const inp = document.getElementById(inputId);
        inp.type = inp.type === 'password' ? 'text' : 'password';
        this.querySelector('i').className = inp.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
    });
}
makeToggle('toggleAdminPass', 'admin_password');
makeToggle('toggleAdminPassConfirm', 'admin_password_confirm');

// Password strength
const strengthBar   = document.getElementById('strength-bar');
const strengthLabel = document.getElementById('strength-label');
const matchLabel    = document.getElementById('match-label');

document.getElementById('admin_password').addEventListener('input', function () {
    const v = this.value;
    let score = 0;
    if (v.length >= 8)              score++;
    if (v.length >= 12)             score++;
    if (/[A-Z]/.test(v))            score++;
    if (/[0-9]/.test(v))            score++;
    if (/[^A-Za-z0-9]/.test(v))     score++;

    const levels = [
        { pct: 0,   cls: '',               label: '' },
        { pct: 20,  cls: 'bg-danger',      label: 'Very weak' },
        { pct: 40,  cls: 'bg-warning',     label: 'Weak' },
        { pct: 60,  cls: 'bg-info',        label: 'Fair' },
        { pct: 80,  cls: 'bg-primary',     label: 'Strong' },
        { pct: 100, cls: 'bg-success',     label: 'Very strong' },
    ];
    const lvl = levels[Math.min(score, 5)];
    strengthBar.style.width  = lvl.pct + '%';
    strengthBar.className    = 'progress-bar ' + lvl.cls;
    strengthLabel.textContent = lvl.label;
    strengthLabel.className  = 'text-muted';
});

// Match check
document.getElementById('admin_password_confirm').addEventListener('input', function () {
    const pass    = document.getElementById('admin_password').value;
    const matches = this.value === pass && pass !== '';
    matchLabel.classList.remove('d-none', 'text-success', 'text-danger');
    matchLabel.classList.add(matches ? 'text-success' : 'text-danger');
    matchLabel.textContent = matches ? '✓ Passwords match' : '✗ Passwords do not match';
});
</script>
