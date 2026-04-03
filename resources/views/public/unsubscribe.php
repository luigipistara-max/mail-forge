<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unsubscribe</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: #f8f9fa; }
    </style>
</head>
<body>
<?php
/** @var array|null $contact */
/** @var array|null $list */
/** @var bool $success */
/** @var array $errors */
/** @var string $token */
$contact = $contact ?? null;
$list    = $list    ?? null;
$success = $success ?? false;
$errors  = $errors  ?? [];
$token   = $token   ?? '';
?>

<div class="min-vh-100 d-flex align-items-center justify-content-center py-5">
    <div class="w-100" style="max-width: 480px">

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4 p-md-5">

                <?php if ($success): ?>
                <!-- Success State -->
                <div class="text-center py-2">
                    <div class="bg-success bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                        style="width:4.5rem;height:4.5rem">
                        <i class="bi bi-check-circle-fill text-success" style="font-size:2.25rem"></i>
                    </div>
                    <h4 class="fw-bold mb-2">You've been unsubscribed</h4>
                    <p class="text-muted mb-4">
                        <?php if ($list): ?>
                        You have been successfully removed from
                        <strong><?= htmlspecialchars($list['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong>.
                        <?php else: ?>
                        You have been successfully unsubscribed from our mailing list.
                        <?php endif; ?>
                        You will no longer receive emails from us.
                    </p>
                    <?php if ($token): ?>
                    <form method="POST" action="/resubscribe">
                        <?= \MailForge\Helpers\CsrfHelper::field() ?>
                        <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit" class="btn btn-outline-primary btn-sm me-2">
                            <i class="bi bi-arrow-counterclockwise me-1"></i>Resubscribe
                        </button>
                    </form>
                    <?php endif; ?>
                </div>

                <?php else: ?>
                <!-- Unsubscribe Form -->
                <div class="text-center mb-4">
                    <div class="bg-warning bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                        style="width:4.5rem;height:4.5rem">
                        <i class="bi bi-envelope-dash text-warning" style="font-size:2.25rem"></i>
                    </div>
                    <h4 class="fw-bold mb-1">
                        <?php if ($list): ?>
                        Unsubscribe from <?= htmlspecialchars($list['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                        <?php else: ?>
                        Manage Your Email Preferences
                        <?php endif; ?>
                    </h4>
                    <p class="text-muted small mb-0">We're sorry to see you go.</p>
                </div>

                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger mb-4">
                    <ul class="mb-0">
                        <?php foreach ($errors as $err): ?>
                        <li><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <form method="POST" action="/unsubscribe">
                    <?= \MailForge\Helpers\CsrfHelper::field() ?>
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">

                    <div class="mb-4">
                        <label for="email" class="form-label fw-semibold">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control"
                            value="<?= htmlspecialchars($contact['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            <?= $contact ? 'readonly' : 'required' ?>>
                        <?php if ($contact): ?>
                        <div class="form-text">This is the email address associated with your subscription.</div>
                        <?php endif; ?>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-danger fw-semibold">
                            <i class="bi bi-envelope-x me-2"></i>Confirm Unsubscribe
                        </button>
                        <a href="/" class="btn btn-outline-secondary">
                            Cancel – Keep Me Subscribed
                        </a>
                    </div>
                </form>
                <?php endif; ?>

            </div>
        </div>

        <p class="text-center text-muted small mt-4 mb-0">
            You can also manage all your preferences in the
            <?php if ($token): ?>
            <a href="/preferences?token=<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none">
                preferences center
            </a>.
            <?php else: ?>
            preferences center.
            <?php endif; ?>
        </p>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
