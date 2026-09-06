<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SystemMetricLog;
use App\Services\NginxService;
use App\Services\StbMonitorService;
use Illuminate\Http\JsonResponse;

class SystemApiController extends Controller
{
    public function __construct(
        protected StbMonitorService $stbMonitor,
        protected NginxService $nginxService
    ) {}

    /**
     * Get real-time system metrics (CPU, RAM, Disk, Disk IO, Temp).
     */
    public function getMetrics(): JsonResponse
    {
        $metrics = $this->stbMonitor->getSystemMetrics();

        // Get last 20 history points for sparkline charts
        $history = SystemMetricLog::latest('id')
            ->take(20)
            ->get()
            ->reverse()
            ->values()
            ->map(fn ($log) => [
                'time' => $log->recorded_at->format('H:i:s'),
                'cpu' => $log->cpu_usage_percent,
                'ram' => $log->ram_usage_percent,
                'disk_read' => $log->disk_read_kb_s,
                'disk_write' => $log->disk_write_kb_s,
                'temp' => $log->temperature_celsius,
            ]);

        $metrics['history'] = $history;

        return response()->json($metrics);
    }

    /**
     * Get Nginx service status and vhosts.
     */
    public function getNginxInfo(): JsonResponse
    {
        $status = $this->nginxService->getNginxStatus();

        return response()->json($status);
    }

    /**
     * Test Nginx configuration (nginx -t).
     */
    public function testNginx(): JsonResponse
    {
        $res = $this->nginxService->testConfiguration();

        return response()->json($res);
    }

    /**
     * Reload Nginx daemon.
     */
    public function reloadNginx(): JsonResponse
    {
        $res = $this->nginxService->reloadService();

        return response()->json($res);
    }
}
