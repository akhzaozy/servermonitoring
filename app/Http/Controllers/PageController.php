<?php

namespace App\Http\Controllers;

use App\Models\MonitoredSite;
use App\Models\Server;
use App\Services\BackupService;
use App\Services\NginxService;
use App\Services\WebHealthService;
use App\Support\SitePayload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PageController extends Controller
{
    public function __construct(
        protected WebHealthService $webHealth,
        protected NginxService $nginx,
        protected BackupService $backup,
    ) {}

    public function dashboard(): View
    {
        $sites = SitePayload::all($this->webHealth);

        return view('pages.dashboard', ['sites' => $sites]);
    }

    public function websites(): View
    {
        $this->bootstrapSites();

        return view('pages.websites', [
            'sites' => SitePayload::all($this->webHealth),
            'nginx' => $this->nginx->getNginxStatus(),
        ]);
    }

    public function backups(): View
    {
        return view('pages.backups', ['backup' => $this->backup->getBackupStatus()]);
    }

    public function servers(): View
    {
        return view('pages.servers');
    }

    public function terminal(): View
    {
        return view('pages.terminal', [
            'terminalEnabled' => (bool) config('monitor.terminal.enabled'),
        ]);
    }

    public function selectServer(Request $request, Server $server): RedirectResponse
    {
        $request->session()->put('server_id', $server->id);

        $back = url()->previous(route('dashboard'));

        return redirect($back);
    }

    /**
     * Jika belum ada situs terdaftar, coba auto-discover dari konfigurasi Nginx.
     */
    protected function bootstrapSites(): void
    {
        if (MonitoredSite::count() === 0) {
            try {
                $this->nginx->syncVhostsToMonitoredSites(autoCheck: true);
            } catch (\Throwable) {
                // lanjut tanpa sinkronisasi
            }
        }
    }
}
