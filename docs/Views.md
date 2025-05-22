# Views Documentation

## Overview
This document provides information about the views used in the application. Views are Blade templates that define the HTML structure and presentation of the application's pages.

## Directory Structure
The views are organized in the following directory structure:

```
resources/views/
├── auth/               # Authentication-related views
│   ├── login.blade.php     # Login form
│   └── register.blade.php  # Registration form
├── errors/             # Error pages
├── layouts/            # Layout templates
│   └── app.blade.php       # Main application layout
├── pages/              # Page views
│   ├── home.blade.php      # Home page
│   ├── about.blade.php     # About page
│   ├── contact.blade.php   # Contact page
│   └── profile.blade.php   # User profile page
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

### Page Views
Page views contain the main content for each page of the application.

#### `pages/home.blade.php`
The home page view, used by `PageController@home`.

#### `pages/about.blade.php`
The about page view, used by `PageController@about`.

#### `pages/contact.blade.php`
The contact page view, used by `PageController@contact`. This page is protected by authentication and permission middleware.

#### `pages/profile.blade.php`
The user profile page view, used by `PageController@profile`. This page is protected by authentication middleware.

### Error Views
Error views display error messages to users when something goes wrong.

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

## Related Documentation
- [Routes Documentation](Routes.md) - Documentation for application routes
- [PageController Documentation](PageController.md) - Documentation for page rendering functionality
- [AuthController Documentation](AuthController.md) - Documentation for authentication functionality
