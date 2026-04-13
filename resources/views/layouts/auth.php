<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Mail Forge - Email Marketing Platform">
    <meta name="theme-color" content="#0d6efd">
    <meta name="csrf-token" content="<?= htmlspecialchars(\MailForge\Helpers\CsrfHelper::getToken(), ENT_QUOTES, 'UTF-8') ?>">
    <title><?= htmlspecialchars($pageTitle ?? 'Mail Forge', ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_PATH ?>/assets/css/app.css">
    <style>
        body {
            background: linear-gradient(135deg, #e8f0fe 0%, #f8f9fa 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .auth-wrapper { width: 100%; max-width: 440px; padding: 1rem; }
        .auth-logo {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .auth-logo a {
            text-decoration: none;
            color: #1a1f2e;
            font-size: 1.75rem;
            font-weight: 700;
        }
        .auth-logo a span { color: #0d6efd; }
        .auth-card {
            background: #fff;
            border-radius: 1rem;
            box-shadow: 0 8px 32px rgba(13,110,253,.12);
            padding: 2rem;
        }
        footer { text-align: center; margin-top: 1rem; font-size: .8rem; color: #6c757d; }
    </style>
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-logo">
        <a href="<?= BASE_PATH ?>/"><i class="bi bi-envelope-paper-fill me-2"></i>Mail <span>Forge</span></a>
    </div>

    <?php
    $flash = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    $flashTypes = ['success' => 'success', 'error' => 'danger', 'warning' => 'warning', 'info' => 'info'];
    foreach ($flashTypes as $key => $bsClass):
        if (!empty($flash[$key])):
    ?>
    <div class="alert alert-<?= $bsClass ?> alert-dismissible fade show" role="alert">
        <i class="bi bi-<?= $bsClass === 'danger' ? 'exclamation-circle' : ($bsClass === 'success' ? 'check-circle' : 'info-circle') ?> me-2"></i>
        <?= htmlspecialchars($flash[$key], ENT_QUOTES, 'UTF-8') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php
        endif;
    endforeach;
    ?>

    <div class="auth-card">
        <?= $content ?? '' ?>
    </div>

    <footer>&copy; <?= date('Y') ?> Mail Forge &mdash; Version <?= htmlspecialchars(defined('APP_VERSION') ? APP_VERSION : '1.0.0', ENT_QUOTES, 'UTF-8') ?></footer>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_PATH ?>/assets/js/app.js"></script>
</body>
</html>
