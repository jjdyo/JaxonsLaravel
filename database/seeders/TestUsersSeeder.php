<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Seeder: TestUsersSeeder
 *
 * Creates 50 test users with a mix of roles and some direct permissions using the
 * existing Spatie Permission setup and the configured role hierarchy (config/roles.php).
 *
 * Usage:
 *   php artisan db:seed --class=Database\\Seeders\\TestUsersSeeder
 *
 * Idempotency:
 * - Uses deterministic email pattern test.user+<n>@example.com to avoid duplicates.
 * - Safe to re-run; will update role assignments if users already exist.
 */
class TestUsersSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure roles from config exist
        /** @var array<string,int> $hier */
        $hier = (array) config('roles.hierarchy', []);
        $guard = (string) config('roles.guard', 'web');

        if (empty($hier)) {
            $this->command?->warn('No roles defined in config/roles.php. Skipping user seeding.');
            return;
        }

        $roleModels = [];
        foreach (array_keys($hier) as $roleName) {
            $roleModels[$roleName] = Role::query()->firstOrCreate([
                'name' => $roleName,
                'guard_name' => $guard,
            ]);
        }

        // Collect available permissions (if any were seeded previously)
        $allPermissions = Permission::query()
            ->where('guard_name', $guard)
            ->get()
            ->pluck('name')
            ->all();

        // Decide distribution across known roles, biased toward lower roles
        // Example distribution for 50 users: admin 5, moderator 10, csc 10, user 25
        $roleOrder = array_keys($hier); // already ordered as configured
        $distribution = $this->defaultDistribution($roleOrder, 50);

        // Create or update users in a transaction for consistency
        DB::transaction(function () use ($distribution, $roleModels, $allPermissions) {
            $counter = 1;
            foreach ($distribution as $roleName => $count) {
                for ($i = 0; $i < $count; $i++, $counter++) {
                    $email = sprintf('test.user+%02d@example.com', $counter);

                    // Find or create to keep idempotent
                    $user = User::query()->firstOrCreate(
                        ['email' => $email],
                        [
                            'name' => $this->fakeNameForRole($roleName, $counter),
                            // When creating directly without factory, specify a known default password
                            // Using Hash::make to be consistent even if factory changes
                            'password' => Hash::make('password'),
                            'email_verified_at' => now(),
                        ]
                    );

                    // Assign the intended role (sync to ensure single role for clarity)
                    $user->syncRoles($roleModels[$roleName]);

                    // Optionally assign 0-2 random direct permissions (non-admin) to add variety
                    if (!empty($allPermissions) && $roleName !== 'admin') {
                        // Pick up to 2 random permissions
                        $rand = Arr::random($allPermissions, min(2, max(0, random_int(0, 2))));
                        $names = is_array($rand) ? $rand : [$rand];
                        if (!empty($names)) {
                            $user->syncPermissions($names); // safe: Spatie creates pivot as needed
                        }
                    } else {
                        // Ensure admins have no direct permissions (they inherit via roles)
                        $user->syncPermissions([]);
                    }

                    // Ensure denormalized role_level is updated (handled in model wrapper)
                    $user->refreshRoleLevel();
                }
            }
        });

        $this->command?->info('Test users seeded: 50 users across roles: '.json_encode($distribution));
    }

    /**
     * Build a default distribution map for the given roles totaling $total users.
     * If roles include [user, csc, moderator, admin], returns [admin=>5, moderator=>10, csc=>10, user=>25].
     * Falls back to a simple weighted distribution if roles differ.
     *
     * @param array<int,string> $roleOrder
     * @return array<string,int>
     */
    protected function defaultDistribution(array $roleOrder, int $total): array
    {
        // Prefer using configured order: lowest -> highest; we want more low-level users
        // We'll assign weights increasing toward lower roles
        $roles = array_values($roleOrder);
        $n = count($roles);
        if ($n === 0) { return []; }

        // If the expected 4 roles exist, use the fixed nice split
        $expected = ['user','csc','moderator','admin'];
        if (array_map('strtolower', $roles) === $expected) {
            return [
                'admin' => 5,
                'moderator' => 10,
                'csc' => 10,
                'user' => 25,
            ];
        }

        // Generic: assign weights inversely proportional to index (lower roles get higher weight)
        // Compute weights like: w[i] = (n - i), then normalize to total
        $weights = [];
        for ($i = 0; $i < $n; $i++) {
            $weights[$roles[$i]] = $n - $i; // last (highest) gets 1
        }
        $sum = array_sum($weights);
        if ($sum <= 0) { $sum = 1; }

        // Initial floor allocation
        $dist = [];
        $allocated = 0;
        foreach ($roles as $name) {
            $count = (int) floor($total * ($weights[$name] / $sum));
            $dist[$name] = $count;
            $allocated += $count;
        }
        // Distribute remaining starting from lowest role
        $remaining = $total - $allocated;
        $i = 0;
        while ($remaining > 0) {
            $name = (string) $roles[$i % $n];
            $dist[$name] = (int) (($dist[$name] ?? 0) + 1);
            $remaining--;
            $i++;
        }

        // Ensure array is keyed by role name; order doesn't matter for seeding
        return $dist;
    }

    protected function fakeNameForRole(string $roleName, int $index): string
    {
        $label = ucfirst($roleName);
        return $label.' User #'.str_pad((string)$index, 2, '0', STR_PAD_LEFT);
    }
}
