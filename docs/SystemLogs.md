# System Logs

The System Logs feature provides administrators with a convenient way to view and monitor application logs from different channels.

## Overview

The System Logs page allows administrators to:

- View logs from different channels (web, api)
- Dynamically load logs as they scroll down (infinite scrolling)
- Filter logs by selecting different log channels

## Access Control

- Only users with the `admin` role can access the System Logs page
- The page is accessible from the Admin Dashboard

## Implementation Details

### Controller

The `SystemLogsController` handles the following actions:

- `index()`: Displays the System Logs page with a selector for available log channels
- `fetchLogs()`: AJAX endpoint that retrieves logs with pagination

### Routes

The following routes are defined for the System Logs feature:

- `GET /admin/system-logs` - Main System Logs page
- `GET /admin/system-logs/fetch` - AJAX endpoint for fetching logs

### View

The System Logs view (`resources/views/admin/system-logs/index.blade.php`) includes:

- A dropdown selector for choosing the log channel
- A log viewer with styled log entries
- JavaScript for handling infinite scrolling and dynamic loading

### Log Parsing

Logs are parsed from the log files in `storage/logs/` directory. The implementation:

- Uses standard log files (e.g., web.log, api.log) with predefined paths
- Extracts timestamp, log level, and message from each log entry
- Formats logs for better readability with color-coding based on log level

## Usage

1. Navigate to the Admin Dashboard
2. Click on "View Logs" in the System Logs card
3. Select a log channel from the dropdown (web or api)
4. Scroll down to load more logs as needed

## Technical Notes

- Logs are loaded in batches of 20 entries to optimize performance
- The page uses AJAX to fetch logs without refreshing the page
- Log entries are displayed from newest to oldest
- The implementation handles various log formats and provides fallback for non-standard formats

## Testing

The feature includes comprehensive tests in `tests/Feature/Controllers/SystemLogsControllerTest.php` that verify:

- Access control (only admins can access)
- Log fetching functionality
- Parameter validation

## Future Enhancements

Potential future enhancements for the System Logs feature:

- Add search functionality to filter logs by content
- Add date range filtering
- Add log level filtering (info, error, warning, etc.)
- Add export functionality to download logs
- Add real-time log streaming for live monitoring
