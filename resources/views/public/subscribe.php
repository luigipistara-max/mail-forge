<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscribe<?= !empty($list['name']) ? ' – ' . htmlspecialchars($list['name'], ENT_QUOTES, 'UTF-8') : '' ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: #f8f9fa; }
        .brand-logo { font-size: 1.25rem; font-weight: 700; letter-spacing: -.5px; }
    </style>
</head>
<body>
<?php
/** @var array $list */
/** @var array $errors */
/** @var bool $success */
/** @var array $settings */
$list     = $list     ?? [];
$errors   = $errors   ?? [];
$success  = $success  ?? false;
$settings = $settings ?? [];
?>

<div class="min-vh-100 d-flex align-items-center justify-content-center py-5">
    <div class="w-100" style="max-width: 480px">

        <!-- Branding -->
        <div class="text-center mb-4">
            <div class="brand-logo text-primary">
                <i class="bi bi-envelope-paper-fill me-1"></i>
                <?= htmlspecialchars($settings['app_name'] ?? 'Mail Forge', ENT_QUOTES, 'UTF-8') ?>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4 p-md-5">

                <?php if ($success): ?>
                <!-- Success State -->
                <div class="text-center py-3">
                    <div class="bg-success bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                        style="width:4rem;height:4rem">
                        <i class="bi bi-check-circle-fill text-success fs-2"></i>
                    </div>
                    <h4 class="fw-bold mb-2">You're subscribed!</h4>
                    <?php if (!empty($list['double_optin'])): ?>
                    <p class="text-muted mb-0">
                        We've sent a confirmation email to your inbox.
                        Please check your email and click the confirmation link to complete your subscription.
                    </p>
                    <?php else: ?>
                    <p class="text-muted mb-0">
                        You've been successfully subscribed to
                        <strong><?= htmlspecialchars($list['name'] ?? 'the list', ENT_QUOTES, 'UTF-8') ?></strong>.
                    </p>
                    <?php endif; ?>
                </div>

                <?php else: ?>
                <!-- Subscription Form -->
                <div class="text-center mb-4">
                    <h4 class="fw-bold mb-1"><?= htmlspecialchars($list['name'] ?? 'Subscribe', ENT_QUOTES, 'UTF-8') ?></h4>
                    <?php if (!empty($list['description'])): ?>
                    <p class="text-muted small mb-0"><?= htmlspecialchars($list['description'], ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>
                </div>

                <?php if (!empty($errors) && isset($errors[0])): ?>
                <div class="alert alert-danger mb-4">
                    <ul class="mb-0">
                        <?php foreach ($errors as $err): ?>
                        <li><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <form method="POST" action="/subscribe/<?= htmlspecialchars($list['slug'] ?? $list['id'] ?? '', ENT_QUOTES, 'UTF-8') ?>" novalidate>
                    <?= \MailForge\Helpers\CsrfHelper::field() ?>

                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                        <input type="email" id="email" name="email"
                            class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                            value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            placeholder="you@example.com" required autocomplete="email" autofocus>
                        <?php if (isset($errors['email'])): ?>
                        <div class="invalid-feedback"><?= htmlspecialchars($errors['email'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label for="first_name" class="form-label fw-semibold">First Name</label>
                            <input type="text" id="first_name" name="first_name" class="form-control"
                                value="<?= htmlspecialchars($_POST['first_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                placeholder="Jane">
                        </div>
                        <div class="col-sm-6">
                            <label for="last_name" class="form-label fw-semibold">Last Name</label>
                            <input type="text" id="last_name" name="last_name" class="form-control"
                                value="<?= htmlspecialchars($_POST['last_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                placeholder="Doe">
                        </div>
                    </div>

                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-primary fw-semibold">
                            <i class="bi bi-envelope-check me-2"></i>Subscribe
                        </button>
                    </div>

                    <p class="text-muted text-center small mb-0">
                        By subscribing you agree to receive emails from us.
                        You can unsubscribe at any time.
                        <?php if (!empty($settings['privacy_policy_url'])): ?>
                        <a href="<?= htmlspecialchars($settings['privacy_policy_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">
                            Privacy Policy
                        </a>
                        <?php endif; ?>
                    </p>
                </form>
                <?php endif; ?>

            </div>
        </div>

        <p class="text-center text-muted small mt-4 mb-0">
            Powered by
            <a href="#" class="text-decoration-none fw-semibold">
                <?= htmlspecialchars($settings['app_name'] ?? 'Mail Forge', ENT_QUOTES, 'UTF-8') ?>
            </a>
        </p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
