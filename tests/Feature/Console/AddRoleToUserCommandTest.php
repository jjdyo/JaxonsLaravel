<?php

namespace Tests\Feature\Console;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AddRoleToUserCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_addrole_assigns_existing_and_warns_about_missing(): void
    {
        $user = User::factory()->create(['email' => 'cliuser@example.com', 'name' => 'CLI User']);
        // existing role
        Role::firstOrCreate(['name' => 'manager']);

        $this->artisan('addrole', [
            'identifier' => 'cliuser@example.com',
            'roles' => 'manager,nonexistent',
        ])
            ->expectsOutputToContain('Found user:')
            ->expectsOutputToContain('Assigned roles: manager')
            ->expectsOutputToContain('These roles do not exist')
            ->expectsOutputToContain('php artisan createrole nonexistent --guard=web')
            ->assertExitCode(0);

        $this->assertTrue($user->fresh()->hasRole('manager'));
    }
}
