<?php

namespace App\Http\Controllers;

use App\Models\MonitoredSite;
use App\Services\NginxService;
use App\Services\StbMonitorService;
use App\Services\WebHealthService;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected StbMonitorService $stbMonitor,
        protected NginxService $nginxService,
        protected WebHealthService $webHealthService
    ) {}

    public function index(): View
    {
        $metrics = $this->stbMonitor->getSystemMetrics();
        $nginxStatus = $this->nginxService->getNginxStatus();
        // If no sites exist yet, automatically auto-discover and sync from Nginx configs
        if (MonitoredSite::count() === 0) {
            try {
                $this->nginxService->syncVhostsToMonitoredSites(autoCheck: true);
            } catch (\Throwable $e) {
                // Silently continue if sync encounters an error
            }
        }

        $sites = MonitoredSite::orderBy('id', 'asc')->get();

        $sitesWithStats = $sites->map(function (MonitoredSite $site) {
            $stats = $this->webHealthService->getSiteUptimeStats($site);

            return [
                'id' => $site->id,
                'name' => $site->name,
                'url' => $site->url,
                'stack_type' => $site->stack_type,
                'port' => $site->port,
                'is_active' => $site->is_active,
                'is_online' => $site->is_online,
                'last_status_code' => $site->last_status_code,
                'last_response_time_ms' => $site->last_response_time_ms,
                'last_checked_at' => $site->last_checked_at?->diffForHumans() ?? 'Belum dicek',
                'last_error' => $site->last_error,
                'ssl_valid' => $site->ssl_valid,
                'ssl_expires_at' => $site->ssl_expires_at?->format('d M Y'),
                'uptime_percentage' => $stats['uptime_percentage'],
                'history_bars' => $stats['history_bars'],
            ];
        });

        return view('dashboard', [
            'metrics' => $metrics,
            'nginxStatus' => $nginxStatus,
            'sites' => $sitesWithStats,
        ]);
    }
}
