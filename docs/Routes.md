# Routes Documentation

## Overview
This document provides a comprehensive overview of all routes defined in the application. Routes are organized by their purpose and include information about the associated controller methods, middleware, and naming.

## Web Routes
All web routes are defined in `routes/web.php`.

## API Routes
All API routes are defined in `routes/api.php`.

> **Note:** All API routes are protected by Cloudflare Zero Trust and require additional authentication headers. See [Cloudflare Zero Trust Access](Security/Cloudflare%20Zero%20Trust%20Proxy.md) for details on how to access the API.

### Default API Routes
These routes are accessible from any domain with proper authentication.

| URL | Method | Controller Action | Name | Middleware | Description |
|-----|--------|------------------|------|------------|-------------|
| `/user` | GET | Closure | None | `auth:sanctum` | Returns the authenticated user's information |

### API URL Routes
These routes are only accessible from the API URL configured in `config('app.api_url')` (api-laravel.jaxonville.com) and require Sanctum authentication.

| URL | Method | Controller Action | Name | Middleware | Description |
|-----|--------|------------------|------|------------|-------------|
| `/example/data` | GET | `ExampleApiController@getData` | None | `auth:sanctum` | Returns example data for the authenticated user |

### API Authentication Requirements
To access any API route, you need:

1. **Cloudflare Access Credentials** - Include `CF-Access-Client-Id` and `CF-Access-Client-Secret` headers
2. **API Token** - Include the `Authorization: Bearer YOUR_API_TOKEN` header
3. **Rate Limiting** - All API requests are limited to 60 requests per minute

### Page Routes
These routes handle the rendering of basic pages in the application.

| URL | Method | Controller Action | Name | Middleware | Description |
|-----|--------|------------------|------|------------|-------------|
| `/` | GET | `PageController@home` | `home` | None | Displays the home page |
| `/about` | GET | `PageController@about` | `about` | None | Displays the about page |
| `/contact` | GET | `PageController@contact` | `contact` | `auth`, `permission:view contact page url` | Displays the contact page (requires authentication and permission) |

### Authentication Routes
These routes handle user authentication, including login, registration, and logout.

| URL | Method | Controller Action | Name | Middleware | Description |
|-----|--------|------------------|------|------------|-------------|
| `/user` | GET | `AuthController@showLoginForm` | `login` | None | Displays the login form |
| `/user` | POST | `AuthController@processLogin` | `login.process` | None | Processes the login form submission |
| `/logout` | POST | `AuthController@logout` | `logout` | `auth` | Logs out the authenticated user |
| `/register` | GET | `AuthController@showRegisterForm` | `register` | None | Displays the registration form |
| `/register` | POST | `AuthController@processRegister` | `register.process` | None | Processes the registration form submission |

### Email Verification Routes
These routes handle the email verification process for new users.

| URL | Method | Controller Action | Name | Middleware | Description |
|-----|--------|------------------|------|------------|-------------|
| `/email/verify` | GET | Closure | `verification.notice` | `auth` | Displays the email verification notice page |
| `/email/verify/{id}/{hash}` | GET | Closure | `verification.verify` | `auth`, `signed` | Verifies the user's email address using the verification link |
| `/email/verification-notification` | POST | Closure | `verification.send` | `auth`, `throttle:5,1` | Resends the verification email (limited to 5 attempts per minute) |

### Verified Email Routes
These routes require authentication and a verified email address.

| URL | Method | Controller Action | Name | Middleware | Description |
|-----|--------|------------------|------|------------|-------------|
| `/profile` | GET | `AuthController@profile` | `profile` | `auth`, `verified` | Displays the user profile page |
| `/profile/edit` | GET | `AuthController@editProfile` | `profile.edit` | `auth`, `verified` | Displays the profile edit form |
| `/profile/edit` | PUT | `AuthController@updateProfile` | `profile.update` | `auth`, `verified` | Processes the profile update form submission |
| `/profile/password` | GET | `AuthController@showChangePasswordForm` | `profile.password.edit` | `auth`, `verified` | Displays the change password form (sends a reset link upon submit) |
| `/profile/password` | POST | `AuthController@processChangePassword` | `profile.password.update` | `auth`, `verified` | Processes the change password request and emails a reset link |
| `/api-tokens` | GET | `ApiKeyController@userIndex` | `api-tokens.index` | `auth`, `verified` | Displays the user's API tokens |
| `/api-tokens/create` | GET | `ApiKeyController@userCreate` | `api-tokens.create` | `auth`, `verified` | Displays the form to create a new API token |
| `/api-tokens` | POST | `ApiKeyController@userStore` | `api-tokens.store` | `auth`, `verified` | Creates a new API token |
| `/api-tokens/{token}` | GET | `ApiKeyController@userShow` | `api-tokens.show` | `auth`, `verified` | Displays details for a specific API token |
| `/api-tokens/{token}` | DELETE | `ApiKeyController@userDestroy` | `api-tokens.destroy` | `auth`, `verified` | Deletes a specific API token |

### Password Reset Routes
These routes handle the password reset process for users who have forgotten their passwords.

| URL | Method | Controller Action | Name | Middleware | Description |
|-----|--------|------------------|------|------------|-------------|
| `/forgot-password` | GET | `AuthController@showForgotPasswordForm` | `password.request` | `guest` | Displays the forgot password form |
| `/forgot-password` | POST | `AuthController@sendResetLinkEmail` | `password.email` | `guest` | Sends a password reset link to the user's email |
| `/reset-password/{password_callback}` | GET | `AuthController@showResetPasswordForm` | `password.reset` | `guest` | Displays the reset password form |
| `/reset-password` | POST | `AuthController@resetPassword` | `password.update` | `guest` | Processes the password reset form submission |
| `/password/reset/{password_callback}` | GET | `redirect()->route('password.reset')` | `password.reset.legacy` | `guest` | Legacy compatibility path that redirects to `/reset-password/{password_callback}` |

### Documentation Routes
These routes handle the documentation system, allowing users to browse and view markdown documentation files.

| URL | Method | Controller Action | Name | Middleware | Description |
|-----|--------|------------------|------|------------|-------------|
| `/docs` | GET | `DocsController@index` | `docs.index` | None | Displays the documentation index page with README content |
| `/docs/{filename}` | GET | `DocsController@show` | `docs.show` | None | Displays a specific documentation page based on the filename parameter (supports subdirectories) |

### Admin Routes
These routes handle administrative functions and require admin role.

| URL | Method | Controller Action | Name | Middleware | Description |
|-----|--------|------------------|------|------------|-------------|
| `/admin` | GET | `view('admin.dashboard')` | `admin.dashboard` | `auth`, `verified`, `role:admin` | Admin dashboard |
| `/admin/system-logs` | GET | `SystemLogsController@index` | `admin.system-logs.index` | `auth`, `verified`, `role:admin` | View available log channels and latest logs |
| `/admin/system-logs/fetch` | GET | `SystemLogsController@fetchLogs` | `admin.system-logs.fetch` | `auth`, `verified`, `role:admin` | Fetch chunks of a specific log file via JSON |
| `/admin/users` | GET | `UserManagementController@listUsers` | `admin.users.index` | `auth`, `verified`, `role:admin` | Lists all users |
| `/admin/users/{user}` | GET | `UserManagementController@showUser` | `admin.users.show` | `auth`, `verified`, `role:admin` | Shows details for a specific user |
| `/admin/users/{user}/edit` | GET | `UserManagementController@editUser` | `admin.users.edit` | `auth`, `verified`, `role:admin` | Shows the edit form for a specific user |
| `/admin/users/{user}` | PUT | `UserManagementController@updateUser` | `admin.users.update` | `auth`, `verified`, `role:admin` | Updates a specific user |
| `/admin/users/{user}` | DELETE | `UserManagementController@deleteUser` | `admin.users.destroy` | `auth`, `verified`, `role:admin` | Deletes a specific user |
| `/admin/users/{user}/verify` | POST | `UserManagementController@verifyUser` | `admin.users.verify` | `auth`, `verified`, `role:admin` | Manually verifies a user's email |
| `/admin/users/{user}/unverify` | POST | `UserManagementController@unverifyUser` | `admin.users.unverify` | `auth`, `verified`, `role:admin` | Manually unverifies a user's email |
| `/admin/users/{user}/roles` | POST | `UserManagementController@updateRoles` | `admin.users.roles.update` | `auth`, `verified`, `role:admin` | Updates a user's roles |
| `/admin/users/{user}/api-keys` | GET | `ApiKeyController@index` | `admin.users.api-keys.index` | `auth`, `verified`, `role:admin` | Lists a user's API keys |
| `/admin/users/{user}/api-keys/create` | GET | `ApiKeyController@create` | `admin.users.api-keys.create` | `auth`, `verified`, `role:admin` | Shows the form to create a new API key |
| `/admin/users/{user}/api-keys` | POST | `ApiKeyController@store` | `admin.users.api-keys.store` | `auth`, `verified`, `role:admin` | Creates a new API key for a user |
| `/admin/users/{user}/api-keys/{token}` | DELETE | `ApiKeyController@destroy` | `admin.users.api-keys.destroy` | `auth`, `verified`, `role:admin` | Deletes a specific API key |

## Route Organization

The application's routes are organized into logical groups for better maintainability and clarity:

### Public Routes
Routes that are accessible without authentication:
- `/` - Home page
- `/about` - About page
- `/docs` - Documentation index
- `/docs/{filename}` - Documentation detail pages
- `/user` (GET and POST) - Login page and form processing
- `/register` (GET and POST) - Registration page and form processing

### Guest-Only Routes
Routes that are only accessible to non-authenticated users:
- `/forgot-password` (GET and POST) - Password reset request
- `/reset-password/{token}` (GET) - Password reset form
- `/reset-password` (POST) - Process password reset

### Authenticated User Routes
Routes that require user authentication:
- `/logout` - User logout
- `/email/verify` - Email verification notice
- `/email/verify/{id}/{hash}` - Email verification link
- `/email/verification-notification` - Resend verification email

### Verified Email Routes
Routes that require authentication and verified email:
- `/profile` - User profile
- `/profile/edit` (GET and PUT) - Edit profile
- `/api-tokens` - List user's API tokens
- `/api-tokens/create` - Create new API token form
- `/api-tokens` (POST) - Store new API token
- `/api-tokens/{token}` - View API token details
- `/api-tokens/{token}` (DELETE) - Delete API token

### Permission-Protected Routes
Routes that require specific permissions:
- `/contact` - Contact page (requires 'view contact page url' permission)

### Admin Routes
Routes that require admin role (all under `/admin` prefix):
- `/admin/users` - List all users
- `/admin/users/{user}` - View user details
- `/admin/users/{user}/edit` - Edit user
- `/admin/users/{user}` (PUT) - Update user
- `/admin/users/{user}` (DELETE) - Delete user
- `/admin/users/{user}/verify` - Verify user's email
- `/admin/users/{user}/unverify` - Unverify user's email
- `/admin/users/{user}/roles` - Update user's roles
- `/admin/users/{user}/api-keys` - List user's API keys
- `/admin/users/{user}/api-keys/create` - Create new API key form
- `/admin/users/{user}/api-keys` (POST) - Store new API key
- `/admin/users/{user}/api-keys/{token}` (DELETE) - Delete API key

## Middleware Groups

The application uses middleware groups to apply protection to multiple routes:

### Authentication Middleware
Routes protected by the `auth` middleware require a logged-in user:
- All routes under the Authenticated User Routes section
- All routes under the Verified Email Routes section
- All routes under the Permission-Protected Routes section
- All routes under the Admin Routes section

### Verified Email Middleware
Routes protected by the `verified` middleware require a verified email address:
- All routes under the Verified Email Routes section
- All routes under the Admin Routes section

### Guest Middleware
Routes protected by the `guest` middleware are only accessible to non-authenticated users:
- All routes under the Guest-Only Routes section

### Permission Middleware
Routes protected by specific permission requirements:
- `/contact` - Requires the 'view contact page url' permission

### Role Middleware
Routes protected by role requirements:
- All Admin Routes - Require the 'admin' role

### Throttle Middleware
Routes with rate limiting to prevent abuse:
- `/email/verification-notification` - Limited to 5 attempts per minute
- `/reset-password/{token}` - Limited to 5 attempts per minute
- `/reset-password` (POST) - Limited to 5 attempts per minute

## Route Naming
All routes in the application are named, which allows for easy URL generation using the `route()` helper function. For example:
```php
// Generate URL to the home page
$url = route('home');

// Generate URL to the profile page
$url = route('profile');
```

## Related Documentation
- [PageController Documentation](Controllers/PageController.md) - Documentation for the PageController
- [AuthController Documentation](Controllers/AuthController.md) - Documentation for the AuthController
- [DocsController Documentation](Controllers/DocsController.md) - Documentation for the DocsController
- [ApiKeyController Documentation](Controllers/ApiKeyController.md) - Documentation for the ApiKeyController
