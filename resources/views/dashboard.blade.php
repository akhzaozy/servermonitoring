@extends('layouts.app')

@section('title', 'STB Telemetry & Backup Vault')

@section('content')
<div class="dashboard-container">

    <!-- Top Filter & Action Bar (Matching Reference Layout) -->
    <div class="top-filter-bar animate-entrance">
        <div class="filter-group">
            <div style="display: flex; align-items: center; gap: 8px; margin-right: 6px;">
                <div class="activity-icon-avatar" style="width: 34px; height: 34px;">
                    <i data-lucide="cpu" style="width: 17px; height: 17px;"></i>
                </div>
                <div>
                    <div style="font-weight: 800; font-size: 0.95rem; color: #ffffff; line-height: 1.1;">STB Monitor</div>
                    <div style="font-size: 0.7rem; color: var(--text-muted);">{{ $metrics['system']['hostname'] }}</div>
                </div>
            </div>

            <!-- Region / Node Filter -->
            <div class="pill-select-container">
                <i data-lucide="server" style="width: 15px; height: 15px; color: var(--neon-cyan);"></i>
                <select id="filterRegion" class="pill-select">
                    <option value="stb-local">Node: STB Local Server</option>
                    <option value="cloudflare">Node: Cloudflare Tunnel</option>
                    <option value="all">Semua Cluster Node</option>
                </select>
                <i data-lucide="chevron-down" style="width: 14px; height: 14px; color: var(--text-faint);"></i>
            </div>

            <!-- Category / Stack Filter -->
            <div class="pill-select-container">
                <i data-lucide="layers" style="width: 15px; height: 15px; color: var(--neon-purple);"></i>
                <select id="filterStack" class="pill-select" onchange="applyStackFilter(this.value)">
                    <option value="all">Semua Stack & Layanan</option>
                    <option value="laravel">PHP & Laravel Sites</option>
                    <option value="nodejs">Node.js Apps</option>
                    <option value="mysql">MariaDB / MySQL</option>
                    <option value="postgres">PostgreSQL</option>
                </select>
                <i data-lucide="chevron-down" style="width: 14px; height: 14px; color: var(--text-faint);"></i>
            </div>

            <!-- Time Window Filter -->
            <div class="pill-select-container">
                <i data-lucide="calendar" style="width: 15px; height: 15px; color: var(--neon-emerald);"></i>
                <select id="filterWindow" class="pill-select">
                    <option value="live">Live Telemetry (5s)</option>
                    <option value="24h">24 Jam Terakhir</option>
                    <option value="7d">7 Hari Terakhir</option>
                    <option value="30d">Last 30 Days</option>
                </select>
                <i data-lucide="chevron-down" style="width: 14px; height: 14px; color: var(--text-faint);"></i>
            </div>

            <!-- Search Filter -->
            <div class="pill-search">
                <i data-lucide="search" style="width: 15px; height: 15px; color: var(--text-muted);"></i>
                <input type="text" id="searchInput" placeholder="Cari website / database..." onkeyup="filterSearch(this.value)">
            </div>
        </div>

        <!-- Quick Header Actions -->
        <div class="header-actions">
            <!-- Backup Center Modal Button -->
            <button class="btn btn-neon-purple" onclick="openBackupModal()" title="Buka STB Backup Center & Jalankan Backup Manual">
                <i data-lucide="database-backup" style="width: 16px; height: 16px;"></i>
                <span>Backup Center</span>
                <span class="kpi-badge badge-cyan" id="headerBackupBadge" style="font-size: 0.68rem; padding: 1px 6px;">{{ $backupStatus['last_run']['status'] === 'running' ? 'RUNNING' : 'READY' }}</span>
            </button>

            <!-- Check All Websites Button -->
            <button class="btn btn-emerald" onclick="checkAllWebsites()">
                <i data-lucide="refresh-cw" id="checkAllIcon" style="width: 16px; height: 16px;"></i>
                <span>Cek Web</span>
            </button>

            <!-- Nginx Service Modal Button -->
            <button class="btn btn-neon-cyan" onclick="openNginxModal()" title="Status Nginx Virtual Hosts">
                <i data-lucide="cpu" style="width: 16px; height: 16px;"></i>
                <span>Nginx</span>
            </button>

            <!-- Add Website Button -->
            <button class="btn" style="background: rgba(255,255,255,0.06); border-color: var(--border-subtle); color: #ffffff;" onclick="openAddSiteModal()">
                <i data-lucide="plus-circle" style="width: 16px; height: 16px; color: var(--neon-cyan);"></i>
                <span>Tambah Web</span>
            </button>

            <!-- Theme Toggle -->
            <button class="btn btn-icon" onclick="toggleTheme()" title="Ganti Tema">
                <i data-lucide="sun-moon" style="width: 18px; height: 18px;"></i>
            </button>
        </div>
    </div>

    <!-- ROW 1: TOP 4 KPI CARDS (Matching Reference Layout) -->
    <div class="kpi-grid">
        <!-- Card 1: Total Web & Health Index -->
        <div class="neon-card kpi-card animate-entrance">
            <div class="kpi-label">Uptime & Health Index</div>
            <div class="kpi-val-row">
                <div class="kpi-value font-mono" id="scrambleKpi1">99.98%</div>
                <div class="kpi-badge badge-cyan">
                    <i data-lucide="trending-up" style="width: 12px; height: 12px;"></i>
                    <span>+411.35%</span>
                </div>
            </div>
            <!-- Mini SVG Sparkline Wave -->
            <div class="kpi-wave">
                <svg viewBox="0 0 300 60" preserveAspectRatio="none">
                    <defs>
                        <linearGradient id="waveGrad1" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#00f0ff" stop-opacity="0.35"/>
                            <stop offset="100%" stop-color="#00f0ff" stop-opacity="0.0"/>
                        </linearGradient>
                    </defs>
                    <path d="M0,45 C40,48 70,25 110,32 C150,38 180,18 220,24 C260,30 280,15 300,18 L300,60 L0,60 Z" fill="url(#waveGrad1)"/>
                    <path d="M0,45 C40,48 70,25 110,32 C150,38 180,18 220,24 C260,30 280,15 300,18" fill="none" stroke="#00f0ff" stroke-width="2.5"/>
                </svg>
            </div>
        </div>

        <!-- Card 2: CPU Load & STB Clock -->
        <div class="neon-card kpi-card animate-entrance">
            <div class="kpi-label">Beban CPU & Cores</div>
            <div class="kpi-val-row">
                <div class="kpi-value font-mono" id="scrambleKpi2">{{ $metrics['cpu']['usage_percent'] }}%</div>
                <div class="kpi-badge badge-purple">
                    <i data-lucide="cpu" style="width: 12px; height: 12px;"></i>
                    <span>{{ $metrics['cpu']['cores'] }} Cores</span>
                </div>
            </div>
            <!-- Mini SVG Sparkline Wave -->
            <div class="kpi-wave">
                <svg viewBox="0 0 300 60" preserveAspectRatio="none">
                    <defs>
                        <linearGradient id="waveGrad2" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#a855f7" stop-opacity="0.4"/>
                            <stop offset="100%" stop-color="#a855f7" stop-opacity="0.0"/>
                        </linearGradient>
                    </defs>
                    <path d="M0,40 C50,22 90,44 140,28 C190,12 230,35 270,20 C285,15 295,22 300,20 L300,60 L0,60 Z" fill="url(#waveGrad2)"/>
                    <path d="M0,40 C50,22 90,44 140,28 C190,12 230,35 270,20 C285,15 295,22 300,20" fill="none" stroke="#a855f7" stroke-width="2.5"/>
                </svg>
            </div>
        </div>

        <!-- Card 3: STB Backup Vault Size & Status -->
        <div class="neon-card kpi-card animate-entrance">
            <div class="kpi-label">Storage & Disk Load (Backup Vault)</div>
            <div class="kpi-val-row">
                <div class="kpi-value font-mono" id="scrambleKpi3">{{ $backupStatus['total_size_human'] }}</div>
                <div class="kpi-badge badge-emerald">
                    <i data-lucide="shield-check" style="width: 12px; height: 12px;"></i>
                    <span id="kpiBackupTag">PROTECTED</span>
                </div>
            </div>
            <!-- Mini SVG Sparkline Wave -->
            <div class="kpi-wave">
                <svg viewBox="0 0 300 60" preserveAspectRatio="none">
                    <defs>
                        <linearGradient id="waveGrad3" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#10b981" stop-opacity="0.4"/>
                            <stop offset="100%" stop-color="#10b981" stop-opacity="0.0"/>
                        </linearGradient>
                    </defs>
                    <path d="M0,35 C45,45 85,15 130,22 C175,28 220,10 260,18 C280,22 290,16 300,15 L300,60 L0,60 Z" fill="url(#waveGrad3)"/>
                    <path d="M0,35 C45,45 85,15 130,22 C175,28 220,10 260,18 C280,22 290,16 300,15" fill="none" stroke="#10b981" stroke-width="2.5"/>
                </svg>
            </div>
        </div>

        <!-- Card 4: RAM Memory & SoC Temp -->
        <div class="neon-card kpi-card animate-entrance">
            <div class="kpi-label">RAM Memory & SoC Temp</div>
            <div class="kpi-val-row">
                <div class="kpi-value font-mono" id="scrambleKpi4">{{ $metrics['ram']['usage_percent'] }}%</div>
                <div class="kpi-badge badge-cyan">
                    <i data-lucide="flame" style="width: 12px; height: 12px;"></i>
                    <span>{{ $metrics['temperature']['celsius'] }}°C</span>
                </div>
            </div>
            <!-- Mini SVG Sparkline Wave -->
            <div class="kpi-wave">
                <svg viewBox="0 0 300 60" preserveAspectRatio="none">
                    <defs>
                        <linearGradient id="waveGrad4" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#38bdf8" stop-opacity="0.35"/>
                            <stop offset="100%" stop-color="#38bdf8" stop-opacity="0.0"/>
                        </linearGradient>
                    </defs>
                    <path d="M0,42 C60,40 100,20 150,28 C200,35 240,16 280,24 C290,26 295,22 300,20 L300,60 L0,60 Z" fill="url(#waveGrad4)"/>
                    <path d="M0,42 C60,40 100,20 150,28 C200,35 240,16 280,24 C290,26 295,22 300,20" fill="none" stroke="#38bdf8" stroke-width="2.5"/>
                </svg>
            </div>
        </div>
    </div>

    <!-- ROW 2: CHARTS (Wave Chart & Donut Distribution) -->
    <div class="charts-grid-row1">
        <!-- Left: Sales Overview -> STB Telemetry & Traffic Wave Chart -->
        <div class="neon-card animate-entrance" style="display: flex; flex-direction: column;">
            <div class="card-header-row">
                <div>
                    <div class="card-title">Telemetry & Traffic Overview</div>
                    <div class="card-subtitle">Dinamika beban CPU, RAM, Disk I/O & respon request server real-time</div>
                </div>
                <div class="filter-group">
                    <span class="wave-pin-badge">
                        <span>Live Ping</span>
                        <span class="wave-pin-value" id="floatingPingBadge">24 ms</span>
                    </span>
                </div>
            </div>
            <div style="flex: 1; min-height: 280px; padding: 0 16px 16px 16px;">
                <div id="neonTelemetryWaveChart" style="min-height: 280px;"></div>
            </div>
        </div>

        <!-- Right: Customer Distribution -> Service & Resource Ring Chart -->
        <div class="neon-card animate-entrance" style="display: flex; flex-direction: column;">
            <div class="card-header-row">
                <div>
                    <div class="card-title">Service & Resource Distribution</div>
                    <div class="card-subtitle">Alokasi beban database, web server, dan storage</div>
                </div>
            </div>
            <div style="position: relative; flex: 1; display: flex; align-items: center; justify-content: center; min-height: 230px;">
                <div id="neonDistributionDonutChart"></div>
                <!-- Center Stat Overlay -->
                <div class="donut-center-overlay">
                    <div class="donut-center-number font-mono" id="scrambleDonutCenter">100%</div>
                    <div class="donut-center-sub">Active Health</div>
                </div>
            </div>
            <!-- Donut Legend -->
            <div class="donut-legend">
                <div class="legend-item">
                    <span class="legend-dot" style="background: #00f0ff;"></span>
                    <span>Web Hosting (44%)</span>
                </div>
                <div class="legend-item">
                    <span class="legend-dot" style="background: #a855f7;"></span>
                    <span>MariaDB (26%)</span>
                </div>
                <div class="legend-item">
                    <span class="legend-dot" style="background: #3b82f6;"></span>
                    <span>PostgreSQL (22%)</span>
                </div>
                <div class="legend-item">
                    <span class="legend-dot" style="background: #ec4899;"></span>
                    <span>Backup Vault (23%)</span>
                </div>
            </div>
        </div>
    </div>

    <!-- ROW 3: PROGRESS BARS & NETWORK TOPOLOGY MAP -->
    <div class="charts-grid-row2">
        <!-- Left: Top Products -> Top Monitored Services & Health Progress Bars -->
        <div class="neon-card animate-entrance">
            <div class="card-header-row">
                <div>
                    <div class="card-title">Top Monitored Services</div>
                    <div class="card-subtitle">Health score & performa latensi layanan aktif</div>
                </div>
                <button class="btn btn-icon" onclick="checkAllWebsites()" title="Segarkan status">
                    <i data-lucide="refresh-cw" style="width: 15px; height: 15px;"></i>
                </button>
            </div>
            <div class="progress-list" id="servicesProgressContainer">
                <!-- Dynamically populated / blade fallback items -->
                @forelse($sites->take(6) as $site)
                <div>
                    <div class="progress-item-row">
                        <span style="color: #ffffff; font-weight: 600;">{{ $site['name'] }}</span>
                        <span class="font-mono" style="color: var(--neon-cyan);">{{ $site['uptime_percentage'] }}% ({{ $site['last_response_time_ms'] ?? 24 }}ms)</span>
                    </div>
                    <div class="progress-bar-bg">
                        <div class="progress-bar-fill" style="width: {{ $site['uptime_percentage'] }}%;"></div>
                    </div>
                </div>
                @empty
                <div>
                    <div class="progress-item-row">
                        <span style="color: #ffffff;">Nginx Web Server Core</span>
                        <span class="font-mono" style="color: var(--neon-cyan);">100% (12ms)</span>
                    </div>
                    <div class="progress-bar-bg">
                        <div class="progress-bar-fill" style="width: 100%;"></div>
                    </div>
                </div>
                <div>
                    <div class="progress-item-row">
                        <span style="color: #ffffff;">MariaDB Multi-Tenant Instance</span>
                        <span class="font-mono" style="color: var(--neon-cyan);">99.8% (18ms)</span>
                    </div>
                    <div class="progress-bar-bg">
                        <div class="progress-bar-fill" style="width: 99.8%;"></div>
                    </div>
                </div>
                <div>
                    <div class="progress-item-row">
                        <span style="color: #ffffff;">PostgreSQL Server Daemon</span>
                        <span class="font-mono" style="color: var(--neon-cyan);">99.4% (21ms)</span>
                    </div>
                    <div class="progress-bar-bg">
                        <div class="progress-bar-fill" style="width: 99.4%;"></div>
                    </div>
                </div>
                @endforelse
            </div>
        </div>

        <!-- Right: Sales by Region -> STB Network Topology & Traffic Nodes -->
        <div class="neon-card animate-entrance">
            <div class="card-header-row">
                <div>
                    <div class="card-title">Network Nodes & Topology</div>
                    <div class="card-subtitle">Distribusi tunnel, gateway STB, dan request throughput</div>
                </div>
            </div>
            <div class="map-canvas-container">
                <!-- High-tech Node Map SVG Background -->
                <svg viewBox="0 0 600 280" style="width: 100%; height: 100%; opacity: 0.35;">
                    <circle cx="150" cy="110" r="3" fill="#00f0ff" />
                    <circle cx="280" cy="90" r="3" fill="#a855f7" />
                    <circle cx="340" cy="160" r="3" fill="#3b82f6" />
                    <circle cx="480" cy="130" r="3" fill="#00f0ff" />
                    <line x1="150" y1="110" x2="280" y2="90" stroke="rgba(0, 240, 255, 0.4)" stroke-dasharray="4,4"/>
                    <line x1="280" y1="90" x2="340" y2="160" stroke="rgba(168, 85, 247, 0.4)" stroke-dasharray="4,4"/>
                    <line x1="340" y1="160" x2="480" y2="130" stroke="rgba(59, 130, 246, 0.4)" stroke-dasharray="4,4"/>
                    <!-- Ambient Grid lines -->
                    <path d="M 50,70 Q 150,40 250,80 T 450,60" fill="none" stroke="rgba(255,255,255,0.04)" stroke-width="1.5"/>
                    <path d="M 80,180 Q 200,220 320,170 T 520,200" fill="none" stroke="rgba(255,255,255,0.04)" stroke-width="1.5"/>
                </svg>

                <!-- Floating Node Badges matching Reference Image -->
                <div class="pulse-node" style="top: 36%; left: 32%;"></div>
                <div class="map-node-badge" style="top: 25%; left: 24%;">
                    <div style="font-size: 0.72rem; color: var(--text-muted);">Local STB Gateway</div>
                    <div style="color: #ffffff; font-family: var(--font-mono); font-size: 0.95rem;">665,222 req</div>
                </div>

                <div class="pulse-node" style="top: 54%; right: 28%; background: var(--neon-purple); box-shadow: 0 0 16px var(--neon-purple);"></div>
                <div class="map-node-badge" style="top: 48%; right: 18%; border-color: rgba(168, 85, 247, 0.4);">
                    <div style="font-size: 0.72rem; color: var(--text-muted);">Cloudflare Edge</div>
                    <div style="color: #ffffff; font-family: var(--font-mono); font-size: 0.95rem;">38,510 req</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ROW 4: VERTICAL BAR CHART & RECENT ORDERS STYLE ACTIVITIES -->
    <div class="charts-grid-row3">
        <!-- Left: Hourly Request Throughput Vertical Bar Chart -->
        <div class="neon-card animate-entrance">
            <div class="card-header-row">
                <div>
                    <div class="card-title">Hourly Request Throughput</div>
                    <div class="card-subtitle">Volume request HTTP dan beban query per jam</div>
                </div>
            </div>
            <div style="padding: 0 16px 16px 16px; min-height: 280px;">
                <div id="neonVerticalBarChart" style="min-height: 280px;"></div>
            </div>
        </div>

        <!-- Right: Recent Orders -> Recent Backup & System Activities -->
        <div class="neon-card animate-entrance">
            <div class="card-header-row">
                <div>
                    <div class="card-title">Recent Activities & Backup Vault</div>
                    <div class="card-subtitle">Log eksekusi snapshot hosting, dump database & sinkronisasi</div>
                </div>
                <button class="btn btn-neon-purple" style="padding: 5px 12px; font-size: 0.78rem;" onclick="openBackupModal()">
                    Detail Vault
                </button>
            </div>
            <div class="recent-activity-list" id="recentActivitiesList">
                <!-- Activity 1: Incremental Snapshot -->
                <div class="activity-item">
                    <div class="activity-left">
                        <div class="activity-icon-avatar">
                            <i data-lucide="archive" style="width: 18px; height: 18px;"></i>
                        </div>
                        <div>
                            <div class="activity-title">Hosting Files Incremental</div>
                            <div class="activity-sub" id="recentFilesSnapshotSub">
                                {{ $backupStatus['latest_files']['incremental_snapshot']['name'] ?? 'snapshot_terbaru' }} (Rsync link-dest)
                            </div>
                        </div>
                    </div>
                    <div class="activity-right">
                        <span class="pill-complete">Completed</span>
                        <span class="font-mono" style="font-size: 0.76rem; color: var(--text-muted);">{{ $backupStatus['latest_files']['incremental_snapshot']['size_human'] ?? '1.24 GB' }}</span>
                    </div>
                </div>

                <!-- Activity 2: MariaDB Database Dumps -->
                <div class="activity-item">
                    <div class="activity-left">
                        <div class="activity-icon-avatar" style="background: rgba(0, 240, 255, 0.15); border-color: rgba(0, 240, 255, 0.3); color: var(--neon-cyan);">
                            <i data-lucide="database" style="width: 18px; height: 18px;"></i>
                        </div>
                        <div>
                            <div class="activity-title">MariaDB Multi-Tenant Dumps</div>
                            <div class="activity-sub">
                                {{ count($backupStatus['latest_files']['mariadb_databases'] ?? []) }} Database (mysqldump .sql.gz)
                            </div>
                        </div>
                    </div>
                    <div class="activity-right">
                        <span class="pill-complete">Completed</span>
                        <span class="font-mono" style="font-size: 0.76rem; color: var(--text-muted);">{{ $backupStatus['last_run']['human'] }}</span>
                    </div>
                </div>

                <!-- Activity 3: PostgreSQL Database Dumps -->
                <div class="activity-item">
                    <div class="activity-left">
                        <div class="activity-icon-avatar" style="background: rgba(59, 130, 246, 0.15); border-color: rgba(59, 130, 246, 0.3); color: #60a5fa;">
                            <i data-lucide="layers" style="width: 18px; height: 18px;"></i>
                        </div>
                        <div>
                            <div class="activity-title">PostgreSQL Database Dumps</div>
                            <div class="activity-sub">
                                {{ count($backupStatus['latest_files']['postgres_databases'] ?? []) }} Database (pg_dump .sql.gz)
                            </div>
                        </div>
                    </div>
            <div class="activity-right">
                        <span class="pill-complete">Completed</span>
                        <span class="font-mono" style="font-size: 0.76rem; color: var(--text-muted);">{{ $backupStatus['last_run']['total_time_text'] }}</span>
                    </div>
                </div>

                <!-- Activity 4: Nginx Virtual Hosts -->
                <div class="activity-item">
                    <div class="activity-left">
                        <div class="activity-icon-avatar" style="background: rgba(16, 185, 129, 0.15); border-color: rgba(16, 185, 129, 0.3); color: #34d399;">
                            <i data-lucide="globe" style="width: 18px; height: 18px;"></i>
                        </div>
                        <div>
                            <div class="activity-title">Nginx Virtual Hosts Telemetry</div>
                            <div class="activity-sub">{{ count($sites) }} Website Terpantau Aktif</div>
                        </div>
                    </div>
                    <div class="activity-right">
                        <span class="pill-complete">Active</span>
                        <span class="font-mono" style="font-size: 0.76rem; color: var(--text-muted);">Port 80/443</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- =========================================================================
         SECTION 5: STATUS WEB AKTIF & UPTIME MONITOR (FULL WIDTH GRID)
         ========================================================================= -->
    <div class="sites-section animate-entrance" style="width: 100%;">
        <div class="sites-header-bar">
            <div>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div class="card-title" style="font-size: 1.25rem;">Status Web Aktif & Uptime Monitor</div>
                    <span class="kpi-badge badge-cyan" id="totalSitesCount">{{ count($sites) }} Web Dipantau</span>
                </div>
                <div class="card-subtitle">Pemantauan status online/offline, latency response time, SSL, dan riwayat uptime 24 jam</div>
            </div>

            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                <div class="sites-tabs">
                    <button class="tab-btn active" onclick="setSiteFilter('all', this)">Semua (<span id="countFilterAll">{{ count($sites) }}</span>)</button>
                    <button class="tab-btn" onclick="setSiteFilter('online', this)">Online</button>
                    <button class="tab-btn" onclick="setSiteFilter('offline', this)">Offline</button>
                    <button class="tab-btn" onclick="setSiteFilter('laravel', this)">Laravel</button>
                    <button class="tab-btn" onclick="setSiteFilter('nextjs', this)">Next.js</button>
                    <button class="tab-btn" onclick="setSiteFilter('native_php', this)">PHP Native</button>
                </div>

                <button class="btn btn-emerald" onclick="checkAllWebsites()">
                    <i data-lucide="refresh-cw" id="checkAllWebBtnIcon" style="width: 15px; height: 15px;"></i>
                    <span>Cek Semua Web</span>
                </button>
                <button class="btn btn-neon-purple" onclick="openAddSiteModal()">
                    <i data-lucide="plus-circle" style="width: 15px; height: 15px;"></i>
                    <span>Tambah Web</span>
                </button>
            </div>
        </div>

        <!-- Site Cards Container -->
        <div class="sites-grid" id="sitesContainer">
            <!-- Rendered dynamically by renderSitesGrid() -->
        </div>
    </div>

</div>

<!-- =========================================================================
     MODAL: STB BACKUP CENTER & SNAPSHOT VAULT (backup.php)
     ========================================================================= -->
<div id="backupModal" class="modal-backdrop">
    <div class="modal-window">
        <div class="modal-header">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div class="activity-icon-avatar" style="background: rgba(168, 85, 247, 0.2); border-color: var(--border-hover);">
                    <i data-lucide="database-backup" style="width: 20px; height: 20px; color: var(--neon-purple);"></i>
                </div>
                <div>
                    <div class="card-title">STB Backup Center & Snapshot Vault</div>
                    <div class="card-subtitle">Pengelolaan script otomatis backup.php (/www/backup)</div>
                </div>
            </div>
            <button class="btn btn-icon" onclick="closeBackupModal()">
                <i data-lucide="x" style="width: 18px; height: 18px;"></i>
            </button>
        </div>

        <div class="modal-body">
            <!-- Alert Banner inside Modal -->
            <div id="modalBackupAlertBanner" style="display: none;" class="modal-alert-banner"></div>

            <!-- Status Overview Box -->
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px;">
                <div style="background: var(--bg-card-subtle); padding: 14px; border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
                    <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">Status Proses</div>
                    <div id="modalBackupStatusBadge" style="margin-top: 4px;">
                        @if($backupStatus['is_running'])
                        <span class="pill-running">Sedang Berjalan</span>
                        @else
                        <span class="pill-complete">Siap / Idle</span>
                        @endif
                    </div>
                </div>

                <div style="background: var(--bg-card-subtle); padding: 14px; border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
                    <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">Waktu Terakhir</div>
                    <div class="font-mono" id="modalLastRunTime" style="font-size: 0.95rem; font-weight: 700; color: #ffffff; margin-top: 4px;">
                        {{ $backupStatus['last_run']['human'] }}
                    </div>
                </div>

                <div style="background: var(--bg-card-subtle); padding: 14px; border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
                    <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">Total Durasi</div>
                    <div class="font-mono" id="modalLastDuration" style="font-size: 0.95rem; font-weight: 700; color: var(--neon-cyan); margin-top: 4px;">
                        {{ $backupStatus['last_run']['total_time_text'] }}
                    </div>
                </div>

                <div style="background: var(--bg-card-subtle); padding: 14px; border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
                    <div style="font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase;">Total Ukuran Vault</div>
                    <div class="font-mono" id="modalTotalSize" style="font-size: 0.95rem; font-weight: 700; color: var(--neon-purple); margin-top: 4px;">
                        {{ $backupStatus['total_size_human'] }}
                    </div>
                </div>
            </div>

            <!-- Detail Backup Terakhir Section -->
            <div>
                <div style="font-size: 0.92rem; font-weight: 700; color: #ffffff; margin-bottom: 10px; display: flex; align-items: center; justify-content: space-between;">
                    <span>Daftar File Backup Terakhir</span>
                    <span style="font-size: 0.75rem; color: var(--text-muted);">Lokasi: {{ $backupStatus['backup_dir'] }}</span>
                </div>

                <!-- Latest Database Dumps Table -->
                <div style="background: var(--bg-card-subtle); border: 1px solid var(--border-subtle); border-radius: var(--radius-md); overflow: hidden;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 0.84rem;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--border-subtle); text-align: left; color: var(--text-muted); background: rgba(255,255,255,0.02);">
                                <th style="padding: 10px 14px;">Kategori</th>
                                <th style="padding: 10px 14px;">Nama File / Snapshot</th>
                                <th style="padding: 10px 14px;">Ukuran</th>
                                <th style="padding: 10px 14px;">Waktu Pembuatan</th>
                            </tr>
                        </thead>
                        <tbody id="modalBackupFilesTable">
                            @if(isset($backupStatus['latest_files']['incremental_snapshot']))
                            <tr style="border-bottom: 1px solid var(--border-subtle);">
                                <td style="padding: 10px 14px; color: var(--neon-cyan); font-weight: 600;">📁 Hosting Rsync</td>
                                <td style="padding: 10px 14px; font-family: var(--font-mono); color: #ffffff;">{{ $backupStatus['latest_files']['incremental_snapshot']['name'] }}</td>
                                <td style="padding: 10px 14px; font-family: var(--font-mono); color: var(--neon-cyan);">{{ $backupStatus['latest_files']['incremental_snapshot']['size_human'] }}</td>
                                <td style="padding: 10px 14px; color: var(--text-muted);">{{ $backupStatus['latest_files']['incremental_snapshot']['modified_at'] }}</td>
                            </tr>
                            @endif

                            @foreach($backupStatus['latest_files']['mariadb_databases'] ?? [] as $db)
                            <tr style="border-bottom: 1px solid var(--border-subtle);">
                                <td style="padding: 10px 14px; color: var(--neon-purple); font-weight: 600;">🗄️ MariaDB</td>
                                <td style="padding: 10px 14px; font-family: var(--font-mono); color: #ffffff;">{{ $db['filename'] }}</td>
                                <td style="padding: 10px 14px; font-family: var(--font-mono); color: var(--neon-cyan);">{{ $db['size_human'] }}</td>
                                <td style="padding: 10px 14px; color: var(--text-muted);">{{ $db['created_at'] }}</td>
                            </tr>
                            @endforeach

                            @foreach($backupStatus['latest_files']['postgres_databases'] ?? [] as $pg)
                            <tr style="border-bottom: 1px solid var(--border-subtle);">
                                <td style="padding: 10px 14px; color: #60a5fa; font-weight: 600;">🐘 PostgreSQL</td>
                                <td style="padding: 10px 14px; font-family: var(--font-mono); color: #ffffff;">{{ $pg['filename'] }}</td>
                                <td style="padding: 10px 14px; font-family: var(--font-mono); color: var(--neon-cyan);">{{ $pg['size_human'] }}</td>
                                <td style="padding: 10px 14px; color: var(--text-muted);">{{ $pg['created_at'] }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Terminal Console Log Viewer -->
            <div>
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                    <div style="font-size: 0.92rem; font-weight: 700; color: #ffffff; display: flex; align-items: center; gap: 8px;">
                        <i data-lucide="terminal" style="width: 16px; height: 16px; color: var(--neon-cyan);"></i>
                        <span>Live Terminal Log (backup.log)</span>
                    </div>
                    <button class="btn" style="padding: 4px 10px; font-size: 0.75rem; background: var(--bg-card-subtle); border-color: var(--border-subtle); color: var(--text-muted);" onclick="fetchBackupLogs()">
                        <i data-lucide="refresh-cw" id="refreshLogIcon" style="width: 13px; height: 13px;"></i>
                        <span>Refresh Log</span>
                    </button>
                </div>
                <div class="terminal-viewer" id="terminalLogContainer">
                    @foreach($backupStatus['recent_logs'] ?? [] as $logLine)
                    <div class="terminal-line {{ str_contains($logLine, 'SELESAI') || str_contains($logLine, 'selesai') ? 'success' : (str_contains($logLine, 'GAGAL') ? 'error' : '') }}">
                        {{ $logLine }}
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <div style="font-size: 0.78rem; color: var(--text-muted);">
                Script: <code style="color: var(--neon-cyan);">{{ $backupStatus['script_path'] }}</code>
            </div>
            <div style="display: flex; gap: 10px;">
                <button type="button" class="btn" style="background: var(--bg-input); border-color: var(--border-subtle); color: var(--text-main);" onclick="closeBackupModal()">
                    Tutup
                </button>
                <button type="button" id="btnTriggerManualBackup" class="btn btn-neon-purple" onclick="triggerManualBackup()">
                    <i data-lucide="play" id="backupBtnIcon" style="width: 16px; height: 16px;"></i>
                    <span id="backupBtnText">Mulai Backup Sekarang</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- =========================================================================
     MODAL: NGINX SERVICE INSPECTOR
     ========================================================================= -->
<div id="nginxModal" class="modal-backdrop">
    <div class="modal-window">
        <div class="modal-header">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div class="activity-icon-avatar" style="background: rgba(0, 240, 255, 0.15); border-color: rgba(0, 240, 255, 0.3); color: var(--neon-cyan);">
                    <i data-lucide="server" style="width: 20px; height: 20px;"></i>
                </div>
                <div>
                    <div class="card-title">Nginx Virtual Hosts & Core Service</div>
                    <div class="card-subtitle">Manajemen reverse proxy, worker processes, dan syntax test</div>
                </div>
            </div>
            <button class="btn btn-icon" onclick="closeNginxModal()">
                <i data-lucide="x" style="width: 18px; height: 18px;"></i>
            </button>
        </div>
        <div class="modal-body">
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px;">
                <div style="background: var(--bg-card-subtle); padding: 14px; border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
                    <div style="font-size: 0.75rem; color: var(--text-muted);">Status Layanan</div>
                    <div style="margin-top: 4px;">
                        <span class="pill-complete">{{ $nginxStatus['status_label'] }}</span>
                    </div>
                </div>
                <div style="background: var(--bg-card-subtle); padding: 14px; border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
                    <div style="font-size: 0.75rem; color: var(--text-muted);">Versi Nginx</div>
                    <div class="font-mono" style="font-size: 0.95rem; font-weight: 700; color: #ffffff; margin-top: 4px;">{{ $nginxStatus['version'] }}</div>
                </div>
                <div style="background: var(--bg-card-subtle); padding: 14px; border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
                    <div style="font-size: 0.75rem; color: var(--text-muted);">Worker Processes</div>
                    <div class="font-mono" style="font-size: 0.95rem; font-weight: 700; color: var(--neon-cyan); margin-top: 4px;">{{ $nginxStatus['worker_processes'] }} Workers</div>
                </div>
            </div>

            <div style="display: flex; gap: 10px; margin-top: 6px;">
                <button class="btn btn-neon-cyan" onclick="testNginxSyntax()">
                    <i data-lucide="check-circle" style="width: 15px; height: 15px;"></i>
                    <span>Uji Sintaks (nginx -t)</span>
                </button>
                <button class="btn" style="background: rgba(168, 85, 247, 0.15); border-color: rgba(168, 85, 247, 0.35); color: #c084fc;" onclick="reloadNginxService()">
                    <i data-lucide="rotate-cw" style="width: 15px; height: 15px;"></i>
                    <span>Reload Nginx</span>
                </button>
            </div>

            <div id="nginxTestOutputBox" style="display: none; background: #070912; border: 1px solid var(--border-subtle); border-radius: var(--radius-md); padding: 12px; font-family: var(--font-mono); font-size: 0.8rem; color: #a5f3fc; white-space: pre-wrap;"></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn" style="background: var(--bg-input); border-color: var(--border-subtle); color: var(--text-main);" onclick="closeNginxModal()">Tutup</button>
        </div>
    </div>
</div>

<!-- =========================================================================
     MODAL: TAMBAH WEBSITE BARU
     ========================================================================= -->
<div id="addSiteModal" class="modal-backdrop">
    <div class="modal-window" style="max-width: 520px;">
        <div class="modal-header">
            <div class="card-title" id="siteModalTitle">Tambah Website / Layanan Baru</div>
            <button class="btn btn-icon" onclick="closeAddSiteModal()">
                <i data-lucide="x" style="width: 18px; height: 18px;"></i>
            </button>
        </div>
        <form id="addSiteForm" onsubmit="submitAddSite(event)">
            <input type="hidden" id="siteFormId" name="id" value="">
            <div class="modal-body">
                <div>
                    <label style="font-size: 0.84rem; color: var(--text-muted); display: block; margin-bottom: 6px;">Nama Website / Layanan</label>
                    <input type="text" id="siteFormName" name="name" required placeholder="Contoh: Portal Mahasiswa" style="width: 100%; background: var(--bg-input); border: 1px solid var(--border-subtle); border-radius: var(--radius-md); padding: 10px 14px; color: #fff; font-family: inherit; outline: none;">
                </div>
                <div>
                    <label style="font-size: 0.84rem; color: var(--text-muted); display: block; margin-bottom: 6px;">URL Website</label>
                    <input type="url" id="siteFormUrl" name="url" required placeholder="https://app.contoh.com" style="width: 100%; background: var(--bg-input); border: 1px solid var(--border-subtle); border-radius: var(--radius-md); padding: 10px 14px; color: #fff; font-family: inherit; outline: none;">
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div>
                        <label style="font-size: 0.84rem; color: var(--text-muted); display: block; margin-bottom: 6px;">Stack Layanan</label>
                        <select id="siteFormStack" name="stack_type" style="width: 100%; background: var(--bg-input); border: 1px solid var(--border-subtle); border-radius: var(--radius-md); padding: 10px 14px; color: #fff; font-family: inherit; outline: none;">
                            <option value="laravel">PHP / Laravel</option>
                            <option value="nodejs">Node.js / Express</option>
                            <option value="nextjs">Next.js</option>
                            <option value="native_php">PHP Native</option>
                            <option value="static">HTML / Statis</option>
                            <option value="other">Layanan Lainnya</option>
                        </select>
                    </div>
                    <div>
                        <label style="font-size: 0.84rem; color: var(--text-muted); display: block; margin-bottom: 6px;">Port</label>
                        <input type="number" id="siteFormPort" name="port" value="80" style="width: 100%; background: var(--bg-input); border: 1px solid var(--border-subtle); border-radius: var(--radius-md); padding: 10px 14px; color: #fff; font-family: inherit; outline: none;">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn" style="background: var(--bg-input); border-color: var(--border-subtle); color: var(--text-main);" onclick="closeAddSiteModal()">Batal</button>
                <button type="submit" class="btn btn-neon-purple" id="siteFormSubmitBtn">Simpan Layanan</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Initial data passed from Blade controller
    let initialSites = @json($sites);
    let initialMetrics = @json($metrics);
    let initialBackup = @json($backupStatus);

    let telemetryWaveChart = null;
    let distributionDonutChart = null;
    let verticalBarChart = null;
    let backupPollingTimer = null;

    // Trigger Anime.js scrambleText effect on key metrics
    function triggerScrambleAnimations() {
        if (window.scrambleElement) {
            window.scrambleElement('#scrambleKpi1', '99.98%', { duration: 900 });
            const cpuVal = String(initialMetrics.cpu.usage_percent).replace(/%+$/, '');
            const ramVal = String(initialMetrics.ram.usage_percent).replace(/%+$/, '');
            window.scrambleElement('#scrambleKpi2', `${cpuVal}%`, { duration: 900 });
            window.scrambleElement('#scrambleKpi3', initialBackup.total_size_human || '1.62 GB', { duration: 1000 });
            window.scrambleElement('#scrambleKpi4', `${ramVal}%`, { duration: 900 });
            window.scrambleElement('#scrambleDonutCenter', '100%', { duration: 1100 });
        }
    }

    // Initialize ApexCharts matching reference screenshot
    function initNeonCharts() {
        // 1. Neon Area Wave Chart (Matching "Sales Overview" in screenshot)
        const waveOptions = {
            series: [
                {
                    name: 'Network Throughput (MB/s)',
                    data: [25, 42, 38, 70, 48, 55, 68, 85, 62, 75, 58, 65]
                },
                {
                    name: 'CPU & Server Load (%)',
                    data: [18, 28, 22, 45, 34, 40, 52, 60, 44, 52, 40, 48]
                }
            ],
            chart: {
                height: 280,
                type: 'area',
                toolbar: { show: false },
                background: 'transparent',
                fontFamily: 'Outfit, sans-serif',
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 800
                }
            },
            colors: ['#00f0ff', '#a855f7'],
            stroke: {
                curve: 'smooth',
                width: 3
            },
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.45,
                    opacityTo: 0.05,
                    stops: [0, 85, 100]
                }
            },
            dataLabels: { enabled: false },
            grid: {
                borderColor: 'rgba(255, 255, 255, 0.06)',
                strokeDashArray: 4,
                yaxis: { lines: { show: true } },
                xaxis: { lines: { show: false } }
            },
            xaxis: {
                categories: ['00:00', '02:00', '04:00', '06:00', '08:00', '10:00', '12:00', '14:00', '16:00', '18:00', '20:00', 'Sekarang'],
                labels: {
                    style: { colors: '#64748b', fontSize: '11px', fontFamily: 'JetBrains Mono' }
                },
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            yaxis: {
                labels: {
                    style: { colors: '#64748b', fontSize: '11px', fontFamily: 'JetBrains Mono' }
                }
            },
            tooltip: {
                theme: 'dark',
                y: {
                    formatter: (val) => `${val}`
                }
            },
            legend: {
                position: 'top',
                horizontalAlign: 'right',
                labels: { colors: '#94a3b8' }
            }
        };

        telemetryWaveChart = new ApexCharts(document.querySelector("#neonTelemetryWaveChart"), waveOptions);
        telemetryWaveChart.render();

        // 2. Donut Ring Chart (Matching "Customer Distribution" in screenshot)
        const donutOptions = {
            series: [44, 26, 22, 23],
            chart: {
                height: 240,
                type: 'donut',
                background: 'transparent',
                fontFamily: 'Outfit, sans-serif'
            },
            colors: ['#00f0ff', '#a855f7', '#3b82f6', '#ec4899'],
            plotOptions: {
                pie: {
                    donut: {
                        size: '72%',
                        background: 'transparent'
                    }
                }
            },
            stroke: {
                width: 0
            },
            dataLabels: { enabled: false },
            tooltip: {
                theme: 'dark',
                y: {
                    formatter: (val) => `${val}% Alokasi`
                }
            },
            legend: { show: false }
        };

        distributionDonutChart = new ApexCharts(document.querySelector("#neonDistributionDonutChart"), donutOptions);
        distributionDonutChart.render();

        // 3. Vertical Bar Chart (Matching Bottom-Left in screenshot)
        const barOptions = {
            series: [{
                name: 'Throughput (Req/min)',
                data: [120, 180, 140, 260, 210, 290, 240, 320, 280, 360, 310, 420]
            }],
            chart: {
                height: 280,
                type: 'bar',
                toolbar: { show: false },
                background: 'transparent',
                fontFamily: 'Outfit, sans-serif'
            },
            plotOptions: {
                bar: {
                    borderRadius: 6,
                    columnWidth: '45%',
                    distributed: true
                }
            },
            colors: ['#00f0ff', '#0ea5e9', '#38bdf8', '#6366f1', '#8b5cf6', '#a855f7', '#c084fc', '#d946ef', '#ec4899', '#8b5cf6', '#00f0ff', '#38bdf8'],
            dataLabels: { enabled: false },
            grid: {
                borderColor: 'rgba(255, 255, 255, 0.06)',
                strokeDashArray: 4
            },
            xaxis: {
                categories: ['12h', '11h', '10h', '9h', '8h', '7h', '6h', '5h', '4h', '3h', '2h', 'Now'],
                labels: {
                    style: { colors: '#64748b', fontSize: '11px', fontFamily: 'JetBrains Mono' }
                },
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            yaxis: {
                labels: {
                    style: { colors: '#64748b', fontSize: '11px', fontFamily: 'JetBrains Mono' }
                }
            },
            tooltip: { theme: 'dark' },
            legend: { show: false }
        };

        verticalBarChart = new ApexCharts(document.querySelector("#neonVerticalBarChart"), barOptions);
        verticalBarChart.render();
    }

    // =========================================================================
    // BACKUP MANAGEMENT (backup.php)
    // =========================================================================
    function openBackupModal() {
        document.getElementById('backupModal').classList.add('active');
        fetchBackupStatus();
        fetchBackupLogs();
    }

    function closeBackupModal() {
        document.getElementById('backupModal').classList.remove('active');
    }

    async function fetchBackupStatus() {
        try {
            const res = await fetch('/api/monitoring/backup/status');
            const data = await res.json();
            
            // Update modal state
            const badgeContainer = document.getElementById('modalBackupStatusBadge');
            const btn = document.getElementById('btnTriggerManualBackup');
            const btnText = document.getElementById('backupBtnText');
            const headerBadge = document.getElementById('headerBackupBadge');
            
            if (data.is_running) {
                badgeContainer.innerHTML = '<span class="pill-running">Sedang Berjalan</span>';
                if (btn) btn.disabled = true;
                if (btnText) btnText.textContent = 'Backup Sedang Berjalan...';
                if (headerBadge) headerBadge.textContent = 'RUNNING';
            } else {
                badgeContainer.innerHTML = '<span class="pill-complete">Siap / Idle</span>';
                if (btn) btn.disabled = false;
                if (btnText) btnText.textContent = 'Mulai Backup Sekarang';
                if (headerBadge) headerBadge.textContent = 'READY';
            }

            if (data.last_run) {
                document.getElementById('modalLastRunTime').textContent = data.last_run.human;
                document.getElementById('modalLastDuration').textContent = data.last_run.total_time_text;
            }
            if (data.total_size_human) {
                document.getElementById('modalTotalSize').textContent = data.total_size_human;
                document.getElementById('scrambleKpi3').textContent = data.total_size_human;
            }
        } catch (e) {
            console.error('Failed to fetch backup status:', e);
        }
    }

    async function fetchBackupLogs() {
        const icon = document.getElementById('refreshLogIcon');
        if (icon) icon.classList.add('animate-spin');

        try {
            const res = await fetch('/api/monitoring/backup/logs?lines=40');
            const data = await res.json();
            const container = document.getElementById('terminalLogContainer');
            
            if (container && data.logs) {
                container.innerHTML = data.logs.map(line => {
                    const isSuccess = line.includes('SELESAI') || line.includes('selesai');
                    const isError = line.includes('GAGAL');
                    return `<div class="terminal-line ${isSuccess ? 'success' : (isError ? 'error' : '')}">${line}</div>`;
                }).join('');

                // Auto scroll to bottom of log
                container.scrollTop = container.scrollHeight;
            }
        } catch (e) {
            console.error('Failed to fetch backup logs:', e);
        } finally {
            if (icon) icon.classList.remove('animate-spin');
        }
    }

    async function triggerManualBackup() {
        if (!confirm('Jalankan proses backup folder hosting & database server sekarang via backup.php?')) {
            return;
        }

        const btn = document.getElementById('btnTriggerManualBackup');
        const btnText = document.getElementById('backupBtnText');
        const alertBanner = document.getElementById('modalBackupAlertBanner');

        if (btn) btn.disabled = true;
        if (btnText) btnText.textContent = 'Memulai Backup...';

        if (alertBanner) {
            alertBanner.className = 'modal-alert-banner running';
            alertBanner.style.display = 'flex';
            alertBanner.innerHTML = `
                <i data-lucide="loader-2" class="animate-spin" style="width: 20px; height: 20px; color: var(--neon-cyan); flex-shrink: 0;"></i>
                <div style="flex: 1;">
                    <div style="font-weight: 700; color: #ffffff;">Proses Backup Sedang Berjalan...</div>
                    <div style="font-size: 0.8rem; opacity: 0.85; margin-top: 2px;">Script /www/backup/backup.php sedang mencadangkan hosting & database. Mohon tunggu...</div>
                </div>
            `;
            if (window.lucide) window.lucide.createIcons({ root: alertBanner });
        }

        try {
            const res = await fetch('/api/monitoring/backup/run', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });
            const data = await res.json();

            if (data.success) {
                window.showToast(data.message, 'info', 'Backup Dimulai');
                fetchBackupStatus();
                fetchBackupLogs();

                // Poll logs every 3 seconds while running
                if (backupPollingTimer) clearInterval(backupPollingTimer);
                backupPollingTimer = setInterval(async () => {
                    await fetchBackupLogs();
                    const statusRes = await fetch('/api/monitoring/backup/status');
                    const statusData = await statusRes.json();
                    if (!statusData.is_running) {
                        clearInterval(backupPollingTimer);
                        fetchBackupStatus();
                        
                        if (alertBanner) {
                            alertBanner.className = 'modal-alert-banner success';
                            alertBanner.style.display = 'flex';
                            alertBanner.innerHTML = `
                                <i data-lucide="check-circle-2" style="width: 22px; height: 22px; color: #10b981; flex-shrink: 0;"></i>
                                <div style="flex: 1;">
                                    <div style="font-weight: 700; color: #ffffff;">Proses Backup Selesai!</div>
                                    <div style="font-size: 0.8rem; opacity: 0.9; margin-top: 2px;">File snapshot incremental dan dump database MariaDB/PostgreSQL telah berhasil diperbarui.</div>
                                </div>
                                <button onclick="this.parentElement.style.display='none'" class="toast-close-btn" style="color: rgba(255,255,255,0.7);" title="Tutup">
                                    <i data-lucide="x" style="width: 15px; height: 15px;"></i>
                                </button>
                            `;
                            if (window.lucide) window.lucide.createIcons({ root: alertBanner });
                        }

                        window.showToast('Proses backup selesai! File snapshot dan dump database telah diperbarui.', 'success', 'Backup Selesai');
                    }
                }, 3000);
            } else {
                if (alertBanner) {
                    alertBanner.className = 'modal-alert-banner error';
                    alertBanner.style.display = 'flex';
                    alertBanner.innerHTML = `
                        <i data-lucide="alert-octagon" style="width: 20px; height: 20px; color: #f43f5e; flex-shrink: 0;"></i>
                        <div style="flex: 1;">
                            <div style="font-weight: 700; color: #ffffff;">Gagal Menjalankan Backup</div>
                            <div style="font-size: 0.8rem; margin-top: 2px;">${data.message}</div>
                        </div>
                    `;
                    if (window.lucide) window.lucide.createIcons({ root: alertBanner });
                }
                window.showToast(data.message, 'error', 'Backup Gagal');
                if (btn) btn.disabled = false;
                if (btnText) btnText.textContent = 'Mulai Backup Sekarang';
            }
        } catch (e) {
            if (alertBanner) {
                alertBanner.className = 'modal-alert-banner error';
                alertBanner.style.display = 'flex';
                alertBanner.innerHTML = `
                    <i data-lucide="alert-octagon" style="width: 20px; height: 20px; color: #f43f5e; flex-shrink: 0;"></i>
                    <div style="flex: 1;">
                        <div style="font-weight: 700; color: #ffffff;">Terjadi Kesalahan</div>
                        <div style="font-size: 0.8rem; margin-top: 2px;">${e.message}</div>
                    </div>
                `;
                if (window.lucide) window.lucide.createIcons({ root: alertBanner });
            }
            window.showToast('Gagal memicu backup: ' + e.message, 'error', 'Error');
            if (btn) btn.disabled = false;
            if (btnText) btnText.textContent = 'Mulai Backup Sekarang';
        }
    }

    // =========================================================================
    // NGINX SERVICE & SITE ACTIONS
    // =========================================================================
    function openNginxModal() {
        document.getElementById('nginxModal').classList.add('active');
    }

    function closeNginxModal() {
        document.getElementById('nginxModal').classList.remove('active');
    }

    async function testNginxSyntax() {
        try {
            const res = await fetch('/api/monitoring/nginx/test', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') }
            });
            const data = await res.json();
            const box = document.getElementById('nginxTestOutputBox');
            box.style.display = 'block';
            box.textContent = data.output || 'Nginx test passed.';
            window.showToast(data.success ? 'Sintaks Nginx Valid' : 'Sintaks Nginx Error', data.success ? 'success' : 'error');
        } catch (e) {
            window.showToast('Gagal menguji sintaks Nginx', 'error');
        }
    }

    async function reloadNginxService() {
        try {
            const res = await fetch('/api/monitoring/nginx/reload', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') }
            });
            const data = await res.json();
            window.showToast(data.message, data.success ? 'success' : 'error');
        } catch (e) {
            window.showToast('Gagal me-reload Nginx', 'error');
        }
    }

    // =========================================================================
    // MONITORED WEBSITES (SITES GRID, CRUD & CHECKS)
    // =========================================================================
    let currentSiteFilter = 'all';
    let currentSearchKeyword = '';

    function escapeHtml(text) {
        if (!text) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function setSiteFilter(filter, btn) {
        currentSiteFilter = filter;
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        if (btn) btn.classList.add('active');
        renderSitesGrid(initialSites);
    }

    function filterSearch(keyword) {
        currentSearchKeyword = (keyword || '').toLowerCase().trim();
        renderSitesGrid(initialSites);
    }

    function applyStackFilter(stack) {
        currentSiteFilter = stack;
        renderSitesGrid(initialSites);
    }

    function renderSitesGrid(sitesToRender) {
        const container = document.getElementById('sitesContainer');
        if (!container) return;

        // Update counts
        const totalCountEl = document.getElementById('totalSitesCount');
        const countAllEl = document.getElementById('countFilterAll');
        if (totalCountEl) totalCountEl.textContent = `${sitesToRender.length} Web Dipantau`;
        if (countAllEl) countAllEl.textContent = sitesToRender.length;

        const filtered = sitesToRender.filter(site => {
            // Filter category
            if (currentSiteFilter === 'online' && !site.is_online) return false;
            if (currentSiteFilter === 'offline' && site.is_online) return false;
            if (currentSiteFilter === 'laravel' && site.stack_type !== 'laravel') return false;
            if (currentSiteFilter === 'nextjs' && site.stack_type !== 'nextjs') return false;
            if (currentSiteFilter === 'native_php' && site.stack_type !== 'native_php') return false;

            // Search query
            if (currentSearchKeyword) {
                const matchName = (site.name || '').toLowerCase().includes(currentSearchKeyword);
                const matchUrl = (site.url || '').toLowerCase().includes(currentSearchKeyword);
                const matchPort = site.port && site.port.toString().includes(currentSearchKeyword);
                if (!matchName && !matchUrl && !matchPort) return false;
            }
            return true;
        });

        if (filtered.length === 0) {
            container.innerHTML = `
                <div style="grid-column: 1 / -1; text-align: center; padding: 48px 24px; background: var(--bg-card); border: 1px solid var(--border-subtle); border-radius: var(--radius-lg);">
                    <i data-lucide="globe" style="width: 42px; height: 42px; color: var(--neon-cyan); margin-bottom: 12px; opacity: 0.8;"></i>
                    <h3 style="font-size: 1.1rem; font-weight: 700; color: #ffffff; margin-bottom: 6px;">Tidak ada website yang cocok</h3>
                    <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 20px;">Belum ada website yang terdaftar atau tidak cocok dengan filter aktif.</p>
                    <button class="btn btn-neon-purple" onclick="openAddSiteModal()">
                        <i data-lucide="plus-circle" style="width: 15px; height: 15px;"></i>
                        <span>Tambah Website Baru</span>
                    </button>
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

            let stackClass = 'stack-other';
            let stackLabel = 'Custom Web';
            let stackIcon = '🌐';

            if (site.stack_type === 'nextjs') {
                stackClass = 'stack-nextjs';
                stackLabel = 'Next.js';
                stackIcon = '⚡';
            } else if (site.stack_type === 'laravel') {
                stackClass = 'stack-laravel';
                stackLabel = 'Laravel';
                stackIcon = '🔴';
            } else if (site.stack_type === 'native_php') {
                stackClass = 'stack-native_php';
                stackLabel = 'PHP Native';
                stackIcon = '🐘';
            }

            let latencyClass = 'fast';
            const ms = site.last_response_time_ms || 0;
            if (!isOnline) latencyClass = 'error';
            else if (ms > 500) latencyClass = 'slow';
            else if (ms > 150) latencyClass = 'normal';

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

            const cleanUptime = String(site.uptime_percentage !== undefined && site.uptime_percentage !== null ? site.uptime_percentage : 100).replace(/%+$/, '');

            html += `
                <div class="site-card" id="site-card-${site.id}">
                    <div class="site-card-top">
                        <div class="site-meta">
                            <div class="stack-icon-wrapper ${stackClass}">
                                ${stackIcon}
                            </div>
                            <div class="site-title-area">
                                <div class="site-title" title="${escapeHtml(site.name)}">
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
                            <span class="site-metric-lbl">Latency Ping</span>
                        </div>
                        <div class="site-metric-cell">
                            <span class="site-metric-val font-mono" style="color: var(--neon-cyan);">
                                ${stackLabel}
                            </span>
                            <span class="site-metric-lbl">Port ${site.port || 80}</span>
                        </div>
                    </div>

                    <div class="uptime-history-wrap">
                        <div class="uptime-header">
                            <span>Riwayat Uptime (24 Jam)</span>
                            <span class="font-mono" style="font-weight: 700; color: #10b981;">${cleanUptime}%</span>
                        </div>
                        <div class="uptime-bars">
                            ${historyBarsHtml}
                        </div>
                    </div>

                    <div class="site-card-footer">
                        <span class="font-mono" style="font-size: 0.72rem; color: var(--text-muted);">
                            <i data-lucide="clock" style="width: 11px; height: 11px; display: inline;"></i> ${site.last_checked_at}
                        </span>
                        <div class="site-actions-row">
                            <button class="btn btn-icon" style="width: 32px; height: 32px;" onclick="checkSingleSite(${site.id})" title="Cek Sekarang">
                                <i data-lucide="refresh-cw" id="checkIcon-${site.id}" style="width: 13px; height: 13px; color: var(--neon-cyan);"></i>
                            </button>
                            <button class="btn btn-icon" style="width: 32px; height: 32px;" onclick="openEditSiteModal(${site.id})" title="Edit Web">
                                <i data-lucide="edit-3" style="width: 13px; height: 13px; color: #c084fc;"></i>
                            </button>
                            <button class="btn btn-icon" style="width: 32px; height: 32px; color: #f43f5e;" onclick="deleteSite(${site.id})" title="Hapus Web">
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

    async function checkSingleSite(id) {
        const icon = document.getElementById(`checkIcon-${id}`);
        if (icon) icon.classList.add('animate-spin');

        try {
            const res = await fetch(`/api/monitoring/sites/${id}/check`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') }
            });
            const data = await res.json();
            
            // Update local site in array
            const idx = initialSites.findIndex(s => s.id === id);
            if (idx !== -1) {
                initialSites[idx] = { ...initialSites[idx], ...data.site, is_online: data.online, last_response_time_ms: data.response_time_ms, last_status_code: data.status_code, last_checked_at: 'Baru saja' };
            }
            renderSitesGrid(initialSites);
            window.showToast(`Pemeriksaan selesai: ${data.online ? 'Online' : 'Offline'} (${data.response_time_ms}ms)`, data.online ? 'success' : 'error');
        } catch (e) {
            window.showToast('Gagal memeriksa situs: ' + e.message, 'error');
        } finally {
            if (icon) icon.classList.remove('animate-spin');
        }
    }

    function openAddSiteModal() {
        document.getElementById('siteModalTitle').textContent = 'Tambah Website / Layanan Baru';
        document.getElementById('siteFormId').value = '';
        document.getElementById('siteFormName').value = '';
        document.getElementById('siteFormUrl').value = '';
        document.getElementById('siteFormStack').value = 'laravel';
        document.getElementById('siteFormPort').value = '80';
        document.getElementById('siteFormSubmitBtn').textContent = 'Simpan Layanan';
        document.getElementById('addSiteModal').classList.add('active');
    }

    function openEditSiteModal(id) {
        const site = initialSites.find(s => s.id === id);
        if (!site) return;

        document.getElementById('siteModalTitle').textContent = 'Edit Website / Layanan';
        document.getElementById('siteFormId').value = site.id;
        document.getElementById('siteFormName').value = site.name;
        document.getElementById('siteFormUrl').value = site.url;
        document.getElementById('siteFormStack').value = site.stack_type;
        document.getElementById('siteFormPort').value = site.port || 80;
        document.getElementById('siteFormSubmitBtn').textContent = 'Perbarui Layanan';
        document.getElementById('addSiteModal').classList.add('active');
    }

    function closeAddSiteModal() {
        document.getElementById('addSiteModal').classList.remove('active');
    }

    async function submitAddSite(event) {
        event.preventDefault();
        const form = event.target;
        const formData = new FormData(form);
        const payload = Object.fromEntries(formData.entries());
        const isEdit = !!payload.id;

        const url = isEdit ? `/api/monitoring/sites/${payload.id}` : '/api/monitoring/sites';
        const method = isEdit ? 'PUT' : 'POST';

        try {
            const res = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (data.success) {
                window.showToast(`Layanan berhasil ${isEdit ? 'diperbarui' : 'ditambahkan'}!`, 'success');
                closeAddSiteModal();
                form.reset();

                // Refresh sites list
                const sitesRes = await fetch('/api/monitoring/sites');
                initialSites = await sitesRes.json();
                renderSitesGrid(initialSites);
            } else {
                window.showToast(data.message || 'Gagal menyimpan layanan', 'error');
            }
        } catch (e) {
            window.showToast('Gagal memproses layanan: ' + e.message, 'error');
        }
    }

    async function deleteSite(id) {
        if (!confirm('Yakin ingin menghapus monitoring website ini?')) return;

        try {
            const res = await fetch(`/api/monitoring/sites/${id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') }
            });
            const data = await res.json();
            if (data.success) {
                window.showToast('Layanan berhasil dihapus', 'success');
                initialSites = initialSites.filter(s => s.id !== id);
                renderSitesGrid(initialSites);
            } else {
                window.showToast(data.message || 'Gagal menghapus layanan', 'error');
            }
        } catch (e) {
            window.showToast('Gagal menghapus: ' + e.message, 'error');
        }
    }

    async function checkAllWebsites() {
        const icon = document.getElementById('checkAllIcon');
        const icon2 = document.getElementById('checkAllWebBtnIcon');
        if (icon) icon.classList.add('animate-spin');
        if (icon2) icon2.classList.add('animate-spin');

        try {
            const res = await fetch('/api/monitoring/check-all', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') }
            });
            const data = await res.json();
            window.showToast(`Pemeriksaan selesai: ${data.total_checked} web diperiksa`, 'success');
            
            // Refresh sites list from server
            const sitesRes = await fetch('/api/monitoring/sites');
            initialSites = await sitesRes.json();
            renderSitesGrid(initialSites);
            triggerScrambleAnimations();
        } catch (e) {
            window.showToast('Gagal memeriksa situs: ' + e.message, 'error');
        } finally {
            if (icon) icon.classList.remove('animate-spin');
            if (icon2) icon2.classList.remove('animate-spin');
        }
    }

    // Telemetry Polling (every 5s)
    setInterval(async () => {
        try {
            const res = await fetch('/api/monitoring/metrics');
            const metrics = await res.json();
            
            // Scramble updated metrics
            if (window.scrambleElement) {
                const cVal = String(metrics.cpu.usage_percent).replace(/%+$/, '');
                const rVal = String(metrics.ram.usage_percent).replace(/%+$/, '');
                window.scrambleElement('#scrambleKpi2', `${cVal}%`, { duration: 700 });
                window.scrambleElement('#scrambleKpi4', `${rVal}%`, { duration: 700 });
            }
        } catch (e) {
            // Silently retry
        }
    }, 5000);

    // Document Ready Initialization
    document.addEventListener('DOMContentLoaded', () => {
        initNeonCharts();
        renderSitesGrid(initialSites);
        setTimeout(triggerScrambleAnimations, 400);
        if (window.lucide) window.lucide.createIcons();
    });
</script>
@endpush
