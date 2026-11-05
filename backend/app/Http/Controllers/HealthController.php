<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HealthController extends Controller
{
    /**
     * Periksa kesehatan sistem secara menyeluruh (Basis Data & Cache).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function check(Request $request): JsonResponse
    {
        $isHealthy = true;
        $checks = [];

        // 1. Pemeriksaan Koneksi Basis Data
        try {
            $dbStart = microtime(true);
            DB::connection()->getPdo();
            DB::select('SELECT 1');
            $dbLatencyMs = round((microtime(true) - $dbStart) * 1000, 2);

            $checks['database'] = [
                'status'     => 'healthy',
                'message'    => 'Koneksi basis data berhasil terhubung.',
                'latency_ms' => $dbLatencyMs,
            ];
        } catch (\Throwable $e) {
            $isHealthy = false;
            $checks['database'] = [
                'status'  => 'unhealthy',
                'message' => 'Gagal menghubungi basis data: ' . $e->getMessage(),
            ];

            // Log terstruktur saat basis data tidak dapat dihubungi (Level: ERROR)
            Log::error('Gagal menghubungi basis data pada health check', [
                'action'    => 'health_check',
                'actor'     => [
                    'user' => $request->user()?->id ?? 'system/healthcheck',
                    'ip'   => $request->ip(),
                ],
                'component' => 'database',
                'reason'    => $e->getMessage(),
            ]);
        }

        // 2. Pemeriksaan Layanan Cache
        try {
            $cacheStart = microtime(true);
            $testKey = 'healthcheck_' . uniqid();
            Cache::put($testKey, 'ok', 10);
            $cachedVal = Cache::get($testKey);
            Cache::forget($testKey);

            if ($cachedVal !== 'ok') {
                throw new \RuntimeException('Nilai cache yang dibaca tidak sesuai verifikasi.');
            }

            $cacheLatencyMs = round((microtime(true) - $cacheStart) * 1000, 2);

            $checks['cache'] = [
                'status'     => 'healthy',
                'message'    => 'Layanan cache beroperasi dengan normal.',
                'latency_ms' => $cacheLatencyMs,
            ];
        } catch (\Throwable $e) {
            $isHealthy = false;
            $checks['cache'] = [
                'status'  => 'unhealthy',
                'message' => 'Layanan cache bermasalah: ' . $e->getMessage(),
            ];

            // Log terstruktur saat cache mengalami kegagalan (Level: ERROR)
            Log::error('Kegagalan layanan cache pada health check', [
                'action'    => 'health_check',
                'actor'     => [
                    'user' => $request->user()?->id ?? 'system/healthcheck',
                    'ip'   => $request->ip(),
                ],
                'component' => 'cache',
                'reason'    => $e->getMessage(),
            ]);
        }

        $statusCode = $isHealthy ? 200 : 503;
        $overallStatus = $isHealthy ? 'healthy' : 'unhealthy';

        return response()->json([
            'status'      => $overallStatus,
            'timestamp'   => now()->toIso8601String(),
            'environment' => config('app.env'),
            'checks'      => $checks,
        ], $statusCode);
    }
}
