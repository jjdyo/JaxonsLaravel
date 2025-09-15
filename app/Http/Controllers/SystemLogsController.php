<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class SystemLogsController extends Controller
{
    public function index(Request $request): View
    {
        $base = storage_path('logs');

        $availableLogs = [];           // ['web' => ['2025-09-03','2025-09-04'], 'api' => [...]]
        $latestDates   = [];           // ['web' => '2025-09-04', 'api' => '2025-09-03']

        $globResult = glob($base . '/*-*.log');
        if ($globResult === false) {
            $globResult = [];
        }

        foreach ($globResult as $path) {
            $file = basename($path);   // e.g. web-2025-09-03.log
            if (!preg_match('/^(.*)-(\d{4}-\d{2}-\d{2})\.log$/', $file, $m)) {
                continue;
            }
            [$full, $channel, $date] = $m;
            $availableLogs[$channel] = $availableLogs[$channel] ?? [];
            $availableLogs[$channel][] = $date;
        }

        foreach ($availableLogs as $ch => $dates) {
            sort($dates);
            $availableLogs[$ch] = array_values(array_unique($dates));
            // Since we know the array is non-empty at this point, we can directly get the last element
            $latestDates[$ch] = end($availableLogs[$ch]);
        }

        $channels = array_keys($availableLogs);
        sort($channels);

        $channelParam = $request->get('channel');
        $dateParam = $request->get('date');

        $selectedChannel = is_string($channelParam) ? trim($channelParam) : '';
        $selectedDate = is_string($dateParam) ? trim($dateParam) : '';

        if ($selectedChannel === '' || !in_array($selectedChannel, $channels, true)) {
            return view('admin.system-logs.index', [
                'channels'        => $channels,
                'availableLogs'   => $availableLogs,
                'latestDates'     => $latestDates,
                'selectedChannel' => '',
                'selectedDate'    => '',
                'content'         => '',
            ]);
        }

        $datesForChannel = $availableLogs[$selectedChannel] ?? [];
        $latestForChan   = $latestDates[$selectedChannel] ?? null;

        if ($selectedDate === '') {
            $targetDate = $latestForChan; // may be null
        } elseif (in_array($selectedDate, $datesForChannel, true)) {
            $targetDate = $selectedDate;
        } else {
            $targetDate = null;
        }

        $content = '';
        if ($targetDate) {
            $path = sprintf('%s/%s-%s.log', $base, $selectedChannel, $targetDate);
            if (is_file($path) && is_readable($path)) {
                // Read at most the last 128 KiB to prevent memory issues with very large files
                $maxBytes = 131072; // 128 KiB
                $size = filesize($path) ?: 0;
                $start = max(0, $size - $maxBytes);
                $fh = fopen($path, 'rb');
                if ($fh !== false) {
                    fseek($fh, $start);
                    $content = fread($fh, $maxBytes) ?: '';
                    fclose($fh);
                } else {
                    $content = '';
                }
            }
        }

        return view('admin.system-logs.index', [
            'channels'        => $channels,
            'availableLogs'   => $availableLogs,
            'latestDates'     => $latestDates,
            'selectedChannel' => $selectedChannel,
            'selectedDate'    => $selectedDate,  // '' means "Latest (...)"
            'content'         => $content,
        ]);
    }

    /**
     * Fetch a chunk of a log file without loading the entire file into memory.
     *
     * Query params:
     * - channel: required, e.g. "web", "api"
     * - date: required, format YYYY-MM-DD
     * - offset: optional int >= 0, byte offset to read from
     * - limit: optional int, number of bytes to read (default 65536, max 262144)
     * - tail_lines: optional int > 0. If provided and offset is not provided, returns last N lines (best-effort)
     */
    public function fetchLogs(Request $request): JsonResponse
    {
        $base = storage_path('logs');

        // Discover available channels and dates (same as index)
        $available = [];
        $globResult = glob($base . '/*-*.log') ?: [];
        foreach ($globResult as $path) {
            $file = basename($path);
            if (!preg_match('/^(.*)-(\d{4}-\d{2}-\d{2})\.log$/', $file, $m)) {
                continue;
            }
            [$full, $channel, $date] = $m;
            $available[$channel] = $available[$channel] ?? [];
            $available[$channel][$date] = true;
        }

        $channelParam = $request->get('channel');
        $dateParam = $request->get('date');

        $channel = is_string($channelParam) ? trim($channelParam) : '';
        $date = is_string($dateParam) ? trim($dateParam) : '';

        if ($channel === '' || !array_key_exists($channel, $available)) {
            return response()->json(['error' => 'Invalid or missing channel'], 422);
        }
        if ($date === '' || !isset($available[$channel][$date])) {
            return response()->json(['error' => 'Invalid or missing date'], 422);
        }

        $path = sprintf('%s/%s-%s.log', $base, $channel, $date);
        if (!is_file($path) || !is_readable($path)) {
            return response()->json(['error' => 'Log file not found'], 404);
        }

        $fileSize = filesize($path);
        if ($fileSize === false) {
            return response()->json(['error' => 'Unable to determine file size'], 500);
        }

        $offset = $request->get('offset');
        $limitParam = $request->get('limit');
        $limit = is_numeric($limitParam) ? (int) $limitParam : 65536;
        $limit = max(1, min(262144, $limit)); // clamp to [1, 256 KiB]
        $tailLinesParam = $request->get('tail_lines');
        $tailLines = is_numeric($tailLinesParam) ? (int) $tailLinesParam : 0;

        $start = 0;
        $chunk = '';

        $fh = fopen($path, 'rb');
        if ($fh === false) {
            return response()->json(['error' => 'Unable to open log file'], 500);
        }

        try {
            if (is_numeric($offset)) {
                $start = max(0, (int) $offset);
                if ($start > $fileSize) {
                    $start = $fileSize;
                }
                fseek($fh, $start);
                $chunk = fread($fh, $limit) ?: '';
            } else {
                // No offset: read from end (tail)
                $start = max(0, $fileSize - $limit);
                fseek($fh, $start);
                $chunk = fread($fh, $limit) ?: '';
                if ($tailLines > 0 && $chunk !== '') {
                    $lines = preg_split("/\r?\n/", $chunk);
                    if (is_array($lines)) {
                        $lines = array_filter($lines, static fn($l) => $l !== '');
                        $last = array_slice($lines, -$tailLines);
                        $chunk = implode(PHP_EOL, $last) . (count($last) > 0 ? PHP_EOL : '');
                        // Adjust start to reflect that we conceptually tailed from end
                        $start = $fileSize; // consumer should treat this as EOF (no next read implied)
                    }
                }
            }
        } finally {
            fclose($fh);
        }

        $readBytes = strlen($chunk);
        $nextOffset = (is_numeric($offset) ? (int) $offset : $start) + $readBytes;
        $eof = $nextOffset >= $fileSize;

        return response()->json([
            'channel'     => $channel,
            'date'        => $date,
            'file_size'   => $fileSize,
            'offset'      => is_numeric($offset) ? (int) $offset : null,
            'start'       => $start,
            'limit'       => $limit,
            'bytes'       => $readBytes,
            'next_offset' => $eof ? null : $nextOffset,
            'eof'         => $eof,
            'chunk'       => $chunk,
        ]);
    }
}
