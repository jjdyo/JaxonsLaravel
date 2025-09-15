<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class RequestLogger
{
    public function __construct(private Router $router) {}

    public function handle(Request $request, Closure $next): SymfonyResponse
    {
        $start = microtime(true);

        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $next($request);

        $pathInfo = $request->getPathInfo();

        $isApiPath = str_starts_with($pathInfo, '/api/');
        $channel   = $isApiPath ? 'api' : 'web';

        // Verb -> emoji
        $method = $request->getMethod();
        $emoji  = match ($method) {
            'GET'             => '👀',
            'POST'            => '📩',
            'PUT', 'PATCH'    => '✏️',
            'DELETE'          => '🗑️',
            default           => '➡️',
        };

        $routeName = $this->router->currentRouteName() ?? 'N/A';

        $sanitizedPath = $this->maskPathHashes($pathInfo);

        $user = Auth::user();
        if ($user) {
            // Use Eloquent attribute accessors instead of property_exists (attributes are dynamic)
            /** @var string|null $maybeName */
            $maybeName = $user->getAttribute('name')
                ?? $user->getAttribute('full_name')
                ?? $user->getAttribute('username');

            /** @var string|null $maybeEmail */
            $maybeEmail = $user->getAttribute('email');

            $displayName = is_string($maybeName) && $maybeName !== ''
                ? $maybeName
                : (is_string($maybeEmail) && $maybeEmail !== '' ? $maybeEmail : 'user');

            /** @var mixed $rawId */
            $rawId = method_exists($user, 'getAuthIdentifier') ? $user->getAuthIdentifier() : null;
            $idLabel = is_scalar($rawId) || (is_object($rawId) && method_exists($rawId, '__toString'))
                ? (string) $rawId
                : 'unknown';

            $userLabel = $displayName . ' (' . $idLabel . ')';
        } else {
            $userLabel = 'guest';
        }

        $durationMs = (int) round((microtime(true) - $start) * 1000);

        $ua = $request->headers->get('User-Agent');
        $uaStr = is_string($ua) ? $ua : 'N/A';

        $lines = [
            $emoji . ' ' . $method,
            '  URI: '      . $sanitizedPath,
            '  Route: '    . $routeName,
            '  Status: '   . $response->getStatusCode(),
            '  User: '     . $userLabel,
            '  Duration: ' . $durationMs . ' ms',
            '  UA: '       . $uaStr,
        ];

        Log::channel($channel)->info(implode("\n", $lines));

        return $response;
    }

    /** Masks hash-like PATH segments: /x/abcdef... -> /x/{{HASH:40}} */
    private function maskPathHashes(string $path): string
    {
        $parts = array_filter(explode('/', $path), static fn($s) => $s !== '');
        $parts = array_map(function ($seg) {
            return $this->looksLikeToken($seg) ? '{{HASH:' . strlen($seg) . '}}' : $seg;
        }, $parts);

        return '/' . implode('/', $parts);
    }

    /** Heuristic for token-looking strings (hex or URL-safe base64-ish, length ≥ 16). */
    private function looksLikeToken(string $s): bool
    {
        $len = strlen($s);
        if ($len < 16) {
            return false;
        }
        if (preg_match('/^[A-Fa-f0-9]+$/', $s) === 1) {
            return true; // hex
        }
        if (preg_match('/^[A-Za-z0-9\-_]+$/', $s) === 1) {
            return true; // url-safe base64-ish / random token
        }
        return false;
    }
}
