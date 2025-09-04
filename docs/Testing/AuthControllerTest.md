# AuthController Tests Documentation

This document describes the tests implemented for the `AuthController` which handles authentication-related functionality in the application.

## Test Coverage

The `AuthControllerTest` class provides comprehensive test coverage for the authentication system, including:

1. Login functionality
2. Logout functionality
3. Registration functionality
4. Role-based access control
5. Authentication middleware
6. Profile management

## Test Setup

The tests use Laravel's testing framework with the following traits:
- `RefreshDatabase`: Ensures a clean database state for each test
- `WithFaker`: Provides fake data generation capabilities

The test environment is set up with the necessary roles (admin and user) in the `setUp` method.

## Test Cases

### Login Functionality

- `test_login_page_can_be_rendered`: Verifies that the login page loads correctly
- `test_users_can_authenticate_with_valid_credentials`: Tests successful login with valid credentials
- `test_users_cannot_authenticate_with_invalid_credentials`: Tests failed login with invalid credentials

### Logout Functionality

- `test_users_can_logout`: Verifies that authenticated users can log out successfully

### Registration Functionality

- `test_registration_page_can_be_rendered`: Verifies that the registration page loads correctly
- `test_new_users_can_register`: Tests successful user registration and role assignment

### Role-Based Access Control

- `test_admin_can_access_admin_routes`: Verifies that users with admin role can access admin-only routes
- `test_regular_user_cannot_access_admin_routes`: Verifies that regular users cannot access admin-only routes

### Authentication Middleware

- `test_guest_cannot_access_authenticated_routes`: Verifies that guests are redirected to login when accessing protected routes
- `test_authenticated_user_can_access_profile`: Verifies that authenticated users can access their profile

### Profile Management

- `test_user_can_update_profile`: Tests profile update with email change (requires re-verification)
- `test_user_can_update_profile_without_changing_email`: Tests profile update without email change

## Running the Tests

To run these tests, use the following command:

```bash
php artisan test --filter=AuthControllerTest
```

## Best Practices Implemented

1. **Isolation**: Each test is isolated and doesn't depend on the state from other tests
2. **Descriptive Names**: Test method names clearly describe what is being tested
3. **Assertions**: Multiple assertions are used to verify different aspects of the functionality
4. **Factory Usage**: User factory is used to create test users
5. **Role Assignment**: Tests verify proper role assignment during registration
6. **Authentication States**: Tests cover different authentication states (logged in, not logged in)
7. **Error Handling**: Tests verify proper error handling for invalid inputs
8. **Redirects**: Tests verify proper redirects after actions
9. **View Rendering**: Tests verify that the correct views are rendered

## Extending the Tests

When adding new authentication-related functionality, consider adding tests for:

1. Password reset functionality
2. Email verification
3. Remember me functionality
4. Account lockout after failed attempts
5. Two-factor authentication (if implemented)
6. API authentication (if applicable)
