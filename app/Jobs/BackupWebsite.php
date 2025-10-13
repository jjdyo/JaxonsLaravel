<?php

namespace App\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

class BackupWebsite implements ShouldQueue, \Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    /**
     * Default maximum runtime for a single backup download phase (in seconds).
     * When exceeded, we gracefully stop wget and proceed to packaging.
     */
    public const DEFAULT_MAX_RUNTIME_SECONDS = 24 * 60 * 60; // 24 hours

    /**
     * Target URL to back up.
     */
    protected string $url;

    /**
     * Optional storage disk (defaults to 'local').
     */
    protected string $disk;

    /**
     * ID of the user who dispatched the job (if available).
     */
    protected ?int $dispatchedBy = null;

    /**
     * Where the backup will be stored relative to storage_path('app').
     * Example: backups/example.com/2025-09-30_10-15-00
     */
    protected string $relativeBackupDir;

    /**
     * Create a new job instance.
     */
    public function __construct(string $url, ?int $dispatchedBy = null, ?string $disk = 'local')
    {
        $this->url = trim($url);
        $this->dispatchedBy = $dispatchedBy;
        $this->disk = $disk ?? 'local';
        // Ensure this job goes to the dedicated 'backups' queue
        $this->onQueue('backups');
    }

    /**
     * Prevent overlapping executions across workers for the same URL
     *
     * @return list<\Illuminate\Queue\Middleware\WithoutOverlapping>
     */
    public function middleware(): array
    {
        return [
            // Lock key is per-URL to allow parallel backups of different URLs
            (new WithoutOverlapping($this->overlapKey()))
                ->expireAfter(2 * 60 * 60), // prevent stale locks (2 hours max)
        ];
    }

    /**
     * Ensure only one queued instance exists for a given URL until processing starts
     */
    public function uniqueId(): string
    {
        return $this->overlapKey();
    }

    /**
     * Build a stable lock/unique key for the job based on the URL
     */
    protected function overlapKey(): string
    {
        // Normalize: trim and lowercase the URL string
        return 'backup-website:' . md5(strtolower(trim($this->url)));
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $startedAt = now();
        $jobStart = microtime(true);

        // Establish default logging context for the entire job
        $jobId = $this->job?->getJobId();
        $attempt = $this->attempts();
        logger()->withContext([
            'job' => 'BackupWebsite',
            'job_id' => $jobId,
            'attempt' => $attempt,
            'url' => $this->url,
        ]);

        if (empty($this->url)) {
            throw new \InvalidArgumentException('BackupWebsite: URL cannot be empty.');
        }

        // Resolve redirects to get the effective URL and extract site name (host)
        Log::info('BackupWebsite: Step 1 - Resolving effective URL');
        $resolveStarted = microtime(true);
        $effectiveUrl = $this->resolveEffectiveUrl($this->url);
        $resolveDurationMs = (int) round((microtime(true) - $resolveStarted) * 1000, 0, PHP_ROUND_HALF_UP);
        Log::info('BackupWebsite: Step 1 completed - Effective URL resolved', [
            'effective_url' => $effectiveUrl,
            'duration_ms' => $resolveDurationMs,
        ]);

        $host = parse_url($effectiveUrl, PHP_URL_HOST) ?: parse_url($this->url, PHP_URL_HOST);
        if (!$host) {
            throw new \InvalidArgumentException('BackupWebsite: Unable to determine host from URL: ' . $this->url);
        }

        $siteName = $this->sanitizeSiteName($host);
        $timestamp = $startedAt->format('Y-m-d_H-i-s');

        // Base storage directory for this backup
        $baseStoragePath = storage_path('app');
        $this->relativeBackupDir = 'backups' . DIRECTORY_SEPARATOR . $siteName . DIRECTORY_SEPARATOR . $timestamp;
        $backupDir = $baseStoragePath . DIRECTORY_SEPARATOR . $this->relativeBackupDir;
        // Use the backup directory as the wget destination; wget will create the site folder inside it.
        $downloadDir = $backupDir;
        $siteDir = $backupDir . DIRECTORY_SEPARATOR . $siteName;

        // Ensure base directory exists
        Log::info('BackupWebsite: Step 2 - Preparing directories', [
            'backup_dir' => $backupDir,
            'download_dir' => $downloadDir,
            'site_dir' => $siteDir,
        ]);
        $dirStart = microtime(true);
        if (!is_dir($downloadDir) && !mkdir($downloadDir, 0775, true) && !is_dir($downloadDir)) {
            throw new \RuntimeException('BackupWebsite: Failed to create backup directory: ' . $downloadDir);
        }
        Log::info('BackupWebsite: Step 2 completed - Directories ready', [
            'duration_ms' => (int) round((microtime(true) - $dirStart) * 1000, 0, PHP_ROUND_HALF_UP),
        ]);

        Log::info('BackupWebsite: Starting backup', [
            'url' => $this->url,
            'effective_url' => $effectiveUrl,
            'site' => $siteName,
            'backup_dir' => $backupDir,
        ]);

        // Verify required commands exist (Step 3)
        Log::info('BackupWebsite: Step 3 - Verifying required commands');
        $this->assertCommandAvailable('wget');
        $this->assertCommandAvailable('tar');
        Log::info('BackupWebsite: Step 3 completed - Commands verified');

        // Step 4: Mirror website via dedicated service
        /** @var \App\Services\BackupWebsite\WebsiteMirrorService $mirrorService */
        $mirrorService = app(\App\Services\BackupWebsite\WebsiteMirrorService::class);

        // Step 4: Download site using wget via service
        Log::info('BackupWebsite: Step 4 - Starting site download (wget)', [
            'working_dir' => $backupDir,
        ]);
        $mirrorResult = $mirrorService->mirror(
            url: $effectiveUrl,
            downloadDir: $downloadDir,
            workingDir: $backupDir,
            timeoutSeconds: self::DEFAULT_MAX_RUNTIME_SECONDS,
            allowedExitCodes: [0, 3, 4, 8],
            treatTimeoutAsSuccess: true,
        );
        Log::info('BackupWebsite: Step 4 completed - Site download finished', [
            'duration_ms' => $mirrorResult->durationMs,
        ]);

        // Create tar.gz archive and place it at backups/<site>/<site>.tar.gz (above the timestamp folder)
        $tarName = $siteName . '.tar.gz';
        $siteBaseDir = $baseStoragePath . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . $siteName;
        if (!is_dir($siteBaseDir) && !mkdir($siteBaseDir, 0775, true) && !is_dir($siteBaseDir)) {
            throw new \RuntimeException('BackupWebsite: Failed to ensure site base directory: ' . $siteBaseDir);
        }
        $tarFullPath = $siteBaseDir . DIRECTORY_SEPARATOR . $tarName;

        // If an archive already exists here, remove it to guarantee the latest is written
        if (file_exists($tarFullPath)) {
            Log::info('BackupWebsite: Existing archive found; removing to overwrite with latest', [
                'archive' => $tarFullPath,
            ]);
            if (!@unlink($tarFullPath)) {
                Log::warning('BackupWebsite: Failed to remove existing archive; tar will attempt to overwrite', [
                    'archive' => $tarFullPath,
                ]);
            }
        }

        // Use tar to create archive of the <site_name> directory
        // tar -czf "<tar>" -C "<backupDir>" "<site_name>"
        $tarArgs = [
            'tar',
            '-czf',
            $tarFullPath,
            '-C',
            $backupDir,
            $siteName,
        ];

        // Step 5: Archive downloaded site
        Log::info('BackupWebsite: Step 5 - Creating archive (tar.gz)', [
            'archive' => $tarFullPath,
        ]);
        $tarStart = microtime(true);
        $this->runProcess($tarArgs, $backupDir, 3600, [0]);
        Log::info('BackupWebsite: Step 5 completed - Archive created', [
            'archive' => $tarFullPath,
            'duration_ms' => (int) round((microtime(true) - $tarStart) * 1000, 0, PHP_ROUND_HALF_UP),
        ]);

        // Step 6: Archive is stored in the site root (above timestamp folder) for easy access
        Log::info('BackupWebsite: Step 6 - Archive location confirmed (site root)', [
            'archive' => $tarFullPath,
            'site_folder' => $siteDir,
        ]);

        Log::info('BackupWebsite: Backup completed', [
            'archive' => $tarFullPath,
            'folder' => $siteDir,
            'relative_path' => 'backups' . DIRECTORY_SEPARATOR . $siteName . DIRECTORY_SEPARATOR . $tarName,
            'total_duration_ms' => (int) round((microtime(true) - $jobStart) * 1000, 0, PHP_ROUND_HALF_UP),
        ]);
    }

    /**
     * Resolve the effective URL following redirects using cURL to mimic the shell script behavior.
     */
    protected function resolveEffectiveUrl(string $url): string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_NOBODY => true,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.6 Safari/605.1.15',
        ]);
        curl_exec($ch);
        $effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL) ?: $url;
        curl_close($ch);
        return $effectiveUrl;
    }

    /**
     * Sanitize site name for filesystem safety.
     */
    protected function sanitizeSiteName(string $host): string
    {
        $site = strtolower($host);
        $site = trim($site, ".- ");
        $site = preg_replace('/[^a-z0-9._-]+/i', '-', $site) ?? 'site';
        $site = trim($site, '-');
        return $site ?: 'site';
    }

    /**
     * Ensure a command is available on the system path.
     */
    protected function assertCommandAvailable(string $command): void
    {
        Log::info('BackupWebsite: Checking command availability', ['command' => $command]);
        // Cross-platform check: attempt to run "<command> --version"
        $proc = new Process([$command, '--version']);
        $proc->setTimeout(15);
        try {
            $proc->mustRun();
            $version = trim($proc->getOutput());
            Log::info('BackupWebsite: Command available', [
                'command' => $command,
                'version' => Str::limit($version, 200),
            ]);
        } catch (\Throwable $e) {
            Log::error('BackupWebsite: Command not available', [
                'command' => $command,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException(sprintf('Required command "%s" is not available on this system. Please install it.', $command));
        }
    }

    /**
     * Run a process and handle non-zero exit codes with optional allowances.
     * Also optionally treat a timeout as success (useful for long-running downloads capped by policy).
     *
     * @param list<string> $command
     * @param string $workingDir
     * @param int|null $timeoutSeconds
     * @param list<int> $allowedExitCodes Exit codes to be treated as success (defaults to [0, 8]).
     * @param bool $treatTimeoutAsSuccess When true, a timeout will be logged and treated as success.
     */
    protected function runProcess(array $command, string $workingDir, ?int $timeoutSeconds = null, array $allowedExitCodes = [0, 8], bool $treatTimeoutAsSuccess = false): void
    {
        $process = new Process($command, $workingDir);
        if ($timeoutSeconds !== null) {
            $process->setTimeout($timeoutSeconds);
        }

        $cmdStr = implode(' ', array_map(static function ($p) {
            // $command is a list<string>, so $p is string
            return preg_match('/\s/', (string) $p) ? '"' . (string) $p . '"' : (string) $p;
        }, $command));
        Log::info('BackupWebsite: Running process', [
            'command' => $cmdStr,
            'working_dir' => $workingDir,
            'timeout' => $timeoutSeconds,
            'allowed_exit_codes' => $allowedExitCodes,
            'treat_timeout_as_success' => $treatTimeoutAsSuccess,
        ]);
        $procStart = microtime(true);

        $timedOut = false;
        // Stream output to logs for visibility
        try {
            $process->run(function ($type, $buffer) {
                $lines = preg_split("/(\r\n|\r|\n)/", (string) $buffer, -1, PREG_SPLIT_NO_EMPTY) ?: [];
                foreach ($lines as $line) {
                    if ($type === Process::ERR) {
                        Log::warning('BackupWebsite: STDERR ' . $line);
                    } else {
                        Log::info('BackupWebsite: ' . $line);
                    }
                }
            });
        } catch (ProcessTimedOutException $e) {
            if ($treatTimeoutAsSuccess) {
                $timedOut = true;
                Log::warning('BackupWebsite: Process timed out but is treated as success', [
                    'command' => $cmdStr,
                    'timeout' => $timeoutSeconds,
                    'message' => $e->getMessage(),
                ]);
            } else {
                Log::error('BackupWebsite: Process timed out', [
                    'command' => $cmdStr,
                    'timeout' => $timeoutSeconds,
                    'message' => $e->getMessage(),
                ]);
                throw $e;
            }
        }

        $durationMs = (int) round((microtime(true) - $procStart) * 1000, 0, PHP_ROUND_HALF_UP);
        $exitCode = $timedOut ? null : $process->getExitCode();
        $exitText = $timedOut ? 'TIMEOUT' : $process->getExitCodeText();

        $treatAsSuccess = $timedOut || $process->isSuccessful();
        if (!$treatAsSuccess) {
            $treatAsSuccess = in_array((int) $exitCode, $allowedExitCodes, true);
        }

        if (!$treatAsSuccess) {
            Log::error('BackupWebsite: Process failed', [
                'command' => $cmdStr,
                'exit_code' => $exitCode,
                'exit_text' => $exitText,
                'duration_ms' => $durationMs,
                'error_output' => Str::limit($process->getErrorOutput(), 5000),
            ]);
            throw new ProcessFailedException($process);
        }

        if ($timedOut) {
            Log::warning('BackupWebsite: Process considered successful due to timeout policy', [
                'command' => $cmdStr,
                'timeout' => $timeoutSeconds,
                'duration_ms' => $durationMs,
            ]);
        } elseif ($process->isSuccessful()) {
            Log::info('BackupWebsite: Process completed', [
                'command' => $cmdStr,
                'exit_code' => $exitCode,
                'exit_text' => $exitText,
                'duration_ms' => $durationMs,
            ]);
        } else {
            // Non-zero but allowed exit code (e.g., Wget exit 8). Log as warning but continue.
            Log::warning('BackupWebsite: Process completed with allowed non-zero exit code', [
                'command' => $cmdStr,
                'exit_code' => $exitCode,
                'exit_text' => $exitText,
                'duration_ms' => $durationMs,
            ]);
        }
    }
    /**
     * Called by the queue worker if the job fails permanently.
     */
    public function failed(\Throwable $e): void
    {
        $jobId = $this->job?->getJobId();
        Log::error('BackupWebsite: Job failed', [
            'job_id' => $jobId,
            'url' => $this->url,
            'message' => $e->getMessage(),
            'exception' => get_class($e),
        ]);
    }
}
