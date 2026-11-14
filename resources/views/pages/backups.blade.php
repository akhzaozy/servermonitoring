@extends('layouts.app')
@section('title', 'Backups')
@section('heading', 'Backups')

@section('actions')
    <button class="btn btn-primary" type="button" id="btnRun" @disabled($backup['is_running'])>@icon('play', 14)<span id="btnRunText">{{ $backup['is_running'] ? 'Sedang berjalan…' : 'Jalankan backup' }}</span></button>
@endsection

@section('content')
@if(!empty($backup['is_mock']))
    <div class="notice" style="margin-bottom:16px">Script backup tidak ditemukan di server ini, sehingga data di bawah hanya contoh tampilan.</div>
@endif

<div class="grid grid-4">
    <div class="card kpi">
        <div class="kpi-top"><span class="card-title">Status</span></div>
        <div style="margin-top:22px" id="bStatus"><span class="pill {{ $backup['is_running'] ? 'warn' : 'ok' }}">{{ $backup['is_running'] ? 'Berjalan' : 'Siap' }}</span></div>
    </div>
    <div class="card kpi">
        <div class="kpi-top"><span class="card-title">Backup terakhir</span></div>
        <div class="kpi-value mono" style="font-size:20px" id="bLast">{{ $backup['last_run']['human'] ?? '—' }}</div>
    </div>
    <div class="card kpi">
        <div class="kpi-top"><span class="card-title">Durasi</span></div>
        <div class="kpi-value mono" style="font-size:20px" id="bDur">{{ $backup['last_run']['total_time_text'] ?? '—' }}</div>
    </div>
    <div class="card kpi">
        <div class="kpi-top"><span class="card-title">Total ukuran</span></div>
        <div class="kpi-value mono" style="font-size:20px" id="bSize">{{ $backup['total_size_human'] ?? '—' }}</div>
    </div>
</div>

<div class="card" style="margin-top:16px">
    <div class="card-head">
        <div><div class="card-title">File backup terbaru</div><div class="card-sub mono">{{ $backup['backup_dir'] }}</div></div>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Jenis</th><th>Nama file</th><th>Ukuran</th><th>Dibuat</th></tr></thead>
            <tbody>
                @php $f = $backup['latest_files'] ?? []; @endphp
                @if(!empty($f['incremental_snapshot']))
                    <tr><td>File hosting</td><td class="mono">{{ $f['incremental_snapshot']['name'] }}</td><td class="mono">{{ $f['incremental_snapshot']['size_human'] }}</td><td class="muted">{{ $f['incremental_snapshot']['modified_at'] }}</td></tr>
                @endif
                @foreach($f['mariadb_databases'] ?? [] as $db)
                    <tr><td>MariaDB</td><td class="mono">{{ $db['filename'] }}</td><td class="mono">{{ $db['size_human'] }}</td><td class="muted">{{ $db['created_at'] }}</td></tr>
                @endforeach
                @foreach($f['postgres_databases'] ?? [] as $pg)
                    <tr><td>PostgreSQL</td><td class="mono">{{ $pg['filename'] }}</td><td class="mono">{{ $pg['size_human'] }}</td><td class="muted">{{ $pg['created_at'] }}</td></tr>
                @endforeach
                @if(empty($f['incremental_snapshot']) && empty($f['mariadb_databases']) && empty($f['postgres_databases']))
                    <tr><td colspan="4"><div class="empty"><b>Belum ada file backup</b></div></td></tr>
                @endif
            </tbody>
        </table>
    </div>
</div>

<div class="card" style="margin-top:16px">
    <div class="card-head">
        <div class="card-title">Log backup</div>
        <button class="btn btn-sm" type="button" id="btnLog">@icon('refresh', 14)<span>Muat ulang</span></button>
    </div>
    <div class="logbox" id="logbox">
        @foreach($backup['recent_logs'] ?? [] as $line)
            <div class="{{ str_contains(strtolower($line), 'selesai') ? 'ok' : (str_contains($line, 'GAGAL') ? 'bad' : '') }}">{{ $line }}</div>
        @endforeach
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const $ = id => document.getElementById(id);
    let timer = null;

    function logLines(lines) {
        $('logbox').innerHTML = lines.map(l => {
            const c = /selesai/i.test(l) ? 'ok' : (/gagal/i.test(l) ? 'bad' : '');
            return `<div class="${c}">${App.esc(l)}</div>`;
        }).join('');
        $('logbox').scrollTop = $('logbox').scrollHeight;
    }

    async function loadLogs() { try { logLines((await App.api('/api/monitoring/backup/logs?lines=60')).logs || []); } catch (e) { App.toast(e.message, 'error'); } }

    async function loadStatus() {
        const d = await App.api('/api/monitoring/backup/status');
        $('bStatus').innerHTML = `<span class="pill ${d.is_running ? 'warn' : 'ok'}">${d.is_running ? 'Berjalan' : 'Siap'}</span>`;
        $('bLast').textContent = d.last_run?.human ?? '—';
        $('bDur').textContent = d.last_run?.total_time_text ?? '—';
        $('bSize').textContent = d.total_size_human ?? '—';
        $('btnRun').disabled = !!d.is_running;
        $('btnRunText').textContent = d.is_running ? 'Sedang berjalan…' : 'Jalankan backup';
        return d;
    }

    function poll() {
        clearInterval(timer);
        timer = setInterval(async () => {
            try {
                await loadLogs();
                const d = await loadStatus();
                if (!d.is_running) { clearInterval(timer); App.toast('Backup selesai'); }
            } catch (e) { /* abaikan */ }
        }, 3000);
    }

    $('btnRun').addEventListener('click', async () => {
        if (!confirm('Jalankan backup file hosting dan database sekarang?')) return;
        $('btnRun').disabled = true;
        try {
            const r = await App.api('/api/monitoring/backup/run', { method: 'POST' });
            App.toast(r.message || 'Backup dimulai', 'info');
            await loadStatus(); await loadLogs(); poll();
        } catch (e) { App.toast(e.message, 'error'); $('btnRun').disabled = false; }
    });
    $('btnLog').addEventListener('click', loadLogs);
    $('logbox').scrollTop = $('logbox').scrollHeight;
    @if($backup['is_running']) poll(); @endif
})();
</script>
@endpush
