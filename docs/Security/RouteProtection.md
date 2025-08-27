# Route Protection and Organization in Laravel

## Overview

This document provides a comprehensive guide to the various methods available in Laravel for protecting, limiting, and organizing routes in both web and API contexts. Understanding these mechanisms is crucial for building secure and well-structured applications.

## Route Organization Methods

### Route Files

Laravel provides several route files for different purposes:

- `routes/web.php` - For web routes that are typically accessed via a browser
- `routes/api.php` - For API routes, automatically prefixed with `/api` and the `api` middleware group
- `routes/console.php` - For defining custom Artisan commands
- `routes/channels.php` - For defining broadcasting channels
- `routes/admin.php` - Custom route file (can be created for admin routes)

### Route Groups

Route groups allow you to share route attributes across multiple routes:

```php
Route::prefix('admin')->group(function () {
    Route::get('/users', function () {
        // Matches the "/admin/users" URL
    });
    Route::get('/settings', function () {
        // Matches the "/admin/settings" URL
    });
});
```

#### Common Group Attributes

- `prefix` - Add a prefix to each route URI within the group
- `name` - Add a prefix to each route name within the group
- `middleware` - Apply middleware to all routes within the group
- `namespace` - Set the controller namespace for all routes within the group
- `domain` - Restrict routes to a specific subdomain
- `where` - Apply pattern constraints to route parameters

### Route Resources

Resource routes provide a quick way to generate routes for CRUD operations:

```php
// Creates 7 routes for index, create, store, show, edit, update, destroy
Route::resource('photos', PhotoController::class);

// Create API resource routes (index, store, show, update, destroy)
Route::apiResource('photos', PhotoController::class);

// Create multiple resource routes at once
Route::resources([
    'photos' => PhotoController::class,
    'posts' => PostController::class,
]);
```

### Route Naming

Named routes provide a convenient way to generate URLs or redirects:

```php
Route::get('/user/profile', function () {
    // ...
})->name('profile');

// Generate URL
$url = route('profile');

// Redirect to named route
return redirect()->route('profile');
```

## Route Protection Methods

### Authentication

Protect routes to ensure only authenticated users can access them:

```php
// Using middleware directly
Route::get('/profile', function () {
    // Only authenticated users may access this route...
})->middleware('auth');

// Using route groups
Route::middleware(['auth'])->group(function () {
    Route::get('/profile', function () {
        // Only authenticated users may access this route...
    });
});
```

### Authentication Guards

Laravel supports multiple authentication guards for different user types:

```php
// Protect route with web guard (default)
Route::middleware('auth')->group(function () {
    // ...
});

// Protect route with API guard
Route::middleware('auth:api')->group(function () {
    // ...
});

// Protect route with admin guard
Route::middleware('auth:admin')->group(function () {
    // ...
});
```

### Email Verification

Ensure users have verified their email address:

```php
Route::get('/profile', function () {
    // Only verified users may access this route...
})->middleware(['auth', 'verified']);
```

### Guest Restriction

Restrict routes to only non-authenticated users:

```php
Route::get('/login', function () {
    // Only non-authenticated users may access this route...
})->middleware('guest');
```

### Role and Permission Based Protection

Using Spatie's Laravel Permission package:

```php
// Role-based protection
Route::middleware('role:admin')->group(function () {
    // Only users with the 'admin' role may access these routes...
});

// Permission-based protection
Route::middleware('permission:edit articles')->group(function () {
    // Only users with the 'edit articles' permission may access these routes...
});

// Role OR Permission based protection
Route::middleware('role_or_permission:admin|edit articles')->group(function () {
    // Users with either 'admin' role OR 'edit articles' permission may access...
});
```

### CSRF Protection

Protect against Cross-Site Request Forgery attacks:

```php
// All routes defined in web.php are automatically protected
// For specific routes that need to bypass CSRF protection:
Route::post('/webhook', function () {
    // ...
})->withoutMiddleware(['csrf']);
```

### Signed URLs

Create tamper-proof URLs with a signature:

```php
// Generate a signed URL
$url = URL::signedRoute('unsubscribe', ['user' => 1]);

// Generate a temporary signed URL that expires
$url = URL::temporarySignedRoute(
    'unsubscribe', now()->addMinutes(30), ['user' => 1]
);

// Protect a route with the 'signed' middleware
Route::get('/unsubscribe/{user}', function () {
    // ...
})->name('unsubscribe')->middleware('signed');
```

### Rate Limiting

Limit the number of requests a user can make:

```php
// Basic rate limiting (60 requests per minute)
Route::middleware(['throttle:60,1'])->group(function () {
    Route::get('/api/users', function () {
        // ...
    });
});

// Different limits for guests and authenticated users
Route::middleware(['throttle:60,1|120,1'])->group(function () {
    // 60 requests per minute for guests, 120 for authenticated users
});

// Named rate limiters (defined in App\Providers\RouteServiceProvider)
Route::middleware(['throttle:api'])->group(function () {
    // Uses the 'api' rate limiter configuration
});
```

### IP Restriction

Restrict access to specific IP addresses:

```php
// Create a middleware (app/Http/Middleware/CheckIp.php)
public function handle($request, Closure $next)
{
    $allowedIPs = ['192.168.1.1', '192.168.1.2'];
    if (!in_array($request->ip(), $allowedIPs)) {
        abort(403, 'Unauthorized access');
    }
    return $next($request);
}

// Register in Kernel.php and apply to routes
Route::middleware(['check.ip'])->group(function () {
    // Only accessible from allowed IPs
});
```

### Subdomain Routing

Restrict routes to specific subdomains:

```php
Route::domain('{account}.example.com')->group(function () {
    Route::get('/', function ($account) {
        // ...
    });
});
```

### Maintenance Mode

Restrict all routes except those with specific middleware:

```php
// Put application in maintenance mode
php artisan down

// Allow specific IPs
php artisan down --allow=127.0.0.1

// Specify a maintenance mode view
php artisan down --render="errors.maintenance"

// Create a bypass token
php artisan down --secret="1630542a-246b-4b66-afa1-dd72a4c43515"
// Then access: https://example.com/1630542a-246b-4b66-afa1-dd72a4c43515
```

## API-Specific Protection Methods

### API Authentication

#### Laravel Sanctum

For SPA authentication, mobile apps, and simple token-based API authentication:

```php
// Protect API routes with Sanctum
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Protect API routes with Sanctum and specific token abilities
Route::middleware(['auth:sanctum', 'abilities:write-data'])->post('/example/data', function (Request $request) {
    // Only accessible with tokens that have the 'write-data' ability
});
```

Token abilities (also called scopes) allow you to grant specific permissions to API tokens. Available abilities are defined in `config/api-scopes.php`. When creating a token, you can specify which abilities it should have:

```php
// Create a token with specific abilities
$token = $user->createToken('token-name', ['read-data', 'write-data']);

// Check if a token has a specific ability
$request->user()->tokenCan('write-data');
```

#### Laravel Passport

For full OAuth2 server implementation:

```php
// Protect API routes with Passport
Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

// Specify OAuth scopes
Route::middleware(['auth:api', 'scope:check-status'])->get('/status', function () {
    // ...
});
```

### API Rate Limiting

Specific rate limiting for APIs:

```php
// Rate limit API endpoints
Route::middleware(['throttle:api'])->prefix('api')->group(function () {
    Route::get('/users', function () {
        // ...
    });
});
```

### CORS (Cross-Origin Resource Sharing)

Control which domains can access your API:

```php
// CORS is configured in config/cors.php
// All API routes are automatically protected by the CORS middleware
```

## Best Practices

1. **Organize Routes Logically**: Group related routes together for better readability and maintenance.

2. **Use Middleware Groups**: Create custom middleware groups for common protection patterns.

3. **Consistent Naming**: Use consistent route naming conventions throughout your application.

4. **Resource Routes**: Use resource routes for standard CRUD operations to maintain RESTful principles.

5. **Route Caching**: Cache routes in production for better performance:
   ```bash
   php artisan route:cache
   ```

6. **Layer Security**: Apply multiple protection methods when needed (e.g., authentication + permission + rate limiting).

7. **Validate Route Parameters**: Use route parameter constraints to ensure data validity:
   ```php
   Route::get('/user/{id}', function ($id) {
       // ...
   })->where('id', '[0-9]+');
   ```

8. **Document API Routes**: Use tools like Laravel Scribe or Swagger to document API routes.

## Implementation in This Application

This application uses several route protection methods:

1. **Authentication**: Many routes are protected with the `auth` middleware.

2. **Email Verification**: Profile routes require email verification with the `verified` middleware.

3. **Role-Based Access**: Admin routes are protected with the `role:admin` middleware.

4. **Permission-Based Access**: Some routes require specific permissions like `view contact page url`.

5. **Guest Restriction**: Password reset routes are restricted to guests with the `guest` middleware.

6. **Rate Limiting**: Some routes are protected with the `throttle` middleware to prevent abuse.

7. **CSRF Protection**: All web routes are protected against CSRF attacks.

## Related Documentation

- [Laravel Routing Documentation](https://laravel.com/docs/routing)
- [Laravel Middleware Documentation](https://laravel.com/docs/middleware)
- [Laravel Authentication Documentation](https://laravel.com/docs/authentication)
- [Laravel Authorization Documentation](https://laravel.com/docs/authorization)
- [Spatie Laravel Permission Documentation](https://spatie.be/docs/laravel-permission)
- [Laravel Sanctum Documentation](https://laravel.com/docs/sanctum)
- [Routes Documentation](../Routes.md) - Overview of all routes in this application
- [Permissions Documentation](../Permissions.md) - Details on the permission system in this application
