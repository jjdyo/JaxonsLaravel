<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use App\Models\ApiKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Facades\Hash;
use Tests\Feature\Traits\AuthTestHelpers;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Notification;

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

    /**
     * Verified users can view and create API tokens (user routes).
     */
    public function test_verified_user_can_access_api_token_pages_and_create_token(): void
    {
        $user = $this->createRegularUser();

        // Index and create pages
        $this->actingAs($user)
            ->get(route('api-tokens.index'))
            ->assertStatus(200)
            ->assertViewIs('user.api-tokens.index');

        $this->actingAs($user)
            ->get(route('api-tokens.create'))
            ->assertStatus(200)
            ->assertViewIs('user.api-tokens.create');

        // Create token
        $scopes = array_keys(config('api-scopes.scopes', []));
        $payload = [
            'name' => 'MyToken01',
            'expiration' => 'month',
            'scopes' => [$scopes[0]],
        ];

        $response = $this->actingAs($user)
            ->post(route('api-tokens.store'), $payload);

        $response->assertRedirect(route('api-tokens.index'));
        $response->assertSessionHas('success');
        $response->assertSessionHas('plainTextApiKey');

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'tokenable_type' => User::class,
            'name' => 'MyToken01',
        ]);
    }

    /**
     * Unverified users should be redirected away from API token routes.
     */
    public function test_unverified_user_cannot_access_api_token_routes(): void
    {
        $user = $this->createUnverifiedUser();

        $this->actingAs($user)
            ->get(route('api-tokens.index'))
            ->assertRedirect(route('verification.notice'));

        $this->actingAs($user)
            ->get(route('api-tokens.create'))
            ->assertRedirect(route('verification.notice'));

        $this->actingAs($user)
            ->post(route('api-tokens.store'), [
                'name' => 'BlockedToken',
                'expiration' => 'week',
                'scopes' => [array_key_first(config('api-scopes.scopes'))],
            ])
            ->assertRedirect(route('verification.notice'));
    }

    /**
     * Users can view and revoke their own token; cannot view others.
     */
    public function test_user_can_view_and_delete_own_api_token(): void
    {
        $user = $this->createRegularUser();
        $token = $user->createToken('Viewable', ['read-data']);
        $tokenModel = $token->accessToken; // instance of ApiKey (PersonalAccessToken)

        // Show
        $this->actingAs($user)
            ->get(route('api-tokens.show', $tokenModel))
            ->assertStatus(200)
            ->assertViewIs('user.api-tokens.show');

        // Delete
        $this->actingAs($user)
            ->delete(route('api-tokens.destroy', $tokenModel))
            ->assertRedirect(route('api-tokens.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $tokenModel->id,
        ]);
    }

    /**
     * Admin can manage a user's API keys via admin routes; regular users cannot.
     */
    public function test_admin_can_manage_users_api_keys_and_regular_user_cannot(): void
    {
        $admin = $this->createAdminUser();
        $target = $this->createRegularUser();

        // Admin: index and create
        $this->actingAs($admin)
            ->get(route('admin.users.api-keys.index', $target))
            ->assertStatus(200)
            ->assertViewIs('admin.users.api-keys.index');

        $this->actingAs($admin)
            ->get(route('admin.users.api-keys.create', $target))
            ->assertStatus(200)
            ->assertViewIs('admin.users.api-keys.create');

        // Admin: create token
        $response = $this->actingAs($admin)
            ->post(route('admin.users.api-keys.store', $target), [
                'name' => 'AdminToken1',
                'abilities' => ['read-data'],
                'expires_at' => now()->addDay()->toDateTimeString(),
            ]);

        $response->assertRedirect(route('admin.users.api-keys.index', $target));
        $response->assertSessionHas('success');
        $response->assertSessionHas('plainTextApiKey');

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $target->id,
            'tokenable_type' => User::class,
            'name' => 'AdminToken1',
        ]);

        // Admin: revoke token
        $created = ApiKey::query()->where('tokenable_id', $target->id)->where('name', 'AdminToken1')->first();
        $this->assertNotNull($created, 'Expected token not found');

        $this->actingAs($admin)
            ->delete(route('admin.users.api-keys.destroy', [$target, $created]))
            ->assertRedirect(route('admin.users.api-keys.index', $target))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $created->id,
        ]);

        // Regular user cannot access admin routes
        $regular = $this->createRegularUser();
        $this->actingAs($regular)
            ->get(route('admin.users.api-keys.index', $target))
            ->assertStatus(403);
    }
    public function test_user_password_change_requires_email_confirmation_and_applies_on_verify(): void
    {
        Notification::fake();
        $user = $this->createRegularUser([
            'password' => Hash::make('old-password'),
        ]);

        $payload = [
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ];

        $response = $this->actingAs($user)
            ->post(route('profile.password.update'), $payload);

        $response->assertRedirect(route('profile'));

        $user->refresh();
        // Password should NOT be changed yet
        $this->assertTrue(Hash::check('old-password', $user->password));

        // Pending record created and verification email sent
        $pending = \App\Models\PendingPasswordChange::where('user_id', $user->id)->first();
        $this->assertNotNull($pending);
        Notification::assertSentTo($user, \App\Notifications\VerifyPasswordChangeNotification::class);

        // Simulate clicking the link from the email
        $url = \Illuminate\Support\Facades\URL::temporarySignedRoute('password-change.verify', now()->addMinutes(20), ['id' => $pending->id]);
        $confirmResponse = $this->get($url);
        $confirmResponse->assertRedirect(route('profile'));

        $user->refresh();
        $this->assertTrue(Hash::check('new-secure-password', $user->password));
        $this->assertDatabaseMissing('pending_password_changes', ['id' => $pending->id]);
    }

    public function test_password_change_verification_link_expires_after_20_minutes(): void
    {
        Notification::fake();
        $user = $this->createRegularUser([
            'password' => Hash::make('old-password'),
        ]);

        $payload = [
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ];

        $this->actingAs($user)->post(route('profile.password.update'), $payload)->assertRedirect(route('profile'));
        $pending = \App\Models\PendingPasswordChange::where('user_id', $user->id)->firstOrFail();
        $url = \Illuminate\Support\Facades\URL::temporarySignedRoute('password-change.verify', now()->addMinutes(20), ['id' => $pending->id]);

        // Travel 21 minutes forward so the signed URL expires
        $this->travel(21)->minutes();
        $this->get($url)->assertStatus(403);

        $user->refresh();
        $this->assertTrue(Hash::check('old-password', $user->password));
    }
}
