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
        /** @var array<string,int> $map */
        $map = (array) config('roles.hierarchy', []);
        $roleName = strtolower($roleName);
        // Cast directly to int to satisfy static analysis regardless of config value type
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
        /** @var array<string,int> $hier */
        $hier = (array) config('roles.hierarchy', []);
        $levels = array_values($hier);
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

        // If no candidates provided, use all role names (strings)
        if (empty($candidates)) {
            /** @var array<int,string> $allNames */
            $allNames = Role::query()
                ->select(['name'])
                ->where('guard_name', config('roles.guard', 'web'))
                ->get()
                ->pluck('name')
                ->all();
            $candidates = $allNames;
        } else {
            // Normalize input to names and keep only strings
            $normalized = [];
            foreach ($candidates as $r) {
                if ($r instanceof Role) {
                    $normalized[] = $r->name; // already string
                } elseif (is_string($r)) {
                    $normalized[] = $r;
                }
                // ignore non-string, non-Role values
            }
            /** @var array<int,string> $candidates */
            $candidates = $normalized;
        }

        // Filter by actor level without casting from mixed
        $names = [];
        foreach ($candidates as $name) {
            // $name is guaranteed string here
            $level = self::levelForRole($name);
            if ($level <= $actorLevel && $level > 0) {
                $names[] = $name;
            }
        }

        // Ensure uniqueness and preserve order
        $names = array_values(array_unique($names));
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
