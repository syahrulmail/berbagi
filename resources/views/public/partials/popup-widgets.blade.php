@php
    $popupBanners = $popupBanners ?? [];
    $statsPopup = $statsPopup ?? null;
@endphp

@if(!empty($popupBanners) || !empty($statsPopup))
<style>
    .bp-stats {
        position: fixed;
        left: 20px;
        bottom: 20px;
        z-index: 65;
        display: flex;
        gap: 12px;
        max-width: 350px;
        padding: 14px 16px 14px 14px;
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 20px 48px -20px rgba(2, 35, 33, .45), 0 0 0 1px rgba(8, 168, 153, .08);
        opacity: 0;
        visibility: hidden;
        transform: translateY(18px);
        transition: opacity .35s ease, transform .35s ease, visibility .35s;
        user-select: none;
        -webkit-user-select: none;
        touch-action: manipulation;
    }
    .bp-stats.is-visible {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }
    .bp-stats-icon {
        flex: none;
        display: grid;
        place-items: center;
        width: 42px;
        height: 42px;
        border-radius: 12px;
        background: linear-gradient(135deg, #08A899, #04786f);
        color: #fff;
        font-size: 17px;
        box-shadow: 0 8px 18px -8px rgba(8, 168, 153, .7);
    }
    .bp-stats-text {
        font-size: 13px;
        line-height: 1.5;
        color: #334155;
    }
    .bp-stats-text strong {
        color: #04786f;
        font-weight: 700;
    }
    .bp-stats-text .bp-value {
        display: block;
        font-size: 22px;
        line-height: 1.15;
        font-weight: 800;
        color: #04786f;
        letter-spacing: -.01em;
    }
    .bp-stats-text .bp-value-label {
        display: block;
        color: #475569;
        font-size: 12px;
        margin-top: 2px;
    }
    .bp-stats-cta {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-top: 8px;
        font-size: 12px;
        font-weight: 700;
        color: #04786f;
        text-decoration: none;
    }
    .bp-stats-cta:hover {
        color: #065f58;
        text-decoration: underline;
    }
    .bp-stats-cta[hidden] {
        display: none;
    }
    .bp-modal-root {
        position: fixed;
        inset: 0;
        z-index: 90;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 24px;
    }
    .bp-modal-root.is-open {
        display: flex;
    }
    .bp-modal-overlay {
        position: absolute;
        inset: 0;
        background: rgba(2, 20, 18, .64);
        backdrop-filter: blur(2px);
        opacity: 0;
        transition: opacity .25s ease;
    }
    .bp-modal-root.is-open .bp-modal-overlay {
        opacity: 1;
    }
    .bp-modal-dialog {
        position: relative;
        z-index: 1;
        width: min(92vw, 620px);
        max-height: 88vh;
        opacity: 0;
        transform: scale(.94);
        transition: opacity .25s ease, transform .25s ease;
    }
    .bp-modal-root.is-open .bp-modal-dialog {
        opacity: 1;
        transform: scale(1);
    }
    .bp-modal-dialog a {
        display: block;
        line-height: 0;
    }
    .bp-modal-image {
        display: block;
        width: 100%;
        height: auto;
        max-height: 88vh;
        object-fit: contain;
        border-radius: 14px;
        background-color: #f8fafc;
        box-shadow: 0 28px 70px -24px rgba(0, 0, 0, .6);
    }
    .bp-modal-dialog .sr-only {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        white-space: nowrap;
        border: 0;
    }
    .bp-modal-close {
        position: absolute;
        top: -14px;
        right: -14px;
        display: grid;
        place-items: center;
        width: 36px;
        height: 36px;
        border: none;
        border-radius: 999px;
        background: #fff;
        color: #0f172a;
        font-size: 20px;
        line-height: 1;
        cursor: pointer;
        box-shadow: 0 10px 24px -8px rgba(0, 0, 0, .45);
    }
    .bp-modal-close:hover {
        background: #f8fafc;
    }
    @media (max-width: 640px) {
        .bp-stats {
            left: 12px;
            right: 12px;
            bottom: 86px;
            max-width: none;
        }
        .bp-modal-root {
            padding: 16px;
        }
        .bp-modal-close {
            top: -10px;
            right: -4px;
        }
    }
</style>

@if(!empty($statsPopup))
<div id="bpStats" class="bp-stats" role="dialog" aria-live="polite" aria-label="Statistik donasi">
    <span class="bp-stats-icon" id="bpStatsIcon"><i class="fas fa-hand-holding-heart"></i></span>
    <div>
        <div class="bp-stats-text" id="bpStatsText"></div>
        <a class="bp-stats-cta" id="bpStatsCta" href="#" target="_blank" rel="noopener" hidden></a>
    </div>
</div>
@endif

@if(!empty($popupBanners))
@php
    $firstPopup = $popupBanners[0];
    $popupWidth = !empty($firstPopup['width']) ? (int) $firstPopup['width'] : 1024;
    $popupHeight = !empty($firstPopup['height']) ? (int) $firstPopup['height'] : 1280;
@endphp
<div id="bpModalRoot" class="bp-modal-root" aria-hidden="true">
    <div class="bp-modal-overlay" data-bp-close></div>
    <div class="bp-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="bpModalTitle">
        <button type="button" class="bp-modal-close" data-bp-close aria-label="Tutup banner">&times;</button>
        <h2 id="bpModalTitle" class="sr-only">Informasi promosi</h2>
        <a id="bpModalLink" href="#" target="_blank" rel="noopener">
            <canvas id="bpModalImage" class="bp-modal-image" role="img" aria-label="" width="{{ $popupWidth }}" height="{{ $popupHeight }}" style="aspect-ratio: {{ $popupWidth }} / {{ $popupHeight }};"></canvas>
        </a>
    </div>
</div>
@endif

<script>
window.BerbagiPopupConfig = {!! json_encode([
    'banners' => $popupBanners ?? [],
    'stats' => $statsPopup,
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES) !!};
</script>
<script>
(function () {
    'use strict';

    var config = window.BerbagiPopupConfig || {};
    var banners = config.banners || [];
    var stats = config.stats;

    function readFlag(key) {
        try {
            return window.sessionStorage.getItem(key) === '1';
        } catch (e) {
            return false;
        }
    }

    function setFlag(key) {
        try {
            window.sessionStorage.setItem(key, '1');
        } catch (e) {}
    }

    var statsController = null;

    if (stats && stats.patterns && stats.patterns.length) {
        (function () {
            var box = document.getElementById('bpStats');
            var text = document.getElementById('bpStatsText');
            var cta = document.getElementById('bpStatsCta');
            var iconBox = document.getElementById('bpStatsIcon');
            var patterns = stats.patterns;
            var currentIndex = -1;
            var isVisible = false;
            var isPaused = false;
            var isHeld = false;
            var showTimer = null;
            var hideTimer = null;
            var nextTimer = null;

            function clearTimers() {
                window.clearTimeout(showTimer);
                window.clearTimeout(hideTimer);
                window.clearTimeout(nextTimer);
            }

            function strong(value) {
                var el = document.createElement('strong');
                el.textContent = value;
                return el;
            }

            function renderPattern(pattern) {
                text.innerHTML = '';
                iconBox.innerHTML = '';

                var icon = document.createElement('i');
                icon.className = 'fas ' + (pattern.type === 'achievement' ? (pattern.icon || 'fa-trophy') : 'fa-hand-holding-heart');
                iconBox.appendChild(icon);

                if (pattern.type === 'category') {
                    text.appendChild(document.createTextNode('Alhamdulillah, ' + (pattern.period || 'hari ini') + ' '));
                    text.appendChild(strong(pattern.count + ' orang'));
                    text.appendChild(document.createTextNode(' donasi '));
                    text.appendChild(strong(pattern.category || ''));
                } else {
                    text.appendChild(document.createTextNode('Alhamdulillah'));

                    var value = document.createElement('span');
                    value.className = 'bp-value';
                    value.textContent = pattern.value || '';
                    text.appendChild(value);

                    var label = document.createElement('span');
                    label.className = 'bp-value-label';
                    label.textContent = pattern.label || '';
                    text.appendChild(label);
                }

                if (pattern.url) {
                    cta.href = pattern.url;
                    cta.textContent = pattern.cta || 'Berbagi sekarang';
                    cta.target = pattern.external ? '_blank' : '_self';
                    cta.rel = pattern.external ? 'noopener' : '';
                    cta.hidden = false;
                } else {
                    cta.hidden = true;
                }
            }

            function nextIndex() {
                if (patterns.length <= 1) return 0;

                var index = Math.floor(Math.random() * patterns.length);

                return index === currentIndex ? (index + 1) % patterns.length : index;
            }

            function scheduleNext() {
                window.clearTimeout(nextTimer);
                var gap = 2000 + Math.floor(Math.random() * 13001);
                nextTimer = window.setTimeout(show, gap);
            }

            function scheduleHide() {
                window.clearTimeout(hideTimer);

                if (!isVisible || isPaused || isHeld) {
                    return;
                }

                hideTimer = window.setTimeout(hide, 4000);
            }

            function show() {
                if (isPaused) return;

                currentIndex = nextIndex();
                renderPattern(patterns[currentIndex]);
                isVisible = true;
                isHeld = false;
                box.classList.add('is-visible');
                scheduleHide();
            }

            function hide() {
                window.clearTimeout(hideTimer);
                box.classList.remove('is-visible');
                isVisible = false;
                isHeld = false;

                if (!isPaused) {
                    scheduleNext();
                }
            }

            function pause() {
                isPaused = true;
                isHeld = false;
                clearTimers();
                box.classList.remove('is-visible');
                isVisible = false;
            }

            function resume() {
                if (!isPaused) return;

                isPaused = false;
                scheduleNext();
            }

            function holdStats() {
                isHeld = true;
                window.clearTimeout(hideTimer);
            }

            function releaseStats() {
                isHeld = false;

                if (isVisible && !isPaused) {
                    scheduleHide();
                }
            }

            box.addEventListener('mouseenter', holdStats);
            box.addEventListener('mouseleave', releaseStats);
            box.addEventListener('pointerdown', holdStats);
            box.addEventListener('pointerup', releaseStats);
            box.addEventListener('pointercancel', releaseStats);
            box.addEventListener('touchstart', holdStats, { passive: true });
            box.addEventListener('touchend', releaseStats);
            box.addEventListener('touchcancel', releaseStats);
            box.addEventListener('focusin', holdStats);
            box.addEventListener('focusout', releaseStats);

            var initialDelay = Math.max(0, parseInt(stats.delay_seconds || 0, 10)) * 1000;
            showTimer = window.setTimeout(show, initialDelay);

            statsController = {
                pause: pause,
                resume: resume,
                isVisible: function () { return isVisible; }
            };
        })();
    }

    if (!banners.length) return;

    var root = document.getElementById('bpModalRoot');
    var image = document.getElementById('bpModalImage');
    var link = document.getElementById('bpModalLink');
    var current = null;
    var pending = [];
    var isOpen = false;
    var scheduled = {};

    function scrollProgress() {
        var doc = document.documentElement;
        var height = Math.max(doc.scrollHeight, document.body ? document.body.scrollHeight : 0);
        var top = window.pageYOffset || doc.scrollTop || 0;
        var view = window.innerHeight || doc.clientHeight || 0;

        return height <= view ? 1 : (top + view) / height;
    }

    // Poster dirender ke <canvas> agar overlay promosi tidak pernah menjadi
    // kandidat LCP (LCP hanya menghitung <img>, <video>, background-image,
    // dan blok teks) sekaligus menghindari pergeseran tata letak.
    function paintPoster(banner) {
        if (!image || !image.getContext || !banner.image) return;

        if (banner.width && banner.height) {
            image.width = banner.width;
            image.height = banner.height;
            image.style.aspectRatio = banner.width + ' / ' + banner.height;
        }

        var ctx = image.getContext('2d');
        var poster = new window.Image();
        poster.decoding = 'async';

        poster.onload = function () {
            if (!ctx || !image.isConnected) return;

            if (!banner.width || !banner.height) {
                image.width = poster.naturalWidth;
                image.height = poster.naturalHeight;
                image.style.aspectRatio = poster.naturalWidth + ' / ' + poster.naturalHeight;
            }

            ctx.clearRect(0, 0, image.width, image.height);
            ctx.drawImage(poster, 0, 0, image.width, image.height);
        };

        poster.src = banner.image;
    }

    function showBanner(banner) {
        if (isOpen) {
            pending.push(banner);
            return;
        }

        if (!banner.image) return;

        if (statsController) statsController.pause();

        current = banner;
        isOpen = true;
        image.setAttribute('aria-label', banner.title || 'Banner informasi');
        paintPoster(banner);

        if (banner.url) {
            link.href = banner.url;
            link.setAttribute('target', '_blank');
            link.setAttribute('rel', 'noopener');
            link.style.cursor = 'pointer';
        } else {
            link.removeAttribute('href');
            link.removeAttribute('target');
            link.style.cursor = 'default';
        }

        root.classList.add('is-open');
        root.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function closeBanner() {
        if (!isOpen) return;

        if (current) {
            setFlag('bp_banner_closed_' + current.id);
        }

        root.classList.remove('is-open');
        root.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        isOpen = false;
        current = null;

        window.setTimeout(function () {
            if (pending.length) {
                showBanner(pending.shift());
            } else if (statsController) {
                statsController.resume();
            }
        }, 320);
    }

    Array.prototype.forEach.call(root.querySelectorAll('[data-bp-close]'), function (el) {
        el.addEventListener('click', closeBanner);
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeBanner();
    });

    function schedule(banner) {
        if (!banner.image || scheduled[banner.id] || readFlag('bp_banner_closed_' + banner.id)) {
            return;
        }

        scheduled[banner.id] = true;
        var trigger = banner.trigger || 'welcome';

        if (trigger === 'time') {
            var minutes = Math.max(1, parseInt(banner.delay_minutes || 1, 10));
            window.setTimeout(function () { showBanner(banner); }, minutes * 60000);
            return;
        }

        if (trigger === 'scroll') {
            var onScroll = function () {
                if (scrollProgress() >= 0.85) {
                    window.removeEventListener('scroll', onScroll);
                    showBanner(banner);
                }
            };
            window.addEventListener('scroll', onScroll, { passive: true });
            onScroll();
            return;
        }

        if (trigger === 'exit') {
            if ('ontouchstart' in window) {
                window.setTimeout(function () { showBanner(banner); }, 15000);
                return;
            }

            var onExit = function (e) {
                if (e.clientY <= 0 || (!e.relatedTarget && !e.toElement)) {
                    document.removeEventListener('mouseout', onExit);
                    showBanner(banner);
                }
            };
            document.addEventListener('mouseout', onExit);
            return;
        }

        // Banner sambutan ditunda sampai halaman selesai dimuat dan browser
        // idle, agar poster besar (ratusan KB) tidak berebut bandwidth
        // dengan LCP/TBT. Bila browser tidak mendukung requestIdleCallback,
        // pakai batas waktu tetap.
        var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        var welcomeDelay = reduced ? 800 : 2600;

        var reveal = function () {
            if ('requestIdleCallback' in window) {
                window.requestIdleCallback(function () {
                    window.setTimeout(function () { showBanner(banner); }, welcomeDelay);
                }, { timeout: 3000 });
            } else {
                window.setTimeout(function () { showBanner(banner); }, welcomeDelay);
            }
        };

        if (document.readyState === 'complete') {
            reveal();
        } else {
            window.addEventListener('load', reveal, { once: true });
        }
    }

    banners.forEach(schedule);
})();
</script>
@endif
