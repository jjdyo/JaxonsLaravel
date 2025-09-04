<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Facades\Hash;
use Tests\Feature\Traits\AuthTestHelpers;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker, AuthTestHelpers;

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        $this->createRoles();
    }

    /**
     * Test that the login page can be displayed.
     */
    public function test_login_page_can_be_rendered(): void
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $response->assertViewIs('auth.login');
    }

    /**
     * Test that a user can login with valid credentials.
     */
    public function test_users_can_authenticate_with_valid_credentials(): void
    {
        $user = $this->createRegularUser([
            'password' => Hash::make('password'),
        ]);

        $response = $this->post(route('login.process'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/');
    }

    /**
     * Test that a user cannot login with invalid credentials.
     */
    public function test_users_cannot_authenticate_with_invalid_credentials(): void
    {
        $user = $this->createRegularUser();

        $response = $this->post(route('login.process'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    /**
     * Test that a user can logout.
     */
    public function test_users_can_logout(): void
    {
        $user = $this->createRegularUser();

        $response = $this->actingAs($user)
            ->post(route('logout'));

        $this->assertGuest();
        $response->assertRedirect(route('home'));
    }

    /**
     * Test that the registration page can be displayed.
     */
    public function test_registration_page_can_be_rendered(): void
    {
        $response = $this->get(route('register'));

        $response->assertStatus(200);
        $response->assertViewIs('auth.register');
    }

    /**
     * Test that a new user can register.
     */
    public function test_new_users_can_register(): void
    {
        $response = $this->post(route('register.process'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        // Assert the response is a redirect to the verification notice
        $response->assertRedirect(route('verification.notice'));

        // Check that the user was created with the correct role
        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user, 'User was not created');
        $this->assertUserHasRole($user, 'user');
    }

    /**
     * Test that a user with admin role can access admin-only routes.
     */
    public function test_admin_can_access_admin_routes(): void
    {
        // Create an admin user
        $admin = $this->createAdminUser();

        // Simulate a request to an admin-only route
        $response = $this->actingAs($admin)
            ->get('/admin'); // Adjust this to an actual admin route in your application

        // Assert the admin can access the route
        $response->assertStatus(200);
    }

    /**
     * Test that a regular user cannot access admin-only routes.
     */
    public function test_regular_user_cannot_access_admin_routes(): void
    {
        // Create a regular user
        $user = $this->createRegularUser();

        // Simulate a request to an admin-only route
        $response = $this->actingAs($user)
            ->get('/admin'); // Adjust this to an actual admin route in your application

        // Assert the user is redirected or gets a 403 forbidden response
        $response->assertStatus(403);
    }

    /**
     * Test that a guest cannot access authenticated routes.
     */
    public function test_guest_cannot_access_authenticated_routes(): void
    {
        // Simulate a request to an authenticated route
        $response = $this->get(route('profile'));

        // Assert the guest is redirected to login
        $response->assertRedirect(route('login'));
    }

    /**
     * Test that an authenticated user can access their profile.
     */
    public function test_authenticated_user_can_access_profile(): void
    {
        $user = $this->createRegularUser();

        $response = $this->actingAs($user)
            ->get(route('profile'));

        $response->assertStatus(200);
        $response->assertViewIs('pages.profile');
    }

    /**
     * Test that a user can update their profile.
     */
    public function test_user_can_update_profile(): void
    {
        $user = $this->createRegularUser();

        $newName = 'Updated Name';
        $newEmail = 'updated@example.com';

        $response = $this->actingAs($user)
            ->put(route('profile.update'), [
                'name' => $newName,
                'email' => $newEmail,
            ]);

        // If email changed, user should be redirected to verification notice
        $response->assertRedirect(route('verification.notice'));

        // Refresh the user from the database
        $user->refresh();

        // Assert the user's information was updated
        $this->assertEquals($newName, $user->name);
        $this->assertEquals($newEmail, $user->email);
        $this->assertNull($user->email_verified_at);
    }

    /**
     * Test that a user can update their profile without changing email.
     */
    public function test_user_can_update_profile_without_changing_email(): void
    {
        $user = $this->createRegularUser();

        $newName = 'Updated Name';

        $response = $this->actingAs($user)
            ->put(route('profile.update'), [
                'name' => $newName,
                'email' => $user->email,
            ]);

        // If email didn't change, user should be redirected to profile
        $response->assertRedirect(route('profile'));

        // Refresh the user from the database
        $user->refresh();

        // Assert the user's name was updated but email verification status remains
        $this->assertEquals($newName, $user->name);
        $this->assertNotNull($user->email_verified_at);
    }
}
