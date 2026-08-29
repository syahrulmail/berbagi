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

    $heroStats = $achievements->filter(function ($a) {
        return $a->numericParts()['number'] !== null;
    })->take(4)->values()->map(function ($a) {
        $parts = $a->numericParts();
        return [
            'icon'     => $a->icon ?: 'fa-hand-holding-heart',
            'value'    => $a->value,
            'label'    => $a->label,
            'number'   => $parts['number'],
            'prefix'   => $parts['prefix'],
            'decimals' => $parts['decimals'],
            'suffix'   => $parts['suffix'],
        ];
    })->values();

    $defaultTagSlugs = \App\Models\CampaignTag::DEFAULT_TAG_SLUGS;

    $programCards = $programs->map(function ($p) use ($waNumber, $waTemplate, $defaultTagSlugs) {
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
            'tags'        => $p->campaignTags->map(function ($t) use ($defaultTagSlugs) {
                return [
                    'name'       => $t->name,
                    'color'      => $t->color,
                    'is_default' => in_array($t->slug, $defaultTagSlugs, true),
                ];
            })->values()->all(),
            'progress'    => $progress,
            'collected'   => 'Rp ' . number_format($collected, 0, ',', '.'),
            'goal'        => 'Rp ' . number_format($goal, 0, ',', '.'),
            'remaining'   => $isComplete ? null : 'Rp ' . number_format(max(0, $goal - $collected), 0, ',', '.'),
            'is_complete' => $isComplete,
            'url'         => route('public.program', $p->slug),
            'wa_url'      => 'https://wa.me/' . $waNumber . '?text=' . urlencode($waMsg),
            'wa_source'   => 'home',
            'wa_program'  => $p->id,
            'edit_url'    => auth()->check() && auth()->user()->isAdmin() ? route('programs.edit', $p) : null,
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
                <i class="fas fa-book-quran"></i> Wakaf Al Qur'an & Kemanusiaan
            </span>
            <h1 class="hero-enter hero-enter-2 mt-6 text-4xl font-extrabold leading-tight md:text-5xl">
                Wakaf untuk Ummat,<br>Dari Anda untuk <em class="hero-shimmer-text not-italic">Kebaikan</em>
            </h1>
            <p class="hero-enter hero-enter-3 mt-5 max-w-lg text-base leading-relaxed text-primary-100/90">
                Wakaf, infak, dan sedekah untuk ummat — dihimpun dan disalurkan secara amanah oleh <strong class="font-semibold text-white">Badan Wakaf Al Qur'an (BWA)</strong>. Setiap rupiah tercatat resmi dan dapat Anda telusuri.
            </p>
            <div class="hero-enter hero-enter-4 mt-8 flex flex-wrap gap-3">
                <a href="#program" class="btn btn-gold"><i class="fas fa-arrow-down"></i> Donasi Sekarang</a>
                <a href="https://wa.me/{{ $waNumber }}" target="_blank" rel="noopener" class="btn btn-light" data-wa-log data-wa-source="home"><i class="fab fa-whatsapp"></i> Hubungi Kami</a>
            </div>
            @if($heroStats->isNotEmpty())
            <div class="hero-enter hero-enter-5">
                <div data-vue-app="HeroStats">
                    <script type="application/json">@json(['stats' => $heroStats])</script>
                </div>
            </div>
            @endif
        </div>
        <div class="hero-enter hero-enter-3" data-reveal>
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
        <div class="rounded-3xl bg-gradient-to-br from-primary-700 to-primary-950 p-10 text-center text-white" data-reveal>
            <h2 class="text-2xl font-extrabold md:text-3xl">Wakaf untuk Ummat, dari Anda untuk Kebaikan</h2>
            <p class="mx-auto mt-3 max-w-xl text-sm text-primary-100/90">Gratis konsultasi. Tim BWA siap membantu Anda menyalurkan wakaf, infak, dan sedekah dengan amanah.</p>
            <div class="mt-7 flex flex-wrap justify-center gap-3">
                <a href="#program" class="btn btn-gold"><i class="fas fa-arrow-down"></i> Donasi Sekarang</a>
            </div>
        </div>
    </div>
</section>

@include('public.partials.testimonial-slider-script')

@endsection
