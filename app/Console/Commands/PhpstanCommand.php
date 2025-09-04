<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class PhpstanCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'phpstan';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate IDE helpers and run PHPStan analysis';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Running IDE helper generators and PHPStan analysis...');
        $this->newLine();

        $this->line('📝 Generating IDE helper file...');
        $this->call('ide-helper:generate');
        $this->newLine();

        $this->line('📝 Generating IDE helper models...');
        $this->call('ide-helper:models', ['--write-mixin' => true, '--no-interaction' => true]);
        $this->newLine();

        $this->line('📝 Generating IDE helper meta file...');
        $this->call('ide-helper:meta');
        $this->newLine();

        $this->line('🔍 Running PHPStan analysis...');

        $phpstanPath = base_path('vendor\bin\phpstan');
        $phpstanBatPath = base_path('vendor\bin\phpstan.bat');

        // On Windows, check for both the regular binary and the .bat file
        if (file_exists($phpstanBatPath)) {
            $phpstanPath = $phpstanBatPath;
        } elseif (!file_exists($phpstanPath)) {
            $this->error('PHPStan binary not found at: ' . $phpstanPath);
            $this->line('Make sure you have installed PHPStan via Composer.');
            return Command::FAILURE;
        }

        $process = new Process([
            $phpstanPath,
            'analyse',
            '--no-progress',
            '--error-format=table',
            '--memory-limit=1G'
        ]);

        $process->setTty(false);
        $process->setTimeout(null);

        $process->run(function ($type, $buffer) {
            $this->output->write($buffer);
        });

        $this->newLine();

        if ($process->isSuccessful()) {
            $this->info('✅ PHPStan analysis completed successfully!');
            return Command::SUCCESS;
        } else {
            $this->error('❌ Failure, pleae check logs.');
            return Command::FAILURE;
        }
    }
}
