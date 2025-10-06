<?php

namespace App\Services\BackupWebsite;

use App\DTOs\BackupWebsite\WebsiteMirrorResult;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

/**
 * Service responsible for executing wget to mirror a website into a local directory.
 *
 * Keeping this logic in a service simplifies the BackupWebsite job and makes the
 * mirroring step reusable and testable.
 */
class WebsiteMirrorService
{
    /**
     * Default desktop-like user agent for wget to avoid simplistic bot blocking.
     */
    public const DEFAULT_USER_AGENT = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.6 Safari/605.1.15';

    /**
     * Mirror a website using wget into the given download directory.
     *
     * @param string $url Effective URL to mirror.
     * @param string $downloadDir Directory where wget should place mirrored files (passed via -P).
     * @param string $workingDir Working directory for the process (generally same as $downloadDir parent).
     * @param int|null $timeoutSeconds Max duration to allow before treating as timeout (null = no limit).
     * @param list<int> $allowedExitCodes Additional exit codes to tolerate as success. Defaults include 0, 3, 8.
     * @param bool $treatTimeoutAsSuccess If true, a timeout is considered a successful stop.
     * @param string|null $userAgent Optional user agent override.
     * @return WebsiteMirrorResult
     */
    public function mirror(
        string $url,
        string $downloadDir,
        string $workingDir,
        ?int $timeoutSeconds = 86400,
        array $allowedExitCodes = [0, 3, 8],
        bool $treatTimeoutAsSuccess = true,
        ?string $userAgent = null,
    ): WebsiteMirrorResult {
        $ua = $userAgent ?: self::DEFAULT_USER_AGENT;

        $args = [
            'wget',
            '--mirror',
            '--convert-links',
            '--adjust-extension',
            '--page-requisites',
            '--no-parent',
            '--no-cache',
            '--restrict-file-names=ascii',
            '-e', 'robots=off',
            '--wait=1',
            '--limit-rate=100k',
            '--tries=3',
            '--timeout=30',
            '--user-agent=' . $ua,
            '-P', $downloadDir,
            $url,
        ];

        return $this->run($args, $workingDir, $timeoutSeconds, $allowedExitCodes, $treatTimeoutAsSuccess, $url, $downloadDir);
    }

    /**
     * Internal runner encapsulating process execution with logging and policies.
     *
     * @param array<int, string> $command
     * @param list<int> $allowedExitCodes
     */
    protected function run(
        array $command,
        string $workingDir,
        ?int $timeoutSeconds,
        array $allowedExitCodes,
        bool $treatTimeoutAsSuccess,
        string $url,
        string $downloadDir,
    ): WebsiteMirrorResult {
        $process = new Process($command, $workingDir);
        if ($timeoutSeconds !== null) {
            $process->setTimeout($timeoutSeconds);
        }

        $cmdStr = implode(' ', array_map(static function (string $p) {
            return preg_match('/\s/', $p) ? '"' . $p . '"' : $p;
        }, $command));

        Log::info('WebsiteMirrorService: Starting wget', [
            'command' => $cmdStr,
            'working_dir' => $workingDir,
            'timeout' => $timeoutSeconds,
            'allowed_exit_codes' => $allowedExitCodes,
            'treat_timeout_as_success' => $treatTimeoutAsSuccess,
            'url' => $url,
            'download_dir' => $downloadDir,
        ]);

        $stdout = '';
        $stderr = '';
        $start = microtime(true);
        $timedOut = false;

        try {
            $process->run(function ($type, $buffer) use (&$stdout, &$stderr) {
                $buffer = (string) $buffer;
                if ($type === Process::ERR) {
                    $stderr .= $buffer;
                    foreach (preg_split("/(\r\n|\r|\n)/", $buffer, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $line) {
                        Log::warning('WebsiteMirrorService: STDERR ' . $line);
                    }
                } else {
                    $stdout .= $buffer;
                    foreach (preg_split("/(\r\n|\r|\n)/", $buffer, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $line) {
                        Log::info('WebsiteMirrorService: ' . $line);
                    }
                }
            });
        } catch (ProcessTimedOutException $e) {
            if ($treatTimeoutAsSuccess) {
                $timedOut = true;
                Log::warning('WebsiteMirrorService: wget timed out but treated as success', [
                    'message' => $e->getMessage(),
                    'timeout' => $timeoutSeconds,
                ]);
            } else {
                Log::error('WebsiteMirrorService: wget timed out', [
                    'message' => $e->getMessage(),
                    'timeout' => $timeoutSeconds,
                ]);
                throw $e;
            }
        }

        $durationMs = (int) round((microtime(true) - $start) * 1000);
        $exitCode = $timedOut ? null : $process->getExitCode();
        $exitText = $timedOut ? 'TIMEOUT' : $process->getExitCodeText();

        $isSuccess = $timedOut || $process->isSuccessful() || in_array((int) $exitCode, $allowedExitCodes, true);

        if (!$isSuccess) {
            Log::error('WebsiteMirrorService: wget failed', [
                'exit_code' => $exitCode,
                'exit_text' => $exitText,
                'duration_ms' => $durationMs,
                'stderr_snippet' => Str::limit($process->getErrorOutput(), 1000),
            ]);
            throw new ProcessFailedException($process);
        }

        if ($timedOut) {
            Log::warning('WebsiteMirrorService: wget considered successful due to timeout policy', [
                'duration_ms' => $durationMs,
            ]);
        } elseif ($process->isSuccessful()) {
            Log::info('WebsiteMirrorService: wget completed', [
                'exit_code' => $exitCode,
                'exit_text' => $exitText,
                'duration_ms' => $durationMs,
            ]);
        } else {
            Log::warning('WebsiteMirrorService: wget completed with allowed non-zero exit code', [
                'exit_code' => $exitCode,
                'exit_text' => $exitText,
                'duration_ms' => $durationMs,
            ]);
        }

        return new WebsiteMirrorResult(
            url: $url,
            downloadDir: $downloadDir,
            workingDir: $workingDir,
            timedOut: $timedOut,
            exitCode: $exitCode,
            durationMs: $durationMs,
            stdoutSnippet: Str::limit($stdout, 2000),
            stderrSnippet: Str::limit($stderr, 2000),
        );
    }
}
