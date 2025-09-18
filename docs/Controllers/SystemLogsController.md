# SystemLogsController

## Overview
The SystemLogsController provides an administrative interface for browsing application log files by channel and date, and a lightweight JSON endpoint for streaming chunks of logs. All routes are protected by the auth, verified, and role:admin middleware and are prefixed with /admin.

- Namespace: App\\Http\\Controllers
- Middleware: auth, verified, role:admin
- Prefix/Name: /admin, admin.

## Routes and Actions

1) GET /admin/system-logs (name: admin.system-logs.index)
- Method: index(Request $request)
- Query params (optional):
  - channel: string; e.g. web, api (must match a configured logging channel file prefix)
  - date: string; format YYYY-MM-DD (must match an available date for the channel)
- Behavior:
  - Scans storage/logs for files following pattern <channel>-YYYY-MM-DD.log
  - Builds a list of available channels and dates, and selects the requested or latest date
  - Reads up to the last 128 KiB of the selected log file to prevent memory issues
- View: resources/views/admin/system-logs/index.blade.php
- Output variables:
  - channels: string[] of available channels
  - availableLogs: map<string, string[]> channel => dates
  - latestDates: map<string, string> channel => latest date
  - selectedChannel: string
  - selectedDate: string (empty means latest)
  - content: string (tail of the selected file)

2) GET /admin/system-logs/fetch (name: admin.system-logs.fetch)
- Method: fetchLogs(Request $request) -> JsonResponse
- Query params:
  - channel: required string; e.g. web, api
  - date: required string; YYYY-MM-DD
  - offset: optional int >= 0; byte offset to start reading
  - limit: optional int; bytes to read (default 65536, max 262144)
  - tail_lines: optional int > 0; if provided and offset not set, returns last N lines best-effort
- Responses:
  - 200 JSON with { channel, date, file_size, offset|null, start, limit, bytes, next_offset|null, eof, chunk }
  - 4xx/5xx JSON with error message on invalid input or file errors

## Logging Channels
The following daily logging channels are configured in config/logging.php and are supported by this UI:
- web -> storage/logs/web-YYYY-MM-DD.log
- api -> storage/logs/api-YYYY-MM-DD.log
- slack (optional, for alerts; not a file on disk)
- daily/single (framework defaults)

To enable Slack alerts, set LOG_SLACK_WEBHOOK_URL in .env. See the .env setup guide for details.

## Security/Performance Notes
- File access is constrained to storage/logs.
- Only the tail (last 128 KiB) is loaded for the HTML view to avoid memory issues with large files.
- The fetch endpoint supports pagination via byte offsets and optional tailing by line count.

## Related
- [Routes](../Routes.md)
- [Application Configuration](../Configuration/Application%20Configuration.md)
- [Setting up .env](../Installation%20and%20Startup/Setting%20up%20.env.md)
