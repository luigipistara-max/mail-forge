<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Subscription</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: #f8f9fa; }
    </style>
</head>
<body>
<?php
/** @var bool $success */
/** @var string $message */
/** @var array|null $list */
$success = $success ?? false;
$message = $message ?? '';
$list    = $list    ?? null;
?>

<div class="min-vh-100 d-flex align-items-center justify-content-center py-5">
    <div class="w-100" style="max-width: 480px">

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4 p-md-5 text-center">

                <?php if ($success): ?>
                <!-- Success -->
                <div class="bg-success bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-4"
                    style="width:5rem;height:5rem">
                    <i class="bi bi-check-circle-fill text-success" style="font-size:2.5rem"></i>
                </div>
                <h4 class="fw-bold mb-2">Subscription Confirmed!</h4>
                <p class="text-muted mb-1">
                    <?php if ($message): ?>
                    <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
                    <?php else: ?>
                    Your email address has been confirmed and you're now subscribed.
                    <?php endif; ?>
                </p>
                <?php if ($list): ?>
                <p class="text-muted small mb-4">
                    You are now subscribed to <strong><?= htmlspecialchars($list['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong>.
                </p>
                <?php else: ?>
                <p class="mb-4"></p>
                <?php endif; ?>

                <?php else: ?>
                <!-- Error -->
                <div class="bg-danger bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-4"
                    style="width:5rem;height:5rem">
                    <i class="bi bi-x-circle-fill text-danger" style="font-size:2.5rem"></i>
                </div>
                <h4 class="fw-bold mb-2">Confirmation Failed</h4>
                <p class="text-muted mb-4">
                    <?php if ($message): ?>
                    <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
                    <?php else: ?>
                    This confirmation link is invalid or has expired.
                    Please try subscribing again.
                    <?php endif; ?>
                </p>
                <?php endif; ?>

                <a href="/" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-house me-1"></i>Back to Website
                </a>

            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
