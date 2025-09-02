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
            $json = json_encode($info, JSON_PRETTY_PRINT);
            $this->line($json === false ? '{}' : $json);
            return 0;
        }

        $this->displayFormattedInfo($info);
        return 0;
    }

    /**
     * Gather all site information
     *
     * @return array<string, mixed>
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
     * @return array<string, mixed>
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
     * @return array<string, mixed>
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
     * @return array<string, mixed>
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
                'host' => config('database.connections.' . (string)config('database.default') . '.host'),
                'port' => config('database.connections.' . (string)config('database.default') . '.port'),
                'connected' => true,
            ];

            // Get database version
            try {
                $result = $pdo->query('SELECT VERSION()');
                if ($result !== false) {
                    $version = $result->fetchColumn();
                    $info['version'] = $version;
                } else {
                    $info['version'] = 'Unknown';
                }
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
     * @return array<string, mixed>
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

        // Redis specific info - only if Redis is configured and available
        if ($this->isRedisConfigured() && $this->isRedisAvailable()) {
            try {
                $redis = Redis::connection();
                $serverInfo = $redis->command('INFO', ['server']);
                $memoryInfo = $redis->command('INFO', ['memory']);

                // Ensure we're working with arrays
                $serverInfoArray = is_array($serverInfo) ? $serverInfo : [];
                $memoryInfoArray = is_array($memoryInfo) ? $memoryInfo : [];

                $info['redis_version'] = $serverInfoArray['redis_version'] ?? 'Unknown';
                $info['redis_memory'] = $memoryInfoArray['used_memory_human'] ?? 'Unknown';
                $info['redis_connected'] = true;
            } catch (\Exception $e) {
                $info['redis_connected'] = false;
                $info['redis_error'] = $e->getMessage();
            }
        } else {
            $info['redis_configured'] = false;
            $info['redis_note'] = 'Redis not configured or not available';
        }

        return $info;
    }

    /**
     * Get memory information
     *
     * @return array<string, mixed>
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
     * @return array<string, mixed>
     */
    private function getStorageInfo(): array
    {
        $info = [
            'default_disk' => config('filesystems.default'),
            'disks' => [],
        ];

        $disks = config('filesystems.disks');
        if (!is_array($disks)) {
            $disks = [];
        }
        foreach ($disks as $name => $config) {
            try {
                // Ensure $config is an array
                if (!is_array($config)) {
                    $config = [];
                }

                $diskInfo = [
                    'driver' => $config['driver'] ?? 'unknown',
                    'root' => $config['root'] ?? 'N/A',
                ];

                // Handle S3 disks separately
                if (isset($config['driver']) && $config['driver'] === 's3') {
                    if ($this->isS3Available()) {
                        try {
                            // Ensure $name is a string
                            $diskName = is_string($name) ? $name : '';
                            $disk = Storage::disk($diskName);
                            // Test S3 connectivity with a simple operation
                            $disk->exists('.test-connectivity-check');
                            $diskInfo['status'] = 'Connected';
                            $diskInfo['bucket'] = $config['bucket'] ?? 'Unknown';
                            $diskInfo['region'] = $config['region'] ?? 'Unknown';
                        } catch (\Exception $e) {
                            $diskInfo['status'] = 'Connection Failed';
                            $diskInfo['s3_error'] = $e->getMessage();
                        }
                    } else {
                        $diskInfo['status'] = 'AWS SDK not available';
                        $diskInfo['note'] = 'AWS SDK or S3 dependencies not installed';
                    }
                } else {
                    // Handle other disk types
                    $diskName = is_string($name) ? $name : '';
                    $disk = Storage::disk($diskName);

                    if (isset($config['driver']) && $config['driver'] === 'local') {
                        $path = $config['root'] ?? '';
                        $pathString = is_string($path) ? $path : '';
                        if ($pathString !== '' && is_dir($pathString)) {
                            $freeSpace = disk_free_space($pathString);
                            $totalSpace = disk_total_space($pathString);
                            $diskInfo['free_space'] = $this->formatBytes(is_float($freeSpace) ? (int)$freeSpace : 0);
                            $diskInfo['total_space'] = $this->formatBytes(is_float($totalSpace) ? (int)$totalSpace : 0);
                            $diskInfo['status'] = 'Available';
                        } else {
                            $diskInfo['status'] = 'Directory not found';
                        }
                    } else {
                        $diskInfo['status'] = 'Configured';
                    }
                }

                $info['disks'][$name] = $diskInfo;
            } catch (\Exception $e) {
                $info['disks'][$name] = [
                    'error' => $e->getMessage(),
                    'driver' => $config['driver'] ?? 'Unknown'
                ];
            }
        }

        return $info;
    }

    /**
     * Get queue information
     *
     * @return array<string, mixed>
     */
    private function getQueueInfo(): array
    {
        return [
            'default_connection' => config('queue.default'),
            'driver' => config('queue.connections.' . (string)config('queue.default') . '.driver'),
            'failed_jobs_enabled' => config('queue.failed.driver') !== null,
        ];
    }

    /**
     * Get system information
     *
     * @return array<string, mixed>
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
     * Check if Redis is configured
     *
     * @return bool
     */
    private function isRedisConfigured(): bool
    {
        return config('cache.default') === 'redis' ||
            config('database.redis.default') !== null ||
            config('queue.default') === 'redis';
    }

    /**
     * Check if Redis is available
     *
     * @return bool
     */
    private function isRedisAvailable(): bool
    {
        try {
            return class_exists('Redis') ||
                class_exists('Predis\Client') ||
                extension_loaded('redis');
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if AWS S3 is available
     *
     * @return bool
     */
    private function isS3Available(): bool
    {
        try {
            return class_exists('Aws\S3\S3Client') &&
                class_exists('League\Flysystem\AwsS3V3\AwsS3V3Adapter');
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Display formatted information
     *
     * @param array<string, mixed> $info The site information to display
     * @return void
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

        if (isset($info['cache']['redis_connected'])) {
            if ($info['cache']['redis_connected']) {
                $cacheTable[] = ['Redis Version', $info['cache']['redis_version']];
                $cacheTable[] = ['Redis Memory', $info['cache']['redis_memory']];
            } else {
                $cacheTable[] = ['Redis Status', 'Connection Failed'];
            }
        } elseif (isset($info['cache']['redis_configured'])) {
            $cacheTable[] = ['Redis Status', 'Not Configured'];
        }

        $this->table(['Property', 'Value'], $cacheTable);

        // Storage Info
        $this->comment('Storage:');
        $this->line('Default Disk: ' . $info['storage']['default_disk']);
        foreach ($info['storage']['disks'] as $name => $disk) {
            if (isset($disk['error'])) {
                $this->error("Disk '{$name}' ({$disk['driver']}): " . $disk['error']);
            } else {
                $status = $disk['status'] ?? 'Unknown';
                $extra = '';

                if ($disk['driver'] === 's3') {
                    $extra = isset($disk['bucket']) ? " (Bucket: {$disk['bucket']})" : '';
                } elseif ($disk['driver'] === 'local') {
                    $extra = isset($disk['free_space']) ? " (Free: {$disk['free_space']})" : '';
                }

                $this->line("Disk '{$name}' ({$disk['driver']}): {$status}{$extra}");
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
