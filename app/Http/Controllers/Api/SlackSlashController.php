<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

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

        return response()->json([
            'response_type' => 'in_channel',
            'text' => $text,
        ]);
    }
}
