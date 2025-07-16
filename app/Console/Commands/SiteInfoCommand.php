<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;

class SiteInfoCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'site:info {--json : Output in JSON format}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display comprehensive site information including database, memory usage, and system details';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $info = $this->gatherSiteInfo();

        if ($this->option('json')) {
            $this->line(json_encode($info, JSON_PRETTY_PRINT));
            return 0;
        }

        $this->displayFormattedInfo($info);
        return 0;
    }

    /**
     * Gather all site information
     *
     * @return array
     */
    private function gatherSiteInfo(): array
    {
        return [
            'application' => $this->getApplicationInfo(),
            'environment' => $this->getEnvironmentInfo(),
            'database' => $this->getDatabaseInfo(),
            'cache' => $this->getCacheInfo(),
            'memory' => $this->getMemoryInfo(),
            'storage' => $this->getStorageInfo(),
            'queue' => $this->getQueueInfo(),
            'system' => $this->getSystemInfo(),
        ];
    }

    /**
     * Get application information
     *
     * @return array
     */
    private function getApplicationInfo(): array
    {
        return [
            'name' => config('app.name'),
            'url' => config('app.url'),
            'environment' => config('app.env'),
            'debug' => config('app.debug'),
            'timezone' => config('app.timezone'),
            'locale' => config('app.locale'),
            'laravel_version' => app()->version(),
            'php_version' => phpversion(),
        ];
    }

    /**
     * Get environment information
     *
     * @return array
     */
    private function getEnvironmentInfo(): array
    {
        return [
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
            'server_name' => $_SERVER['SERVER_NAME'] ?? 'Unknown',
            'server_port' => $_SERVER['SERVER_PORT'] ?? 'Unknown',
            'https' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        ];
    }

    /**
     * Get database information
     *
     * @return array
     */
    private function getDatabaseInfo(): array
    {
        try {
            $connection = DB::connection();
            $pdo = $connection->getPdo();

            $info = [
                'default_connection' => config('database.default'),
                'driver' => $connection->getDriverName(),
                'database_name' => $connection->getDatabaseName(),
                'host' => config('database.connections.' . config('database.default') . '.host'),
                'port' => config('database.connections.' . config('database.default') . '.port'),
                'connected' => true,
            ];

            // Get database version
            try {
                $version = $pdo->query('SELECT VERSION()')->fetchColumn();
                $info['version'] = $version;
            } catch (\Exception $e) {
                $info['version'] = 'Unknown';
            }

            // Get database size (MySQL/MariaDB specific)
            if (in_array($connection->getDriverName(), ['mysql', 'mariadb'])) {
                try {
                    $size = DB::select("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'size_mb' FROM information_schema.tables WHERE table_schema = ?", [$connection->getDatabaseName()]);
                    $info['size_mb'] = $size[0]->size_mb ?? 'Unknown';
                } catch (\Exception $e) {
                    $info['size_mb'] = 'Unknown';
                }
            }

            return $info;
        } catch (\Exception $e) {
            return [
                'connected' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get cache information
     *
     * @return array
     */
    private function getCacheInfo(): array
    {
        $info = [
            'default_driver' => config('cache.default'),
            'prefix' => config('cache.prefix'),
        ];

        try {
            // Test cache functionality
            $testKey = 'site_info_test_' . time();
            Cache::put($testKey, 'test_value', 10);
            $testValue = Cache::get($testKey);
            Cache::forget($testKey);

            $info['working'] = $testValue === 'test_value';
        } catch (\Exception $e) {
            $info['working'] = false;
            $info['error'] = $e->getMessage();
        }

        // Redis specific info
        if (config('cache.default') === 'redis') {
            try {
                $redis = Redis::connection();
                $info['redis_version'] = $redis->command('INFO', ['server'])['redis_version'] ?? 'Unknown';
                $info['redis_memory'] = $redis->command('INFO', ['memory'])['used_memory_human'] ?? 'Unknown';
            } catch (\Exception $e) {
                $info['redis_error'] = $e->getMessage();
            }
        }

        return $info;
    }

    /**
     * Get memory information
     *
     * @return array
     */
    private function getMemoryInfo(): array
    {
        return [
            'current_usage' => $this->formatBytes(memory_get_usage(true)),
            'peak_usage' => $this->formatBytes(memory_get_peak_usage(true)),
            'memory_limit' => ini_get('memory_limit'),
            'memory_limit_bytes' => $this->parseMemoryLimit(ini_get('memory_limit')),
            'usage_percentage' => round((memory_get_usage(true) / $this->parseMemoryLimit(ini_get('memory_limit'))) * 100, 2) . '%',
        ];
    }

    /**
     * Get storage information
     *
     * @return array
     */
    private function getStorageInfo(): array
    {
        $info = [
            'default_disk' => config('filesystems.default'),
            'disks' => [],
        ];

        foreach (config('filesystems.disks') as $name => $config) {
            try {
                $disk = Storage::disk($name);
                $diskInfo = [
                    'driver' => $config['driver'],
                    'root' => $config['root'] ?? 'N/A',
                ];

                if ($config['driver'] === 'local') {
                    $path = $config['root'];
                    if (is_dir($path)) {
                        $diskInfo['free_space'] = $this->formatBytes(disk_free_space($path));
                        $diskInfo['total_space'] = $this->formatBytes(disk_total_space($path));
                    }
                }

                $info['disks'][$name] = $diskInfo;
            } catch (\Exception $e) {
                $info['disks'][$name] = ['error' => $e->getMessage()];
            }
        }

        return $info;
    }

    /**
     * Get queue information
     *
     * @return array
     */
    private function getQueueInfo(): array
    {
        return [
            'default_connection' => config('queue.default'),
            'driver' => config('queue.connections.' . config('queue.default') . '.driver'),
            'failed_jobs_enabled' => config('queue.failed.driver') !== null,
        ];
    }

    /**
     * Get system information
     *
     * @return array
     */
    private function getSystemInfo(): array
    {
        return [
            'operating_system' => php_uname('s'),
            'php_sapi' => php_sapi_name(),
            'max_execution_time' => ini_get('max_execution_time') . 's',
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'loaded_extensions' => count(get_loaded_extensions()),
            'opcache_enabled' => extension_loaded('opcache') && ini_get('opcache.enable'),
        ];
    }

    /**
     * Display formatted information
     *
     * @param array $info
     */
    private function displayFormattedInfo(array $info): void
    {
        $this->info('=== Site Information ===');
        $this->newLine();

        // Application Info
        $this->comment('Application:');
        $this->table(['Property', 'Value'], [
            ['Name', $info['application']['name']],
            ['URL', $info['application']['url']],
            ['Environment', $info['application']['environment']],
            ['Debug Mode', $info['application']['debug'] ? 'Enabled' : 'Disabled'],
            ['Timezone', $info['application']['timezone']],
            ['Locale', $info['application']['locale']],
            ['Laravel Version', $info['application']['laravel_version']],
            ['PHP Version', $info['application']['php_version']],
        ]);

        // Database Info
        $this->comment('Database:');
        if ($info['database']['connected']) {
            $dbTable = [
                ['Connection', $info['database']['default_connection']],
                ['Driver', $info['database']['driver']],
                ['Database', $info['database']['database_name']],
                ['Host', $info['database']['host']],
                ['Port', $info['database']['port']],
                ['Version', $info['database']['version'] ?? 'Unknown'],
                ['Status', 'Connected'],
            ];

            if (isset($info['database']['size_mb'])) {
                $dbTable[] = ['Size', $info['database']['size_mb'] . ' MB'];
            }

            $this->table(['Property', 'Value'], $dbTable);
        } else {
            $this->error('Database connection failed: ' . $info['database']['error']);
        }

        // Memory Info
        $this->comment('Memory Usage:');
        $this->table(['Property', 'Value'], [
            ['Current Usage', $info['memory']['current_usage']],
            ['Peak Usage', $info['memory']['peak_usage']],
            ['Memory Limit', $info['memory']['memory_limit']],
            ['Usage Percentage', $info['memory']['usage_percentage']],
        ]);

        // Cache Info
        $this->comment('Cache:');
        $cacheTable = [
            ['Driver', $info['cache']['default_driver']],
            ['Prefix', $info['cache']['prefix']],
            ['Status', $info['cache']['working'] ? 'Working' : 'Failed'],
        ];

        if (isset($info['cache']['redis_version'])) {
            $cacheTable[] = ['Redis Version', $info['cache']['redis_version']];
            $cacheTable[] = ['Redis Memory', $info['cache']['redis_memory']];
        }

        $this->table(['Property', 'Value'], $cacheTable);

        // Storage Info
        $this->comment('Storage:');
        $this->line('Default Disk: ' . $info['storage']['default_disk']);
        foreach ($info['storage']['disks'] as $name => $disk) {
            if (isset($disk['error'])) {
                $this->error("Disk '{$name}': " . $disk['error']);
            } else {
                $this->line("Disk '{$name}' ({$disk['driver']}): " . ($disk['free_space'] ?? 'N/A'));
            }
        }

        // Queue Info
        $this->comment('Queue:');
        $this->table(['Property', 'Value'], [
            ['Default Connection', $info['queue']['default_connection']],
            ['Driver', $info['queue']['driver']],
            ['Failed Jobs', $info['queue']['failed_jobs_enabled'] ? 'Enabled' : 'Disabled'],
        ]);

        // System Info
        $this->comment('System:');
        $this->table(['Property', 'Value'], [
            ['Operating System', $info['system']['operating_system']],
            ['PHP SAPI', $info['system']['php_sapi']],
            ['Max Execution Time', $info['system']['max_execution_time']],
            ['Upload Max Filesize', $info['system']['upload_max_filesize']],
            ['Post Max Size', $info['system']['post_max_size']],
            ['Loaded Extensions', $info['system']['loaded_extensions']],
            ['OPcache', $info['system']['opcache_enabled'] ? 'Enabled' : 'Disabled'],
        ]);
    }

    /**
     * Format bytes to human readable format
     *
     * @param int $bytes
     * @return string
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Parse memory limit string to bytes
     *
     * @param string $limit
     * @return int
     */
    private function parseMemoryLimit(string $limit): int
    {
        if ($limit === '-1') {
            return PHP_INT_MAX;
        }

        $limit = trim($limit);
        $unit = strtolower($limit[strlen($limit) - 1]);
        $value = (int) $limit;

        switch ($unit) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }

        return $value;
    }
}
