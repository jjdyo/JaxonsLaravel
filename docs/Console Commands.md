# Console Commands Documentation

## Overview
This document provides information about the custom Artisan console commands available in the application. Console commands are defined in `routes/console.php` and can be executed using the Artisan CLI.

## Available Commands

### `dev:clear`
Clears development caches (views, config, routes) - perfect for template/logic changes.

**Usage:**
```bash
php artisan dev:clear
# With all caches (including application cache and compiled services)
php artisan dev:clear --all
```

**Purpose:** Clear various caches during development to ensure changes take effect.

**Example Output:**
```
🧹 Clearing development caches...
   Clearing compiled views...
   Clearing configuration cache...
   Clearing route cache...
   Clearing event cache...

✅ Development caches cleared successfully!
💡 Use --all flag to clear application cache and compiled services too

Perfect for after adding new email notifications, views, or config changes!
```

**Implementation:** See `app\Console\Commands\ClearDevelopmentCache.php`

### `site:info`
Displays comprehensive site information including database, memory usage, and system details.

**Usage:**
```bash
php artisan site:info
# Output in JSON format
php artisan site:info --json
```

**Purpose:** Provide detailed information about the application environment for debugging and documentation.

**Example Output (partial):**
```
=== Site Information ===

Application:
+----------------+------------------+
| Property       | Value            |
+----------------+------------------+
| Name           | JaxonsLaravel    |
| URL            | http://localhost |
| Environment    | local            |
| Debug Mode     | Enabled          |
| Timezone       | UTC              |
| Locale         | en               |
| Laravel Version| 10.x.x           |
| PHP Version    | 8.1.x            |
+----------------+------------------+

Database:
+----------------+------------------+
| Property       | Value            |
+----------------+------------------+
| Connection     | mysql            |
| Driver         | mysql            |
| Database       | jaxons_laravel   |
| Host           | 127.0.0.1        |
| Port           | 3306             |
| Version        | 10.5.x-MariaDB   |
| Status         | Connected        |
+----------------+------------------+
```

**Implementation:** See `app\Console\Commands\SiteInfoCommand.php`

### `inspire`
Displays an inspiring quote in the console.

**Usage:**
```bash
php artisan inspire
```

**Purpose:** Display an inspiring quote to motivate developers.

**Implementation:**
```php
Artisan::command('inspire', function () {
    /** @var ClosureCommand $this */
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
```

## Running Artisan Commands
To run an Artisan command, use the following syntax in your terminal:

```bash
php artisan [command-name]
```

For example, to run the `inspire` command:

```bash
php artisan inspire
```

## Built-in Laravel Commands
In addition to the custom commands defined in `routes/console.php`, Laravel provides many built-in Artisan commands for common tasks such as:

- `php artisan list` - List all available commands
- `php artisan help [command]` - Display help for a specific command
- `php artisan make:controller` - Create a new controller
- `php artisan make:model` - Create a new model
- `php artisan migrate` - Run database migrations
- `php artisan serve` - Start the Laravel development server

For a complete list of built-in Laravel commands, run:

```bash
php artisan list
```

## Related Documentation
- [Routes Documentation](Routes.md) - Documentation for application routes
- [DocsController Documentation](Controllers/DocsController.md) - Documentation for the documentation system
