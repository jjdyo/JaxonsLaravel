# 🚀 Laravel Project Setup on Debian 12 (Bookworm)

This guide provides instructions for setting up your Laravel application on a Debian 12 (Bookworm) machine in both development and production environments.

## Table of Contents

- [Development Environment Setup](#development-environment-setup) - Using Herd PHP, Laravel's built-in server, and MailHog
- [Production Environment Setup](#production-environment-setup) - Using Nginx, PHP-FPM 8.2, and MariaDB
- [Common Configuration](#common-configuration) - Final checks and mail configuration for both environments

---

# Development Environment Setup

This section walks you through setting up a development environment using Herd PHP, Laravel's built-in server, and MailHog.

---

## 🧩 Step 1: Clone the Laravel Project via SSH

Make sure you’ve added your SSH key to GitHub. Then:

```bash
git clone git@github.com:jjdyo/JaxonsLaravel.git /var/www/JaxonsLaravel
cd /var/www/JaxonsLaravel
```

---

## 🐘 Step 2: Install MariaDB

```bash
sudo apt update
sudo apt install mariadb-server -y
sudo systemctl enable mariadb
sudo systemctl start mariadb
```

Optional secure install:

```bash
sudo mysql_secure_installation
```

---

## ✉️ Step 3: Install MailHog
If using Herd, you may skip this step. Herd offers a built-in SMTP server for local email capture.

```bash
sudo apt install golang-go -y
go install github.com/mailhog/MailHog@latest
```

Add to PATH if necessary:

```bash
export PATH=$PATH:$(go env GOPATH)/bin
```

Test it works:

```bash
MailHog
```

MailHog UI: [http://localhost:8025](http://localhost:8025)  
SMTP: `localhost:1025`

---

## 🧰 Step 4: Install PHP via Herd/Laravel Installer

Run the full setup script:

```bash
/bin/bash -c "$(curl -fsSL https://php.new/install/linux/8.4)"
composer global require laravel/installer
composer require install
```

---

## 🔧 Step 5: Create systemd Services

### Laravel Service

```bash
sudo nano /etc/systemd/system/laravel-app.service
```

Paste:

```ini
[Unit]
Description=Laravel Development Server
After=network.target mariadb.service mailhog.service
Requires=mariadb.service mailhog.service

[Service]
User=root
WorkingDirectory=/var/www/JaxonsLaravel
ExecStart=/root/.config/herd-lite/bin/php artisan serve --host=0.0.0.0 --port=8000
Restart=always

[Install]
WantedBy=multi-user.target
```

---

### MailHog Service

```bash
sudo nano /etc/systemd/system/mailhog.service
```

Paste:

```ini
[Unit]
Description=MailHog Service
After=network.target

[Service]
ExecStart=/root/go/bin/MailHog
Restart=always
User=root

[Install]
WantedBy=multi-user.target
```

---

### Enable and Start All Services

```bash
sudo systemctl daemon-reexec
sudo systemctl daemon-reload
sudo systemctl enable mariadb.service
sudo systemctl enable mailhog.service
sudo systemctl enable laravel-app.service

sudo systemctl start mariadb.service
sudo systemctl start mailhog.service
sudo systemctl start laravel-app.service
```

---

# Production Environment Setup

This section walks you through setting up a production environment using Nginx, PHP-FPM 8.2, and MariaDB.

## 🧩 Step 1: Clone the Laravel Project via SSH

Make sure you've added your SSH key to GitHub. Then:

```bash
git clone git@github.com:jjdyo/JaxonsLaravel.git /var/www/JaxonsLaravel
cd /var/www/JaxonsLaravel
```

---

## 📦 Step 2: Install and Configure Composer

### Using Composer in Your Laravel Project

Once Composer is installed, you can use it to install Laravel dependencies:

```bash
# Install dependencies defined in composer.json
composer install

# Update dependencies to their latest versions
composer update

# Add a new package
composer require package/name

# Add a development-only package
composer require --dev package/name
```

---

## 🐘 Step 3: Install MariaDB

```bash
sudo apt update
sudo apt install mariadb-server -y
sudo systemctl enable mariadb
sudo systemctl start mariadb
```

Optional secure install:

```bash
sudo mysql_secure_installation
```

---

## 🌐 Step 2: Install and Configure Nginx and PHP-FPM 8.2

For production environments, it's recommended to use Nginx with PHP-FPM instead of Laravel's built-in development server.

### Install Nginx and PHP-FPM 8.2

```bash
sudo apt update
sudo apt install nginx php8.2-fpm php8.2-cli php8.2-common php8.2-mysql php8.2-zip php8.2-gd php8.2-mbstring php8.2-curl php8.2-xml php8.2-bcmath -y
```

### Configure PHP-FPM 8.2

Ensure PHP-FPM is running and enabled:

```bash
sudo systemctl enable php8.2-fpm
sudo systemctl start php8.2-fpm
```

Verify PHP-FPM is running:

```bash
sudo systemctl status php8.2-fpm
```

### Configure Nginx for Laravel

Create a new Nginx site configuration:

```bash
sudo nano /etc/nginx/sites-available/laravel
```

Paste the following configuration (adjust paths as needed):

```nginx
server {
    listen 80 default_server;
    listen [::]:80 default_server;
    server_name _;

    root /var/www/JaxonsLaravel/public;
    index index.php;
    charset utf-8;

    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;

    client_max_body_size 20M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_hide_header X-Powered-By;
        fastcgi_read_timeout 60s;
        fastcgi_param PATH_INFO "";
    }

    # Hide dotfiles except .well-known
    location ~ /\.(?!well-known).* { deny all; }

    # Optional static asset caching
    location ~* \.(?:css|js|jpg|jpeg|gif|png|svg|webp|ico|woff2?)$ {
        expires 7d;
        access_log off;
        add_header Cache-Control "public, max-age=604800, immutable";
        try_files $uri =404;
    }
}
```

Enable the site and test the configuration:

```bash
sudo ln -s /etc/nginx/sites-available/laravel /etc/nginx/sites-enabled/
sudo rm /etc/nginx/sites-enabled/default  # Remove default site if needed
sudo nginx -t  # Test configuration
sudo systemctl restart nginx
```

### Set Up Systemd Services

Create systemd service files for PHP-FPM and Nginx if they don't already exist:

#### PHP-FPM Service (if needed)

```bash
sudo nano /etc/systemd/system/php8.2-fpm.service
```

Paste:

```ini
[Unit]
Description=The PHP 8.2 FastCGI Process Manager
Documentation=man:php-fpm8.2(8)
After=network-online.target
Wants=network-online.target

[Service]
Type=simple
# Creates /run/php for sockets and pid files
RuntimeDirectory=php
RuntimeDirectoryMode=0755

# Adjust path if php-fpm8.2 is elsewhere
ExecStart=/usr/sbin/php-fpm8.2 -F
# Graceful reload: send USR2 to master
ExecReload=/bin/kill -USR2 $MAINPID

# Hardening (optional but nice)
PrivateTmp=true
ProtectHome=true
ProtectSystem=full
NoNewPrivileges=true
Restart=on-failure
RestartSec=2

[Install]
WantedBy=multi-user.target
```

#### Nginx Service (if needed)

```bash
sudo nano /etc/systemd/system/nginx.service
```

Paste:

```ini
[Unit]
Description=A high performance web server and a reverse proxy server
Documentation=man:nginx(8)
After=network-online.target remote-fs.target nss-lookup.target
Wants=network-online.target

[Service]
Type=forking
PIDFile=/run/nginx.pid
ExecStartPre=/usr/sbin/nginx -t -q -g 'daemon on; master_process on;'
ExecStart=/usr/sbin/nginx -g 'daemon on; master_process on;'
ExecReload=/usr/sbin/nginx -g 'daemon on; master_process on;' -s reload
ExecStop=-/sbin/start-stop-daemon --quiet --stop --retry QUIT/5 --pidfile /run/nginx.pid
TimeoutStopSec=5
KillMode=mixed

[Install]
WantedBy=multi-user.target
```

### Enable and Start Services

```bash
sudo systemctl daemon-reload
sudo systemctl enable nginx
sudo systemctl enable php8.2-fpm
sudo systemctl start nginx
sudo systemctl start php8.2-fpm
```

### Set Proper Permissions

Ensure the web server has proper permissions to access Laravel files:

```bash
sudo chown -R www-data:www-data /var/www/JaxonsLaravel/storage
sudo chown -R www-data:www-data /var/www/JaxonsLaravel/bootstrap/cache
sudo chmod -R 775 /var/www/JaxonsLaravel/storage
sudo chmod -R 775 /var/www/JaxonsLaravel/bootstrap/cache
```

---

# Common Configuration

The following sections apply to both development and production environments.

## ✅ Final Check

### Development Environment
Visit your app:

```
http://<your-server-ip>:8000 (80 for nginx)
```

### Production Environment
Visit your app:

```
http://<your-server-ip>
```

### Both Environments

MailHog UI:

```
http://<your-server-ip>:8025
```

API Endpoints:

```
http://api-laravel.<your-server-ip>
```

## Mail Configuration

To catch email delivery, update your `.env` file:

```env
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

---

## Environment Configuration (.env File)

Setting up your `.env` file is a crucial step in configuring your Laravel application. The `.env` file contains environment-specific variables such as database credentials, API keys, and application settings.

### Creating and Configuring Your .env File

1. Copy the example file to create your own .env file:

```bash
copy .env.example .env
```

2. Generate an application key:

```bash
php artisan key:generate
```

3. Configure your environment variables according to your setup:
   - Application settings (APP_NAME, APP_ENV, APP_URL, etc.)
   - Database connection details
   - Mail configuration
   - Other service credentials

For detailed instructions on setting up your `.env` file, including common configurations and troubleshooting tips, please refer to our dedicated guide:

[Setting up .env File Guide](Setting%20up%20.env.md)

---
