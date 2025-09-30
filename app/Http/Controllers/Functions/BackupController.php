<?php

namespace App\Http\Controllers\Functions;

use App\Http\Controllers\Controller;
use App\Jobs\BackupWebsite;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupController extends Controller
{
    /**
     * Show the backup website tool page with current backups and pending jobs.
     *
     * @return View The backup tool view
     */
    public function show(): View
    {
        $backups = $this->gatherBackups();
        $pendingJobs = $this->getPendingBackupJobs();

        /** @var View $view */
        $view = view('functions.backup', [
            'backups' => $backups,
            'pendingJobs' => $pendingJobs,
        ]);
        return $view;
    }

    /**
     * Dispatch the BackupWebsite job for a given URL.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function dispatch(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'url' => ['required', 'url'],
        ]);

        try {
            /** @var int|null $userId */
            $userId = auth()->id();
            BackupWebsite::dispatch($validated['url'], $userId);
        } catch (\Throwable $e) {
            report($e);
            return back()->withErrors(['url' => 'Failed to dispatch backup job: ' . $e->getMessage()])->withInput();
        }

        return back()->with('success', 'Backup job dispatched for ' . $validated['url']);
    }


    /**
     * Download latest archive for the given site.
     */
    public function download(string $site): BinaryFileResponse
    {
        $safe = strtolower(trim($site));
        if (!preg_match('/^[a-z0-9._-]+$/i', $safe)) {
            abort(400, 'Invalid site name');
        }

        $path = storage_path('app' . DIRECTORY_SEPARATOR . 'backups' . DIRECTORY_SEPARATOR . $safe . DIRECTORY_SEPARATOR . $safe . '.tar.gz');
        if (!file_exists($path)) {
            abort(404, 'Archive not found');
        }

        return response()->download($path, $safe . '.tar.gz');
    }

    /**
     * Gather backups information from storage/app/backups structure.
     * @return array<int, array<string, mixed>>
     */
    private function gatherBackups(): array
    {
        $base = storage_path('app' . DIRECTORY_SEPARATOR . 'backups');
        $rows = [];
        if (!is_dir($base)) {
            return $rows;
        }

        $sites = array_values(array_filter(scandir($base) ?: [], function ($d) use ($base) {
            return !in_array($d, ['.', '..'], true) && is_dir($base . DIRECTORY_SEPARATOR . $d);
        }));

        foreach ($sites as $site) {
            $siteDir = $base . DIRECTORY_SEPARATOR . $site;
            $archive = $siteDir . DIRECTORY_SEPARATOR . $site . '.tar.gz';
            $archiveExists = is_file($archive);
            $archiveSize = $archiveExists ? filesize($archive) : null;
            $archiveMtime = $archiveExists ? filemtime($archive) : null;

            // Find timestamped subdirectories
            $timestamps = array_values(array_filter(scandir($siteDir) ?: [], function ($d) use ($siteDir) {
                return !in_array($d, ['.', '..'], true) && is_dir($siteDir . DIRECTORY_SEPARATOR . $d);
            }));
            // Sort newest first
            usort($timestamps, static function ($a, $b) use ($siteDir) {
                return filemtime($siteDir . DIRECTORY_SEPARATOR . $b) <=> filemtime($siteDir . DIRECTORY_SEPARATOR . $a);
            });
            $latestTimestamp = $timestamps[0] ?? null;

            $rows[] = [
                'site' => $site,
                'archive_exists' => $archiveExists,
                'archive_path' => $archive,
                'archive_size' => $archiveSize,
                'archive_mtime' => $archiveMtime,
                'latest_timestamp' => $latestTimestamp,
                'count' => count($timestamps),
            ];
        }

        // Sort sites alphabetically
        usort($rows, static fn($a, $b) => strcmp($a['site'], $b['site']));
        return $rows;
    }

    /**
     * Fetch pending jobs from the jobs table that are BackupWebsite jobs.
     * @return array<int, array<string, mixed>>
     */
    private function getPendingBackupJobs(): array
    {
        try {
            $jobs = DB::table('jobs')
                ->select(['id', 'queue', 'payload', 'available_at', 'created_at'])
                ->where('queue', 'backups')
                ->orderByDesc('id')
                ->get();
        } catch (\Throwable $e) {
            // jobs table may not exist or different driver; return empty gracefully
            return [];
        }

        $rows = [];
        $userIds = [];
        foreach ($jobs as $job) {
            $payload = (string) ($job->payload ?? '');
            if ($payload === '') {
                continue;
            }

            // Basic JSON decode of payload
            $data = json_decode($payload, true);
            $displayName = null;
            $command = null;
            if (is_array($data)) {
                $displayName = (isset($data['displayName']) && is_string($data['displayName'])) ? $data['displayName'] : null;
                if (isset($data['data']['command']) && is_string($data['data']['command'])) {
                    $command = $data['data']['command'];
                }
            }

            // Heuristic: ensure it's our BackupWebsite job
            $haystack = $command ?: $payload;
            if (stripos($haystack, 'BackupWebsite') === false) {
                continue;
            }

            // Try to extract URL and dispatchedBy without unserializing
            $url = null;
            $dispatchedBy = null;
            if (is_string($command) && $command !== '') {
                // Find a URL-like token
                if (preg_match('#https?://[^\s\"\'\\<>]+#i', $command, $m)) {
                    $url = $m[0];
                }
                // Look for dispatchedBy integer in serialized properties
                if (preg_match('/dispatchedBy";i:(\d+)/', $command, $m2)) {
                    $dispatchedBy = (int) $m2[1];
                } elseif (preg_match('/\\0App\\\\Jobs\\\\BackupWebsite\\0dispatchedBy";i:(\d+)/', $command, $m3)) {
                    $dispatchedBy = (int) $m3[1];
                }
            }

            if ($dispatchedBy !== null) {
                $userIds[] = $dispatchedBy;
            }

            // Safely format timestamps
            $availableAtStr = null;
            if (isset($job->available_at)) {
                $ts = null;
                if (is_numeric($job->available_at)) {
                    $ts = (int) $job->available_at;
                } else {
                    $parsed = strtotime((string) $job->available_at);
                    if ($parsed !== false) {
                        $ts = $parsed;
                    }
                }
                if ($ts !== null) {
                    $availableAtStr = date('Y-m-d H:i:s', $ts);
                }
            }

            $createdAtStr = null;
            if (isset($job->created_at)) {
                if (is_string($job->created_at)) {
                    $createdAtStr = $job->created_at;
                } elseif (is_numeric($job->created_at)) {
                    $createdAtStr = date('Y-m-d H:i:s', (int) $job->created_at);
                }
            }

            $rows[] = [
                'id' => $job->id,
                'queue' => $job->queue,
                'display' => $displayName ?? 'BackupWebsite',
                'url' => $url,
                'dispatched_by' => $dispatchedBy,
                'available_at' => $availableAtStr,
                'created_at' => $createdAtStr,
            ];
        }

        // Resolve user names for dispatcher IDs
        $userMap = [];
        if (!empty($userIds)) {
            $ids = array_values(array_unique($userIds));
            try {
                $users = DB::table('users')->select(['id', 'name', 'email'])->whereIn('id', $ids)->get();
                foreach ($users as $u) {
                    $name = $u->name;
                    $email = $u->email;
                    if (!is_string($name)) {
                        $name = (string) $name;
                    }
                    if (!is_string($email)) {
                        $email = (string) $email;
                    }
                    $userMap[(int) $u->id] = [
                        'name' => $name,
                        'email' => $email,
                    ];
                }
            } catch (\Throwable $_) {
                // ignore lookup failure
            }
        }

        // Attach display strings
        foreach ($rows as &$r) {
            $dispatcherId = $r['dispatched_by'];
            if (is_int($dispatcherId) && isset($userMap[$dispatcherId])) {
                $meta = $userMap[$dispatcherId];
                $r['dispatched_by_display'] = $meta['name'] . ' <' . $meta['email'] . '>';
            } elseif (is_int($dispatcherId)) {
                $r['dispatched_by_display'] = 'User ID ' . $dispatcherId;
            } else {
                $r['dispatched_by_display'] = 'n/a';
            }
        }
        unset($r);

        return $rows;
    }
}
