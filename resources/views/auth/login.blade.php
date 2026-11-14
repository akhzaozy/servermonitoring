<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk · Monitor</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
    @vite(['resources/js/app.js'])
</head>
<body>
<div class="login-stage" id="loginStage">
    <div class="login-bg-grid"></div>
    <div class="login-spotlight"></div>
    <div class="login-orb login-orb-1"></div>
    <div class="login-orb login-orb-2"></div>

    <div class="login-shell">
        {{-- Left Panel: Live Telemetry & WITA Time Showcase --}}
        <section class="login-showcase" data-motion-panel>
            <div class="login-showcase-top">
                <div class="brand" style="padding:0">
                    <div class="brand-mark">M</div>
                    <div class="brand-name">Monitor</div>
                </div>
                <div class="login-status-badge mono">
                    <span class="pulse-dot"></span>
                    <span id="witaBadgeClock">MEMUAT WAKTU WITA…</span>
                </div>
            </div>

            <div class="login-hero-copy" data-motion-item>
                <h2>Observabilitas infrastruktur &amp; layanan secara terpusat.</h2>
                <p>Pantau ketersediaan layanan web, kesehatan sumber daya server, dan pencadangan data secara real-time dalam satu panel terpadu.</p>
            </div>

            <div class="login-preview-card" data-motion-item>
                <div class="login-preview-metrics">
                    <div class="login-metric-box" data-motion-kpi>
                        <span>Uptime Layanan</span>
                        <div>
                            <b class="mono" id="prevUptime">99.98%</b>
                            <small class="mono">+0.12% stabil</small>
                        </div>
                    </div>
                    <div class="login-metric-box" data-motion-kpi>
                        <span>Status Beban</span>
                        <div>
                            <b class="mono" id="prevCpu">14.2%</b>
                            <small class="mono" style="color:var(--text-2)">Optimal</small>
                        </div>
                    </div>
                    <div class="login-metric-box" data-motion-kpi>
                        <span>Waktu Server</span>
                        <div>
                            <b class="mono" id="witaCardClock">--:--:--</b>
                            <small class="mono">WITA (UTC+8)</small>
                        </div>
                    </div>
                </div>

                <div class="login-wave-box">
                    <div class="login-wave-head">
                        <span>Live Telemetry Stream</span>
                        <span class="mono" id="witaDateTag">ZONA WITA</span>
                    </div>
                    <svg class="login-wave-svg" viewBox="0 0 420 76" preserveAspectRatio="none">
                        <defs>
                            <linearGradient id="loginWaveFill" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#f4f4f5" stop-opacity="0.22"/>
                                <stop offset="100%" stop-color="#f4f4f5" stop-opacity="0.0"/>
                            </linearGradient>
                        </defs>
                        <path id="loginWaveArea" d="M0,56 C40,52 75,28 115,34 C155,40 190,18 235,24 C280,30 320,12 365,20 C390,24 405,15 420,16 L420,76 L0,76 Z" fill="url(#loginWaveFill)"/>
                        <path id="loginWavePath" d="M0,56 C40,52 75,28 115,34 C155,40 190,18 235,24 C280,30 320,12 365,20 C390,24 405,15 420,16" fill="none" stroke="#f4f4f5" stroke-width="1.8" stroke-linecap="round"/>
                        <circle id="loginWaveDot" cx="365" cy="20" r="3.5" fill="#22c55e" stroke="#0a0a0b" stroke-width="1.5"/>
                    </svg>
                </div>

                <div class="login-nodes-row">
                    <div class="login-node-chip" data-motion-chip>
                        <span class="dot ok"></span>
                        <span>Monitoring Aktif</span>
                    </div>
                    <div class="login-node-chip" data-motion-chip>
                        <span class="dot ok"></span>
                        <span>Health Check Otomatis</span>
                    </div>
                    <div class="login-node-chip" data-motion-chip>
                        <span class="dot ok"></span>
                        <span>Sesi Terenkripsi</span>
                    </div>
                </div>
            </div>
        </section>

        {{-- Right Panel: Interactive Login Card --}}
        <section class="login-panel" id="loginCard" data-motion-panel data-has-error="{{ $errors->any() ? '1' : '0' }}">
            <div class="login-panel-head" data-motion-item>
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
                    <div class="brand-mark" style="width:36px;height:36px;border-radius:10px;font-size:15px">M</div>
                    <span class="pill ok">Terenkripsi</span>
                </div>
                <h1>Masuk ke Monitor</h1>
                <p>Gunakan akun yang terdaftar untuk mengakses panel pemantauan.</p>
            </div>

            <form class="login-form" id="loginForm" method="POST" action="{{ route('login.attempt') }}">
                @csrf
                <div class="field" data-motion-item>
                    <label for="email">Alamat Email</label>
                    <div class="input-wrap">
                        <svg class="icon input-icon" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                        <input class="input" id="email" name="email" type="email" value="{{ old('email') }}" placeholder="nama@domain.com" autocomplete="username" autofocus required>
                        <span class="input-focus-beam"></span>
                    </div>
                    @error('email')<div class="field-error" id="loginErrorMsg">{{ $message }}</div>@enderror
                </div>

                <div class="field" data-motion-item>
                    <label for="password">Password</label>
                    <div class="input-wrap">
                        <svg class="icon input-icon" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        <input class="input has-toggle" id="password" name="password" type="password" placeholder="••••••••" autocomplete="current-password" required>
                        <button class="pwd-toggle" type="button" id="pwdToggle" title="Tampilkan password" aria-label="Tampilkan password">
                            <svg id="eyeOpen" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg id="eyeClosed" class="hidden" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M10.733 5.076a10.744 10.744 0 0 1 11.205 6.575 1 1 0 0 1 0 .696 10.747 10.747 0 0 1-1.444 2.49"/><path d="M14.084 14.158a3 3 0 0 1-4.242-4.242"/><path d="M17.479 17.499a10.75 10.75 0 0 1-15.417-5.151 1 1 0 0 1 0-.696 10.75 10.75 0 0 1 4.446-5.143"/><path d="m2 2 20 20"/></svg>
                        </button>
                        <span class="input-focus-beam"></span>
                    </div>
                    @error('password')<div class="field-error">{{ $message }}</div>@enderror
                </div>

                <div style="display:flex;align-items:center;justify-content:space-between;gap:8px" data-motion-item>
                    <label class="check">
                        <input type="checkbox" name="remember" value="1">
                        <span>Ingat perangkat ini</span>
                    </label>
                    <span class="mono faint" style="font-size:11.5px" id="witaFormClock">--:-- WITA</span>
                </div>

                <button class="btn btn-primary login-submit" type="submit" id="loginSubmitBtn" data-motion-item>
                    <span id="loginSubmitText">Masuk ke Dashboard</span>
                    <svg class="icon arrow-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                </button>
            </form>

            <div class="login-footer-meta" data-motion-item>
                <span>Sistem Pemantauan Terpadu</span>
                <span class="mono" id="witaFooterDate">WITA (UTC+8)</span>
            </div>
        </section>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const stage = document.getElementById('loginStage');
    const card = document.getElementById('loginCard');
    const isTouch = window.matchMedia('(pointer: coarse)').matches;

    // 1. Live WITA Clock (Asia/Makassar, UTC+8)
    const badgeEl = document.getElementById('witaBadgeClock');
    const cardClockEl = document.getElementById('witaCardClock');
    const dateTagEl = document.getElementById('witaDateTag');
    const formClockEl = document.getElementById('witaFormClock');
    const footerDateEl = document.getElementById('witaFooterDate');

    const timeFmt = new Intl.DateTimeFormat('id-ID', {
        timeZone: 'Asia/Makassar',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false
    });
    const dateFmt = new Intl.DateTimeFormat('id-ID', {
        timeZone: 'Asia/Makassar',
        weekday: 'short',
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });

    function updateWitaClock(initial = false) {
        const now = new Date();
        const t = timeFmt.format(now).replace(/\./g, ':');
        const d = dateFmt.format(now).toUpperCase();
        const badgeText = `${d} · ${t} WITA`;

        if (initial && window.scrambleElement) {
            window.scrambleElement(badgeEl, badgeText, { duration: 700 });
            window.scrambleElement(cardClockEl, t, { duration: 600 });
        } else {
            if (badgeEl) badgeEl.textContent = badgeText;
            if (cardClockEl) cardClockEl.textContent = t;
        }
        if (dateTagEl) dateTagEl.textContent = `${t} WITA`;
        if (formClockEl) formClockEl.textContent = `${t} WITA`;
        if (footerDateEl) footerDateEl.textContent = `${d} · WITA`;
    }
    updateWitaClock(true);
    setInterval(() => updateWitaClock(false), 1000);

    // 2. Framer Motion (motion) staggered spring entrance
    if (window.motionAnimate && window.motionStagger) {
        window.motionAnimate(
            '[data-motion-panel]',
            { opacity: [0, 1], y: [24, 0], scale: [0.97, 1] },
            { delay: window.motionStagger(0.08), duration: 0.65, easing: [0.16, 1, 0.3, 1] }
        );
        window.motionAnimate(
            '[data-motion-item]',
            { opacity: [0, 1], y: [14, 0] },
            { delay: window.motionStagger(0.06, { start: 0.12 }), duration: 0.55, easing: [0.16, 1, 0.3, 1] }
        );
        window.motionAnimate(
            '[data-motion-kpi], [data-motion-chip]',
            { opacity: [0, 1], scale: [0.92, 1], y: [8, 0] },
            { delay: window.motionStagger(0.05, { start: 0.22 }), duration: 0.45, easing: [0.16, 1, 0.3, 1] }
        );
    }

    // 3. Input focus beam animation via Framer Motion
    document.querySelectorAll('.input-wrap .input').forEach(inp => {
        const beam = inp.parentElement.querySelector('.input-focus-beam');
        const icon = inp.parentElement.querySelector('.input-icon');
        inp.addEventListener('focus', () => {
            if (beam && window.motionAnimate) {
                window.motionAnimate(beam, { scaleX: [0, 1], opacity: [0, 1] }, { duration: 0.35, easing: [0.16, 1, 0.3, 1] });
            }
            if (icon && window.motionAnimate) {
                window.motionAnimate(icon, { scale: [1, 1.12, 1] }, { duration: 0.3 });
            }
        });
        inp.addEventListener('blur', () => {
            if (beam && window.motionAnimate) {
                window.motionAnimate(beam, { scaleX: 0, opacity: 0 }, { duration: 0.25 });
            }
        });
    });

    // 4. Password visibility toggle with Motion micro-animation
    const pwdInput = document.getElementById('password');
    const pwdToggle = document.getElementById('pwdToggle');
    const eyeOpen = document.getElementById('eyeOpen');
    const eyeClosed = document.getElementById('eyeClosed');
    pwdToggle?.addEventListener('click', () => {
        const show = pwdInput.type === 'password';
        pwdInput.type = show ? 'text' : 'password';
        eyeOpen.classList.toggle('hidden', show);
        eyeClosed.classList.toggle('hidden', !show);
        if (window.motionAnimate) {
            window.motionAnimate(pwdToggle, { scale: [0.85, 1.08, 1] }, { duration: 0.25 });
        }
        pwdInput.focus();
    });

    // 5. Cursor spotlight & subtle 3D perspective card tilt (desktop only)
    if (!isTouch) {
        window.addEventListener('pointermove', e => {
            stage.style.setProperty('--mx', `${e.clientX}px`);
            stage.style.setProperty('--my', `${e.clientY}px`);

            if (card) {
                const r = card.getBoundingClientRect();
                const cx = e.clientX - r.left;
                const cy = e.clientY - r.top;
                card.style.setProperty('--card-mx', `${cx}px`);
                card.style.setProperty('--card-my', `${cy}px`);

                const rx = ((cy / r.height) - 0.5) * -3.5;
                const ry = ((cx / r.width) - 0.5) * 3.5;
                card.style.transform = `perspective(1000px) rotateX(${rx.toFixed(2)}deg) rotateY(${ry.toFixed(2)}deg)`;
            }
        });
        card?.addEventListener('pointerleave', () => {
            card.style.transition = 'transform 0.45s cubic-bezier(0.16, 1, 0.3, 1)';
            card.style.transform = 'perspective(1000px) rotateX(0deg) rotateY(0deg)';
            setTimeout(() => { card.style.transition = ''; }, 450);
        });
    }

    // 6. Anime.js v4 continuous ambient orbs + SVG telemetry wave + scramble text
    if (window.animateAnime) {
        window.animateAnime('.login-orb-1', {
            translateX: [-25, 30],
            translateY: [-15, 25],
            scale: [1, 1.12],
            duration: 5500,
            alternate: true,
            loop: true,
            ease: 'inOutSine'
        });
        window.animateAnime('.login-orb-2', {
            translateX: [25, -30],
            translateY: [20, -20],
            scale: [1.08, 0.94],
            duration: 6200,
            alternate: true,
            loop: true,
            ease: 'inOutSine'
        });

        const wavePath = document.getElementById('loginWavePath');
        if (wavePath && wavePath.getTotalLength) {
            const len = wavePath.getTotalLength();
            wavePath.style.strokeDasharray = `${len}`;
            wavePath.style.strokeDashoffset = `${len}`;
            window.animateAnime(wavePath, {
                strokeDashoffset: [len, 0],
                duration: 1400,
                ease: 'outQuart'
            });
        }

        window.animateAnime('#loginWaveDot', {
            r: [3, 5, 3],
            duration: 1800,
            loop: true,
            ease: 'inOutSine'
        });

        if (card?.dataset.hasError === '1') {
            window.animateAnime(card, {
                translateX: [-10, 10, -7, 7, -4, 4, 0],
                duration: 480,
                ease: 'outElastic(1, .6)'
            });
        }
    }

    // 7. Anime.js scrambleText live CPU preview ticker
    if (window.scrambleElement) {
        const cpus = ['14.2%', '11.8%', '13.5%', '12.4%'];
        let idx = 0;
        setInterval(() => {
            idx = (idx + 1) % cpus.length;
            window.scrambleElement('#prevCpu', cpus[idx], { duration: 600 });
        }, 4500);
    }

    // 8. Submit button interactive feedback
    const form = document.getElementById('loginForm');
    const submitBtn = document.getElementById('loginSubmitBtn');
    form?.addEventListener('submit', () => {
        if (submitBtn) {
            submitBtn.disabled = true;
            if (window.scrambleElement) {
                window.scrambleElement('#loginSubmitText', 'Memverifikasi sesi...', { duration: 500 });
            } else {
                document.getElementById('loginSubmitText').textContent = 'Memverifikasi sesi...';
            }
        }
    });
});
</script>
</body>
</html>
