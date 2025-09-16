<?php

namespace Tests\Feature\Traits;

use App\Models\User;
use Spatie\Permission\Models\Role;

/**
 * Trait AuthTestHelpers
 *
 * This trait provides helper methods for authentication-related tests.
 */
trait AuthTestHelpers
{
    /**
     * Create roles needed for testing.
     *
     * @return array An array containing the created roles
     */
    protected function createRoles(): array
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $userRole = Role::firstOrCreate(['name' => 'user']);

        return [
            'admin' => $adminRole,
            'user' => $userRole,
        ];
    }

    /**
     * Create an admin user for testing.
     *
     * @param array $attributes Additional attributes for the user
     * @return User The created admin user
     */
    protected function createAdminUser(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $roles = $this->createRoles();
        $user->assignRole($roles['admin']);

        return $user;
    }

    /**
     * Create a regular user for testing.
     *
     * @param array $attributes Additional attributes for the user
     * @return User The created regular user
     */
    protected function createRegularUser(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $roles = $this->createRoles();
        $user->assignRole($roles['user']);

        return $user;
    }

    /**
     * Create an unverified user for testing.
     *
     * @param array $attributes Additional attributes for the user
     * @return User The created unverified user
     */
    protected function createUnverifiedUser(array $attributes = []): User
    {
        $user = User::factory()->unverified()->create($attributes);
        $roles = $this->createRoles();
        $user->assignRole($roles['user']);

        return $user;
    }

    /**
     * Assert that a user has a specific role.
     *
     * @param User $user The user to check
     * @param string $role The role name
     * @return void
     */
    protected function assertUserHasRole(User $user, string $role): void
    {
        $this->assertTrue(
            $user->hasRole($role),
            "User does not have the expected role: {$role}"
        );
    }

    /**
     * Assert that a user does not have a specific role.
     *
     * @param User $user The user to check
     * @param string $role The role name
     * @return void
     */
    protected function assertUserDoesNotHaveRole(User $user, string $role): void
    {
        $this->assertFalse(
            $user->hasRole($role),
            "User has a role that they should not have: {$role}"
        );
    }
}
