@extends('layouts.public')

@section('title', $agen->name . ' · Agen Berbagi.or.id')
@section('meta_description', 'Hubungi ' . $agen->name . ' — mitra agen Badan Wakaf Al Qur\'an untuk program wakaf dan sedekah.')

@section('content')
@php
    $waNumber = preg_replace('/\D/', '', $agen->phone ?: '');
    $waNumber = $waNumber !== '' ? $waNumber : preg_replace('/\D/', '', $waFallback);
@endphp

<section class="agent-hero">
    <div class="container">
        <div class="agent-avatar">{{ strtoupper(mb_substr($agen->name, 0, 1)) }}</div>
        <h1>{{ $agen->name }}</h1>
        <p class="agent-role">Agen Wakaf & Sedekah · Badan Wakaf Al Qur'an</p>
        <div class="agent-badges">
            @if($agen->branch)<span class="agent-badge"><i class="fas fa-location-dot"></i> {{ $agen->branch->name }}</span>@endif
            <span class="agent-badge"><i class="fas fa-at"></i> {{ $agen->username }}</span>
        </div>
        <div class="agent-cta">
            <a href="https://wa.me/{{ $waNumber }}" target="_blank" rel="noopener" class="btn btn-wa"
               data-wa-log data-wa-source="agent" data-wa-agen="{{ $agen->id }}">
                <i class="fab fa-whatsapp"></i> Chat Saya
            </a>
            <a href="{{ route('home') }}" class="btn btn-light"><i class="fas fa-house"></i> Beranda</a>
        </div>
        <p class="agent-note">Assalamualaikum, saya siap membantu Anda menyalurkan wakaf, infak, dan sedekah melalui program-program BWA. Insya Allah amanah dan tepat sasaran.</p>
    </div>
</section>

<main class="section">
    <div class="container">
        <div class="section-head">
            <div>
                <h2>Program yang Saya Bantu</h2>
                <p class="muted">Program wakaf aktif yang dapat Anda dukung melalui {{ $agen->name }}.</p>
            </div>
        </div>

        <div class="program-grid">
            @forelse($programs as $program)
                @php
                    $collected = (float) ($program->total_collected ?? 0);
                    $goal = (float) $program->goal_amount;
                    $progress = $goal > 0 ? min(100, round(($collected / $goal) * 100, 1)) : 0;
                    $cat = $program->category ?? 'penggalangan';
                    $waMsg = str_replace(
                        ['{agen}', '{program}'],
                        [$agen->name, $program->name],
                        $waTemplate ?: 'Assalamualaikum {agen}, saya ingin berdonasi untuk program {program} melalui Anda.'
                    );
                @endphp
                <article class="program-card">
                    <a href="{{ route('public.program', $program->slug) }}" class="pc-media">
                        @if($program->image)
                            <img src="{{ $program->image }}" alt="{{ $program->name }}" loading="lazy">
                        @else
                            <div style="width:100%;height:100%;display:grid;place-items:center;background:linear-gradient(135deg,var(--teal-100),var(--teal-50));color:var(--teal-400);font-size:40px;"><i class="fas fa-book-quran"></i></div>
                        @endif
                        <span class="cat-badge {{ $cat }}">{{ $cat }}</span>
                    </a>
                    <div class="pc-body">
                        <div class="pc-tags">
                            @foreach($program->campaignTags as $tag)
                                <span class="tag-chip {{ $loop->first ? 'gold' : '' }}">{{ $tag->name }}</span>
                            @endforeach
                        </div>
                        <h3 class="pc-title"><a href="{{ route('public.program', $program->slug) }}">{{ $program->name }}</a></h3>
                        <p class="pc-desc">{{ $program->description }}</p>
                        <div class="progress">
                            <div class="progress-head">
                                <span>Terkumpul <strong>Rp {{ number_format($collected, 0, ',', '.') }}</strong></span>
                                <span>Target Rp {{ number_format($goal, 0, ',', '.') }}</span>
                            </div>
                            <div class="progress-track">
                                <div class="progress-fill" data-percent="{{ $progress }}"></div>
                            </div>
                        </div>
                        <div class="pc-foot">
                            <a href="{{ route('public.program', $program->slug) }}" class="btn btn-outline btn-sm">Detail</a>
                            <a href="https://wa.me/{{ $waNumber }}?text={{ urlencode($waMsg) }}" target="_blank" rel="noopener" class="btn btn-ghost-wa" aria-label="Donasi via WhatsApp"
                               data-wa-log data-wa-source="agent" data-wa-agen="{{ $agen->id }}" data-wa-program="{{ $program->id }}">
                                <i class="fab fa-whatsapp"></i>
                            </a>
                        </div>
                    </div>
                </article>
            @empty
                <div class="empty-state" style="grid-column:1/-1;">
                    <i class="fas fa-folder-open"></i>
                    <p>Belum ada program wakaf yang aktif.</p>
                </div>
            @endforelse
        </div>
    </div>
</main>
@endsection
