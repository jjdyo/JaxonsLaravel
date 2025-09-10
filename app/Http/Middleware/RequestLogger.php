<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class RequestLogger
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): SymfonyResponse
    {
        $start = microtime(true);

        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $next($request);

        // Channel: api vs web
        $pathInfo = $request->getPathInfo();
        $isApi = str_starts_with($pathInfo, '/api');
        $channel = $isApi ? 'api' : 'web';

        // @phpstan-ignore-next-line
        $routeName = Route::current()?->getName();

        $context = [
            'method'      => $request->getMethod(),
            'uri'         => '/' . ltrim($pathInfo, '/'),
            'query'       => $request->getQueryString(),
            'full_url'    => $request->getUri(),
            'ip'          => $request->getClientIp(),
            'user_id'     => Auth::id() ?? 'guest',
            'status'      => $response->getStatusCode(),
            'route'       => $routeName,
            'ua'          => $request->headers->get('User-Agent'),
            'duration_ms' => (int) round((microtime(true) - $start) * 1000),
        ];

        Log::channel($channel)->info('http_request', $context);

        return $response;
    }
}
