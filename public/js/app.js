/* Helper bersama untuk seluruh halaman */
window.App = (() => {
    const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';

    function icon(name, size = 16) {
        const inner = (window.ICONS || {})[name] || '';
        return `<svg class="icon" width="${size}" height="${size}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${inner}</svg>`;
    }

    function esc(v) {
        return String(v ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }

    async function api(url, { method = 'GET', body } = {}) {
        const res = await fetch(url, {
            method,
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrf(),
                ...(body !== undefined ? { 'Content-Type': 'application/json' } : {}),
            },
            body: body !== undefined ? JSON.stringify(body) : undefined,
        });
        if (res.status === 401 || res.status === 419) { location.href = '/login'; throw new Error('Sesi berakhir'); }
        let data = {};
        try { data = await res.json(); } catch (e) { /* non-JSON */ }
        if (!res.ok) {
            const first = data.errors ? Object.values(data.errors)[0]?.[0] : null;
            const err = new Error(first || data.message || `Permintaan gagal (HTTP ${res.status})`);
            err.status = res.status; err.data = data;
            throw err;
        }
        return data;
    }

    function toast(message, type = 'success') {
        const box = document.getElementById('toasts');
        if (!box) return;
        const el = document.createElement('div');
        el.className = `toast ${type}`;
        el.innerHTML = `<span class="dot"></span><span>${esc(message)}</span>`;
        box.appendChild(el);
        setTimeout(() => { el.style.opacity = '0'; el.style.transition = 'opacity .25s'; setTimeout(() => el.remove(), 260); }, 3800);
    }

    const modal = {
        open: id => document.getElementById(id)?.classList.add('open'),
        close: id => document.getElementById(id)?.classList.remove('open'),
    };
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') document.querySelectorAll('.modal-backdrop.open').forEach(m => m.classList.remove('open'));
    });
    document.addEventListener('mousedown', e => {
        if (e.target.classList?.contains('modal-backdrop')) e.target.classList.remove('open');
    });

    /** Level beban -> kelas warna */
    const level = v => (v >= 90 ? 'bad' : v >= 75 ? 'warn' : 'ok');
    const levelText = v => (v >= 90 ? 'Kritis' : v >= 75 ? 'Tinggi' : 'Normal');
    const pct = v => `${Number(v ?? 0).toFixed(1).replace(/\.0$/, '')}%`;

    return { csrf, icon, esc, api, toast, modal, level, levelText, pct };
})();
