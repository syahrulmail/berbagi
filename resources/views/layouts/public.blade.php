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
    <link rel="stylesheet" href="{{ asset('css/public.css') }}">
    @stack('styles')
</head>
<body>

<nav class="pub-nav">
    <div class="container pub-nav-inner">
        <a href="{{ route('home') }}" class="brand">
            <span class="brand-mark">BWA</span>
            <span class="brand-text">
                <span class="brand-name">Berbagi.or.id</span>
                <span class="brand-tagline">Badan Wakaf Al Qur'an</span>
            </span>
        </a>
        <div class="pub-nav-links">
            <a href="{{ route('home') }}" class="nav-btn"><i class="fas fa-house"></i><span>Beranda</span></a>
            <a href="{{ route('login') }}" class="nav-btn"><i class="fas fa-user"></i><span>Masuk</span></a>
        </div>
    </div>
</nav>

@yield('content')

<footer class="pub-footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-brand">
                <span class="brand">
                    <span class="brand-mark">BWA</span>
                    <span class="brand-text">
                        <span class="brand-name">Berbagi.or.id</span>
                        <span class="brand-tagline">Badan Wakaf Al Qur'an</span>
                    </span>
                </span>
                <p>Platform wakaf, infak, dan sedekah Badan Wakaf Al Qur'an (BWA). Semangat dalam mentadabburi dan mengamalkan Al Qur'an untuk kebaikan ummat.</p>
            </div>
            <div class="footer-col">
                <h4>Program</h4>
                <ul>
                    <li><a href="{{ route('home') }}">Semua Program</a></li>
                    <li><a href="{{ route('home') }}#program">Program Penggalangan</a></li>
                    <li><a href="{{ route('home') }}#program">Program Penyaluran</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Lembaga</h4>
                <ul>
                    <li><a href="{{ route('home') }}">Beranda</a></li>
                    <li><a href="{{ route('login') }}">Masuk Anggota</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <span>&copy; {{ date('Y') }} Badan Wakaf Al Qur'an (BWA) · berbagi.or.id</span>
            <span>Wakaf untuk ummat, dari Anda untuk kebaikan.</span>
        </div>
    </div>
</footer>

<div class="toast-wrap" id="toastWrap"></div>

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

    // Logging klik WhatsApp (atribusi follow-up ke agen)
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

    // Animasi progress bar saat terlihat
    var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                entry.target.style.width = entry.target.dataset.percent + '%';
                io.unobserve(entry.target);
            }
        });
    }, { threshold: 0.15 });
    document.querySelectorAll('.progress-fill[data-percent]').forEach(function (bar) {
        io.observe(bar);
    });
    window.BerbagiToast = toast;
})();
</script>
@stack('scripts')
</body>
</html>
