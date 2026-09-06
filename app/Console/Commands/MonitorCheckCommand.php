<?php

namespace App\Console\Commands;

use App\Services\StbMonitorService;
use App\Services\WebHealthService;
use Illuminate\Console\Command;

class MonitorCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitor:check {--all : Check all websites and record system metrics}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check health of all monitored websites and capture STB system metrics';

    /**
     * Execute the console command.
     */
    public function handle(StbMonitorService $stbMonitor, WebHealthService $webHealth): int
    {
        $this->info('Starting STB System & Web Health probe...');

        // 1. Snapshot system metrics
        $metrics = $stbMonitor->getSystemMetrics();
        $this->line("CPU: {$metrics['cpu']['usage_percent']}% | RAM: {$metrics['ram']['usage_percent']}% | Temp: {$metrics['temperature']['celsius']}°C | Disk Load: {$metrics['disk_io']['load_percent']}%");

        // 2. Check all active websites
        $sites = $webHealth->checkAllSites();
        $this->info("Checked {$sites->count()} websites.");

        foreach ($sites as $site) {
            $status = $site->is_online ? '<fg=green>ONLINE</>' : '<fg=red>OFFLINE</>';
            $this->line(sprintf(
                '[%s] %-28s %-6s (HTTP %s - %.1fms)',
                $site->stack_type,
                $site->name,
                $status,
                $site->last_status_code ?? 'ERR',
                $site->last_response_time_ms ?? 0
            ));
        }

        $this->info('Monitor check completed successfully.');

        return Command::SUCCESS;
    }
}
