<?php
/** @var array $settings */
/** @var array $errors */
$settings = $settings ?? [];
$errors   = $errors   ?? [];

$s = fn(string $key, string $default = ''): string =>
    htmlspecialchars($settings[$key] ?? $default, ENT_QUOTES, 'UTF-8');
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 fw-bold">PWA Settings</h1>
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

<form method="POST" action="<?= BASE_PATH ?>/settings/pwa">
    <?= \MailForge\Helpers\CsrfHelper::field() ?>

    <div class="row g-4">
        <div class="col-lg-8">

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent fw-semibold">Progressive Web App</div>
                <div class="card-body">

                    <div class="form-check form-switch mb-4">
                        <input class="form-check-input" type="checkbox" id="pwa_enabled" name="pwa_enabled" value="1"
                            <?= !empty($settings['pwa_enabled']) ? 'checked' : '' ?>>
                        <label class="form-check-label fw-semibold" for="pwa_enabled">Enable PWA</label>
                        <div class="form-text">Serve a web app manifest and service worker.</div>
                    </div>

                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label for="pwa_name" class="form-label fw-semibold">App Name <span class="text-danger">*</span></label>
                            <input type="text" id="pwa_name" name="pwa_name"
                                class="form-control <?= isset($errors['pwa_name']) ? 'is-invalid' : '' ?>"
                                value="<?= $s('pwa_name') ?>" required placeholder="Mail Forge">
                            <?php if (isset($errors['pwa_name'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['pwa_name'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-sm-6">
                            <label for="pwa_short_name" class="form-label fw-semibold">Short Name <span class="text-danger">*</span></label>
                            <input type="text" id="pwa_short_name" name="pwa_short_name" maxlength="12"
                                class="form-control <?= isset($errors['pwa_short_name']) ? 'is-invalid' : '' ?>"
                                value="<?= $s('pwa_short_name') ?>" required placeholder="MailForge">
                            <div class="form-text">Max 12 characters; shown on home screen.</div>
                            <?php if (isset($errors['pwa_short_name'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['pwa_short_name'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-sm-6">
                            <label for="pwa_theme_color" class="form-label fw-semibold">Theme Color</label>
                            <div class="input-group">
                                <input type="color" id="pwa_theme_color" name="pwa_theme_color"
                                    class="form-control form-control-color <?= isset($errors['pwa_theme_color']) ? 'is-invalid' : '' ?>"
                                    value="<?= $s('pwa_theme_color', '#0d6efd') ?>">
                                <span class="input-group-text small" id="themeColorHex"><?= $s('pwa_theme_color', '#0d6efd') ?></span>
                                <?php if (isset($errors['pwa_theme_color'])): ?>
                                <div class="invalid-feedback"><?= htmlspecialchars($errors['pwa_theme_color'], ENT_QUOTES, 'UTF-8') ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <label for="pwa_background_color" class="form-label fw-semibold">Background Color</label>
                            <div class="input-group">
                                <input type="color" id="pwa_background_color" name="pwa_background_color"
                                    class="form-control form-control-color <?= isset($errors['pwa_background_color']) ? 'is-invalid' : '' ?>"
                                    value="<?= $s('pwa_background_color', '#ffffff') ?>">
                                <span class="input-group-text small" id="bgColorHex"><?= $s('pwa_background_color', '#ffffff') ?></span>
                                <?php if (isset($errors['pwa_background_color'])): ?>
                                <div class="invalid-feedback"><?= htmlspecialchars($errors['pwa_background_color'], ENT_QUOTES, 'UTF-8') ?></div>
                                <?php endif; ?>
                            </div>
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

<script>
document.getElementById('pwa_theme_color')?.addEventListener('input', function () {
    document.getElementById('themeColorHex').textContent = this.value;
});
document.getElementById('pwa_background_color')?.addEventListener('input', function () {
    document.getElementById('bgColorHex').textContent = this.value;
});
</script>
