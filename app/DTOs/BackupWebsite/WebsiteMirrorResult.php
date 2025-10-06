<?php

namespace App\DTOs\BackupWebsite;

/**
 * DTO representing the result of a website mirroring (wget) operation.
 */
class WebsiteMirrorResult
{
    public function __construct(
        public readonly string $url,
        public readonly string $downloadDir,
        public readonly string $workingDir,
        public readonly bool $timedOut,
        public readonly int|null $exitCode,
        public readonly int $durationMs,
        public readonly string $stdoutSnippet,
        public readonly string $stderrSnippet,
    ) {
    }
}
