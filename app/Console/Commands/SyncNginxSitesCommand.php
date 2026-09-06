<?php

namespace App\Console\Commands;

use App\Services\NginxService;
use Illuminate\Console\Command;

class SyncNginxSitesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitor:sync-nginx {--no-check : Do not run immediate health check probe on synced sites}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan active Nginx virtual hosts and synchronize them into the website monitoring table';

    /**
     * Execute the console command.
     */
    public function handle(NginxService $nginxService): int
    {
        $this->info('Scanning Nginx virtual hosts...');

        $autoCheck = ! $this->option('no-check');
        $result = $nginxService->syncVhostsToMonitoredSites(autoCheck: $autoCheck);

        $this->info($result['message']);

        $headers = ['ID', 'Name', 'URL', 'Stack', 'Port', 'Status'];
        $rows = [];

        foreach ($result['sites'] as $site) {
            $status = $site->is_online ? '<fg=green>ONLINE</>' : '<fg=yellow>STANDBY/OFFLINE</>';
            $rows[] = [
                $site->id,
                $site->name,
                $site->url,
                $site->stack_type,
                $site->port ?? '-',
                $status,
            ];
        }

        $this->table($headers, $rows);

        return Command::SUCCESS;
    }
}
