<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SystemLogsController extends Controller
{
    /**
     * Display the system logs page
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $availableChannels = ['web', 'api'];

        // Scan the logs directory for available log files
        $logFiles = File::files(storage_path('logs'));

        // Group log files by channel and extract dates
        $availableLogs = [];
        foreach ($logFiles as $file) {
            $filename = $file->getFilename();

            // Match files with pattern: channel-YYYY-MM-DD.log
            if (preg_match('/^(web|api)-(\d{4}-\d{2}-\d{2})\.log$/', $filename, $matches)) {
                $channel = $matches[1];
                $date = $matches[2];

                if (!isset($availableLogs[$channel])) {
                    $availableLogs[$channel] = [];
                }

                $availableLogs[$channel][] = $date;
            }
        }

        // Sort dates for each channel
        foreach ($availableLogs as $channel => $dates) {
            sort($availableLogs[$channel]);
        }

        return view('admin.system-logs.index', [
            'channels' => $availableChannels,
            'availableLogs' => $availableLogs
        ]);
    }

    /**
     * Fetch logs for a specific channel with pagination
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function fetchLogs(Request $request)
    {
        $channel = $request->string('channel')->toString();   // e.g. 'web' | 'api'
        $date    = $request->string('date')->toString();      // 'YYYY-MM-DD' or '' for latest

        // Resolve the filename for the requested channel/date.
        // Adjust these patterns to match your log naming:
        //   - latest: storage/logs/{channel}.log
        //   - dated:  storage/logs/{channel}-{YYYY-MM-DD}.log
        $base = storage_path('logs');
        $path = $date
            ? "{$base}/{$channel}-{$date}.log"
            : "{$base}/{$channel}.log";

        if (!is_file($path) || !is_readable($path)) {
            return response()->json([
                'content' => null,
                'error'   => "Log not found or unreadable: {$path}",
            ], 404);
        }

        // Read entire file. (If logs are huge, consider tail-ing or size limits later.)
        $content = file_get_contents($path) ?: '';

        return response()->json([
            'content' => $content,
            // keep these keys stable if you later reintroduce paging
            'hasMore' => false,
        ]);
    }

    /**
     * Get the path to the log file for a specific channel and date
     *
     * @param string $channel
     * @param string|null $date Date in Y-m-d format
     * @return string
     */
    private function getLogPath(string $channel, ?string $date = null): string
    {
        // All logs are stored in the same directory with a standard naming convention
        $basePath = storage_path('logs');

        // If date is provided, use the dated log file format
        if ($date) {
            return $basePath . "/{$channel}-{$date}.log";
        }

        // Check if there are any dated log files for this channel
        $pattern = $basePath . "/{$channel}-*.log";
        $datedLogFiles = glob($pattern);

        // If dated log files exist, return the most recent one
        if (!empty($datedLogFiles)) {
            // Sort files by name (which includes the date) in descending order
            rsort($datedLogFiles);
            return $datedLogFiles[0];
        }

        // Fall back to the default log file if no dated logs exist
        return $basePath . "/{$channel}.log";
    }

    /**
     * Parse the log file and extract entries with pagination
     *
     * @param string $logPath
     * @param int $page
     * @param int $limit
     * @return array{entries: array<int, array<string, string>>, hasMore: bool}
     */
    private function parseLogFile(string $logPath, int $page, int $limit): array
    {
        // Read the file content
        $content = File::get($logPath);

        // Split the content by log entries (each starting with [YYYY-MM-DD HH:MM:SS])
        preg_match_all('/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\].*?(?=\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\]|$)/s', $content, $matches);

        $allEntries = $matches[0];

        // Reverse the array to get newest logs first
        $allEntries = array_reverse($allEntries);

        // Calculate pagination
        $offset = ($page - 1) * $limit;
        $entries = array_slice($allEntries, $offset, $limit);

        // Format the entries
        $formattedEntries = [];
        foreach ($entries as $entry) {
            // Extract timestamp, level, and message
            if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] (\w+)\.(\w+): (.*)$/s', $entry, $parts)) {
                $formattedEntries[] = [
                    'timestamp' => $parts[1],
                    'level' => $parts[3],
                    'message' => $parts[4],
                ];
            } else {
                // Fallback if the regex doesn't match
                $formattedEntries[] = [
                    'raw' => trim($entry)
                ];
            }
        }

        return [
            'entries' => $formattedEntries,
            'hasMore' => count($allEntries) > ($offset + $limit)
        ];
    }
}
