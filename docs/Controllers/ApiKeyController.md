# ApiKeyController Documentation

## Overview
The `ApiKeyController` is responsible for managing API tokens for both administrators and regular users. It provides functionality for creating, viewing, and revoking API tokens using Laravel Sanctum.

The controller supports token scoping, allowing users to select specific permissions for their API tokens. Available scopes are defined in the `config/api-scopes.php` configuration file.

## Location
`app\Http\Controllers\ApiKeyController.php`

## Namespace
`App\Http\Controllers`

## Dependencies
The controller relies on the following imports:
- `App\Models\ApiKey` - For API key model operations
- `App\Models\User` - For user model interactions
- `Illuminate\Http\Request` - For handling HTTP requests
- `Illuminate\Http\RedirectResponse` - For redirecting after actions
- `Illuminate\View\View` - For returning views

The controller also uses the `config/api-scopes.php` configuration file to define available token scopes.

## Methods

### Admin Methods

#### `index(User $user)`
Displays a listing of a specific user's API keys (admin view).

**Parameters:** 
- `$user` - The user whose API keys to display

**Returns:** View (`admin.users.api-keys.index`)  
**Middleware:** 
- `auth` - Requires user to be authenticated
- `verified` - Requires user to have a verified email
- `role:admin` - Requires user to have the admin role

**Example Usage:**
```php
// In routes/web.php
Route::get('/admin/users/{user}/api-keys', [ApiKeyController::class, 'index'])->name('admin.users.api-keys.index');
```

#### `create(User $user)`
Shows the form for creating a new API key for a specific user (admin view).

**Parameters:** 
- `$user` - The user to create an API key for

**Returns:** View (`admin.users.api-keys.create`)  
**Middleware:** Same as `index()`

**Example Usage:**
```php
// In routes/web.php
Route::get('/admin/users/{user}/api-keys/create', [ApiKeyController::class, 'create'])->name('admin.users.api-keys.create');
```

#### `store(Request $request, User $user)`
Creates and stores a new API key for a specific user (admin view).

**Parameters:** 
- `$request` - The HTTP request containing form data
- `$user` - The user to create an API key for

**Returns:** RedirectResponse to the API keys index with success message and the plain text token  
**Middleware:** Same as `index()`

**Example Usage:**
```php
// In routes/web.php
Route::post('/admin/users/{user}/api-keys', [ApiKeyController::class, 'store'])->name('admin.users.api-keys.store');
```

#### `destroy(User $user, ApiKey $token)`
Deletes a specific API key for a user (admin view).

**Parameters:** 
- `$user` - The user who owns the API key
- `$token` - The token to delete

**Returns:** RedirectResponse to the API keys index with success message  
**Middleware:** Same as `index()`

**Example Usage:**
```php
// In routes/web.php
Route::delete('/admin/users/{user}/api-keys/{token}', [ApiKeyController::class, 'destroy'])->name('admin.users.api-keys.destroy');
```

### User Methods

#### `userIndex()`
Displays a listing of the authenticated user's API keys.

**Parameters:** None  
**Returns:** View (`user.api-tokens.index`)  
**Middleware:** 
- `auth` - Requires user to be authenticated
- `verified` - Requires user to have a verified email

**Example Usage:**
```php
// In routes/web.php
Route::get('/api-tokens', [ApiKeyController::class, 'userIndex'])->name('api-tokens.index');
```

#### `userCreate()`
Shows the form for creating a new API key for the authenticated user.

**Parameters:** None  
**Returns:** View (`user.api-tokens.create`)  
**Middleware:** Same as `userIndex()`

**Example Usage:**
```php
// In routes/web.php
Route::get('/api-tokens/create', [ApiKeyController::class, 'userCreate'])->name('api-tokens.create');
```

#### `userStore(Request $request)`
Creates and stores a new API key for the authenticated user.

**Parameters:** 
- `$request` - The HTTP request containing form data (name, expiration, and scopes)

**Validation:**
- `name` - Required, string, alphanumeric only, max 100 characters
- `expiration` - Required, must be one of: week, month, year
- `scopes` - Required, array with at least one scope
- Each scope must be a valid scope defined in the `config/api-scopes.php` file

**Returns:** RedirectResponse to the API tokens index with success message and the plain text token  
**Middleware:** Same as `userIndex()`

**Example Usage:**
```php
// In routes/web.php
Route::post('/api-tokens', [ApiKeyController::class, 'userStore'])->name('api-tokens.store');
```

#### `userShow(ApiKey $token)`
Displays details for a specific API token.

**Parameters:** 
- `$token` - The token to show details for

**Returns:** View (`user.api-tokens.show`)  
**Middleware:** Same as `userIndex()`

**Example Usage:**
```php
// In routes/web.php
Route::get('/api-tokens/{token}', [ApiKeyController::class, 'userShow'])->name('api-tokens.show');
```

#### `userDestroy(ApiKey $token)`
Deletes a specific API token.

**Parameters:** 
- `$token` - The token to delete

**Returns:** RedirectResponse to the API tokens index with success message  
**Middleware:** Same as `userIndex()`

**Example Usage:**
```php
// In routes/web.php
Route::delete('/api-tokens/{token}', [ApiKeyController::class, 'userDestroy'])->name('api-tokens.destroy');
```

#### `authorizeToken(ApiKey $token)`
Helper method to ensure a token belongs to the authenticated user.

**Parameters:** 
- `$token` - The token to authorize

**Returns:** void (aborts with 403 if unauthorized)  
**Access:** private

## Views Used
- `admin.users.api-keys.index` - Admin view for listing a user's API keys
- `admin.users.api-keys.create` - Admin view for creating a new API key
- `user.api-tokens.index` - User view for listing their API tokens
- `user.api-tokens.create` - User view for creating a new API token
- `user.api-tokens.show` - User view for showing API token details

## ApiKey Model

The controller uses the `ApiKey` model, which extends Laravel Sanctum's `PersonalAccessToken` model. This model encapsulates the logic for API key operations, following the "Fat Models, Skinny Controllers" pattern.

### Model Location
`app\Models\ApiKey.php`

### Model Methods

#### `getAllForUser(User $user)`
Gets all API keys for a user, ordered by creation date.

**Parameters:**
- `$user` - The user whose API keys to retrieve

**Returns:** Collection of API keys

#### `createForUser(User $user, string $name, array $abilities = ['*'], ?\DateTime $expiresAt = null)`
Creates a new API key for a user.

**Parameters:**
- `$user` - The user to create the API key for
- `$name` - The name of the API key
- `$abilities` - The abilities of the API key (default: ['*'])
- `$expiresAt` - The expiration date of the API key (default: null)

**Returns:** NewAccessToken containing the plain text token

#### `createWithExpiration(User $user, string $name, array $scopes, string $expiration)`
Creates a new API key with expiration based on a time period.

**Parameters:**
- `$user` - The user to create the API key for
- `$name` - The name of the API key
- `$scopes` - The scopes of the API key
- `$expiration` - The expiration period ('week', 'month', 'year')

**Returns:** NewAccessToken containing the plain text token

#### `belongsToUser(User $user)`
Checks if the token belongs to the given user.

**Parameters:**
- `$user` - The user to check

**Returns:** Boolean indicating whether the token belongs to the user

#### `scopeForUser(Builder $query, User $user)`
Scope a query to only include tokens for a specific user.

**Parameters:**
- `$query` - The query builder
- `$user` - The user to filter by

**Returns:** Modified query builder

## Token Scopes Configuration

API token scopes are defined in the `config/api-scopes.php` configuration file. This file contains an array of available scopes, where each key is the scope identifier and each value is a human-readable description of the scope.

### Example Configuration

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

### Adding New Scopes

To add a new scope:

1. Edit the `config/api-scopes.php` file
2. Add a new entry to the `scopes` array with a unique key and descriptive value
3. The key will be used internally for token validation
4. The value will be displayed to users in the UI

For example, to add a new scope for managing users:

```php
'manage-users' => 'Manage user accounts',
```

Once added, the new scope will automatically appear in the token creation form and can be selected by users.
