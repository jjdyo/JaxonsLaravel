<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CreateRoleCommand extends Command
{
    /**
     * The name and signature of the console command.
     * Supports comma-separated list of roles and optional guard.
     *
     * Examples:
     *  php artisan createrole admin,user
     *  php artisan createrole admin --guard=web
     */
    protected $signature = 'createrole {roles : Comma-separated role names (e.g. admin,user,editor)} {--guard=web : Guard name to use}';

    /**
     * The console command description.
     */
    protected $description = 'Create roles by delegating to Spatie\'s permission:create-role command (supports comma-separated list)';

    public function handle(): int
    {
        $guard = (string) $this->option('guard');
        $rolesArg = (string) $this->argument('roles');
        $names = collect(explode(',', $rolesArg))
            ->map(fn ($r) => trim($r))
            ->filter(fn ($r) => $r !== '')
            ->unique()
            ->values();

        if ($names->isEmpty()) {
            $this->error('No valid role names provided.');
            return Command::FAILURE;
        }

        foreach ($names as $name) {
            // Pipe to Spatie's built-in command for each role
            // Signature: permission:create-role {name} {guard?} {permissions?} {--team-id=}
            $this->call('permission:create-role', [
                'name' => $name,
                'guard' => $guard,
            ]);
        }

        // Print a simple summary line expected by tests and for clarity
        $this->line('Created roles: ' . implode(', ', $names->all()));

        return Command::SUCCESS;
    }
}
