# Application Configuration Guide

## Overview
This document provides information about the application's core configuration settings in `config/app.php`, including how these settings are fetched from environment variables and how to configure the application for different environments (development, testing, and production).

## Configuration Basics

### Environment Variables
The application uses Laravel's environment configuration system, which loads variables from a `.env` file in the project root. The `config/app.php` file references these environment variables using the `env()` helper function:

```php
'name' => env('APP_NAME', 'Jaxonville'),
```

This pattern provides:
- A way to read from the `.env` file
- A default value ('Jaxonville' in this example) if the environment variable isn't set

### Key Configuration Settings

#### Application Name
```php
'name' => env('APP_NAME', 'Jaxonville'),
```
- Controls the application name used in notifications and UI elements
- Set via `APP_NAME` in your `.env` file

#### Application Environment
```php
'env' => env('APP_ENV', 'production'),
```
- Determines the current running environment
- Common values: `local`, `testing`, `staging`, `production`
- Set via `APP_ENV` in your `.env` file

#### Debug Mode
```php
'debug' => (bool) env('APP_DEBUG', false),
```
- Controls whether detailed error messages are displayed
- Should be `true` in development and `false` in production
- Set via `APP_DEBUG` in your `.env` file

#### Application URL
```php
'url' => env('APP_URL', 'http://laravel.jaxonville.com'),
'api_url' => env('APP_API_URL', 'http://api-laravel.jaxonville.com'),
```
- `url` is used for generating URLs in the main application
- `api_url` is used for API routes and is typically set to a domain like `api-example.com` (using a hyphen instead of a nested subdomain)
- Set via `APP_URL` and `APP_API_URL` in your `.env` file

#### Encryption Key
```php
'key' => env('APP_KEY'),
```
- Used for all encrypted values in the application
- Must be a 32-character string
- Generate using `php artisan key:generate`
- Set via `APP_KEY` in your `.env` file

## Environment-Specific Configuration

### Development Environment
For local development, your `.env` file might contain:

```
APP_NAME=Jaxonville
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
```

This configuration:
- Enables detailed error messages
- Uses a local URL for development
- Sets the environment to 'local'

### Testing Environment
For automated testing, create a `.env.testing` file with:

```
APP_NAME=Jaxonville
APP_ENV=testing
APP_DEBUG=true
APP_URL=http://localhost:8000
```

When running tests with `php artisan test`, Laravel will automatically use the `.env.testing` file if it exists.

### Production Environment
For production, your `.env` file should contain:

```
APP_NAME=Jaxonville
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-production-domain.com
```

This configuration:
- Disables detailed error messages for security
- Uses your production domain
- Sets the environment to 'production'

> **⚠️ SECURITY WARNING**: Before deploying to production, ensure you remove the default users created by the database seeders (admin@example.com/password and user@example.com/password). These default accounts are intended for development only and pose a significant security risk in production environments. Create your own admin accounts with strong passwords instead.

## Maintenance Mode

The application includes configuration for maintenance mode:

```php
'maintenance' => [
    'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
    'store' => env('APP_MAINTENANCE_STORE', 'database'),
],
```

- Enable maintenance mode with: `php artisan down`
- Disable maintenance mode with: `php artisan up`
- Customize the maintenance page by creating `resources/views/errors/503.blade.php`

## Troubleshooting

### Configuration Cache
Laravel can cache configuration for better performance. If you change your `.env` file but don't see the changes reflected:

1. Clear the configuration cache:
   ```
   php artisan config:clear
   ```

2. Optionally, rebuild the configuration cache:
   ```
   php artisan config:cache
   ```

Note: Don't use `config:cache` in local development as it can cause issues with environment detection.

### Environment Detection Issues
If your application isn't correctly detecting the environment:

1. Check that your `.env` file exists and has the correct permissions
2. Verify that `APP_ENV` is set correctly
3. Restart your web server after making changes
4. Clear configuration cache as described above
