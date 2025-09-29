<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use App\Services\Slack\SlashCommandService;

class SlackSlashController extends Controller
{
    /**
     * Handle Slack slash command requests.
     *
     * Slack sends application/x-www-form-urlencoded payloads with a `command` key.
     * We return a JSON response matching Slack's expected schema:
     *   - response_type: "in_channel" (visible to everyone in the channel)
     *   - text: simple text response
     * Additionally, if a response_url is provided, we POST the message payload to it.
     */
    public function __invoke(Request $request, SlashCommandService $service): JsonResponse
    {
        $commandRaw = $request->get('command', '');
        $command = is_string($commandRaw) ? $commandRaw : '';
        $normalizedTemp = preg_replace('/\s+/', '', $command);
        $normalized = is_string($normalizedTemp) ? $normalizedTemp : '';

        $textRaw = $request->get('text');
        $textParam = is_string($textRaw) ? $textRaw : null;

        // Delegate payload construction to the service. It returns a structured array:
        //    - payload: the final Slack message (could be a simple text or a large nested array of blocks/attachments)
        //    - text: the raw text chosen for this command (useful for logging)
        //    - response_type: Slack visibility mode (we default to in_channel)
        //    - has_blocks: whether the payload contains rich Block Kit structures
        try {
            $build = $service->routeAndBuild($normalized, $textParam);
            $payload = $build['payload'];
            $text = $build['text'];
            $responseType = $build['response_type'];
            $hasBlocks = $build['has_blocks'];
        } catch (\Throwable $e) {
            Log::channel('api')->error('Slack slash command build failed', [
                'command_raw' => $command,
                'command_normalized' => $normalized,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'ok' => false,
                'acknowledged' => true,
                'error' => 'Unable to process command at this time.',
            ]);
        }

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


        $postStatus = null;
        $postOk = null;
        $postError = null;
        $postBody = null;
        if (is_string($responseUrl) && $responseUrl !== '') {
            $result = $service->postToResponseUrl($responseUrl, $payload);
            $postStatus = $result['status'];
            $postOk = $result['ok'];
            $postError = $result['error'];
            $postBody = $result['body'];
        }

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
            'has_blocks' => $hasBlocks,
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

        // Final ACK - Handled by Cloudflare Edge Worker initially
        return response()->json([
            'ok' => true,
            'acknowledged' => true,
            'note' => 'Message posted via response_url when available.',
        ]);
    }
}
