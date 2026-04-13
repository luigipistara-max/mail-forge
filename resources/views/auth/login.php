<?php
/** @var array $errors */
$errors = $errors ?? [];
?>
<h4 class="fw-bold text-center mb-1">Sign In</h4>
<p class="text-muted text-center small mb-4">Welcome back! Please enter your details.</p>

<?php if (!empty($errors['general'])): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="bi bi-exclamation-circle me-2"></i>
    <?= htmlspecialchars($errors['general'], ENT_QUOTES, 'UTF-8') ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<form method="POST" action="<?= BASE_PATH ?>/login" novalidate>
    <?= \MailForge\Helpers\CsrfHelper::field() ?>

    <div class="mb-3">
        <label for="email" class="form-label fw-semibold">Email Address</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
            <input
                type="email"
                id="email"
                name="email"
                class="form-control <?= !empty($errors['email']) ? 'is-invalid' : '' ?>"
                value="<?= htmlspecialchars($old['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                placeholder="you@example.com"
                required
                autocomplete="email"
                autofocus
            >
            <?php if (!empty($errors['email'])): ?>
            <div class="invalid-feedback"><?= htmlspecialchars($errors['email'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="mb-3">
        <div class="d-flex justify-content-between align-items-center">
            <label for="password" class="form-label fw-semibold mb-0">Password</label>
            <a href="<?= BASE_PATH ?>/forgot-password" class="small text-decoration-none">Forgot password?</a>
        </div>
        <div class="input-group mt-1">
            <span class="input-group-text"><i class="bi bi-lock"></i></span>
            <input
                type="password"
                id="password"
                name="password"
                class="form-control <?= !empty($errors['password']) ? 'is-invalid' : '' ?>"
                placeholder="Enter your password"
                required
                autocomplete="current-password"
            >
            <button class="btn btn-outline-secondary" type="button" id="togglePassword" tabindex="-1" title="Show/hide password">
                <i class="bi bi-eye" id="togglePasswordIcon"></i>
            </button>
            <?php if (!empty($errors['password'])): ?>
            <div class="invalid-feedback"><?= htmlspecialchars($errors['password'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="mb-4 form-check">
        <input type="checkbox" class="form-check-input" id="remember" name="remember" value="1"
            <?= !empty($old['remember'] ?? '') ? 'checked' : '' ?>>
        <label class="form-check-label" for="remember">Remember me for 30 days</label>
    </div>

    <button type="submit" class="btn btn-primary w-100 fw-semibold">
        <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
    </button>
</form>

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
