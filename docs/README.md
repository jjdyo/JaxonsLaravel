# Project Documentation

## Overview
This folder contains comprehensive documentation for the Laravel application. The documentation covers controllers, routes, and console commands.

## Table of Contents

### Controllers
- [AuthController Documentation](Controllers/AuthController.md) - Documentation for authentication-related functionality
- [DocsController Documentation](Controllers/DocsController.md) - Documentation for the documentation system
- [PageController Documentation](Controllers/PageController.md) - Documentation for page rendering functionality

### Models
- [Models Documentation](Models.md) - Documentation for data models and relationships

### Routes
- [Routes Documentation](Routes.md) - Comprehensive overview of all routes in the application

### Console Commands
- [Console Commands Documentation](Console Commands.md) - Documentation for Artisan console commands

### Views
- [Views Documentation](Views.md) - Documentation for Blade templates and view structure

### Configuration
- [Email Settings](Configuration/Email Notifications.md) - Guide for configuring email settings and fixing common issues
- [Application Configuration](Configuration/Application Configuration.md) - Guide for core application settings and environment configuration

### Development Environments
- [Database Migrations with MariaDB](Development%20Environments/Database%20Migrations%20with%20MariaDB.md) - Guide for database migrations with MariaDB
- [Services and Startup](Development%20Environments/Services%20and%20Startup.md) - Guide for services and startup procedures

### Styling
- [Theme System](Styling/Themes.md) - Documentation for the CSS variable-based theme system

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
- Email verification for new users
- Password reset functionality

## Theme System
The application implements a CSS variable-based theme system for easy customization of colors and visual elements. The theme system includes:

- Centralized color variables in `public/css/theme.css`
- Variables for primary/secondary colors, text colors, background colors, and UI element colors
- Easy customization by modifying variable values
- Consistent styling across the application
- Starry background effect for header and footer

## Page Structure
The application includes the following pages:

- Home page (`/`)
- About page (`/about`)
- Contact page (`/contact`) - Requires authentication and permission
- Profile page (`/profile`) - Requires authentication
- Login page (`/user`)
- Registration page (`/register`)
- Email verification page (`/email/verify`)
- Forgot password page (`/forgot-password`)
- Reset password page (`/reset-password/{token}`)

## How to Use This Documentation
- Start with the [Routes Documentation](Routes.md) for an overview of all available routes
- For details about specific controllers, refer to the controller documentation files
- For information about data models and relationships, see the [Models Documentation](Models.md)
- For information about views and Blade templates, see the [Views Documentation](Views.md)
- For information about console commands, see the [Console Commands Documentation](Console Commands.md)
- For information about the theme system and styling, see the [Theme System Documentation](Styling/Themes.md) and [Starry Background Documentation](Styling/StarryBackground.md)
- For information about email configuration, see the [Email Settings Documentation](Configuration/Email Notifications.md)
- For information about core application settings and environment configuration, see the [Application Configuration Documentation](Configuration/Application Configuration.md)
