<?php

namespace Tests\Feature\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Traits\AuthTestHelpers;
use Tests\TestCase;

class SystemsControllerTest extends TestCase
{
    use RefreshDatabase;
    use AuthTestHelpers;

    public function test_guest_cannot_access_systems_page(): void
    {
        $response = $this->get('/admin/systems');
        $response->assertRedirect('/user');
    }

    public function test_admin_can_view_systems_page(): void
    {
        $admin = $this->createAdminUser();
        $this->actingAs($admin);
        $admin->markEmailAsVerified();

        $response = $this->get('/admin/systems');
        $response->assertOk();
        $response->assertSee('System Configuration');
        $response->assertSee('System Timezone');
        $response->assertSee('User Management');
        $response->assertSee('Users per page');
    }

    public function test_update_timezone_persists_and_redirects(): void
    {
        $admin = $this->createAdminUser();
        $this->actingAs($admin);
        $admin->markEmailAsVerified();

        $response = $this->post('/admin/systems', [
            'timezone' => 'America/New_York',
        ]);

        $response->assertRedirect(route('admin.systems.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('settings', [
            'key' => 'system.timezone',
            'value' => 'America/New_York',
        ]);
    }

    public function test_update_site_name_and_contact_email_and_users_per_page(): void
    {
        $admin = $this->createAdminUser();
        $this->actingAs($admin);
        $admin->markEmailAsVerified();

        $response = $this->post('/admin/systems', [
            'site_name' => 'My Cool Site',
            'contact_email' => 'contact@example.com',
            'users_per_page' => 33,
        ]);

        $response->assertRedirect(route('admin.systems.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('settings', [
            'key' => 'system.site_name',
            'value' => 'My Cool Site',
        ]);
        $this->assertDatabaseHas('settings', [
            'key' => 'system.contact_email',
            'value' => 'contact@example.com',
        ]);
        $this->assertDatabaseHas('settings', [
            'key' => 'users.pagination',
            'value' => '33',
        ]);
    }

    public function test_posting_same_timezone_results_in_no_changes_message(): void
    {
        $admin = $this->createAdminUser();
        $this->actingAs($admin);
        $admin->markEmailAsVerified();

        // First set to a known value
        $this->post('/admin/systems', [
            'timezone' => 'UTC',
        ])->assertRedirect(route('admin.systems.index'));

        // Post the same value again
        $response = $this->post('/admin/systems', [
            'timezone' => 'UTC',
        ]);

        $response->assertRedirect(route('admin.systems.index'));
        $response->assertSessionHas('success', 'No changes to save.');
    }

    public function test_invalid_timezone_is_rejected(): void
    {
        $admin = $this->createAdminUser();
        $this->actingAs($admin);
        $admin->markEmailAsVerified();

        $response = $this->post('/admin/systems/timezone', [
            'timezone' => 'Not/A_Timezone',
        ]);

        $response->assertSessionHasErrors('timezone');
    }
}
