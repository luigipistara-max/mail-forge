<?php
/** @var array $errors */
$errors = $errors ?? [];
$emailSent = $emailSent ?? false;
?>
<h4 class="fw-bold text-center mb-1">Forgot Password</h4>
<p class="text-muted text-center small mb-4">Enter your email and we'll send you a reset link.</p>

<?php if ($emailSent): ?>
<div class="alert alert-success" role="alert">
    <i class="bi bi-check-circle me-2"></i>
    If an account exists for that email, a password reset link has been sent. Check your inbox.
</div>
<div class="text-center mt-3">
    <a href="/login" class="btn btn-outline-primary">
        <i class="bi bi-arrow-left me-2"></i>Back to Sign In
    </a>
</div>
<?php else: ?>

<?php if (!empty($errors['general'])): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="bi bi-exclamation-circle me-2"></i>
    <?= htmlspecialchars($errors['general'], ENT_QUOTES, 'UTF-8') ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<form method="POST" action="/forgot-password" novalidate>
    <?= \MailForge\Helpers\CsrfHelper::field() ?>

    <div class="mb-4">
        <label for="email" class="form-label fw-semibold">Email Address</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
            <input
                type="email"
                id="email"
                name="email"
                class="form-control <?= !empty($errors['email']) ? 'is-invalid' : '' ?>"
                value="<?= htmlspecialchars($_OLD['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
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

    <button type="submit" class="btn btn-primary w-100 fw-semibold mb-3">
        <i class="bi bi-send me-2"></i>Send Reset Link
    </button>
</form>

<div class="text-center">
    <a href="/login" class="small text-decoration-none text-muted">
        <i class="bi bi-arrow-left me-1"></i>Back to Sign In
    </a>
</div>

<?php endif; ?>
