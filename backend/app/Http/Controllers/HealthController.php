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
        try {
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
                    'message' => 'Layanan basis data tidak dapat dijangkau atau koneksi terputus.',
                ];

                // Log terstruktur saat basis data tidak dapat dihubungi (Level: ERROR)
                // Keamanan: Bebas dari email, IP, token, password, dan exception mentah berkredensial.
                Log::error('Koneksi basis data gagal saat pemeriksaan kesehatan', [
                    'action'    => 'health_check',
                    'component' => 'database',
                    'actor'     => [
                        'type' => 'system',
                        'role' => 'health_checker',
                    ],
                    'reason'    => 'Koneksi ke server basis data tidak dapat dibuat atau terputus',
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
                    'message' => 'Layanan cache mengalami kendala operasional.',
                ];

                // Log terstruktur saat cache bermasalah (Level: ERROR)
                Log::error('Layanan cache gagal saat pemeriksaan kesehatan', [
                    'action'    => 'health_check',
                    'component' => 'cache',
                    'actor'     => [
                        'type' => 'system',
                        'role' => 'health_checker',
                    ],
                    'reason'    => 'Operasi baca-tulis cache tidak dapat diselesaikan',
                ]);
            }

            $statusCode = $isHealthy ? 200 : 503;
            $overallStatus = $isHealthy ? 'healthy' : 'unhealthy';

            return response()->json([
                'status'      => $overallStatus,
                'timestamp'   => now()->toIso8601String(),
                'environment' => config('app.env'),
                'checks'      => $checks,
            ], $statusCode, [], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        } catch (\Throwable $fatal) {
            // Jaminan pengaman agar tidak pernah menghasilkan HTTP 500 HTML
            Log::error('Kegagalan sistem kritis saat pemeriksaan kesehatan', [
                'action'    => 'health_check',
                'component' => 'system',
                'actor'     => [
                    'type' => 'system',
                    'role' => 'health_checker',
                ],
                'reason'    => 'Kesalahan tak terduga dalam eksekusi health check',
            ]);

            return response()->json([
                'status'      => 'unhealthy',
                'timestamp'   => now()->toIso8601String(),
                'environment' => config('app.env'),
                'checks'      => [
                    'system' => [
                        'status'  => 'unhealthy',
                        'message' => 'Layanan pemeriksaan kesehatan sistem mengalami kendala tak terduga.',
                    ],
                ],
            ], 503, [], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        }
    }
}
