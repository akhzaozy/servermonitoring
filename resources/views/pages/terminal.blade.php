@extends('layouts.app')
@section('title', 'Terminal')
@section('heading', 'Terminal')
@section('server-switch', '1')

@push('head')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@xterm/xterm@5.5.0/css/xterm.min.css">
    <script src="https://cdn.jsdelivr.net/npm/@xterm/xterm@5.5.0/lib/xterm.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@xterm/addon-fit@0.10.0/lib/addon-fit.min.js"></script>
@endpush

@section('actions')
    <button class="btn" type="button" id="btnClear">@icon('eraser', 16)<span>Bersihkan</span></button>
@endsection

@section('content')
@unless($terminalEnabled)
    <div class="notice">Terminal dinonaktifkan. Set <span class="mono">TERMINAL_ENABLED=true</span> pada <span class="mono">.env</span> untuk mengaktifkan.</div>
@else
<div class="card term-card">
    <div class="term-bar">
        <span><span class="dot ok" style="margin-right:8px"></span>{{ $currentServer?->name }}</span>
        <span>Enter untuk menjalankan · ↑↓ riwayat · Ctrl+C batal · Ctrl+L bersihkan</span>
    </div>
    <div class="term-host"><div id="term" style="height:100%"></div></div>
</div>
<p class="faint" style="margin-top:12px; font-size:12.5px">
    Setiap perintah dijalankan terpisah (non-interaktif), jadi program layar-penuh seperti <span class="mono">vim</span> atau <span class="mono">top</span> tidak didukung — gunakan <span class="mono">top -bn1</span>. Batas waktu {{ config('monitor.terminal.timeout') }} detik per perintah.
</p>
@endunless
@endsection

@push('scripts')
@if($terminalEnabled)
<script>
(() => {
    const SERVER_ID = {{ $currentServer?->id ?? 'null' }};
    const term = new Terminal({
        fontFamily: '"JetBrains Mono", ui-monospace, Menlo, monospace',
        fontSize: 13, lineHeight: 1.35, cursorBlink: true, convertEol: false, scrollback: 5000,
        theme: { background: '#0a0a0b', foreground: '#e4e4e7', cursor: '#e4e4e7', selectionBackground: 'rgba(255,255,255,.2)',
                 black: '#18181b', red: '#ef4444', green: '#22c55e', yellow: '#f59e0b', blue: '#60a5fa', magenta: '#c084fc', cyan: '#67e8f9', white: '#e4e4e7' },
    });
    const fit = new FitAddon.FitAddon();
    term.loadAddon(fit);
    term.open(document.getElementById('term'));
    fit.fit();
    window.addEventListener('resize', () => fit.fit());

    const state = { cwd: '', user: 'user', host: 'server', line: '', pos: 0, hist: [], hi: -1, busy: false, abort: null };
    const hkey = 'term_hist_' + SERVER_ID;
    try { state.hist = JSON.parse(localStorage.getItem(hkey) || '[]'); } catch (e) {}

    const GREEN = '\x1b[32m', BLUE = '\x1b[34m', DIM = '\x1b[2m', RESET = '\x1b[0m';

    function shortCwd() {
        const c = state.cwd || '~';
        return c.replace(new RegExp('^/(home|Users)/' + state.user), '~').replace(/^\/root/, '~');
    }
    const prompt = () => `${GREEN}${state.user}@${state.host}${RESET}:${BLUE}${shortCwd()}${RESET}$ `;
    const promptLen = () => `${state.user}@${state.host}:${shortCwd()}$ `.length;

    function writePrompt() { term.write('\r\n' + prompt()); }
    function redraw() {
        term.write('\r\x1b[2K' + prompt() + state.line);
        const back = state.line.length - state.pos;
        if (back > 0) term.write(`\x1b[${back}D`);
    }

    async function run(cmd) {
        state.busy = true;
        state.abort = new AbortController();
        try {
            const res = await fetch('/api/monitoring/terminal/exec', {
                method: 'POST', credentials: 'same-origin', signal: state.abort.signal,
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': App.csrf() },
                body: JSON.stringify({ server: SERVER_ID, command: cmd, cwd: state.cwd }),
            });
            if (res.status === 401 || res.status === 419) { location.href = '/login'; return; }
            const d = await res.json().catch(() => ({}));
            if (!res.ok) { term.write('\r\n\x1b[31m' + (d.message || 'Gagal menjalankan perintah (HTTP ' + res.status + ')') + RESET); return; }
            if (d.output) term.write('\r\n' + d.output.replace(/\r?\n/g, '\r\n').replace(/\r\n$/, ''));
            if (d.cwd) state.cwd = d.cwd;
            if (d.user) state.user = d.user;
            if (d.host) state.host = d.host;
        } catch (e) {
            term.write(e.name === 'AbortError' ? '^C' : '\r\n\x1b[31m' + e.message + RESET);
        } finally {
            state.busy = false; state.abort = null; state.line = ''; state.pos = 0; state.hi = -1;
            writePrompt();
        }
    }

    function submit() {
        const cmd = state.line.trim();
        if (cmd) {
            if (state.hist[state.hist.length - 1] !== cmd) state.hist.push(cmd);
            state.hist = state.hist.slice(-200);
            localStorage.setItem(hkey, JSON.stringify(state.hist));
        }
        if (cmd === 'clear') { term.clear(); state.line = ''; state.pos = 0; term.write('\x1b[2J\x1b[H' + prompt()); return; }
        if (!cmd) { state.line = ''; state.pos = 0; writePrompt(); return; }
        run(state.line);
    }

    term.onData(data => {
        if (state.busy) { if (data === '\x03') state.abort?.abort(); return; }
        switch (data) {
            case '\r': return submit();
            case '\x7f': if (state.pos > 0) { state.line = state.line.slice(0, state.pos - 1) + state.line.slice(state.pos); state.pos--; redraw(); } return;
            case '\x03': term.write('^C'); state.line = ''; state.pos = 0; return writePrompt();
            case '\x0c': term.write('\x1b[2J\x1b[H' + prompt() + state.line); return;
            case '\x01': state.pos = 0; return redraw();
            case '\x05': state.pos = state.line.length; return redraw();
            case '\x15': state.line = state.line.slice(state.pos); state.pos = 0; return redraw();
            case '\x1b[A': if (state.hist.length) { state.hi = state.hi < 0 ? state.hist.length - 1 : Math.max(0, state.hi - 1); state.line = state.hist[state.hi]; state.pos = state.line.length; redraw(); } return;
            case '\x1b[B': if (state.hi >= 0) { state.hi++; state.line = state.hi >= state.hist.length ? '' : state.hist[state.hi]; if (state.hi >= state.hist.length) state.hi = -1; state.pos = state.line.length; redraw(); } return;
            case '\x1b[D': if (state.pos > 0) { state.pos--; term.write('\x1b[D'); } return;
            case '\x1b[C': if (state.pos < state.line.length) { state.pos++; term.write('\x1b[C'); } return;
            case '\x1b[3~': if (state.pos < state.line.length) { state.line = state.line.slice(0, state.pos) + state.line.slice(state.pos + 1); redraw(); } return;
        }
        if (data.startsWith('\x1b')) return;
        const text = data.replace(/[\r\n]+/g, ' ');           // paste
        state.line = state.line.slice(0, state.pos) + text + state.line.slice(state.pos);
        state.pos += text.length; redraw();
    });

    document.getElementById('btnClear').addEventListener('click', () => { term.clear(); term.write('\x1b[2J\x1b[H' + prompt() + state.line); term.focus(); });

    // Sambungkan: ambil user/host/cwd awal dari server
    term.write(DIM + 'Menghubungkan…' + RESET);
    state.busy = true;
    App.api('/api/monitoring/terminal/exec', { method: 'POST', body: { server: SERVER_ID, command: '', cwd: '' } })
        .then(d => { state.cwd = d.cwd || ''; state.user = d.user || 'user'; state.host = d.host || 'server'; term.write('\x1b[2K\r' + DIM + `Terhubung ke ${state.user}@${state.host}` + RESET); })
        .catch(e => term.write('\x1b[2K\r\x1b[31m' + e.message + RESET))
        .finally(() => { state.busy = false; writePrompt(); term.focus(); });
})();
</script>
@endif
@endpush
