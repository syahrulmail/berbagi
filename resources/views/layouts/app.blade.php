<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · {{ config('app.name', 'Berbagi') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @stack('styles')
</head>
<body class="layout-body">
<div class="layout" id="app-layout">
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <i class="fas fa-hands-helping sidebar-brand-icon"></i>
            <span class="sidebar-brand-text">Berbagi.or.id</span>
        </div>

        <nav class="sidebar-nav">
            <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="fas fa-gauge-high"></i><span class="nav-text">Dashboard</span>
            </a>

            @if(auth()->user()->isAdmin())
                <a href="{{ route('branches.index') }}" class="nav-item {{ request()->routeIs('branches.*') ? 'active' : '' }}">
                    <i class="fas fa-building"></i><span class="nav-text">Cabang</span>
                </a>
                <a href="{{ route('users.index') }}" class="nav-item {{ request()->routeIs('users.*') ? 'active' : '' }}">
                    <i class="fas fa-users"></i><span class="nav-text">Pengguna</span>
                </a>
            @endif

            <a href="{{ route('contacts.index') }}" class="nav-item {{ request()->routeIs('contacts.*') ? 'active' : '' }}">
                <i class="fas fa-address-book"></i><span class="nav-text">Kontak</span>
            </a>

            <a href="{{ route('donations.index') }}" class="nav-item {{ request()->routeIs('donations.*') ? 'active' : '' }}">
                <i class="fas fa-hand-holding-dollar"></i><span class="nav-text">Donasi</span>
            </a>

            <a href="{{ route('programs.index') }}" class="nav-item {{ request()->routeIs('programs.*') ? 'active' : '' }}">
                <i class="fas fa-file-invoice-dollar"></i><span class="nav-text">Program</span>
            </a>

            <a href="{{ route('whatsapp.index') }}" class="nav-item {{ request()->routeIs('whatsapp.*') ? 'active' : '' }}">
                <i class="fab fa-whatsapp"></i><span class="nav-text">WhatsApp</span>
            </a>

            @if(auth()->user()->isAdmin())
                <a href="{{ route('campaign-tags.index') }}" class="nav-item {{ request()->routeIs('campaign-tags.*') ? 'active' : '' }}">
                    <i class="fas fa-tags"></i><span class="nav-text">Label Kampanye</span>
                </a>
                <a href="{{ route('banners.index') }}" class="nav-item {{ request()->routeIs('banners.*') ? 'active' : '' }}">
                    <i class="fas fa-images"></i><span class="nav-text">Banner & Label</span>
                </a>
            @endif

            @if(auth()->user()->isAdmin() || auth()->user()->isSupervisor())
                <a href="{{ route('activity-logs.index') }}" class="nav-item {{ request()->routeIs('activity-logs.*') ? 'active' : '' }}">
                    <i class="fas fa-clipboard-list"></i><span class="nav-text">Log Aktivitas</span>
                </a>
            @endif

            @if(auth()->user()->isAdmin())
                <a href="{{ route('settings.index') }}" class="nav-item {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                    <i class="fas fa-gear"></i><span class="nav-text">Pengaturan</span>
                </a>
            @endif
        </nav>

        <div class="sidebar-footer">
            <a href="{{ route('home') }}" target="_blank" class="nav-item">
                <i class="fas fa-globe"></i><span class="nav-text">Lihat Situs</span>
            </a>
        </div>
    </aside>

    <div class="main-area">
        <header class="topbar">
            <button class="sidebar-toggle" id="sidebar-toggle" type="button" aria-label="Toggle sidebar">
                <i class="fas fa-bars"></i>
            </button>

            <form action="{{ route('contacts.index') }}" method="GET" class="topbar-search">
                <i class="fas fa-magnifying-glass"></i>
                <input type="search" name="search" placeholder="Cari cepat..." value="{{ request('search') }}">
            </form>

            <div class="topbar-right">
                <a href="{{ route('donations.create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Catat Donasi
                </a>
                <div class="user-menu">
                    <div class="user-chip" id="user-chip">
                        <div class="user-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
                        <div class="user-info">
                            <strong>{{ auth()->user()->name }}</strong>
                            <span>{{ auth()->user()->roleLabel() }}</span>
                        </div>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="user-dropdown" id="user-dropdown">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item text-danger">
                                <i class="fas fa-right-from-bracket"></i> Keluar
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main class="content">
            @if(session('success'))
                <div class="alert alert-success">
                    <i class="fas fa-circle-check"></i> {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-error">
                    <i class="fas fa-circle-exclamation"></i> {{ session('error') }}
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-error">
                    <i class="fas fa-circle-exclamation"></i>
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>

<script>
    const sidebar = document.getElementById('sidebar');
    const toggle = document.getElementById('sidebar-toggle');
    const layout = document.getElementById('app-layout');

    toggle.addEventListener('click', () => {
        layout.classList.toggle('sidebar-collapsed');
        localStorage.setItem('sidebar-collapsed', layout.classList.contains('sidebar-collapsed') ? '1' : '0');
    });

    if (localStorage.getItem('sidebar-collapsed') === '1') {
        layout.classList.add('sidebar-collapsed');
    }

    const chip = document.getElementById('user-chip');
    const dropdown = document.getElementById('user-dropdown');
    chip.addEventListener('click', (e) => {
        e.stopPropagation();
        dropdown.classList.toggle('open');
    });
    document.addEventListener('click', () => dropdown.classList.remove('open'));
</script>
@stack('scripts')
</body>
</html>
