@extends('layouts.public')

@section('title', 'Berbagi.or.id · Badan Wakaf Al Qur\'an')

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
                Badan Wakaf Al Qur'an (BWA) hadir menghimpun dan menyalurkan wakaf, infak, dan sedekah untuk program Al-Qur'an serta kemanusiaan di seluruh Nusantara.
            </p>
            <div class="mt-8 flex flex-wrap gap-3">
                <a href="#program" class="btn btn-gold"><i class="fas fa-arrow-down"></i> Lihat Program</a>
                <a href="https://wa.me/{{ $waNumber }}" target="_blank" rel="noopener" class="btn btn-light"><i class="fab fa-whatsapp"></i> Hubungi Kami</a>
            </div>
            <div class="mt-12 grid max-w-md grid-cols-3 gap-4">
                <div>
                    <div class="text-2xl font-extrabold md:text-3xl" data-vue-app="CountUp"><script type="application/json">{"value": {{ $programs->count() }}, "suffix": ""}</script></div>
                    <span class="mt-1 block text-xs text-primary-200">Program Aktif</span>
                </div>
                <div>
                    <div class="text-2xl font-extrabold md:text-3xl" data-vue-app="CountUp"><script type="application/json">{"value": {{ (int) $totalCollected }}, "prefix": "Rp "}</script></div>
                    <span class="mt-1 block text-xs text-primary-200">Total Terkumpul</span>
                </div>
                <div>
                    <div class="text-2xl font-extrabold md:text-3xl" data-vue-app="CountUp"><script type="application/json">{"value": {{ $totalAgents }}}</script></div>
                    <span class="mt-1 block text-xs text-primary-200">Mitra Agen</span>
                </div>
            </div>
        </div>
        <div data-reveal>
            <div data-vue-app="BannerSlider">
                <script type="application/json">@json(['slides' => $bannerSlides])</script>
            </div>
        </div>
    </div>
</section>

<main class="section" id="program">
    <div class="container">
        <div class="section-head" data-reveal>
            <div>
                <h2>Program Aktif</h2>
                <p class="muted mt-1 text-sm">Pilih program wakaf yang ingin Anda dukung.</p>
            </div>
        </div>

        <div data-reveal>
            <div data-vue-app="ProgramExplorer">
                <script type="application/json">@json(['programs' => $programCards, 'tags' => $tags->pluck('name')->all()])</script>
            </div>
        </div>
    </div>
</main>
@endsection
