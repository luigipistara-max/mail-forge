<?php

declare(strict_types=1);

/**
 * MailForge — Application Entry Point
 *
 * Bootstraps the application, starts the session, and renders a status page.
 */

// ----------------------------------------------------------------
// Bootstrap
// ----------------------------------------------------------------

define('ROOT_DIR', dirname(__DIR__));

require_once ROOT_DIR . '/src/autoload.php';
require_once ROOT_DIR . '/config/constants.php';
require_once ROOT_DIR . '/config/app.php';
require_once ROOT_DIR . '/config/database.php';

use MailForge\Helpers\Sanitizer;

// ----------------------------------------------------------------
// Session
// ----------------------------------------------------------------

/** @var array<string, mixed> $appConfig */
$sessionCfg = $appConfig['session'];

session_name($sessionCfg['name']);
session_set_cookie_params([
    'lifetime' => $sessionCfg['lifetime'],
    'secure'   => $sessionCfg['secure'],
    'httponly' => $sessionCfg['http_only'],
    'samesite' => $sessionCfg['same_site'],
]);
session_start();

// ----------------------------------------------------------------
// Database status check
// ----------------------------------------------------------------

$dbStatus = 'unknown';
$dbError  = '';

try {
    $pdo      = getDbConnection();
    $dbStatus = 'connected';
} catch (\PDOException $e) {
    $dbStatus = 'error';
    $dbError  = $e->getMessage();
}

// ----------------------------------------------------------------
// Render status page
// ----------------------------------------------------------------

$appName = Sanitizer::string($appConfig['name']);
$appEnv  = Sanitizer::string($appConfig['env']);
$appUrl  = Sanitizer::url($appConfig['url']);
$phpVer  = PHP_VERSION;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $appName ?> — Status</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f1f5f9;
            color: #1e293b;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0,0,0,.08);
            padding: 2.5rem 3rem;
            max-width: 520px;
            width: 100%;
        }
        .logo { font-size: 2rem; font-weight: 800; color: #2563eb; margin-bottom: .25rem; }
        .tagline { color: #64748b; margin-bottom: 2rem; font-size: .95rem; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: .55rem .5rem; font-size: .9rem; border-bottom: 1px solid #f1f5f9; }
        td:first-child { font-weight: 600; color: #64748b; width: 45%; }
        .badge {
            display: inline-block;
            padding: .2rem .65rem;
            border-radius: 999px;
            font-size: .8rem;
            font-weight: 600;
        }
        .badge-green  { background: #dcfce7; color: #16a34a; }
        .badge-yellow { background: #fef9c3; color: #ca8a04; }
        .badge-red    { background: #fee2e2; color: #dc2626; }
        .footer { margin-top: 2rem; font-size: .8rem; color: #94a3b8; text-align: center; }
    </style>
</head>
<body>
<div class="card">
    <div class="logo">&#9993; <?= $appName ?></div>
    <div class="tagline">Professional Self-Hosted Email Marketing Platform</div>

    <table>
        <tr>
            <td>Status</td>
            <td><span class="badge badge-green">Online</span></td>
        </tr>
        <tr>
            <td>Environment</td>
            <td><?= htmlspecialchars($appEnv, ENT_QUOTES, 'UTF-8') ?></td>
        </tr>
        <tr>
            <td>PHP Version</td>
            <td><?= htmlspecialchars($phpVer, ENT_QUOTES, 'UTF-8') ?></td>
        </tr>
        <tr>
            <td>Database</td>
            <td>
                <?php if ($dbStatus === 'connected'): ?>
                    <span class="badge badge-green">Connected</span>
                <?php elseif ($dbStatus === 'error'): ?>
                    <span class="badge badge-red">Error</span>
                    <?php if ($appConfig['debug']): ?>
                        <br><small style="color:#dc2626"><?= htmlspecialchars($dbError, ENT_QUOTES, 'UTF-8') ?></small>
                    <?php endif; ?>
                <?php else: ?>
                    <span class="badge badge-yellow">Unknown</span>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <td>Timezone</td>
            <td><?= htmlspecialchars($appConfig['timezone'], ENT_QUOTES, 'UTF-8') ?></td>
        </tr>
        <tr>
            <td>Debug Mode</td>
            <td>
                <?php if ($appConfig['debug']): ?>
                    <span class="badge badge-yellow">On</span>
                <?php else: ?>
                    <span class="badge badge-green">Off</span>
                <?php endif; ?>
            </td>
        </tr>
    </table>

    <div class="footer">MailForge &copy; <?= date('Y') ?> — Licensed under GPLv3</div>
</div>
</body>
</html>
