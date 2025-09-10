<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Routing\Router;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class RequestLogger
{
    public function __construct(private Router $router) {}

    public function handle(Request $request, Closure $next): SymfonyResponse
    {
        $start    = microtime(true);
        $response = $next($request);

        $pathInfo = $request->getPathInfo();
        $isApi    = str_starts_with($pathInfo, '/api');
        $channel  = $isApi ? 'api' : 'web';

        // Map HTTP verb -> emoji
        $emoji = match ($request->getMethod()) {
            'GET'    => '👀',
            'POST'   => '📩',
            'PUT', 'PATCH' => '✏️',
            'DELETE' => '🗑️',
            default  => '➡️',
        };

        $routeName = $this->router->currentRouteName() ?? 'N/A';


        $user = Auth::user();
        $userLabel = $user
            ? sprintf('%s (%s)', trim(($user->name ?? $user->full_name ?? $user->email ?? 'user')), $user->getAuthIdentifier())
            : 'guest';

        $durationMs = (int) round((microtime(true) - $start) * 1000);

        // Pretty, multi-line log message (no IP)
        $logMessage = implode("\n", [
            sprintf('%s %s', $emoji, $request->getMethod()),
            '  URI: '       . $pathInfo,
            '  Full URL: '  . $request->getUri(),
            '  Route: '     . $routeName,
            '  Status: '    . $response->getStatusCode(),
            '  User: '      . $userLabel,
            '  Duration: '  . $durationMs . ' ms',
            '  UA: '        . ($request->userAgent() ?? 'N/A'),
        ]);

        Log::channel($channel)->info($logMessage);

        return $response;
    }
}
