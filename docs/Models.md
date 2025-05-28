# Models Documentation

## Overview
This document provides information about the data models used in the application. Models represent database tables and define the structure and relationships of the application's data.

## User Model
The User model represents users in the application and is used for authentication and authorization.

### Location
`app\Models\User.php`

### Namespace
`App\Models`

### Extends
`Illuminate\Foundation\Auth\User as Authenticatable`

### Traits
- `HasFactory` - Provides factory functionality for testing
- `Notifiable` - Allows the model to receive notifications
- `HasRoles` - Provides role-based permissions (from Spatie's permission package)

### Fillable Attributes
These attributes can be mass-assigned:
```php
protected $fillable = [
    'name',
    'email',
    'password',
];
```

### Hidden Attributes
These attributes are hidden when the model is converted to an array or JSON:
```php
protected $hidden = [
    'password',
    'remember_token',
];
```

### Cast Attributes
These attributes are automatically cast to specific types:
```php
protected function casts(): array
{
    return [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
}
```

### Relationships
- Has many roles through the `HasRoles` trait

### Usage
The User model is used for:
- Authentication (login/logout)
- User registration
- Role-based access control
- User profile management

## Role Model
The Role model represents user roles in the application and is used for role-based access control.

### Location
`app\Models\Role.php`

### Namespace
`App\Models`

### Extends
`Spatie\Permission\Models\Role`

### Fillable Attributes
These attributes can be mass-assigned:
```php
protected $fillable = ['name, guard_name'];
```

### Relationships
```php
public function users(): BelongsToMany
{
    return $this->belongsToMany(User::class, 'role_user');
}
```

### Usage
The Role model is used for:
- Defining user roles (e.g., admin, user)
- Assigning roles to users
- Role-based access control

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
