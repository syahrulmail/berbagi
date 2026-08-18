@extends('layouts.public')

@section('title', $agen->name . ' · Agen Berbagi.or.id')
@section('meta_description', 'Hubungi ' . $agen->name . ' — mitra agen Badan Wakaf Al Qur\'an untuk program wakaf dan sedekah.')

@section('content')
@php
    $waNumber = preg_replace('/\D/', '', $agen->phone ?: '');
    $waNumber = $waNumber !== '' ? $waNumber : preg_replace('/\D/', '', $waFallback);

    $programCards = $programs->map(function ($p) use ($agen, $waNumber, $waTemplate) {
        $collected = (float) ($p->total_collected ?? 0);
        $goal = (float) $p->goal_amount;
        $progress = $goal > 0 ? min(100, round(($collected / $goal) * 100, 1)) : 0;
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
            'tags'        => $p->campaignTags->pluck('name')->all(),
            'progress'    => $progress,
            'collected'   => 'Rp ' . number_format($collected, 0, ',', '.'),
            'goal'        => 'Rp ' . number_format($goal, 0, ',', '.'),
            'url'         => route('public.agent-program', ['agentSlug' => $agen->slug, 'program' => $p->slug]),
            'wa_url'      => 'https://wa.me/' . $waNumber . '?text=' . urlencode($waMsg),
            'wa_source'   => 'agent',
            'wa_program'  => $p->id,
            'wa_agen'     => $agen->id,
        ];
    })->values();
@endphp

<section class="relative overflow-hidden bg-gradient-to-br from-primary-700 via-primary-800 to-primary-950 py-16 text-center text-white">
    <div class="pointer-events-none absolute -right-24 -top-24 h-96 w-96 rounded-full bg-primary-500/20 blur-3xl"></div>
    <div class="container relative">
        <div class="mx-auto grid h-20 w-20 place-items-center rounded-3xl bg-gradient-to-br from-primary-400 to-primary-600 text-3xl font-bold shadow-xl shadow-primary-900/40">
            {{ strtoupper(mb_substr($agen->name, 0, 1)) }}
        </div>
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
            <a href="{{ route('home') }}" class="btn btn-light"><i class="fas fa-house"></i> Beranda</a>
        </div>
        <p class="mx-auto mt-7 max-w-xl text-sm leading-relaxed text-primary-100/90">
            Assalamualaikum, saya siap membantu Anda menyalurkan wakaf, infak, dan sedekah melalui program-program BWA. Insya Allah amanah dan tepat sasaran.
        </p>
    </div>
</section>

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
                <script type="application/json">@json(['programs' => $programCards, 'tags' => []])</script>
            </div>
        </div>
    </div>
</main>
@endsection
