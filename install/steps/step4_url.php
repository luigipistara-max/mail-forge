<?php
// Auto-detect app URL
function detectAppUrl(): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $path   = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
    return $scheme . '://' . $host . ($path !== '/' ? $path : '');
}

// Persist form when moving forward via next action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['app_url']) && ($_POST['action'] ?? '') === 'next') {
    $_SESSION['install_data']['url'] = [
        'app_url'     => trim($_POST['app_url']     ?? ''),
        'force_https' => !empty($_POST['force_https']),
    ];
}

$saved = $_SESSION['install_data']['url'] ?? [];
$f = [
    'app_url'     => $saved['app_url']     ?? detectAppUrl(),
    'force_https' => $saved['force_https'] ?? false,
];
?>

<div class="text-center mb-4">
    <div class="step-icon bg-primary bg-opacity-10 mx-auto">
        <i class="bi bi-link-45deg text-primary"></i>
    </div>
    <h2 class="fw-bold mb-1">Site URL</h2>
    <p class="text-muted">Set the base URL your application will be accessed from.</p>
</div>

<form method="POST" action="index.php">
    <input type="hidden" name="action" value="next">

    <div class="mb-4">
        <label class="form-label" for="app_url">Application URL</label>
        <input type="url" class="form-control form-control-lg" id="app_url" name="app_url"
               value="<?= htmlspecialchars((string)$f['app_url']) ?>"
               placeholder="https://example.com" required>
        <div class="form-text">
            The URL visitors use to access Mail Forge. No trailing slash.
            Auto-detected from your server: <code><?= htmlspecialchars(detectAppUrl()) ?></code>
        </div>
    </div>

    <div class="mb-4">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="force_https" name="force_https"
                   value="1" <?= $f['force_https'] ? 'checked' : '' ?>>
            <label class="form-check-label" for="force_https">
                <strong>Force HTTPS</strong>
                <div class="text-muted" style="font-size:.85rem">Redirect all HTTP traffic to HTTPS. Enable only if you have a valid SSL certificate.</div>
            </label>
        </div>
    </div>

    <div class="alert alert-info d-flex gap-2 align-items-start">
        <i class="bi bi-info-circle-fill mt-1 flex-shrink-0"></i>
        <div>Make sure the URL matches your web server's virtual host configuration. Using the wrong URL may prevent you from logging in.</div>
    </div>

    <div class="d-flex justify-content-between mt-3">
        <button type="submit" name="action" value="prev" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </button>
        <button type="submit" class="btn btn-primary">
            Next <i class="bi bi-arrow-right ms-1"></i>
        </button>
    </div>
</form>
