<div class="text-center mb-4">
    <div class="step-icon bg-primary bg-opacity-10 mx-auto">
        <i class="bi bi-envelope-paper text-primary"></i>
    </div>
    <h2 class="fw-bold mb-2">Welcome to Mail Forge</h2>
    <p class="text-muted">The open-source email marketing platform. Let's get you set up in a few minutes.</p>
</div>

<p class="text-secondary">This installer will walk you through the following configuration steps:</p>

<ul class="list-group list-group-flush mb-4">
    <li class="list-group-item d-flex align-items-center gap-2 px-0">
        <i class="bi bi-check2-circle text-primary"></i>
        <strong>System Requirements</strong> – verify your server meets all prerequisites
    </li>
    <li class="list-group-item d-flex align-items-center gap-2 px-0">
        <i class="bi bi-database text-primary"></i>
        <strong>Database</strong> – configure MySQL connection and run migrations
    </li>
    <li class="list-group-item d-flex align-items-center gap-2 px-0">
        <i class="bi bi-link-45deg text-primary"></i>
        <strong>Site URL</strong> – set your application URL and HTTPS settings
    </li>
    <li class="list-group-item d-flex align-items-center gap-2 px-0">
        <i class="bi bi-send text-primary"></i>
        <strong>SMTP</strong> – configure outgoing email delivery
    </li>
    <li class="list-group-item d-flex align-items-center gap-2 px-0">
        <i class="bi bi-gear text-primary"></i>
        <strong>Platform Settings</strong> – name, timezone, language, PWA options
    </li>
    <li class="list-group-item d-flex align-items-center gap-2 px-0">
        <i class="bi bi-person-check text-primary"></i>
        <strong>Admin Account</strong> – create your administrator login
    </li>
</ul>

<div class="alert alert-info d-flex gap-2 align-items-start">
    <i class="bi bi-info-circle-fill mt-1 flex-shrink-0"></i>
    <div>Before you begin, make sure you have your <strong>database credentials</strong> and <strong>SMTP details</strong> ready. The process takes about 5 minutes.</div>
</div>

<form method="POST" action="index.php">
    <input type="hidden" name="action" value="next">
    <div class="d-flex justify-content-end mt-3">
        <button type="submit" class="btn btn-primary btn-lg px-5">
            Get Started <i class="bi bi-arrow-right ms-1"></i>
        </button>
    </div>
</form>
