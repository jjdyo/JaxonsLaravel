# Console Commands Documentation

## Overview
This document provides information about the custom Artisan console commands available in the application. Console commands are defined in `routes/console.php` and can be executed using the Artisan CLI.

## Available Commands

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
