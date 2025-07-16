# Database Migrations with MariaDB
### Setting Up and Running Migrations for Laravel

This document provides a step-by-step guide on how to set up a MariaDB database and run Laravel migrations. This is typically done after setting up your `.env` file (see the companion guide "Setting up .env.md") and before starting your application services (see "Services and Startup.md").

---

## 🔍 What are Migrations?

Migrations are Laravel's way of managing database schema changes:

* They define your database structure in code
* They allow version control of your database schema
* They make it easy to recreate your database structure in different environments
* They ensure all developers work with the same database structure

The project includes migrations for:
* User authentication
* Session management
* Cache storage
* Job queuing
* Role-based permissions

### 1. Secure Your MariaDB Installation (Optional but Recommended)

For production environments, it's recommended to secure your MariaDB installation:

```bash
sudo mysql_secure_installation
```

Follow the prompts to:
* Set a root password
* Remove anonymous users
* Disallow root login remotely
* Remove test database
* Reload privilege tables

For local development, you can skip this step.

### 2. Create a Database

Connect to MariaDB:

```bash
mysql -u root -p
```

If you didn't set a password during installation or secure setup, just press Enter when prompted for a password.

Create a new database:

```sql
CREATE DATABASE laravelsite;
```

Replace `laravelsite` with your preferred database name.

Exit the MariaDB shell:

```sql
EXIT;
```

---

## 🔑 Database Credentials

You need to configure your Laravel application with the correct database credentials.

### 1. Get Your Database Credentials

For a fresh MariaDB installation:

* **Host**: `127.0.0.1` (localhost)
* **Port**: `3306` (default MariaDB port)
* **Database**: The name you created (e.g., `laravelsite`)
* **Username**: `root` (default admin user)
* **Password**: Empty if you didn't set one, otherwise the password you created

### 2. Update Your .env File

Edit your `.env` file and update the database section:

```
DB_CONNECTION=mariadb
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravelsite
DB_USERNAME=root
DB_PASSWORD=your_password_or_empty
```

Replace `laravelsite` with your database name and set the appropriate password.

---

## 🚀 Running Migrations

Once your database is set up and your `.env` file is configured, you can run the migrations.

### 1. Navigate to Your Project Directory

```bash
cd /path/to/your/laravel/project
```

### 2. Run the Migrations

```bash
php artisan migrate
```

If you're using a custom PHP installation (like Herd, XAMPP, etc.):

```bash
/path/to/your/php artisan migrate
```

This will create all the necessary tables in your database.

### 3. Verify the Migrations

You should see output indicating that each migration was run successfully.

To check the migration status:

```bash
php artisan migrate:status
```

This will show you which migrations have been run and which are pending.

---

## 🔄 Common Migration Commands

### Fresh Install (Drop All Tables and Migrate)

If you need to start fresh:

```bash
php artisan migrate:fresh
```

**Warning**: This will delete all data in your database!

### Rollback the Last Migration Batch

To undo the last batch of migrations:

```bash
php artisan migrate:rollback
```

### Rollback All Migrations and Run Again

To reset and re-run all migrations:

```bash
php artisan migrate:refresh
```

### Seed the Database with Test Data

If your project includes seeders:

```bash
php artisan db:seed
```

Or combine migration and seeding:

```bash
php artisan migrate:fresh --seed
```

---

## ⚠️ Troubleshooting

### Connection Refused

If you get a connection refused error:
* Verify MariaDB is running
* Check that the host and port in your `.env` file are correct

### Access Denied

If you get an access denied error:
* Verify your username and password in the `.env` file
* Check that the user has permissions to access the database

### Migration Table Already Exists

If you get an error that the migration table already exists:
* Your database might already have been migrated
* Run `php artisan migrate:status` to check

### PDO Extension Not Found

If you get an error about the PDO extension:
* Ensure PHP is configured with PDO and the MySQL driver
* For most installations, add or uncomment `extension=pdo_mysql` in your php.ini file

---

## 📌 Notes

* Always back up your database before running migrations in production
* Migrations are run in the order of their timestamp prefixes
* Custom migrations can be created with `php artisan make:migration`
* The migrations table keeps track of which migrations have been run
* For team development, always commit your migration files to version control
* Never commit your `.env` file with database credentials to version control
