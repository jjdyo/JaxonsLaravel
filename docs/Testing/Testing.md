# Testing Documentation

This directory contains documentation for the testing approach and test utilities used in the JaxonsLaravel project.

## Overview

The JaxonsLaravel project uses PHPUnit for testing, with a focus on feature tests that verify the functionality of controllers, models, and services. The tests are organized into two main directories:

- `tests/Feature`: Contains feature tests that test the application as a whole
- `tests/Unit`: Contains unit tests that test individual components in isolation

## Test Utilities

The project includes several test utilities to make writing tests easier and more consistent:

- [AuthTestHelpers](AuthTestHelpers.md): A trait that provides helper methods for authentication-related tests

## Test Cases

The following test cases are documented:

- [AuthControllerTest](AuthControllerTest.md): Tests for the authentication controller

## Best Practices

When writing tests for the JaxonsLaravel project, follow these best practices:

1. **Use the RefreshDatabase trait**: This ensures a clean database state for each test
2. **Use factories**: Create test data using factories rather than direct database insertions
3. **Test edge cases**: Include tests for error conditions and edge cases
4. **Keep tests isolated**: Each test should be independent and not rely on the state from other tests
5. **Use descriptive names**: Test method names should clearly describe what is being tested
6. **Use assertions**: Make multiple assertions to verify different aspects of the functionality
7. **Mock external services**: Use mocks or fakes for external services to avoid side effects
8. **Test different user roles**: Include tests for different user roles and permissions
9. **Test authentication states**: Include tests for authenticated and unauthenticated users
10. **Document tests**: Add documentation for complex test cases

## Running Tests

To run all tests:

```bash
php artisan test
```

To run a specific test class:

```bash
php artisan test --filter=AuthControllerTest
```

To run a specific test method:

```bash
php artisan test --filter=AuthControllerTest::test_users_can_authenticate_with_valid_credentials
```

## Test Coverage

To generate a test coverage report, you need to have Xdebug installed and enabled. Then run:

```bash
php artisan test --coverage
```

For a more detailed HTML coverage report:

```bash
php artisan test --coverage-html coverage
```

This will generate an HTML report in the `coverage` directory.

## Extending the Tests

When adding new functionality to the application, consider adding tests for:

1. New controllers and their methods
2. New models and their relationships
3. New services and their methods
4. New middleware
5. New console commands
6. New API endpoints

## Troubleshooting

If you encounter issues with the tests:

1. Make sure your `.env.testing` file is properly configured
2. Check that the database connection is working
3. Ensure that all required dependencies are installed
4. Check for any pending migrations
5. Look for error messages in the test output
