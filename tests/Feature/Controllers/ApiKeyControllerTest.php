<?php

namespace Tests\Feature\Controllers;

use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Carbon;
use Tests\Feature\Traits\AuthTestHelpers;
use Tests\TestCase;

class ApiKeyControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker, AuthTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        // Ensure roles exist for helpers
        $this->createRoles();
    }

    // ===================== Admin routes =====================

    public function test_admin_index_displays_user_api_keys(): void
    {
        $admin = $this->createAdminUser();
        $target = $this->createRegularUser();
        // Seed a token for target
        $token = $target->createToken('SeededToken', ['read-data']);

        $this->actingAs($admin)
            ->get(route('admin.users.api-keys.index', $target))
            ->assertStatus(200)
            ->assertViewIs('admin.users.api-keys.index')
            ->assertViewHas('user', fn ($u) => $u->is($target))
            ->assertViewHas('apiKeys', function ($keys) use ($token, $target) {
                // ApiKey::getAllForUser sorts desc by created_at; we only assert containment
                return $keys->contains(fn ($k) => $k->tokenable_id === $target->id && $k->name === 'SeededToken');
            });
    }

    public function test_admin_create_displays_form(): void
    {
        $admin = $this->createAdminUser();
        $target = $this->createRegularUser();

        $this->actingAs($admin)
            ->get(route('admin.users.api-keys.create', $target))
            ->assertStatus(200)
            ->assertViewIs('admin.users.api-keys.create')
            ->assertViewHas('user', fn ($u) => $u->is($target));
    }

    public function test_admin_store_creates_token_with_valid_data_and_defaults(): void
    {
        $admin = $this->createAdminUser();
        $target = $this->createRegularUser();

        // When abilities not provided, controller defaults to ['*'] and expires_at can be null
        $response = $this->actingAs($admin)
            ->post(route('admin.users.api-keys.store', $target), [
                'name' => 'AdminCreated01',
                // 'abilities' omitted on purpose
                // 'expires_at' omitted on purpose
            ]);

        $response->assertRedirect(route('admin.users.api-keys.index', $target));
        $response->assertSessionHas('success');
        $response->assertSessionHas('plainTextApiKey');

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $target->id,
            'tokenable_type' => User::class,
            'name' => 'AdminCreated01',
        ]);
    }

    public function test_admin_store_validates_input(): void
    {
        $admin = $this->createAdminUser();
        $target = $this->createRegularUser();

        // Invalid: name with spaces and expires_at in the past
        $response = $this->actingAs($admin)
            ->from(route('admin.users.api-keys.create', $target))
            ->post(route('admin.users.api-keys.store', $target), [
                'name' => 'Invalid Name',
                'abilities' => ['read-data'],
                'expires_at' => now()->subDay()->toDateTimeString(),
            ]);

        $response->assertRedirect(route('admin.users.api-keys.create', $target));
        $response->assertSessionHasErrors(['name', 'expires_at']);

        // Nothing should be created
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $target->id,
            'name' => 'Invalid Name',
        ]);
    }

    public function test_admin_can_destroy_token(): void
    {
        $admin = $this->createAdminUser();
        $target = $this->createRegularUser();
        $created = $target->createToken('ToRevoke', ['*'])->accessToken; // ApiKey instance

        $this->actingAs($admin)
            ->delete(route('admin.users.api-keys.destroy', [$target, $created]))
            ->assertRedirect(route('admin.users.api-keys.index', $target))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $created->id]);
    }

    public function test_regular_user_cannot_access_admin_api_key_routes(): void
    {
        $regular = $this->createRegularUser();
        $target = $this->createRegularUser();

        $this->actingAs($regular)
            ->get(route('admin.users.api-keys.index', $target))
            ->assertStatus(403);
    }

    // ===================== User routes =====================

    public function test_verified_user_can_view_index_and_create_pages(): void
    {
        $user = $this->createRegularUser();

        $this->actingAs($user)
            ->get(route('api-tokens.index'))
            ->assertStatus(200)
            ->assertViewIs('user.api-tokens.index');

        $this->actingAs($user)
            ->get(route('api-tokens.create'))
            ->assertStatus(200)
            ->assertViewIs('user.api-tokens.create');
    }

    public function test_user_store_creates_token_with_valid_scopes_and_expiration(): void
    {
        $user = $this->createRegularUser();
        $availableScopes = array_keys(config('api-scopes.scopes', []));

        $payload = [
            'name' => 'MyToken01',
            'expiration' => 'month',
            'scopes' => [$availableScopes[0]],
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

    public function test_user_store_validates_name_scopes_and_expiration(): void
    {
        $user = $this->createRegularUser();

        // Invalid: missing scopes, non-alphanumeric name, invalid expiration
        $response = $this->actingAs($user)
            ->from(route('api-tokens.create'))
            ->post(route('api-tokens.store'), [
                'name' => 'Bad Name!',
                'expiration' => 'day', // not in week|month|year
                'scopes' => [],
            ]);

        $response->assertRedirect(route('api-tokens.create'));
        $response->assertSessionHasErrors(['name', 'expiration', 'scopes']);

        // Invalid: one invalid scope value
        $response2 = $this->actingAs($user)
            ->from(route('api-tokens.create'))
            ->post(route('api-tokens.store'), [
                'name' => 'ValidName01',
                'expiration' => 'week',
                'scopes' => ['unknown-scope'],
            ]);

        $response2->assertRedirect(route('api-tokens.create'));
        $response2->assertSessionHasErrors(['scopes.0']);
    }

    public function test_user_can_show_and_destroy_own_token_and_cannot_access_others(): void
    {
        $user = $this->createRegularUser();
        $other = $this->createRegularUser();

        $own = $user->createToken('OwnToken', ['read-data'])->accessToken; // ApiKey
        $others = $other->createToken('OthersToken', ['read-data'])->accessToken;

        // Can show own
        $this->actingAs($user)
            ->get(route('api-tokens.show', $own))
            ->assertStatus(200)
            ->assertViewIs('user.api-tokens.show');

        // Cannot show others (controller aborts 403 via authorizeToken)
        $this->actingAs($user)
            ->get(route('api-tokens.show', $others))
            ->assertStatus(403);

        // Can delete own
        $this->actingAs($user)
            ->delete(route('api-tokens.destroy', $own))
            ->assertRedirect(route('api-tokens.index'))
            ->assertSessionHas('success');
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $own->id]);

        // Cannot delete others
        $this->actingAs($user)
            ->delete(route('api-tokens.destroy', $others))
            ->assertStatus(403);
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $others->id]);
    }

    public function test_unverified_user_is_redirected_from_user_routes(): void
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
}
