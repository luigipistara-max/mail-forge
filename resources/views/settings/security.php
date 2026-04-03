<?php
/** @var array $settings */
/** @var array $errors */
$settings = $settings ?? [];
$errors   = $errors   ?? [];

$s = fn(string $key, string $default = ''): string =>
    htmlspecialchars($settings[$key] ?? $default, ENT_QUOTES, 'UTF-8');
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 fw-bold">Security Settings</h1>
</div>

<?php if (!empty($errors) && isset($errors[0])): ?>
<div class="alert alert-danger mb-4">
    <ul class="mb-0">
        <?php foreach ($errors as $err): ?>
        <li><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<form method="POST" action="/settings/security">
    <?= \MailForge\Helpers\CsrfHelper::field() ?>

    <div class="row g-4">
        <div class="col-lg-8">

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent fw-semibold">Login Protection</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label for="max_login_attempts" class="form-label fw-semibold">Max Login Attempts <span class="text-danger">*</span></label>
                            <input type="number" id="max_login_attempts" name="max_login_attempts" min="1"
                                class="form-control <?= isset($errors['max_login_attempts']) ? 'is-invalid' : '' ?>"
                                value="<?= $s('max_login_attempts', '5') ?>" required>
                            <div class="form-text">Failed attempts before lockout.</div>
                            <?php if (isset($errors['max_login_attempts'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['max_login_attempts'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-sm-6">
                            <label for="lockout_duration_minutes" class="form-label fw-semibold">Lockout Duration (min) <span class="text-danger">*</span></label>
                            <input type="number" id="lockout_duration_minutes" name="lockout_duration_minutes" min="1"
                                class="form-control <?= isset($errors['lockout_duration_minutes']) ? 'is-invalid' : '' ?>"
                                value="<?= $s('lockout_duration_minutes', '15') ?>" required>
                            <?php if (isset($errors['lockout_duration_minutes'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['lockout_duration_minutes'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent fw-semibold">Session &amp; Password</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label for="session_timeout_minutes" class="form-label fw-semibold">Session Timeout (min) <span class="text-danger">*</span></label>
                            <input type="number" id="session_timeout_minutes" name="session_timeout_minutes" min="1"
                                class="form-control <?= isset($errors['session_timeout_minutes']) ? 'is-invalid' : '' ?>"
                                value="<?= $s('session_timeout_minutes', '120') ?>" required>
                            <?php if (isset($errors['session_timeout_minutes'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['session_timeout_minutes'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-sm-6">
                            <label for="password_min_length" class="form-label fw-semibold">Min Password Length <span class="text-danger">*</span></label>
                            <input type="number" id="password_min_length" name="password_min_length" min="6" max="128"
                                class="form-control <?= isset($errors['password_min_length']) ? 'is-invalid' : '' ?>"
                                value="<?= $s('password_min_length', '8') ?>" required>
                            <?php if (isset($errors['password_min_length'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['password_min_length'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent fw-semibold">HTTPS</div>
                <div class="card-body">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="force_https" name="force_https" value="1"
                            <?= !empty($settings['force_https']) ? 'checked' : '' ?>>
                        <label class="form-check-label fw-semibold" for="force_https">Force HTTPS</label>
                        <div class="form-text">Redirect all HTTP requests to HTTPS.</div>
                    </div>
                </div>
            </div>

        </div><!-- /col-lg-8 -->

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>Save Settings
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
