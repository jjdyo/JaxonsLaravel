<?php

namespace Tests\Feature\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CreateRoleCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_createrole_creates_multiple_roles_and_prints_summary(): void
    {
        // Ensure roles are not present
        Role::whereIn('name', ['alpha', 'beta'])->delete();

        $this->artisan('createrole', ['roles' => 'alpha,beta'])
            ->expectsOutputToContain('Created roles: alpha, beta')
            ->assertExitCode(0);

        $this->assertDatabaseHas('roles', ['name' => 'alpha']);
        $this->assertDatabaseHas('roles', ['name' => 'beta']);
    }
}
