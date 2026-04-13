<?php
/** @var array $errors */
$errors = $errors ?? [];
$token = $token ?? '';
?>
<h4 class="fw-bold text-center mb-1">Reset Password</h4>
<p class="text-muted text-center small mb-4">Enter your new password below.</p>

<?php if (!empty($errors['general'])): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="bi bi-exclamation-circle me-2"></i>
    <?= htmlspecialchars($errors['general'], ENT_QUOTES, 'UTF-8') ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<form method="POST" action="<?= BASE_PATH ?>/reset-password" novalidate>
    <?= \MailForge\Helpers\CsrfHelper::field() ?>
    <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">

    <div class="mb-3">
        <label for="password" class="form-label fw-semibold">New Password</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-lock"></i></span>
            <input
                type="password"
                id="password"
                name="password"
                class="form-control <?= !empty($errors['password']) ? 'is-invalid' : '' ?>"
                placeholder="Minimum 8 characters"
                required
                autocomplete="new-password"
                autofocus
            >
            <button class="btn btn-outline-secondary" type="button" id="togglePassword" tabindex="-1" title="Show/hide password">
                <i class="bi bi-eye" id="togglePasswordIcon"></i>
            </button>
            <?php if (!empty($errors['password'])): ?>
            <div class="invalid-feedback"><?= htmlspecialchars($errors['password'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        </div>
        <div class="form-text">Minimum 8 characters. Use a mix of letters, numbers and symbols.</div>
    </div>

    <div class="mb-4">
        <label for="password_confirmation" class="form-label fw-semibold">Confirm New Password</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
            <input
                type="password"
                id="password_confirmation"
                name="password_confirmation"
                class="form-control <?= !empty($errors['password_confirmation']) ? 'is-invalid' : '' ?>"
                placeholder="Repeat new password"
                required
                autocomplete="new-password"
            >
            <?php if (!empty($errors['password_confirmation'])): ?>
            <div class="invalid-feedback"><?= htmlspecialchars($errors['password_confirmation'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        </div>
    </div>

    <button type="submit" class="btn btn-primary w-100 fw-semibold mb-3">
        <i class="bi bi-shield-check me-2"></i>Reset Password
    </button>
</form>

<div class="text-center">
    <a href="<?= BASE_PATH ?>/login" class="small text-decoration-none text-muted">
        <i class="bi bi-arrow-left me-1"></i>Back to Sign In
    </a>
</div>

<script>
document.getElementById('togglePassword')?.addEventListener('click', function () {
    const input = document.getElementById('password');
    const icon = document.getElementById('togglePasswordIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('bi-eye', 'bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('bi-eye-slash', 'bi-eye');
    }
});
</script>
