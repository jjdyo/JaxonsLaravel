# Permissions Documentation

## Introduction

This application uses Spatie's Laravel Permission package (v6.16) for role and permission management. This package provides a flexible way to implement role-based access control (RBAC) in Laravel applications.

## Installation and Configuration

The Spatie Permission package is already installed in this application. If you need to install it in another project, you can do so via Composer:

```bash
composer require spatie/laravel-permission
```

After installation, publish the configuration file:

```bash
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
```

The configuration file is located at `config/permission.php` and contains settings for:
- Model definitions
- Table names
- Cache settings
- Team support (disabled by default)
- Wildcard permissions (disabled by default)

## Database Structure

The package creates several tables in your database:
- `roles` - Stores role definitions
- `permissions` - Stores permission definitions
- `model_has_roles` - Pivot table linking users to roles
- `model_has_permissions` - Pivot table linking users to permissions
- `role_has_permissions` - Pivot table linking roles to permissions

## Basic Usage

### Working with Roles and Permissions

#### Creating Roles and Permissions

```php
// Create a role
$role = Role::create(['name' => 'writer']);

// Create a permission
$permission = Permission::create(['name' => 'edit articles']);
```

#### Assigning Permissions to Roles

```php
// Assign a permission to a role
$role->givePermissionTo($permission);

// Assign multiple permissions to a role
$role->syncPermissions($permissions);
```

#### Assigning Roles to Users

```php
// Assign a role to a user
$user->assignRole('writer');

// Assign multiple roles to a user
$user->syncRoles(['writer', 'admin']);
```

#### Direct Permission Assignment

```php
// Give a permission directly to a user
$user->givePermissionTo('edit articles');
```

### Checking Permissions and Roles

#### Check if a User Has a Role

```php
// Check if user has a role
$user->hasRole('writer');

// Check if user has any of the given roles
$user->hasAnyRole(['writer', 'admin']);

// Check if user has all of the given roles
$user->hasAllRoles(['writer', 'admin']);
```

#### Check if a User Has a Permission

```php
// Check if user has a permission
$user->hasPermissionTo('edit articles');
$user->can('edit articles'); // Laravel's Gate facade

// Check if user has any of the given permissions
$user->hasAnyPermission(['edit articles', 'publish articles']);
```

## Middleware

The package provides middleware for protecting routes based on roles and permissions:

### Role Middleware

```php
Route::middleware('role:admin')->group(function () {
    // Routes accessible only to users with the 'admin' role
});
```

### Permission Middleware

```php
Route::middleware('permission:edit articles')->group(function () {
    // Routes accessible only to users with the 'edit articles' permission
});
```

### Role or Permission Middleware

```php
Route::middleware('role_or_permission:admin|edit articles')->group(function () {
    // Routes accessible to users with either the 'admin' role OR the 'edit articles' permission
});
```

## Implementation in This Application

### User Model

The `User` model uses the `HasRoles` trait to enable role and permission functionality:

```php
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;
    
    // ...
}
```

### Custom Role Model

This application extends the default Spatie Role model:

```php
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    protected $fillable = ['name', 'guard_name'];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'role_user');
    }
}
```

### Defined Roles and Permissions

The application defines the following roles and permissions in the `PermissionSeeder`:

**Roles:**
- `admin` - Has all permissions
- `user` - Basic user role

**Permissions:**
- `view about page url` - Permission to view the about page
- `view contact page url` - Permission to view the contact page

### Route Protection

Routes are protected using middleware:

```php
Route::middleware(['auth', 'permission:view contact page url'])->group(function () {
    Route::get('/contact', [PageController::class, 'contact'])->name('contact');
});
```

## Best Practices

1. **Use Role-Based Permissions**: Assign permissions to roles and then assign roles to users, rather than assigning permissions directly to users.

2. **Descriptive Permission Names**: Use clear, descriptive names for permissions that indicate the action and the resource.

3. **Cache Permissions**: The package caches permissions by default for 24 hours. You can adjust this in the configuration.

4. **Use Middleware for Route Protection**: Protect routes using middleware rather than checking permissions in controllers.

5. **Blade Directives**: Use the provided Blade directives for permission checks in views:

```blade
@role('admin')
    <!-- Admin-only content -->
@endrole

@can('edit articles')
    <!-- Content for users who can edit articles -->
@endcan
```

## Artisan Commands

The package provides several Artisan commands:

```bash
# Clear the permission cache
php artisan permission:cache-reset

# Create a permission
php artisan permission:create-permission "edit articles"

# Create a role
php artisan permission:create-role "writer"

# Show all roles and permissions
php artisan permission:show
```

## Troubleshooting

1. **Permission Cache**: If changes to permissions aren't reflected, clear the permission cache:
   ```bash
   php artisan permission:cache-reset
   ```

2. **Multiple Guards**: If you're using multiple authentication guards, make sure to specify the guard when creating roles and permissions:
   ```php
   $role = Role::create(['name' => 'writer', 'guard_name' => 'api']);
   ```

3. **Super Admin**: For a super admin role that bypasses permission checks, you can use Laravel's Gate::before callback in `AuthServiceProvider`:
   ```php
   Gate::before(function ($user, $ability) {
       return $user->hasRole('super-admin') ? true : null;
   });
   ```

## Additional Resources

- [Spatie Laravel Permission Documentation](https://spatie.be/docs/laravel-permission)
- [GitHub Repository](https://github.com/spatie/laravel-permission)
- [Laravel Documentation on Authorization](https://laravel.com/docs/authorization)
