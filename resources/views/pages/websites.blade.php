@extends('layouts.app')
@section('title', 'Websites')
@section('heading', 'Websites')

@section('actions')
    <button class="btn" type="button" id="btnNginx">@icon('server', 16)<span>Nginx</span></button>
    <button class="btn" type="button" id="btnCheckAll">@icon('refresh', 16)<span>Cek semua</span></button>
    <button class="btn btn-primary" type="button" id="btnAdd">@icon('plus', 16)<span>Tambah website</span></button>
@endsection

@section('content')
<div class="card">
    <div class="card-head" style="flex-wrap:wrap">
        <div class="tabs" id="filters">
            <button class="tab active" data-f="all">Semua <span class="faint" id="cAll"></span></button>
            <button class="tab" data-f="online">Online <span class="faint" id="cOn"></span></button>
            <button class="tab" data-f="offline">Offline <span class="faint" id="cOff"></span></button>
        </div>
        <div class="search">
            @icon('search', 16)
            <input class="input" id="q" type="search" placeholder="Cari nama atau URL…">
        </div>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Website</th><th>Status</th><th>HTTP</th><th>Latensi</th><th>Uptime 24 jam</th><th>Cek terakhir</th><th></th></tr>
            </thead>
            <tbody id="rows"></tbody>
        </table>
    </div>
</div>

{{-- Modal: tambah / edit --}}
<div class="modal-backdrop" id="siteModal">
    <form class="modal" id="siteForm" autocomplete="off">
        <div class="modal-head"><h3 id="siteModalTitle">Tambah website</h3><button class="btn btn-icon" type="button" data-close="siteModal">@icon('x', 18)</button></div>
        <div class="modal-body">
            <input type="hidden" name="id">
            <div class="field"><label>Nama</label><input class="input" name="name" required maxlength="100" placeholder="Portal Mahasiswa"></div>
            <div class="field"><label>URL</label><input class="input" name="url" type="url" required placeholder="https://contoh.com"></div>
            <div class="grid grid-2" style="gap:12px">
                <div class="field"><label>Stack</label>
                    <select class="select-el" name="stack_type">
                        <option value="laravel">Laravel</option>
                        <option value="nextjs">Next.js</option>
                        <option value="native_php">PHP native</option>
                        <option value="static">Statis</option>
                        <option value="api">API</option>
                        <option value="other">Lainnya</option>
                    </select>
                </div>
                <div class="field"><label>Port</label><input class="input" name="port" type="number" min="1" max="65535" placeholder="80"></div>
            </div>
            <div class="field"><label>Kata kunci yang harus muncul (opsional)</label><input class="input" name="expected_keyword" maxlength="100"></div>
        </div>
        <div class="modal-foot">
            <button class="btn" type="button" data-close="siteModal">Batal</button>
            <button class="btn btn-primary" type="submit" id="siteSubmit">Simpan</button>
        </div>
    </form>
</div>

{{-- Modal: nginx --}}
<div class="modal-backdrop" id="nginxModal">
    <div class="modal wide">
        <div class="modal-head"><h3>Nginx</h3><button class="btn btn-icon" type="button" data-close="nginxModal">@icon('x', 18)</button></div>
        <div class="modal-body">
            <dl class="kv">
                <div><dt>Status</dt><dd><span class="pill {{ $nginx['is_running'] ? 'ok' : 'bad' }}">{{ $nginx['status_label'] }}</span></dd></div>
                <div><dt>Versi</dt><dd class="mono">{{ $nginx['version'] }}</dd></div>
                <div><dt>Worker</dt><dd class="mono">{{ $nginx['worker_processes'] }}</dd></div>
                <div><dt>Virtual host</dt><dd class="mono">{{ $nginx['vhosts_count'] }}</dd></div>
            </dl>
            <div class="code hidden" id="nginxOut"></div>
        </div>
        <div class="modal-foot" style="justify-content:space-between">
            <button class="btn" type="button" id="btnSync">@icon('refresh', 16)<span>Sinkron situs dari Nginx</span></button>
            <div style="display:flex;gap:8px">
                <button class="btn" type="button" id="btnTest">Uji konfigurasi</button>
                <button class="btn" type="button" id="btnReload">Reload</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const $ = id => document.getElementById(id);
    let sites = @json($sites);
    let filter = 'all', query = '';

    const stackLabel = { laravel: 'Laravel', nextjs: 'Next.js', native_php: 'PHP native', static: 'Statis', api: 'API', other: 'Lainnya' };
    const cleanPct = v => String(v ?? 100).replace(/%+$/, '');

    function render() {
        $('cAll').textContent = sites.length;
        $('cOn').textContent = sites.filter(s => s.is_online).length;
        $('cOff').textContent = sites.filter(s => !s.is_online).length;

        const q = query.toLowerCase();
        const list = sites.filter(s =>
            (filter === 'all' || (filter === 'online') === !!s.is_online) &&
            (!q || (s.name || '').toLowerCase().includes(q) || (s.url || '').toLowerCase().includes(q)));

        if (!list.length) {
            $('rows').innerHTML = `<tr><td colspan="7"><div class="empty"><b>Tidak ada website</b>Tambahkan website atau ubah filter pencarian.</div></td></tr>`;
            return;
        }

        $('rows').innerHTML = list.map(s => {
            const bars = Array.from({ length: 24 }, (_, i) => {
                const b = (s.history_bars || [])[i];
                return b ? `<i class="${b.online ? 'up' : 'down'}" title="${App.esc(b.time)} · HTTP ${b.status_code || 'err'} · ${b.ms} ms"></i>` : '<i></i>';
            }).join('');
            return `<tr>
                <td>
                    <div class="cell-main">${App.esc(s.name)}</div>
                    <div class="cell-sub"><a href="${App.esc(s.url)}" target="_blank" rel="noopener">${App.esc(s.url)}</a> · ${App.esc(stackLabel[s.stack_type] || s.stack_type)}${s.port ? ' · :' + s.port : ''}</div>
                </td>
                <td><span class="pill ${s.is_online ? 'ok' : 'bad'}">${s.is_online ? 'Online' : 'Offline'}</span></td>
                <td class="mono">${s.last_status_code ?? '—'}</td>
                <td class="mono">${s.is_online && s.last_response_time_ms != null ? Math.round(s.last_response_time_ms) + ' ms' : '—'}</td>
                <td><div class="uptime-bars" style="width:150px">${bars}</div><div class="cell-sub mono" style="margin-top:4px">${cleanPct(s.uptime_percentage)}%</div></td>
                <td class="muted">${App.esc(s.last_checked_at)}</td>
                <td><div class="actions">
                    <button class="btn btn-icon" title="Cek sekarang" data-act="check" data-id="${s.id}">${App.icon('refresh', 16)}</button>
                    <button class="btn btn-icon" title="Edit" data-act="edit" data-id="${s.id}">${App.icon('edit', 16)}</button>
                    <button class="btn btn-icon" title="Hapus" data-act="del" data-id="${s.id}">${App.icon('trash', 16)}</button>
                </div></td>
            </tr>`;
        }).join('');
    }

    async function reload() { sites = await App.api('/api/monitoring/sites'); render(); }

    async function withSpin(btn, fn) {
        const ic = btn.querySelector('.icon'); ic?.classList.add('spin'); btn.disabled = true;
        try { await fn(); } finally { ic?.classList.remove('spin'); btn.disabled = false; }
    }

    // Filters & search
    $('filters').addEventListener('click', e => {
        const b = e.target.closest('.tab'); if (!b) return;
        document.querySelectorAll('#filters .tab').forEach(t => t.classList.remove('active'));
        b.classList.add('active'); filter = b.dataset.f; render();
    });
    $('q').addEventListener('input', e => { query = e.target.value; render(); });

    // Row actions
    $('rows').addEventListener('click', async e => {
        const b = e.target.closest('button[data-act]'); if (!b) return;
        const id = Number(b.dataset.id), site = sites.find(s => s.id === id);
        try {
            if (b.dataset.act === 'check') {
                await withSpin(b, async () => { await App.api(`/api/monitoring/sites/${id}/check`, { method: 'POST' }); await reload(); });
                const s = sites.find(x => x.id === id);
                App.toast(`${s.name}: ${s.is_online ? 'online' : 'offline'}`, s.is_online ? 'success' : 'error');
            } else if (b.dataset.act === 'edit') {
                openForm(site);
            } else if (b.dataset.act === 'del') {
                if (!confirm(`Hapus "${site.name}" dari monitoring?`)) return;
                await App.api(`/api/monitoring/sites/${id}`, { method: 'DELETE' });
                App.toast('Website dihapus'); await reload();
            }
        } catch (err) { App.toast(err.message, 'error'); }
    });

    $('btnCheckAll').addEventListener('click', e => withSpin(e.currentTarget, async () => {
        try { sites = await App.api('/api/monitoring/check-all', { method: 'POST' }); render(); App.toast('Semua website sudah dicek'); }
        catch (err) { App.toast(err.message, 'error'); }
    }));

    // Add / edit form
    const form = $('siteForm');
    function openForm(site) {
        $('siteModalTitle').textContent = site ? 'Edit website' : 'Tambah website';
        form.reset();
        form.id.value = site?.id ?? '';
        if (site) {
            form.name.value = site.name; form.url.value = site.url; form.stack_type.value = site.stack_type;
            form.port.value = site.port ?? '';
        }
        App.modal.open('siteModal'); form.name.focus();
    }
    $('btnAdd').addEventListener('click', () => openForm(null));
    document.querySelectorAll('[data-close]').forEach(b => b.addEventListener('click', () => App.modal.close(b.dataset.close)));

    form.addEventListener('submit', async e => {
        e.preventDefault();
        const f = Object.fromEntries(new FormData(form).entries());
        const id = f.id; delete f.id;
        f.port = f.port ? Number(f.port) : null; f.expected_keyword = f.expected_keyword || null;
        $('siteSubmit').disabled = true;
        try {
            await App.api(id ? `/api/monitoring/sites/${id}` : '/api/monitoring/sites', { method: id ? 'PUT' : 'POST', body: f });
            App.modal.close('siteModal'); App.toast(id ? 'Perubahan disimpan' : 'Website ditambahkan'); await reload();
        } catch (err) { App.toast(err.message, 'error'); }
        finally { $('siteSubmit').disabled = false; }
    });

    // Nginx
    $('btnNginx').addEventListener('click', () => App.modal.open('nginxModal'));
    const out = (t) => { const o = $('nginxOut'); o.textContent = t; o.classList.remove('hidden'); };
    $('btnTest').addEventListener('click', async () => {
        try { const r = await App.api('/api/monitoring/nginx/test', { method: 'POST' }); out(r.output || r.message || 'OK'); App.toast(r.success ? 'Konfigurasi valid' : 'Konfigurasi bermasalah', r.success ? 'success' : 'error'); }
        catch (err) { App.toast(err.message, 'error'); }
    });
    $('btnReload').addEventListener('click', async () => {
        try { const r = await App.api('/api/monitoring/nginx/reload', { method: 'POST' }); App.toast(r.message || 'Nginx di-reload', r.success === false ? 'error' : 'success'); }
        catch (err) { App.toast(err.message, 'error'); }
    });
    $('btnSync').addEventListener('click', e => withSpin(e.currentTarget, async () => {
        try { const r = await App.api('/api/monitoring/nginx/sync', { method: 'POST' }); await reload(); App.toast(r.message || 'Sinkronisasi selesai'); }
        catch (err) { App.toast(err.message, 'error'); }
    }));

    render();
})();
</script>
@endpush
