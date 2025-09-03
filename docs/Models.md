# Models Documentation

## Overview
This document provides information about the data models used in the application. Models represent database tables and define the structure and relationships of the application's data.

## Available Models
The application includes the following models:

### User Model
The User model represents users in the application and is used for authentication, authorization, and user management. It implements email verification and integrates with Laravel Sanctum for API token management and Spatie's permission package for role-based access control.

[Detailed User Model Documentation](Models/User.md)

### Role Model
The Role model extends Spatie's Role model to provide role-based access control in the application. It defines the structure and relationships for user roles, which are used for authorization and permission management.

[Detailed Role Model Documentation](Models/Role.md)

### ApiKey Model
The ApiKey model extends Laravel Sanctum's PersonalAccessToken model to provide enhanced functionality for API key management. It follows the "Fat Models, Skinny Controllers" pattern by encapsulating database operations and business logic related to API keys.

[Detailed ApiKey Model Documentation](Models/ApiKey.md)

## Permission System
The application uses Spatie's Laravel-permission package for role and permission management. This provides:

- Role-based access control
- Permission-based access control
- Middleware for protecting routes based on roles and permissions

### Example Usage
```php
// Assign a role to a user
$user->assignRole('admin');

// Check if a user has a role
if ($user->hasRole('admin')) {
    // User is an admin
}

// Protect a route with a permission
Route::middleware(['permission:view contact page url'])->group(function () {
    // Routes that require the 'view contact page url' permission
});
```

## Related Documentation
- [Routes Documentation](Routes.md) - Documentation for application routes
- [AuthController Documentation](Controllers/AuthController.md) - Documentation for authentication functionality
- [DocsController Documentation](Controllers/DocsController.md) - Documentation for the documentation system
