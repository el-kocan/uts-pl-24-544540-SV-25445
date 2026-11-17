<?php

namespace App\Services;

use PDO;
use Throwable;

class MetricsService
{
    protected ?PDO $pdo = null;
    protected string $dbPath;

    public function __construct(?string $dbPath = null)
    {
        $this->dbPath = $dbPath ?? storage_path('framework/metrics.sqlite');
    }

    /**
     * Inisialisasi koneksi SQLite lokal untuk penyimpanan metrik.
     */
    protected function getPdo(): PDO
    {
        if ($this->pdo !== null) {
            return $this->pdo;
        }

        $dir = dirname($this->dbPath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $this->pdo = new PDO('sqlite:' . $this->dbPath, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 2,
        ]);

        $this->pdo->exec('PRAGMA journal_mode = WAL;');
        $this->pdo->exec('PRAGMA busy_timeout = 2000;');

        $this->pdo->exec('
            CREATE TABLE IF NOT EXISTS http_requests_total (
                method TEXT NOT NULL,
                status INTEGER NOT NULL,
                path TEXT NOT NULL,
                count INTEGER NOT NULL DEFAULT 0,
                PRIMARY KEY (method, status, path)
            );
        ');

        $this->pdo->exec('
            CREATE TABLE IF NOT EXISTS http_request_duration_seconds (
                method TEXT NOT NULL,
                path TEXT NOT NULL,
                total_duration REAL NOT NULL DEFAULT 0.0,
                count INTEGER NOT NULL DEFAULT 0,
                PRIMARY KEY (method, path)
            );
        ');

        return $this->pdo;
    }

    /**
     * Catat satu HTTP request ke dalam penyimpanan metrik terstruktur.
     */
    public function recordRequest(string $method, int $status, string $path, float $durationSeconds): void
    {
        try {
            $pdo = $this->getPdo();

            // Normalisasi path agar tidak ada ID acak / query string (menjaga kardinalitas metrik)
            $cleanPath = $this->normalizePath($path);
            $cleanMethod = strtoupper($method);

            // Increment request counter
            $stmt = $pdo->prepare('
                INSERT INTO http_requests_total (method, status, path, count)
                VALUES (:method, :status, :path, 1)
                ON CONFLICT(method, status, path) DO UPDATE SET count = count + 1
            ');
            $stmt->execute([
                ':method' => $cleanMethod,
                ':status' => $status,
                ':path' => $cleanPath,
            ]);

            // Update duration summary
            $stmtDur = $pdo->prepare('
                INSERT INTO http_request_duration_seconds (method, path, total_duration, count)
                VALUES (:method, :path, :duration, 1)
                ON CONFLICT(method, path) DO UPDATE SET
                    total_duration = total_duration + :duration,
                    count = count + 1
            ');
            $stmtDur->execute([
                ':method' => $cleanMethod,
                ':path' => $cleanPath,
                ':duration' => max(0.0, $durationSeconds),
            ]);
        } catch (Throwable $e) {
            // Kegagalan pencatatan metrik tidak boleh mengganggu respons aplikasi
        }
    }

    /**
     * Normalisasi path URL untuk mencegah kardinalitas tinggi dan menjaga privasi.
     */
    public function normalizePath(string $path): string
    {
        $path = '/' . ltrim(parse_url($path, PHP_URL_PATH) ?? '', '/');
        // Ganti ID numerik atau UUID dengan placeholder {id}
        $path = preg_replace('/\/[0-9]+(\/|$)/', '/{id}$1', $path);
        $path = preg_replace('/\/[0-9a-fA-F-]{36}(\/|$)/', '/{id}$1', $path);

        return $path ?: '/';
    }

    /**
     * Format metrik ke standar Prometheus Text-Based Exposition Format (v0.0.4).
     */
    public function renderPrometheus(): string
    {
        $output = [];

        $output[] = '# HELP laravel_app_info Informasi metadata aplikasi Laravel.';
        $output[] = '# TYPE laravel_app_info gauge';
        $output[] = 'laravel_app_info{app="lab_inventory_tracker",framework="laravel"} 1';
        $output[] = '';

        $output[] = '# HELP laravel_http_requests_total Jumlah kumulatif HTTP requests yang diproses aplikasi Laravel.';
        $output[] = '# TYPE laravel_http_requests_total counter';

        try {
            $pdo = $this->getPdo();
            $stmt = $pdo->query('SELECT method, status, path, count FROM http_requests_total ORDER BY method, status, path');
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($rows)) {
                // Berikan entri default 0 jika belum ada request
                $output[] = 'laravel_http_requests_total{method="GET",status="200",path="/"} 0';
            } else {
                foreach ($rows as $row) {
                    $output[] = sprintf(
                        'laravel_http_requests_total{method="%s",status="%d",path="%s"} %d',
                        addcslashes($row['method'], "\"\\\n"),
                        (int) $row['status'],
                        addcslashes($row['path'], "\"\\\n"),
                        (int) $row['count']
                    );
                }
            }
        } catch (Throwable $e) {
            $output[] = 'laravel_http_requests_total{method="GET",status="200",path="/"} 0';
        }

        $output[] = '';
        $output[] = '# HELP laravel_http_request_duration_seconds_total Total durasi pemrosesan HTTP request dalam detik.';
        $output[] = '# TYPE laravel_http_request_duration_seconds_total counter';

        try {
            $pdo = $this->getPdo();
            $stmt = $pdo->query('SELECT method, path, total_duration, count FROM http_request_duration_seconds ORDER BY method, path');
            $durRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($durRows as $row) {
                $output[] = sprintf(
                    'laravel_http_request_duration_seconds_total{method="%s",path="%s"} %.6f',
                    addcslashes($row['method'], "\"\\\n"),
                    addcslashes($row['path'], "\"\\\n"),
                    (float) $row['total_duration']
                );
                $output[] = sprintf(
                    'laravel_http_request_duration_seconds_count{method="%s",path="%s"} %d',
                    addcslashes($row['method'], "\"\\\n"),
                    addcslashes($row['path'], "\"\\\n"),
                    (int) $row['count']
                );
            }
        } catch (Throwable $e) {
            // Abaikan jika tabel kosong
        }

        $output[] = '';

        return implode("\n", $output);
    }
}

