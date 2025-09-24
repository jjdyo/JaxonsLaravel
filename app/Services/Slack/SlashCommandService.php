<?php

namespace App\Services\Slack;

use Illuminate\Support\Facades\Http;

/**
 * Service responsible for building Slack slash command payloads
 * and posting them back via response_url.
 */
class SlashCommandService
{
    /**
     * Centralized, easily editable URL for the handbook command.
     */
    public const HANDBOOK_URL = 'https://docs.google.com/document/d/1AJOXgbRYp5Bcm9mxGp3Z3SY1ocHHoqDIPSdewa0J_KQ/edit?usp=sharing';

    /**
     * Build the Slack message payload for a given (already normalized) command.
     *
     * @param string $normalized e.g. "/handbook", "/example2", "/example3"
     * @return array{payload: array<string,mixed>, text: string, response_type: string, has_blocks: bool}
     */
    public function buildPayload(string $normalized): array
    {
        $responseType = 'in_channel';

        $text = match ($normalized) {
            '/handbook' => self::HANDBOOK_URL, // clickable URL in Slack
            '/example2' => 'example2',
            '/example3' => 'example3',
            default => 'Unknown command. Try /handbook, /example2, or /example3.',
        };

        $payload = [
            'response_type' => $responseType,
            'text' => $text,
        ];

        $hasBlocks = false;
        if ($normalized === '/handbook') {
            $docUrl = self::HANDBOOK_URL;
            $payload['unfurl_links'] = true;
            $payload['unfurl_media'] = true;

            // Use an attachment to provide a colored accent/background-like treatment
            // and move the button into an actions block so it's not floated to the side.
            $payload['attachments'] = [
                [
                    'color' => '#1D9BD1', // Slack blue accent (differentiating color)
                    'blocks' => [
                        [
                            'type' => 'section',
                            'text' => [
                                'type' => 'mrkdwn',
                                'text' => "*Team Handbook*\n<{$docUrl}|Open the live document>",
                            ],
                        ],
                        [
                            'type' => 'actions',
                            'elements' => [
                                [
                                    'type' => 'button',
                                    'text' => [
                                        'type' => 'plain_text',
                                        'text' => 'Open Handbook',
                                        'emoji' => true,
                                    ],
                                    'style' => 'primary',
                                    'url' => $docUrl,
                                ],
                            ],
                        ],
                        [
                            'type' => 'context',
                            'elements' => [
                                [
                                    'type' => 'mrkdwn',
                                    'text' => 'Google Doc • Always up to date',
                                ],
                            ],
                        ],
                    ],
                ],
            ];

            // Keep a simple top-level fallback text without the URL to avoid duplicate link rendering
            $payload['text'] = 'Team Handbook';
            $hasBlocks = true;
        }

        return [
            'payload' => $payload,
            'text' => $text,
            'response_type' => $responseType,
            'has_blocks' => $hasBlocks,
        ];
    }

    /**
     * Post the message payload to Slack's response_url.
     *
     * @param string $responseUrl
     * @param array<string,mixed> $payload
     * @return array{status: int|null, ok: bool|null, error: string|null, body: string|null}
     */
    public function postToResponseUrl(string $responseUrl, array $payload): array
    {
        $status = null;
        $ok = null;
        $error = null;
        $body = null;

        try {
            $resp = Http::timeout(3)->asJson()->post($responseUrl, $payload);
            $status = $resp->status();
            $ok = $resp->successful();
            $body = $resp->body();
        } catch (\Throwable $e) {
            $ok = false;
            $error = $e->getMessage();
        }

        return [
            'status' => $status,
            'ok' => $ok,
            'error' => $error,
            'body' => $body,
        ];
    }
}
