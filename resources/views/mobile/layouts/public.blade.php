<!DOCTYPE html>
<html lang="id" class="mobile-app">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow, noarchive, nosnippet">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#086e66">
    <title>@yield('title', 'Laporan') · Berbagi</title>
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
