# Auth Test Helpers Documentation

This document describes the `AuthTestHelpers` trait which provides helper methods for authentication-related tests in the application.

## Overview

The `AuthTestHelpers` trait simplifies the creation of authentication-related tests by providing reusable methods for common tasks such as:

- Creating roles for testing
- Creating users with different roles
- Creating unverified users
- Asserting that users have or don't have specific roles

## Usage

To use the `AuthTestHelpers` trait in your test class, add the following use statement:

```php
use Tests\Feature\Traits\AuthTestHelpers;

class YourControllerTest extends TestCase
{
    use RefreshDatabase, AuthTestHelpers;
    
    // Your test methods...
}
```

## Available Methods

### Creating Roles

```php
protected function createRoles(): array
```

Creates the 'admin' and 'user' roles if they don't exist and returns them in an array.

**Example:**
```php
$roles = $this->createRoles();
$adminRole = $roles['admin'];
$userRole = $roles['user'];
```

### Creating Users with Roles

#### Create Admin User

```php
protected function createAdminUser(array $attributes = []): User
```

Creates a user with the 'admin' role. You can pass additional attributes to customize the user.

**Example:**
```php
$admin = $this->createAdminUser([
    'name' => 'Admin Name',
    'email' => 'admin@example.com',
]);
```

#### Create Regular User

```php
protected function createRegularUser(array $attributes = []): User
```

Creates a user with the 'user' role. You can pass additional attributes to customize the user.

**Example:**
```php
$user = $this->createRegularUser([
    'name' => 'User Name',
    'email' => 'user@example.com',
]);
```

#### Create Unverified User

```php
protected function createUnverifiedUser(array $attributes = []): User
```

Creates an unverified user with the 'user' role. You can pass additional attributes to customize the user.

**Example:**
```php
$unverifiedUser = $this->createUnverifiedUser([
    'name' => 'Unverified User',
    'email' => 'unverified@example.com',
]);
```

### Assertions

#### Assert User Has Role

```php
protected function assertUserHasRole(User $user, string $role): void
```

Asserts that a user has a specific role.

**Example:**
```php
$user = $this->createRegularUser();
$this->assertUserHasRole($user, 'user');
```

#### Assert User Does Not Have Role

```php
protected function assertUserDoesNotHaveRole(User $user, string $role): void
```

Asserts that a user does not have a specific role.

**Example:**
```php
$user = $this->createRegularUser();
$this->assertUserDoesNotHaveRole($user, 'admin');
```

## Example Test Case

Here's an example of how to use the `AuthTestHelpers` trait in a test case:

```php
use Tests\Feature\Traits\AuthTestHelpers;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminControllerTest extends TestCase
{
    use RefreshDatabase, AuthTestHelpers;
    
    public function test_only_admin_can_access_admin_dashboard()
    {
        // Create an admin user
        $admin = $this->createAdminUser();
        
        // Create a regular user
        $user = $this->createRegularUser();
        
        // Admin should be able to access the admin dashboard
        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertStatus(200);
            
        // Regular user should not be able to access the admin dashboard
        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertStatus(403);
    }
}
```
