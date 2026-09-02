@extends('layouts.public')

@section('title', 'Berbagi.or.id · Badan Wakaf Al Qur\'an')

@include('public.partials.funnel-styles')
@include('public.partials.hero-styles')

@section('content')

@php
    $bannerSlides = $banners->map(function ($b) {
        return [
            'image' => $b->image_url,
            'title' => $b->title,
            'url'   => $b->url,
        ];
    })->values();
@endphp

<section class="relative overflow-hidden bg-gradient-to-br from-primary-700 via-primary-800 to-primary-950 text-white">
    <div class="hero-mesh"></div>
    <div class="hero-blob-a"></div>
    <div class="hero-blob-b"></div>
    <div class="hero-blob-c"></div>
    <div class="container relative grid items-center gap-12 py-16 lg:grid-cols-2 lg:py-24">
        <div>
            <span class="hero-enter hero-enter-1 inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-1.5 text-sm font-medium text-primary-100 ring-1 ring-white/15">
                <i class="fas fa-book-quran"></i> Wakaf, Infaq & Sedekah untuk Ummat
            </span>
            <h1 class="hero-enter hero-enter-2 mt-6 text-4xl font-extrabold leading-tight md:text-5xl">
                Berbagi kebaikan,<br>untuk <em class="hero-shimmer-text not-italic">Ummat Sejahtera</em>
            </h1>
            <p class="hero-enter hero-enter-3 mt-5 max-w-lg text-base leading-relaxed text-primary-100/90">
                Wakaf, infak, dan sedekah untuk ummat — dihimpun dan disalurkan secara amanah oleh <strong class="font-semibold text-white">Badan Wakaf Al Qur'an (BWA)</strong>. Buah dari taqwa untuk manfaat terbaik
            </p>
            <div class="hero-enter hero-enter-4 mt-8 flex flex-wrap gap-3">
                <a href="#program" class="btn btn-gold"><i class="fas fa-arrow-down"></i> Lihat Program</a>
                <a href="https://wa.me/{{ $waNumber }}" target="_blank" rel="noopener" class="btn btn-light" data-wa-log data-wa-source="home"><i class="fab fa-whatsapp"></i> Tanya cara berbagi</a>
            </div>
        </div>
        <div class="hero-enter hero-enter-1" data-reveal>
            <div data-vue-app="BannerSlider">
                <script type="application/json">@json(['slides' => $bannerSlides])</script>
            </div>
        </div>
    </div>
</section>

@include('public.partials.funnel-sections')

@include('public.partials.achievement-slider')

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
        <div class="cta-box rounded-3xl bg-gradient-to-br from-primary-700 to-primary-950 text-center text-white" data-reveal>
            <h2 class="text-2xl font-extrabold md:text-3xl">Wujudkan buah taqwamu dengan amal ibadah maal terbaik</h2>
            <blockquote class="cta-quote">"Sebaik-baik manusia adalah yang paling bermanfaat bagi manusia lainnya."</blockquote>
            <p class="mt-2 text-sm font-bold text-gold-300">HR. Ahmad &amp; Thabrani</p>
            <div class="mt-7 flex flex-wrap justify-center gap-3">
                <a href="https://wa.me/{{ $waNumber }}" target="_blank" rel="noopener" class="btn btn-gold" data-wa-log data-wa-source="home"><i class="fab fa-whatsapp"></i> Mulai kebaikan sekarang</a>
            </div>
        </div>
    </div>
</section>

@include('public.partials.testimonial-slider-script')

@endsection
