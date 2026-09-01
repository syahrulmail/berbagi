<!DOCTYPE html>
<html lang="id" class="mobile-app">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, user-scalable=no">
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

    {{-- Bottom Tab Bar --}}
    <nav class="mo-tabbar" id="mo-tabbar">
        @php
            $tabs = [
                ['route' => 'mo.home', 'icon' => 'fa-house', 'label' => 'Beranda'],
                ['route' => 'mo.donations', 'icon' => 'fa-hand-holding-dollar', 'label' => 'Donasi'],
                ['route' => 'mo.contacts', 'icon' => 'fa-address-book', 'label' => 'Kontak'],
                ['route' => 'mo.programs', 'icon' => 'fa-file-invoice-dollar', 'label' => 'Program'],
                ['route' => 'mo.more', 'icon' => 'fa-ellipsis', 'label' => 'Lainnya'],
            ];
        @endphp
        @foreach($tabs as $tab)
            <a href="{{ route($tab['route']) }}"
               class="mo-tab {{ request()->routeIs($tab['route']) ? 'active' : '' }}">
                <i class="fas {{ $tab['icon'] }}"></i>
                <span>{{ $tab['label'] }}</span>
            </a>
        @endforeach
    </nav>

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
