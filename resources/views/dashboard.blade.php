@extends('layouts.app')

@section('title', 'STB Telemetry & Web Active Monitor')

@section('content')
<div class="container">
    <!-- Top Header Bar -->
    <header class="header-bar">
        <div class="brand-section">
            <div class="brand-logo">
                <i data-lucide="cpu" style="width: 24px; height: 24px; color: #fff;"></i>
            </div>
            <div>
                <div class="brand-title">
                    STB Monitor & Web Aktif
                    <span class="stb-badge">STB Server</span>
                </div>
                <div class="brand-sub">
                    <span id="headerUptimeText"><i data-lucide="clock" style="width: 14px; height: 14px; display: inline;"></i> Uptime: {{ $metrics['system']['uptime_formatted'] }}</span>
                    <span>•</span>
                    <span id="headerHostname">{{ $metrics['system']['hostname'] }} ({{ $metrics['system']['architecture'] }})</span>
                </div>
            </div>
        </div>

        <div class="header-actions">
            <!-- Auto-refresh Live Indicator -->
            <button id="refreshToggleBtn" class="live-indicator" onclick="toggleAutoRefresh()" title="Klik untuk Pause / Lanjutkan Auto Refresh">
                <span class="live-dot" id="liveDot"></span>
                <span id="refreshLabel">Live Telemetry (5s)</span>
            </button>

            <!-- Check All Websites Button -->
            <button class="btn btn-emerald" onclick="checkAllWebsites()">
                <i data-lucide="refresh-cw" id="checkAllIcon" style="width: 16px; height: 16px;"></i>
                <span>Cek Semua Web</span>
            </button>

            <!-- Nginx Inspector Modal Button -->
            <button class="btn" onclick="openNginxModal()">
                <i data-lucide="server" style="width: 16px; height: 16px; color: #06b6d4;"></i>
                <span>Nginx Service</span>
            </button>

            <!-- Add Website Button -->
            <button class="btn btn-primary" onclick="openAddSiteModal()">
                <i data-lucide="plus-circle" style="width: 16px; height: 16px;"></i>
                <span>Tambah Web</span>
            </button>

            <!-- Dark / Light Theme Toggle -->
            <button class="btn btn-icon" onclick="toggleTheme()" title="Ganti Tema">
                <i data-lucide="sun-moon" style="width: 18px; height: 18px;"></i>
            </button>
        </div>
    </header>

    <!-- STB Hardware Specs Ribbon -->
    <div class="spec-ribbon">
        <div class="glass-card spec-box">
            <div class="spec-icon icon-cpu">
                <i data-lucide="microchip" style="width: 22px; height: 22px;"></i>
            </div>
            <div class="spec-info">
                <span class="spec-label">Prosesor & Cores</span>
                <span class="spec-value">{{ $metrics['system']['model'] }}</span>
                <span class="font-mono" style="font-size: 0.72rem; color: var(--text-muted);">{{ $metrics['cpu']['cores'] }} Cores • {{ $metrics['system']['architecture'] }}</span>
            </div>
        </div>

        <div class="glass-card spec-box">
            <div class="spec-icon icon-os">
                <i data-lucide="layers" style="width: 22px; height: 22px;"></i>
            </div>
            <div class="spec-info">
                <span class="spec-label">Sistem Operasi</span>
                <span class="spec-value" style="font-size: 0.88rem;">{{ $metrics['system']['os'] }}</span>
                <span class="font-mono" style="font-size: 0.72rem; color: var(--text-muted);">Kernel {{ $metrics['system']['kernel'] }}</span>
            </div>
        </div>

        <div class="glass-card spec-box">
            <div class="spec-icon icon-temp">
                <i data-lucide="flame" style="width: 22px; height: 22px;"></i>
            </div>
            <div class="spec-info">
                <span class="spec-label">Suhu SoC STB</span>
                <span class="spec-value font-mono" id="specTempText">{{ $metrics['temperature']['celsius'] }} °C</span>
                <span style="font-size: 0.72rem; color: #10b981; font-weight: 600;" id="specTempStatus">Optimal Thermal Zone</span>
            </div>
        </div>

        <div class="glass-card spec-box">
            <div class="spec-icon icon-uptime">
                <i data-lucide="activity" style="width: 22px; height: 22px;"></i>
            </div>
            <div class="spec-info">
                <span class="spec-label">Disk Load / I/O Status</span>
                <span class="spec-value font-mono" id="specDiskIoText">R: {{ $metrics['disk_io']['read_kb_s'] }} KB/s • W: {{ $metrics['disk_io']['write_kb_s'] }} KB/s</span>
                <span style="font-size: 0.72rem; color: #06b6d4; font-weight: 600;" id="specDiskIoStatus">I/O Load: {{ $metrics['disk_io']['load_status'] }}</span>
            </div>
        </div>
    </div>

    <!-- 4 Main Hardware Metrics Grid -->
    <div class="metrics-grid">
        <!-- 1. CPU Card -->
        <div class="glass-card metric-card">
            <div class="metric-header">
                <span class="metric-title">
                    <i data-lucide="cpu" style="width: 16px; height: 16px; color: #818cf8;"></i>
                    Beban CPU
                </span>
                <span class="metric-tag font-mono" id="cpuLoadAvgTag" style="background: rgba(99, 102, 241, 0.15); color: #818cf8;">
                    Load: {{ implode(', ', $metrics['cpu']['load_avg']) }}
                </span>
            </div>
            <div class="metric-body">
                <div>
                    <span class="metric-value font-mono" id="cpuUsageVal">{{ $metrics['cpu']['usage_percent'] }}</span>
                    <span class="metric-unit">%</span>
                </div>
                <div class="metric-subtext font-mono" id="cpuCoresText">{{ $metrics['cpu']['cores'] }} Cores Active</div>
            </div>
            <div class="progress-bar-container">
                <div class="progress-bar-fill fill-indigo" id="cpuProgressBar" style="width: {{ $metrics['cpu']['usage_percent'] }}%;"></div>
            </div>
        </div>

        <!-- 2. STB Temperature Card -->
        <div class="glass-card metric-card">
            <div class="metric-header">
                <span class="metric-title">
                    <i data-lucide="thermometer" style="width: 16px; height: 16px; color: #fb7185;"></i>
                    Suhu SoC STB
                </span>
                <span class="metric-tag" id="tempTag" style="background: rgba(16, 185, 129, 0.15); color: #34d399;">
                    Normal
                </span>
            </div>
            <div class="metric-body">
                <div>
                    <span class="metric-value font-mono" id="tempUsageVal">{{ $metrics['temperature']['celsius'] }}</span>
                    <span class="metric-unit">°C</span>
                </div>
                <div class="metric-subtext" id="tempLimitText">Batas Aman: &lt; 75°C</div>
            </div>
            <div class="progress-bar-container">
                <div class="progress-bar-fill fill-rose" id="tempProgressBar" style="width: {{ min(100, ($metrics['temperature']['celsius'] / 85) * 100) }}%;"></div>
            </div>
        </div>

        <!-- 3. RAM Memory Card -->
        <div class="glass-card metric-card">
            <div class="metric-header">
                <span class="metric-title">
                    <i data-lucide="database" style="width: 16px; height: 16px; color: #34d399;"></i>
                    RAM Memory
                </span>
                <span class="metric-tag font-mono" id="ramMbTag" style="background: rgba(16, 185, 129, 0.15); color: #34d399;">
                    {{ $metrics['ram']['used_mb'] }} / {{ $metrics['ram']['total_mb'] }} MB
                </span>
            </div>
            <div class="metric-body">
                <div>
                    <span class="metric-value font-mono" id="ramUsageVal">{{ $metrics['ram']['usage_percent'] }}</span>
                    <span class="metric-unit">%</span>
                </div>
                <div class="metric-subtext font-mono" id="ramFreeText">Free: {{ $metrics['ram']['free_mb'] }} MB</div>
            </div>
            <div class="progress-bar-container">
                <div class="progress-bar-fill fill-emerald" id="ramProgressBar" style="width: {{ $metrics['ram']['usage_percent'] }}%;"></div>
            </div>
        </div>

        <!-- 4. Storage & Disk Load Card -->
        <div class="glass-card metric-card">
            <div class="metric-header">
                <span class="metric-title">
                    <i data-lucide="hard-drive" style="width: 16px; height: 16px; color: #22d3ee;"></i>
                    Storage & Disk Load
                </span>
                <span class="metric-tag font-mono" id="diskIoTag" style="background: rgba(6, 182, 212, 0.15); color: #22d3ee;">
                    I/O: {{ $metrics['disk_io']['load_percent'] }}%
                </span>
            </div>
            <div class="metric-body">
                <div>
                    <span class="metric-value font-mono" id="diskUsageVal">{{ $metrics['disk']['usage_percent'] }}</span>
                    <span class="metric-unit">%</span>
                </div>
                <div class="metric-subtext font-mono" id="diskUsedText">{{ $metrics['disk']['used_gb'] }} GB / {{ $metrics['disk']['total_gb'] }} GB</div>
            </div>
            <div class="progress-bar-container">
                <div class="progress-bar-fill fill-cyan" id="diskProgressBar" style="width: {{ $metrics['disk']['usage_percent'] }}%;"></div>
            </div>
        </div>
    </div>

    <!-- Live Telemetry Area Chart & Nginx Panel -->
    <div class="charts-row">
        <!-- Live Real-Time Telemetry Chart -->
        <div class="glass-card chart-card">
            <div class="chart-header">
                <div class="chart-title">
                    <i data-lucide="trending-up" style="width: 20px; height: 20px; color: #6366f1;"></i>
                    Live STB Telemetry Trend (Real-time CPU, RAM & Suhu)
                </div>
                <div class="chart-legend">
                    <div class="legend-item">
                        <span class="legend-dot" style="background: #6366f1;"></span>
                        <span>CPU (%)</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-dot" style="background: #10b981;"></span>
                        <span>RAM (%)</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-dot" style="background: #fb7185;"></span>
                        <span>Suhu (°C)</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-dot" style="background: #06b6d4;"></span>
                        <span>Disk I/O Load</span>
                    </div>
                </div>
            </div>
            <div id="liveTelemetryChart" style="min-height: 250px; width: 100%;"></div>
        </div>

        <!-- Nginx Quick Status Card -->
        <div class="glass-card chart-card nginx-panel">
            <div class="chart-header">
                <div class="chart-title">
                    <i data-lucide="shield-check" style="width: 20px; height: 20px; color: #06b6d4;"></i>
                    Status Daemon Nginx
                </div>
                <span class="nginx-badge-running" id="nginxStatusBadge">
                    <span class="status-dot"></span>
                    <span id="nginxStatusText">{{ $nginxStatus['status_label'] }}</span>
                </span>
            </div>

            <div class="nginx-stat-grid">
                <div class="nginx-stat-item">
                    <div class="nginx-stat-num font-mono" id="nginxWorkerCount">{{ $nginxStatus['worker_processes'] }}</div>
                    <div class="nginx-stat-lbl">Worker Processes</div>
                </div>
                <div class="nginx-stat-item">
                    <div class="nginx-stat-num font-mono" id="nginxVhostCount">{{ $nginxStatus['vhosts_count'] }}</div>
                    <div class="nginx-stat-lbl">Virtual Hosts Terpasang</div>
                </div>
            </div>

            <div class="spec-info" style="margin-top: 4px;">
                <span class="spec-label">Versi Nginx</span>
                <span class="font-mono" style="font-size: 0.85rem; font-weight: 600; color: var(--text-primary);" id="nginxVersionText">
                    {{ $nginxStatus['version'] }} (Ports: 80, 443)
                </span>
            </div>

            <div style="display: flex; gap: 8px; margin-top: auto;">
                <button class="btn" style="flex: 1; justify-content: center; font-size: 0.78rem;" onclick="testNginxSyntax()">
                    <i data-lucide="check-circle" style="width: 14px; height: 14px; color: #10b981;"></i>
                    <span>Test Config (nginx -t)</span>
                </button>
                <button class="btn" style="flex: 1; justify-content: center; font-size: 0.78rem;" onclick="reloadNginxService()">
                    <i data-lucide="rotate-ccw" style="width: 14px; height: 14px; color: #06b6d4;"></i>
                    <span>Reload Service</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Active Web Services Section Header & Filters -->
    <div class="section-header">
        <div class="section-title">
            <i data-lucide="globe" style="width: 24px; height: 24px; color: #10b981;"></i>
            Web Aktif & Health Uptime Monitor
            <span class="badge-count font-mono" id="totalSitesCount" style="background: rgba(16, 185, 129, 0.2); color: #34d399; font-size: 0.8rem; padding: 3px 10px;">
                {{ count($sites) }} Web Dipantau
            </span>
        </div>

        <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
            <!-- Filter Tabs -->
            <div class="filter-tabs">
                <button class="filter-btn active" onclick="setFilter('all', this)">
                    <span>Semua Web</span>
                    <span class="badge-count" id="countFilterAll">{{ count($sites) }}</span>
                </button>
                <button class="filter-btn" onclick="setFilter('nextjs', this)">
                    <span>⚡ Next.js</span>
                    <span class="badge-count" id="countFilterNextjs">0</span>
                </button>
                <button class="filter-btn" onclick="setFilter('laravel', this)">
                    <span>🔴 Laravel</span>
                    <span class="badge-count" id="countFilterLaravel">0</span>
                </button>
                <button class="filter-btn" onclick="setFilter('native_php', this)">
                    <span>🐘 PHP Native</span>
                    <span class="badge-count" id="countFilterNative">0</span>
                </button>
                <button class="filter-btn" onclick="setFilter('online', this)">
                    <span>🟢 Online</span>
                    <span class="badge-count" id="countFilterOnline">0</span>
                </button>
                <button class="filter-btn" onclick="setFilter('offline', this)">
                    <span>🔴 Down</span>
                    <span class="badge-count" id="countFilterOffline">0</span>
                </button>
            </div>

            <!-- Search input -->
            <div style="position: relative;">
                <input type="text" id="searchInput" class="form-input font-mono" placeholder="Cari domain / port..." style="padding-left: 32px; font-size: 0.82rem; width: 180px;" oninput="applyFilters()">
                <i data-lucide="search" style="position: absolute; left: 10px; top: 10px; width: 14px; height: 14px; color: var(--text-muted);"></i>
            </div>
        </div>
    </div>

    <!-- Monitored Websites Grid -->
    <div class="sites-grid" id="sitesContainer">
        <!-- Site cards will be dynamically rendered and updated by JS -->
    </div>

</div>

<!-- Modal: Tambah / Edit Website -->
<div class="modal-backdrop" id="siteModal">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title">
                <i data-lucide="plus-circle" style="width: 22px; height: 22px; color: #6366f1;"></i>
                <span id="siteModalTitle">Tambah Website Monitoring</span>
            </div>
            <button class="btn btn-icon" onclick="closeSiteModal()">
                <i data-lucide="x" style="width: 18px; height: 18px;"></i>
            </button>
        </div>
        <form id="siteForm" onsubmit="handleSiteFormSubmit(event)">
            <input type="hidden" id="siteFormId">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Tipe Stack Web (Pilih Preset):</label>
                    <div class="stack-preset-grid">
                        <button type="button" class="stack-preset-btn selected" id="presetNextjs" onclick="selectStackPreset('nextjs')">
                            <span>⚡ Next.js</span>
                            <small style="color: var(--text-muted);">Port 3000 / SSR</small>
                        </button>
                        <button type="button" class="stack-preset-btn" id="presetLaravel" onclick="selectStackPreset('laravel')">
                            <span>🔴 Laravel</span>
                            <small style="color: var(--text-muted);">PHP-FPM / /up</small>
                        </button>
                        <button type="button" class="stack-preset-btn" id="presetNative" onclick="selectStackPreset('native_php')">
                            <span>🐘 PHP Native</span>
                            <small style="color: var(--text-muted);">FastCGI Port 8080</small>
                        </button>
                        <button type="button" class="stack-preset-btn" id="presetApi" onclick="selectStackPreset('api')">
                            <span>🌐 API / Custom</span>
                            <small style="color: var(--text-muted);">REST / Cloudflare</small>
                        </button>
                    </div>
                    <input type="hidden" id="siteStackType" value="nextjs">
                </div>

                <div class="form-group">
                    <label class="form-label" for="siteName">Nama Website / Layanan</label>
                    <input type="text" id="siteName" class="form-input" placeholder="Contoh: Toko Online Next.js STB" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="siteUrl">URL Target / Host</label>
                    <input type="url" id="siteUrl" class="form-input font-mono" placeholder="http://localhost:3000 atau https://domainstb.my.id" required>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                    <div class="form-group">
                        <label class="form-label" for="sitePort">Port Spesifik (Opsional)</label>
                        <input type="number" id="sitePort" class="form-input font-mono" placeholder="3000 / 80 / 8080">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="siteKeyword">Keyword Check (Opsional)</label>
                        <input type="text" id="siteKeyword" class="form-input" placeholder="Teks wajib di HTML">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn" onclick="closeSiteModal()">Batal</button>
                <button type="submit" class="btn btn-primary" id="siteSubmitBtn">Simpan & Aktifkan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Nginx Inspector & Syntax Test -->
<div class="modal-backdrop" id="nginxModal">
    <div class="modal-box" style="max-width: 680px;">
        <div class="modal-header">
            <div class="modal-title">
                <i data-lucide="server" style="width: 22px; height: 22px; color: #06b6d4;"></i>
                <span>Nginx Inspector & Virtual Hosts STB</span>
            </div>
            <button class="btn btn-icon" onclick="closeNginxModal()">
                <i data-lucide="x" style="width: 18px; height: 18px;"></i>
            </button>
        </div>
        <div class="modal-body">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
                <div>
                    <h4 style="font-size: 0.95rem; font-weight: 700;">Syntax Validation (nginx -t)</h4>
                    <p style="font-size: 0.78rem; color: var(--text-muted);">Memeriksa kebenaran sintaks file konfigurasi Nginx di server STB.</p>
                </div>
                <button class="btn btn-emerald" style="font-size: 0.8rem;" onclick="testNginxSyntax()">
                    <i data-lucide="play" style="width: 14px; height: 14px;"></i>
                    <span>Jalankan Test</span>
                </button>
            </div>

            <!-- Terminal Output Window -->
            <div class="terminal-window">
                <div class="terminal-bar">
                    <div class="terminal-dots">
                        <span class="t-dot t-red"></span>
                        <span class="t-dot t-yellow"></span>
                        <span class="t-dot t-green"></span>
                    </div>
                    <span class="terminal-title">bash - stb@server: ~ /usr/sbin/nginx -t</span>
                    <span style="font-size: 0.68rem; color: #64748b;">CLI Output</span>
                </div>
                <div class="terminal-body" id="nginxTerminalOutput">Memeriksa konfigurasi Nginx...</div>
            </div>

            <div style="margin-top: 24px;">
                <h4 style="font-size: 0.95rem; font-weight: 700; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="folder-code" style="width: 16px; height: 16px; color: #818cf8;"></i>
                    <span>Virtual Hosts Terdeteksi di STB</span>
                </h4>
                <div id="vhostsListContainer" style="display: flex; flex-direction: column; gap: 8px;">
                    <!-- Dynamically populated -->
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn" onclick="reloadNginxService()">
                <i data-lucide="rotate-ccw" style="width: 14px; height: 14px; color: #06b6d4;"></i>
                <span>Reload Nginx Daemon</span>
            </button>
            <button type="button" class="btn btn-primary" onclick="closeNginxModal()">Tutup</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Initial data passed from Blade controller
    let initialSites = @json($sites);
    let initialMetrics = @json($metrics);
    let currentFilter = 'all';
    let isAutoRefreshActive = true;
    let refreshInterval = null;
    let telemetryChart = null;

    // Initialize Telemetry Chart (ApexCharts)
    function initTelemetryChart() {
        const options = {
            series: [
                { name: 'CPU Load (%)', data: [22, 28, 25, 34, 30, 26, 35, 32, 28, 30] },
                { name: 'RAM Usage (%)', data: [42, 43, 42, 44, 43, 42, 43, 44, 43, 42] },
                { name: 'Suhu SoC (°C)', data: [49, 50, 50, 51, 51, 52, 51, 51, 52, 51] },
                { name: 'Disk I/O Load (%)', data: [5, 8, 12, 6, 9, 15, 7, 10, 8, 9] }
            ],
            chart: {
                height: 250,
                type: 'area',
                toolbar: { show: false },
                animations: { enabled: true, easing: 'linear', dynamicAnimation: { speed: 800 } },
                fontFamily: 'Plus Jakarta Sans, sans-serif',
                background: 'transparent',
            },
            colors: ['#6366f1', '#10b981', '#fb7185', '#06b6d4'],
            stroke: { curve: 'smooth', width: 2 },
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.35,
                    opacityTo: 0.05,
                    stops: [0, 90, 100]
                }
            },
            dataLabels: { enabled: false },
            grid: {
                borderColor: 'rgba(255, 255, 255, 0.06)',
                strokeDashArray: 3,
                xaxis: { lines: { show: true } },
                yaxis: { lines: { show: true } }
            },
            xaxis: {
                categories: ['1m lalu', '50s', '40s', '30s', '25s', '20s', '15s', '10s', '5s', 'Sekarang'],
                labels: { style: { colors: '#64748b', fontSize: '11px', fontFamily: 'JetBrains Mono' } },
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            yaxis: {
                max: 100,
                min: 0,
                labels: {
                    style: { colors: '#64748b', fontSize: '11px', fontFamily: 'JetBrains Mono' },
                    formatter: (val) => `${Math.round(val)}%`
                }
            },
            tooltip: {
                theme: document.documentElement.getAttribute('data-theme') === 'light' ? 'light' : 'dark',
                x: { show: true },
                y: { formatter: (val) => `${val.toFixed(1)}` }
            },
            legend: { show: false }
        };

        telemetryChart = new ApexCharts(document.querySelector("#liveTelemetryChart"), options);
        telemetryChart.render();
    }

    // Render Website Cards
    function renderSitesGrid(sitesToRender) {
        const container = document.getElementById('sitesContainer');
        if (!container) return;

        // Update counts in filter badges
        updateFilterBadges(sitesToRender);

        const searchKeyword = (document.getElementById('searchInput')?.value || '').toLowerCase().trim();

        const filtered = sitesToRender.filter(site => {
            // Stack / status filter
            if (currentFilter === 'nextjs' && site.stack_type !== 'nextjs') return false;
            if (currentFilter === 'laravel' && site.stack_type !== 'laravel') return false;
            if (currentFilter === 'native_php' && site.stack_type !== 'native_php') return false;
            if (currentFilter === 'online' && !site.is_online) return false;
            if (currentFilter === 'offline' && site.is_online) return false;

            // Search query filter
            if (searchKeyword) {
                const matchName = (site.name || '').toLowerCase().includes(searchKeyword);
                const matchUrl = (site.url || '').toLowerCase().includes(searchKeyword);
                const matchPort = site.port && site.port.toString().includes(searchKeyword);
                if (!matchName && !matchUrl && !matchPort) return false;
            }

            return true;
        });

        if (filtered.length === 0) {
            container.innerHTML = `
                <div class="glass-card" style="grid-column: 1 / -1; padding: 48px 24px; text-align: center;">
                    <i data-lucide="inbox" style="width: 48px; height: 48px; color: var(--text-muted); margin: 0 auto 16px;"></i>
                    <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 6px;">Tidak ada website yang cocok</h3>
                    <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 20px;">Coba ubah filter atau kata kunci pencarian Anda.</p>
                    <button class="btn btn-primary" onclick="setFilter('all', document.querySelector('.filter-btn'))">Reset Filter</button>
                </div>
            `;
            if (window.lucide) window.lucide.createIcons();
            return;
        }

        let html = '';
        filtered.forEach(site => {
            const isOnline = site.is_online;
            const statusClass = isOnline ? 'online' : 'offline';
            const statusText = isOnline ? 'ONLINE' : 'OFFLINE';

            // Stack icon & badges
            let stackBadge = '';
            let stackClass = 'stack-other';
            let stackLabel = 'Custom Web';

            if (site.stack_type === 'nextjs') {
                stackClass = 'stack-nextjs';
                stackLabel = 'Next.js';
            } else if (site.stack_type === 'laravel') {
                stackClass = 'stack-laravel';
                stackLabel = 'Laravel';
            } else if (site.stack_type === 'native_php') {
                stackClass = 'stack-native_php';
                stackLabel = 'PHP Native';
            } else if (site.stack_type === 'api') {
                stackClass = 'stack-api';
                stackLabel = 'API / Gateway';
            }

            // Latency styling
            let latencyClass = 'fast';
            const ms = site.last_response_time_ms || 0;
            if (!isOnline) latencyClass = 'error';
            else if (ms > 500) latencyClass = 'slow';
            else if (ms > 150) latencyClass = 'normal';

            // Uptime history bars HTML
            let historyBarsHtml = '';
            const bars = site.history_bars || [];
            for (let i = 0; i < 24; i++) {
                const bar = bars[i];
                if (bar) {
                    const cls = bar.online ? 'up' : 'down';
                    historyBarsHtml += `<div class="uptime-bar-segment ${cls}" title="${bar.time} • HTTP ${bar.status_code || 'Err'} • ${bar.ms}ms"></div>`;
                } else {
                    historyBarsHtml += `<div class="uptime-bar-segment empty" title="Belum ada data"></div>`;
                }
            }

            html += `
                <div class="glass-card site-card" id="site-card-${site.id}">
                    <div class="site-card-top">
                        <div class="site-meta">
                            <div class="stack-icon-wrapper ${stackClass}">
                                ${site.stack_type === 'nextjs' ? '⚡' : (site.stack_type === 'laravel' ? '🔴' : (site.stack_type === 'native_php' ? '🐘' : '🌐'))}
                            </div>
                            <div class="site-title-area">
                                <div class="site-title">
                                    ${escapeHtml(site.name)}
                                </div>
                                <a href="${escapeHtml(site.url)}" target="_blank" class="site-url-link font-mono">
                                    <span>${escapeHtml(site.url)}</span>
                                    <i data-lucide="external-link" style="width: 12px; height: 12px;"></i>
                                </a>
                            </div>
                        </div>

                        <div class="site-badges-group">
                            <span class="status-pill ${statusClass}">
                                <span class="status-dot"></span>
                                <span>${statusText}</span>
                            </span>
                        </div>
                    </div>

                    <!-- Middle metrics: HTTP Status, Latency, Stack -->
                    <div class="site-metrics-row">
                        <div class="site-metric-cell">
                            <span class="site-metric-val font-mono ${isOnline ? 'fast' : 'error'}">
                                ${site.last_status_code ? 'HTTP ' + site.last_status_code : 'N/A'}
                            </span>
                            <span class="site-metric-lbl">Status Code</span>
                        </div>
                        <div class="site-metric-cell">
                            <span class="site-metric-val font-mono ${latencyClass}">
                                ${isOnline ? ms + ' ms' : 'Offline'}
                            </span>
                            <span class="site-metric-lbl">Response Time</span>
                        </div>
                        <div class="site-metric-cell">
                            <span class="site-metric-val font-mono" style="color: var(--accent-indigo);">
                                ${stackLabel}
                            </span>
                            <span class="site-metric-lbl">Stack Engine</span>
                        </div>
                    </div>

                    <!-- 24h Uptime History Bar -->
                    <div class="uptime-history-wrap">
                        <div class="uptime-header">
                            <span>Riwayat Uptime (24 Jam)</span>
                            <span class="font-mono" style="font-weight: 700; color: #10b981;">${site.uptime_percentage}%</span>
                        </div>
                        <div class="uptime-bars">
                            ${historyBarsHtml}
                        </div>
                    </div>

                    <!-- Bottom Card Footer -->
                    <div class="site-card-footer">
                        <span class="site-last-checked font-mono">
                            <i data-lucide="clock" style="width: 11px; height: 11px; display: inline;"></i> ${site.last_checked_at}
                        </span>
                        <div class="site-actions-row">
                            <button class="btn btn-icon" style="padding: 6px 10px; font-size: 0.75rem;" onclick="checkSingleSite(${site.id})" title="Cek Sekarang">
                                <i data-lucide="refresh-cw" id="checkIcon-${site.id}" style="width: 13px; height: 13px;"></i>
                            </button>
                            <button class="btn btn-icon" style="padding: 6px 10px; font-size: 0.75rem;" onclick="openEditSiteModal(${site.id})" title="Edit Web">
                                <i data-lucide="edit-3" style="width: 13px; height: 13px;"></i>
                            </button>
                            <button class="btn btn-icon" style="padding: 6px 10px; font-size: 0.75rem; color: #f43f5e;" onclick="deleteSite(${site.id})" title="Hapus Web">
                                <i data-lucide="trash-2" style="width: 13px; height: 13px;"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
        });

        container.innerHTML = html;
        if (window.lucide) window.lucide.createIcons();
    }

    // Helper: update filter badge counts
    function updateFilterBadges(allSites) {
        document.getElementById('totalSitesCount').innerText = `${allSites.length} Web Dipantau`;
        document.getElementById('countFilterAll').innerText = allSites.length;
        document.getElementById('countFilterNextjs').innerText = allSites.filter(s => s.stack_type === 'nextjs').length;
        document.getElementById('countFilterLaravel').innerText = allSites.filter(s => s.stack_type === 'laravel').length;
        document.getElementById('countFilterNative').innerText = allSites.filter(s => s.stack_type === 'native_php').length;
        document.getElementById('countFilterOnline').innerText = allSites.filter(s => s.is_online).length;
        document.getElementById('countFilterOffline').innerText = allSites.filter(s => !s.is_online).length;
    }

    // Set filter tab
    function setFilter(type, el) {
        currentFilter = type;
        document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
        if (el) el.classList.add('active');
        renderSitesGrid(initialSites);
    }

    function applyFilters() {
        renderSitesGrid(initialSites);
    }

    // Polling STB Metrics
    async function fetchSystemMetrics() {
        try {
            const res = await fetch('/api/monitoring/metrics');
            if (!res.ok) return;
            const data = await res.json();

            // Update UI elements
            document.getElementById('cpuUsageVal').innerText = data.cpu.usage_percent;
            document.getElementById('cpuProgressBar').style.width = `${data.cpu.usage_percent}%`;
            document.getElementById('cpuLoadAvgTag').innerText = `Load: ${data.cpu.load_avg.join(', ')}`;

            document.getElementById('tempUsageVal').innerText = data.temperature.celsius;
            document.getElementById('specTempText').innerText = `${data.temperature.celsius} °C`;
            document.getElementById('tempProgressBar').style.width = `${Math.min(100, (data.temperature.celsius / 85) * 100)}%`;

            const tempTag = document.getElementById('tempTag');
            if (data.temperature.celsius >= 75) {
                tempTag.innerText = 'Kritis / Panas!';
                tempTag.style.background = 'rgba(244, 63, 94, 0.2)';
                tempTag.style.color = '#fb7185';
            } else {
                tempTag.innerText = 'Normal';
                tempTag.style.background = 'rgba(16, 185, 129, 0.15)';
                tempTag.style.color = '#34d399';
            }

            document.getElementById('ramUsageVal').innerText = data.ram.usage_percent;
            document.getElementById('ramProgressBar').style.width = `${data.ram.usage_percent}%`;
            document.getElementById('ramMbTag').innerText = `${data.ram.used_mb} / ${data.ram.total_mb} MB`;
            document.getElementById('ramFreeText').innerText = `Free: ${data.ram.free_mb} MB`;

            document.getElementById('diskUsageVal').innerText = data.disk.usage_percent;
            document.getElementById('diskProgressBar').style.width = `${data.disk.usage_percent}%`;
            document.getElementById('diskUsedText').innerText = `${data.disk.used_gb} GB / ${data.disk.total_gb} GB`;

            // Disk Load & I/O
            document.getElementById('diskIoTag').innerText = `I/O: ${data.disk_io.load_percent}%`;
            document.getElementById('specDiskIoText').innerText = `R: ${data.disk_io.read_kb_s} KB/s • W: ${data.disk_io.write_kb_s} KB/s`;
            document.getElementById('specDiskIoStatus').innerText = `I/O Load: ${data.disk_io.load_status.toUpperCase()}`;

            // Uptime update
            if (data.system.uptime_formatted) {
                document.getElementById('headerUptimeText').innerHTML = `<i data-lucide="clock" style="width: 14px; height: 14px; display: inline;"></i> Uptime: ${data.system.uptime_formatted}`;
                if (window.lucide) window.lucide.createIcons();
            }

            // Update Telemetry Chart
            if (telemetryChart && data.history && data.history.length > 0) {
                const times = data.history.map(h => h.time);
                const cpus = data.history.map(h => h.cpu);
                const rams = data.history.map(h => h.ram);
                const temps = data.history.map(h => h.temp);
                const diskIo = data.history.map(h => Math.min(100, (h.disk_read + h.disk_write) / 5));

                telemetryChart.updateOptions({
                    xaxis: { categories: times }
                }, false, false);

                telemetryChart.updateSeries([
                    { name: 'CPU Load (%)', data: cpus },
                    { name: 'RAM Usage (%)', data: rams },
                    { name: 'Suhu SoC (°C)', data: temps },
                    { name: 'Disk I/O Load (%)', data: diskIo }
                ]);
            }
        } catch (err) {
            console.error('Error fetching STB metrics:', err);
        }
    }

    // Fetch Sites
    async function fetchSites() {
        try {
            const res = await fetch('/api/monitoring/sites');
            if (!res.ok) return;
            const data = await res.json();
            initialSites = data;
            renderSitesGrid(initialSites);
        } catch (err) {
            console.error('Error fetching sites:', err);
        }
    }

    // Single site check trigger
    async function checkSingleSite(siteId) {
        const icon = document.getElementById(`checkIcon-${siteId}`);
        if (icon) icon.style.animation = 'spin 1s infinite linear';

        try {
            const res = await fetch(`/api/monitoring/sites/${siteId}/check`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') }
            });
            const data = await res.json();
            showToast(`Pengecekan ${data.check.name} selesai: ${data.check.is_online ? 'ONLINE' : 'OFFLINE'} (${data.check.response_time_ms}ms)`);
            await fetchSites();
        } catch (err) {
            showToast('Gagal melakukan pengecekan web.', 'error');
        } finally {
            if (icon) icon.style.animation = '';
        }
    }

    // Check all websites trigger
    async function checkAllWebsites() {
        const icon = document.getElementById('checkAllIcon');
        if (icon) icon.style.animation = 'spin 1s infinite linear';
        showToast('Sedang melakukan health check semua web aktif (Next.js, Laravel, PHP Native)...');

        try {
            const res = await fetch('/api/monitoring/check-all', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') }
            });
            const data = await res.json();
            initialSites = data;
            renderSitesGrid(initialSites);
            showToast(`Berhasil memeriksa ${data.length} website aktif!`);
        } catch (err) {
            showToast('Gagal memeriksa semua web.', 'error');
        } finally {
            if (icon) icon.style.animation = '';
        }
    }

    // Nginx Syntax Test
    async function testNginxSyntax() {
        const term = document.getElementById('nginxTerminalOutput');
        term.innerText = 'Menjalankan: sudo nginx -t ...\nSedang memvalidasi file konfigurasi...';
        openNginxModal();

        try {
            const res = await fetch('/api/monitoring/nginx/test', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') }
            });
            const data = await res.json();
            term.innerText = `$ nginx -t\n\n${data.output}\n\n[STATUS]: ${data.success ? 'BERHASIL - Konfigurasi valid dan aman!' : 'PERINGATAN: Terdapat kesalahan sintaks'}`;
            showToast(data.success ? 'Nginx config test: BERHASIL!' : 'Nginx config error!', data.success ? 'success' : 'error');
        } catch (err) {
            term.innerText = 'Gagal menghubungi server untuk menjalankan test.';
        }
    }

    // Reload Nginx Service
    async function reloadNginxService() {
        if (!confirm('Apakah Anda yakin ingin me-reload daemon Nginx di server STB?')) return;
        showToast('Sedang mere-load service Nginx...');

        try {
            const res = await fetch('/api/monitoring/nginx/reload', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') }
            });
            const data = await res.json();
            showToast(data.message, data.success ? 'success' : 'error');
            fetchNginxInfo();
        } catch (err) {
            showToast('Gagal me-reload Nginx.', 'error');
        }
    }

    // Fetch Nginx info for modal
    async function fetchNginxInfo() {
        try {
            const res = await fetch('/api/monitoring/nginx');
            if (!res.ok) return;
            const data = await res.json();

            document.getElementById('nginxStatusText').innerText = data.status_label;
            document.getElementById('nginxWorkerCount').innerText = data.worker_processes;
            document.getElementById('nginxVhostCount').innerText = data.vhosts_count;
            document.getElementById('nginxVersionText').innerText = `${data.version} (Ports: 80, 443)`;

            // Render virtual hosts
            const vhostContainer = document.getElementById('vhostsListContainer');
            if (vhostContainer && data.vhosts) {
                let vHtml = '';
                data.vhosts.forEach(vh => {
                    vHtml += `
                        <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; border-radius: var(--radius-sm); background: var(--bg-glass); border: 1px solid var(--border-color); font-size: 0.82rem;">
                            <div>
                                <span class="font-mono" style="font-weight: 700; color: var(--accent-cyan);">${escapeHtml(vh.server_name)}</span>
                                <span style="color: var(--text-muted); font-size: 0.74rem;">(Port ${vh.port}) • Target: ${escapeHtml(vh.target)}</span>
                            </div>
                            <span class="stb-badge" style="font-size: 0.65rem;">${vh.stack_type}</span>
                        </div>
                    `;
                });
                vhostContainer.innerHTML = vHtml;
            }
        } catch (err) {
            console.error('Error fetching Nginx info:', err);
        }
    }

    // Stack Preset Selector
    function selectStackPreset(type) {
        document.getElementById('siteStackType').value = type;
        document.querySelectorAll('.stack-preset-btn').forEach(btn => btn.classList.remove('selected'));

        const portInput = document.getElementById('sitePort');
        const urlInput = document.getElementById('siteUrl');

        if (type === 'nextjs') {
            document.getElementById('presetNextjs').classList.add('selected');
            if (!portInput.value) portInput.value = 3000;
            if (!urlInput.value) urlInput.value = 'http://localhost:3000';
        } else if (type === 'laravel') {
            document.getElementById('presetLaravel').classList.add('selected');
            if (!portInput.value) portInput.value = 8000;
            if (!urlInput.value) urlInput.value = 'http://127.0.0.1:8000';
        } else if (type === 'native_php') {
            document.getElementById('presetNative').classList.add('selected');
            if (!portInput.value) portInput.value = 8080;
            if (!urlInput.value) urlInput.value = 'http://127.0.0.1:8080';
        } else {
            document.getElementById('presetApi').classList.add('selected');
            if (!portInput.value) portInput.value = 443;
            if (!urlInput.value) urlInput.value = 'https://';
        }
    }

    // Open Add Site Modal
    function openAddSiteModal() {
        document.getElementById('siteFormId').value = '';
        document.getElementById('siteModalTitle').innerText = 'Tambah Website Monitoring';
        document.getElementById('siteSubmitBtn').innerText = 'Simpan & Aktifkan';
        document.getElementById('siteName').value = '';
        document.getElementById('siteUrl').value = 'http://localhost:3000';
        document.getElementById('sitePort').value = 3000;
        document.getElementById('siteKeyword').value = '';
        selectStackPreset('nextjs');
        document.getElementById('siteModal').classList.add('active');
    }

    // Open Edit Site Modal
    function openEditSiteModal(siteId) {
        const site = initialSites.find(s => s.id === siteId);
        if (!site) return;

        document.getElementById('siteFormId').value = site.id;
        document.getElementById('siteModalTitle').innerText = `Edit: ${site.name}`;
        document.getElementById('siteSubmitBtn').innerText = 'Perbarui Pengaturan';
        document.getElementById('siteName').value = site.name;
        document.getElementById('siteUrl').value = site.url;
        document.getElementById('sitePort').value = site.port || '';
        document.getElementById('siteKeyword').value = site.expected_keyword || '';
        selectStackPreset(site.stack_type || 'laravel');
        document.getElementById('siteModal').classList.add('active');
    }

    function closeSiteModal() {
        document.getElementById('siteModal').classList.remove('active');
    }

    // Form submit for site add/edit
    async function handleSiteFormSubmit(e) {
        e.preventDefault();
        const id = document.getElementById('siteFormId').value;
        const payload = {
            name: document.getElementById('siteName').value,
            url: document.getElementById('siteUrl').value,
            stack_type: document.getElementById('siteStackType').value,
            port: document.getElementById('sitePort').value ? parseInt(document.getElementById('sitePort').value) : null,
            expected_keyword: document.getElementById('siteKeyword').value || null,
        };

        const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        try {
            const url = id ? `/api/monitoring/sites/${id}` : '/api/monitoring/sites';
            const method = id ? 'PUT' : 'POST';

            const res = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify(payload)
            });

            if (!res.ok) {
                const errData = await res.json();
                throw new Error(errData.message || 'Gagal menyimpan website');
            }

            const data = await res.json();
            showToast(data.message);
            closeSiteModal();
            await fetchSites();
        } catch (err) {
            showToast(err.message, 'error');
        }
    }

    // Delete site
    async function deleteSite(siteId) {
        if (!confirm('Yakin ingin menghapus website ini dari pantauan?')) return;
        const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        try {
            const res = await fetch(`/api/monitoring/sites/${siteId}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf }
            });
            const data = await res.json();
            showToast(data.message);
            await fetchSites();
        } catch (err) {
            showToast('Gagal menghapus website.', 'error');
        }
    }

    // Nginx Modal Controls
    function openNginxModal() {
        fetchNginxInfo();
        document.getElementById('nginxModal').classList.add('active');
    }
    function closeNginxModal() {
        document.getElementById('nginxModal').classList.remove('active');
    }

    // Auto-refresh controls
    function toggleAutoRefresh() {
        isAutoRefreshActive = !isAutoRefreshActive;
        const dot = document.getElementById('liveDot');
        const lbl = document.getElementById('refreshLabel');

        if (isAutoRefreshActive) {
            dot.style.background = '#10b981';
            dot.style.animation = 'pulseDot 2s infinite ease-in-out';
            lbl.innerText = 'Live Telemetry (5s)';
            startPolling();
            showToast('Auto-refresh diaktifkan.');
        } else {
            dot.style.background = '#f59e0b';
            dot.style.animation = 'none';
            lbl.innerText = 'Telemetry: Paused';
            stopPolling();
            showToast('Auto-refresh dijeda.');
        }
    }

    function startPolling() {
        if (refreshInterval) clearInterval(refreshInterval);
        refreshInterval = setInterval(() => {
            if (isAutoRefreshActive) {
                fetchSystemMetrics();
            }
        }, 5000);
    }

    function stopPolling() {
        if (refreshInterval) clearInterval(refreshInterval);
    }

    // Utility: HTML escape
    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/[&<>'"]/g, tag => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            "'": '&#39;',
            '"': '&quot;'
        }[tag] || tag));
    }

    // Initialize on DOM load
    document.addEventListener('DOMContentLoaded', () => {
        initTelemetryChart();
        renderSitesGrid(initialSites);
        fetchNginxInfo();
        startPolling();
    });

    // Theme changed hook
    window.onThemeChanged = function(theme) {
        if (telemetryChart) {
            telemetryChart.updateOptions({
                tooltip: { theme: theme }
            });
        }
    };
</script>
<style>
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
</style>
@endpush
