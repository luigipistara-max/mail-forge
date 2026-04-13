<?php
/** @var array $settings */
/** @var array $errors */
$settings = $settings ?? [];
$errors   = $errors   ?? [];

$s = fn(string $key, string $default = ''): string =>
    htmlspecialchars($settings[$key] ?? $default, ENT_QUOTES, 'UTF-8');
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 fw-bold">Queue Settings</h1>
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

<form method="POST" action="<?= BASE_PATH ?>/settings/queue">
    <?= \MailForge\Helpers\CsrfHelper::field() ?>

    <div class="row g-4">
        <div class="col-lg-8">

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent fw-semibold">Batch Defaults</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label for="default_batch_size" class="form-label fw-semibold">Default Batch Size <span class="text-danger">*</span></label>
                            <input type="number" id="default_batch_size" name="default_batch_size" min="1"
                                class="form-control <?= isset($errors['default_batch_size']) ? 'is-invalid' : '' ?>"
                                value="<?= $s('default_batch_size', '100') ?>" required>
                            <div class="form-text">Emails processed per batch run.</div>
                            <?php if (isset($errors['default_batch_size'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['default_batch_size'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-sm-6">
                            <label for="default_batch_interval" class="form-label fw-semibold">Batch Interval (min) <span class="text-danger">*</span></label>
                            <input type="number" id="default_batch_interval" name="default_batch_interval" min="1"
                                class="form-control <?= isset($errors['default_batch_interval']) ? 'is-invalid' : '' ?>"
                                value="<?= $s('default_batch_interval', '5') ?>" required>
                            <div class="form-text">Pause between batch runs.</div>
                            <?php if (isset($errors['default_batch_interval'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['default_batch_interval'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent fw-semibold">Retry Policy</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label for="max_retry_attempts" class="form-label fw-semibold">Max Retry Attempts <span class="text-danger">*</span></label>
                            <input type="number" id="max_retry_attempts" name="max_retry_attempts" min="0"
                                class="form-control <?= isset($errors['max_retry_attempts']) ? 'is-invalid' : '' ?>"
                                value="<?= $s('max_retry_attempts', '3') ?>" required>
                            <div class="form-text">0 = no retries.</div>
                            <?php if (isset($errors['max_retry_attempts'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['max_retry_attempts'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-sm-6">
                            <label for="retry_delay_minutes" class="form-label fw-semibold">Retry Delay (min) <span class="text-danger">*</span></label>
                            <input type="number" id="retry_delay_minutes" name="retry_delay_minutes" min="1"
                                class="form-control <?= isset($errors['retry_delay_minutes']) ? 'is-invalid' : '' ?>"
                                value="<?= $s('retry_delay_minutes', '10') ?>" required>
                            <?php if (isset($errors['retry_delay_minutes'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['retry_delay_minutes'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent fw-semibold">Failure Threshold</div>
                <div class="card-body">
                    <div class="col-sm-6">
                        <label for="failure_threshold_percent" class="form-label fw-semibold">
                            Failure Threshold (%) <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <input type="number" id="failure_threshold_percent" name="failure_threshold_percent"
                                min="0" max="100"
                                class="form-control <?= isset($errors['failure_threshold_percent']) ? 'is-invalid' : '' ?>"
                                value="<?= $s('failure_threshold_percent', '10') ?>" required>
                            <span class="input-group-text">%</span>
                            <?php if (isset($errors['failure_threshold_percent'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['failure_threshold_percent'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="form-text">Pause queue when failure rate exceeds this value.</div>
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
