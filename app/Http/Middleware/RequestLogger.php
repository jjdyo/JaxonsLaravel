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
        $start    = microtime(true);
        $response = $next($request);

        $pathInfo = $request->getPathInfo();
        $channel  = str_starts_with($pathInfo, '/api') ? 'api' : 'web';

        $emoji = match ($request->getMethod()) {
            'GET'    => '👀',
            'POST'   => '📩',
            'PUT', 'PATCH' => '✏️',
            'DELETE' => '🗑️',
            default  => '➡️',
        };

        // Route info (no type drama)
        $route     = $this->router->current();
        $routeName = $this->router->currentRouteName() ?? 'N/A';
        $routeUri  = $route?->uri() ?? ltrim($pathInfo, '/');

        // Mask hash-ish path segments but keep structure
        $sanitizedPath = $this->maskPathHashes($pathInfo);

        // Already-resolved user (no extra queries)
        $user = Auth::user();
        $userLabel = $user
            ? sprintf('%s (%s)', trim($user->name ?? $user->email ?? 'user'), $user->getAuthIdentifier())
            : 'guest';

        $durationMs = (int) round((microtime(true) - $start) * 1000);

        // No query string, no full URL, no IP
        $lines = [
            sprintf('%s %s', $emoji, $request->getMethod()),
            '  URI: '       . $sanitizedPath,
            '  Route: '     . $routeName,
            '  Route URI: ' . $routeUri,   // template like email/verify/{id}/{hash} when available
            '  Status: '    . $response->getStatusCode(),
            '  User: '      . $userLabel,
            '  Duration: '  . $durationMs . ' ms',
            '  UA: '        . ($request->userAgent() ?? 'N/A'),
        ];

        Log::channel($channel)->info(implode("\n", $lines));

        return $response;
    }

    /** Masks hash-like PATH segments: /x/abc123... -> /x/{{HASH:40}} */
    private function maskPathHashes(string $path): string
    {
        $segments = array_filter(explode('/', $path), static fn($s) => $s !== '');
        $segments = array_map(function ($seg) {
            return $this->looksLikeToken($seg) ? '{{HASH:' . strlen($seg) . '}}' : $seg;
        }, $segments);

        return '/' . implode('/', $segments);
    }

    /** Heuristic for token-looking strings (hex or url-safe base64-ish, length ≥ 16). */
    private function looksLikeToken(string $s): bool
    {
        $len = strlen($s);
        if ($len < 16) return false;
        if (preg_match('/^[A-Fa-f0-9]+$/', $s) === 1) return true;           // hex
        if (preg_match('/^[A-Za-z0-9\-_]+$/', $s) === 1) return true;        // url-safe b64-ish
        return false;
    }
}
