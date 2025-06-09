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
- `Illuminate\Support\Facades\Password` - For password reset functionality
- `Illuminate\Auth\Events\PasswordReset` - For firing password reset events
- `Illuminate\Support\Str` - For generating random strings

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

### `profile()`
Displays the user profile page. This route is protected by authentication and email verification middleware.

**Parameters:** None  
**Returns:** View (`pages.profile`)  
**Middleware:** 
- `auth` - Requires user to be authenticated
- `verified` - Requires user to have a verified email address

**Example Usage:**
```php
// In routes/web.php
Route::get('/profile', [AuthController::class, 'profile'])->middleware(['auth', 'verified'])->name('profile');
```

### `editProfile()`
Displays the profile edit form. This route is protected by authentication and email verification middleware.

**Parameters:** None  
**Returns:** View (`pages.profile_edit`) with the authenticated user data  
**Middleware:** 
- `auth` - Requires user to be authenticated
- `verified` - Requires user to have a verified email address

**Example Usage:**
```php
// In routes/web.php
Route::get('/profile/edit', [AuthController::class, 'editProfile'])->middleware(['auth', 'verified'])->name('profile.edit');
```

### `updateProfile(Request $request)`
Handles the profile update form submission. This route is protected by authentication and email verification middleware.

**Parameters:**
- `$request` (Request): Contains form data including name and email

**Returns:** 
- If email is changed: Redirect to verification notice route with success message
- Otherwise: Redirect to profile route with success message

**Validation Rules:**
- Name: required, string, max 20 characters, regex pattern for letters, spaces, apostrophes, and hyphens
- Email: required, string, valid email format, unique in users table (except for current user), max 255 characters

**Email Change Behavior:**
- If the email address is changed, the user's email_verified_at field is set to null
- A new verification email is sent to the new address
- The user is redirected to the verification notice page

**Security Features:**
- Input validation with custom error messages
- Input sanitization (trimming whitespace and normalizing spaces)
- Email verification requirement for email changes

**Example Usage:**
```php
// In routes/web.php
Route::put('/profile/edit', [AuthController::class, 'updateProfile'])->middleware(['auth', 'verified'])->name('profile.update');
```

### `processRegister(Request $request)`
Handles the registration form submission, creates a new user, logs them in, and sends a verification email.

**Parameters:**
- `$request` (Request): Contains form data including name, email, and password

**Returns:** Redirect to verification notice route  
**Validation Rules:**
- Name: required, string, max 255 characters
- Email: required, valid email format, unique in users table, max 255 characters
- Password: required, minimum 8 characters, must be confirmed

**Email Verification:**
- Sends a verification email to the user's email address
- Redirects to the verification notice page where the user is informed to check their email

**Example Usage:**
```php
// In routes/web.php
Route::post('/register', [AuthController::class, 'processRegister'])->name('register.process');
```

### `showForgotPasswordForm()`
Displays the forgot password form to the user.

**Parameters:** None  
**Returns:** View (`auth.forgot-password`)  
**Example Usage:**
```php
// In routes/web.php
Route::get('/forgot-password', [AuthController::class, 'showForgotPasswordForm'])->middleware('guest')->name('password.request');
```

### `sendResetLinkEmail(Request $request)`
Handles the forgot password form submission and sends a password reset link to the user's email.

**Parameters:**
- `$request` (Request): Contains form data including email

**Returns:** Redirect back with status message or errors  
**Validation Rules:**
- Email: required, valid email format

**Example Usage:**
```php
// In routes/web.php
Route::post('/forgot-password', [AuthController::class, 'sendResetLinkEmail'])->middleware('guest')->name('password.email');
```

### `showResetPasswordForm(Request $request)`
Displays the reset password form to the user.

**Parameters:**
- `$request` (Request): Contains token and email parameters

**Returns:** View (`auth.reset-password`) with the request data  
**Example Usage:**
```php
// In routes/web.php
Route::get('/reset-password/{token}', [AuthController::class, 'showResetPasswordForm'])->middleware('guest')->name('password.reset');
```

### `resetPassword(Request $request)`
Handles the reset password form submission and updates the user's password.

**Parameters:**
- `$request` (Request): Contains form data including token, email, password, and password_confirmation

**Returns:** 
- If successful: Redirect to login route with success message
- Otherwise: Redirect back with errors

**Validation Rules:**
- Token: required
- Email: required, valid email format
- Password: required, minimum 8 characters, must be confirmed

**Security Features:**
- Password hashing using Laravel's Hash facade
- Generation of new remember token
- Firing of PasswordReset event

**Example Usage:**
```php
// In routes/web.php
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('guest')->name('password.update');
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
- Email verification for new user registrations
- Email verification required for changing email addresses
- Protected routes requiring verified email addresses
- Secure password reset functionality
- Token-based password reset links
- Password reset links expire after a certain time
