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
        $response->assertSee('— Select channel —'); // Changed to match actual HTML
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
     * Test that invalid parameters are handled gracefully.
     *
     * @return void
     */
    public function testInvalidParametersAreHandledGracefully()
    {
        // Create and login as admin
        $admin = $this->createAdminUser();
        $this->actingAs($admin);

        // Test with invalid channel - should still return 200 but show no logs
        $response = $this->get(route('admin.system-logs.index', [
            'channel' => 'invalid'
        ]));
        $response->assertStatus(200);
        $response->assertViewIs('admin.system-logs.index');
        $response->assertSee('Choose a channel to view logs');

        // Test with invalid date format - should still return 200
        $response = $this->get(route('admin.system-logs.index', [
            'channel' => 'web',
            'date' => 'invalid-date'
        ]));
        $response->assertStatus(200);
        $response->assertViewIs('admin.system-logs.index');
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

        // Test accessing logs with a specific channel and date
        $response = $this->get(route('admin.system-logs.index', [
            'channel' => $testChannel,
            'date' => $testDate
        ]));

        // Assert successful response
        $response->assertStatus(200);
        $response->assertViewIs('admin.system-logs.index');
        $response->assertSee($testChannel); // Should show the selected channel
        $response->assertSee('Test log entry for dated file'); // Should show log content

        // Test accessing logs with just channel (should get latest date)
        $response = $this->get(route('admin.system-logs.index', [
            'channel' => $testChannel
        ]));

        $response->assertStatus(200);
        $response->assertViewIs('admin.system-logs.index');
        $response->assertSee($testChannel);

        // Clean up test file
        if (file_exists($testLogPath)) {
            unlink($testLogPath);
        }
    }

    /**
     * Test that channels are discovered correctly from log files.
     *
     * @return void
     */
    public function testChannelDiscoveryFromLogFiles()
    {
        // Create and login as admin
        $admin = $this->createAdminUser();
        $this->actingAs($admin);

        // Create test log files for different channels
        $testDate = '2025-09-03';
        $channels = ['web', 'api', 'custom'];
        $createdFiles = [];

        foreach ($channels as $channel) {
            $logPath = storage_path("logs/{$channel}-{$testDate}.log");

            // Ensure the logs directory exists
            if (!file_exists(dirname($logPath))) {
                mkdir(dirname($logPath), 0755, true);
            }

            file_put_contents($logPath, "[{$testDate} 10:00:00] test.INFO: Test log for {$channel}");
            $createdFiles[] = $logPath;
        }

        // Access the system logs page
        $response = $this->get(route('admin.system-logs.index'));

        // Assert successful response and that all channels are available
        $response->assertStatus(200);
        foreach ($channels as $channel) {
            $response->assertSee($channel);
        }

        // Clean up test files
        foreach ($createdFiles as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }
}
