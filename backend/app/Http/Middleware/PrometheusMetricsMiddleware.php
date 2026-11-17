<?php

namespace App\Http\Middleware;

use App\Services\MetricsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PrometheusMetricsMiddleware
{
    public function __construct(
        protected MetricsService $metricsService
    ) {}

    /**
     * Handle an incoming request and record HTTP metrics.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        $response = $next($request);

        $duration = microtime(true) - $startTime;

        // Path yang diukur: gunakan URI route terdefinisi jika ada, atau URL path
        $path = $request->route()?->uri() ?? $request->path();
        if (!str_starts_with($path, '/')) {
            $path = '/' . $path;
        }

        $this->metricsService->recordRequest(
            $request->method(),
            $response->getStatusCode(),
            $path,
            $duration
        );

        return $response;
    }
}

