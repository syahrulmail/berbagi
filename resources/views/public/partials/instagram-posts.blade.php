@push('styles')
<style>
    /* Kartu menampilkan isi postingan saja: header embed Instagram
       (avatar, nama akun, tombol "View profile") dan footer disembunyikan
       dengan menggeser/memotong iframe, dan bilah hitam kiri-kanan pada
       video vertikal dihilangkan dengan memperbesar iframe sesuai rasio
       media (lihat perhitungan --ig-zoom & --ig-top dari controller). */
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
</style>
@endpush

@if(!empty($instagramPosts))
<section class="border-b border-black/5 bg-white py-10" data-reveal>
    <div class="container">
        <div class="mx-auto max-w-6xl">
            <div class="ig-posts" data-ig-posts data-interval="7000">
                <div class="ig-posts-track{{ count($instagramPosts) <= 4 ? ' ig-posts-track--center' : '' }}" data-ig-track>
                    @foreach($instagramPosts as $index => $post)
                        <div class="ig-posts-item" data-ig-item
                             style="aspect-ratio: 1 / {{ $post['ratio'] }}; --ig-zoom: {{ $post['zoom'] }}; --ig-top: {{ $post['top'] }}px;">
                            <iframe
                                src="{{ $post['embed'] }}"
                                title="Postingan Instagram {{ $index + 1 }}"
                                loading="lazy"
                                allowtransparency="true"
                                allowfullscreen="true"
                                scrolling="no"
                                frameborder="0"
                                referrerpolicy="no-referrer-when-downgrade"></iframe>
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
    var items = track ? track.querySelectorAll('[data-ig-item]') : [];
    if (!track || items.length < 2) return;

    var interval = parseInt(root.getAttribute('data-interval'), 10) || 7000;
    var timer = null;
    var paused = false;
    var index = 0;

    function step() {
        var item = items[index % items.length];
        if (!item) return;
        var left = item.offsetLeft - (track.clientWidth - item.offsetWidth) / 2;
        track.scrollTo({ left: Math.max(0, left), behavior: 'smooth' });
        index++;
    }

    function start() {
        stop();
        timer = setInterval(function () {
            if (!paused && !document.hidden) step();
        }, interval);
    }

    function stop() {
        if (timer) { clearInterval(timer); timer = null; }
    }

    root.addEventListener('mouseenter', function () { paused = true; });
    root.addEventListener('mouseleave', function () { paused = false; });
    track.addEventListener('touchstart', function () { paused = true; }, { passive: true });
    track.addEventListener('touchend', function () {
        setTimeout(function () { paused = false; }, 2500);
    }, { passive: true });

    if ('IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) { start(); } else { stop(); }
            });
        }, { threshold: 0.2 });
        observer.observe(root);
    } else {
        start();
    }
})();
</script>
@endpush
@endif
