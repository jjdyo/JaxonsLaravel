<?php

namespace Tests\Feature\Console;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AddPermissionToUserCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Ensure Spatie tables are migrated if using package migrations
        // The RefreshDatabase trait will handle migrations.
    }

    public function test_assigns_existing_permissions_and_reports_missing(): void
    {
        // Arrange
        $user = User::factory()->create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);

        // Create two permissions, only one exists to start
        Permission::create(['name' => 'view functions page', 'guard_name' => 'web']);
        // "edit widgets" will be missing

        // Act
        $exitCode = Artisan::call('addperm', [
            'identifier' => 'jane@example.com',
            'permissions' => 'view functions page,edit widgets',
            '--guard' => 'web',
        ]);

        $output = Artisan::output();

        // Assert
        $this->assertSame(0, $exitCode, 'Command should succeed');
        $this->assertTrue($user->fresh()->hasPermissionTo('view functions page'));
        $this->assertStringContainsString('Assigned permissions: view functions page', $output);
        $this->assertStringContainsString('These permissions do not exist for guard [web]: edit widgets', $output);
    }

    public function test_reports_already_had_permissions(): void
    {
        // Arrange
        $user = User::factory()->create([
            'name' => 'John Smith',
            'email' => 'john@example.com',
        ]);

        Permission::create(['name' => 'view about page url', 'guard_name' => 'web']);
        $user->givePermissionTo('view about page url');

        // Act
        $this->artisan('addperm', [
            'identifier' => 'john@example.com',
            'permissions' => 'view about page url',
            '--guard' => 'web',
        ])->assertExitCode(0)
          ->expectsOutputToContain('User already had: view about page url');
    }
}
