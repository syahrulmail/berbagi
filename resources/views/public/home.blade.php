@extends('layouts.public')

@section('title', 'Berbagi.or.id · Badan Wakaf Al Qur\'an')

@push('styles')
<style>
    .quote-content blockquote {
        margin: 0.5rem 0;
        padding: 0.25rem 0 0.25rem 1rem;
        border-left: 3px solid #d4911e;
        text-align: left;
        color: #086e66;
        font-style: italic;
    }
    .testimonial-photo {
        width: 120px;
        height: 120px;
        object-fit: contain;
        flex: none;
        filter: drop-shadow(0 8px 16px rgba(8, 110, 102, 0.18));
    }
    .testimonial-photo-placeholder {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 120px;
        height: 120px;
        flex: none;
        border-radius: 9999px;
        background: #e7f4f2;
        color: #086e66;
        font-size: 44px;
        font-weight: 700;
    }
    .testimonial-slide { display: none; }
    .testimonial-slide.active { display: block; animation: tFade .6s ease; }
    @keyframes tFade {
        from { opacity: 0; transform: translateY(8px); }
        to { opacity: 1; transform: none; }
    }
    .testimonial-text {
        font-style: italic;
        font-weight: 400;
        font-size: 1.15rem;
        line-height: 1.8;
        color: #064e3b;
    }
    .testimonial-name {
        font-style: normal;
        font-weight: 700;
        color: #0f172a;
    }
    #testimonialDots button {
        width: 9px;
        height: 9px;
        border-radius: 9999px;
        border: none;
        padding: 0;
        cursor: pointer;
        background: #cbe4e0;
        transition: background .2s, transform .2s;
    }
    #testimonialDots button.active {
        background: #08A899;
        transform: scale(1.25);
    }
    .logo-marquee { overflow: hidden; }
    .logo-track {
        display: flex;
        align-items: center;
        gap: 56px;
        width: max-content;
        animation: logoScroll 40s linear infinite;
    }
    .logo-marquee:hover .logo-track { animation-play-state: paused; }
    .logo-item {
        height: 60px;
        width: auto;
        object-fit: contain;
        flex: none;
        opacity: .8;
        transition: opacity .2s;
    }
    .logo-item:hover { opacity: 1; }
    @keyframes logoScroll {
        from { transform: translateX(0); }
        to { transform: translateX(-50%); }
    }
    .filter-bar-sticky {
        position: sticky;
        top: 76px;
        z-index: 30;
        align-items: center;
        margin-bottom: 20px;
        padding: 12px 16px;
        border: 1px solid rgba(0, 0, 0, .06);
        border-radius: 16px;
        background: rgba(255, 255, 255, .92);
        backdrop-filter: blur(10px);
        box-shadow: 0 10px 24px -12px rgba(2, 35, 33, .18);
    }
    .pdetail-overlay {
        position: fixed;
        inset: 0;
        z-index: 90;
        background: rgba(2, 44, 41, .58);
        backdrop-filter: blur(3px);
        display: flex;
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
        padding: 20px 16px;
    }
    .pdetail-modal {
        margin: auto;
        width: 100%;
        max-width: 520px;
        max-height: calc(100vh - 40px);
        background: #fff;
        border-radius: 22px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        box-shadow: 0 28px 70px rgba(2, 44, 41, .4);
    }
    .pdetail-media {
        position: relative;
        height: 210px;
        flex: none;
        background: linear-gradient(135deg, #08574f, #022321);
    }
    @media (max-width: 480px) {
        .pdetail-media { height: 160px; }
        .pdetail-body { padding: 16px 16px 18px; }
        .pdetail-title { font-size: 17px; }
        .pdetail-desc { font-size: 12.5px; }
    }
    .pdetail-media img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .pdetail-media-ph {
        width: 100%;
        height: 100%;
        display: grid;
        place-items: center;
        color: rgba(255, 255, 255, .35);
        font-size: 56px;
    }
    .pdetail-close {
        position: absolute;
        top: 12px;
        right: 12px;
        z-index: 2;
        width: 34px;
        height: 34px;
        border: none;
        border-radius: 10px;
        background: rgba(2, 35, 33, .45);
        color: #fff;
        cursor: pointer;
        font-size: 16px;
        display: grid;
        place-items: center;
    }
    .pdetail-close:hover { background: rgba(2, 35, 33, .7); }
    .pdetail-cat {
        position: absolute;
        top: 14px;
        left: 14px;
        z-index: 2;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .03em;
        color: #fff;
        background: #08A899;
        padding: 5px 12px;
        border-radius: 9999px;
    }
    .pdetail-cat.is-gold { background: #D4911E; }
    .pdetail-done {
        position: absolute;
        right: 56px;
        top: 15px;
        z-index: 2;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 12px;
        font-weight: 700;
        color: #fff;
        background: #10b981;
        padding: 5px 12px;
        border-radius: 9999px;
    }
    .pdetail-hot {
        position: absolute;
        right: 56px;
        top: 15px;
        z-index: 2;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 12px;
        font-weight: 700;
        color: #fff;
        background: #D4911E;
        padding: 5px 12px;
        border-radius: 9999px;
    }
    .pdetail-body {
        padding: 20px 22px 22px;
        overflow-y: auto;
    }
    .pdetail-title { font-size: 20px; font-weight: 800; color: #04211f; line-height: 1.3; }
    .pdetail-tags { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 10px; }
    .pdetail-tag {
        font-size: 11px;
        font-weight: 600;
        color: #086e66;
        background: #e7f4f2;
        padding: 3px 10px;
        border-radius: 9999px;
    }
    .pdetail-tag.is-gold { background: #f9edcd; color: #935516; }
    .pdetail-desc { font-size: 13.5px; line-height: 1.7; color: #4b5563; margin-top: 12px; }
    .pdetail-progress { margin-top: 16px; }
    .pdetail-progress-track {
        height: 10px;
        border-radius: 9999px;
        background: #e7f4f2;
        overflow: hidden;
    }
    .pdetail-progress-track div {
        height: 100%;
        border-radius: 9999px;
        background: linear-gradient(90deg, #08A899, #34d399);
    }
    .pdetail-progress-meta {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: 10px;
        margin-top: 7px;
        font-size: 12px;
    }
    .pdetail-pct { font-weight: 800; color: #086e66; font-size: 14px; }
    .pdetail-rem { color: var(--gray-500); font-weight: 600; }
    .pdetail-rem strong { color: #086e66; }
    .pdetail-stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 10px;
        margin-top: 14px;
    }
    .pdetail-stats div {
        background: #f4f8f7;
        border-radius: 12px;
        padding: 10px 12px;
        text-align: center;
    }
    .pdetail-stats span {
        display: block;
        font-size: 10px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: var(--gray-500);
        margin-bottom: 3px;
    }
    .pdetail-stats strong { font-size: 14px; font-weight: 800; color: #043d3a; }
    .pdetail-stats strong.is-gold { color: #935516; }
    .pdetail-trust {
        display: flex;
        flex-wrap: wrap;
        gap: 14px;
        margin-top: 14px;
        font-size: 11.5px;
        font-weight: 600;
        color: #086e66;
    }
    .pdetail-trust i { color: #08A899; margin-right: 4px; }
    .pdetail-cta {
        margin-top: 16px;
        padding: 14px 18px;
        font-size: 15px;
        border-radius: 14px;
    }
    .pdetail-more {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        margin-top: 14px;
        font-size: 13px;
        font-weight: 600;
        color: #086e66;
        text-align: center;
    }
    .pdetail-more:hover { color: #08A899; }
    .pdetail-more-arrow { transition: transform .2s; }
    .pdetail-more:hover .pdetail-more-arrow { transform: translateX(3px); }
    @media (max-width: 520px) {
        .pdetail-stats { grid-template-columns: repeat(2, 1fr); }
        .pdetail-stats div:last-child { grid-column: span 2; }
    }
</style>
@endpush

@section('content')

@php
    $bannerSlides = $banners->map(function ($b) {
        return [
            'image' => $b->image_url,
            'title' => $b->title,
            'url'   => $b->url,
        ];
    })->values();

    $programCards = $programs->map(function ($p) use ($waNumber, $waTemplate) {
        $collected = (float) ($p->total_collected ?? 0);
        $goal = (float) $p->goal_amount;
        $progress = $goal > 0 ? min(100, round(($collected / $goal) * 100, 1)) : 0;
        $isComplete = $goal > 0 && $collected >= $goal;
        $waMsg = str_replace('{program}', $p->name, $waTemplate ?: 'Assalamualaikum, saya ingin berdonasi untuk program {program}');
        return [
            'slug'        => $p->slug,
            'name'        => $p->name,
            'description' => $p->description,
            'image'       => $p->image_url,
            'category'    => $p->category ?? 'penggalangan',
            'tags'        => $p->campaignTags->pluck('name')->all(),
            'progress'    => $progress,
            'collected'   => 'Rp ' . number_format($collected, 0, ',', '.'),
            'goal'        => 'Rp ' . number_format($goal, 0, ',', '.'),
            'remaining'   => $isComplete ? null : 'Rp ' . number_format(max(0, $goal - $collected), 0, ',', '.'),
            'is_complete' => $isComplete,
            'url'         => route('public.program', $p->slug),
            'wa_url'      => 'https://wa.me/' . $waNumber . '?text=' . urlencode($waMsg),
            'wa_source'   => 'home',
            'wa_program'  => $p->id,
        ];
    })->values();
@endphp

<section class="relative overflow-hidden bg-gradient-to-br from-primary-700 via-primary-800 to-primary-950 text-white">
    <div class="pointer-events-none absolute -right-24 -top-24 h-96 w-96 rounded-full bg-primary-500/20 blur-3xl"></div>
    <div class="pointer-events-none absolute -bottom-32 -left-24 h-96 w-96 rounded-full bg-gold-500/10 blur-3xl"></div>
    <div class="container relative grid items-center gap-12 py-16 lg:grid-cols-2 lg:py-24">
        <div>
            <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-1.5 text-sm font-medium text-primary-100 ring-1 ring-white/15">
                <i class="fas fa-book-quran"></i> Wakaf Al Qur'an & Kemanusiaan
            </span>
            <h1 class="mt-6 text-4xl font-extrabold leading-tight md:text-5xl">
                Wakaf untuk Ummat,<br>Dari Anda untuk <em class="not-italic text-gold-300">Kebaikan</em>
            </h1>
            <p class="mt-5 max-w-lg text-base leading-relaxed text-primary-100/90">
                Wakaf, infak, dan sedekah untuk ummat — dihimpun dan disalurkan secara amanah oleh <strong class="font-semibold text-white">Badan Wakaf Al Qur'an (BWA)</strong>. Setiap rupiah tercatat resmi dan dapat Anda telusuri.
            </p>
            <div class="mt-8 flex flex-wrap gap-3">
                <a href="#program" class="btn btn-gold"><i class="fas fa-arrow-down"></i> Donasi Sekarang</a>
                <a href="https://wa.me/{{ $waNumber }}" target="_blank" rel="noopener" class="btn btn-light" data-wa-log data-wa-source="home"><i class="fab fa-whatsapp"></i> Hubungi Kami</a>
            </div>
        </div>
        <div data-reveal>
            <div data-vue-app="BannerSlider">
                <script type="application/json">@json(['slides' => $bannerSlides])</script>
            </div>
        </div>
    </div>
</section>

@if(trim(strip_tags($homeQuote)) !== '')
<section class="border-b border-black/5 bg-white py-12" data-reveal>
    <div class="container mx-auto max-w-3xl text-center">
        <i class="fas fa-quote-left mb-4 text-3xl text-gold-400"></i>
        <div class="quote-content text-xl font-light leading-relaxed text-primary-900 md:text-2xl">{!! $homeQuote !!}</div>
    </div>
</section>
@endif

@if(count($testimonials) > 0)
<section class="border-b border-black/5 bg-primary-50/40 py-16" data-reveal>
    <div class="container mx-auto max-w-3xl">
        <div id="testimonialSlider" class="relative" data-autoplay="6000">
            @foreach($testimonials as $i => $testimonial)
            <div class="testimonial-slide {{ $i === 0 ? 'active' : '' }}">
                <div class="flex flex-col items-center gap-6 sm:flex-row sm:items-center sm:gap-10">
                    @if($testimonial['photo_url'])
                    <img src="{{ $testimonial['photo_url'] }}" alt="{{ $testimonial['name'] }}" class="testimonial-photo" loading="lazy">
                    @else
                    <div class="testimonial-photo-placeholder">{{ mb_substr($testimonial['name'], 0, 1) }}</div>
                    @endif
                    <div class="text-center sm:text-left">
                        <p class="testimonial-text">&ldquo;{{ $testimonial['text'] }}&rdquo;</p>
                        <p class="testimonial-name mt-4">{{ $testimonial['name'] }}</p>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @if(count($testimonials) > 1)
        <div class="mt-8 flex justify-center gap-2.5" id="testimonialDots"></div>
        @endif
    </div>
</section>
@endif

@if(count($partnerLogos) > 0)
<section class="border-b border-black/5 bg-white py-10" data-reveal>
    <div class="container mx-auto max-w-6xl">
        <div class="logo-marquee">
            <div class="logo-track">
                @foreach($partnerLogos as $logo)
                <img src="{{ $logo }}" alt="Logo mitra" class="logo-item" loading="lazy">
                @endforeach
                @foreach($partnerLogos as $logo)
                <img src="{{ $logo }}" alt="Logo mitra" class="logo-item" loading="lazy" aria-hidden="true">
                @endforeach
            </div>
        </div>
    </div>
</section>
@endif

<main class="section" id="program">
    <div class="container">
        <div data-reveal>
            <div data-vue-app="ProgramExplorer">
                <script type="application/json">@json(['programs' => $programCards, 'tags' => $tags->pluck('name')->all(), 'sticky' => true])</script>
            </div>
        </div>
    </div>
</main>

<section class="section">
    <div class="container">
        <div class="rounded-3xl bg-gradient-to-br from-primary-700 to-primary-950 p-10 text-center text-white" data-reveal>
            <h2 class="text-2xl font-extrabold md:text-3xl">Wakaf untuk Ummat, dari Anda untuk Kebaikan</h2>
            <p class="mx-auto mt-3 max-w-xl text-sm text-primary-100/90">Gratis konsultasi. Tim BWA siap membantu Anda menyalurkan wakaf, infak, dan sedekah dengan amanah.</p>
            <div class="mt-7 flex flex-wrap justify-center gap-3">
                <a href="#program" class="btn btn-gold"><i class="fas fa-arrow-down"></i> Donasi Sekarang</a>
            </div>
        </div>
    </div>
</section>

@push('scripts')
<script>
(function () {
    var slider = document.getElementById('testimonialSlider');
    if (!slider) return;

    var slides = slider.querySelectorAll('.testimonial-slide');
    if (slides.length < 2) return;

    var dotsWrap = document.getElementById('testimonialDots');
    var idx = 0;
    var timer = null;
    var interval = parseInt(slider.getAttribute('data-autoplay'), 10) || 6000;
    var dots = [];

    for (var i = 0; i < slides.length; i++) {
        var dot = document.createElement('button');
        dot.type = 'button';
        dot.setAttribute('aria-label', 'Testimoni ' + (i + 1));
        (function (k) {
            dot.addEventListener('click', function () { go(k); restart(); });
        })(i);
        dotsWrap.appendChild(dot);
        dots.push(dot);
    }

    function go(n) {
        slides[idx].classList.remove('active');
        if (dots[idx]) dots[idx].classList.remove('active');
        idx = (n + slides.length) % slides.length;
        slides[idx].classList.add('active');
        if (dots[idx]) dots[idx].classList.add('active');
    }

    function next() { go(idx + 1); }

    function restart() {
        clearInterval(timer);
        timer = setInterval(next, interval);
    }

    if (dots[0]) dots[0].classList.add('active');
    timer = setInterval(next, interval);

    slider.addEventListener('mouseenter', function () { clearInterval(timer); });
    slider.addEventListener('mouseleave', restart);
})();
</script>
@endpush

@endsection
