<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class SlackSlashController extends Controller
{
    // Centralized, easily editable URL for the handbook command
    private const HANDBOOK_URL = 'https://docs.google.com/document/d/1AJOXgbRYp5Bcm9mxGp3Z3SY1ocHHoqDIPSdewa0J_KQ/edit?usp=sharing';
    /**
     * Handle Slack slash command requests.
     *
     * Slack sends application/x-www-form-urlencoded payloads with a `command` key.
     * We return a JSON response matching Slack's expected schema:
     *   - response_type: "in_channel" (visible to everyone in the channel)
     *   - text: simple text response
     * Additionally, if a response_url is provided, we POST the message payload to it.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $commandRaw = $request->get('command', '');
        $command = is_string($commandRaw) ? $commandRaw : '';
        // Normalize potential spacing variants like "/example 3"
        $normalized = preg_replace('/\s+/', '', $command);

        $text = match ($normalized) {
            '/handbook' => self::HANDBOOK_URL, // clickable URL in Slack
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

        $responseUrlRaw = $request->get('response_url');
        $responseUrl = is_string($responseUrlRaw) ? $responseUrlRaw : null;

        $responseType = 'in_channel';

        // If response_url is provided, POST the message there per Slack docs
        $postStatus = null;
        $postOk = null;
        $postError = null;
        $postBody = null;
        if (is_string($responseUrl) && $responseUrl !== '') {
            try {
                $resp = Http::timeout(3)->asJson()->post($responseUrl, [
                    'text' => $text,
                    'response_type' => $responseType,
                ]);
                $postStatus = $resp->status();
                $postOk = $resp->successful();
                $postBody = $resp->body();
            } catch (\Throwable $e) {
                $postOk = false;
                $postError = $e->getMessage();
            }
        }

        // Log to the dedicated 'api' logging channel
        $responseUrlHost = null;
        if (is_string($responseUrl) && $responseUrl !== '') {
            $parts = parse_url($responseUrl);
            $responseUrlHost = is_array($parts) && isset($parts['host']) ? $parts['host'] : null;
        }

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
            'has_response_url' => is_string($responseUrl) && $responseUrl !== '',
            'response_url' => $responseUrl,
            'response_url_host' => $responseUrlHost,
            'response_post_ok' => $postOk,
            'response_post_status' => $postStatus,
            'response_post_error' => $postError,
            'response_post_body' => $postBody,
        ]);

        // Acknowledge the slash command quickly. We keep the JSON for compatibility,
        // but the authoritative message is sent via response_url when provided.
        return response()->json([
            'ok' => true,
            'acknowledged' => true,
            'note' => 'Message posted via response_url when available.',
        ]);
    }
}
