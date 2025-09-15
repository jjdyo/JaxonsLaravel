<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;

class AddRoleToUserCommand extends Command
{
    /**
     * Signature supports: php artisan addrole email@example.com admin,user
     * Options: --guard to target guard, defaults to web
     */
    protected $signature = 'addrole {identifier : User email or name} {roles : Comma-separated roles to assign} {--guard=web : Guard name to use}';

    protected $description = 'Assign one or more existing roles to a user by email or name. Suggests creating missing roles.';

    public function handle(): int
    {
        $identifier = trim((string) $this->argument('identifier'));
        $rolesArg = (string) $this->argument('roles');
        $guard = (string) $this->option('guard');

        if ($identifier === '') {
            $this->error('Identifier (email or name) is required.');
            return Command::FAILURE;
        }

        $roles = collect(explode(',', $rolesArg))
            ->map(fn ($r) => trim($r))
            ->filter(fn ($r) => $r !== '')
            ->unique()
            ->values();

        if ($roles->isEmpty()) {
            $this->error('Please provide at least one role. Example: php artisan addrole user@example.com admin,editor');
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

        /** @var Collection<int, non-empty-string> $existingRoles */
        $existingRoles = Role::query()
            ->whereIn('name', $roles->all())
            ->where('guard_name', $guard)
            ->pluck('name');

        $missing = $roles->diff($existingRoles)->values();
        $assigned = [];
        $alreadyHad = [];

        /** @var non-empty-string $roleName */
        foreach ($existingRoles as $roleName) {
            if ($user->hasRole($roleName)) {
                $alreadyHad[] = $roleName;
                continue;
            }
            $user->assignRole($roleName);
            $assigned[] = $roleName;
        }

        if (!empty($assigned)) {
            $this->info('✅ Assigned roles: ' . implode(', ', $assigned));
        }
        if (!empty($alreadyHad)) {
            $this->comment('ℹ️ User already had: ' . implode(', ', $alreadyHad));
        }
        if ($missing->isNotEmpty()) {
            $this->warn('⚠️ These roles do not exist for guard [' . $guard . ']: ' . $missing->implode(', '));
            $this->line('You can create them with:');
            $this->line('  php artisan createrole ' . $missing->implode(',') . ' --guard=' . $guard);
            $this->line('Or use Spatie command for single role:');
            $this->line('  php artisan permission:create-role "role-name" ' . $guard);
        }

        return Command::SUCCESS;
    }
}
