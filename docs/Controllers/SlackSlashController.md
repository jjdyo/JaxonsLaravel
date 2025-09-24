# SlackSlashController

Document owner: Platform • Last updated: 2025-09-24

This document explains how our Slack slash command endpoint works end‑to‑end, including the Zero Trust proxy/worker that fronts the server and the internal service that builds Slack payloads.

---

## Overview

- Entry point: POST /api/slack/slash (routes/api.php)
- Controller: App\Http\Controllers\Api\SlackSlashController (invokable)
- Service: App\Services\Slack\SlashCommandService
- Pattern: Skinny controller delegating to a service to construct Slack messages, then optionally posting via Slack’s response_url for the final user-visible message.
- Philosophy: The app immediately acknowledges the request (HTTP 200 with a small JSON “ack”) and, when provided, pushes the rich message to Slack asynchronously via response_url.


## High-level Request Flow (with Zero Trust Proxy)

Slack does not call the Laravel app directly. Requests traverse a security and trust chain:

1) Slack → Edge Worker (Proxy)
   - Validates Slack’s signature (X-Slack-Signature + X-Slack-Request-Timestamp) using a shared signing secret (HMAC-SHA256).
   - Rejects tampered/expired requests (prevents replay attacks and spoofing).

2) Edge Worker → Zero Trust Gateway
   - Attaches Zero Trust headers that assert the request has passed security policy (device posture, identity, IP reputation, etc.).
   - Example concepts: X-Forwarded-Email, CF-Access-Jwt-Assertion, or custom ZT headers depending on provider.

3) Zero Trust Gateway → Application (our Laravel API)
   - The gateway forwards the validated request to POST /api/slack/slash.
   - Our application assumes the HMAC has been verified upstream and trusts Zero Trust headers as the assertion of authenticity and policy compliance.

4) Application behavior
   - Normalize and interpret the command string.
   - Build the Slack message payload via SlashCommandService.
   - If response_url is present, POST the constructed payload to Slack using that URL.
   - Immediately return JSON { ok: true, acknowledged: true } to the proxy (not the full Slack message). Slack renders the final message from the response_url post.

Note: Slack’s response_url is how we deliver the final, user-visible message. The immediate HTTP response from our controller is only an acknowledgment for the worker/proxy chain.


## Endpoint Details

- Route: POST /api/slack/slash
- Controller: SlackSlashController::__invoke(Request $request, SlashCommandService $service)
- Auth: No app-side auth for initial testing; trust boundary is at the Edge Worker / Zero Trust layer.

Expected request fields (form-urlencoded by Slack; the worker forwards them):
- command: string, like "/handbook". We normalize by removing all whitespace anywhere in the string.
- response_url: string (optional). If present, the server will send the actual Slack message payload to this URL.
- team_id, channel_id, user_id, user_name: optional metadata; logged for observability.

Response (immediate):
- 200 OK with JSON: { ok: boolean, acknowledged: true, note?: string, error?: string }


## Normalization and Validation

- The controller coerces command to a string and removes all whitespace using a regex (preg_replace('/\s+/', '', $command)).
- Missing, null, or non-string commands are treated as empty ("").
- Normalization ensures variants like " /hand  book  " are taken as "/handbook".


## Building the Slack Message (SlashCommandService)

SlashCommandService centralizes the message construction logic so it’s easy to test and extend.

- buildPayload(string $normalized): array
  - Returns a structured array with keys:
    - payload: array — final Slack message body (response_type, text, and optionally blocks/attachments)
    - text: string — the chosen textual representation/log summary
    - response_type: string — typically "in_channel"
    - has_blocks: bool — whether blocks/attachments are present
  - Commands supported today:
    - /handbook — links to the team handbook (blocks/attachments with a button)
    - /example2, /example3 — placeholders
    - default — returns an “Unknown command” text

- postToResponseUrl(string $responseUrl, array $payload): array
  - Posts JSON to response_url with a small timeout.
  - Returns diagnostic info: { status, ok, error, body }.
  - Exceptions are caught and surfaced in the return structure for logging.


## Posting via response_url (the real Slack message)

- If response_url is provided, the controller calls SlashCommandService::postToResponseUrl(response_url, payload) after building the payload.
- We still return the immediate JSON ack to the caller so the Edge Worker can finish quickly.
- The actual user-visible message in Slack comes from the response_url post.


## Logging and Observability

The controller writes a single structured log entry to the api channel (configurable) including:
- command_raw and command_normalized
- response_text and response_type
- has_blocks flag
- request metadata (ip, user_agent, team_id, channel_id, user_id, user_name)
- response_url presence and host
- result of posting to response_url (status/ok/error/body)

On failures within buildPayload(), the controller logs an error and still returns a safe JSON ack with ok=false to avoid breaking the proxy pipeline.


## Error Handling

- Service exceptions during payload build are caught.
- The controller returns 200 with: { ok: false, acknowledged: true, error: "Unable to process command at this time." }
- Rationale: Slack expects a timely response; we prefer non-fatal acks and follow-up via response_url if applicable.


## Security Model (Recap)

- HMAC verification: Performed by the Edge Worker using Slack’s signing secret. The Laravel app does not re-verify HMAC; it trusts the worker.
- Zero Trust headers: The Edge Worker/Gateway attaches headers indicating trust. Our app may use these for additional checks or auditing if desired.
- Rate limiting: Consider adding rate limiting at the edge and/or within Laravel if spam becomes a concern.


## Configuration and Extensibility

- Add new commands: Extend the match statement and payload logic in SlashCommandService::buildPayload().
- URLs and constants: Use class constants (e.g., HANDBOOK_URL) for easy updates.
- Timeouts: postToResponseUrl uses a short timeout (currently 3s via Http::timeout(3)). Adjust based on reliability needs.
- Logging channel: api channel is used; configure in logging.php if needed.


## Testing

Feature tests live in tests/Feature/Controllers/SlackSlashControllerTest.php and cover:
- Missing/null/non-string commands
- Invalid commands
- Command normalization via a DataProvider
- Posting to response_url
- Graceful handling of service exceptions

Tests mock SlashCommandService to avoid real HTTP calls and to assert controller interactions.


## Example Flows

1) /handbook with response_url
   - User runs /handbook in Slack.
   - Slack → Worker (HMAC OK) → Zero Trust → Laravel.
   - Controller normalizes to "/handbook", service builds blocks with a button, controller POSTs the payload to response_url.
   - Controller returns { ok: true, acknowledged: true } immediately; Slack renders the message from the response_url post.

2) Unknown command without response_url
   - Controller builds a simple text payload: "Unknown command. Try /handbook, /example2, or /example3." but does not post anywhere.
   - Controller still returns the JSON ack.


## Related Files

- app/Http/Controllers/Api/SlackSlashController.php
- app/Services/Slack/SlashCommandService.php
- routes/api.php (POST /api/slack/slash)
- tests/Feature/Controllers/SlackSlashControllerTest.php
