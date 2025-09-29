<?php

namespace App\Services\Slack;

use App\Services\Asana\AsanaService;
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

    public function __construct(private readonly AsanaService $asana)
    {
    }

    /**
     * Route the normalized command to the appropriate payload builder.
     * This keeps the controller slim and makes it easy to add more commands.
     *
     * For now we keep simple routing logic here. In the future, this can evolve
     * into a map of handlers implementing a common interface, without changing
     * the controller.
     *
     * @param string $normalized
     * @param string|null $text Optional free-text argument sent after the command (e.g., for searches)
     * @return array{payload: array<string,mixed>, text: string, response_type: string, has_blocks: bool}
     */
    public function routeAndBuild(string $normalized, ?string $text = null): array
    {
        if ($normalized === '/asanaprojects') {
            return $this->buildPayloadAsanaProjects($normalized, $text);
        }

        return $this->buildSimplePayloads($normalized);
    }

    /**
     * Build the Slack message payload for a given (already normalized) command.
     *
     * Why a separate function from buildPayloadAsanaProjects?
     * - This method handles our "basic" commands that only need simple text or a small set of static blocks
     *   (e.g., /handbook, /example2, /example3). These are fast-path responses with no external API calls.
     * - Keeping simple commands here avoids mixing them with the Asana pipeline, which has network I/O and
     *   richer block composition logic.
     *
     * The Asana-specific/other flow lives in buildPayloadAsanaProjects()/other, which isolates that integration (API calls,
     * parsing, and block construction) behind a clear boundary, keeping this method focused on basic text/url commands.
     *
     * @param string $normalized e.g. "/handbook", "/example2", "/example3"
     * @return array{payload: array<string,mixed>, text: string, response_type: string, has_blocks: bool}
     */
    public function buildSimplePayloads(string $normalized): array
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

            // Remove top-level text so only blocks/attachments are rendered for /handbook
            unset($payload['text']);
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
     * Build payload for /asanaprojects <query>
     *
     * This method encapsulates our Asana integration pipeline.
     * - It calls Asana Typeahead to fetch matching projects in the configured workspace.
     * - Then, for each project, it retrieves the permalink_url via the Projects API.
     * - Finally, it constructs rich Slack Block Kit sections listing project names and links.
     *
     * Keeping this logic separate from buildSimplePayloads() keeps concerns clean:
     * - buildSimplePayloads() remains focused on basic text/URL-based commands that do not require network I/O.
     * - buildPayloadAsanaProjects() owns external API calls and richer, list-style block composition.
     *
     * @param string $normalized
     * @param string|null $query
     * @return array{payload: array<string,mixed>, text: string, response_type: string, has_blocks: bool}
     */
    public function buildPayloadAsanaProjects(string $normalized, ?string $query): array
    {
        $responseType = 'in_channel';
        $q = is_string($query) ? trim($query) : '';

        $results = $this->asana->typeaheadProjects($q, 5);

        $blocks = [];
        if (empty($results)) {
            $blocks[] = [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => ($q === '')
                        ? "No recent projects found. Try '/asanaprojects <keywords>'."
                        : "No projects found for ‘{$q}’.",
                ],
            ];
        } else {
            $blocks[] = [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => $q === '' ? '*Recent Asana Projects*' : "*Asana Projects matching:* {$q}",
                ],
            ];
            $blocks[] = ['type' => 'divider'];

            // Fetch permalink_url for each project (best-effort)
            foreach ($results as $item) {
                // $results items are guaranteed to have string 'gid' and 'name' by AsanaService::typeaheadProjects return type
                $gid = $item['gid'];
                $name = $item['name'];
                $url = null;

                $proj = $this->asana->getProject($gid);
                if (is_array($proj) && isset($proj['permalink_url'])) {
                    $url = $proj['permalink_url'];
                }

                $text = $url ? "• *{$name}* — <{$url}|Open>" : "• *{$name}* (no URL)";
                $blocks[] = [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => $text,
                    ],
                ];
            }
        }

        $payload = [
            'response_type' => $responseType,
            'unfurl_links' => false,
            'unfurl_media' => false,
            'blocks' => $blocks,
        ];

        return [
            'payload' => $payload,
            'text' => $q,
            'response_type' => $responseType,
            'has_blocks' => true,
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
