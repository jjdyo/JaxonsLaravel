<?php

namespace Tests\Feature\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteInfoCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_site_info_runs_and_outputs_sections(): void
    {
        $this->artisan('site:info')
            ->expectsOutputToContain('Gathering site information')
            ->expectsOutputToContain('Application')
            ->expectsOutputToContain('System')
            ->expectsOutputToContain('Database')
            ->expectsOutputToContain('Cache')
            ->expectsOutputToContain('Queue')
            ->expectsOutputToContain('Filesystem')
            ->expectsOutputToContain('Memory')
            ->assertExitCode(0);
    }
}
