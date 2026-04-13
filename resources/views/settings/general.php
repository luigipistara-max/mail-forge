<?php
/** @var array $settings */
/** @var array $timezones */
/** @var array $errors */
$settings  = $settings  ?? [];
$timezones = $timezones ?? [];
$errors    = $errors    ?? [];

$s = fn(string $key, string $default = ''): string =>
    htmlspecialchars($settings[$key] ?? $default, ENT_QUOTES, 'UTF-8');
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 fw-bold">General Settings</h1>
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

<form method="POST" action="<?= BASE_PATH ?>/settings/general">
    <?= \MailForge\Helpers\CsrfHelper::field() ?>

    <div class="row g-4">
        <div class="col-lg-8">

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent fw-semibold">Application</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="app_name" class="form-label fw-semibold">App Name <span class="text-danger">*</span></label>
                        <input type="text" id="app_name" name="app_name"
                            class="form-control <?= isset($errors['app_name']) ? 'is-invalid' : '' ?>"
                            value="<?= $s('app_name') ?>" required>
                        <?php if (isset($errors['app_name'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['app_name'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="app_url" class="form-label fw-semibold">App URL <span class="text-danger">*</span></label>
                        <input type="url" id="app_url" name="app_url"
                            class="form-control <?= isset($errors['app_url']) ? 'is-invalid' : '' ?>"
                            value="<?= $s('app_url') ?>" required placeholder="https://example.com">
                        <?php if (isset($errors['app_url'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['app_url'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label for="timezone" class="form-label fw-semibold">Timezone</label>
                            <select id="timezone" name="timezone"
                                class="form-select <?= isset($errors['timezone']) ? 'is-invalid' : '' ?>">
                                <?php foreach ($timezones as $tz): ?>
                                <option value="<?= htmlspecialchars($tz, ENT_QUOTES, 'UTF-8') ?>"
                                    <?= ($settings['timezone'] ?? '') === $tz ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($tz, ENT_QUOTES, 'UTF-8') ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['timezone'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['timezone'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-sm-6">
                            <label for="locale" class="form-label fw-semibold">Locale</label>
                            <select id="locale" name="locale"
                                class="form-select <?= isset($errors['locale']) ? 'is-invalid' : '' ?>">
                                <?php
                                $locales = ['en' => 'English', 'it' => 'Italian', 'es' => 'Spanish', 'fr' => 'French', 'de' => 'German'];
                                foreach ($locales as $code => $name):
                                ?>
                                <option value="<?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?>"
                                    <?= ($settings['locale'] ?? 'en') === $code ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['locale'])): ?>
                            <div class="invalid-feedback"><?= htmlspecialchars($errors['locale'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent fw-semibold">Company</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="company_name" class="form-label fw-semibold">Company Name</label>
                        <input type="text" id="company_name" name="company_name"
                            class="form-control <?= isset($errors['company_name']) ? 'is-invalid' : '' ?>"
                            value="<?= $s('company_name') ?>">
                        <?php if (isset($errors['company_name'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['company_name'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-0">
                        <label for="company_email" class="form-label fw-semibold">Company Email</label>
                        <input type="email" id="company_email" name="company_email"
                            class="form-control <?= isset($errors['company_email']) ? 'is-invalid' : '' ?>"
                            value="<?= $s('company_email') ?>" placeholder="contact@company.com">
                        <?php if (isset($errors['company_email'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['company_email'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
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
