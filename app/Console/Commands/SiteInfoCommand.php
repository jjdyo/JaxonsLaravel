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
    protected $signature = 'site:info';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display detailed system, app, database, cache, queue, and filesystem information';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Gathering site information...' . PHP_EOL);

        $info = [
            'app'       => $this->getAppInfo(),
            'system'    => $this->getSystemInfo(),
            'database'  => $this->getDatabaseInfo(),
            'cache'     => $this->getCacheInfo(),
            'queue'     => $this->getQueueInfo(),
            'filesystem'=> $this->getFilesystemInfo(),
            'memory'    => $this->getMemoryInfo(),
        ];

        // Output sections
        $this->outputAppInfo($info['app']);
        $this->outputSystemInfo($info['system']);
        $this->outputDatabaseInfo($info['database']);
        $this->outputCacheInfo($info['cache']);
        $this->outputQueueInfo($info['queue']);
        $this->outputFilesystemInfo($info['filesystem']);
        $this->outputMemoryInfo($info['memory']);

        // Optional JSON dump (comment in/out as desired)
        // $json = json_encode($info, JSON_PRETTY_PRINT);
        // $this->line(PHP_EOL . $json);

        return self::SUCCESS;
    }

    /**
     * Get application information
     *
     * @return array<string, mixed>
     */
    private function getAppInfo(): array
    {
        return [
            'name'           => config('app.name'),
            'env'            => config('app.env'),
            'debug'          => config('app.debug') ? 'true' : 'false',
            'url'            => config('app.url'),
            'api_url'            => config('app.api_url'),
            'locale'         => config('app.locale'),
            'fallback_locale'=> config('app.fallback_locale'),
            'timezone'       => config('app.timezone'),
            'key_set'        => config('app.key') ? 'yes' : 'no',
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

            $defaultConnection = config('database.default');
            $defaultConnectionStr = is_string($defaultConnection) ? $defaultConnection : '';

            $databaseConnections = config('database.connections');
            $connections = is_array($databaseConnections) ? $databaseConnections : [];
            $currentConnection = isset($connections[$defaultConnectionStr]) && is_array($connections[$defaultConnectionStr])
                ? $connections[$defaultConnectionStr]
                : [];

            $driver = $currentConnection['driver'] ?? 'unknown';

            $info = [
                'default_connection' => $defaultConnectionStr,
                'driver'             => $driver,
                'database'           => $currentConnection['database'] ?? null,
                'host'               => $currentConnection['host'] ?? null,
                'port'               => $currentConnection['port'] ?? null,
                'prefix'             => $currentConnection['prefix'] ?? null,
                'server_version'     => $pdo->getAttribute(\PDO::ATTR_SERVER_VERSION),
                'client_version'     => $pdo->getAttribute(\PDO::ATTR_CLIENT_VERSION),
            ];

            $connection->getDatabaseName();

            $info['connected'] = true;
            $info['error'] = null;

            return $info;
        } catch (\Throwable $e) {
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
            'default_store' => config('cache.default'),
            'stores'        => [],
            'working'       => false,
            'error'         => null,
            'redis'         => [
                'configured' => false,
                'available'  => false,
                'server'     => [],
                'memory'     => [],
            ],
        ];

        try {
            $testKey = 'site_info_test_' . uniqid();
            $testValue = 'test_value';

            Cache::put($testKey, $testValue, 5);
            $value = Cache::get($testKey);
            Cache::forget($testKey);

            $info['working'] = $testValue === $value;
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

                $serverInfoArr = is_array($serverInfo) ? $serverInfo : [];
                $memoryInfoArr = is_array($memoryInfo) ? $memoryInfo : [];

                $info['redis'] = [
                    'configured' => true,
                    'available'  => true,
                    'server'     => $serverInfoArr,
                    'memory'     => $memoryInfoArr,
                ];
            } catch (\Throwable $e) {
                $info['redis'] = [
                    'configured' => true,
                    'available'  => false,
                    'error'      => $e->getMessage(),
                ];
            }
        }

        return $info;
    }

    /**
     * Get filesystem information
     *
     * @return array<string, mixed>
     */
    private function getFilesystemInfo(): array
    {
        $info = [
            'default' => config('filesystems.default'),
            'disks' => [],
        ];

        $disksConfig = config('filesystems.disks');
        $disks = is_array($disksConfig) ? $disksConfig : [];

        foreach ($disks as $name => $config) {
            try {
                if (!is_array($config)) {
                    $config = [];
                }

                $diskInfo = [
                    'driver' => $config['driver'] ?? 'unknown',
                    'root'   => $config['root']   ?? 'N/A',
                ];

                $driver = $config['driver'] ?? null;

                if ($driver === 's3') {
                    $diskInfo['bucket'] = $config['bucket'] ?? null;
                    $diskInfo['region'] = $config['region'] ?? null;

                    $adapterAvailable = class_exists(\League\Flysystem\AwsS3V3\AwsS3V3Adapter::class)
                        || class_exists(\League\Flysystem\AwsS3V3\PortableVisibilityConverter::class);

                    $diskInfo['status'] = $adapterAvailable
                        ? 'Configured (adapter available)'
                        : 'Configured (adapter missing: league/flysystem-aws-s3-v3)';

                } else {
                    // Other drivers
                    $diskName = is_string($name) ? $name : '';
                    if ($driver === 'local') {
                        $path = $config['root'] ?? '';
                        $pathString = is_string($path) ? $path : '';
                        if ($pathString !== '' && is_dir($pathString)) {
                            $freeSpace  = disk_free_space($pathString);
                            $totalSpace = disk_total_space($pathString);
                            $diskInfo['free_space']  = $freeSpace !== false ? (int)$freeSpace : 0;
                            $diskInfo['total_space'] = $totalSpace !== false ? (int)$totalSpace : 0;
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
                    'error'  => $e->getMessage(),
                    'driver' => (is_array($config) && array_key_exists('driver', $config)) ? $this->toStringSafe($config['driver']) : 'Unknown',
                ];
            }
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
        $memoryLimitStr   = $this->iniGetString('memory_limit', 'Unknown');
        $memoryLimitBytes = $this->parseMemoryLimit($memoryLimitStr);

        return [
            'current_usage'      => $this->formatBytes(memory_get_usage(true)),
            'peak_usage'         => $this->formatBytes(memory_get_peak_usage(true)),
            'memory_limit'       => $memoryLimitStr,
            'memory_limit_bytes' => $memoryLimitBytes,
        ];
    }

    /**
     * Parse memory limit shorthand (e.g. 128M, 1G)
     */
    private function parseMemoryLimit(string $val): int
    {
        $val = trim($val);
        if ($val === '' || $val === '-1') {
            return -1;
        }
        $last = strtolower(substr($val, -1));
        $num  = (int) $val;

        switch ($last) {
            case 'g': return $num * 1024 * 1024 * 1024;
            case 'm': return $num * 1024 * 1024;
            case 'k': return $num * 1024;
            default:  return (int) $val;
        }
    }

    /**
     * Get system information
     *
     * @return array<string, mixed>
     */
    private function getSystemInfo(): array
    {
        $maxExecutionTimeStr = $this->iniGetString('max_execution_time', '30');
        $uploadMaxFilesize   = $this->iniGetString('upload_max_filesize', 'Unknown');
        $postMaxSize         = $this->iniGetString('post_max_size', 'Unknown');
        $opcacheEnableRaw    = ini_get('opcache.enable');
        $opcacheEnabled      = $opcacheEnableRaw !== false ? $opcacheEnableRaw === '1' : false;

        return [
            'operating_system'   => php_uname('s'),
            'php_sapi'           => php_sapi_name(),
            'max_execution_time' => $maxExecutionTimeStr . 's',
            'upload_max_filesize'=> $uploadMaxFilesize,
            'post_max_size'      => $postMaxSize,
            'loaded_extensions'  => count(get_loaded_extensions()),
            'opcache_enabled'    => $opcacheEnabled,
        ];
    }

    /**
     * Get queue information
     *
     * @return array<string, mixed>
     */
    private function getQueueInfo(): array
    {
        $info = [
            'default'  => config('queue.default'),
            'failed'   => 0,
            'driver'   => null,
            'conn'     => null,
            'working'  => null,
            'error'    => null,
        ];

        try {
            $defaultRaw = config('queue.default');
            $default = is_string($defaultRaw) ? $defaultRaw : 'sync';
            $info['driver'] = config('queue.connections.' . $default . '.driver');
            $info['conn']   = config('queue.connections.' . $default);

            $info['working'] = $info['driver'] ? true : false;
        } catch (\Throwable $e) {
            $info['working'] = false;
            $info['error']   = $e->getMessage();
        }

        return $info;
    }

    /**
     * Output helpers
     * @param array<string, mixed> $appInfo
     *
     */
    private function outputAppInfo(array $appInfo): void
    {
        $this->section('Application');
        $this->table(['Key', 'Value'], [
            ['Name', $appInfo['name']],
            ['Environment', $appInfo['env']],
            ['Debug', $appInfo['debug']],
            ['URL', $appInfo['url']],
            ['API URL', $appInfo['api_url']],
            ['Locale', $appInfo['locale']],
            ['Fallback Locale', $appInfo['fallback_locale']],
            ['Timezone', $appInfo['timezone']],
            ['App Key Set', $appInfo['key_set']],
        ]);
    }
    /**
     * @param array<string, mixed> $sysInfo
     */
    private function outputSystemInfo(array $sysInfo): void
    {
        $this->section('System');
        $this->table(['Key', 'Value'], [
            ['Operating System', $sysInfo['operating_system'] ?? 'Unknown'],
            ['SAPI', $sysInfo['php_sapi'] ?? 'Unknown'],
            ['Max Execution Time', $sysInfo['max_execution_time'] ?? 'Unknown'],
            ['Upload Max Filesize', $sysInfo['upload_max_filesize'] ?? 'Unknown'],
            ['Post Max Size', $sysInfo['post_max_size'] ?? 'Unknown'],
            ['Loaded Extensions', $sysInfo['loaded_extensions'] ?? 'Unknown'],
            ['OPcache', isset($sysInfo['opcache_enabled']) && $sysInfo['opcache_enabled'] ? 'Enabled' : 'Disabled'],
        ]);
    }
    /**
     * @param array<string, mixed> $dbInfo
     */
    private function outputDatabaseInfo(array $dbInfo): void
    {
        $this->section('Database');
        if (($dbInfo['connected'] ?? false) === true) {
            $this->table(['Key', 'Value'], [
                ['Default Connection', $dbInfo['default_connection'] ?? 'Unknown'],
                ['Driver', $dbInfo['driver'] ?? 'Unknown'],
                ['Database', $dbInfo['database'] ?? 'Unknown'],
                ['Host', $dbInfo['host'] ?? 'Unknown'],
                ['Port', $dbInfo['port'] ?? 'Unknown'],
                ['Prefix', $dbInfo['prefix'] ?? ''],
                ['Server Version', $dbInfo['server_version'] ?? 'Unknown'],
                ['Client Version', $dbInfo['client_version'] ?? 'Unknown'],
            ]);
        } else {
            $this->error('Database connection failed: ' . $this->toStringSafe($dbInfo['error'] ?? 'Unknown'));
        }
    }
    /**
     * @param array<string, mixed> $cacheInfo
     */
    private function outputCacheInfo(array $cacheInfo): void
    {
        $this->section('Cache');
        $this->table(['Key', 'Value'], [
            ['Default Store', $cacheInfo['default_store'] ?? 'Unknown'],
            ['Working', ($cacheInfo['working'] ?? false) ? 'Yes' : 'No'],
            ['Error', $cacheInfo['error'] ?? 'None'],
        ]);
        $redis = $cacheInfo['redis'] ?? null;
        if (is_array($redis) && (($redis['configured'] ?? false) || ($redis['available'] ?? false))) {
            $this->line('Redis:');
            $this->table(['Key', 'Value'], [
                ['Configured', ($redis['configured'] ?? false) ? 'Yes' : 'No'],
                ['Available', ($redis['available'] ?? false) ? 'Yes' : 'No'],
            ]);
        }
    }
    /**
     * @param array<string, mixed> $queueInfo
     */
    private function outputQueueInfo(array $queueInfo): void
    {
        $this->section('Queue');
        $this->table(['Key', 'Value'], [
            ['Default', $queueInfo['default'] ?? 'Unknown'],
            ['Driver', $queueInfo['driver'] ?? 'Unknown'],
            ['Working', ($queueInfo['working'] ?? false) ? 'Yes' : 'No'],
            ['Error', $queueInfo['error'] ?? 'None'],
        ]);
    }
    /**
     * @param array<string, mixed> $fsInfo
     */
    private function outputFilesystemInfo(array $fsInfo): void
    {
        $this->section('Filesystem');

        $rows = [
            ['Default', $fsInfo['default'] ?? 'Unknown'],
        ];

        $this->table(['Key', 'Value'], $rows);

        if (isset($fsInfo['disks']) && is_array($fsInfo['disks'])) {
            foreach ($fsInfo['disks'] as $name => $disk) {
                $this->line(PHP_EOL . "Disk: " . (is_string($name) ? $name : (string)$name));
                if (is_array($disk) && isset($disk['error'])) {
                    $diskError  = $disk['error'];
                    $diskDriver = $disk['driver'] ?? 'Unknown';
                    $this->error("Disk '" . $name . "' (" . $this->toStringSafe($diskDriver) . '): ' . $this->toStringSafe($diskError));
                } elseif (is_array($disk)) {
                    $status    = $disk['status'] ?? 'Unknown';
                    $statusStr = is_string($status) ? $status : $this->toStringSafe($status);
                    $extra     = '';

                    if (isset($disk['driver'])) {
                        $driver    = $disk['driver'];
                        $driverStr = is_string($driver) ? $driver : $this->toStringSafe($driver);

                        if ($driverStr === 's3') {
                            $bucket    = $disk['bucket'] ?? '';
                            $bucketStr = is_string($bucket) ? $bucket : $this->toStringSafe($bucket);
                            $extra     = isset($disk['bucket']) ? " (Bucket: " . $bucketStr . ")" : '';
                        } elseif ($driverStr === 'local') {
                            if (isset($disk['free_space'], $disk['total_space']) && is_int($disk['free_space']) && is_int($disk['total_space'])) {
                                $extra = " (Free: " . $this->formatBytes($disk['free_space']) . " / Total: " . $this->formatBytes($disk['total_space']) . ")";
                            }
                        }
                    }

                    $this->info("Status: " . $statusStr . $extra);
                } else {
                    $this->warn('Unknown disk configuration format');
                }
            }
        }
    }
    /**
     * @param array<string, mixed> $memInfo
     */
    private function outputMemoryInfo(array $memInfo): void
    {
        $this->section('Memory');
        $this->table(['Key', 'Value'], [
            ['Current Usage', $memInfo['current_usage'] ?? 'Unknown'],
            ['Peak Usage', $memInfo['peak_usage'] ?? 'Unknown'],
            ['Memory Limit', $memInfo['memory_limit'] ?? 'Unknown'],
            ['Memory Limit (bytes)', $memInfo['memory_limit_bytes'] ?? 'Unknown'],
        ]);
    }

    /**
     * Helpers
     */
    private function section(string $title): void
    {
        $this->line(PHP_EOL . '=== ' . $title . ' ===');
    }

    private function isRedisConfigured(): bool
    {
        $default = config('database.redis.client');
        return is_string($default) && $default !== '';
    }

    private function isRedisAvailable(): bool
    {
        try {
            Redis::connection()->ping();
            return true;
        } catch (\Throwable $e) {
            return false;
        }
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

        if ($bytes < 1) {
            return '0 B';
        }

        $power = (int) floor(log($bytes, 1024));
        $power = min($power, count($units) - 1);
        $bytes /=
            (1024 ** $power);

        return number_format($bytes, 2) . ' ' . $units[$power];
    }

    /**
     * Return an INI value as a string with a fallback (avoids string|false).
     */
    private function iniGetString(string $key, string $fallback = 'Unknown'): string
    {
        $val = ini_get($key);
        return $val !== false ? $val : $fallback;
    }

    /**
     * Safely stringify “mixed” for logging/output.
     * @param mixed $v
     */
    private function toStringSafe($v): string
    {
        if (is_string($v)) {
            return $v;
        }
        if (is_scalar($v)) {
            return (string) $v;
        }
        if (is_object($v) && method_exists($v, '__toString')) {
            return (string) $v;
        }
        $json = json_encode($v, JSON_UNESCAPED_SLASHES);
        return $json !== false ? $json : gettype($v);
    }
}
