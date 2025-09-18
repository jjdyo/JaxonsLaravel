# User Model Documentation

## Overview
The `User` model represents users in the application and is used for authentication, authorization, and user management. It implements email verification and integrates with Laravel Sanctum for API token management and Spatie's permission package for role-based access control.

## Location
`app\Models\User.php`

## Namespace
`App\Models`

## Inheritance
Extends `Illuminate\Foundation\Auth\User as Authenticatable`

## Implements
`Illuminate\Contracts\Auth\MustVerifyEmail` - Enables email verification functionality

## Dependencies
- `App\Notifications\ResetPasswordEmail`
- `App\Notifications\VerifyEmailNotification`
- `Illuminate\Contracts\Auth\MustVerifyEmail`
- `Illuminate\Database\Eloquent\Factories\HasFactory`
- `Illuminate\Foundation\Auth\User as Authenticatable`
- `Illuminate\Notifications\Notifiable`
- `Laravel\Sanctum\HasApiTokens`
- `Spatie\Permission\Traits\HasRoles`

## Traits
- `HasApiTokens` - Provides API token functionality via Laravel Sanctum
- `HasFactory` - Provides factory functionality for testing
- `Notifiable` - Allows the model to receive notifications
- `HasRoles` - Provides role-based permissions (from Spatie's permission package)

## Properties

### Fillable Attributes
These attributes can be mass-assigned:
```php
protected $fillable = [
    'name',
    'email',
    'password',
    'email_verified_at',
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

## Query Scopes
- `verified()` – Only users with a verified email.
- `unverified()` – Only users without a verified email.
- `admins()` – Users with the admin role (via Spatie's role scope).
- `selectSummary()` – Selects common summary columns for listings.
- `withRolesMinimal()` – Eager loads roles with minimal columns.
- `search(string $term)` – Case-insensitive search by name or email.
- `orderByName(string $direction = 'asc')` – Orders by name.
- `newest()` – Orders by most recent creation date.

## Methods

### `sendPasswordResetNotification($token)`
Sends a custom password reset notification to the user.

**Parameters:**
- `$token` - The password reset token

**Returns:** void

**Example Usage:**
```php
$user->sendPasswordResetNotification($resetToken);
```

### `sendEmailVerificationNotification()`
Sends a custom email verification notification to the user.

**Parameters:** None

**Returns:** void

**Example Usage:**
```php
$user->sendEmailVerificationNotification();
```

### `canBeDeletedBy(?User $actor): bool`
Returns whether this user can be deleted by the given actor. Currently prevents self-deletion.

### `markEmailVerified(): void`
Marks the user's email as verified (no-op if already verified).

### `markEmailUnverified(): void`
Marks the user's email as unverified.

## Relationships
- `tokens()` - Morphs many relationship to Sanctum PersonalAccessToken model
- Has many roles through the `HasRoles` trait

## Usage in Controllers
The User model is primarily used in:
- `AuthController` - For authentication operations
- `UserManagementController` - For user management operations

## Security Considerations
- Passwords are automatically hashed via the 'password' cast
- Email verification is implemented via MustVerifyEmail interface
- Sensitive attributes like password and remember_token are hidden from JSON/array serialization
- Role-based access control is implemented via the HasRoles trait
