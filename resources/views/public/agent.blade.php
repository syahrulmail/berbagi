@extends('layouts.public')

@section('title', $agen->name . ' · Agen Berbagi.or.id')
@section('meta_description', 'Hubungi ' . $agen->name . ' — mitra agen Badan Wakaf Al Qur\'an untuk program wakaf dan sedekah.')

@section('content')
@include('public.partials.funnel-styles')
@push('styles')
<style>
    .agen-hero-photo {
        width: 112px;
        height: 112px;
        border-radius: 50%;
        object-fit: cover;
        flex: none;
        box-shadow: 0 0 0 4px rgba(255, 255, 255, .2), 0 18px 30px rgba(2, 35, 33, .35);
    }
    .agen-hero-avatar {
        width: 112px;
        height: 112px;
        border-radius: 50%;
        display: grid;
        place-items: center;
        background: linear-gradient(135deg, #3bb3ae, #08a899);
        color: #fff;
        font-size: 34px;
        font-weight: 700;
        box-shadow: 0 0 0 4px rgba(255, 255, 255, .2), 0 18px 30px rgba(2, 35, 33, .35);
    }
</style>
@endpush
@php
    $waNumber = preg_replace('/\D/', '', $agen->phone ?: '');
    $waNumber = $waNumber !== '' ? $waNumber : preg_replace('/\D/', '', $waFallback);

    $defaultTagSlugs = \App\Models\CampaignTag::DEFAULT_TAG_SLUGS;

    $programCards = $programs->map(function ($p) use ($agen, $waNumber, $waTemplate, $defaultTagSlugs) {
        $collected = (float) ($p->total_collected ?? 0);
        $goal = (float) $p->goal_amount;
        $progress = $goal > 0 ? min(100, round(($collected / $goal) * 100, 1)) : 0;
        $isComplete = $goal > 0 && $collected >= $goal;
        $waMsg = str_replace(
            ['{agen}', '{program}'],
            [$agen->name, $p->name],
            $waTemplate ?: 'Assalamualaikum {agen}, saya ingin berdonasi untuk program {program} melalui Anda.'
        );
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
            'url'         => route('public.agent-program', ['agentSlug' => $agen->slug, 'program' => $p->slug]),
            'wa_url'      => 'https://wa.me/' . $waNumber . '?text=' . urlencode($waMsg),
            'wa_source'   => 'agent',
            'wa_program'  => $p->id,
            'wa_agen'     => $agen->id,
            'edit_url'    => auth()->check() && auth()->user()->isAdmin() ? route('programs.edit', $p) : null,
        ];
    })->values();
@endphp

<section class="relative overflow-hidden bg-gradient-to-br from-primary-700 via-primary-800 to-primary-950 py-16 text-center text-white">
    <div class="pointer-events-none absolute -right-24 -top-24 h-96 w-96 rounded-full bg-primary-500/20 blur-3xl"></div>
    <div class="container relative">
        @if($agenPhoto)
        <img src="{{ $agenPhoto }}" alt="{{ $agen->name }}" class="agen-hero-photo mx-auto">
        @else
        <div class="agen-hero-avatar mx-auto">
            {{ strtoupper(mb_substr($agen->name, 0, 1)) }}
        </div>
        @endif
        <h1 class="mt-5 text-3xl font-extrabold md:text-4xl">{{ $agen->name }}</h1>
        <p class="mt-2 text-primary-100">Agen Wakaf & Sedekah · Badan Wakaf Al Qur'an</p>
        <div class="mt-5 flex flex-wrap justify-center gap-2">
            @if($agen->branch)<span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-4 py-1.5 text-sm ring-1 ring-white/15"><i class="fas fa-location-dot"></i> {{ $agen->branch->name }}</span>@endif
            <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-4 py-1.5 text-sm ring-1 ring-white/15"><i class="fas fa-at"></i> {{ $agen->username }}</span>
        </div>
        <div class="mt-7 flex flex-wrap justify-center gap-3">
            <a href="https://wa.me/{{ $waNumber }}" target="_blank" rel="noopener" class="btn btn-wa"
               data-wa-log data-wa-source="agent" data-wa-agen="{{ $agen->id }}">
                <i class="fab fa-whatsapp"></i> Chat Saya
            </a>
        </div>
        <p class="mx-auto mt-7 max-w-xl text-sm leading-relaxed text-primary-100/90">{{ $agenIntro }}</p>
    </div>
</section>

@include('public.partials.funnel-sections')

<main class="section">
    <div class="container">
        <div class="section-head" data-reveal>
            <div>
                <h2>Program yang Saya Bantu</h2>
                <p class="muted mt-1 text-sm">Program wakaf aktif yang dapat Anda dukung melalui {{ $agen->name }}.</p>
            </div>
        </div>

        <div data-reveal>
            <div data-vue-app="ProgramExplorer">
                <script type="application/json">@json(['programs' => $programCards, 'tags' => $tags->pluck('name')->all(), 'sticky' => true])</script>
            </div>
        </div>
    </div>
</main>

@include('public.partials.testimonial-slider-script')
@endsection
