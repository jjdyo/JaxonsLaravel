<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SystemLogsController extends Controller
{
    public function index(Request $request)
    {
        // 1) Define which channels you want to expose
        //    (or discover them by scanning storage/logs)
        $channels = ['web', 'api', 'etc'];

        // 2) Build a map of available dates for each channel
        //    Matches files like: storage/logs/web.log and web-YYYY-MM-DD.log
        $base = storage_path('logs');
        $availableLogs = [];
        foreach ($channels as $ch) {
            $pattern = sprintf('%s/%s-*.log', $base, $ch);
            $dates = collect(glob($pattern))
                ->map(function ($path) use ($ch) {
                    // Extract YYYY-MM-DD from "{channel}-YYYY-MM-DD.log"
                    $file = basename($path);
                    $maybe = Str::after($file, $ch.'-');
                    return Str::replaceLast('.log', '', $maybe);
                })
                ->filter(fn ($d) => preg_match('/^\d{4}-\d{2}-\d{2}$/', $d))
                ->sort()               // oldest -> newest
                ->values()
                ->all();

            $availableLogs[$ch] = $dates;
        }

        // 3) Figure out what the user selected (or default)
        $selectedChannel = $request->query('channel', $channels[0] ?? 'web');
        if (!in_array($selectedChannel, $channels, true)) {
            $selectedChannel = $channels[0] ?? 'web';
        }

        $selectedDate = $request->query('date', ''); // '' means "Latest"

        // 4) Resolve the file path (latest vs dated)
        $latestPath = sprintf('%s/%s.log', $base, $selectedChannel);
        $datedPath  = $selectedDate
            ? sprintf('%s/%s-%s.log', $base, $selectedChannel, $selectedDate)
            : null;

        $path = $datedPath && is_file($datedPath) ? $datedPath : $latestPath;

        // 5) Load the content (or a friendly error)
        $content = '';
        if (is_file($path) && is_readable($path)) {
            $content = File::get($path) ?? '';
        } else {
            $content = "Log not found or unreadable:\n{$path}";
        }

        // 6) Pass everything to the view
        return view('admin.system-logs.index', [
            'channels'        => $channels,
            'availableLogs'   => $availableLogs,  // ['web' => ['2025-09-03', ...], ...]
            'selectedChannel' => $selectedChannel,
            'selectedDate'    => $selectedDate,
            'content'         => $content,        // raw file text
        ]);
    }
}
