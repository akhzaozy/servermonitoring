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

<div class="grid grid-2" style="margin-top:16px">
    <div class="card">
        <div class="card-head">
            <div>
                <div class="card-title">Cara 1 — Tanpa install di Server 2 (SSH / Cloudflare Tunnel)</div>
                <div class="card-sub">Langsung tarik metrik &amp; buka terminal lewat koneksi SSH / cloudflared.</div>
            </div>
        </div>
        <ol class="muted" style="padding-left:18px; display:flex; flex-direction:column; gap:8px; font-size:13.5px">
            <li>Klik <b>Tambah server</b> → pilih metode <b>SSH / Cloudflare Tunnel</b>.</li>
            <li>Centang <b>Gunakan Cloudflare Tunnel</b> lalu masukkan hostname seperti <span class="mono">server-fsti.myst-tech.com</span> (atau matikan centang jika memakai IP/Port SSH biasa).</li>
            <li>Masukkan <b>Username SSH</b> beserta <b>Password SSH</b> (atau path/isi SSH key bila menggunakan key).</li>
            <li>Simpan — Server 1 akan otomatis menarik data CPU, RAM, Disk, Suhu, Uptime, serta membuka <b>Web Terminal</b> ke Server 2 tanpa perlu install web/Docker apa pun di Server 2.</li>
        </ol>
    </div>

    <div class="card">
        <div class="card-head">
            <div>
                <div class="card-title">Cara 2 — Via Portainer Git Stack (Port 8576)</div>
                <div class="card-sub">Opsional jika ingin menjalankan container agent mandiri di Server 2.</div>
            </div>
        </div>
        <ol class="muted" style="padding-left:18px; display:flex; flex-direction:column; gap:8px; font-size:13.5px">
            <li>Di Portainer Server 2: <b>Stacks → Add stack → Repository</b> ke <span class="mono">https://github.com/akhzaozy/servermonitoring</span> (<span class="mono">docker-compose.yml</span>, port <span class="mono">8576</span>).</li>
            <li>Klik <b>Tambah server</b> → pilih <b>HTTP Agent</b>, isi URL <span class="mono">http://IP-SERVER:8576</span> dan <span class="mono">AGENT_TOKEN</span>.</li>
        </ol>
    </div>
</div>

<div class="modal-backdrop" id="serverModal">
    <form class="modal wide" id="serverForm" autocomplete="off">
        <div class="modal-head">
            <h3 id="serverModalTitle">Tambah server</h3>
            <button class="btn btn-icon" type="button" data-close>@icon('x', 18)</button>
        </div>
        <div class="modal-body">
            <input type="hidden" name="id">
            <input type="hidden" name="type" id="serverTypeInput" value="ssh">

            <div class="field" id="methodPickerWrap">
                <label>Metode koneksi</label>
                <div class="seg" id="typeSeg" style="width:100%; display:grid; grid-template-columns:1fr 1fr">
                    <button type="button" class="active" data-t="ssh">SSH / Cloudflare Tunnel (Tanpa Install)</button>
                    <button type="button" data-t="remote">HTTP Agent (Docker :8576)</button>
                </div>
            </div>

            <div class="field">
                <label>Nama server</label>
                <input class="input" name="name" required maxlength="80" placeholder="Server FSTI">
            </div>

            {{-- Mode SSH / Cloudflare Tunnel --}}
            <div id="sshFields" class="stack" style="gap:14px">
                <label class="check">
                    <input type="checkbox" name="use_cloudflare" id="useCloudflareInput" value="1" checked>
                    <span>Gunakan Cloudflare Tunnel (<span class="mono">cloudflared access ssh --hostname …</span>)</span>
                </label>

                <div class="grid grid-2" style="gap:12px" id="sshHostGrid">
                    <div class="field" id="sshHostField" style="grid-column: 1 / -1">
                        <label id="sshHostLabel">Cloudflare SSH Hostname</label>
                        <input class="input mono" name="ssh_host" placeholder="server-fsti.myst-tech.com">
                    </div>
                    <div class="field hidden" id="sshPortField">
                        <label>Port SSH</label>
                        <input class="input mono" name="ssh_port" type="number" min="1" max="65535" value="22">
                    </div>
                </div>

                <div class="grid grid-2" style="gap:12px">
                    <div class="field">
                        <label>Username SSH</label>
                        <input class="input mono" name="ssh_user" placeholder="root / ubuntu / user">
                    </div>
                    <div class="field">
                        <label>Password SSH <span class="faint" id="pwdHint">(opsional jika pakai SSH key)</span></label>
                        <input class="input" name="ssh_password" type="password" placeholder="••••••••">
                    </div>
                </div>

                <div class="field">
                    <label>SSH Private Key / Path <span class="faint" id="keyHint">(opsional, mis. ~/.ssh/id_ed25519)</span></label>
                    <input class="input mono" name="ssh_key" placeholder="Kosongkan bila menggunakan password atau default key">
                </div>
            </div>

            {{-- Mode HTTP Agent --}}
            <div id="agentFields" class="stack hidden" style="gap:14px">
                <div class="field">
                    <label>URL agent</label>
                    <input class="input mono" name="base_url" type="url" placeholder="http://203.0.113.10:8576">
                </div>
                <div class="field">
                    <label>Token agent <span class="faint" id="tokenHint"></span></label>
                    <input class="input mono" name="token" type="password" placeholder="stb-agent-secret-token-2026">
                </div>
            </div>

            <div class="field">
                <label>URL Portainer (opsional)</label>
                <input class="input" name="portainer_url" type="url" placeholder="https://portainer.contoh.com">
            </div>
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

    function typeBadge(s) {
        if (s.type === 'local') return 'Lokal';
        if (s.type === 'ssh') return s.use_cloudflare ? 'Cloudflare SSH' : 'SSH';
        return 'Agent';
    }

    function subTitle(s) {
        if (s.type === 'local') return s.hostname || 'Server dashboard ini';
        if (s.type === 'ssh') {
            return `${s.ssh_user || ''}@${s.ssh_host || ''}${s.use_cloudflare ? ' (cloudflared)' : ':' + (s.ssh_port || 22)}`;
        }
        return s.base_url || '';
    }

    function render() {
        $('rows').innerHTML = list.map(s => `<tr>
            <td>
                <div class="cell-main">${App.esc(s.name)} <span class="pill plain" style="margin-left:6px">${typeBadge(s)}</span></div>
                <div class="cell-sub mono">${App.esc(subTitle(s))}</div>
            </td>
            <td>${s.online
                ? `<span class="pill ok">Online</span>${s.type !== 'local' ? `<span class="cell-sub mono" style="margin-left:8px">${s.latency_ms} ms</span>` : ''}`
                : `<span class="pill bad" title="${App.esc(s.error || '')}">Offline</span><div class="cell-sub" style="color:#fca5a5;margin-top:3px;max-width:260px">${App.esc(s.error || '')}</div>`}</td>
            <td>${cell(s, 'cpu')}</td><td>${cell(s, 'ram')}</td><td>${cell(s, 'disk')}</td>
            <td>${s.portainer_url ? `<a class="btn btn-sm" href="${App.esc(s.portainer_url)}" target="_blank" rel="noopener">${App.icon('external', 14)}<span>Buka</span></a>` : '<span class="faint">—</span>'}</td>
            <td><div class="actions">
                ${s.type !== 'local' ? `<button class="btn btn-icon" title="Tes koneksi" data-act="test" data-id="${s.id}">${App.icon('plug', 16)}</button>` : ''}
                <button class="btn btn-icon" title="Edit" data-act="edit" data-id="${s.id}">${App.icon('edit', 16)}</button>
                ${s.type !== 'local' ? `<button class="btn btn-icon" title="Hapus" data-act="del" data-id="${s.id}">${App.icon('trash', 16)}</button>` : ''}
            </div></td>
        </tr>`).join('');
    }

    async function load() {
        try { list = await App.api('/api/monitoring/servers/overview'); render(); }
        catch (e) { App.toast(e.message, 'error'); }
    }

    function syncTypeUI(type, isEdit = false) {
        $('serverTypeInput').value = type;
        document.querySelectorAll('#typeSeg button').forEach(b => b.classList.toggle('active', b.dataset.t === type));

        const isLocal = type === 'local';
        const isSsh = type === 'ssh';
        const isAgent = type === 'remote';

        $('methodPickerWrap').classList.toggle('hidden', isLocal);
        $('sshFields').classList.toggle('hidden', !isSsh);
        $('agentFields').classList.toggle('hidden', !isAgent);

        form.ssh_host.required = isSsh;
        form.ssh_user.required = isSsh;
        form.base_url.required = isAgent;
        form.token.required = isAgent && !isEdit;
        syncCloudflareUI();
    }

    function syncCloudflareUI() {
        const cf = $('useCloudflareInput').checked;
        $('sshHostLabel').textContent = cf ? 'Cloudflare SSH Hostname' : 'Host / IP Server';
        form.ssh_host.placeholder = cf ? 'server-fsti.myst-tech.com' : '203.0.113.10';
        $('sshPortField').classList.toggle('hidden', cf);
        $('sshHostField').style.gridColumn = cf ? '1 / -1' : 'auto';
    }

    $('useCloudflareInput').addEventListener('change', syncCloudflareUI);
    document.querySelectorAll('#typeSeg button').forEach(b => b.addEventListener('click', () => {
        syncTypeUI(b.dataset.t, !!form.id.value);
    }));

    function openForm(s) {
        $('serverModalTitle').textContent = s ? 'Edit server' : 'Tambah server';
        form.reset();
        form.id.value = s?.id ?? '';
        form.name.value = s?.name ?? '';
        form.portainer_url.value = s?.portainer_url ?? '';

        const type = s ? s.type : 'ssh';
        if (s) {
            form.base_url.value = s.base_url ?? '';
            form.ssh_host.value = s.ssh_host ?? '';
            form.ssh_port.value = s.ssh_port ?? 22;
            form.ssh_user.value = s.ssh_user ?? '';
            $('useCloudflareInput').checked = s.type === 'ssh' ? !!s.use_cloudflare : true;
        } else {
            $('useCloudflareInput').checked = true;
            form.ssh_port.value = 22;
        }

        $('tokenHint').textContent = s && s.type === 'remote' ? '(kosongkan untuk tidak mengubah)' : '';
        $('pwdHint').textContent = s && s.has_password ? '(tersimpan · kosongkan jika tidak diubah)' : '(opsional jika pakai SSH key)';
        $('keyHint').textContent = s && s.has_key ? '(tersimpan · kosongkan jika tidak diubah)' : '(opsional, mis. ~/.ssh/id_ed25519)';

        syncTypeUI(type, !!s);
        App.modal.open('serverModal');
        form.name.focus();
    }

    $('btnAdd').addEventListener('click', () => openForm(null));
    document.querySelectorAll('[data-close]').forEach(b => b.addEventListener('click', () => App.modal.close('serverModal')));

    $('rows').addEventListener('click', async e => {
        const b = e.target.closest('button[data-act]'); if (!b) return;
        const s = list.find(x => x.id === Number(b.dataset.id));
        if (b.dataset.act === 'edit') return openForm(s);
        if (b.dataset.act === 'test') {
            b.disabled = true;
            try {
                const r = await App.api(`/api/monitoring/servers/${s.id}/test`, { method: 'POST' });
                App.toast(r.ok ? `${s.name}: Terhubung (${r.latency_ms} ms)` : `${s.name}: ${r.message}`, r.ok ? 'success' : 'error');
                load();
            } catch (err) { App.toast(err.message, 'error'); }
            finally { b.disabled = false; }
            return;
        }
        if (!confirm(`Hapus server "${s.name}"?`)) return;
        try { await App.api(`/api/monitoring/servers/${s.id}`, { method: 'DELETE' }); App.toast('Server dihapus'); load(); }
        catch (err) { App.toast(err.message, 'error'); }
    });

    form.addEventListener('submit', async e => {
        e.preventDefault();
        const f = Object.fromEntries(new FormData(form).entries());
        const id = f.id; delete f.id;
        f.use_cloudflare = $('useCloudflareInput').checked;
        f.ssh_port = f.ssh_port ? Number(f.ssh_port) : 22;
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
