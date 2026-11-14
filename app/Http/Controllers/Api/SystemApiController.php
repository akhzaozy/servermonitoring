<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Server;
use App\Models\SystemMetricLog;
use App\Services\AgentClient;
use App\Services\NginxService;
use App\Services\StbMonitorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SystemApiController extends Controller
{
    public function __construct(
        protected StbMonitorService $stbMonitor,
        protected NginxService $nginxService
    ) {}

    /**
     * Metrik real-time + riwayat untuk server terpilih (?server=ID, default: lokal).
     */
    public function getMetrics(Request $request, AgentClient $agent): JsonResponse
    {
        $server = Server::resolve($request->integer('server') ?: null);

        if ($server->isLocal()) {
            $metrics = $this->stbMonitor->getSystemMetrics();
            $serverId = null;
        } else {
            try {
                $metrics = $agent->metrics($server);
            } catch (\Throwable $e) {
                return response()->json(['online' => false, 'message' => $e->getMessage()], 502);
            }
            $this->stbMonitor->recordSnapshot($metrics, $server->id);
            $serverId = $server->id;
        }

        $metrics['history'] = SystemMetricLog::where('server_id', $serverId)
            ->latest('id')
            ->take(60)
            ->get()
            ->reverse()
            ->values()
            ->map(fn ($log) => [
                'time' => $log->recorded_at->format('H:i:s'),
                'cpu' => $log->cpu_usage_percent,
                'ram' => $log->ram_usage_percent,
                'disk' => $log->disk_usage_percent,
                'disk_read' => $log->disk_read_kb_s,
                'disk_write' => $log->disk_write_kb_s,
                'temp' => $log->temperature_celsius,
            ]);

        $metrics['online'] = true;
        $metrics['server'] = ['id' => $server->id, 'name' => $server->name, 'type' => $server->type];

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

    /**
     * Scan and synchronize Nginx virtual hosts into MonitoredSite database.
     */
    public function syncNginx(): JsonResponse
    {
        $result = $this->nginxService->syncVhostsToMonitoredSites(autoCheck: true);

        return response()->json($result);
    }
}
