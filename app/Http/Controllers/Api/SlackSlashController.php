<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;

class SlackSlashController extends Controller
{
    /**
     * Handle Slack slash command requests.
     *
     * Slack sends application/x-www-form-urlencoded payloads with a `command` key.
     * We return a JSON response matching Slack's expected schema:
     *   - response_type: "in_channel" (visible to everyone in the channel)
     *   - text: simple text response
     */
    public function __invoke(Request $request): JsonResponse
    {
        $commandRaw = $request->get('command', '');
        $command = is_string($commandRaw) ? $commandRaw : '';
        // Normalize potential spacing variants like "/example 3"
        $normalized = preg_replace('/\s+/', '', $command);

        $text = match ($normalized) {
            '/handbook' => 'handbook',
            '/example2' => 'example2',
            '/example3' => 'example3',
            default => 'Unknown command. Try /handbook, /example2, or /example3.',
        };

        // Gather request metadata safely for logging
        $ip = is_string($_SERVER['REMOTE_ADDR'] ?? null) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
        $userAgent = is_string($_SERVER['HTTP_USER_AGENT'] ?? null) ? $_SERVER['HTTP_USER_AGENT'] : 'unknown';

        $teamIdRaw = $request->get('team_id');
        $teamId = is_string($teamIdRaw) ? $teamIdRaw : null;

        $channelIdRaw = $request->get('channel_id');
        $channelId = is_string($channelIdRaw) ? $channelIdRaw : null;

        $userIdRaw = $request->get('user_id');
        $userId = is_string($userIdRaw) ? $userIdRaw : null;

        $userNameRaw = $request->get('user_name');
        $userName = is_string($userNameRaw) ? $userNameRaw : null;

        $responseType = 'in_channel';

        // Log to the dedicated 'api' logging channel
        Log::channel('api')->info('Slack slash command processed', [
            'command_raw' => $command,
            'command_normalized' => $normalized,
            'response_text' => $text,
            'response_type' => $responseType,
            'ip' => $ip,
            'user_agent' => $userAgent,
            'team_id' => $teamId,
            'channel_id' => $channelId,
            'user_id' => $userId,
            'user_name' => $userName,
        ]);

        return response()->json([
            'response_type' => $responseType,
            'text' => $text,
        ]);
    }
}
