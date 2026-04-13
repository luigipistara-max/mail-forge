<?php
$basePath = defined('BASE_PATH') ? BASE_PATH : '';
$navCards = [
    [
        'href'        => $basePath . '/settings/general',
        'icon'        => 'bi-sliders',
        'color'       => 'primary',
        'title'       => 'General',
        'description' => 'App name, company info, timezone and locale.',
    ],
    [
        'href'        => $basePath . '/settings/email',
        'icon'        => 'bi-envelope-at',
        'color'       => 'info',
        'title'       => 'Email',
        'description' => 'Default sender, reply-to and batch sending options.',
    ],
    [
        'href'        => $basePath . '/settings/pwa',
        'icon'        => 'bi-phone',
        'color'       => 'success',
        'title'       => 'PWA',
        'description' => 'Progressive Web App name, colors and manifest.',
    ],
    [
        'href'        => $basePath . '/settings/security',
        'icon'        => 'bi-shield-lock',
        'color'       => 'danger',
        'title'       => 'Security',
        'description' => 'Login limits, session timeout and password policy.',
    ],
    [
        'href'        => $basePath . '/settings/queue',
        'icon'        => 'bi-stack',
        'color'       => 'warning',
        'title'       => 'Queue',
        'description' => 'Batch sizes, retry attempts and failure thresholds.',
    ],
    [
        'href'        => $basePath . '/smtp-servers',
        'icon'        => 'bi-envelope-arrow-up',
        'color'       => 'secondary',
        'title'       => 'SMTP Servers',
        'description' => 'Manage outbound SMTP connections and rate limits.',
    ],
];
?>

<div class="mb-4">
    <h1 class="h3 mb-1 fw-bold">Settings</h1>
    <p class="text-muted mb-0">Configure all aspects of Mail Forge from one place.</p>
</div>

<div class="row g-4">
    <?php foreach ($navCards as $card): ?>
    <div class="col-sm-6 col-lg-4">
        <a href="<?= htmlspecialchars($card['href'], ENT_QUOTES, 'UTF-8') ?>"
            class="card border-0 shadow-sm h-100 text-decoration-none text-reset settings-nav-card">
            <div class="card-body d-flex align-items-start gap-3 py-4">
                <div class="flex-shrink-0 d-flex align-items-center justify-content-center rounded-3 bg-<?= $card['color'] ?> bg-opacity-10 p-3">
                    <i class="bi <?= $card['icon'] ?> fs-4 text-<?= $card['color'] ?>"></i>
                </div>
                <div>
                    <div class="fw-semibold mb-1"><?= htmlspecialchars($card['title'], ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="small text-muted"><?= htmlspecialchars($card['description'], ENT_QUOTES, 'UTF-8') ?></div>
                </div>
                <i class="bi bi-chevron-right ms-auto text-muted align-self-center"></i>
            </div>
        </a>
    </div>
    <?php endforeach; ?>
</div>

<style>
.settings-nav-card { transition: box-shadow .15s, transform .15s; }
.settings-nav-card:hover { box-shadow: 0 .5rem 1.5rem rgba(0,0,0,.12) !important; transform: translateY(-2px); }
</style>
