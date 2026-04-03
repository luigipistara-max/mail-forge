<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($subject ?? '', ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <style>
        body {
            background: #ffffff;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: #212529;
        }
        .email-wrapper {
            max-width: 680px;
            margin: 0 auto;
        }
        .email-topbar {
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }
        .email-footer {
            background: #f8f9fa;
            border-top: 1px solid #dee2e6;
            color: #6c757d;
            font-size: .8rem;
        }
    </style>
</head>
<body>
<?php
/** @var array $campaign */
/** @var string $body */
/** @var string $subject */
$campaign = $campaign ?? [];
$body     = $body     ?? '';
$subject  = $subject  ?? $campaign['subject'] ?? '';

$unsubscribeUrl = $campaign['unsubscribe_url'] ?? '#';
$webviewUrl     = $campaign['webview_url']     ?? '#';
?>

<!-- Top bar -->
<div class="email-topbar py-2 px-3">
    <div class="email-wrapper d-flex align-items-center justify-content-between">
        <span class="small text-muted">
            <i class="bi bi-globe2 me-1"></i>Viewing in browser
        </span>
        <?php if (!empty($campaign['sent_at'])): ?>
        <span class="small text-muted">
            Sent <?= htmlspecialchars($campaign['sent_at'], ENT_QUOTES, 'UTF-8') ?>
        </span>
        <?php endif; ?>
    </div>
</div>

<!-- Subject -->
<div class="py-3 px-3 border-bottom">
    <div class="email-wrapper">
        <h5 class="fw-semibold mb-0"><?= htmlspecialchars($subject, ENT_QUOTES, 'UTF-8') ?></h5>
        <?php if (!empty($campaign['from_name']) || !empty($campaign['from_email'])): ?>
        <p class="text-muted small mb-0 mt-1">
            From:
            <?php if (!empty($campaign['from_name'])): ?>
            <strong><?= htmlspecialchars($campaign['from_name'], ENT_QUOTES, 'UTF-8') ?></strong>
            <?php endif; ?>
            <?php if (!empty($campaign['from_email'])): ?>
            &lt;<?= htmlspecialchars($campaign['from_email'], ENT_QUOTES, 'UTF-8') ?>&gt;
            <?php endif; ?>
        </p>
        <?php endif; ?>
    </div>
</div>

<!-- Email Body -->
<div class="py-4 px-3">
    <div class="email-wrapper">
        <?= $body ?>
    </div>
</div>

<!-- Footer -->
<div class="email-footer py-3 px-3">
    <div class="email-wrapper text-center">
        <p class="mb-1">
            You are receiving this email because you subscribed to our mailing list.
        </p>
        <p class="mb-0">
            <a href="<?= htmlspecialchars($webviewUrl, ENT_QUOTES, 'UTF-8') ?>" class="text-muted me-3">
                View in browser
            </a>
            <a href="<?= htmlspecialchars($unsubscribeUrl, ENT_QUOTES, 'UTF-8') ?>" class="text-muted">
                Unsubscribe
            </a>
        </p>
        <?php if (!empty($campaign['name'])): ?>
        <p class="mb-0 mt-1">
            Campaign: <?= htmlspecialchars($campaign['name'], ENT_QUOTES, 'UTF-8') ?>
        </p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
