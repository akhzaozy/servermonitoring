<!DOCTYPE html>
<html lang="id" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'STB Monitor & Web Active Health') • Server Dashboard</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700;800&family=Outfit:wght@400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <!-- ApexCharts -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    <!-- Vite Assets (Anime.js & Motion) -->
    @vite(['resources/js/app.js'])

    <!-- Custom Modern Styles (with automatic cache busting for Cloudflare & Browsers) -->
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}?v={{ file_exists(public_path('css/dashboard.css')) ? filemtime(public_path('css/dashboard.css')) : time() }}">

    @stack('styles')
</head>
<body>
    <!-- Background Ambient Glow -->
    <div class="bg-ambient">
        <div class="bg-blob blob-1"></div>
        <div class="bg-blob blob-2"></div>
        <div class="bg-blob blob-3"></div>
    </div>

    <!-- Toast Notification Container (Floating Top-Right) -->
    <div id="toastContainer" class="toast-container" style="position: fixed; top: 24px; right: 24px; z-index: 99999999; display: flex; flex-direction: column; gap: 12px; pointer-events: none;"></div>

    <!-- Main Content -->
    @yield('content')

    <!-- Scripts -->
    <script>
        // Lucide Icons Auto Initialize
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) {
                window.lucide.createIcons();
            }
        });

        // Global Toast Notification Helper
        window.showToast = function(message, type = 'success', title = null) {
            const container = document.getElementById('toastContainer');
            if (!container) return;
            const toast = document.createElement('div');
            toast.className = `toast-item ${type}`;
            const icon = type === 'success' ? 'check-circle-2' : (type === 'error' ? 'alert-octagon' : 'info');
            const defaultTitle = type === 'success' ? 'Berhasil' : (type === 'error' ? 'Peringatan / Gagal' : 'Informasi');
            const displayTitle = title || defaultTitle;
            
            toast.innerHTML = `
                <div class="toast-content">
                    <i data-lucide="${icon}" style="width: 22px; height: 22px; flex-shrink: 0; color: ${type === 'success' ? '#10b981' : (type === 'error' ? '#f43f5e' : '#00f0ff')};"></i>
                    <div>
                        <div style="font-size: 0.76rem; text-transform: uppercase; letter-spacing: 0.05em; opacity: 0.7; font-weight: 700;">${displayTitle}</div>
                        <div style="font-size: 0.88rem; font-weight: 500; color: #ffffff; margin-top: 2px;">${message}</div>
                    </div>
                </div>
                <button class="toast-close-btn" onclick="this.parentElement.remove()" title="Tutup">
                    <i data-lucide="x" style="width: 15px; height: 15px;"></i>
                </button>
            `;
            container.appendChild(toast);
            if (window.lucide) window.lucide.createIcons({ root: toast });
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(60px) scale(0.9)';
                setTimeout(() => toast.remove(), 300);
            }, 4500);
        };

        // Theme Toggle (Dark / Light)
        window.toggleTheme = function() {
            const html = document.documentElement;
            const current = html.getAttribute('data-theme') || 'dark';
            const next = current === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-theme', next);
            localStorage.setItem('stb_theme', next);
            if (window.onThemeChanged) window.onThemeChanged(next);
        };

        // Restore saved theme
        const savedTheme = localStorage.getItem('stb_theme');
        if (savedTheme) {
            document.documentElement.setAttribute('data-theme', savedTheme);
        }
    </script>
    @stack('scripts')
</body>
</html>
