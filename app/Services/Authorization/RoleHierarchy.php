<?php

namespace App\Services\Authorization;

use App\Models\User;
use Illuminate\Support\Arr;
use Spatie\Permission\Models\Role;

class RoleHierarchy
{
    /**
     * Get the numeric level for a role name. Higher number = higher privilege.
     */
    public static function levelForRole(string $roleName): int
    {
        $map = config('roles.hierarchy', []);
        $roleName = strtolower($roleName);
        return (int) ($map[$roleName] ?? 0);
    }

    /**
     * Highest role level for a given user (based on their assigned roles).
     */
    public static function highestLevelForUser(User $user): int
    {
        // Ensure roles are loaded minimally
        $roles = $user->relationLoaded('roles') ? $user->roles : $user->roles()->select(['id', 'name'])->get();
        $levels = $roles->map(fn($r) => self::levelForRole($r->name))->all();
        return empty($levels) ? 0 : max($levels);
    }

    /**
     * Determine if the actor can manage the target user based on highest role level.
     * - Actor must have a higher level than the target (strictly greater).
     * - Actor cannot manage themselves.
     */
    public static function canManageUser(?User $actor, User $target): bool
    {
        if (!$actor) {
            return false;
        }

        $actorLevel = self::highestLevelForUser($actor);
        $targetLevel = self::highestLevelForUser($target);
        $levels = array_values(config('roles.hierarchy', []));
        $maxLevel = empty($levels) ? 0 : max($levels);

        // Admins (max level) can manage anyone, including themselves
        if ($actorLevel === $maxLevel) {
            return true;
        }

        // Non-admins cannot manage themselves and must be strictly higher than target
        if ($actor->id === $target->id) {
            return false;
        }
        return $actorLevel > $targetLevel;
    }

    /**
     * Get role names the actor is allowed to assign (levels less than or equal to actor's highest level).
     * Optionally, provide a list of candidate Role models or names to filter.
     *
     * @param  User  $actor
     * @param  array<int, string|Role>  $candidates
     * @return array<int, string> Role names actor can assign
     */
    public static function assignableRoleNames(User $actor, array $candidates = []): array
    {
        $actorLevel = self::highestLevelForUser($actor);

        // If no candidates provided, use all roles
        if (empty($candidates)) {
            $candidates = Role::query()
                ->select(['name'])
                ->where('guard_name', config('roles.guard', 'web'))
                ->get()
                ->pluck('name')
                ->all();
        } else {
            // Normalize input to names
            $candidates = array_map(function ($r) {
                if ($r instanceof Role) { return $r->name; }
                return (string) $r;
            }, $candidates);
        }

        $names = array_values(array_filter($candidates, function (string $name) use ($actorLevel) {
            return self::levelForRole($name) <= $actorLevel && self::levelForRole($name) > 0;
        }));

        // Ensure uniqueness and preserve order
        $names = array_values(array_unique(array_map('strval', $names)));
        return $names;
    }

    /**
     * Validate a set of desired role names against what the actor may assign.
     * Returns the filtered set that is allowed.
     *
     * @param  User  $actor
     * @param  array<int, string> $desiredRoleNames
     * @return array<int, string>
     */
    public static function filterAssignable(User $actor, array $desiredRoleNames): array
    {
        $allowed = self::assignableRoleNames($actor);
        $desired = array_map(fn($n) => strtolower($n), $desiredRoleNames);
        $allowed = array_map(fn($n) => strtolower($n), $allowed);
        return array_values(array_intersect($desired, $allowed));
    }
}
