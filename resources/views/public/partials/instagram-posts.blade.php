@push('styles')
<style>
    .ig-posts {
        position: relative;
    }
    .ig-posts-track {
        display: flex;
        gap: 16px;
        overflow-x: auto;
        scroll-snap-type: x mandatory;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
        padding: 4px 2px 12px;
    }
    .ig-posts-track::-webkit-scrollbar {
        display: none;
    }
    .ig-posts-item {
        flex: 0 0 300px;
        max-width: 300px;
        scroll-snap-align: center;
        border-radius: 20px;
        overflow: hidden;
        background: #fff;
        border: 1px solid rgba(2, 35, 33, .08);
        box-shadow: 0 12px 30px -18px rgba(2, 35, 33, .45);
    }
    .ig-posts-item iframe {
        display: block;
        width: 100%;
        height: 380px;
        border: 0;
        background: #fff;
    }
    @media (min-width: 768px) {
        .ig-posts-item {
            flex: 0 0 320px;
            max-width: 320px;
        }
        .ig-posts-item iframe {
            height: 400px;
        }
    }
</style>
@endpush

@if(!empty($instagramPosts))
<section class="border-b border-black/5 bg-white py-14" data-reveal>
    <div class="container">
        <div class="mx-auto max-w-6xl">
            <div class="mb-6 text-center">
                <h2 class="text-2xl font-extrabold text-primary-900">Kabar Terbaru Kami</h2>
                <p class="mt-2 text-sm text-gray-500">Ikuti kegiatan dan program terbaru BWA melalui Instagram.</p>
            </div>

            <div class="ig-posts" data-ig-posts data-interval="7000">
                <div class="ig-posts-track" data-ig-track>
                    @foreach($instagramPosts as $index => $embed)
                        <div class="ig-posts-item" data-ig-item>
                            <iframe
                                src="{{ $embed }}"
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
