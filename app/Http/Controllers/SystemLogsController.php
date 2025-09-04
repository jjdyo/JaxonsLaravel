<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class SystemLogsController extends Controller
{
    public function index(Request $request)
    {
        $base = storage_path('logs');

        // Discover channels & dates from dated files only: {channel}-YYYY-MM-DD.log
        $availableLogs = [];           // ['web' => ['2025-09-03','2025-09-04'], 'api' => [...]]
        $latestDates   = [];           // ['web' => '2025-09-04', 'api' => '2025-09-03']

        foreach (glob($base . '/*-*.log') as $path) {
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
            $latestDates[$ch]   = end($availableLogs[$ch]) ?: null;
        }

        $channels = array_keys($availableLogs);
        sort($channels);

        $selectedChannel = trim((string) $request->query('channel', ''));
        $selectedDate    = trim((string) $request->query('date', ''));

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
                $content = File::get($path) ?? '';
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
}
