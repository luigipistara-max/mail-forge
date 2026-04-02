<?php
declare(strict_types=1);

session_start();

define('INSTALL_BASE', __DIR__);
define('ROOT_BASE', dirname(__DIR__));
define('LOCK_FILE', ROOT_BASE . '/storage/install.lock');

// ── Already installed ────────────────────────────────────────────────────────
if (file_exists(LOCK_FILE)) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Already Installed – Mail Forge</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    </head>
    <body class="bg-light d-flex align-items-center justify-content-center" style="min-height:100vh">
        <div class="card shadow text-center p-5" style="max-width:480px;width:100%">
            <div class="mb-3"><i class="bi bi-envelope-check-fill text-primary" style="font-size:3rem"></i></div>
            <h2 class="fw-bold">Mail Forge is Already Installed</h2>
            <p class="text-muted mt-3">The installation lock file exists. If you need to reinstall, remove <code>storage/install.lock</code> and try again.</p>
            <a href="../public/index.php" class="btn btn-primary mt-3">Go to Application</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// ── Step navigation ──────────────────────────────────────────────────────────
const TOTAL_STEPS = 9;

if (!isset($_SESSION['install_step'])) {
    $_SESSION['install_step'] = 1;
}

if (!isset($_SESSION['install_data'])) {
    $_SESSION['install_data'] = [];
}

// Handle AJAX sub-actions (test_db, test_smtp, run_install) before any output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'test_db') {
        header('Content-Type: application/json');
        echo json_encode(handleTestDb());
        exit;
    }

    if ($action === 'test_smtp') {
        header('Content-Type: application/json');
        echo json_encode(handleTestSmtp());
        exit;
    }

    if ($action === 'run_install') {
        header('Content-Type: application/json');
        echo json_encode(handleRunInstall());
        exit;
    }

    if ($action === 'next') {
        $step = (int) $_SESSION['install_step'];
        if ($step < TOTAL_STEPS) {
            $_SESSION['install_step'] = $step + 1;
        }
        header('Location: index.php');
        exit;
    }

    if ($action === 'prev') {
        $step = (int) $_SESSION['install_step'];
        if ($step > 1) {
            $_SESSION['install_step'] = $step - 1;
        }
        header('Location: index.php');
        exit;
    }
}

$currentStep = (int) $_SESSION['install_step'];

// ── Render ───────────────────────────────────────────────────────────────────
$stepFile = INSTALL_BASE . '/steps/step' . $currentStep . '_' . stepSlug($currentStep) . '.php';

ob_start();
if (file_exists($stepFile)) {
    require $stepFile;
} else {
    echo '<p class="text-danger">Step file not found: ' . htmlspecialchars($stepFile) . '</p>';
}
$content = ob_get_clean();

require INSTALL_BASE . '/templates/layout.php';

// ── Helper: step slug ────────────────────────────────────────────────────────
function stepSlug(int $step): string
{
    $slugs = [
        1 => 'welcome',
        2 => 'requirements',
        3 => 'database',
        4 => 'url',
        5 => 'smtp',
        6 => 'platform',
        7 => 'admin',
        8 => 'install',
        9 => 'complete',
    ];
    return $slugs[$step] ?? 'welcome';
}

// ── Helper: test DB connection ───────────────────────────────────────────────
function handleTestDb(): array
{
    $host    = trim($_POST['db_host'] ?? 'localhost');
    $port    = (int) ($_POST['db_port'] ?? 3306);
    $name    = trim($_POST['db_name'] ?? '');
    $user    = trim($_POST['db_user'] ?? '');
    $pass    = $_POST['db_password'] ?? '';
    $charset = trim($_POST['db_charset'] ?? 'utf8mb4');

    if ($name === '' || $user === '') {
        return ['success' => false, 'message' => 'Database name and username are required.'];
    }

    try {
        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT            => 5,
        ]);
        $version = $pdo->query('SELECT VERSION()')->fetchColumn();

        // Persist DB config to session
        $_SESSION['install_data']['db'] = compact('host', 'port', 'name', 'user', 'pass', 'charset');
        $_SESSION['install_data']['db']['prefix'] = trim($_POST['db_prefix'] ?? 'mf_');

        return ['success' => true, 'message' => "Connected successfully. MySQL {$version}"];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Connection failed: ' . $e->getMessage()];
    }
}

// ── Helper: test SMTP ────────────────────────────────────────────────────────
function handleTestSmtp(): array
{
    $host       = trim($_POST['smtp_host'] ?? '');
    $port       = (int) ($_POST['smtp_port'] ?? 587);
    $user       = trim($_POST['smtp_user'] ?? '');
    $pass       = $_POST['smtp_password'] ?? '';
    $encryption = trim($_POST['smtp_encryption'] ?? 'tls');
    $fromEmail  = trim($_POST['smtp_from_email'] ?? '');
    $fromName   = trim($_POST['smtp_from_name'] ?? 'Mail Forge');

    if ($host === '') {
        return ['success' => false, 'message' => 'SMTP host is required.'];
    }
    if ($fromEmail === '' || !filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'A valid From email address is required.'];
    }

    // Attempt a raw TCP connect to verify the host/port are reachable
    $address = ($encryption === 'ssl') ? "ssl://{$host}" : $host;
    $errno   = 0;
    $errstr  = '';
    $socket  = @fsockopen($address, $port, $errno, $errstr, 10);

    if ($socket === false) {
        return ['success' => false, 'message' => "Cannot connect to {$host}:{$port} – {$errstr}"];
    }
    fclose($socket);

    // Store SMTP config in session
    $_SESSION['install_data']['smtp'] = compact(
        'host', 'port', 'user', 'pass', 'encryption', 'fromEmail', 'fromName'
    );

    return ['success' => true, 'message' => "SMTP host {$host}:{$port} is reachable. Settings saved."];
}

// ── Helper: run full installation ────────────────────────────────────────────
function handleRunInstall(): array
{
    $errors = [];
    $data   = $_SESSION['install_data'] ?? [];

    // 1. Write .env file
    $envResult = writeEnvFile($data);
    if (!$envResult['success']) {
        $errors[] = 'ENV: ' . $envResult['message'];
    }

    // 2. Run migrations
    $db = null;
    if (empty($errors)) {
        $dbConf = $data['db'] ?? [];
        try {
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $dbConf['host'] ?? 'localhost',
                (int) ($dbConf['port'] ?? 3306),
                $dbConf['name'] ?? '',
                $dbConf['charset'] ?? 'utf8mb4'
            );
            $db = new PDO($dsn, $dbConf['user'] ?? '', $dbConf['pass'] ?? '', [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            $errors[] = 'DB connect: ' . $e->getMessage();
        }
    }

    if ($db !== null && empty($errors)) {
        $prefix = $data['db']['prefix'] ?? 'mf_';
        try {
            require_once ROOT_BASE . '/database/MigrationRunner.php';
            $runner = new MigrationRunner($db, $prefix);
            $runner->run();
        } catch (Throwable $e) {
            $errors[] = 'Migrations: ' . $e->getMessage();
        }
    }

    // 3. Insert admin user + role
    if ($db !== null && empty($errors)) {
        try {
            insertAdminUser($db, $data);
        } catch (Throwable $e) {
            $errors[] = 'Admin user: ' . $e->getMessage();
        }
    }

    // 4. Insert default settings
    if ($db !== null && empty($errors)) {
        try {
            insertDefaultSettings($db, $data);
        } catch (Throwable $e) {
            $errors[] = 'Settings: ' . $e->getMessage();
        }
    }

    // 5. Write PWA manifest updates (manifest.json)
    if (empty($errors)) {
        try {
            writePwaManifest($data);
        } catch (Throwable $e) {
            $errors[] = 'PWA manifest: ' . $e->getMessage();
        }
    }

    // 6. Create install.lock
    if (empty($errors)) {
        if (file_put_contents(LOCK_FILE, date('Y-m-d H:i:s')) === false) {
            $errors[] = 'Could not write storage/install.lock';
        }
    }

    if (empty($errors)) {
        $_SESSION['install_step'] = 9;
        return ['success' => true, 'message' => 'Installation completed successfully.', 'errors' => []];
    }

    return ['success' => false, 'message' => 'Installation encountered errors.', 'errors' => $errors];
}

function writeEnvFile(array $data): array
{
    $db       = $data['db']       ?? [];
    $url      = $data['url']      ?? [];
    $smtp     = $data['smtp']     ?? [];
    $platform = $data['platform'] ?? [];
    $admin    = $data['admin']    ?? [];

    $appUrl      = rtrim($url['app_url'] ?? 'http://localhost', '/');
    $forceHttps  = !empty($url['force_https']) ? 'true' : 'false';
    $appName     = $platform['app_name'] ?? 'Mail Forge';
    $companyName = $platform['company_name'] ?? '';
    $companyEmail= $platform['company_email'] ?? ($admin['email'] ?? '');
    $timezone    = $platform['default_timezone'] ?? 'UTC';
    $language    = $platform['default_language'] ?? 'en';
    $doubleOptIn = !empty($platform['double_opt_in']) ? 'true' : 'false';
    $pwaName     = $platform['pwa_name'] ?? $appName;
    $pwaShort    = $platform['pwa_short_name'] ?? 'MailForge';
    $pwaTheme    = $platform['pwa_theme_color'] ?? '#0d6efd';
    $pwaBg       = $platform['pwa_background_color'] ?? '#ffffff';

    $appKey = 'base64:' . base64_encode(random_bytes(32));

    $env = <<<ENV
APP_NAME="{$appName}"
APP_ENV=production
APP_DEBUG=false
APP_URL={$appUrl}
APP_KEY={$appKey}
FORCE_HTTPS={$forceHttps}

DB_HOST={$db['host']}
DB_PORT={$db['port']}
DB_DATABASE={$db['name']}
DB_USERNAME={$db['user']}
DB_PASSWORD={$db['pass']}
DB_PREFIX={$db['prefix']}
DB_CHARSET={$db['charset']}
DB_COLLATION=utf8mb4_unicode_ci

MAIL_DRIVER=smtp
MAIL_HOST={$smtp['host']}
MAIL_PORT={$smtp['port']}
MAIL_USERNAME={$smtp['user']}
MAIL_PASSWORD={$smtp['pass']}
MAIL_ENCRYPTION={$smtp['encryption']}
MAIL_FROM_ADDRESS={$smtp['fromEmail']}
MAIL_FROM_NAME="{$smtp['fromName']}"
MAIL_REPLYTO_ADDRESS=
MAIL_REPLYTO_NAME=
MAIL_TIMEOUT=30
MAIL_BATCH_SIZE=100
MAIL_BATCH_INTERVAL=10

SESSION_LIFETIME=120
SESSION_SECURE={$forceHttps}
SESSION_HTTPONLY=true
SESSION_SAMESITE=Lax

COMPANY_NAME="{$companyName}"
COMPANY_EMAIL={$companyEmail}
DEFAULT_TIMEZONE={$timezone}
DEFAULT_LANGUAGE={$language}
DEFAULT_CURRENCY=USD

PWA_NAME="{$pwaName}"
PWA_SHORT_NAME="{$pwaShort}"
PWA_THEME_COLOR={$pwaTheme}
PWA_BG_COLOR={$pwaBg}

TRACKING_OPENS=true
TRACKING_CLICKS=true
DOUBLE_OPTIN={$doubleOptIn}
ENV;

    $envPath = ROOT_BASE . '/.env';
    if (file_put_contents($envPath, $env) === false) {
        return ['success' => false, 'message' => 'Cannot write .env file. Check permissions on ' . ROOT_BASE];
    }
    return ['success' => true, 'message' => '.env written'];
}

function insertAdminUser(PDO $db, array $data): void
{
    $prefix = $data['db']['prefix'] ?? 'mf_';
    $admin  = $data['admin'] ?? [];

    $usersTable     = $prefix . 'users';
    $rolesTable     = $prefix . 'roles';
    $userRolesTable = $prefix . 'user_roles';

    // Ensure admin role exists
    $db->exec("INSERT IGNORE INTO `{$rolesTable}` (`name`, `display_name`, `description`) VALUES
        ('admin', 'Administrator', 'Full system access'),
        ('user', 'User', 'Standard user access')");

    $uuid     = generateUuid();
    $password = password_hash($admin['password'] ?? '', PASSWORD_DEFAULT);
    $now      = date('Y-m-d H:i:s');

    $stmt = $db->prepare("INSERT INTO `{$usersTable}`
        (`uuid`, `first_name`, `last_name`, `email`, `password`, `status`, `email_verified_at`, `created_at`)
        VALUES (:uuid, :first, :last, :email, :password, 'active', :now, :now)");

    $stmt->execute([
        ':uuid'     => $uuid,
        ':first'    => $admin['first_name'] ?? 'Admin',
        ':last'     => $admin['last_name']  ?? 'User',
        ':email'    => $admin['email']      ?? '',
        ':password' => $password,
        ':now'      => $now,
    ]);

    $userId = (int) $db->lastInsertId();

    // Assign admin role
    $roleStmt = $db->prepare("SELECT `id` FROM `{$rolesTable}` WHERE `name` = 'admin' LIMIT 1");
    $roleStmt->execute();
    $roleId = (int) $roleStmt->fetchColumn();

    if ($roleId && $userId) {
        $db->prepare("INSERT IGNORE INTO `{$userRolesTable}` (`user_id`, `role_id`) VALUES (:uid, :rid)")
           ->execute([':uid' => $userId, ':rid' => $roleId]);
    }
}

function insertDefaultSettings(PDO $db, array $data): void
{
    $prefix   = $data['db']['prefix'] ?? 'mf_';
    $platform = $data['platform'] ?? [];
    $table    = $prefix . 'settings';

    $settings = [
        ['app_name',         $platform['app_name']            ?? 'Mail Forge',  'string',  'general'],
        ['company_name',     $platform['company_name']         ?? '',            'string',  'general'],
        ['company_email',    $platform['company_email']        ?? '',            'string',  'general'],
        ['default_language', $platform['default_language']     ?? 'en',          'string',  'general'],
        ['default_timezone', $platform['default_timezone']     ?? 'UTC',         'string',  'general'],
        ['double_opt_in',    !empty($platform['double_opt_in']) ? '1' : '0',     'boolean', 'email'],
        ['tracking_opens',   '1',                                                 'boolean', 'email'],
        ['tracking_clicks',  '1',                                                 'boolean', 'email'],
        ['pwa_name',         $platform['pwa_name']             ?? 'Mail Forge',  'string',  'pwa'],
        ['pwa_short_name',   $platform['pwa_short_name']       ?? 'MailForge',   'string',  'pwa'],
        ['pwa_theme_color',  $platform['pwa_theme_color']      ?? '#0d6efd',     'string',  'pwa'],
        ['pwa_bg_color',     $platform['pwa_background_color'] ?? '#ffffff',     'string',  'pwa'],
    ];

    $stmt = $db->prepare("INSERT IGNORE INTO `{$table}` (`key`, `value`, `type`, `group`) VALUES (:k, :v, :t, :g)");
    foreach ($settings as [$k, $v, $t, $g]) {
        $stmt->execute([':k' => $k, ':v' => $v, ':t' => $t, ':g' => $g]);
    }
}

function writePwaManifest(array $data): void
{
    $platform   = $data['platform'] ?? [];
    $url        = $data['url']      ?? [];
    $appUrl     = rtrim($url['app_url'] ?? '', '/');
    $manifest   = [
        'name'             => $platform['pwa_name']             ?? 'Mail Forge',
        'short_name'       => $platform['pwa_short_name']       ?? 'MailForge',
        'start_url'        => $appUrl . '/',
        'display'          => 'standalone',
        'background_color' => $platform['pwa_background_color'] ?? '#ffffff',
        'theme_color'      => $platform['pwa_theme_color']      ?? '#0d6efd',
        'icons'            => [
            ['src' => $appUrl . '/images/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png'],
            ['src' => $appUrl . '/images/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png'],
        ],
    ];

    $manifestPath = ROOT_BASE . '/public/manifest.json';
    file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

function generateUuid(): string
{
    $data    = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}
