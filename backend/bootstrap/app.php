<?php

use App\Http\Controllers\HealthController;
use App\Http\Controllers\MetricsController;
use App\Http\Middleware\PrometheusMetricsMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Daftarkan endpoint /health dengan middleware api (stateless, bebas session database/csrf)
            Route::middleware('api')
                ->get('/health', [HealthController::class, 'check']);

            // Daftarkan endpoint /metrics standar untuk Prometheus scraper
            Route::get('/metrics', [MetricsController::class, 'metrics']);
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Pasang middleware instrumen metrik Prometheus secara global
        $middleware->append(PrometheusMetricsMiddleware::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*')
                || $request->is('health')
                || $request->is('health/*')
                || $request->expectsJson(),
        );
    })->create();
