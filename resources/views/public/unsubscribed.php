<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unsubscribed</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>body { background: #f8f9fa; }</style>
</head>
<body>
<?php
/** @var array|null $contact */
$contact = $contact ?? null;
?>
<div class="min-vh-100 d-flex align-items-center justify-content-center py-5">
    <div class="text-center" style="max-width:480px;padding:1rem">
        <div class="mb-4">
            <i class="bi bi-envelope-x text-secondary" style="font-size:4rem;"></i>
        </div>
        <h2 class="fw-bold mb-2">You've Been Unsubscribed</h2>
        <p class="text-muted">
            <?php if ($contact !== null): ?>
            <strong><?= htmlspecialchars($contact['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong> has been
            <?php endif; ?>
            successfully removed from our mailing list. You will no longer receive emails from us.
        </p>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
