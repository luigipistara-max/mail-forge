<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Mail Forge - Email Marketing Platform">
    <meta name="theme-color" content="#0d6efd">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Mail Forge">
    <meta name="csrf-token" content="<?= htmlspecialchars(\MailForge\Helpers\CsrfHelper::getToken(), ENT_QUOTES, 'UTF-8') ?>">
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/assets/img/icon-192.png">
    <title><?= htmlspecialchars($pageTitle ?? 'Mail Forge', ENT_QUOTES, 'UTF-8') ?> - Mail Forge</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/assets/css/app.css">
    <style>
        :root { --sidebar-width: 260px; }
        body { background-color: #f8f9fa; }
        #sidebar {
            width: var(--sidebar-width);
            min-height: 100vh;
            background: linear-gradient(180deg, #1a1f2e 0%, #252b3b 100%);
            position: fixed;
            top: 0; left: 0;
            z-index: 1000;
            transition: transform .3s ease;
            overflow-y: auto;
        }
        #sidebar .brand {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,.1);
        }
        #sidebar .brand a {
            color: #fff;
            text-decoration: none;
            font-size: 1.3rem;
            font-weight: 700;
            letter-spacing: .5px;
        }
        #sidebar .brand a span { color: #0d6efd; }
        #sidebar .nav-link {
            color: rgba(255,255,255,.7);
            padding: .6rem 1.5rem;
            border-radius: 0;
            display: flex;
            align-items: center;
            gap: .6rem;
            font-size: .9rem;
            transition: background .2s, color .2s;
        }
        #sidebar .nav-link:hover,
        #sidebar .nav-link.active {
            color: #fff;
            background: rgba(13,110,253,.25);
        }
        #sidebar .nav-link.active { border-left: 3px solid #0d6efd; }
        #sidebar .nav-section {
            color: rgba(255,255,255,.35);
            font-size: .7rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 1rem 1.5rem .3rem;
        }
        #main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            transition: margin .3s ease;
        }
        #topbar {
            background: #fff;
            border-bottom: 1px solid #e9ecef;
            padding: .75rem 1.5rem;
            position: sticky;
            top: 0;
            z-index: 999;
        }
        .content-area { flex: 1; padding: 1.5rem; }
        footer { background: #fff; border-top: 1px solid #e9ecef; padding: .75rem 1.5rem; }
        @media (max-width: 991.98px) {
            #sidebar { transform: translateX(-100%); }
            #sidebar.show { transform: translateX(0); }
            #main-content { margin-left: 0; }
            .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 999; }
            .sidebar-overlay.show { display: block; }
        }
    </style>
</head>
<body>

<?php
$currentUri = $_SERVER['REQUEST_URI'] ?? '/';
$navItems = [
    ['href' => '/dashboard',    'icon' => 'bi-speedometer2',    'label' => 'Dashboard'],
    ['href' => '/contacts',     'icon' => 'bi-people',          'label' => 'Contacts'],
    ['href' => '/lists',        'icon' => 'bi-list-ul',         'label' => 'Lists'],
    ['href' => '/segments',     'icon' => 'bi-funnel',          'label' => 'Segments'],
    ['href' => '/templates',    'icon' => 'bi-file-earmark-text','label' => 'Templates'],
    ['href' => '/campaigns',    'icon' => 'bi-megaphone',       'label' => 'Campaigns'],
    ['href' => '/automations',  'icon' => 'bi-diagram-3',       'label' => 'Automations'],
    ['href' => '/reports',      'icon' => 'bi-bar-chart-line',  'label' => 'Reports'],
    ['href' => '/smtp-servers', 'icon' => 'bi-server',         'label' => 'SMTP Servers'],
    ['href' => '/settings',     'icon' => 'bi-gear',            'label' => 'Settings'],
];
?>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<nav id="sidebar">
    <div class="brand">
        <a href="/dashboard"><i class="bi bi-envelope-paper-fill me-2"></i>Mail <span>Forge</span></a>
    </div>
    <ul class="nav flex-column py-2">
        <?php foreach ($navItems as $item):
            $active = str_starts_with($currentUri, $item['href']);
        ?>
        <li class="nav-item">
            <a class="nav-link <?= $active ? 'active' : '' ?>" href="<?= htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8') ?>">
                <i class="bi <?= htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
                <?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>
</nav>

<div id="main-content">
    <nav id="topbar" class="d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-sm btn-outline-secondary d-lg-none" id="sidebarToggle">
                <i class="bi bi-list fs-5"></i>
            </button>
            <h5 class="mb-0 fw-semibold text-dark"><?= htmlspecialchars($pageTitle ?? 'Dashboard', ENT_QUOTES, 'UTF-8') ?></h5>
        </div>
        <div class="d-flex align-items-center gap-3">
            <a href="/notifications" class="btn btn-sm btn-outline-secondary position-relative" title="Notifications">
                <i class="bi bi-bell"></i>
                <?php if (!empty($unreadNotifications)): ?>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:.6rem;">
                    <?= (int)$unreadNotifications ?>
                </span>
                <?php endif; ?>
            </a>
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle"></i>
                    <span><?= htmlspecialchars($currentUser['name'] ?? 'User', ENT_QUOTES, 'UTF-8') ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><h6 class="dropdown-header"><?= htmlspecialchars($currentUser['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></h6></li>
                    <li><a class="dropdown-item" href="/profile"><i class="bi bi-person me-2"></i>Profile</a></li>
                    <li><a class="dropdown-item" href="/settings"><i class="bi bi-gear me-2"></i>Settings</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form action="/logout" method="POST" class="d-inline">
                            <?= \MailForge\Helpers\CsrfHelper::field() ?>
                            <button type="submit" class="dropdown-item text-danger">
                                <i class="bi bi-box-arrow-right me-2"></i>Sign Out
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="content-area">
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

        <?= $content ?? '' ?>
    </div>

    <footer class="d-flex align-items-center justify-content-between text-muted small">
        <span>&copy; <?= date('Y') ?> Mail Forge</span>
        <span>Version <?= htmlspecialchars(defined('APP_VERSION') ? APP_VERSION : '1.0.0', ENT_QUOTES, 'UTF-8') ?></span>
    </footer>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/app.js"></script>
<script>
(function () {
    const toggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    if (toggle && sidebar && overlay) {
        toggle.addEventListener('click', () => {
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');
        });
        overlay.addEventListener('click', () => {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        });
    }

    // PWA install prompt
    let deferredPrompt;
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;
        const btn = document.getElementById('pwaInstallBtn');
        if (btn) {
            btn.classList.remove('d-none');
            btn.addEventListener('click', () => {
                deferredPrompt.prompt();
                deferredPrompt.userChoice.then(() => { deferredPrompt = null; btn.classList.add('d-none'); });
            });
        }
    });

    // Service worker registration
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js').catch(() => {});
    }

    // CSRF token helper for fetch
    window.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
})();
</script>
</body>
</html>
