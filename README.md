![Laravel Logo](https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg)

# JaxonsLaravel

![Status](https://status.jaxonville.com/api/badge/9/status) ![Ping](https://status.jaxonville.com/api/badge/9/ping)

## About JaxonsLaravel

JaxonsLaravel is a custom Laravel 12 platform template I am developing for most anything I may need a server/processing application for. This application comes pre-loaded with examples of our Role-Based-Access-Control, integrations to third-party APIs, queues, and more. This is an on-going process and no publicly accessible endpoints are hosted yet. Please feel free to fork this repo if it benefits you; Ample documentaiton is provided within the documentation/ directory.

## Routing
![Routing Schema](/public/media/diagrams/Routing.jpg)


## Key Features

### Authentication System
- Custom authentication with role-based access control
- Secure user registration with email verification
- Password reset functionality with secure token-based links
- Rate limiting protection against brute force attacks
- Profile management with secure email change verification

### Theme System
- CSS variable-based theme system for easy customization
- Centralized color variables in `public/css/theme.css`
- Variables for primary/secondary colors, text colors, background colors, and UI elements
- Consistent styling across the application
- Starry background effect for header and footer

### Documentation System
- Comprehensive documentation in Markdown format
- Documentation for controllers, models, routes, and views
- Detailed API documentation
- Configuration guides and development environment setup instructions

### Application Structure
- Standard Laravel MVC architecture
- Well-organized directory structure
- Clean separation of concerns
- RESTful API endpoints
- Custom Artisan console commands

### Logging System
- Multiple specialized logging channels (web, api, slack)
- Channel-specific log files for better organization
- Slack integration for critical error notifications
- Configurable log levels for different environments
- Comprehensive logging documentation

## Pages and Functionality
- Home page with dynamic content
- About page with application information
- Contact page (requires authentication and permission)
- User profile management
- Admin dashboard for user administration
- API key management for developers
- Comprehensive documentation browser

## Release Format - Major.Minor.Bug - 01.23.45

## Seeding Test Users
- Default: run `php artisan db:seed` to seed base data plus 50 test users across roles (user, csc, moderator, admin). The default password is `password` and emails look like `test.user+01@example.com`.
- To run only the test users seeder:
  - PowerShell (Windows): `php artisan db:seed --class "Database\\Seeders\\TestUsersSeeder"`
  - CMD (Windows): `php artisan db:seed --class=Database\\Seeders\\TestUsersSeeder`
  - macOS/Linux (bash/zsh): `php artisan db:seed --class=Database\\Seeders\\TestUsersSeeder`

Notes:
- If you previously ran the seeder, it is idempotent and will not create duplicates; it will update roles/permissions to the intended distribution.
- Roles and guard are read from `config/roles.php`. Adjust the hierarchy there if needed.
