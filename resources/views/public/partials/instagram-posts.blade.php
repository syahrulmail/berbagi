@push('styles')
<style>
    /* Carousel postingan Instagram.
       Ringan saat dimuat: yang dirender hanya gambar sampul (thumbnail).
       Embed resmi Instagram (±229 KB HTML + JS eksternal per postingan)
       baru dimuat setelah pengunjung menekan kartu. */
    .ig-posts {
        position: relative;
    }
    .ig-posts-track {
        display: flex;
        align-items: flex-start;
        gap: 14px;
        overflow-x: auto;
        scroll-snap-type: x mandatory;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
        padding: 2px 2px 8px;
    }
    .ig-posts-track::-webkit-scrollbar {
        display: none;
    }
    /* Saat postingan sedikit (muat di layar lebar), kartu ditengahkan. */
    @media (min-width: 768px) {
        .ig-posts-track--center {
            justify-content: center;
            justify-content: safe center;
        }
    }
    .ig-posts-item {
        position: relative;
        flex: 0 0 272px;
        max-width: 272px;
        overflow: hidden;
        border-radius: 14px;
        background: #000;
        box-shadow: 0 12px 30px -18px rgba(2, 35, 33, .45);
        scroll-snap-align: center;
    }
    .ig-posts-media {
        position: relative;
        display: block;
        width: 100%;
        height: 100%;
        padding: 0;
        border: 0;
        background: #000;
        cursor: pointer;
        color: #fff;
        text-decoration: none;
    }
    .ig-posts-cover {
        display: block;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    /* Tampil di belakang sampul; terlihat bila thumbnail tidak tersedia. */
    .ig-posts-fallback {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #4f5bd5, #962fbf, #d62976, #fa7e1e, #feda75);
        opacity: .9;
    }
    .ig-posts-fallback svg {
        width: 44px;
        height: 44px;
        fill: #fff;
        opacity: .85;
    }
    .ig-posts-shade {
        position: absolute;
        inset: 0;
        background: linear-gradient(180deg, rgba(0, 0, 0, .16), rgba(0, 0, 0, 0) 40%, rgba(0, 0, 0, .1));
        pointer-events: none;
    }
    .ig-posts-play {
        position: absolute;
        top: 50%;
        left: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 56px;
        height: 56px;
        margin: -28px 0 0 -28px;
        border-radius: 9999px;
        background: rgba(255, 255, 255, .92);
        box-shadow: 0 8px 24px -8px rgba(0, 0, 0, .55);
        pointer-events: none;
        transition: transform .2s ease, background .2s ease;
    }
    .ig-posts-play svg {
        width: 22px;
        height: 22px;
        margin-left: 3px;
        fill: #111;
    }
    .ig-posts-media:hover .ig-posts-play,
    .ig-posts-media:focus-visible .ig-posts-play {
        transform: scale(1.08);
        background: #fff;
    }
    .ig-posts-media:focus-visible {
        outline: 3px solid #f59e0b;
        outline-offset: -3px;
    }
    .ig-posts-item iframe {
        position: absolute;
        top: 0;
        left: 0;
        display: block;
        width: 100%;
        height: 1600px;
        border: 0;
        background: #000;
        transform-origin: top center;
        transform: translateY(var(--ig-top, -54px)) scale(var(--ig-zoom, 1));
    }
    /* Navigasi geser (desktop saja). */
    .ig-posts-nav {
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
    .ig-posts-nav:hover:not(:disabled) {
        transform: scale(1.06);
    }
    .ig-posts-nav:disabled {
        opacity: .35;
        cursor: default;
    }
    .ig-posts-nav svg {
        width: 18px;
        height: 18px;
        fill: none;
        stroke: currentColor;
        stroke-width: 2.4;
        stroke-linecap: round;
        stroke-linejoin: round;
    }
    .ig-posts-nav--prev { left: -10px; }
    .ig-posts-nav--next { right: -10px; }
    @media (min-width: 768px) {
        .ig-posts--scrollable .ig-posts-nav {
            display: flex;
        }
    }
</style>
@endpush

@if(!empty($instagramPosts))
<section class="border-b border-black/5 bg-white py-10" data-reveal>
    <div class="container">
        <div class="mx-auto max-w-6xl">
            <div class="ig-posts" data-ig-posts>
                @if(count($instagramPosts) > 1)
                    <button type="button" class="ig-posts-nav ig-posts-nav--prev" data-ig-prev aria-label="Postingan sebelumnya">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 6l-6 6 6 6"/></svg>
                    </button>
                    <button type="button" class="ig-posts-nav ig-posts-nav--next" data-ig-next aria-label="Postingan berikutnya">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>
                    </button>
                @endif
                <div class="ig-posts-track{{ count($instagramPosts) <= 4 ? ' ig-posts-track--center' : '' }}" data-ig-track>
                    @foreach($instagramPosts as $index => $post)
                        <div class="ig-posts-item" data-ig-item
                             style="aspect-ratio: 1 / {{ $post['ratio'] }}; --ig-zoom: {{ $post['zoom'] }}; --ig-top: {{ $post['top'] }}px;">
                            <a class="ig-posts-media" href="{{ $post['url'] }}"
                               target="_blank" rel="noopener noreferrer"
                               data-ig-open data-src="{{ $post['embed'] }}"
                               aria-label="Putar postingan Instagram {{ $index + 1 }}">
                                <span class="ig-posts-fallback" aria-hidden="true">
                                    <svg viewBox="0 0 24 24"><path d="M12 2.2c3.2 0 3.6 0 4.9.1 1.2.1 1.8.3 2.2.4.6.2 1 .5 1.4.9.4.4.7.8.9 1.4.2.4.4 1 .4 2.2.1 1.3.1 1.7.1 4.9s0 3.6-.1 4.9c-.1 1.2-.3 1.8-.4 2.2-.2.6-.5 1-.9 1.4-.4.4-.8.7-1.4.9-.4.2-1 .4-2.2.4-1.3.1-1.7.1-4.9.1s-3.6 0-4.9-.1c-1.2-.1-1.8-.3-2.2-.4-.6-.2-1-.5-1.4-.9-.4-.4-.7-.8-.9-1.4-.2-.4-.4-1-.4-2.2-.1-1.3-.1-1.7-.1-4.9s0-3.6.1-4.9c.1-1.2.3-1.8.4-2.2.2-.6.5-1 .9-1.4.4-.4.8-.7 1.4-.9.4-.2 1-.4 2.2-.4 1.3-.1 1.7-.1 4.9-.1zm0 1.8c-3.1 0-3.5 0-4.8.1-1.1.1-1.7.2-2.1.4-.5.2-.9.4-1.2.8-.4.4-.6.7-.8 1.2-.1.4-.3 1-.4 2.1-.1 1.3-.1 1.7-.1 4.8s0 3.5.1 4.8c.1 1.1.2 1.7.4 2.1.2.5.4.9.8 1.2.4.4.7.6 1.2.8.4.1 1 .3 2.1.4 1.3.1 1.7.1 4.8.1s3.5 0 4.8-.1c1.1-.1 1.7-.2 2.1-.4.5-.2.9-.4 1.2-.8.4-.4.6-.7.8-1.2.1-.4.3-1 .4-2.1.1-1.3.1-1.7.1-4.8s0-3.5-.1-4.8c-.1-1.1-.2-1.7-.4-2.1-.2-.5-.4-.9-.8-1.2-.4-.4-.7-.6-1.2-.8-.4-.1-1-.3-2.1-.4-1.3-.1-1.7-.1-4.8-.1zm0 3.1a4.9 4.9 0 110 9.8 4.9 4.9 0 010-9.8zm0 1.8a3.1 3.1 0 100 6.2 3.1 3.1 0 000-6.2zm5.1-3.2a1.15 1.15 0 110 2.3 1.15 1.15 0 010-2.3z"/></svg>
                                </span>
                                @if($post['thumbnail'])
                                    <img class="ig-posts-cover" src="{{ $post['thumbnail'] }}"
                                         alt="Sampul postingan Instagram {{ $index + 1 }}"
                                         loading="lazy" decoding="async" referrerpolicy="no-referrer"
                                         onerror="this.remove()">
                                @endif
                                <span class="ig-posts-shade" aria-hidden="true"></span>
                                <span class="ig-posts-play" aria-hidden="true">
                                    <svg viewBox="0 0 24 24"><path d="M8 5.14v13.72L19 12z"/></svg>
                                </span>
                            </a>
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
    var root = document.querySelector('[data-ig-posts]');
    if (!root) return;

    var track = root.querySelector('[data-ig-track]');
    if (!track) return;

    var items = track.querySelectorAll('[data-ig-item]');
    var prev = root.querySelector('[data-ig-prev]');
    var next = root.querySelector('[data-ig-next]');

    /* Muat embed Instagram hanya setelah kartu ditekan. Sebelum itu
       halaman hanya memuat gambar sampul, jadi tetap ringan. */
    root.addEventListener('click', function (event) {
        var opener = event.target.closest('[data-ig-open]');
        if (!opener) return;

        var src = opener.getAttribute('data-src');
        if (!src) return;

        var item = opener.closest('[data-ig-item]');
        if (!item || item.querySelector('iframe')) return;

        // Biarkan Ctrl/Cmd/Shift-klik tetap membuka Instagram di tab baru.
        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

        event.preventDefault();

        var frame = document.createElement('iframe');
        frame.src = src;
        frame.title = opener.getAttribute('aria-label') || 'Postingan Instagram';
        frame.setAttribute('allowtransparency', 'true');
        frame.setAttribute('allowfullscreen', 'true');
        frame.setAttribute('scrolling', 'no');
        frame.setAttribute('frameborder', '0');
        frame.setAttribute('referrerpolicy', 'no-referrer-when-downgrade');

        opener.parentNode.replaceChild(frame, opener);
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
        root.classList.toggle('ig-posts--scrollable', max > 4);

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
