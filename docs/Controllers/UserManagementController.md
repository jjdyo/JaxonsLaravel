# UserManagementController

## Overview
The UserManagementController provides administrative functionality for managing users. All routes are protected by the auth, verified, and role:admin middleware and are prefixed with /admin.

- Namespace: App\\Http\\Controllers
- Middleware: auth, verified, role:admin
- Prefix/Name: /admin, admin.

## Routes and Actions

1) GET /admin/users (name: admin.users.index)
- Method: listUsers(Request $request)
- Description: Lists users with pagination and filtering.
- Query params:
  - filter: new | unverified | az | za | search (default: new)
  - q: search term (only used when filter=search)
- View: resources/views/admin/users/index.blade.php

2) GET /admin/users/{user} (name: admin.users.show)
- Method: showUser(User $user)
- Description: Shows details for a specific user, including Roles and Permissions (Spatie) with eager-loaded relations to avoid N+1.
- View: resources/views/admin/users/show.blade.php

3) GET /admin/users/{user}/edit (name: admin.users.edit)
- Method: editUser(User $user)
- Description: Shows the user edit form. Loads available roles.
- View: resources/views/admin/users/edit.blade.php

4) PUT /admin/users/{user} (name: admin.users.update)
- Method: updateUser(Request $request, User $user)
- Validation:
  - name: required|string|max:255
  - email: required|email|max:255|unique:users,email,{user}
  - password: nullable|string|min:8
  - email_verified: nullable|boolean
  - roles: nullable|array
  - roles.*: integer|exists:roles,id
- Behavior: Updates user fields and synchronizes roles (empty selection clears roles). Password is hashed by model cast; email verification toggling is delegated to User model helpers.
- Redirects: admin.users.show with success message.

5) DELETE /admin/users/{user} (name: admin.users.destroy)
- Method: deleteUser(User $user)
- Description: Deletes a user (prevents deleting your own account via User::canBeDeletedBy()).
- Redirects: admin.users.index

6) POST /admin/users/{user}/verify (name: admin.users.verify)
- Method: verifyUser(User $user)
- Description: Manually marks a user's email as verified via User::markEmailVerified().

7) POST /admin/users/{user}/unverify (name: admin.users.unverify)
- Method: unverifyUser(User $user)
- Description: Removes email verification (sets email_verified_at=null).

8) POST /admin/users/{user}/roles (name: admin.users.roles.update)
- Method: updateRoles(Request $request, User $user)
- Validation:
  - roles: array
  - roles.*: integer|exists:roles,id
- Behavior: Syncs user roles by ID via Spatie Permissions.
- Note: The UI now updates roles via PUT /admin/users/{user} (Save Changes). This endpoint remains for API/backward compatibility.

9) Nested API Key Management
- Prefix: /admin/users/{user}/api-keys (name: admin.users.api-keys.)
- Methods handled by ApiKeyController:
  - GET index, GET create, POST store, DELETE destroy

## Dependencies
- Models: App\\Models\\User
- Packages: spatie/laravel-permission (Role model)
- Views: admin/users/*

## Authorization
- All endpoints require the admin role.
- Ensure PermissionSeeder has created roles and assigned them accordingly.

## Related
- [ApiKeyController](ApiKeyController.md)
- [Routes](../Routes.md)
- [Permissions](../Permissions.md)
