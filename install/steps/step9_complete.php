<?php
$appUrl = rtrim($_SESSION['install_data']['url']['app_url'] ?? '..', '/');
?>

<div class="text-center mb-4">
    <div class="step-icon bg-success bg-opacity-10 mx-auto" style="width:72px;height:72px;font-size:2rem">
        <i class="bi bi-check2-circle text-success"></i>
    </div>
    <h2 class="fw-bold mb-1 text-success">Mail Forge Installed Successfully!</h2>
    <p class="text-muted">Your platform is ready. Follow the steps below to finalise your setup.</p>
</div>

<!-- Login link -->
<div class="card border-success mb-4">
    <div class="card-body d-flex align-items-center gap-3">
        <i class="bi bi-box-arrow-in-right text-success fs-3"></i>
        <div>
            <div class="fw-semibold">Log In to Mail Forge</div>
            <div class="text-muted small">Use the admin credentials you configured in the previous step.</div>
        </div>
        <a href="<?= htmlspecialchars($appUrl) ?>/public/index.php" class="btn btn-success ms-auto text-nowrap">
            Go to Login <i class="bi bi-arrow-right ms-1"></i>
        </a>
    </div>
</div>

<!-- Cron job instructions -->
<h6 class="text-uppercase text-muted fw-semibold mb-2" style="font-size:.75rem;letter-spacing:.6px">
    <i class="bi bi-clock me-1"></i> Cron Job Setup
</h6>
<p class="small text-muted mb-2">Add these entries to your server crontab (<code>crontab -e</code>) to enable scheduled sending, automation processing, and queue workers:</p>
<div class="bg-dark text-light rounded p-3 mb-3 small font-monospace" style="font-size:.8rem;overflow-x:auto">
    <div class="text-success mb-1"># Mail Forge – scheduled tasks (run every minute)</div>
    <div>* * * * * <?= PHP_BINARY ?> <?= htmlspecialchars(dirname(INSTALL_BASE)) ?>/artisan schedule:run >> /dev/null 2>&1</div>
    <br>
    <div class="text-success mb-1"># Mail Forge – queue worker</div>
    <div>* * * * * <?= PHP_BINARY ?> <?= htmlspecialchars(dirname(INSTALL_BASE)) ?>/artisan queue:work --sleep=3 --tries=3 --max-time=60 >> /dev/null 2>&1</div>
</div>
<p class="small text-muted mb-4">If you use a shared hosting control panel (cPanel / DirectAdmin), add the commands in the Cron Jobs section and set the frequency to <em>every minute</em>.</p>

<!-- Security reminder -->
<h6 class="text-uppercase text-muted fw-semibold mb-2" style="font-size:.75rem;letter-spacing:.6px">
    <i class="bi bi-shield-lock me-1"></i> Security Checklist
</h6>
<div class="list-group list-group-flush mb-4 small">
    <div class="list-group-item list-group-item-warning d-flex gap-2 align-items-start px-0">
        <i class="bi bi-exclamation-triangle-fill text-warning mt-1 flex-shrink-0"></i>
        <div>
            <strong>Remove or protect the <code>install/</code> directory.</strong><br>
            Anyone who can reach this installer could reconfigure or wipe your installation.
            <br>Run: <code class="user-select-all">rm -rf <?= htmlspecialchars(INSTALL_BASE) ?></code>
            <br>Or restrict access in your web server config / <code>.htaccess</code>.
        </div>
    </div>
    <div class="list-group-item d-flex gap-2 align-items-start px-0">
        <i class="bi bi-lock-fill text-primary mt-1 flex-shrink-0"></i>
        <div>
            <strong>Set correct permissions on <code>storage/</code></strong><br>
            <code class="user-select-all">chmod -R 775 <?= htmlspecialchars(dirname(INSTALL_BASE)) ?>/storage</code><br>
            <code class="user-select-all">chown -R www-data:www-data <?= htmlspecialchars(dirname(INSTALL_BASE)) ?>/storage</code>
        </div>
    </div>
    <div class="list-group-item d-flex gap-2 align-items-start px-0">
        <i class="bi bi-file-earmark-lock-fill text-primary mt-1 flex-shrink-0"></i>
        <div>
            <strong>Protect your <code>.env</code> file</strong><br>
            Ensure your web server does not serve the <code>.env</code> file publicly. The included <code>.htaccess</code> in the project root already denies access.
        </div>
    </div>
    <div class="list-group-item d-flex gap-2 align-items-start px-0">
        <i class="bi bi-arrow-repeat text-primary mt-1 flex-shrink-0"></i>
        <div>
            <strong>Keep Mail Forge updated</strong><br>
            Watch the <a href="https://github.com/mail-forge/mail-forge/releases" target="_blank" rel="noopener">releases page</a> for security patches and new features.
        </div>
    </div>
</div>

<!-- Clear session -->
<?php session_destroy(); ?>
<div class="text-center text-muted small mt-2">
    <i class="bi bi-envelope-paper-fill text-primary me-1"></i>
    Thank you for choosing <strong>Mail Forge</strong>.
</div>
