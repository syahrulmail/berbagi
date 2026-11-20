@push('styles')
<style>
    /* Carousel video beranda.
       Ringan saat dimuat: hanya gambar sampul yang diunduh. Berkas video
       (maksimal 10 MB) baru dimuat setelah pengunjung menekan tombol putar. */
    .hv {
        position: relative;
    }
    .hv-track {
        display: flex;
        align-items: flex-start;
        gap: 14px;
        overflow-x: auto;
        scroll-snap-type: x mandatory;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
        padding: 2px 2px 8px;
    }
    .hv-track::-webkit-scrollbar {
        display: none;
    }
    /* Saat video sedikit (muat di layar lebar), kartu ditengahkan. */
    @media (min-width: 768px) {
        .hv-track--center {
            justify-content: center;
            justify-content: safe center;
        }
    }
    .hv-item {
        position: relative;
        flex: 0 0 272px;
        max-width: 272px;
        overflow: hidden;
        border-radius: 14px;
        background: #000;
        box-shadow: 0 12px 30px -18px rgba(2, 35, 33, .45);
        scroll-snap-align: center;
    }
    .hv-media {
        position: relative;
        width: 100%;
        height: 100%;
        background: #000;
    }
    .hv-cover {
        position: absolute;
        inset: 0;
        display: block;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    /* Tampil di belakang sampul; terlihat bila belum ada sampul. */
    .hv-fallback {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #0f766e, #0b3b36);
    }
    .hv-fallback svg {
        width: 46px;
        height: 46px;
        fill: #fff;
        opacity: .7;
    }
    .hv-shade {
        position: absolute;
        inset: 0;
        background: linear-gradient(180deg, rgba(0, 0, 0, .16), rgba(0, 0, 0, 0) 40%, rgba(0, 0, 0, .1));
        pointer-events: none;
    }
    .hv-video {
        position: absolute;
        inset: 0;
        display: none;
        width: 100%;
        height: 100%;
        object-fit: contain;
        background: #000;
    }
    .hv-item.is-playing .hv-video {
        display: block;
    }
    .hv-item.is-playing .hv-cover,
    .hv-item.is-playing .hv-fallback,
    .hv-item.is-playing .hv-shade,
    .hv-item.is-playing .hv-play {
        display: none;
    }
    .hv-play {
        position: absolute;
        top: 50%;
        left: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 56px;
        height: 56px;
        margin: -28px 0 0 -28px;
        padding: 0;
        border: 0;
        border-radius: 9999px;
        background: rgba(255, 255, 255, .92);
        box-shadow: 0 8px 24px -8px rgba(0, 0, 0, .55);
        cursor: pointer;
        transition: transform .2s ease, background .2s ease;
    }
    .hv-play svg {
        width: 22px;
        height: 22px;
        margin-left: 3px;
        fill: #111;
    }
    .hv-play:hover,
    .hv-play:focus-visible {
        transform: scale(1.08);
        background: #fff;
    }
    .hv-play:focus-visible {
        outline: 3px solid #f59e0b;
        outline-offset: 2px;
    }
    /* Navigasi geser (desktop saja). */
    .hv-nav {
        position: absolute;
        top: 50%;
        z-index: 3;
        display: none;
        align-items: center;
        justify-content: center;
        width: 44px;
        height: 44px;
        margin-top: -18px;
        padding: 0;
        border: 1px solid rgba(2, 35, 33, .1);
        border-radius: 9999px;
        background: #fff;
        color: #0b3b36;
        box-shadow: 0 10px 24px -12px rgba(2, 35, 33, .5);
        cursor: pointer;
        transition: opacity .2s ease, transform .2s ease;
    }
    .hv-nav:hover:not(:disabled) {
        transform: scale(1.06);
    }
    .hv-nav:disabled {
        opacity: .35;
        cursor: default;
    }
    .hv-nav svg {
        width: 18px;
        height: 18px;
        fill: none;
        stroke: currentColor;
        stroke-width: 2.4;
        stroke-linecap: round;
        stroke-linejoin: round;
    }
    .hv-nav--prev { left: -10px; }
    .hv-nav--next { right: -10px; }
    @media (min-width: 768px) {
        .hv--scrollable .hv-nav {
            display: flex;
        }
    }
</style>
@endpush

@if(!empty($homeVideos))
<section class="border-b border-black/5 bg-white py-10" data-reveal>
    <div class="container">
        <div class="mx-auto max-w-6xl">
            <div class="hv" data-hv>
                @if(count($homeVideos) > 1)
                    <button type="button" class="hv-nav hv-nav--prev" data-hv-prev aria-label="Video sebelumnya">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 6l-6 6 6 6"/></svg>
                    </button>
                    <button type="button" class="hv-nav hv-nav--next" data-hv-next aria-label="Video berikutnya">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>
                    </button>
                @endif
                <div class="hv-track{{ count($homeVideos) <= 4 ? ' hv-track--center' : '' }}" data-hv-track>
                    @foreach($homeVideos as $index => $video)
                        <div class="hv-item" data-hv-item style="aspect-ratio: 1 / {{ $video['ratio'] }};">
                            <div class="hv-media">
                                <span class="hv-fallback" aria-hidden="true">
                                    <svg viewBox="0 0 24 24"><path d="M4 5.5A1.5 1.5 0 015.5 4h9A1.5 1.5 0 0116 5.5v13a1.5 1.5 0 01-1.5 1.5h-9A1.5 1.5 0 014 18.5v-13zM17 8.5l4-2.5v12l-4-2.5v-7z"/></svg>
                                </span>
                                @if($video['poster'])
                                    <img class="hv-cover" src="{{ $video['poster'] }}"
                                         alt="Sampul video {{ $index + 1 }}"
                                         loading="lazy" decoding="async" onerror="this.remove()">
                                @endif
                                <span class="hv-shade" aria-hidden="true"></span>
                                <video class="hv-video" preload="none" playsinline
                                       data-src="{{ $video['src'] }}" data-mime="{{ $video['mime'] }}"
                                       aria-label="Video {{ $index + 1 }}"></video>
                                <button type="button" class="hv-play" data-hv-play
                                        aria-label="Putar video {{ $index + 1 }}">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5.14v13.72L19 12z"/></svg>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>

@push('scripts')
<script>
(function () {
    var root = document.querySelector('[data-hv]');
    if (!root) return;

    var track = root.querySelector('[data-hv-track]');
    if (!track) return;

    var items = track.querySelectorAll('[data-hv-item]');
    var prev = root.querySelector('[data-hv-prev]');
    var next = root.querySelector('[data-hv-next]');

    function pauseOthers(current) {
        track.querySelectorAll('video').forEach(function (video) {
            if (video !== current && !video.paused) video.pause();
        });
    }

    /* Video baru dimuat saat tombol putar ditekan. */
    root.addEventListener('click', function (event) {
        var button = event.target.closest('[data-hv-play]');
        if (!button) return;

        var item = button.closest('[data-hv-item]');
        var video = item ? item.querySelector('video') : null;
        if (!video) return;

        var src = video.getAttribute('data-src');
        if (!src) return;

        pauseOthers(video);

        if (!video.getAttribute('src')) {
            video.setAttribute('src', src);
        }

        item.classList.add('is-playing');
        video.controls = true;
        video.muted = false;

        var play = video.play();
        if (play && typeof play.catch === 'function') {
            play.catch(function () {
                video.muted = true;
                video.play().catch(function () {});
            });
        }
    });

    /* Navigasi geser untuk desktop. */
    function gap() {
        var style = window.getComputedStyle(track);
        return parseFloat(style.columnGap || style.gap) || 0;
    }

    function step() {
        var first = items[0];
        return first ? first.offsetWidth + gap() : track.clientWidth;
    }

    function maxScroll() {
        return Math.max(0, track.scrollWidth - track.clientWidth);
    }

    function sync() {
        var max = maxScroll();
        root.classList.toggle('hv--scrollable', max > 4);

        if (prev) prev.disabled = track.scrollLeft <= 4;
        if (next) next.disabled = track.scrollLeft >= max - 4;
    }

    if (prev) {
        prev.addEventListener('click', function () {
            track.scrollBy({ left: -step(), behavior: 'smooth' });
        });
    }

    if (next) {
        next.addEventListener('click', function () {
            track.scrollBy({ left: step(), behavior: 'smooth' });
        });
    }

    var frame = null;
    track.addEventListener('scroll', function () {
        if (frame) return;
        frame = window.requestAnimationFrame(function () {
            frame = null;
            sync();
        });
    }, { passive: true });

    window.addEventListener('resize', sync);
    sync();
})();
</script>
@endpush
@endif
