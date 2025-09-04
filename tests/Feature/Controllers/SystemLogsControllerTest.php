<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use Tests\Feature\Traits\AuthTestHelpers;

class SystemLogsControllerTest extends TestCase
{
    use RefreshDatabase, AuthTestHelpers;

    /**
     * Test that the system logs page can be accessed by admin users.
     *
     * @return void
     */
    public function testSystemLogsPageCanBeAccessedByAdmin()
    {
        // Create and login as admin
        $admin = $this->createAdminUser();
        $this->actingAs($admin);

        // Access the system logs page
        $response = $this->get(route('admin.system-logs.index'));

        // Assert successful response
        $response->assertStatus(200);
        $response->assertViewIs('admin.system-logs.index');
        $response->assertSee('System Logs');
        $response->assertSee('Select Log Channel');
    }

    /**
     * Test that the system logs page cannot be accessed by non-admin users.
     *
     * @return void
     */
    public function testSystemLogsPageCannotBeAccessedByNonAdmin()
    {
        // Create and login as regular user
        $user = $this->createRegularUser();
        $this->actingAs($user);

        // Try to access the system logs page
        $response = $this->get(route('admin.system-logs.index'));

        // Assert redirect or forbidden
        $response->assertStatus(403);
    }

    /**
     * Test that invalid parameters are rejected.
     *
     * @return void
     */
    public function testInvalidParametersAreRejected()
    {
        // Create and login as admin
        $admin = $this->createAdminUser();
        $this->actingAs($admin);

        // Test with invalid channel
        $response = $this->getJson(route('admin.system-logs.fetch', [
            'channel' => 'invalid',
            'page' => 1,
            'limit' => 20
        ]));
        $response->assertStatus(422);

        // Test with invalid page
        $response = $this->getJson(route('admin.system-logs.fetch', [
            'channel' => 'web',
            'page' => 0,
            'limit' => 20
        ]));
        $response->assertStatus(422);

        // Test with invalid limit
        $response = $this->getJson(route('admin.system-logs.fetch', [
            'channel' => 'web',
            'page' => 1,
            'limit' => 200
        ]));
        $response->assertStatus(422);

        // Test with invalid date format
        $response = $this->getJson(route('admin.system-logs.fetch', [
            'channel' => 'web',
            'page' => 1,
            'limit' => 20,
            'date' => 'invalid-date'
        ]));
        $response->assertStatus(422);
    }

    /**
     * Test that the system can handle dated log files correctly.
     *
     * @return void
     */
    public function testDatedLogFilesAreHandledCorrectly()
    {
        // Create and login as admin
        $admin = $this->createAdminUser();
        $this->actingAs($admin);

        // Create a test dated log file
        $testDate = '2025-09-03';
        $testChannel = 'web';
        $testLogPath = storage_path("logs/{$testChannel}-{$testDate}.log");

        // Ensure the logs directory exists
        if (!file_exists(dirname($testLogPath))) {
            mkdir(dirname($testLogPath), 0755, true);
        }

        // Create a test log entry
        $testLogContent = "[2025-09-03 10:00:00] test.INFO: Test log entry for dated file";
        file_put_contents($testLogPath, $testLogContent);

        // Test fetching logs with a specific date
        $response = $this->getJson(route('admin.system-logs.fetch', [
            'channel' => $testChannel,
            'page' => 1,
            'limit' => 20,
            'date' => $testDate
        ]));

        // Assert successful response
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'logs',
            'hasMore'
        ]);

        // Clean up test file
        if (file_exists($testLogPath)) {
            unlink($testLogPath);
        }
    }
}
