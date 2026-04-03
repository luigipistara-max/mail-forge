<?php
// Persist form values on next
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['app_name']) && ($_POST['action'] ?? '') === 'next') {
    $_SESSION['install_data']['platform'] = [
        'app_name'             => trim($_POST['app_name']             ?? 'Mail Forge'),
        'company_name'         => trim($_POST['company_name']         ?? ''),
        'company_email'        => trim($_POST['company_email']        ?? ''),
        'default_language'     => trim($_POST['default_language']     ?? 'en'),
        'default_timezone'     => trim($_POST['default_timezone']     ?? 'UTC'),
        'double_opt_in'        => !empty($_POST['double_opt_in']),
        'pwa_name'             => trim($_POST['pwa_name']             ?? 'Mail Forge'),
        'pwa_short_name'       => trim($_POST['pwa_short_name']       ?? 'MailForge'),
        'pwa_theme_color'      => trim($_POST['pwa_theme_color']      ?? '#0d6efd'),
        'pwa_background_color' => trim($_POST['pwa_background_color'] ?? '#ffffff'),
    ];
}

$saved = $_SESSION['install_data']['platform'] ?? [];
$f = [
    'app_name'             => $saved['app_name']             ?? 'Mail Forge',
    'company_name'         => $saved['company_name']         ?? '',
    'company_email'        => $saved['company_email']        ?? '',
    'default_language'     => $saved['default_language']     ?? 'en',
    'default_timezone'     => $saved['default_timezone']     ?? 'UTC',
    'double_opt_in'        => $saved['double_opt_in']        ?? true,
    'pwa_name'             => $saved['pwa_name']             ?? 'Mail Forge',
    'pwa_short_name'       => $saved['pwa_short_name']       ?? 'MailForge',
    'pwa_theme_color'      => $saved['pwa_theme_color']      ?? '#0d6efd',
    'pwa_background_color' => $saved['pwa_background_color'] ?? '#ffffff',
];

$languages = ['en' => 'English', 'it' => 'Italian', 'es' => 'Spanish', 'fr' => 'French', 'de' => 'German'];
$timezones = DateTimeZone::listIdentifiers();
?>

<div class="text-center mb-4">
    <div class="step-icon bg-primary bg-opacity-10 mx-auto">
        <i class="bi bi-gear text-primary"></i>
    </div>
    <h2 class="fw-bold mb-1">Platform Settings</h2>
    <p class="text-muted">Configure your Mail Forge platform identity and defaults.</p>
</div>

<form method="POST" action="index.php">
    <input type="hidden" name="action" value="next">

    <!-- General -->
    <h6 class="text-uppercase text-muted fw-semibold mb-2" style="font-size:.75rem;letter-spacing:.6px">General</h6>
    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <label class="form-label" for="app_name">Application Name</label>
            <input type="text" class="form-control" id="app_name" name="app_name"
                   value="<?= htmlspecialchars((string)$f['app_name']) ?>" required>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="company_name">Company Name</label>
            <input type="text" class="form-control" id="company_name" name="company_name"
                   value="<?= htmlspecialchars((string)$f['company_name']) ?>">
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label" for="company_email">Company Email</label>
        <input type="email" class="form-control" id="company_email" name="company_email"
               value="<?= htmlspecialchars((string)$f['company_email']) ?>"
               placeholder="admin@example.com">
    </div>

    <!-- Localisation -->
    <h6 class="text-uppercase text-muted fw-semibold mb-2 mt-4" style="font-size:.75rem;letter-spacing:.6px">Localisation</h6>
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <label class="form-label" for="default_language">Default Language</label>
            <select class="form-select" id="default_language" name="default_language">
                <?php foreach ($languages as $code => $name): ?>
                <option value="<?= $code ?>" <?= $f['default_language'] === $code ? 'selected' : '' ?>>
                    <?= htmlspecialchars($name) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-8">
            <label class="form-label" for="default_timezone">Default Timezone</label>
            <select class="form-select" id="default_timezone" name="default_timezone">
                <?php foreach ($timezones as $tz): ?>
                <option value="<?= htmlspecialchars($tz) ?>" <?= $f['default_timezone'] === $tz ? 'selected' : '' ?>>
                    <?= htmlspecialchars($tz) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <!-- Email Behaviour -->
    <h6 class="text-uppercase text-muted fw-semibold mb-2 mt-4" style="font-size:.75rem;letter-spacing:.6px">Email Behaviour</h6>
    <div class="mb-4">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="double_opt_in" name="double_opt_in"
                   value="1" <?= $f['double_opt_in'] ? 'checked' : '' ?>>
            <label class="form-check-label" for="double_opt_in">
                <strong>Enable Double Opt-In</strong>
                <div class="text-muted" style="font-size:.85rem">Subscribers receive a confirmation email before being added to lists.</div>
            </label>
        </div>
    </div>

    <!-- PWA -->
    <h6 class="text-uppercase text-muted fw-semibold mb-2 mt-4" style="font-size:.75rem;letter-spacing:.6px">Progressive Web App (PWA)</h6>
    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <label class="form-label" for="pwa_name">PWA Full Name</label>
            <input type="text" class="form-control" id="pwa_name" name="pwa_name"
                   value="<?= htmlspecialchars((string)$f['pwa_name']) ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label" for="pwa_short_name">PWA Short Name</label>
            <input type="text" class="form-control" id="pwa_short_name" name="pwa_short_name"
                   value="<?= htmlspecialchars((string)$f['pwa_short_name']) ?>">
        </div>
    </div>
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <label class="form-label" for="pwa_theme_color">Theme Color</label>
            <div class="input-group">
                <input type="color" class="form-control form-control-color" id="pwa_theme_color_picker"
                       value="<?= htmlspecialchars((string)$f['pwa_theme_color']) ?>">
                <input type="text" class="form-control" id="pwa_theme_color" name="pwa_theme_color"
                       value="<?= htmlspecialchars((string)$f['pwa_theme_color']) ?>"
                       pattern="^#[0-9A-Fa-f]{6}$">
            </div>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="pwa_background_color">Background Color</label>
            <div class="input-group">
                <input type="color" class="form-control form-control-color" id="pwa_bg_color_picker"
                       value="<?= htmlspecialchars((string)$f['pwa_background_color']) ?>">
                <input type="text" class="form-control" id="pwa_background_color" name="pwa_background_color"
                       value="<?= htmlspecialchars((string)$f['pwa_background_color']) ?>"
                       pattern="^#[0-9A-Fa-f]{6}$">
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between mt-2">
        <button type="submit" name="action" value="prev" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </button>
        <button type="submit" class="btn btn-primary">
            Next <i class="bi bi-arrow-right ms-1"></i>
        </button>
    </div>
</form>

<script>
// Sync color pickers with text inputs
function syncColorPicker(pickerId, textId) {
    const picker = document.getElementById(pickerId);
    const text   = document.getElementById(textId);
    picker.addEventListener('input', () => { text.value = picker.value; });
    text.addEventListener('input', () => {
        if (/^#[0-9A-Fa-f]{6}$/.test(text.value)) picker.value = text.value;
    });
}
syncColorPicker('pwa_theme_color_picker', 'pwa_theme_color');
syncColorPicker('pwa_bg_color_picker', 'pwa_background_color');
</script>
