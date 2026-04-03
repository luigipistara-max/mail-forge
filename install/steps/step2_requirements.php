<?php
// Requirements check logic
$rootBase = dirname(__DIR__);

$phpOk = version_compare(PHP_VERSION, '8.3.0', '>=');

$extensions = ['pdo', 'pdo_mysql', 'openssl', 'mbstring', 'json', 'fileinfo', 'curl', 'gd'];
$extResults = [];
foreach ($extensions as $ext) {
    $extResults[$ext] = extension_loaded($ext);
}

$writableDirs = [
    'storage/'         => $rootBase . '/storage',
    'storage/logs/'    => $rootBase . '/storage/logs',
    'storage/cache/'   => $rootBase . '/storage/cache',
    'storage/uploads/' => $rootBase . '/storage/uploads',
    'public/'          => $rootBase . '/public',
];
$dirResults = [];
foreach ($writableDirs as $label => $path) {
    $dirResults[$label] = is_dir($path) && is_writable($path);
}

$allPassed = $phpOk
    && !in_array(false, $extResults, true)
    && !in_array(false, $dirResults, true);
?>

<div class="text-center mb-4">
    <div class="step-icon <?= $allPassed ? 'bg-success' : 'bg-danger' ?> bg-opacity-10 mx-auto">
        <i class="bi bi-clipboard2-check <?= $allPassed ? 'text-success' : 'text-danger' ?>"></i>
    </div>
    <h2 class="fw-bold mb-1">System Requirements</h2>
    <p class="text-muted">Checking that your server meets all prerequisites.</p>
</div>

<!-- PHP Version -->
<h6 class="text-uppercase text-muted fw-semibold mb-2" style="font-size:.75rem;letter-spacing:.6px">PHP Version</h6>
<div class="card mb-3">
    <div class="card-body p-0">
        <div class="check-item px-3">
            <?php if ($phpOk): ?>
                <i class="bi bi-check-circle-fill text-success"></i>
            <?php else: ?>
                <i class="bi bi-x-circle-fill text-danger"></i>
            <?php endif; ?>
            <span class="flex-grow-1">PHP &ge; 8.3.0</span>
            <span class="badge <?= $phpOk ? 'bg-success' : 'bg-danger' ?>"><?= PHP_VERSION ?></span>
        </div>
    </div>
</div>

<!-- PHP Extensions -->
<h6 class="text-uppercase text-muted fw-semibold mb-2" style="font-size:.75rem;letter-spacing:.6px">PHP Extensions</h6>
<div class="card mb-3">
    <div class="card-body p-0">
        <?php foreach ($extResults as $ext => $loaded): ?>
        <div class="check-item px-3">
            <?php if ($loaded): ?>
                <i class="bi bi-check-circle-fill text-success"></i>
            <?php else: ?>
                <i class="bi bi-x-circle-fill text-danger"></i>
            <?php endif; ?>
            <span class="flex-grow-1"><code><?= htmlspecialchars($ext) ?></code></span>
            <span class="badge <?= $loaded ? 'bg-success' : 'bg-danger' ?>"><?= $loaded ? 'Loaded' : 'Missing' ?></span>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Writable Directories -->
<h6 class="text-uppercase text-muted fw-semibold mb-2" style="font-size:.75rem;letter-spacing:.6px">Writable Directories</h6>
<div class="card mb-4">
    <div class="card-body p-0">
        <?php foreach ($dirResults as $label => $ok): ?>
        <div class="check-item px-3">
            <?php if ($ok): ?>
                <i class="bi bi-check-circle-fill text-success"></i>
            <?php else: ?>
                <i class="bi bi-x-circle-fill text-danger"></i>
            <?php endif; ?>
            <span class="flex-grow-1"><code><?= htmlspecialchars($label) ?></code></span>
            <span class="badge <?= $ok ? 'bg-success' : 'bg-danger' ?>"><?= $ok ? 'Writable' : 'Not Writable' ?></span>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php if ($allPassed): ?>
    <div class="alert alert-success d-flex gap-2 align-items-center">
        <i class="bi bi-check-circle-fill"></i>
        <strong>All requirements met!</strong> You can proceed to the next step.
    </div>
<?php else: ?>
    <div class="alert alert-danger d-flex gap-2 align-items-center">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <div><strong>Fix the issues above</strong> before proceeding. After fixing, refresh this page.</div>
    </div>
<?php endif; ?>

<form method="POST" action="index.php">
    <input type="hidden" name="action" value="next">
    <div class="d-flex justify-content-between mt-2">
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
        <button type="submit" class="btn btn-primary" <?= $allPassed ? '' : 'disabled' ?>>
            Next <i class="bi bi-arrow-right ms-1"></i>
        </button>
    </div>
</form>
