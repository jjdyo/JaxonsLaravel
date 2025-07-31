# Setting Up Your Laravel .env File for Local Dev

This guide explains how to configure your Laravel environment variables for local development using Herd. The `.env` file contains crucial configuration settings that your application needs to run properly. **This is typically the first step** before setting up services (see the companion guide "Services and Startup.md").

---

## 🔑 What is the .env File?

The `.env` file contains environment-specific variables for your Laravel application, including:

* Database credentials
* API keys
* Application settings
* Mail configuration
* Cache and session settings

These settings should **never** be committed to version control, which is why Laravel includes a `.env.example` file as a template.

---

## 📋 Step-by-Step Setup Process

### 1. Create Your .env File

First, copy the example file to create your own .env file:

```bash
copy .env.example .env
```

This creates a new `.env` file based on the example template.

### 2. Configure Basic Application Settings

Edit your .env file and update the following settings:

```
APP_NAME="Your App Name"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost
```

* `APP_NAME`: Set this to your project name
* `APP_ENV`: Keep as `local` for development
* `APP_DEBUG`: Set to `true` for development (shows detailed error messages)
* `APP_URL`: Set to your local development URL (typically `http://localhost` or a custom domain configured in Herd)
* `APP_KEY`: Leave empty for now - we'll generate it in step 4

### 3. Configure Database Connection

Herd provides a pre-configured database environment. Update these settings:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=root
DB_PASSWORD=
```



For Herd:
* `DB_CONNECTION`: Use `mysql` for MySQL or `mariadb` for MariaDB
* `DB_HOST`: Usually `127.0.0.1` (localhost)
* `DB_PORT`: Default is `3306`
* `DB_DATABASE`: Create a database in Herd and enter its name here
* `DB_USERNAME`: Default is `root` in Herd
* `DB_PASSWORD`: your local MariaDB user may or may not require a password

### 4. Generate Application Key

After setting up your `.env` file, generate an application key:

```bash
php artisan key:generate
```

If you're using your PHP path (e.g. Herd, /usr/bin/php, etc.):

```bash
/path/to/herd/php artisan key:generate
```

This will update your `.env` file with a random application key used for encryption.

## 📬 Configure Mail to Use MailHog

If you're running MailHog locally (SMTP on port 1025, UI on 8025), use the following:

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

You can then view any email output by visiting:

```
http://localhost:8025
```

Alternatively, set the mail driver to log:

```
MAIL_MAILER=log
```

This will write emails to your log file instead of actually sending them.

---

## 🧪 Testing Your Configuration

### 1. Check Database Connection:

```bash
php artisan tinker
```

Then in the Tinker console:

```php
DB::connection()->getPdo();
```

If successful, you'll see a PDO object. If not, you'll get an error message.

### 2. Run Migrations:

```bash
php artisan migrate
```

This will test your database connection and set up your database tables.

---

## 🔄 Common Configuration Issues

### Database Connection Failed:

* Verify Herd's database service is running
* Check that your database exists
* Confirm username and password are correct
* Try connecting with a database client to test credentials

### Application Key Not Set:

* Run `php artisan key:generate` again
* Check that the `.env` file is writable

### Cache Issues:

If you've made changes to your `.env` file but they don't seem to take effect:

```bash
php artisan config:clear
```

---

## 📌 Notes

* Never commit your `.env` file to version control
* Different environments (development, staging, production) should have different `.env` files
* For team development, consider documenting required environment variables
* Herd typically uses the following database settings:
  * Host: `127.0.0.1`
  * Port: `3306`
  * Username: `root`
  * Password: (empty)
* If you're using a custom domain with Herd, update your `APP_URL` accordingly
---

## 🛠 Create a Laravel Database and User

Before Laravel can connect to a database, you'll need to create one and grant a user access to it.

### 1. Open MariaDB:

```bash
sudo mariadb -u root
```

### 2. Create a database and user:

```sql
CREATE DATABASE laravelsite CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'laravel_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON laravelsite.* TO 'laravel_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

Update your `.env` file:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravelsite
DB_USERNAME=laravel_user
DB_PASSWORD=your_secure_password
```

---
