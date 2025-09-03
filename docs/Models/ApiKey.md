# ApiKey Model Documentation

## Overview
The `ApiKey` model extends Laravel Sanctum's `PersonalAccessToken` model to provide enhanced functionality for API key management. It follows the "Fat Models, Skinny Controllers" pattern by encapsulating database operations and business logic related to API keys.

## Location
`app\Models\ApiKey.php`

## Namespace
`App\Models`

## Inheritance
Extends `Laravel\Sanctum\PersonalAccessToken`

## Dependencies
- `Illuminate\Database\Eloquent\Builder`
- `Illuminate\Database\Eloquent\Collection`
- `Laravel\Sanctum\PersonalAccessToken`
- `App\Models\User`

## Methods

### `getAllForUser(User $user)`
Gets all API keys for a user, ordered by creation date.

**Parameters:**
- `$user` - The user whose API keys to retrieve

**Returns:** Collection of API keys

**Example Usage:**
```php
$apiKeys = ApiKey::getAllForUser($user);
```

### `createForUser(User $user, string $name, array $abilities = ['*'], ?\DateTime $expiresAt = null)`
Creates a new API key for a user.

**Parameters:**
- `$user` - The user to create the API key for
- `$name` - The name of the API key
- `$abilities` - The abilities of the API key (default: ['*'])
- `$expiresAt` - The expiration date of the API key (default: null)

**Returns:** NewAccessToken containing the plain text token

**Example Usage:**
```php
$token = ApiKey::createForUser(
    $user,
    'My API Key',
    ['read', 'write'],
    now()->addYear()
);
```

### `createWithExpiration(User $user, string $name, array $scopes, string $expiration)`
Creates a new API key with expiration based on a time period.

**Parameters:**
- `$user` - The user to create the API key for
- `$name` - The name of the API key
- `$scopes` - The scopes of the API key
- `$expiration` - The expiration period ('week', 'month', 'year')

**Returns:** NewAccessToken containing the plain text token

**Example Usage:**
```php
$token = ApiKey::createWithExpiration(
    $user,
    'My API Key',
    ['read', 'write'],
    'month'
);
```

### `belongsToUser(User $user)`
Checks if the token belongs to the given user.

**Parameters:**
- `$user` - The user to check

**Returns:** Boolean indicating whether the token belongs to the user

**Example Usage:**
```php
if ($apiKey->belongsToUser($user)) {
    // Token belongs to the user
}
```

### `scopeForUser(Builder $query, User $user)`
Scope a query to only include tokens for a specific user.

**Parameters:**
- `$query` - The query builder
- `$user` - The user to filter by

**Returns:** Modified query builder

**Example Usage:**
```php
$userApiKeys = ApiKey::forUser($user)->get();
```

## Relationships
Inherits all relationships from the `PersonalAccessToken` model, including:

- `tokenable()` - Polymorphic relationship to the model that owns the token

## Usage in Controllers
The `ApiKey` model is primarily used in the `ApiKeyController` to handle API key operations. By moving database operations and business logic to the model, the controller becomes leaner and more focused on handling HTTP requests and responses.

## Security Considerations
- API keys are sensitive data and should be handled securely
- The plain text token is only available immediately after creation
- Tokens can be scoped to limit their capabilities
- Tokens can be set to expire after a certain period
