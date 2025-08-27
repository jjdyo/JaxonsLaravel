# Views Documentation

## Overview
This document provides information about the views used in the application. Views are Blade templates that define the HTML structure and presentation of the application's pages.

## Directory Structure
The views are organized in the following directory structure:

```
resources/views/
├── auth/               # Authentication-related views
│   ├── login.blade.php     # Login form
│   ├── register.blade.php  # Registration form
│   ├── verify-email.blade.php # Email verification notice
│   ├── forgot-password.blade.php # Forgot password form
│   └── reset-password.blade.php # Reset password form
├── docs/               # Documentation-related views
│   ├── index.blade.php     # Documentation index page
│   └── show.blade.php      # Documentation page display
├── errors/             # Error pages
├── layouts/            # Layout templates
│   └── app.blade.php       # Main application layout
├── pages/              # Page views
│   ├── home.blade.php      # Home page
│   ├── about.blade.php     # About page
│   ├── contact.blade.php   # Contact page
│   ├── profile.blade.php   # User profile page
│   └── profile_edit.blade.php # Profile edit page
├── vendor/             # Vendor-specific views
│   └── mail/               # Custom email templates
└── welcome.blade.php   # Default Laravel welcome page
```

## View Types

### Layout Views
Layout views define the common structure of the application, including headers, footers, and navigation menus.

#### `layouts/app.blade.php`
The main application layout that wraps around content views. It typically includes:
- HTML doctype and head section
- CSS and JavaScript includes
- Header with navigation
- Main content area (using `@yield('content')`)
- Footer

### Authentication Views
Authentication views handle user login and registration.

#### `auth/login.blade.php`
The login form view, used by `AuthController@showLoginForm`. It includes:
- Email input field
- Password input field
- Remember me checkbox
- Login button
- Link to registration page

#### `auth/register.blade.php`
The registration form view, used by `AuthController@showRegisterForm`. It includes:
- Name input field
- Email input field
- Password input field
- Password confirmation field
- Register button
- Link to login page

#### `auth/verify-email.blade.php`
The email verification notice view, used by the `/email/verify` route. It is displayed to users who have registered but not yet verified their email address. It includes:
- Information about the verification process
- A form to request a new verification email
- Success message when a new verification email is sent
- Link back to the home page

#### `auth/forgot-password.blade.php`
The forgot password form view, used by `AuthController@showForgotPasswordForm`. It is displayed to users who have forgotten their password and need to reset it. It includes:
- Information about the password reset process
- Email input field
- Submit button to request a password reset link
- Success message when a password reset link is sent
- Link back to the login page

#### `auth/reset-password.blade.php`
The reset password form view, used by `AuthController@showResetPasswordForm`. It is displayed to users who have clicked on a password reset link in their email. It includes:
- Email input field (pre-filled and read-only)
- New password input field
- Password confirmation field
- Submit button to reset the password
- Link back to the login page

### Page Views
Page views contain the main content for each page of the application.

#### `pages/home.blade.php`
The home page view, used by `PageController@home`.

#### `pages/about.blade.php`
The about page view, used by `PageController@about`.

#### `pages/contact.blade.php`
The contact page view, used by `PageController@contact`. This page is protected by authentication and permission middleware.

#### `pages/profile.blade.php`
The user profile page view, used by `AuthController@profile`. This page is protected by authentication and email verification middleware. It displays the user's profile information and provides a link to edit the profile.

#### `pages/profile_edit.blade.php`
The profile edit page view, used by `AuthController@editProfile`. This page is protected by authentication and email verification middleware. It provides a form for updating the user's profile information, specifically the name field.

### Documentation Views
Documentation views handle the display of markdown documentation files.

#### `docs/index.blade.php`
The documentation index page view, used by `DocsController@index`. It includes:
- Collapsible hierarchical navigation sidebar with directories and files
  - Initially collapsed with +/- indicators for expandable items
  - Multiple directories can be expanded at once
  - All directories collapse when switching pages
- Rendered HTML content of the README.md file
- Links to all documentation pages

#### `docs/show.blade.php`
The documentation page view, used by `DocsController@show`. It includes:
- Collapsible hierarchical navigation sidebar with directories and files
  - Initially collapsed with +/- indicators for expandable items
  - Multiple directories can be expanded at once
  - Auto-expands to show the current page
  - All directories collapse when switching pages except for the one containing the current page
- Rendered HTML content of the selected markdown file
- Title derived from the filename
- Back to index link

### Error Views
Error views display error messages to users when something goes wrong.

### Vendor Views
Vendor views are views that override or extend functionality provided by Laravel or third-party packages.

#### `vendor/mail/`
The `vendor/mail/` directory is used for customizing email templates. Laravel's email notification system uses these templates for sending emails. By placing custom templates in this directory, you can override the default Laravel email templates without modifying core files.

The directory structure follows Laravel's convention:
```
vendor/mail/
├── html/               # HTML email templates
```

Currently, this directory is empty, which means the application is using Laravel's default email templates. To customize email templates:

1. Publish Laravel's default email templates:
   ```bash
   php artisan vendor:publish --tag=laravel-mail
   ```

2. Modify the published templates in the `vendor/mail/` directory.

3. Laravel will automatically use your custom templates instead of the default ones.

## Blade Templating
The application uses Laravel's Blade templating engine, which provides:
- Template inheritance (`@extends`, `@section`, `@yield`)
- Control structures (`@if`, `@foreach`, etc.)
- Component inclusion (`@include`)
- Form helpers (`@csrf`)
- And more

### Example Blade Syntax
```blade
@extends('layouts.app')

@section('content')
    <h1>Welcome to the Home Page</h1>

    @if(auth()->check())
        <p>Hello, {{ auth()->user()->name }}!</p>
    @else
        <p>Please <a href="{{ route('login') }}">login</a> to access all features.</p>
    @endif
@endsection
```

## Language Files
The application uses language files for localization and customization of messages:

```
resources/lang/
├── en/               # English language files
│   └── passwords.php     # Custom password reset messages
```

### Custom Password Reset Messages
The application includes custom password reset messages in `resources/lang/en/passwords.php`. These messages override the default Laravel messages without modifying core files.

## Related Documentation
- [Routes Documentation](Routes.md) - Documentation for application routes
- [PageController Documentation](Controllers/PageController.md) - Documentation for page rendering functionality
- [AuthController Documentation](Controllers/AuthController.md) - Documentation for authentication functionality
- [DocsController Documentation](Controllers/DocsController.md) - Documentation for the documentation system
- [Email Settings](Configuration/Email%20Notifications.md) - Guide for configuring email settings and fixing common issues
