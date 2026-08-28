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
                Badan Wakaf Al Qur'an (BWA) hadir menghimpun dan menyalurkan wakaf, infak, dan sedekah untuk program Al-Qur'an serta kemanusiaan di seluruh Nusantara.
            </p>
            <div class="mt-8 flex flex-wrap gap-3">
                <a href="#program" class="btn btn-gold"><i class="fas fa-arrow-down"></i> Donasi Sekarang</a>
                <a href="https://wa.me/{{ $waNumber }}" target="_blank" rel="noopener" class="btn btn-light" data-wa-log data-wa-source="home"><i class="fab fa-whatsapp"></i> Hubungi Kami</a>
            </div>
            <div class="mt-12 hidden max-w-md grid-cols-3 gap-4 sm:grid">
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
            <div class="mt-6 max-w-md rounded-2xl bg-white/10 p-4 ring-1 ring-white/15">
                <div class="flex items-center justify-between text-xs text-primary-100">
                    <span>Progress nasional</span>
                    <span class="font-bold text-gold-300">{{ $globalProgress }}%</span>
                </div>
                <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-white/15">
                    <div class="h-full rounded-full bg-gradient-to-r from-gold-400 to-gold-500" style="width: {{ $globalProgress }}%"></div>
                </div>
                <div class="mt-2 flex items-center justify-between text-xs text-primary-100/80">
                    <span>Terkumpul <strong class="text-white">Rp {{ number_format($totalCollected, 0, ',', '.') }}</strong></span>
                    <span>Target Rp {{ number_format($globalTarget, 0, ',', '.') }}</span>
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

<section class="border-b border-black/5 bg-white" data-reveal>
    <div class="container grid gap-x-6 gap-y-6 py-8 sm:grid-cols-2 lg:grid-cols-4">
        <a href="{{ route('public.transparansi') }}" class="group flex items-center gap-3.5">
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-primary-100 text-primary-600 transition group-hover:bg-primary-600 group-hover:text-white"><i class="fas fa-scale-balanced"></i></span>
            <span>
                <span class="block font-bold text-primary-900">Legalitas Resmi</span>
                <span class="block text-xs text-gray-500">Terdaftar &amp; berizin</span>
            </span>
        </a>
        <a href="{{ route('public.transparansi') }}" class="group flex items-center gap-3.5">
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-gold-100 text-gold-600 transition group-hover:bg-gold-500 group-hover:text-white"><i class="fas fa-hand-holding-dollar"></i></span>
            <span>
                <span class="block font-bold text-primary-900">Penyaluran Transparan</span>
                <span class="block text-xs text-gray-500">Tercatat &amp; dapat ditelusuri</span>
            </span>
        </a>
        <a href="{{ route('public.transparansi') }}#laporan" class="group flex items-center gap-3.5">
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-emerald-100 text-emerald-600 transition group-hover:bg-emerald-500 group-hover:text-white"><i class="fas fa-file-invoice"></i></span>
            <span>
                <span class="block font-bold text-primary-900">Laporan Berkala</span>
                <span class="block text-xs text-gray-500">Transparansi penyaluran</span>
            </span>
        </a>
        <a href="{{ route('home') }}#program" class="group flex items-center gap-3.5">
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-primary-100 text-primary-600 transition group-hover:bg-primary-600 group-hover:text-white"><i class="fas fa-users"></i></span>
            <span>
                <span class="block font-bold text-primary-900">{{ $totalAgents }} Mitra Agen</span>
                <span class="block text-xs text-gray-500">Melayani di seluruh Nusantara</span>
            </span>
        </a>
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

@if(count($recentDonors))
<section class="section bg-primary-50/40">
    <div class="container">
        <div class="section-head" data-reveal>
            <div>
                <h2>Donatur Terbaru</h2>
                <p class="muted mt-1 text-sm">Sebagian dari mereka yang telah berdonasi (nama disamarkan).</p>
            </div>
        </div>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3" data-reveal>
            @foreach($recentDonors as $donor)
                <div class="flex items-center gap-3 rounded-2xl border border-black/5 bg-white px-4 py-3 shadow-card">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-primary-100 font-bold text-primary-600">{{ $donor['initial'] }}</span>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-primary-900">{{ $donor['name'] }}</p>
                        <p class="truncate text-xs text-gray-500">{{ $donor['program'] }} · {{ $donor['date'] }}</p>
                    </div>
                    <span class="ml-auto shrink-0 text-sm font-bold text-primary-700">{{ $donor['amount'] }}</span>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

<section class="section">
    <div class="container">
        <div class="rounded-3xl bg-gradient-to-br from-primary-700 to-primary-950 p-10 text-center text-white" data-reveal>
            <h2 class="text-2xl font-extrabold md:text-3xl">Wakaf untuk Ummat, dari Anda untuk Kebaikan</h2>
            <p class="mx-auto mt-3 max-w-xl text-sm text-primary-100/90">Gratis konsultasi. Tim BWA siap membantu Anda menyalurkan wakaf, infak, dan sedekah dengan amanah.</p>
            <div class="mt-7 flex flex-wrap justify-center gap-3">
                <a href="#program" class="btn btn-gold"><i class="fas fa-arrow-down"></i> Donasi Sekarang</a>
                <a href="{{ route('public.cara-donasi') }}" class="btn btn-light">Cara Berdonasi</a>
            </div>
        </div>
    </div>
</section>
@endsection
