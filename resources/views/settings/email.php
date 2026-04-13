<?php
/** @var array $settings */
/** @var array $errors */
$settings = $settings ?? [];
$errors   = $errors   ?? [];

$s = fn(string $key, string $default = ''): string =>
    htmlspecialchars($settings[$key] ?? $default, ENT_QUOTES, 'UTF-8');
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 fw-bold">Email Settings</h1>
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

<form method="POST" action="<?= BASE_PATH ?>/settings/email">
    <?= \MailForge\Helpers\CsrfHelper::field() ?>

    <div class="row g-4">
        <div class="col-lg-8">

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent fw-semibold">Sender Defaults</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label for="from_name" class="form-label fw-semibold">From Name <span class="text-danger">*</span></label>
                            <input type="text" id="from_name" name="from_name"
                                class="form-control <?= isset($errors['from_name']) ? 'is-invalid' : '' ?>"
                                value="<?= $s('from_name') ?>" required placeholder="My Company">
                            <?php if (isset($errors['from_name'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['from_name'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-sm-6">
                            <label for="from_email" class="form-label fw-semibold">From Email <span class="text-danger">*</span></label>
                            <input type="email" id="from_email" name="from_email"
                                class="form-control <?= isset($errors['from_email']) ? 'is-invalid' : '' ?>"
                                value="<?= $s('from_email') ?>" required placeholder="noreply@example.com">
                            <?php if (isset($errors['from_email'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['from_email'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-sm-6">
                            <label for="reply_to_email" class="form-label fw-semibold">Reply-To Email</label>
                            <input type="email" id="reply_to_email" name="reply_to_email"
                                class="form-control <?= isset($errors['reply_to_email']) ? 'is-invalid' : '' ?>"
                                value="<?= $s('reply_to_email') ?>" placeholder="replies@example.com">
                            <div class="form-text">Leave blank to use the From Email.</div>
                            <?php if (isset($errors['reply_to_email'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['reply_to_email'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent fw-semibold">Sending Batches</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label for="batch_size" class="form-label fw-semibold">Batch Size <span class="text-danger">*</span></label>
                            <input type="number" id="batch_size" name="batch_size" min="1"
                                class="form-control <?= isset($errors['batch_size']) ? 'is-invalid' : '' ?>"
                                value="<?= $s('batch_size', '100') ?>" required>
                            <div class="form-text">Number of emails sent per batch.</div>
                            <?php if (isset($errors['batch_size'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['batch_size'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-sm-6">
                            <label for="batch_interval_minutes" class="form-label fw-semibold">Interval (minutes) <span class="text-danger">*</span></label>
                            <input type="number" id="batch_interval_minutes" name="batch_interval_minutes" min="1"
                                class="form-control <?= isset($errors['batch_interval_minutes']) ? 'is-invalid' : '' ?>"
                                value="<?= $s('batch_interval_minutes', '5') ?>" required>
                            <div class="form-text">Pause between batches in minutes.</div>
                            <?php if (isset($errors['batch_interval_minutes'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['batch_interval_minutes'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </div>
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
