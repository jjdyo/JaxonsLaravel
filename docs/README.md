# Project Documentation

## Overview
This folder contains comprehensive documentation for the Laravel application. The documentation covers controllers, routes, and console commands.

## Table of Contents

### Controllers
- [AuthController Documentation](Controllers/AuthController.md) - Documentation for authentication-related functionality
- [PageController Documentation](Controllers/PageController.md) - Documentation for page rendering functionality

### Models
- [Models Documentation](Models.md) - Documentation for data models and relationships

### Routes
- [Routes Documentation](Routes.md) - Comprehensive overview of all routes in the application

### Console Commands
- [Console Commands Documentation](ConsoleCommands.md) - Documentation for Artisan console commands

### Views
- [Views Documentation](Views.md) - Documentation for Blade templates and view structure

## Application Structure
The application follows a standard Laravel structure:

- `app/Http/Controllers` - Contains controller classes that handle HTTP requests
- `resources/views` - Contains Blade templates for rendering views
- `routes/web.php` - Defines web routes
- `routes/console.php` - Defines custom Artisan console commands

## Authentication System
The application implements a custom authentication system using Laravel's built-in authentication features. The system includes:

- User login with rate limiting protection
- User registration with validation
- Secure password hashing
- Role-based access control

## Page Structure
The application includes the following pages:

- Home page (`/`)
- About page (`/about`)
- Contact page (`/contact`) - Requires authentication and permission
- Profile page (`/profile`) - Requires authentication
- Login page (`/user`)
- Registration page (`/register`)

## How to Use This Documentation
- Start with the [Routes Documentation](Routes.md) for an overview of all available routes
- For details about specific controllers, refer to the controller documentation files
- For information about data models and relationships, see the [Models Documentation](Models.md)
- For information about views and Blade templates, see the [Views Documentation](Views.md)
- For information about console commands, see the [Console Commands Documentation](ConsoleCommands.md)
