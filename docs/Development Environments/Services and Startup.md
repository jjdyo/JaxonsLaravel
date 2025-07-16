# Laravel + MariaDB Startup Guide

This document outlines how to start and verify both the MariaDB database server and a Laravel application on your local development environment. This guide follows standard conventions for web application deployment.

```
/var/www/your-project-name
```

---

## 🐬 Start the MariaDB Server

MariaDB is installed as a `systemd` service and can be controlled using `systemctl`.

### Start MariaDB:

```bash
sudo systemctl start mariadb.service
```

### Check Status:

```bash
sudo systemctl status mariadb.service
```

* You should see something like:
  `Active: active (running)`

### Enable on Boot (Optional):

```bash
sudo systemctl enable mariadb.service
```

---

## 🌐 Start the Laravel Development Server

The Laravel app is located at:

```
/var/www/your-project-name
```

It runs via Artisan's built-in server and is managed by a custom `systemd` service. You'll need to create a service file for your application (example: `laravel-app.service`).

### Start Laravel:

```bash
sudo systemctl start laravel-app.service
```

Replace `laravel-app.service` with your actual service name.

### Check Status:

```bash
sudo systemctl status laravel-app.service
```

* You should see:

    * `Active: active (running)`
    * Laravel running on `http://0.0.0.0:8000`

### Enable on Boot (Optional):

```bash
sudo systemctl enable laravel-app.service
```

---

## 🛠 Manually Run Laravel Without systemd

If the systemd service fails, you can run Laravel manually:

### Navigate to the app directory:

```bash
cd /var/www/your-project-name
```

### Start Artisan dev server:

If you're using a custom PHP installation (like Herd, XAMPP, etc.):

```bash
/path/to/your/php artisan serve --host=0.0.0.0 --port=8000
```

Or use system-wide PHP if available:

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

---

## ♻️ Restarting Services

If you make changes to your Laravel `.env`, migrations, or config, you might want to restart:

```bash
sudo systemctl restart laravel-app.service
sudo systemctl restart mariadb.service
```

Remember to replace `laravel-app.service` with your actual service name.

---

## ✅ Confirm Everything is Running

Check if both services are active:

```bash
sudo systemctl status mariadb.service
sudo systemctl status laravel-app.service
```

Remember to replace `laravel-app.service` with your actual service name.

And then visit your Laravel app in the browser:

```
http://<your-server-ip>:8000
```

---

## 📌 Notes

* Laravel's service definition should be created at `/etc/systemd/system/laravel-app.service` (replace with your service name).
* MariaDB socket location: `/run/mysqld/mysqld.sock`
* Laravel connects to MariaDB typically using `127.0.0.1` and port `3306`.
* After creating or modifying service files, run `sudo systemctl daemon-reload` to apply changes.
