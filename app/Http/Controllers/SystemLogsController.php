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

        return view('admin.system-logs.index', [
            'availableChannels' => $availableChannels
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
        $request->validate([
            'channel' => 'required|string|in:web,api',
            'page' => 'required|integer|min:1',
            'limit' => 'required|integer|min:10|max:100',
        ]);

        $channel = $request->channel;
        $page = $request->page;
        $limit = $request->limit;

        // Determine the log file path based on the channel
        $logPath = $this->getLogPath($channel);

        if (!File::exists($logPath)) {
            return response()->json([
                'logs' => [],
                'hasMore' => false,
                'error' => 'Log file not found'
            ]);
        }

        // Read and parse the log file
        $logs = $this->parseLogFile($logPath, $page, $limit);

        return response()->json([
            'logs' => $logs['entries'],
            'hasMore' => $logs['hasMore']
        ]);
    }

    /**
     * Get the path to the log file for a specific channel
     *
     * @param string $channel
     * @return string
     */
    private function getLogPath(string $channel): string
    {
        // All logs are stored in the same directory with a standard naming convention
        $basePath = storage_path('logs');
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
