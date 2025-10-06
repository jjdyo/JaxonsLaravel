# Role Hierarchy

This application uses Spatie Laravel Permission for roles/permissions and adds a simple, configurable role hierarchy to control who can manage whom.

## Configuration

Edit `config/roles.php` to set role levels (higher number = higher privilege):

```
return [
    'hierarchy' => [
        'user' => 1,
        'moderator' => 2,
        'admin' => 3,
    ],
    'guard' => 'web',
];
```

To insert a new role between existing ones, just add it with the appropriate level. Example adding `csc` between `user` and `moderator`:

```
'hierarchy' => [
    'user' => 1,
    'csc' => 2,
    'moderator' => 3,
    'admin' => 4,
],
```

No migrations are required for the hierarchy itself.

## Centralized Logic

- Service: `App\Services\Authorization\RoleHierarchy`
  - `levelForRole(string $roleName): int`
  - `highestLevelForUser(User $user): int`
  - `canManageUser(User $actor, User $target): bool` (actor must be strictly higher)
  - `assignableRoleNames(User $actor, array $candidates = []): array` (roles actor can assign)
  - `filterAssignable(User $actor, array $desiredRoleNames): array`

- User model helpers:
  - `User::highestRoleLevel(): int`
  - `User::canManageUser(User $target): bool`
  - `User::scopeManageableBy($query, User $actor)`

## Controller Enforcement

`UserManagementController` enforces hierarchy for management actions:
- Edit: Role list is limited to roles the actor can assign.
- Update: Prevents managing peers/superiors and filters role sync to allowed roles.
- Delete/Verify/Unverify: Blocked unless the actor has a higher level than the target.

## Notes

- This approach keeps controllers lean while centralizing the hierarchy rules in a service.
- Future roles only require updating `config/roles.php`. Existing logic adapts automatically.
