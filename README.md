```
███╗   ███╗ █████╗ ██╗██╗      ███████╗ ██████╗ ██████╗  ██████╗ ███████╗
████╗ ████║██╔══██╗██║██║      ██╔════╝██╔═══██╗██╔══██╗██╔════╝ ██╔════╝
██╔████╔██║███████║██║██║      █████╗  ██║   ██║██████╔╝██║  ███╗█████╗
██║╚██╔╝██║██╔══██║██║██║      ██╔══╝  ██║   ██║██╔══██╗██║   ██║██╔══╝
██║ ╚═╝ ██║██║  ██║██║███████╗ ██║     ╚██████╔╝██║  ██║╚██████╔╝███████╗
╚═╝     ╚═╝╚═╝  ╚═╝╚═╝╚══════╝ ╚═╝      ╚═════╝ ╚═╝  ╚═╝ ╚═════╝ ╚══════╝
```

<h3 align="center">Self-hosted Email Marketing Platform</h3>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.3%2B-777BB4?style=flat-square&logo=php&logoColor=white" alt="PHP 8.3+">
  <img src="https://img.shields.io/badge/License-GPL--3.0-blue?style=flat-square" alt="License: GPL-3.0">
  <img src="https://img.shields.io/badge/Bootstrap-5-7952B3?style=flat-square&logo=bootstrap&logoColor=white" alt="Bootstrap 5">
</p>

---

## Description

**Mail Forge** is a fully self-hosted email marketing platform built on **PHP 8.3**. It gives you complete ownership of your data and sending infrastructure — no third-party SaaS subscriptions required. Manage contacts, build segmented lists, design and schedule campaigns, automate email workflows, and track detailed analytics, all from your own server.

Key pillars:

- **Contact management** — import, segment, tag, and enrich contacts with custom fields.
- **Campaign creation & scheduling** — compose, preview, schedule, or send immediately.
- **Email automation** — trigger-based workflows that run on autopilot.
- **SMTP server management** — add multiple servers with automatic failover.
- **Analytics** — real-time open and click tracking with per-campaign reports.

---

## Features

| Category | Details |
|---|---|
| **Contact & List Management** | CSV import, list segmentation, tags, custom fields, double opt-in |
| **Campaign Management** | Draft, schedule, send, pause, and cancel campaigns |
| **Email Automation** | Trigger-based workflows (subscribe, open, click, date, etc.) |
| **SMTP Management** | Multiple SMTP servers, automatic failover, per-server quota |
| **Tracking & Analytics** | Open tracking, click tracking, real-time reports |
| **Subscription Forms** | Public embeddable forms with double opt-in support |
| **PWA Support** | Installable progressive web app for mobile/desktop use |
| **Web Installer** | Guided 9-step installer — no command line required |
| **Bounce Handling** | Automated bounce processing via cron |

---

## System Requirements

| Component | Minimum Version |
|---|---|
| PHP | 8.3+ |
| MySQL | 8.0+ |
| Apache | 2.4+ |
| Nginx | 1.18+ *(alternative to Apache)* |
| Composer | 2.x |

**Required PHP extensions:** `pdo`, `pdo_mysql`, `openssl`, `mbstring`, `json`, `curl`, `gd`, `fileinfo`, `session`

---

## Quick Installation

```bash
git clone https://github.com/luigipistara-max/mail-forge.git
cd mail-forge
composer install
```

Then open **`http://yourdomain.com/install/`** in your browser and follow the guided installer.

---

## Manual Installation

For environments where you prefer full control over each step:

1. **Clone the repository**

   ```bash
   git clone https://github.com/luigipistara-max/mail-forge.git
   cd mail-forge
   ```

2. **Install PHP dependencies**

   ```bash
   composer install --no-dev --optimize-autoloader
   ```

3. **Copy the environment file**

   ```bash
   cp .env.example .env
   ```

4. **Configure `.env`** — set your database credentials, app URL, and other settings (see [Environment Configuration](#environment-configuration)).

5. **Run database migrations**

   ```bash
   php database/MigrationRunner.php
   ```

6. **Configure your web server** — point the document root to the `public/` directory (see [Apache](#apache-configuration) / [Nginx](#nginx-configuration) examples below).

7. **Set file permissions**

   ```bash
   chmod -R 775 storage/
   chmod -R 775 public/assets/
   chown -R www-data:www-data storage/
   chown -R www-data:www-data public/assets/
   ```

8. **Create your admin account** — log in for the first time and complete the setup via the admin panel.

---

## Web Installer (Recommended)

Navigate to `http://yourdomain.com/install/` after uploading the files and running `composer install`. The installer guides you through **9 steps**:

| Step | Name | Description |
|---|---|---|
| 1 | **Welcome** | Overview of the installation process |
| 2 | **Requirements Check** | Verifies PHP version and all required extensions |
| 3 | **Database Configuration** | Host, port, database name, username, password, and table prefix |
| 4 | **Application URL** | Auto-detected base URL; can be overridden |
| 5 | **SMTP Configuration** | Primary mail server credentials and sender address |
| 6 | **Platform Settings** | App name, company details, language, timezone, and opt-in mode |
| 7 | **Admin Account** | First name, last name, email, and password for the admin user |
| 8 | **Install** | Writes `.env`, runs migrations, and seeds initial data |
| 9 | **Complete** | Success screen with links to the dashboard and next steps |

> **Security note:** Remove or restrict the `install/` directory after completing installation (see [Security Hardening](#security-hardening)).

---

## Cron Job Configuration

Mail Forge relies on scheduled tasks for sending queues, automation processing, bounce handling, and log cleanup. Add the following entries to your server's crontab (`crontab -e`):

```cron
# Process email queue every 10 minutes
*/10 * * * * /usr/bin/php /path/to/mail-forge/cron/process_email_queue.php >> /path/to/mail-forge/storage/logs/queue.log 2>&1

# Process automations every 10 minutes
*/10 * * * * /usr/bin/php /path/to/mail-forge/cron/process_automations.php >> /path/to/mail-forge/storage/logs/automation.log 2>&1

# Process bounces hourly
0 * * * * /usr/bin/php /path/to/mail-forge/cron/process_bounces.php >> /path/to/mail-forge/storage/logs/bounces.log 2>&1

# Cleanup logs daily at 2:30 AM
30 2 * * * /usr/bin/php /path/to/mail-forge/cron/cleanup_logs.php >> /path/to/mail-forge/storage/logs/cleanup.log 2>&1
```

Replace `/path/to/mail-forge` with the absolute path to your installation.

---

## Apache Configuration

```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    DocumentRoot /var/www/mail-forge/public

    <Directory /var/www/mail-forge/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/mail-forge-error.log
    CustomLog ${APACHE_LOG_DIR}/mail-forge-access.log combined
</VirtualHost>
```

Ensure `mod_rewrite` is enabled:

```bash
a2enmod rewrite
systemctl restart apache2
```

---

## Nginx Configuration

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/mail-forge/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\. {
        deny all;
    }
}
```

---

## File Permissions

```bash
chmod -R 775 storage/
chmod -R 775 public/assets/
chown -R www-data:www-data storage/
chown -R www-data:www-data public/assets/
```

---

## Environment Configuration

The `.env` file (copied from `.env.example`) controls all runtime settings. Key variables:

| Variable | Default | Description |
|---|---|---|
| `APP_NAME` | `Mail Forge` | Application display name |
| `APP_ENV` | `local` | Environment (`local`, `production`) |
| `APP_DEBUG` | `true` | Enable debug output (set `false` in production) |
| `APP_URL` | `http://localhost` | Full base URL of your installation |
| `APP_KEY` | *(empty)* | Application encryption key (auto-generated by installer) |
| `FORCE_HTTPS` | `false` | Redirect all traffic to HTTPS |
| `DB_HOST` | `127.0.0.1` | Database hostname |
| `DB_PORT` | `3306` | Database port |
| `DB_DATABASE` | `mailforge` | Database name |
| `DB_USERNAME` | `root` | Database username |
| `DB_PASSWORD` | *(empty)* | Database password |
| `DB_PREFIX` | `mf_` | Table prefix |
| `MAIL_HOST` | `smtp.example.com` | Default SMTP host |
| `MAIL_PORT` | `587` | Default SMTP port |
| `MAIL_ENCRYPTION` | `tls` | Encryption (`tls`, `ssl`, `none`) |
| `MAIL_FROM_ADDRESS` | `noreply@example.com` | Default sender address |
| `MAIL_BATCH_SIZE` | `100` | Emails sent per batch |
| `MAIL_BATCH_INTERVAL` | `10` | Seconds between batches |
| `TRACKING_OPENS` | `true` | Enable open tracking |
| `TRACKING_CLICKS` | `true` | Enable click tracking |
| `DOUBLE_OPTIN` | `true` | Require email confirmation on subscribe |
| `SESSION_LIFETIME` | `120` | Session lifetime in minutes |
| `DEFAULT_TIMEZONE` | `UTC` | Default timezone |
| `DEFAULT_LANGUAGE` | `en` | Default UI language |
| `PWA_NAME` | `Mail Forge` | PWA full name |
| `PWA_SHORT_NAME` | `MailForge` | PWA short name |
| `PWA_THEME_COLOR` | `#1a73e8` | PWA theme colour |

---

## SMTP Configuration

Mail Forge supports **multiple SMTP servers** with automatic failover.

### Via the Web Installer (Step 5)

Enter your primary SMTP credentials during installation. This configures the default server and writes the settings to `.env`.

### Via the Admin Panel

After installation, navigate to **Settings → SMTP Servers** to:

- Add additional SMTP servers (e.g., per-campaign senders, backup relays).
- Set priority order for failover.
- Test each server's connection before activating it.
- Configure per-server sending quotas.

---

## PWA Setup

Mail Forge ships as a **Progressive Web App** out of the box. The manifest and service worker are located in `public/`:

```
public/manifest.json
public/service-worker.js
public/offline.html
```

PWA metadata (name, short name, theme colour, background colour) is configured via the `PWA_*` variables in `.env` or through **Settings → Platform** in the admin panel. Users can install the app on desktop or mobile directly from the browser's address bar prompt.

---

## Security Hardening

After installation, apply the following hardening steps:

1. **Remove or restrict the installer**

   ```bash
   rm -rf install/
   # or deny web access in your server config:
   # Apache: Deny from all inside a <Directory> block
   # Nginx:  location ^~ /install/ { deny all; }
   ```

2. **Protect `.env`**

   ```bash
   chmod 600 .env
   ```

3. **Enable HTTPS** — obtain a certificate (e.g., Let's Encrypt) and set `FORCE_HTTPS=true` in `.env`.

4. **Disable directory listing**
   - *Apache:* Add `Options -Indexes` to your `<Directory>` block.
   - *Nginx:* Nginx disables directory listing by default.

5. **Set `APP_DEBUG=false`** in production to prevent stack traces from leaking to the browser.

6. **Keep dependencies updated** — run `composer update` periodically to pull security patches.

---

## Troubleshooting

| Symptom | Likely Cause | Resolution |
|---|---|---|
| **Blank white page** | PHP fatal error suppressed | Check the PHP error log; temporarily set `APP_DEBUG=true` in `.env` |
| **HTTP 500 error** | File permission issue | Verify `storage/` and `public/assets/` are writable by the web server user (`chmod -R 775`) |
| **Emails not sending** | Incorrect SMTP settings | Go to **Settings → SMTP Servers**, verify credentials, and use the built-in connection test |
| **Cron jobs not running** | PHP not in cron user's PATH | Use the full PHP binary path (e.g., `/usr/bin/php`) and confirm with `crontab -l` |
| **Installer loops / won't progress** | Session not writable | Ensure `session.save_path` is writable or PHP sessions are configured correctly |
| **Assets returning 404** | Wrong document root | Confirm your web server points to `public/`, not the project root |

---

## Project Structure

```
mail-forge/
├── app/                     # Application source code (PSR-4: MailForge\)
│   ├── Controllers/         # HTTP controllers
│   ├── Core/                # Framework core (router, request, response)
│   ├── Helpers/             # Utility functions
│   ├── Middleware/          # HTTP middleware
│   ├── Models/              # Database models
│   ├── Services/            # Business logic services
│   └── Validators/          # Input validation
├── bootstrap/               # Application bootstrap / DI container
├── config/                  # Configuration files
├── cron/                    # Scheduled task scripts
│   ├── cleanup_logs.php
│   ├── process_automations.php
│   ├── process_bounces.php
│   └── process_email_queue.php
├── database/                # Migrations and seeders
├── install/                 # Web installer
│   ├── steps/               # Step 1–9 installer views
│   ├── templates/           # Installer layout templates
│   └── index.php
├── public/                  # Web root (document root)
│   ├── assets/              # CSS, JS, images
│   ├── manifest.json        # PWA manifest
│   ├── service-worker.js    # PWA service worker
│   ├── offline.html         # PWA offline fallback
│   └── index.php            # Front controller
├── resources/               # Views, lang files, email templates
├── routes/                  # Route definitions
├── storage/                 # Logs, cache, uploads (writable)
├── .env.example             # Environment template
├── composer.json
└── LICENSE
```

---

## License

Mail Forge is open-source software released under the **GNU General Public License v3.0**.

See the [LICENSE](LICENSE) file for full terms, or read the licence online at <https://www.gnu.org/licenses/gpl-3.0.html>.

---

## Contributing

Contributions are welcome! To get started:

1. Fork the repository and create a feature branch.
2. Make your changes, following the existing code style.
3. Open a pull request with a clear description of what was changed and why.

Please open an issue first for large changes or new features so the direction can be discussed before implementation.
