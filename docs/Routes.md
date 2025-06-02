# Routes Documentation

## Overview
This document provides a comprehensive overview of all routes defined in the application. Routes are organized by their purpose and include information about the associated controller methods, middleware, and naming.

## Web Routes
All web routes are defined in `routes/web.php`.

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
| `/profile` | GET | `AuthController@profile` | `profile` | `auth`, `verified` | Displays the user profile page (requires authentication and email verification) |
| `/profile/edit` | GET | `AuthController@editProfile` | `profile.edit` | `auth`, `verified` | Displays the profile edit form (requires authentication and email verification) |
| `/profile/edit` | PUT | `AuthController@updateProfile` | `profile.update` | `auth`, `verified` | Processes the profile update form submission (requires authentication and email verification) |

### Email Verification Routes
These routes handle the email verification process for new users.

| URL | Method | Controller Action | Name | Middleware | Description |
|-----|--------|------------------|------|------------|-------------|
| `/email/verify` | GET | Closure | `verification.notice` | `auth` | Displays the email verification notice page |
| `/email/verify/{id}/{hash}` | GET | Closure | `verification.verify` | `auth`, `signed` | Verifies the user's email address using the verification link |
| `/email/verification-notification` | POST | Closure | `verification.send` | `auth`, `throttle:6,1` | Resends the verification email (limited to 6 attempts per minute) |

### Documentation Routes
These routes handle the documentation system, allowing users to browse and view markdown documentation files.

| URL | Method | Controller Action | Name | Middleware | Description |
|-----|--------|------------------|------|------------|-------------|
| `/docs` | GET | `DocsController@index` | `docs.index` | None | Displays the documentation index page with README content |
| `/docs/{filename}` | GET | `DocsController@show` | `docs.show` | None | Displays a specific documentation page based on the filename parameter (supports subdirectories) |

## Route Groups
The application uses route groups to apply middleware to multiple routes at once.

### Authentication Middleware Group
The following routes require the user to be authenticated:
- `/contact` (also requires the 'view contact page url' permission)
- `/profile` (also requires email verification)
- `/profile/edit` (GET and PUT methods) (also requires email verification)
- `/logout`
- `/email/verify` (email verification notice page)
- `/email/verify/{id}/{hash}` (email verification link)
- `/email/verification-notification` (resend verification email)

### Verified Middleware Group
The following routes require the user to have a verified email address:
- `/profile`
- `/profile/edit` (GET and PUT methods)

### Permission Middleware Group
The following routes require specific permissions:
- `/contact` - Requires the 'view contact page url' permission

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
