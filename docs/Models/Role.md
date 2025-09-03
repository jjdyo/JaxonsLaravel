# Role Model Documentation

## Overview
The `Role` model extends Spatie's Role model to provide role-based access control in the application. It defines the structure and relationships for user roles, which are used for authorization and permission management.

## Location
`app\Models\Role.php`

## Namespace
`App\Models`

## Inheritance
Extends `Spatie\Permission\Models\Role`

## Dependencies
- `Spatie\Permission\Models\Role`
- `Illuminate\Database\Eloquent\Relations\BelongsToMany`
- `Illuminate\Database\Eloquent\Factories\HasFactory`

## Properties

### Fillable Attributes
These attributes can be mass-assigned:
```php
protected $fillable = ['name', 'guard_name'];
```

## Methods

### `users()`
Defines the many-to-many relationship with users.

**Returns:** BelongsToMany relationship to User model

**Example Usage:**
```php
$role = Role::find(1);
$usersWithRole = $role->users()->get();
```

## Relationships
- `users()` - Many-to-many relationship with the User model through the 'role_user' pivot table

## Usage in Controllers
The Role model is primarily used for:
- Defining user roles (e.g., admin, user)
- Assigning roles to users
- Role-based access control

## Integration with Spatie Permission Package
This model extends Spatie's Role model, which provides:
- Role-based access control
- Permission-based access control
- Middleware for protecting routes based on roles and permissions

### Example Usage
```php
// Create a new role
$role = Role::create(['name' => 'editor']);

// Assign a role to a user
$user->assignRole('editor');

// Check if a user has a role
if ($user->hasRole('editor')) {
    // User is an editor
}

// Get all users with a specific role
$editors = Role::where('name', 'editor')->first()->users;
```

## Security Considerations
- Roles should be assigned carefully to maintain proper access control
- Consider using permissions in addition to roles for more granular access control
- Use middleware to protect routes based on roles and permissions
