<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\Feature\Traits\AuthTestHelpers;
use Tests\TestCase;

class UserManagementControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker, AuthTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        // Ensure base roles exist
        $this->createRoles();
    }

    public function test_admin_index_lists_users_with_filters_and_search(): void
    {
        $admin = $this->createAdminUser();
        // Create a mix of users
        $u1 = $this->createRegularUser(['name' => 'Alice A', 'email' => 'alice@example.com']);
        $u2 = $this->createRegularUser(['name' => 'Bob B', 'email' => 'bob@example.com']);
        $u3 = $this->createRegularUser(['name' => 'Zelda Z', 'email' => 'zelda@example.com']);
        $u4 = $this->createUnverifiedUser(['name' => 'Unverified U', 'email' => 'uv@example.com']);

        // Default filter = new
        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertStatus(200)
            ->assertViewIs('admin.users.index')
            ->assertViewHas('users');

        // Filter: unverified should include u4
        $this->actingAs($admin)
            ->get(route('admin.users.index', ['filter' => 'unverified']))
            ->assertStatus(200)
            ->assertSee('Unverified U');

        // Filter: az should order by name asc (Alice before Zelda)
        $resp = $this->actingAs($admin)
            ->get(route('admin.users.index', ['filter' => 'az']))
            ->assertStatus(200);
        $content = $resp->getContent();
        $this->assertTrue(strpos($content, 'Alice A') < strpos($content, 'Zelda Z'));

        // Search filter by email fragment
        $this->actingAs($admin)
            ->get(route('admin.users.index', ['filter' => 'search', 'q' => 'bob@']))
            ->assertStatus(200)
            ->assertSee('Bob B')
            ->assertDontSee('Alice A');
    }

    public function test_admin_show_displays_user_details(): void
    {
        $admin = $this->createAdminUser();
        $target = $this->createRegularUser();

        $this->actingAs($admin)
            ->get(route('admin.users.show', $target))
            ->assertStatus(200)
            ->assertViewIs('admin.users.show')
            ->assertSee($target->email);
    }

    public function test_admin_edit_displays_form_with_roles(): void
    {
        $admin = $this->createAdminUser();
        $target = $this->createRegularUser();

        $this->actingAs($admin)
            ->get(route('admin.users.edit', $target))
            ->assertStatus(200)
            ->assertViewIs('admin.users.edit')
            ->assertSee('Account Details')
            ->assertSee('Roles')
            ->assertSee('Permissions');
    }

    public function test_admin_update_user_validates_and_updates_fields(): void
    {
        $admin = $this->createAdminUser();
        $target = $this->createRegularUser(['name' => 'Before', 'email' => 'before@example.com']);

        // Invalid email
        $this->actingAs($admin)
            ->from(route('admin.users.edit', $target))
            ->put(route('admin.users.update', $target), [
                'name' => 'After',
                'email' => 'not-an-email',
                'password' => '',
            ])
            ->assertRedirect(route('admin.users.edit', $target))
            ->assertSessionHasErrors(['email']);

        // Valid update with email_verified toggle on and new password
        $this->actingAs($admin)
            ->put(route('admin.users.update', $target), [
                'name' => 'After',
                'email' => 'after@example.com',
                'password' => 'newpassword123',
                'email_verified' => '1',
            ])
            ->assertRedirect(route('admin.users.show', $target))
            ->assertSessionHas('success');

        $target->refresh();
        $this->assertSame('After', $target->name);
        $this->assertSame('after@example.com', $target->email);
        $this->assertNotNull($target->email_verified_at);
        $this->assertTrue(password_verify('newpassword123', $target->getAuthPassword()));

        // Turn email_verified off without changing password
        $this->actingAs($admin)
            ->put(route('admin.users.update', $target), [
                'name' => 'After2',
                'email' => 'after2@example.com',
                'password' => '',
                'email_verified' => '0',
            ])
            ->assertRedirect(route('admin.users.show', $target));

        $target->refresh();
        $this->assertSame('After2', $target->name);
        $this->assertSame('after2@example.com', $target->email);
        $this->assertNull($target->email_verified_at);
    }

    public function test_admin_delete_user_prevents_self_and_deletes_others(): void
    {
        $admin = $this->createAdminUser();
        $other = $this->createRegularUser();

        // Prevent self-delete
        $this->actingAs($admin)
            ->from(route('admin.users.show', $admin))
            ->delete(route('admin.users.destroy', $admin))
            ->assertRedirect()
            ->assertSessionHasErrors(['error']);

        // Can delete other user
        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $other))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $other->id]);
    }

    public function test_admin_verify_and_unverify_user(): void
    {
        $admin = $this->createAdminUser();
        $target = $this->createUnverifiedUser();

        $this->actingAs($admin)
            ->post(route('admin.users.verify', $target))
            ->assertRedirect(route('admin.users.show', $target))
            ->assertSessionHas('success');
        $this->assertNotNull($target->fresh()->email_verified_at);

        $this->actingAs($admin)
            ->post(route('admin.users.unverify', $target))
            ->assertRedirect(route('admin.users.show', $target))
            ->assertSessionHas('success');
        $this->assertNull($target->fresh()->email_verified_at);
    }

    public function test_admin_update_roles_syncs_roles_by_ids(): void
    {
        $admin = $this->createAdminUser();
        $target = $this->createRegularUser();

        $manager = Role::firstOrCreate(['name' => 'manager']);

        // Submit roles: assign manager only
        $this->actingAs($admin)
            ->post(route('admin.users.roles.update', $target), [
                'roles' => [$manager->id],
            ])
            ->assertRedirect(route('admin.users.show', $target))
            ->assertSessionHas('success');

        $this->assertTrue($target->fresh()->hasRole('manager'));
        $this->assertFalse($target->fresh()->hasRole('user'));

        // Validation: non-existent ID
        $this->actingAs($admin)
            ->from(route('admin.users.edit', $target))
            ->post(route('admin.users.roles.update', $target), [
                'roles' => [999999],
            ])
            ->assertRedirect(route('admin.users.edit', $target))
            ->assertSessionHasErrors(['roles.0']);
    }

    public function test_admin_update_permissions_syncs_permissions_by_ids(): void
    {
        $admin = $this->createAdminUser();
        $target = $this->createRegularUser();

        $perm = Permission::firstOrCreate(['name' => 'edit posts']);

        $this->actingAs($admin)
            ->post(route('admin.users.permissions.update', $target), [
                'permissions' => [$perm->id],
            ])
            ->assertRedirect(route('admin.users.show', $target))
            ->assertSessionHas('success');

        $this->assertTrue($target->fresh()->hasDirectPermission('edit posts'));

        // Validation: non-existent ID
        $this->actingAs($admin)
            ->from(route('admin.users.edit', $target))
            ->post(route('admin.users.permissions.update', $target), [
                'permissions' => [999999],
            ])
            ->assertRedirect(route('admin.users.edit', $target))
            ->assertSessionHasErrors(['permissions.0']);
    }

    public function test_admin_update_user_can_sync_permissions_via_main_form(): void
    {
        $admin = $this->createAdminUser();
        $target = $this->createRegularUser();

        $perm1 = Permission::firstOrCreate(['name' => 'view reports']);
        $perm2 = Permission::firstOrCreate(['name' => 'export reports']);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $target), [
                'name' => $target->name,
                'email' => $target->email,
                'password' => '',
                'permissions' => [$perm1->id, $perm2->id],
            ])
            ->assertRedirect(route('admin.users.show', $target))
            ->assertSessionHas('success');

        $target->refresh();
        $this->assertTrue($target->hasDirectPermission('view reports'));
        $this->assertTrue($target->hasDirectPermission('export reports'));

        // If permissions key is omitted, no change should occur
        $this->actingAs($admin)
            ->put(route('admin.users.update', $target), [
                'name' => $target->name,
                'email' => $target->email,
                'password' => '',
            ])
            ->assertRedirect(route('admin.users.show', $target));

        $this->assertTrue($target->fresh()->hasDirectPermission('view reports'));
        $this->assertTrue($target->fresh()->hasDirectPermission('export reports'));
    }

    public function test_non_admin_is_forbidden_from_admin_user_routes(): void
    {
        $regular = $this->createRegularUser();
        $target = $this->createRegularUser();

        $this->actingAs($regular)
            ->get(route('admin.users.index'))
            ->assertStatus(403);

        $this->actingAs($regular)
            ->get(route('admin.users.show', $target))
            ->assertStatus(403);

        $this->actingAs($regular)
            ->get(route('admin.users.edit', $target))
            ->assertStatus(403);

        $this->actingAs($regular)
            ->put(route('admin.users.update', $target), [])
            ->assertStatus(403);

        $this->actingAs($regular)
            ->delete(route('admin.users.destroy', $target))
            ->assertStatus(403);

        $this->actingAs($regular)
            ->post(route('admin.users.verify', $target))
            ->assertStatus(403);

        $this->actingAs($regular)
            ->post(route('admin.users.unverify', $target))
            ->assertStatus(403);

        $this->actingAs($regular)
            ->post(route('admin.users.roles.update', $target), [])
            ->assertStatus(403);

        $this->actingAs($regular)
            ->post(route('admin.users.permissions.update', $target), [])
            ->assertStatus(403);
    }
    public function test_moderator_can_access_user_management_routes(): void
    {
        $moderator = $this->createModeratorUser();
        $target = $this->createRegularUser();

        $this->actingAs($moderator)
            ->get(route('admin.users.index'))
            ->assertStatus(200);

        $this->actingAs($moderator)
            ->get(route('admin.users.show', $target))
            ->assertStatus(200);

        $this->actingAs($moderator)
            ->get(route('admin.users.edit', $target))
            ->assertStatus(200);
    }

    public function test_moderator_cannot_access_edit_page_for_admin_or_peer(): void
    {
        $moderator = $this->createModeratorUser();
        $peer = $this->createModeratorUser();
        $admin = $this->createAdminUser();

        // Cannot view edit page for peer moderator
        $this->actingAs($moderator)
            ->get(route('admin.users.edit', $peer))
            ->assertStatus(403);

        // Cannot view edit page for admin
        $this->actingAs($moderator)
            ->get(route('admin.users.edit', $admin))
            ->assertStatus(403);
    }

    public function test_moderator_can_update_regular_user_roles_within_allowed_set(): void
    {
        $moderator = $this->createModeratorUser();
        $target = $this->createRegularUser();

        $modRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'moderator']);
        $adminRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);

        // Attempt to assign both moderator (allowed) and admin (not allowed)
        $this->actingAs($moderator)
            ->put(route('admin.users.update', $target), [
                'name' => $target->name,
                'email' => $target->email,
                'password' => '',
                'roles' => [$modRole->id, $adminRole->id],
            ])
            ->assertRedirect(route('admin.users.show', $target))
            ->assertSessionHas('success');

        $fresh = $target->fresh();
        $this->assertTrue($fresh->hasRole('moderator'));
        $this->assertFalse($fresh->hasRole('admin'));
    }

    public function test_moderator_cannot_update_or_delete_moderator_or_admin(): void
    {
        $moderator = $this->createModeratorUser();
        $peer = $this->createModeratorUser();
        $admin = $this->createAdminUser();

        // Cannot update peer moderator
        $this->actingAs($moderator)
            ->from(route('admin.users.edit', $peer))
            ->put(route('admin.users.update', $peer), [
                'name' => $peer->name,
                'email' => $peer->email,
                'password' => '',
            ])
            ->assertRedirect(route('admin.users.edit', $peer))
            ->assertSessionHasErrors(['error']);

        // Cannot delete peer moderator
        $this->actingAs($moderator)
            ->from(route('admin.users.show', $peer))
            ->delete(route('admin.users.destroy', $peer))
            ->assertRedirect()
            ->assertSessionHasErrors(['error']);

        // Cannot delete admin
        $this->actingAs($moderator)
            ->from(route('admin.users.show', $admin))
            ->delete(route('admin.users.destroy', $admin))
            ->assertRedirect()
            ->assertSessionHasErrors(['error']);
    }

    public function test_moderator_can_delete_regular_user(): void
    {
        $moderator = $this->createModeratorUser();
        $user = $this->createRegularUser();

        $this->actingAs($moderator)
            ->delete(route('admin.users.destroy', $user))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_admin_can_delete_moderator(): void
    {
        $admin = $this->createAdminUser();
        $moderator = $this->createModeratorUser();

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $moderator))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $moderator->id]);
    }

    public function test_admin_can_update_self_permissions_via_main_form(): void
    {
        $admin = $this->createAdminUser();
        $perm1 = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'self manage']);
        $perm2 = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'view secrets']);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $admin), [
                'name' => $admin->name,
                'email' => $admin->email,
                'password' => '',
                'permissions' => [$perm1->id, $perm2->id],
            ])
            ->assertRedirect(route('admin.users.show', $admin))
            ->assertSessionHas('success');

        $admin->refresh();
        $this->assertTrue($admin->hasDirectPermission('self manage'));
        $this->assertTrue($admin->hasDirectPermission('view secrets'));
    }

    public function test_moderator_can_update_own_permissions_but_not_admin_permissions(): void
    {
        $moderator = $this->createModeratorUser();
        $admin = $this->createAdminUser();
        $perm = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'moderate forum']);

        // Moderator can update their own permissions via dedicated endpoint
        $this->actingAs($moderator)
            ->post(route('admin.users.permissions.update', $moderator), [
                'permissions' => [$perm->id],
            ])
            ->assertRedirect(route('admin.users.show', $moderator))
            ->assertSessionHas('success');

        $this->assertTrue($moderator->fresh()->hasDirectPermission('moderate forum'));

        // Moderator cannot update an admin's permissions
        $this->actingAs($moderator)
            ->from(route('admin.users.edit', $admin))
            ->post(route('admin.users.permissions.update', $admin), [
                'permissions' => [$perm->id],
            ])
            ->assertRedirect(route('admin.users.edit', $admin))
            ->assertSessionHasErrors(['error']);
    }
}
