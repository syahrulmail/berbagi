<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Berbagi.or.id · Badan Wakaf Al Qur\'an')</title>
    <meta name="description" content="@yield('meta_description', 'Platform wakaf dan sedekah Badan Wakaf Al Qur\'an (BWA) — wakaf untuk ummat, dari Anda untuk kebaikan.')">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fira+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="{{ assetv('css/app.css') }}">
    @stack('styles')
    <style>
        #siteHeader .container { height: 64px; }
        #siteHeader.header-scrolled .container { height: 56px; }
        #siteHeader.header-scrolled {
            background: rgba(255, 255, 255, .94);
            box-shadow: 0 8px 24px -12px rgba(2, 35, 33, .22);
        }
        #siteHeader.header-scrolled .header-donate {
            box-shadow: 0 8px 20px -6px rgba(212, 145, 30, .55);
            transform: translateY(-1px);
        }
        .back-to-top {
            position: fixed;
            right: 18px;
            bottom: 92px;
            z-index: 60;
            display: grid;
            place-items: center;
            width: 44px;
            height: 44px;
            border: none;
            border-radius: 9999px;
            background: linear-gradient(135deg, #08A899, #04786f);
            color: #fff;
            font-size: 15px;
            cursor: pointer;
            box-shadow: 0 10px 24px -8px rgba(8, 168, 153, .55);
            opacity: 0;
            visibility: hidden;
            transform: translateY(14px);
            transition: opacity .3s, transform .3s, visibility .3s;
        }
        .back-to-top.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        .back-to-top:hover {
            transform: translateY(-3px);
            box-shadow: 0 14px 28px -8px rgba(8, 168, 153, .65);
        }
        @media (min-width: 1024px) {
            .back-to-top { bottom: 32px; right: 28px; }
        }
        #program {
            scroll-margin-top: 84px;
        }
    </style>
</head>
<body class="bg-white text-primary-950">

<div class="trustbar">
    <div class="container flex h-9 items-center justify-between gap-3">
        <span class="inline-flex items-center gap-1.5 truncate">
            <i class="fas fa-shield-heart text-gold-400"></i>
            {{ $trustbarText }}
        </span>
    </div>
</div>

<header id="siteHeader" class="sticky top-0 z-40 border-b border-black/5 bg-white/85 backdrop-blur-md">
    <div class="container flex h-16 items-center justify-between transition-all duration-300">
        <a href="{{ route('home') }}" class="flex items-center gap-3">
            <span class="grid h-10 w-10 place-items-center rounded-xl bg-gradient-to-br from-primary-500 to-primary-700 text-sm font-bold text-white shadow-lg shadow-primary-500/25 transition-all duration-300">BWA</span>
            <span class="leading-tight">
                <span class="block text-base font-bold text-primary-900 transition-all duration-300">Berbagi.or.id</span>
                <span class="block text-[11px] font-medium text-gray-500">Badan Wakaf Al Qur'an</span>
            </span>
        </a>
        <nav class="flex items-center gap-2">
            <a href="{{ route('home') }}" class="rounded-lg px-3 py-2 text-sm font-medium text-gray-600 transition hover:bg-primary-50 hover:text-primary-700"><i class="fas fa-house mr-1.5"></i>Beranda</a>
            <a href="{{ route('home') }}#program" class="hidden rounded-lg px-3 py-2 text-sm font-medium text-gray-600 transition hover:bg-primary-50 hover:text-primary-700 sm:block">Program</a>
            <a href="@yield('headerDonasiUrl', '#program')" class="btn btn-gold btn-sm header-donate"><i class="fas fa-heart"></i> Donasi</a>
            <a href="{{ route('login') }}" class="hidden rounded-lg px-3 py-2 text-sm font-medium text-gray-600 transition hover:bg-primary-50 hover:text-primary-700 sm:block"><i class="fas fa-right-to-bracket mr-1.5"></i>Masuk</a>
        </nav>
    </div>
</header>

@yield('content')

<footer class="mt-16 bg-primary-950 text-primary-100">
    <div class="container py-14">
        <div class="grid gap-10 md:grid-cols-3">
            <div>
                <a href="{{ route('home') }}" class="mb-4 flex items-center gap-3">
                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-gradient-to-br from-primary-500 to-primary-700 text-sm font-bold text-white">BWA</span>
                    <span class="leading-tight">
                        <span class="block text-base font-bold text-white">Berbagi.or.id</span>
                        <span class="block text-[11px] font-medium text-primary-300">Badan Wakaf Al Qur'an</span>
                    </span>
                </a>
                <p class="max-w-xs text-sm leading-relaxed text-primary-300">Platform wakaf, infak, dan sedekah Badan Wakaf Al Qur'an (BWA). Semangat dalam mentadabburi dan mengamalkan Al Qur'an untuk kebaikan ummat.</p>
            </div>
            <div>
                <h4 class="mb-4 text-sm font-bold uppercase tracking-wide text-white">Program</h4>
                <ul class="space-y-2.5 text-sm">
                    <li><a href="{{ route('home') }}#program" class="text-primary-300 transition hover:text-white">Semua Program</a></li>
                    <li><a href="{{ route('home') }}#program" class="text-primary-300 transition hover:text-white">Program Penggalangan</a></li>
                    <li><a href="{{ route('home') }}#program" class="text-primary-300 transition hover:text-white">Program Penyaluran</a></li>
                </ul>
            </div>
            <div>
                <h4 class="mb-4 text-sm font-bold uppercase tracking-wide text-white">Lembaga</h4>
                <ul class="space-y-2.5 text-sm">
                    <li><a href="{{ route('home') }}" class="text-primary-300 transition hover:text-white">Beranda</a></li>
                    <li><a href="{{ route('login') }}" class="text-primary-300 transition hover:text-white">Masuk Anggota</a></li>
                </ul>
            </div>
        </div>
        <div class="mt-12 flex flex-col gap-2 border-t border-white/10 pt-6 text-sm text-primary-400 sm:flex-row sm:items-center sm:justify-between">
            <span>&copy; {{ date('Y') }} Badan Wakaf Al Qur'an (BWA) · berbagi.or.id</span>
            <span>Wakaf untuk ummat, dari Anda untuk kebaikan.</span>
        </div>
    </div>
</footer>

<div class="donasi-bar-spacer lg:hidden"></div>

<div class="donasi-bar lg:hidden">
    <div class="donasi-bar-inner">
        <div class="donasi-bar-info">
            <strong>@yield('donasiBarTitle', 'Wakaf untuk Ummat')</strong>
            <span>@yield('donasiBarSub', 'Gratis konsultasi · Tanpa kewajiban')</span>
        </div>
        <a href="@yield('donasiBarUrl', $siteWaNumber ? 'https://wa.me/' . $siteWaNumber . '?text=' . urlencode('Assalamualaikum, saya ingin berdonasi untuk program wakaf Berbagi.or.id. Mohon info selanjutnya.') : '#')"
           target="_blank" rel="noopener"
           class="btn btn-wa"
           data-wa-log data-wa-source="@yield('donasiBarSource', 'home')" data-wa-program="@yield('donasiBarProgram', '')">
            <i class="fab fa-whatsapp"></i> @yield('donasiBarCta', 'Donasi')
        </a>
    </div>
</div>

<div class="toast-wrap" id="toastWrap"></div>

<button type="button" id="backToTop" class="back-to-top" aria-label="Kembali ke atas"><i class="fas fa-arrow-up"></i></button>

<script src="{{ assetv('js/vendor/vue.global.prod.js') }}"></script>
<script src="{{ assetv('js/components/BannerSlider.js') }}"></script>
<script src="{{ assetv('js/components/AchievementSlider.js') }}"></script>
<script src="{{ assetv('js/components/HeroStats.js') }}"></script>
<script src="{{ assetv('js/components/ProgramExplorer.js') }}"></script>
<script src="{{ assetv('js/components/CountUp.js') }}"></script>
<script src="{{ assetv('js/components/BarChart.js') }}"></script>
<script src="{{ assetv('js/components/DonutChart.js') }}"></script>
<script src="{{ assetv('js/app.js') }}"></script>

<script>
(function () {
    'use strict';

    function toast(msg) {
        var wrap = document.getElementById('toastWrap');
        var el = document.createElement('div');
        el.className = 'toast';
        el.textContent = msg;
        wrap.appendChild(el);
        requestAnimationFrame(function () { el.classList.add('show'); });
        setTimeout(function () {
            el.classList.remove('show');
            setTimeout(function () { el.remove(); }, 350);
        }, 2600);
    }

    function logWaClick(el) {
        var csrf = document.querySelector('meta[name="csrf-token"]');
        var payload = {
            source: el.dataset.waSource || 'program',
            program_id: el.dataset.waProgram || null,
            agen_id: el.dataset.waAgen || null,
            phone: el.dataset.waPhone || null
        };
        if (navigator.sendBeacon && csrf) {
            var fd = new FormData();
            fd.append('source', payload.source);
            if (payload.program_id) fd.append('program_id', payload.program_id);
            if (payload.agen_id) fd.append('agen_id', payload.agen_id);
            if (payload.phone) fd.append('phone', payload.phone);
            fd.append('_token', csrf.content);
            navigator.sendBeacon('{{ route('wa.followup') }}', fd);
        } else {
            fetch('{{ route('wa.followup') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf ? csrf.content : ''
                },
                body: JSON.stringify(payload),
                keepalive: true
            }).catch(function () {});
        }
    }

    document.addEventListener('click', function (e) {
        var el = e.target.closest('[data-wa-log]');
        if (el) logWaClick(el);
    });

    var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('reveal-visible');
                io.unobserve(entry.target);
            }
        });
    }, { threshold: 0.12 });
    document.querySelectorAll('[data-reveal]').forEach(function (el) {
        io.observe(el);
    });

    var pio = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                entry.target.style.width = entry.target.dataset.percent + '%';
                pio.unobserve(entry.target);
            }
        });
    }, { threshold: 0.15 });
    document.querySelectorAll('.progress-fill[data-percent]').forEach(function (bar) {
        pio.observe(bar);
    });

    window.BerbagiToast = toast;

    var header = document.getElementById('siteHeader');
    var backTop = document.getElementById('backToTop');
    function onScroll() {
        var y = window.pageYOffset || document.documentElement.scrollTop;
        if (header) header.classList.toggle('header-scrolled', y > 24);
        if (backTop) backTop.classList.toggle('show', y > 400);
    }
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
    if (backTop) {
        backTop.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }
})();
</script>
@stack('scripts')
</body>
</html>
