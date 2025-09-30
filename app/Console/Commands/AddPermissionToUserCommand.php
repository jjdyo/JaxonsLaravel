<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;

class AddPermissionToUserCommand extends Command
{
    /**
     * Signature supports: php artisan addperm email@example.com perm1,perm2
     * Options: --guard to target guard, defaults to web
     */
    protected $signature = 'addperm {identifier : User email or name} {permissions : Comma-separated permissions to assign} {--guard=web : Guard name to use}';

    protected $description = 'Assign one or more existing Spatie permissions to a user by email or name. Uses Spatie APIs only.';

    public function handle(): int
    {
        $identifier = trim((string) $this->argument('identifier'));
        $permsArg = (string) $this->argument('permissions');
        $guard = (string) $this->option('guard');

        if ($identifier === '') {
            $this->error('Identifier (email or name) is required.');
            return Command::FAILURE;
        }

        /** @var Collection<int, string> $permissions */
        $permissions = collect(explode(',', $permsArg))
            ->map(fn ($p) => trim((string)$p))
            ->filter(fn ($p) => $p !== '')
            ->unique()
            ->values();

        if ($permissions->isEmpty()) {
            $this->error('Please provide at least one permission. Example: php artisan addperm user@example.com view posts,edit posts');
            return Command::FAILURE;
        }

        // Find user by email first, then by exact name
        $user = User::query()
            ->where('email', $identifier)
            ->orWhere('name', $identifier)
            ->first();

        if (!$user) {
            $this->error("User not found by email or name: {$identifier}");
            return Command::FAILURE;
        }

        $this->info("Found user: {$user->name} <{$user->email}>");

        // Fetch existing permissions for the specified guard
        /** @var Collection<int, non-empty-string> $existingPerms */
        $existingPerms = Permission::query()
            ->whereIn('name', $permissions->all())
            ->where('guard_name', $guard)
            ->pluck('name');

        // Determine which requested permissions are missing
        $missing = $permissions->diff($existingPerms->all())->values();
        $assigned = [];
        $alreadyHad = [];

        /** @var non-empty-string $permName */
        foreach ($existingPerms as $permName) {
            if ($user->hasPermissionTo($permName, $guard)) {
                $alreadyHad[] = $permName;
                continue;
            }
            // Use Spatie API to grant
            $user->givePermissionTo($permName);
            $assigned[] = $permName;
        }

        if (!empty($assigned)) {
            $this->info('✅ Assigned permissions: ' . implode(', ', $assigned));
        }
        if (!empty($alreadyHad)) {
            $this->comment('ℹ️ User already had: ' . implode(', ', $alreadyHad));
        }
        if ($missing->isNotEmpty()) {
            $this->warn('⚠️ These permissions do not exist for guard [' . $guard . ']: ' . $missing->implode(', '));
            $this->line('You can create them with:');
            $this->line('  php artisan permission:create-permission "permission-name" ' . $guard);
        }

        return Command::SUCCESS;
    }
}
