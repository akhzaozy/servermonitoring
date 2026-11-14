<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · Monitor</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
    @stack('head')
</head>
<body>
@php
    $nav = [
        'Platform' => [
            ['dashboard', 'Dashboard', 'dashboard'],
            ['websites', 'Websites', 'globe'],
            ['backups', 'Backups', 'archive'],
            ['terminal', 'Terminal', 'terminal'],
        ],
        'System' => [
            ['servers', 'Servers', 'server'],
        ],
    ];
@endphp
<div class="app">
    <aside class="sidebar" id="sidebar">
        <div class="brand">
            <div class="brand-mark">M</div>
            <div class="brand-name">Monitor</div>
        </div>

        <nav class="nav">
            @foreach($nav as $label => $items)
                <div class="nav-label">{{ $label }}</div>
                @foreach($items as [$route, $text, $icon])
                    <a href="{{ route($route) }}" class="{{ request()->routeIs($route) ? 'active' : '' }}">
                        @icon($icon, 18)<span>{{ $text }}</span>
                    </a>
                @endforeach
            @endforeach
        </nav>

        <div class="sidebar-foot">
            <div class="user-row">
                <div class="avatar">{{ \Illuminate\Support\Str::substr(auth()->user()->name, 0, 1) }}</div>
                <div class="user-meta">
                    <b>{{ auth()->user()->name }}</b>
                    <span>{{ auth()->user()->email }}</span>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn btn-icon" title="Keluar" type="submit">@icon('logout', 17)</button>
                </form>
            </div>
        </div>
    </aside>

    <main class="main">
        <header class="topbar">
            <div class="topbar-left">
                <button class="btn btn-icon menu-btn" type="button" onclick="document.getElementById('sidebar').classList.toggle('open')">@icon('menu', 20)</button>
                <h1 class="page-title">@yield('heading', 'Dashboard')</h1>
                @hasSection('server-switch')
                    <div class="select">
                        <select onchange="location.href='/servers/' + this.value + '/select'" aria-label="Pilih server">
                            @foreach($servers as $s)
                                <option value="{{ $s->id }}" @selected($currentServer && $currentServer->id === $s->id)>{{ $s->name }}</option>
                            @endforeach
                        </select>
                        @icon('chevron', 14)
                    </div>
                @endif
            </div>
            <div class="topbar-right">@yield('actions')</div>
        </header>

        @yield('content')
    </main>
</div>

<div class="toasts" id="toasts"></div>

<script>window.ICONS = @json($iconSet);</script>
<script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}"></script>
@stack('scripts')
</body>
</html>
