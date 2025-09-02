<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ClearDevelopmentCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dev:clear {--all : Clear all caches including heavy ones}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear development caches (views, config, routes) - perfect for template/logic changes';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🧹 Clearing development caches...');

        // Essential clears for template/logic changes
        $commands = [
            'view:clear' => 'Clearing compiled views',
            'config:clear' => 'Clearing configuration cache',
            'route:clear' => 'Clearing route cache',
            'event:clear' => 'Clearing event cache',
        ];

        // Add heavy clears if --all flag is used
        if ($this->option('all')) {
            $commands['cache:clear'] = 'Clearing application cache';
            $commands['clear-compiled'] = 'Clearing compiled services';
        }

        // Execute each command
        foreach ($commands as $command => $description) {
            $this->line("   {$description}...");
            $this->call($command);
        }

        $this->newLine();
        $this->info('✅ Development caches cleared successfully!');

        if (!$this->option('all')) {
            $this->comment('💡 Use --all flag to clear application cache and compiled services too');
        }

        $this->newLine();
        $this->comment('Perfect for after adding new email notifications, views, or config changes!');
        return Command::SUCCESS;
    }
}
