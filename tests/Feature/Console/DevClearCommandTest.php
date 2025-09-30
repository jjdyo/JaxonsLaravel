<?php

namespace Tests\Feature\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DevClearCommandTest extends TestCase
{
    use RefreshDatabase;

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
}
