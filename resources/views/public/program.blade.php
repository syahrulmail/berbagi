@extends('layouts.public')

@section('title', $program->name . ' · Berbagi.or.id')
@section('meta_description', mb_substr(strip_tags($program->description ?? ''), 0, 155))

@push('styles')
<style>
    .share-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 38px;
        height: 38px;
        border-radius: 9999px;
        border: 1px solid #d2e2e0;
        color: #086e66;
        font-size: 15px;
        transition: all 0.15s ease;
    }
    .share-icon:hover {
        background: #086e66;
        border-color: #086e66;
        color: #fff;
    }
    .program-cat-badge {
        background: #08574f;
    }
    .gallery-thumb {
        border-color: rgba(0, 0, 0, 0.08);
        cursor: pointer;
        transition: border-color 0.15s ease, opacity 0.15s ease;
    }
    .gallery-thumb:hover {
        border-color: #08a899;
        opacity: 0.9;
    }
    .gallery-thumb-active {
        border-color: #08a899;
        box-shadow: 0 0 0 3px rgba(8, 168, 153, 0.18);
    }
    .media-slider { position: relative; overflow: hidden; }
    .media-slides-track { display: flex; transition: transform .5s ease; }
    .media-slide { flex: 0 0 100%; min-width: 0; }
    .media-slide-img { display: block; width: 100%; aspect-ratio: 4/3; object-fit: cover; }
    .media-slide-video .media-slide-inner { position: relative; width: 100%; aspect-ratio: 4/3; background: #022321; }
    .media-slide-video iframe { position: absolute; inset: 0; width: 100%; height: 100%; }
    .media-arrow {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        z-index: 3;
        width: 38px;
        height: 38px;
        border: none;
        border-radius: 9999px;
        background: rgba(2, 35, 33, .5);
        color: #fff;
        font-size: 14px;
        cursor: pointer;
        display: grid;
        place-items: center;
        opacity: 0;
        transition: opacity .2s, background .2s;
    }
    .media-arrow:hover { background: rgba(2, 35, 33, .75); }
    .media-arrow-prev { left: 12px; }
    .media-arrow-next { right: 12px; }
    .media-slider:hover .media-arrow { opacity: 1; }
    @media (max-width: 640px) {
        .media-arrow { opacity: 1; width: 32px; height: 32px; }
        .media-arrow-prev { left: 8px; }
        .media-arrow-next { right: 8px; }
    }
    .media-dots {
        position: absolute;
        bottom: 12px;
        left: 0;
        right: 0;
        z-index: 3;
        display: flex;
        justify-content: center;
        gap: 6px;
    }
    .media-dot {
        width: 8px;
        height: 8px;
        padding: 0;
        border: none;
        border-radius: 9999px;
        background: rgba(255, 255, 255, .55);
        cursor: pointer;
        transition: all .2s;
    }
    .media-dot-active { width: 22px; background: #fff; }
    .media-counter {
        position: absolute;
        bottom: 10px;
        right: 14px;
        z-index: 3;
        font-size: 11px;
        font-weight: 700;
        color: #fff;
        background: rgba(2, 35, 33, .5);
        padding: 4px 10px;
        border-radius: 9999px;
        pointer-events: none;
    }
    .media-thumb { position: relative; }
    .media-thumb-video-badge {
        position: absolute;
        inset: 0;
        display: grid;
        place-items: center;
        color: #fff;
        font-size: 20px;
        background: rgba(2, 35, 33, .35);
    }
    /* Donasi Cepat sticky/melayang saat discroll — hanya mode desktop (layar tinggi) */
    @media (min-width: 1024px) and (min-height: 640px) {
        .donasi-cepat-aside {
            position: sticky;
            top: 96px;
            align-self: start;
        }
        .donasi-cepat-card {
            box-shadow: 0 2px 8px rgba(2, 35, 33, .05), 0 12px 28px -8px rgba(2, 35, 33, .1);
        }
    }
    .rich-text { color: var(--gray-700); }
    .rich-text p { margin: 8px 0; }
    .rich-text h2, .rich-text h3 { margin: 16px 0 8px; color: #043d3a; font-weight: 700; }
    .rich-text h2 { font-size: 1.25rem; }
    .rich-text h3 { font-size: 1.1rem; }
    .rich-text ul, .rich-text ol { margin: 8px 0; padding-left: 24px; }
    .rich-text blockquote {
        margin: 12px 0;
        padding: 10px 16px;
        border-left: 4px solid #3bb3ae;
        background: #f0faf8;
        color: var(--gray-700);
        border-radius: 0 10px 10px 0;
    }
    .rich-text a { color: #086e66; text-decoration: underline; }
    .rich-text img { max-width: 100%; border-radius: 12px; margin: 8px 0; }
    /* Swipe untuk galeri media */
    .media-slider { touch-action: pan-y; }
    .media-slides-track.media-dragging { transition: none !important; }
    .media-slider.media-dragging * { user-select: none; -webkit-user-select: none; }
</style>
@endpush

@php
    $showGoal = (bool) $program->show_goal;
    $progress = $program->goal_amount > 0 ? min(100, round(((float) $collected / (float) $program->goal_amount) * 100, 1)) : 0;
    $isComplete = $program->goal_amount > 0 && (float) $collected >= (float) $program->goal_amount;
    $remaining = $isComplete ? null : max(0, (float) $program->goal_amount - (float) $collected);

    if ($agen) {
        $waMsg = str_replace(
            ['{agen}', '{program}'],
            [$agen->name, $program->name],
            $waTemplate ?: 'Assalamualaikum {agen}, saya ingin berdonasi untuk program {program} melalui Anda.'
        );
        $waSource = 'agent';
    } else {
        $waMsg = str_replace('{program}', $program->name, $waTemplate ?: 'Assalamualaikum, saya ingin berdonasi untuk program {program}. Mohon info selanjutnya.');
        $waSource = 'program';
    }

    $waUrl = 'https://wa.me/' . $waNumber . '?text=' . urlencode($waMsg);
    $shareUrl = route('public.program', $program->slug);
    $shareTitle = urlencode('Bantu wakaf & sedekah: ' . $program->name);
    $shareText = urlencode('Bantu wakaf & sedekah: ' . $program->name . ' — ' . $shareUrl);
    $ctaHelpName = $agen ? $agen->name : 'Tim BWA';
@endphp

@section('donasiBarTitle', $program->name)
@section('donasiBarSub', $showGoal ? ($isComplete ? 'Target tercapai · Terima kasih' : 'Progress ' . $progress . '% · ' . ($remaining !== null ? 'Dibutuhkan Rp ' . number_format($remaining, 0, ',', '.') : 'Target tercapai')) : 'Bantu wujudkan program kebaikan ini bersama BWA')
@section('donasiBarUrl', $waUrl)
@section('donasiBarSource', $waSource)
@section('donasiBarProgram', $program->id)

@section('content')

<main class="section">
    <div class="container">
        <nav class="mb-8 flex flex-wrap items-center gap-2 text-sm text-gray-500">
            <a href="{{ route('home') }}" class="font-medium text-primary-600 transition hover:text-primary-700"><i class="fas fa-arrow-left"></i> Beranda</a>
            <span>/</span>
            @if($agen)
                <a href="{{ route('public.agent', $agen->slug) }}" class="font-medium text-primary-600 transition hover:text-primary-700">{{ $agen->name }}</a>
                <span>/</span>
            @endif
            <span class="truncate">{{ $program->name }}</span>
        </nav>

        @if($agen)
            <div class="mb-8 flex flex-wrap items-center gap-3 rounded-2xl border border-primary-100 bg-primary-50/60 p-4" data-reveal>
                @if(!empty($agenPhoto))
                <img src="{{ $agenPhoto }}" alt="{{ $agen->name }}" class="h-12 w-12 flex-shrink-0 rounded-xl object-cover">
                @else
                <div class="grid h-12 w-12 flex-shrink-0 place-items-center rounded-xl bg-gradient-to-br from-primary-500 to-primary-700 text-lg font-bold text-white">{{ strtoupper(mb_substr($agen->name, 0, 1)) }}</div>
                @endif
                <div class="flex-1">
                    <p class="text-sm text-gray-500">CS Badan Wakaf Al Quran</p>
                    <p class="font-semibold text-primary-900">{{ $agen->name }}</p>
                </div>
                <a href="{{ route('public.agent', $agen->slug) }}" class="btn btn-outline btn-sm">Lihat Program Lainnya</a>
            </div>
        @endif

        <div class="grid items-start gap-8 lg:grid-cols-2">
            <div class="min-w-0">
                @php $slides = $program->media_slides; $slideCount = count($slides); @endphp
                <div class="relative overflow-hidden rounded-3xl border border-black/5 shadow-card" data-reveal>
                    @if($slideCount > 0)
                    <div class="media-slider" id="mediaSlider">
                        <div class="media-slides-track" id="mediaSliderTrack">
                            @foreach($slides as $idx => $slide)
                                @if($slide['type'] === 'video')
                                <div class="media-slide media-slide-video">
                                    <div class="media-slide-inner">
                                        <iframe data-src="{{ $slide['url'] }}" title="Video {{ $program->name }}"
                                                frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                                allowfullscreen loading="lazy" class="h-full w-full"></iframe>
                                    </div>
                                </div>
                                @else
                                <div class="media-slide">
                                    <img src="{{ $slide['url'] }}" alt="{{ $program->name }} {{ $idx + 1 }}" class="media-slide-img" loading="lazy">
                                </div>
                                @endif
                            @endforeach
                        </div>
                        @if($slideCount > 1)
                        <button type="button" class="media-arrow media-arrow-prev" data-media-prev aria-label="Slide sebelumnya"><i class="fas fa-chevron-left"></i></button>
                        <button type="button" class="media-arrow media-arrow-next" data-media-next aria-label="Slide berikutnya"><i class="fas fa-chevron-right"></i></button>
                        <div class="media-dots" id="mediaDots" role="tablist" aria-label="Navigasi slide"></div>
                        <span class="media-counter" id="mediaCounter">1 / {{ $slideCount }}</span>
                        @endif
                        @if($program->category)
                            <span class="cat-badge {{ $program->category }} absolute left-4 top-4 !text-[12px] !px-3 !py-1.5">{{ $program->category }}</span>
                        @endif
                        @if($program->program_category)
                            <span class="cat-badge program-cat-badge absolute right-4 top-4 !text-[12px] !px-3 !py-1.5">{{ $program->category_label }}</span>
                        @endif
                    </div>
                    @else
                        <div class="grid aspect-[4/3] w-full place-items-center bg-gradient-to-br from-primary-100 to-primary-50 text-primary-400">
                            <i class="fas fa-book-quran" style="font-size:72px;"></i>
                        </div>
                    @endif

                    @if($slideCount > 1)
                    <div class="mt-4 grid grid-cols-4 gap-3" id="mediaSliderThumbs" data-reveal>
                        @foreach($slides as $idx => $slide)
                            <button type="button"
                                    class="gallery-thumb media-thumb {{ $idx === 0 ? 'gallery-thumb-active' : '' }} aspect-[4/3] overflow-hidden rounded-xl border transition"
                                    data-media-thumb="{{ $idx }}"
                                    aria-label="Tampilkan media {{ $idx + 1 }}">
                                @if($slide['type'] === 'video')
                                    <img src="{{ $slide['thumb'] ?? '' }}" alt="Video {{ $program->name }}" class="h-full w-full object-cover">
                                    <span class="media-thumb-video-badge"><i class="fab fa-youtube"></i></span>
                                @else
                                    <img src="{{ $slide['url'] }}" alt="{{ $program->name }} {{ $idx + 1 }}" class="h-full w-full object-cover" loading="lazy">
                                @endif
                            </button>
                        @endforeach
                    </div>
                    @endif
                </div>

                <div class="mt-6 lg:hidden" data-reveal>
                    @include('public.partials.donasi-cepat-card')
                </div>

                <div class="mt-8" data-reveal>
                    <div class="mb-3 flex flex-wrap items-center gap-2">
                        @if($program->category)
                            <span class="cat-badge {{ $program->category }} hidden lg:inline-block">{{ $program->category }}</span>
                        @endif
                        @foreach($program->campaignTags as $tag)
                            <span class="tag-chip">{{ $tag->name }}</span>
                        @endforeach
                    </div>
                    <h1 class="text-3xl font-extrabold text-primary-900 md:text-4xl">{{ $program->name }}</h1>
                    <div class="rich-text lead mt-4">{!! $program->description !!}</div>
                </div>

                @if(count($relatedCards))
                    <section class="mt-14" data-reveal>
                        <div class="section-head">
                            <div>
                                <h2>Program Serupa</h2>
                                <p class="muted mt-1 text-sm">Mungkin Anda juga tertarik mendukung program berikut.</p>
                            </div>
                        </div>
                        <div class="grid gap-5 sm:grid-cols-2">
                            @foreach($relatedCards as $p)
                                @include('public.partials.program-card', ['p' => $p])
                            @endforeach
                        </div>
                    </section>
                @endif

                <section class="mt-14 rounded-3xl bg-gradient-to-br from-primary-700 to-primary-950 p-8 text-center text-white md:p-10" data-reveal>
                    <h2 class="text-2xl font-extrabold md:text-3xl">Wujudkan kebaikan, mulai dari {{ $program->name }}</h2>
                    <p class="mx-auto mt-3 max-w-xl text-sm text-primary-100/90">Setiap rupiah sangat berarti. {{ $ctaHelpName }} siap membantu sepenuh hati.</p>
                    <a href="{{ $waUrl }}" target="_blank" rel="noopener" class="btn btn-wa mt-7 !px-8 !py-3.5 !text-base"
                       data-wa-log data-wa-source="{{ $waSource }}" data-wa-program="{{ $program->id }}" @if($agen) data-wa-agen="{{ $agen->id }}" @endif>
                        <i class="fab fa-whatsapp"></i> Berbagi Sekarang
                    </a>
                </section>
            </div>

            <aside class="hidden lg:block donasi-cepat-aside" data-reveal>
                @include('public.partials.donasi-cepat-card')
            </aside>
        </div>
    </div>
</main>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';
    function copyUrl(done) {
        var url = window.location.href;
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(url).then(done, done);
        } else {
            var ta = document.createElement('textarea');
            ta.value = url;
            ta.style.position = 'fixed';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.select();
            try { document.execCommand('copy'); } catch (e) {}
            document.body.removeChild(ta);
            done();
        }
    }
    document.querySelectorAll('[data-copy-link]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            copyUrl(function () {
                if (window.BerbagiToast) window.BerbagiToast('Link berhasil disalin.');
            });
        });
    });
    document.querySelectorAll('[data-share-copy]').forEach(function (icon) {
        icon.addEventListener('click', function (e) {
            e.preventDefault();
            var platform = icon.getAttribute('data-share-copy');
            copyUrl(function () {
                if (window.BerbagiToast) window.BerbagiToast('Link disalin. Tempel di ' + platform + '.');
            });
        });
    });

    // ---- Auto slideshow media (gambar + video di belakang) ----
    var slider = document.getElementById('mediaSlider');
    if (!slider) return;
    var track = document.getElementById('mediaSliderTrack');
    var slides = track ? Array.prototype.slice.call(track.children) : [];
    var count = slides.length;
    if (count < 1) return;

    var index = 0;
    var timer = null;
    var INTERVAL = 4500;

    var prevBtn = slider.querySelector('[data-media-prev]');
    var nextBtn = slider.querySelector('[data-media-next]');
    var thumbs = Array.prototype.slice.call(document.querySelectorAll('[data-media-thumb]'));
    var dotsWrap = document.getElementById('mediaDots');
    var counter = document.getElementById('mediaCounter');
    var dots = [];

    function isVideo(i) {
        return slides[i] && slides[i].classList.contains('media-slide-video');
    }

    function loadVideo(i) {
        var iframe = slides[i] ? slides[i].querySelector('iframe[data-src]') : null;
        if (iframe && !iframe.getAttribute('src')) {
            iframe.setAttribute('src', iframe.getAttribute('data-src'));
        }
    }

    function go(i) {
        index = (i + count) % count;
        if (track) track.style.transform = 'translateX(-' + (index * 100) + '%)';
        thumbs.forEach(function (t, k) { t.classList.toggle('gallery-thumb-active', k === index); });
        dots.forEach(function (d, k) { d.classList.toggle('media-dot-active', k === index); });
        if (counter) counter.textContent = (index + 1) + ' / ' + count;
        loadVideo(index);
        restart();
    }

    function stop() { if (timer) { clearInterval(timer); timer = null; } }

    function restart() {
        stop();
        timer = setInterval(function () {
            if (isVideo(index)) return;
            go(index + 1);
        }, INTERVAL);
    }

    if (dotsWrap) {
        for (var k = 0; k < count; k++) {
            (function (k) {
                var d = document.createElement('button');
                d.type = 'button';
                d.className = 'media-dot' + (k === 0 ? ' media-dot-active' : '');
                d.setAttribute('aria-label', 'Pergi ke slide ' + (k + 1));
                d.addEventListener('click', function () { go(k); });
                dotsWrap.appendChild(d);
                dots.push(d);
            })(k);
        }
    }

    if (prevBtn) prevBtn.addEventListener('click', function () { go(index - 1); });
    if (nextBtn) nextBtn.addEventListener('click', function () { go(index + 1); });
    thumbs.forEach(function (t) {
        t.addEventListener('click', function () { go(parseInt(t.getAttribute('data-media-thumb'), 10)); });
    });

    slider.addEventListener('mouseenter', stop);
    slider.addEventListener('mouseleave', restart);

    // Swipe / drag galeri media (jari & mouse)
    var drag = null;
    var SWIPE_THRESHOLD = 50;
    function dragStart(x, pointerId) {
        if (drag) return;
        drag = { id: pointerId, startX: x, dx: 0, active: false };
        track.classList.add('media-dragging');
        stop();
    }
    function dragMove(x, pointerId) {
        if (!drag || pointerId !== drag.id) return;
        drag.dx = x - drag.startX;
        if (Math.abs(drag.dx) > 8) drag.active = true;
        track.style.transform = 'translateX(calc(-' + (index * 100) + '% + ' + drag.dx + 'px))';
    }
    function dragEnd(pointerId) {
        if (!drag || pointerId !== drag.id) return;
        var d = drag;
        drag = null;
        track.classList.remove('media-dragging');
        if (d.active && Math.abs(d.dx) > SWIPE_THRESHOLD) {
            go(index + (d.dx < 0 ? 1 : -1));
        } else {
            go(index);
        }
    }
    function dragCancel(pointerId) {
        if (!drag || pointerId !== drag.id) return;
        drag = null;
        track.classList.remove('media-dragging');
        go(index);
    }
    slider.addEventListener('pointerdown', function (e) {
        if (e.target.closest('button')) return;
        dragStart(e.clientX, e.pointerId);
    });
    window.addEventListener('pointermove', function (e) { dragMove(e.clientX, e.pointerId); });
    window.addEventListener('pointerup', function (e) { dragEnd(e.pointerId); });
    window.addEventListener('pointercancel', function (e) { dragCancel(e.pointerId); });

    document.addEventListener('keydown', function (e) {
        if (document.activeElement && slider.contains(document.activeElement)) {
            if (e.key === 'ArrowLeft') { e.preventDefault(); go(index - 1); }
            else if (e.key === 'ArrowRight') { e.preventDefault(); go(index + 1); }
        }
    });

    loadVideo(0);
    restart();
})();
</script>
@endpush
