# API Token Scopes Documentation

## Overview

This document provides information about the API token scopes (abilities) available in the application. API token scopes allow you to grant specific permissions to API tokens, limiting what actions they can perform.

## Configuration

API token scopes are defined in the `config/api-scopes.php` file:

```php
return [
    'scopes' => [
        'site-backups' => 'Perform site backups',
        'site-spinup' => 'Spin up new sites',
        'read-data' => 'Read application data',
        'write-data' => 'Write application data',
    ],
];
```

Each scope has a key (used internally) and a description (displayed to users).

## Available Scopes

The application defines the following scopes:

| Scope | Description | Used For |
|-------|-------------|----------|
| `site-backups` | Perform site backups | Endpoints related to backing up site data |
| `site-spinup` | Spin up new sites | Endpoints related to creating new sites |
| `read-data` | Read application data | Read-only API endpoints |
| `write-data` | Write application data | API endpoints that modify data |

## Using Scopes in Routes

Scopes are enforced using the `abilities` middleware provided by Laravel Sanctum:

```php
Route::middleware(['auth:sanctum', 'abilities:write-data'])
    ->post('/example/data', [ExampleApiController::class, 'storeData']);
```

This route can only be accessed with tokens that have the `write-data` scope.

## Creating Tokens with Scopes

When creating a token, you can specify which scopes it should have:

```php
// Create a token with specific scopes
$token = $user->createToken(
    'Token Name',
    ['read-data', 'write-data'], // Scopes
    $expiresAt // Optional expiration date
);
```

## Checking Token Scopes

You can check if a token has a specific scope using the `tokenCan` method:

```php
if ($request->user()->tokenCan('write-data')) {
    // User's token has the 'write-data' scope
}
```

## User Interface

Users can create API tokens with specific scopes through the user interface at `/api-tokens/create`. The available scopes are displayed with their descriptions, allowing users to select which permissions they want to grant to the token.

## Best Practices

1. **Principle of Least Privilege**: Only grant the minimum scopes necessary for a token to perform its intended function.

2. **Separate Tokens for Different Purposes**: Create different tokens for different applications or services, each with its own set of scopes.

3. **Regularly Audit Tokens**: Periodically review and revoke unused or unnecessary tokens.

4. **Set Expiration Dates**: Use token expiration dates to limit the lifetime of tokens, especially those with powerful scopes.

5. **Scope Naming Conventions**: Use clear, descriptive names for scopes that indicate the action and resource they apply to.

## Related Documentation

- [RouteProtection Documentation](RouteProtection.md) - Information about protecting routes, including using token abilities
- [ApiKeyController Documentation](../Controllers/ApiKeyController.md) - Documentation for the controller that manages API tokens
