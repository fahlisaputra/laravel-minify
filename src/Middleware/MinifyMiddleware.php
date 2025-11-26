<?php

namespace Fahlisaputra\Minify\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Unified wrapper middleware that executes the three minify middlewares
 * (CSS, JavaScript, HTML) in a controlled order by calling their handle()
 * methods with a closure that returns the current response.
 *
 * Rationale:
 * - preserves encapsulation (apply() stays protected)
 * - reuses existing logic and config fallbacks in each middleware
 * - minimal surface for regressions
 */
class MinifyMiddleware
{
    /**
     * Handle the incoming request and run CSS, JS, then HTML minifiers.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        // First, get the initial response by letting the app run as usual
        $response = $next($request);

        // If the response is not a Response instance, don't touch it
        if (! $response instanceof Response) {
            return $response;
        }

        // Instantiate existing middlewares (they extend Minifier and implement handle())
        $cssMiddleware  = new MinifyCssMiddleware();
        $jsMiddleware   = new MinifyJavascriptMiddleware();
        $htmlMiddleware = new MinifyHtmlMiddleware();

        // Helper closure factory that returns a closure resolving to $response
        // This allows calling $middleware->handle($request, $closure) so that
        // each middleware runs its own shouldProcessMinify / apply flow.
        $makeNext = fn($resp) => function () use ($resp) {
            return $resp;
        };

        // 1) Run CSS middleware: it will read current response and return possibly modified response.
        $response = $cssMiddleware->handle($request, $makeNext($response));

        // 2) Run JS middleware
        $response = $jsMiddleware->handle($request, $makeNext($response));

        // 3) Run HTML middleware
        return $htmlMiddleware->handle($request, $makeNext($response));
    }
}
