<?php

namespace Tests\Feature\Console;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Role;
use Tests\Feature\Traits\AuthTestHelpers;
use Tests\TestCase;

class ConsoleCommandsTest extends TestCase
{
    use RefreshDatabase, AuthTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createRoles();
    }

    public function test_site_info_runs_and_outputs_sections(): void
    {
        $this->artisan('site:info')
            ->expectsOutputToContain('Gathering site information')
            ->expectsOutputToContain('Application')
            ->expectsOutputToContain('System')
            ->expectsOutputToContain('Database')
            ->expectsOutputToContain('Cache')
            ->expectsOutputToContain('Queue')
            ->expectsOutputToContain('Filesystem')
            ->expectsOutputToContain('Memory')
            ->assertExitCode(0);
    }

    public function test_dev_clear_runs_without_all_flag(): void
    {
        $this->artisan('dev:clear')
            ->expectsOutputToContain('Clearing development caches')
            ->expectsOutputToContain('Clearing compiled views')
            ->expectsOutputToContain('Clearing configuration cache')
            ->expectsOutputToContain('Clearing route cache')
            ->expectsOutputToContain('Clearing event cache')
            ->expectsOutputToContain('Development caches cleared successfully')
            ->expectsOutputToContain('Use --all flag')
            ->assertExitCode(0);
    }

    public function test_dev_clear_runs_with_all_flag(): void
    {
        $this->artisan('dev:clear', ['--all' => true])
            ->expectsOutputToContain('Clearing development caches')
            ->expectsOutputToContain('Clearing compiled views')
            ->expectsOutputToContain('Clearing configuration cache')
            ->expectsOutputToContain('Clearing route cache')
            ->expectsOutputToContain('Clearing event cache')
            ->expectsOutputToContain('Clearing application cache')
            ->expectsOutputToContain('Clearing compiled services')
            ->assertExitCode(0);
    }

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

    public function test_inspire_command_runs(): void
    {
        $this->artisan('inspire')
            ->assertExitCode(0);
    }

    public function test_phpstan_binary_is_present(): void
    {
        $phpstan = base_path('vendor/bin/phpstan');
        $phpstanBat = base_path('vendor/bin/phpstan.bat');

        $this->assertTrue(
            file_exists($phpstan) || file_exists($phpstanBat),
            'PHPStan binary not found in vendor/bin. Please install PHPStan.'
        );
    }
}
