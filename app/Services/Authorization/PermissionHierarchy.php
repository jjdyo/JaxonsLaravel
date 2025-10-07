<?php

namespace App\Services\Authorization;

use App\Models\User;

class PermissionHierarchy
{
    /**
     * Get the required role level for a permission name.
     * Returns 0 if the permission is not mapped (i.e., no level-based enforcement).
     */
    public static function levelForPermission(string $permissionName): int
    {
        /** @var array<string,int> $map */
        $map = (array) config('permissions.levels', []);
        $permissionName = strtolower($permissionName);
        return (int) ($map[$permissionName] ?? 0);
    }

    /**
     * Determine if the given user satisfies the required level for the permission.
     * This compares the user\'s highest role level against the permission\'s level.
     */
    public static function userHasByLevel(User $user, string $permissionName): bool
    {
        $required = self::levelForPermission($permissionName);
        if ($required <= 0) {
            // Not enforced by level map
            return false;
        }
        $userLevel = RoleHierarchy::highestLevelForUser($user);
        return $userLevel >= $required;
    }

    /**
     * Filter a list of permissions by what the user is allowed by level.
     *
     * @param array<int,string> $permissionNames
     * @return array<int,string>
     */
    public static function filterAllowed(User $user, array $permissionNames): array
    {
        $allowed = [];
        foreach ($permissionNames as $name) {
            if (self::userHasByLevel($user, $name)) {
                $allowed[] = $name;
            }
        }
        return array_values(array_unique($allowed));
    }
}
