@extends('layouts.app')
@section('title', 'Servers')
@section('heading', 'Servers')

@section('actions')
    <button class="btn btn-primary" type="button" id="btnAdd">@icon('plus', 16)<span>Tambah server</span></button>
@endsection

@section('content')
<div class="card">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Server</th><th>Status</th><th>CPU</th><th>Memori</th><th>Disk</th><th>Portainer</th><th></th></tr></thead>
            <tbody id="rows"><tr><td colspan="7"><div class="empty">Memuat…</div></td></tr></tbody>
        </table>
    </div>
</div>

<div class="card" style="margin-top:16px">
    <div class="card-head"><div><div class="card-title">Menghubungkan server lain</div><div class="card-sub">Jalankan Docker agent di server tujuan, lalu daftarkan di sini.</div></div></div>
    <ol class="muted" style="padding-left:18px; display:flex; flex-direction:column; gap:8px; font-size:13.5px">
        <li>Di server tujuan, salin folder proyek lalu jalankan <span class="mono">docker compose up -d --build</span> (lihat <span class="mono">DEPLOY.md</span>).</li>
        <li>Isi <span class="mono">AGENT_TOKEN</span> pada <span class="mono">.env</span> server tujuan dengan string acak yang panjang.</li>
        <li>Klik <b>Tambah server</b>, isi URL (mis. <span class="mono">http://IP-SERVER:8080</span>) dan token yang sama.</li>
    </ol>
</div>

<div class="modal-backdrop" id="serverModal">
    <form class="modal" id="serverForm" autocomplete="off">
        <div class="modal-head"><h3 id="serverModalTitle">Tambah server</h3><button class="btn btn-icon" type="button" data-close>@icon('x', 18)</button></div>
        <div class="modal-body">
            <input type="hidden" name="id">
            <div class="field"><label>Nama</label><input class="input" name="name" required maxlength="80" placeholder="Server 2"></div>
            <div class="field" data-remote><label>URL agent</label><input class="input" name="base_url" type="url" placeholder="http://203.0.113.10:8080"></div>
            <div class="field" data-remote><label>Token agent <span class="faint" id="tokenHint"></span></label><input class="input" name="token" type="password" placeholder="AGENT_TOKEN di server tujuan"></div>
            <div class="field"><label>URL Portainer (opsional)</label><input class="input" name="portainer_url" type="url" placeholder="https://portainer.contoh.com"></div>
        </div>
        <div class="modal-foot">
            <button class="btn" type="button" data-close>Batal</button>
            <button class="btn btn-primary" type="submit" id="serverSubmit">Simpan</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const $ = id => document.getElementById(id);
    const form = $('serverForm');
    let list = [];

    const cell = (s, k) => s.online ? `<span class="mono">${App.pct(s[k])}</span>` : '<span class="faint">—</span>';

    function render() {
        $('rows').innerHTML = list.map(s => `<tr>
            <td>
                <div class="cell-main">${App.esc(s.name)} <span class="pill plain" style="margin-left:6px">${s.type === 'local' ? 'Lokal' : 'Remote'}</span></div>
                <div class="cell-sub">${App.esc(s.type === 'local' ? (s.hostname || 'Server dashboard ini') : s.base_url)}</div>
            </td>
            <td>${s.online
                ? `<span class="pill ok">Online</span>${s.type === 'remote' ? `<span class="cell-sub mono" style="margin-left:8px">${s.latency_ms} ms</span>` : ''}`
                : `<span class="pill bad" title="${App.esc(s.error || '')}">Offline</span>`}</td>
            <td>${cell(s, 'cpu')}</td><td>${cell(s, 'ram')}</td><td>${cell(s, 'disk')}</td>
            <td>${s.portainer_url ? `<a class="btn btn-sm" href="${App.esc(s.portainer_url)}" target="_blank" rel="noopener">${App.icon('external', 14)}<span>Buka</span></a>` : '<span class="faint">—</span>'}</td>
            <td><div class="actions">
                <button class="btn btn-icon" title="Edit" data-act="edit" data-id="${s.id}">${App.icon('edit', 16)}</button>
                ${s.type === 'remote' ? `<button class="btn btn-icon" title="Hapus" data-act="del" data-id="${s.id}">${App.icon('trash', 16)}</button>` : ''}
            </div></td>
        </tr>`).join('');
    }

    async function load() {
        try { list = await App.api('/api/monitoring/servers/overview'); render(); }
        catch (e) { App.toast(e.message, 'error'); }
    }

    function openForm(s) {
        const local = s?.type === 'local';
        $('serverModalTitle').textContent = s ? 'Edit server' : 'Tambah server';
        form.reset();
        form.id.value = s?.id ?? '';
        form.name.value = s?.name ?? '';
        form.base_url.value = s?.base_url ?? '';
        form.portainer_url.value = s?.portainer_url ?? '';
        form.base_url.required = !local; form.token.required = !s;
        $('tokenHint').textContent = s && !local ? '(kosongkan untuk tidak mengubah)' : '';
        document.querySelectorAll('[data-remote]').forEach(el => el.classList.toggle('hidden', local));
        App.modal.open('serverModal'); form.name.focus();
    }

    $('btnAdd').addEventListener('click', () => openForm(null));
    document.querySelectorAll('[data-close]').forEach(b => b.addEventListener('click', () => App.modal.close('serverModal')));

    $('rows').addEventListener('click', async e => {
        const b = e.target.closest('button[data-act]'); if (!b) return;
        const s = list.find(x => x.id === Number(b.dataset.id));
        if (b.dataset.act === 'edit') return openForm(s);
        if (!confirm(`Hapus server "${s.name}"?`)) return;
        try { await App.api(`/api/monitoring/servers/${s.id}`, { method: 'DELETE' }); App.toast('Server dihapus'); load(); }
        catch (err) { App.toast(err.message, 'error'); }
    });

    form.addEventListener('submit', async e => {
        e.preventDefault();
        const f = Object.fromEntries(new FormData(form).entries());
        const id = f.id; delete f.id;
        Object.keys(f).forEach(k => { if (f[k] === '') f[k] = null; });
        $('serverSubmit').disabled = true;
        try {
            await App.api(id ? `/api/monitoring/servers/${id}` : '/api/monitoring/servers', { method: id ? 'PUT' : 'POST', body: f });
            App.modal.close('serverModal'); App.toast(id ? 'Perubahan disimpan' : 'Server ditambahkan'); load();
        } catch (err) { App.toast(err.message, 'error'); }
        finally { $('serverSubmit').disabled = false; }
    });

    load(); setInterval(load, 15000);
})();
</script>
@endpush
