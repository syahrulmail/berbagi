<!DOCTYPE html>
<html lang="id" class="mobile-app">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, user-scalable=no">
    <meta name="robots" content="noindex, nofollow, noarchive, nosnippet">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#086e66">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>@yield('title', 'Berbagi') · Berbagi Mobile</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fira+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="{{ assetv('css/mobile.css') }}">
    @stack('styles')
</head>
<body>
<div class="mo-app">
    <div class="mo-screen">
        @yield('mobile-content')
    </div>

    @include('mobile.partials.flash')

    {{-- Bottom Tab Bar --}}
    <nav class="mo-tabbar" id="mo-tabbar">
        @php
            $tabs = [
                ['route' => 'mo.dashboard', 'icon' => 'fa-house', 'label' => 'Beranda'],
                ['route' => 'mo.donations', 'icon' => 'fa-hand-holding-dollar', 'label' => 'Donasi'],
                ['route' => 'mo.contacts', 'icon' => 'fa-address-book', 'label' => 'Kontak'],
                ['route' => 'mo.programs', 'icon' => 'fa-file-invoice-dollar', 'label' => 'Program'],
            ];
        @endphp
        @foreach($tabs as $i => $tab)
            <a href="{{ route($tab['route']) }}"
               class="mo-tab {{ request()->routeIs($tab['route']) ? 'active' : '' }}">
                <i class="fas {{ $tab['icon'] }}"></i>
                <span>{{ $tab['label'] }}</span>
            </a>
            @if($i === 1)
                <button type="button" class="mo-tab mo-tab-add" id="mo-add-tab" aria-label="Tambah">
                    <span class="mo-tab-add-btn"><i class="fas fa-plus"></i></span>
                    <span class="mo-tab-add-label">Tambah</span>
                </button>
            @endif
        @endforeach
    </nav>

    {{-- Quick add sheet (tombol + tengah) --}}
    <div class="mo-sheet-backdrop" data-for="mo-add-sheet"></div>
    <div class="mo-sheet" id="mo-add-sheet" aria-hidden="true">
        <div class="mo-sheet-handle"></div>
        <div class="mo-sheet-head">
            <h3 class="mo-sheet-title"><i class="fas fa-plus" style="color:#16a34a;margin-right:6px;"></i>Tambah</h3>
            <button type="button" class="mo-sheet-close" aria-label="Tutup"><i class="fas fa-xmark"></i></button>
        </div>
        <div class="mo-sheet-body">
            <a href="{{ route('mo.donation.create') }}" class="mo-add-action">
                <span class="mo-add-action-icon mo-add-action-icon--donation"><i class="fas fa-hand-holding-dollar"></i></span>
                <span class="mo-add-action-text">
                    <strong>Tambah Donasi</strong>
                    <small>Catat donasi baru</small>
                </span>
                <i class="fas fa-chevron-right mo-add-action-chevron"></i>
            </a>
            <a href="{{ route('mo.contact.create') }}" class="mo-add-action">
                <span class="mo-add-action-icon mo-add-action-icon--contact"><i class="fas fa-address-book"></i></span>
                <span class="mo-add-action-text">
                    <strong>Tambah Kontak</strong>
                    <small>Daftarkan kontak/donatur baru</small>
                </span>
                <i class="fas fa-chevron-right mo-add-action-chevron"></i>
            </a>
        </div>
    </div>

    {{-- Shared bottom sheets --}}
    @yield('sheets')
</div>

<script>
    window.MoApp = {
        csrf: document.querySelector('meta[name="csrf-token"]').content,
        api: '{{ route('mo.api') }}',
    };
</script>
<script src="{{ assetv('js/mobile-app.js') }}"></script>
@stack('scripts')
</body>
</html>
