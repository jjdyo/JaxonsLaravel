# Setting Up Your Laravel .env File for Local Dev

This guide explains how to configure your Laravel environment variables for local development using Herd. The `.env` file contains crucial configuration settings that your application needs to run properly. **This is typically the first step** before setting up services (see the companion guide "Services and Startup.md").

---

## 🔑 What is the .env File?

The `.env` file contains environment-specific variables for your Laravel application.

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
APP_API_URL=http://api.localhost
```

* `APP_NAME`: Set this to your project name
* `APP_ENV`: Keep as `local` for development
* `APP_DEBUG`: Set to `true` for development (shows detailed error messages)
* `APP_URL`: Set to your local development URL (typically `http://localhost` or a custom domain configured in Herd)
* `APP_API_URL`: Set to your API URL (typically a subdomain like `http://api.localhost` or `http://api.yourdomain.test`)
* `APP_KEY`: Leave empty for now - we'll generate it in step 4

### 3. Configure Database Connection

Herd provides a pre-configured database environment. Update these settings:

```
DB_CONNECTION=mariadb
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=root
DB_PASSWORD=
```

For Herd:
* `DB_CONNECTION`: Use `mariadb` for MariaDB or `mysql` for MySQL
* `DB_HOST`: Usually `127.0.0.1` (localhost)
* `DB_PORT`: Default is `3306`
* `DB_DATABASE`: Create a database in Herd and enter its name here
* `DB_USERNAME`: Default is `root` in Herd
* `DB_PASSWORD`: Your local MariaDB user may or may not require a password

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

### 5. Configure Session and Cache Settings

For local development, the default database-driven session and cache work well:

```env
SESSION_DRIVER=database
CACHE_STORE=database
```

If you need better performance, consider using Redis:

```env
SESSION_DRIVER=redis
CACHE_STORE=redis
```

### 6. Configure Queue Settings

For local development, you can use the database queue:

```env
QUEUE_CONNECTION=database
```

Run the queue worker in development:

```bash
php artisan queue:work
```

## 📬 Configure Mail Settings

### Using MailHog (Recommended for Development)

If you're running MailHog locally (SMTP on port 1025, UI on 8025), use:

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

### Using Log Driver

Alternatively, set the mail driver to log:

```
MAIL_MAILER=log
```

This will write emails to your log file instead of actually sending them.

## 📊 Configure Laravel Pulse (Performance Monitoring)

Laravel 12 includes Pulse for application monitoring. Configure it with:

```env
PULSE_DOMAIN=null
PULSE_PATH=pulse
PULSE_ENABLED=true
```

Access the Pulse dashboard at `http://your-app-url/pulse`

## 📝 Configure Logging

For development, configure comprehensive logging:

```env
LOG_CHANNEL=stack
LOG_STACK=single,web,api,slack
LOG_LEVEL=debug
LOG_DAILY_DAYS=14
```

### Optional: Slack Integration

To receive critical errors in Slack:

```env
LOG_SLACK_WEBHOOK_URL=your_slack_webhook_url
LOG_SLACK_USERNAME="Laravel Log"
LOG_SLACK_EMOJI=":boom:"
```

## 🚀 Configure Broadcasting (Optional)

For real-time features, configure broadcasting:

```env
BROADCAST_CONNECTION=redis
```

Or use log driver for development:

```env
BROADCAST_CONNECTION=log
```

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

### 3. Test Cache Configuration:

```bash
php artisan cache:clear
php artisan config:cache
```

### 4. Test Queue Configuration:

```bash
php artisan queue:table
php artisan migrate
```

### 5. Verify Pulse Configuration:

Visit `http://your-app-url/pulse` to ensure Pulse is working.

---

## 🔧 Performance Optimization for Development

### Enable Opcache (Optional)

For better performance in development, consider enabling Opcache in your PHP configuration.

### Use Redis for Sessions and Cache

If you have Redis available:

```env
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
BROADCAST_CONNECTION=redis
```

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
php artisan config:cache
php artisan route:clear
php artisan view:clear
```

### Session Issues:

If sessions aren't working properly:

```bash
php artisan session:table
php artisan migrate
```

### Queue Not Processing:

Make sure to run the queue worker:

```bash
php artisan queue:work --verbose
```

---

## 📌 Environment-Specific Notes

### Development Environment:
* `APP_ENV=local`
* `APP_DEBUG=true`
* `LOG_LEVEL=debug`
* Use database drivers for cache/sessions for simplicity

### Staging Environment:
* `APP_ENV=staging`
* `APP_DEBUG=false`
* `LOG_LEVEL=info`
* Use Redis for better performance

### Production Environment:
* `APP_ENV=production`
* `APP_DEBUG=false`
* `LOG_LEVEL=warning`
* Use Redis/Memcached for optimal performance
* Enable Opcache
* Set proper CACHE_PREFIX

---

## 🔌 Additional Configuration Options

### API Rate Limiting:

```env
API_THROTTLE=true
```

### Maintenance Mode:

```env
APP_MAINTENANCE_DRIVER=file
```

### Trusted Proxies (for load balancers):

```env
APP_TRUSTED_PROXIES=*
```

### Bcrypt Rounds (security vs performance):

```env
BCRYPT_ROUNDS=12
```

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
DB_CONNECTION=mariadb
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravelsite
DB_USERNAME=laravel_user
DB_PASSWORD=your_secure_password
```

---

## ⚠️ Security Reminders

* Never commit your `.env` file to version control
* Use strong, unique passwords for database users
* Keep your `APP_KEY` secure and never share it
* Use environment-appropriate debug settings
* Regularly rotate API keys and passwords
* Different environments (development, staging, production) should have different `.env` files
* For team development, consider documenting required environment variables in your README
