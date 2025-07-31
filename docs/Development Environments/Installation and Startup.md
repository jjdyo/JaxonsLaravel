# 🚀 Laravel Project Setup on Debian 12 (Bookworm)

This guide walks you through cloning, installing, and configuring your Laravel application on a Debian Bookworm machine using SSH, MailHog, MariaDB, and Herd PHP.

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

## 🧰 Step 4: Install Laravel & PHP via Herd

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

## ✅ Final Check

Visit your app:

```
http://<your-server-ip>:8000
```

MailHog UI:

```
http://<your-server-ip>:8025
```

---

## Mail

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

**---**
