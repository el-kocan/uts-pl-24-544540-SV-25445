<?php

namespace App\Http\Controllers;

use App\Services\MetricsService;
use Illuminate\Http\Response;

class MetricsController extends Controller
{
    public function __construct(
        protected MetricsService $metricsService
    ) {}

    /**
     * Endpoint /metrics untuk di-scrape oleh Prometheus.
     */
    public function metrics(): Response
    {
        $content = $this->metricsService->renderPrometheus();

        return response($content, 200, [
            'Content-Type' => 'text/plain; version=0.0.4; charset=utf-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }
}

