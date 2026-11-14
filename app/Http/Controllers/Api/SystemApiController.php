<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Server;
use App\Models\SystemMetricLog;
use App\Services\AgentClient;
use App\Services\NginxService;
use App\Services\SshServerService;
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
    public function getMetrics(Request $request, AgentClient $agent, SshServerService $ssh): JsonResponse
    {
        $server = Server::resolve($request->integer('server') ?: null);

        if ($server->isLocal()) {
            $metrics = $this->stbMonitor->getSystemMetrics();
            $serverId = null;
        } elseif ($server->isSsh()) {
            try {
                $metrics = $ssh->metrics($server);
            } catch (\Throwable $e) {
                return response()->json(['online' => false, 'message' => $e->getMessage()], 502);
            }
            $this->stbMonitor->recordSnapshot($metrics, $server->id);
            $serverId = $server->id;
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

    public function getNginxInfo(): JsonResponse
    {
        return response()->json($this->nginxService->getNginxStatus());
    }

    public function testNginx(): JsonResponse
    {
        return response()->json($this->nginxService->testConfiguration());
    }

    public function reloadNginx(): JsonResponse
    {
        return response()->json($this->nginxService->reloadService());
    }

    public function syncNginx(): JsonResponse
    {
        return response()->json($this->nginxService->syncVhostsToMonitoredSites(autoCheck: true));
    }
}
