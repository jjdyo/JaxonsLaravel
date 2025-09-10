<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\View\View;

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
                $content = File::get($path) ?: '';
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
