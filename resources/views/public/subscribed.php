<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscribed</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>body { background: #f8f9fa; }</style>
</head>
<body>
<?php
/** @var array $list */
/** @var bool $doubleOptin */
$list        = $list        ?? [];
$doubleOptin = $doubleOptin ?? false;
?>
<div class="min-vh-100 d-flex align-items-center justify-content-center py-5">
    <div class="text-center" style="max-width:480px;padding:1rem">
        <?php if ($doubleOptin): ?>
        <div class="mb-4">
            <i class="bi bi-envelope-check text-primary" style="font-size:4rem;"></i>
        </div>
        <h2 class="fw-bold mb-2">Check Your Email</h2>
        <p class="text-muted">
            We've sent a confirmation email to verify your subscription to
            <strong><?= htmlspecialchars($list['name'] ?? 'our list', ENT_QUOTES, 'UTF-8') ?></strong>.
            Please click the link in that email to complete your subscription.
        </p>
        <?php else: ?>
        <div class="mb-4">
            <i class="bi bi-check-circle-fill text-success" style="font-size:4rem;"></i>
        </div>
        <h2 class="fw-bold mb-2">You're Subscribed!</h2>
        <p class="text-muted">
            You have successfully subscribed to
            <strong><?= htmlspecialchars($list['name'] ?? 'our list', ENT_QUOTES, 'UTF-8') ?></strong>.
        </p>
        <?php endif; ?>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
