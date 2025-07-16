# Setting Up Your Laravel .env File for Herd

This guide explains how to configure your Laravel environment variables for local development using Herd. The `.env` file contains crucial configuration settings that your application needs to run properly.

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

## 📋 Creating Your .env File

### Copy the Example File:

```bash
copy .env.example .env
```

This creates a new `.env` file based on the example template.

---

## ⚙️ Configuring for Herd

Herd provides a pre-configured PHP development environment with database services. Here's how to set up your `.env` file for use with Herd:

### 1. Basic Application Settings:

```
APP_NAME="Your App Name"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost
```

* `APP_NAME`: Set this to your project name
* `APP_ENV`: Keep as `local` for development
* `APP_KEY`: Will be generated in a later step
* `APP_DEBUG`: Set to `true` for development (shows detailed error messages)
* `APP_URL`: Set to your local development URL (typically `http://localhost` or a custom domain configured in Herd)

### 2. Database Configuration:

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
* `DB_PASSWORD`: Herd typically uses no password for local development (leave empty)

### 3. Mail Configuration:

For local development, set the mail driver to log:

```
MAIL_MAILER=log
```

This will write emails to your log file instead of actually sending them.

---

## 🔐 Generating the Application Key

After setting up your `.env` file, you need to generate an application key:

```bash
php artisan key:generate
```

If you're using Herd's PHP:

```bash
/path/to/herd/php artisan key:generate
```

This will update your `.env` file with a random application key used for encryption.

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
