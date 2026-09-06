<?php

namespace App\Services;

use App\Models\MonitoredSite;
use App\Models\SiteCheckLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class WebHealthService
{
    /**
     * Check all active monitored websites.
     *
     * @return Collection<int, MonitoredSite>
     */
    public function checkAllSites(): Collection
    {
        $sites = MonitoredSite::where('is_active', true)->get();

        foreach ($sites as $site) {
            $this->checkSite($site);
        }

        return $sites->fresh();
    }

    /**
     * Probe a single website and record its health.
     *
     * @return array<string, mixed>
     */
    public function checkSite(MonitoredSite $site): array
    {
        $url = $site->url;
        $startTime = microtime(true);
        $statusCode = null;
        $isOnline = false;
        $errorMessage = null;
        $sslValid = null;
        $sslExpiresAt = null;

        try {
            // Setup cURL probe with timeout suitable for local STB and remote sites
            $response = Http::timeout(4)
                ->connectTimeout(3)
                ->withoutVerifying() // Useful for self-signed or local IP vhosts
                ->withHeaders([
                    'User-Agent' => 'STB-Monitor-HealthCheck/1.0',
                ])
                ->get($url);

            $responseTimeMs = round((microtime(true) - $startTime) * 1000, 1);
            $statusCode = $response->status();

            // Status codes 2xx, 3xx are considered online
            $isOnline = ($statusCode >= 200 && $statusCode < 400);

            // If an expected keyword is required, check body
            if ($isOnline && ! empty($site->expected_keyword)) {
                $body = $response->body();
                if (! str_contains($body, $site->expected_keyword)) {
                    $isOnline = false;
                    $errorMessage = "Keyword '{$site->expected_keyword}' not found in body";
                }
            }

            // SSL certificate check if https
            if (str_starts_with(strtolower($url), 'https://')) {
                $sslInfo = $this->checkSslCertificate($url);
                $sslValid = $sslInfo['valid'];
                $sslExpiresAt = $sslInfo['expires_at'];
            }

        } catch (\Throwable $e) {
            $responseTimeMs = round((microtime(true) - $startTime) * 1000, 1);
            $statusCode = 0;
            $isOnline = false;
            $errorMessage = $this->sanitizeErrorMessage($e->getMessage());
        }

        // Update site model
        $site->update([
            'last_status_code' => $statusCode,
            'last_response_time_ms' => $responseTimeMs,
            'is_online' => $isOnline,
            'last_checked_at' => now(),
            'last_error' => $errorMessage,
            'ssl_valid' => $sslValid,
            'ssl_expires_at' => $sslExpiresAt,
        ]);

        // Record check log
        SiteCheckLog::create([
            'monitored_site_id' => $site->id,
            'status_code' => $statusCode,
            'response_time_ms' => $responseTimeMs,
            'is_online' => $isOnline,
            'error_message' => $errorMessage,
            'checked_at' => now(),
        ]);

        // Cleanup old logs for this site (keep last 150 checks)
        $logCount = SiteCheckLog::where('monitored_site_id', $site->id)->count();
        if ($logCount > 180) {
            SiteCheckLog::where('monitored_site_id', $site->id)
                ->orderBy('id', 'asc')
                ->limit($logCount - 150)
                ->delete();
        }

        return [
            'site_id' => $site->id,
            'name' => $site->name,
            'url' => $site->url,
            'is_online' => $isOnline,
            'status_code' => $statusCode,
            'response_time_ms' => $responseTimeMs,
            'error' => $errorMessage,
        ];
    }

    /**
     * Inspect SSL Certificate expiration.
     *
     * @return array{valid: bool, expires_at: ?string, days_left: ?int}
     */
    private function checkSslCertificate(string $url): array
    {
        $host = parse_url($url, PHP_URL_HOST);
        $port = parse_url($url, PHP_URL_PORT) ?: 443;

        $g = stream_context_create([
            'ssl' => [
                'capture_peer_cert' => true,
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);

        $client = @stream_socket_client(
            "ssl://{$host}:{$port}",
            $errno,
            $errstr,
            2,
            STREAM_CLIENT_CONNECT,
            $g
        );

        if ($client) {
            $cont = stream_context_get_params($client);
            if (! empty($cont['options']['ssl']['peer_certificate'])) {
                $cert = openssl_x509_parse($cont['options']['ssl']['peer_certificate']);
                fclose($client);

                if (isset($cert['validTo_time_t'])) {
                    $validTo = $cert['validTo_time_t'];
                    $expiresAt = date('Y-m-d H:i:s', $validTo);
                    $daysLeft = (int) round(($validTo - time()) / 86400);

                    return [
                        'valid' => $daysLeft > 0,
                        'expires_at' => $expiresAt,
                        'days_left' => $daysLeft,
                    ];
                }
            }
            fclose($client);
        }

        return [
            'valid' => false,
            'expires_at' => null,
            'days_left' => null,
        ];
    }

    /**
     * Calculate 24h uptime percentage and compile history chunks.
     *
     * @return array{uptime_percentage: float, history_bars: array<int, array{online: bool, ms: float, time: string}>}
     */
    public function getSiteUptimeStats(MonitoredSite $site): array
    {
        $logs = SiteCheckLog::where('monitored_site_id', $site->id)
            ->where('checked_at', '>=', now()->subHours(24))
            ->orderBy('id', 'desc')
            ->take(30)
            ->get()
            ->reverse()
            ->values();

        if ($logs->isEmpty()) {
            return [
                'uptime_percentage' => 100.0,
                'history_bars' => [],
            ];
        }

        $total = $logs->count();
        $onlineCount = $logs->where('is_online', true)->count();
        $percentage = round(($onlineCount / $total) * 100, 1);

        $bars = $logs->map(function ($log) {
            return [
                'online' => $log->is_online,
                'ms' => $log->response_time_ms,
                'status_code' => $log->status_code,
                'time' => $log->checked_at->format('H:i:s'),
            ];
        })->toArray();

        return [
            'uptime_percentage' => $percentage,
            'history_bars' => $bars,
        ];
    }

    /**
     * Sanitize and format error message for user display.
     */
    private function sanitizeErrorMessage(string $msg): string
    {
        if (str_contains($msg, 'Connection refused')) {
            return 'Koneksi ditolak (Port / Service Offline)';
        }
        if (str_contains($msg, 'timed out') || str_contains($msg, 'Operation timed out')) {
            return 'Timeout (Server Lambat / Tidak Merespon)';
        }
        if (str_contains($msg, 'Could not resolve host')) {
            return 'DNS gagal di-resolve';
        }
        if (str_contains($msg, 'Failed to connect')) {
            return 'Gagal terhubung ke host/port';
        }

        return mb_substr($msg, 0, 120);
    }
}
