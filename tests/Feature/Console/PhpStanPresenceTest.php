<?php

namespace Tests\Feature\Console;

use Tests\TestCase;

class PhpStanPresenceTest extends TestCase
{
    public function test_phpstan_binary_is_present(): void
    {
        $phpstan = base_path('vendor/bin/phpstan');
        $phpstanBat = base_path('vendor/bin/phpstan.bat');

        $this->assertTrue(
            file_exists($phpstan) || file_exists($phpstanBat),
            'PHPStan binary not found in vendor/bin. Please install PHPStan.'
        );
    }
}
