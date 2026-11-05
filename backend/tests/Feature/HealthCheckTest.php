<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_health_check_returns_200_when_all_services_are_healthy(): void
    {
        $response = $this->getJson('/health');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'healthy',
            ])
            ->assertJsonStructure([
                'status',
                'timestamp',
                'environment',
                'checks' => [
                    'database' => ['status', 'message'],
                    'cache'    => ['status', 'message'],
                ],
            ]);

        $this->assertEquals('healthy', $response->json('checks.database.status'));
        $this->assertEquals('healthy', $response->json('checks.cache.status'));
    }

    /** @test */
    public function test_api_health_check_endpoint_also_works(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'healthy',
            ]);
    }

    /** @test */
    public function test_health_check_returns_503_when_database_fails(): void
    {
        // Mock DB connection to simulate failure
        DB::shouldReceive('connection')
            ->once()
            ->andThrow(new \Exception('Connection refused: SQLSTATE[HY000] [2002]'));

        $response = $this->getJson('/health');

        $response->assertStatus(503)
            ->assertJson([
                'status' => 'unhealthy',
            ]);

        $this->assertEquals('unhealthy', $response->json('checks.database.status'));
        $this->assertStringContainsString('Connection refused', $response->json('checks.database.message'));
    }

    /** @test */
    public function test_health_check_returns_503_when_cache_fails(): void
    {
        // Mock Cache to simulate failure
        Cache::shouldReceive('put')
            ->once()
            ->andThrow(new \Exception('Cache store unavailable'));

        $response = $this->getJson('/health');

        $response->assertStatus(503)
            ->assertJson([
                'status' => 'unhealthy',
            ]);

        $this->assertEquals('unhealthy', $response->json('checks.cache.status'));
        $this->assertStringContainsString('Cache store unavailable', $response->json('checks.cache.message'));
    }
}
