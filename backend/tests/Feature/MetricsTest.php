<?php

namespace Tests\Feature;

use App\Services\MetricsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MetricsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_metrics_endpoint_returns_200_with_prometheus_format(): void
    {
        $response = $this->get('/metrics');

        $response->assertStatus(200);
        $this->assertStringContainsString('text/plain', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('laravel_app_info', $response->getContent());
        $this->assertStringContainsString('laravel_http_requests_total', $response->getContent());
    }

    /** @test */
    public function test_api_metrics_endpoint_also_returns_prometheus_metrics(): void
    {
        $response = $this->get('/api/metrics');

        $response->assertStatus(200);
        $this->assertStringContainsString('text/plain', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('laravel_http_requests_total', $response->getContent());
    }

    /** @test */
    public function test_http_requests_are_instrumented_and_incremented(): void
    {
        // Panggil endpoint /health
        $this->getJson('/health')->assertStatus(200);

        // Periksa apakah tercatat pada /metrics
        $metricsResponse = $this->get('/metrics');
        $metricsResponse->assertStatus(200);

        $body = $metricsResponse->getContent();
        $this->assertStringContainsString('laravel_http_requests_total{', $body);
        $this->assertStringContainsString('method="GET"', $body);
        $this->assertStringContainsString('status="200"', $body);
    }

    /** @test */
    public function test_metrics_service_normalizes_numeric_ids(): void
    {
        $service = new MetricsService();
        $this->assertEquals('/api/equipments/{id}', $service->normalizePath('/api/equipments/123'));
        $this->assertEquals('/health', $service->normalizePath('/health'));
        $this->assertEquals('/api/equipments/{id}/edit', $service->normalizePath('/api/equipments/999/edit'));
    }
}

