# Logging Configuration Guide

## Overview
This document provides information about the application's logging configuration, including available log channels and how to use them effectively. The application uses Laravel's logging system, which is built on top of the Monolog library.

## Log Channels

The application supports multiple logging channels, each designed for a specific purpose:

### Web Channel
- **Purpose**: Logs events related to web requests and user interactions
- **Configuration**:
  ```php
  'web' => [
      'driver' => 'daily',
      'path' => storage_path('logs/web.log'),
      'level' => env('LOG_LEVEL', 'debug'),
      'days' => env('LOG_DAILY_DAYS', 14),
  ],
  ```
- **File Location**: `storage/logs/web.log`
- **Rotation**: Daily rotation with 14 days retention (configurable via `LOG_DAILY_DAYS`)

### API Channel
- **Purpose**: Logs events related to API requests and responses
- **Configuration**:
  ```php
  'api' => [
      'driver' => 'daily',
      'path' => storage_path('logs/api.log'),
      'level' => env('LOG_LEVEL', 'debug'),
      'days' => env('LOG_DAILY_DAYS', 14),
  ],
  ```
- **File Location**: `storage/logs/api.log`
- **Rotation**: Daily rotation with 14 days retention (configurable via `LOG_DAILY_DAYS`)

### Slack Channel
- **Purpose**: Sends critical log messages to a Slack channel for immediate attention
- **Configuration**:
  ```php
  'slack' => [
      'driver' => 'slack',
      'url' => env('LOG_SLACK_WEBHOOK_URL'),
      'username' => env('LOG_SLACK_USERNAME', 'Laravel Log'),
      'emoji' => env('LOG_SLACK_EMOJI', ':boom:'),
      'level' => env('LOG_LEVEL', 'critical'),
  ],
  ```
- **Setup**: Requires a valid Slack webhook URL set in `LOG_SLACK_WEBHOOK_URL`
- **Default Level**: Critical (only sends critical errors by default)

## Using Log Channels

### Basic Logging

To write to the default log channel:

```php
Log::info('This is an informational message');
Log::error('An error occurred', ['context' => 'additional information']);
```

### Channel-Specific Logging

To write to a specific channel:

```php
// Log to the web channel
Log::channel('web')->info('User logged in', ['user_id' => $user->id]);

// Log to the API channel
Log::channel('api')->warning('Rate limit approaching', ['endpoint' => '/api/users']);

// Log to Slack (critical messages)
Log::channel('slack')->critical('Database connection failed');
```

### Using Multiple Channels

To write to multiple channels simultaneously:

```php
// Log to both web and slack channels
Log::stack(['web', 'slack'])->critical('Application is in maintenance mode');
```

## Log Levels

The application supports the following log levels, in order of severity:

1. **emergency**: System is unusable
2. **alert**: Action must be taken immediately
3. **critical**: Critical conditions
4. **error**: Error conditions
5. **warning**: Warning conditions
6. **notice**: Normal but significant conditions
7. **info**: Informational messages
8. **debug**: Debug-level messages

The minimum log level can be configured via the `LOG_LEVEL` environment variable.

## Environment Variables

The following environment variables can be set in your `.env` file to configure the logging system:

| Variable | Description | Default | Example |
|----------|-------------|---------|---------|
| `LOG_CHANNEL` | The default log channel to use | `stack` | `stack`, `single`, `daily`, `slack` |
| `LOG_LEVEL` | The minimum log level to record | `debug` | `debug`, `info`, `warning`, `error`, `critical` |
| `LOG_STACK` | Comma-separated list of channels for the stack driver | `single` | `single,web,api,slack` |
| `LOG_DAILY_DAYS` | Number of days to keep daily log files | `14` | `7`, `14`, `30` |
| `LOG_DEPRECATIONS_CHANNEL` | Channel for deprecation warnings | `null` | `null`, `single` |
| `LOG_DEPRECATIONS_TRACE` | Whether to include stack traces for deprecations | `false` | `true`, `false` |

### Slack-Specific Environment Variables

| Variable | Description | Default | Example |
|----------|-------------|---------|---------|
| `LOG_SLACK_WEBHOOK_URL` | Webhook URL for Slack integration | None | `https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXXXXXXXXXX` |
| `LOG_SLACK_USERNAME` | Username displayed in Slack messages | `Laravel Log` | `App Alerts`, `Production Errors` |
| `LOG_SLACK_EMOJI` | Emoji displayed in Slack messages | `:boom:` | `:rotating_light:`, `:warning:`, `:x:` |

### Development Environment Configuration

For local development, you might want to set a more verbose log level:

```
LOG_CHANNEL=stack
LOG_LEVEL=debug
LOG_STACK=single,web,api
```

### Production Environment Configuration

For production, consider a more restrictive log level:

```
LOG_CHANNEL=stack
LOG_LEVEL=warning
LOG_STACK=single,web,api,slack
LOG_SLACK_WEBHOOK_URL=https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXXXXXXXXXX
LOG_SLACK_USERNAME="Production Alerts"
LOG_SLACK_EMOJI=":rotating_light:"
```

## Configuring Slack Integration

### Creating a Slack Webhook

To set up Slack logging, you need to create an incoming webhook in your Slack workspace:

1. **Create a Slack App**:
   - Go to [https://api.slack.com/apps](https://api.slack.com/apps)
   - Click "Create New App" and select "From scratch"
   - Enter a name for your app (e.g., "Laravel Logs")
   - Select your workspace and click "Create App"

2. **Enable Incoming Webhooks**:
   - In the left sidebar, click on "Incoming Webhooks"
   - Toggle "Activate Incoming Webhooks" to On
   - Click "Add New Webhook to Workspace"
   - Select the channel where you want to receive log notifications
   - Click "Allow"

3. **Copy the Webhook URL**:
   - After allowing access, you'll be redirected back to the Incoming Webhooks page
   - Copy the webhook URL (it should look like `https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXXXXXXXXXX`)

4. **Add to Environment Variables**:
   - Add the webhook URL to your `.env` file:
     ```
     LOG_SLACK_WEBHOOK_URL=https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXXXXXXXXXX
     ```

### Customizing Slack Notifications

You can customize how your log messages appear in Slack:

1. **Change the Username**:
   ```
   LOG_SLACK_USERNAME="Production Error Monitor"
   ```

2. **Change the Emoji**:
   ```
   LOG_SLACK_EMOJI=":rotating_light:"
   ```

3. **Adjust the Log Level**:
   By default, only critical errors are sent to Slack. To change this:
   ```
   # Send errors and above to Slack
   LOG_LEVEL=error
   ```

### Testing Slack Integration

To test if your Slack integration is working correctly:

1. Add the following route to your `routes/web.php` file (for testing purposes only):
   ```php
   Route::get('/test-slack-logging', function () {
       Log::channel('slack')->critical('Test critical message to Slack');
       return 'Test message sent to Slack';
   });
   ```

2. Visit `/test-slack-logging` in your browser
3. Check your Slack channel for the test message
4. Remove the test route after confirming it works

## Troubleshooting

### Viewing Logs

Log files are stored in the `storage/logs` directory. You can view them using:

```bash
# View the web log
tail -f storage/logs/web.log

# View the API log
tail -f storage/logs/api.log
```

### Common Issues

1. **Logs not being written**: Check file permissions on the `storage/logs` directory
2. **Slack notifications not working**: Verify your webhook URL is correct and the channel exists
3. **Missing log entries**: Ensure your minimum log level is appropriate for the messages you're logging
