# AuthController Documentation

## Overview
The `AuthController` is responsible for handling all authentication-related functionality in the application, including user login, registration, and logout processes. It provides secure authentication flows with rate limiting protection against brute force attacks.

## Location
`app\Http\Controllers\AuthController.php`

## Namespace
`App\Http\Controllers`

## Dependencies
The controller relies on the following imports:
- `App\Models\User` - User model for creating and managing user accounts
- `Illuminate\Http\Request` - For handling HTTP requests
- `Illuminate\Support\Facades\Auth` - Laravel's authentication system
- `Illuminate\Support\Facades\Hash` - For secure password hashing
- `Illuminate\Support\Facades\RateLimiter` - For rate limiting login attempts

## Methods

### `showLoginForm()`
Displays the login form to the user.

**Parameters:** None  
**Returns:** View (`auth.login`)  
**Example Usage:**
```php
// In routes/web.php
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
```

### `showRegisterForm()`
Displays the registration form to the user.

**Parameters:** None  
**Returns:** View (`auth.register`)  
**Example Usage:**
```php
// In routes/web.php
Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
```

### `processLogin(Request $request)`
Handles the login form submission, validates credentials, and authenticates the user.

**Parameters:**
- `$request` (Request): Contains form data including email and password

**Returns:** Redirect to intended page or back to login form with errors  
**Security Features:**
- Rate limiting (5 attempts per email)
- Input validation
- Session regeneration to prevent session fixation attacks

**Example Usage:**
```php
// In routes/web.php
Route::post('/login', [AuthController::class, 'processLogin'])->name('login.process');
```

### `logout(Request $request)`
Logs out the currently authenticated user and invalidates their session.

**Parameters:**
- `$request` (Request): The current request

**Returns:** Redirect to home route  
**Example Usage:**
```php
// In routes/web.php
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
```

### `processRegister(Request $request)`
Handles the registration form submission, creates a new user, and automatically logs them in.

**Parameters:**
- `$request` (Request): Contains form data including name, email, and password

**Returns:** Redirect to home route with success message  
**Validation Rules:**
- Name: required, string, max 255 characters
- Email: required, valid email format, unique in users table, max 255 characters
- Password: required, minimum 8 characters, must be confirmed

**Example Usage:**
```php
// In routes/web.php
Route::post('/register', [AuthController::class, 'processRegister'])->name('register.process');
```

## Models Used
- `User` - For creating and authenticating users

## Role Management
The controller assigns the 'user' role to newly registered users via the `assignRole()` method, suggesting integration with a role-based access control system (likely using Spatie's Laravel-permission package).

## Security Features
- Password hashing using Laravel's Hash facade
- Rate limiting on login attempts
- Session regeneration to prevent session fixation attacks
- Validation of user inputs
- Secure password requirements (minimum 8 characters, confirmation required)
