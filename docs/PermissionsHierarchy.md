# Permission Level Hierarchy

This project includes a level-based permission system aligned with the existing role hierarchy defined in `config/roles.php`.

- Each permission can be assigned a minimum required role level.
- A user is allowed a permission if their highest role level is greater than or equal to the permission's level.
- Explicit Spatie permissions (assigned to a user or role) still grant access regardless of the level mapping.

## Configuration

Define permission levels in `config/permissions.php`:

```
return [
    'levels' => [
        'view about page url' => 1,
        'view functions page' => 2,
    ],
    'guard' => 'web',
];
```

- The numbers correspond to the role levels in `config/roles.php` (higher is more privileged).
- A value of 0 or missing entry means the permission is not enforced by level mapping.

## Seeding

`Database\Seeders\PermissionSeeder` seeds the example permissions and assigns all permissions to the `admin` role. The seeder uses the configured `permissions.guard`.
