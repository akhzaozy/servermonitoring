<?php

namespace App\Support;

use App\Models\MonitoredSite;
use App\Services\WebHealthService;
use Illuminate\Support\Collection;

class SitePayload
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public static function all(WebHealthService $health): Collection
    {
        return MonitoredSite::orderBy('id')->get()->map(function (MonitoredSite $site) use ($health) {
            $stats = $health->getSiteUptimeStats($site);

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
        })->values();
    }
}
