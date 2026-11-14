@extends('layouts.app')
@section('title', 'Dashboard')
@section('heading', 'Dashboard')
@section('server-switch', '1')

@section('actions')
    <span class="pill plain" id="statePill">Memuat…</span>
    @if($currentServer?->portainer_url)
        <a class="btn" href="{{ $currentServer->portainer_url }}" target="_blank" rel="noopener">@icon('box', 16)<span>Portainer</span></a>
    @endif
@endsection

@push('head')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.54.1/dist/apexcharts.min.js"></script>
@endpush

@section('content')
<div id="offlineNotice" class="notice hidden" style="margin-bottom:16px"></div>

<div class="grid grid-4">
    <div class="card kpi">
        <div class="kpi-top"><span class="card-title">CPU</span><span class="pill" id="cpuPill">—</span></div>
        <div class="kpi-value mono" id="cpuVal">—</div>
        <div class="meter" style="margin-top:14px"><i id="cpuBar" style="width:0"></i></div>
        <div class="card-sub" id="cpuSub">&nbsp;</div>
    </div>
    <div class="card kpi">
        <div class="kpi-top"><span class="card-title">Memori</span><span class="pill" id="ramPill">—</span></div>
        <div class="kpi-value mono" id="ramVal">—</div>
        <div class="meter" style="margin-top:14px"><i id="ramBar" style="width:0"></i></div>
        <div class="card-sub" id="ramSub">&nbsp;</div>
    </div>
    <div class="card kpi">
        <div class="kpi-top"><span class="card-title">Penyimpanan</span><span class="pill" id="diskPill">—</span></div>
        <div class="kpi-value mono" id="diskVal">—</div>
        <div class="meter" style="margin-top:14px"><i id="diskBar" style="width:0"></i></div>
        <div class="card-sub" id="diskSub">&nbsp;</div>
    </div>
    <div class="card kpi">
        <div class="kpi-top"><span class="card-title">Uptime</span><span class="pill plain" id="tempPill">—</span></div>
        <div class="kpi-value mono" id="upVal">—</div>
        <div class="card-sub" id="upSub" style="margin-top:22px">&nbsp;</div>
    </div>
</div>

<div class="card" style="margin-top:16px">
    <div class="card-head" style="margin-bottom:6px">
        <div>
            <div class="card-title" id="chartTitle">Penggunaan CPU</div>
            <div class="chart-value mono" id="chartValue" style="margin-top:6px">—</div>
        </div>
        <div class="seg" id="chartSeg">
            <button class="active" data-k="cpu">CPU</button>
            <button data-k="ram">Memori</button>
            <button data-k="io">Disk I/O</button>
        </div>
    </div>
    <div class="chart-wrap"><div id="chart"></div></div>
</div>

<div class="card" style="margin-top:16px">
    <div class="card-head">
        <div>
            <div class="card-title">Semua server</div>
        </div>
        <a class="btn btn-sm" href="{{ route('servers') }}">Kelola</a>
    </div>
    <div class="grid grid-3" id="serverTiles"></div>
</div>

<div class="grid grid-main" style="margin-top:16px">
    <div class="card">
        <div class="card-head">
            <div>
                <div class="card-title">Website</div>
                <div class="card-sub">{{ $sites->where('is_online', true)->count() }} dari {{ $sites->count() }} online</div>
            </div>
            <a class="btn btn-sm" href="{{ route('websites') }}">Lihat semua</a>
        </div>
        @forelse($sites->take(6) as $site)
            <div class="row-item">
                <span class="dot {{ $site['is_online'] ? 'ok' : 'bad' }}"></span>
                <div class="grow">
                    <b>{{ $site['name'] }}</b>
                    <span class="cell-sub">{{ $site['url'] }}</span>
                </div>
                <span class="mono muted" style="font-size:12.5px">{{ $site['is_online'] ? round($site['last_response_time_ms'] ?? 0).' ms' : 'offline' }}</span>
                <span class="mono faint" style="font-size:12.5px; width:56px; text-align:right">{{ rtrim(rtrim((string) $site['uptime_percentage'], '%'), '.') }}%</span>
            </div>
        @empty
            <div class="empty"><b>Belum ada website</b>Tambahkan website di halaman Websites.</div>
        @endforelse
    </div>

    <div class="stack">
        <div class="card">
            <div class="card-head"><div class="card-title">Informasi sistem</div></div>
            <dl class="kv" id="sysInfo"><div><dt>Memuat…</dt><dd></dd></div></dl>
        </div>
        <div class="card">
            <div class="card-head"><div class="card-title">Partisi</div></div>
            <div id="partitions" class="stack" style="gap:14px"></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const SERVER_ID = {{ $currentServer?->id ?? 'null' }};
    const $ = id => document.getElementById(id);
    let chart = null, tab = 'cpu', history = [], busy = false;

    const titles = { cpu: 'Penggunaan CPU', ram: 'Penggunaan memori', io: 'Disk I/O (KB/s)' };

    function setKpi(key, value, sub) {
        const lvl = App.level(value);
        $(key + 'Val').textContent = App.pct(value);
        $(key + 'Bar').style.width = Math.min(100, value) + '%';
        $(key + 'Bar').parentElement.className = 'meter ' + (lvl === 'ok' ? '' : lvl);
        $(key + 'Pill').className = 'pill ' + lvl;
        $(key + 'Pill').textContent = App.levelText(value);
        $(key + 'Sub').textContent = sub;
    }

    function renderMetrics(m) {
        $('offlineNotice').classList.add('hidden');
        $('statePill').className = 'pill ok'; $('statePill').textContent = 'Online';

        setKpi('cpu', m.cpu.usage_percent, `${m.cpu.cores} core · load ${m.cpu.load_avg.join(' / ')}`);
        setKpi('ram', m.ram.usage_percent, `${Math.round(m.ram.used_mb)} MB dari ${Math.round(m.ram.total_mb)} MB`);
        setKpi('disk', m.disk.usage_percent, `${m.disk.used_gb} GB dari ${m.disk.total_gb} GB`);

        $('upVal').textContent = m.system.uptime_formatted;
        $('upSub').textContent = m.system.hostname;
        const t = m.temperature?.celsius;
        $('tempPill').textContent = t ? `${t}°C` : 'Suhu n/a';

        const rows = [
            ['Host', m.system.hostname], ['Sistem operasi', m.system.os], ['Kernel', m.system.kernel],
            ['Arsitektur', m.system.architecture], ['Perangkat', m.system.model],
        ];
        $('sysInfo').innerHTML = rows.map(([k, v]) => `<div><dt>${App.esc(k)}</dt><dd>${App.esc(v)}</dd></div>`).join('');

        $('partitions').innerHTML = (m.disk.partitions || []).map(p => {
            const l = App.level(p.usage_percent);
            return `<div>
                <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:8px">
                    <span>${App.esc(p.mount)} <span class="faint">${App.esc(p.device || '')}</span></span>
                    <span class="mono muted">${p.used_gb} / ${p.total_gb} GB</span>
                </div>
                <div class="meter ${l === 'ok' ? '' : l}"><i style="width:${Math.min(100, p.usage_percent)}%"></i></div>
            </div>`;
        }).join('') || '<div class="faint">Tidak ada data.</div>';

        history = m.history || [];
        renderChart();
    }

    function renderOffline(msg) {
        $('statePill').className = 'pill bad'; $('statePill').textContent = 'Offline';
        const n = $('offlineNotice'); n.textContent = msg || 'Server tidak dapat dihubungi.'; n.classList.remove('hidden');
    }

    function series() {
        if (tab === 'io') {
            return [
                { name: 'Baca', data: history.map(h => ({ x: h.time, y: h.disk_read })) },
                { name: 'Tulis', data: history.map(h => ({ x: h.time, y: h.disk_write })) },
            ];
        }
        return [{ name: tab === 'cpu' ? 'CPU' : 'Memori', data: history.map(h => ({ x: h.time, y: h[tab] })) }];
    }

    function renderChart() {
        const last = history[history.length - 1];
        $('chartTitle').textContent = titles[tab];
        if (last) {
            $('chartValue').textContent = tab === 'io'
                ? `${Math.round(last.disk_read)} / ${Math.round(last.disk_write)} KB/s`
                : App.pct(last[tab]);
        }

        const opts = {
            chart: { type: 'area', height: 270, toolbar: { show: false }, background: 'transparent', fontFamily: 'Inter, sans-serif',
                     animations: { enabled: true, easing: 'easeinout', speed: 400, dynamicAnimation: { speed: 400 } },
                     zoom: { enabled: false }, sparkline: { enabled: false } },
            series: series(),
            colors: ['#e4e4e7', '#71717a'],
            stroke: { curve: 'smooth', width: [1.6, 1.4] },
            fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: .28, opacityTo: 0, stops: [0, 95, 100] } },
            dataLabels: { enabled: false },
            grid: { borderColor: 'rgba(255,255,255,.06)', strokeDashArray: 4, padding: { left: 14, right: 14 } },
            xaxis: { type: 'category', tickAmount: 6, labels: { style: { colors: '#6c6c75', fontSize: '11px' }, rotate: 0, hideOverlappingLabels: true },
                     axisBorder: { show: false }, axisTicks: { show: false }, tooltip: { enabled: false },
                     crosshairs: { stroke: { color: 'rgba(255,255,255,.35)', dashArray: 0 } } },
            yaxis: { min: 0, max: tab === 'io' ? undefined : 100, tickAmount: 4,
                     labels: { style: { colors: '#6c6c75', fontSize: '11px' }, formatter: v => Math.round(v) } },
            markers: { size: 0, hover: { size: 4 }, colors: ['#fff'], strokeColors: '#fff' },
            legend: { show: tab === 'io', position: 'top', horizontalAlign: 'right', labels: { colors: '#a1a1aa' }, markers: { width: 8, height: 8 } },
            tooltip: { theme: 'dark', x: { show: true }, y: { formatter: v => tab === 'io' ? `${Math.round(v)} KB/s` : `${v}%` } },
        };

        if (!chart) {
            chart = new ApexCharts($('chart'), opts);
            chart.render();
        } else {
            chart.updateOptions(opts, false, false);
        }
    }

    document.querySelectorAll('#chartSeg button').forEach(b => b.addEventListener('click', () => {
        document.querySelectorAll('#chartSeg button').forEach(x => x.classList.remove('active'));
        b.classList.add('active'); tab = b.dataset.k;
        if (chart) { chart.destroy(); chart = null; }
        renderChart();
    }));

    async function loadMetrics() {
        if (busy) return; busy = true;
        try {
            renderMetrics(await App.api(`/api/monitoring/metrics?server=${SERVER_ID ?? ''}`));
        } catch (e) {
            renderOffline(e.message);
        } finally { busy = false; }
    }

    async function loadServers() {
        try {
            const list = await App.api('/api/monitoring/servers/overview');
            $('serverTiles').innerHTML = list.map(s => {
                const bar = (label, v) => {
                    const l = App.level(v);
                    return `<div class="mini-metric"><span>${label}</span><div class="meter ${l === 'ok' ? '' : l}"><i style="width:${Math.min(100, v)}%"></i></div><span class="mono">${App.pct(v)}</span></div>`;
                };
                const body = s.online
                    ? `${bar('CPU', s.cpu)}${bar('Memori', s.ram)}${bar('Disk', s.disk)}`
                    : `<div class="faint" style="font-size:12.5px">${App.esc(s.error || 'Tidak dapat dihubungi')}</div>`;
                const meta = s.online ? `${App.esc(s.hostname || '')}${s.type === 'remote' ? ' · ' + s.latency_ms + ' ms' : ''}` : App.esc(s.base_url || '');
                return `<a class="server-tile ${s.id === SERVER_ID ? 'active' : ''}" href="/servers/${s.id}/select">
                    <div class="server-tile-head">
                        <div class="server-tile-name"><span class="dot ${s.online ? 'ok' : 'bad'}"></span><b>${App.esc(s.name)}</b></div>
                        <span class="pill plain">${s.type === 'local' ? 'Lokal' : 'Remote'}</span>
                    </div>
                    <div class="cell-sub" style="margin-top:-8px">${meta}</div>
                    ${body}
                </a>`;
            }).join('');
        } catch (e) { /* biarkan tile terakhir */ }
    }

    loadMetrics(); loadServers();
    setInterval(loadMetrics, 5000);
    setInterval(loadServers, 15000);
})();
</script>
@endpush
