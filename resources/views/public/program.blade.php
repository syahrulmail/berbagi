@extends('layouts.public')

@section('title', $program->name . ' · Berbagi.or.id')
@section('meta_description', mb_substr(strip_tags($program->description ?? ''), 0, 155))

@section('content')
<main class="detail-wrap">
    <div class="container">
        <nav style="margin-bottom: 20px; font-size: 13.5px; color: var(--muted);">
            <a href="{{ route('home') }}" style="color: var(--teal-700);"><i class="fas fa-arrow-left"></i> Semua Program</a>
            <span style="margin: 0 8px;">/</span><span>{{ $program->name }}</span>
        </nav>

        @php
            $progress = $program->goal_amount > 0 ? min(100, round(((float) $collected / (float) $program->goal_amount) * 100, 1)) : 0;
            $waMsg = str_replace('{program}', $program->name, $waTemplate ?: 'Assalamualaikum, saya ingin berdonasi untuk program {program}. Mohon info selanjutnya.');
        @endphp

        <div class="detail-grid">
            <div class="detail-media">
                @if($program->image)
                    <img src="{{ $program->image }}" alt="{{ $program->name }}">
                @else
                    <div style="width:100%;height:100%;display:grid;place-items:center;background:linear-gradient(135deg,var(--teal-100),var(--teal-50));color:var(--teal-400);font-size:70px;"><i class="fas fa-book-quran"></i></div>
                @endif
            </div>

            <div class="detail-content">
                <div class="detail-meta">
                    @if($program->category)
                        <span class="cat-badge {{ $program->category }}" style="display:inline-block;">{{ $program->category }}</span>
                    @endif
                    @foreach($program->campaignTags as $tag)
                        <span class="tag-chip">{{ $tag->name }}</span>
                    @endforeach
                </div>
                <h1>{{ $program->name }}</h1>
                <p class="lead">{{ $program->description }}</p>

                <div class="detail-progress-card">
                    <div class="progress-head">
                        <span>Terkumpul <strong style="color: var(--teal-700);">Rp {{ number_format($collected, 0, ',', '.') }}</strong></span>
                        <span>Target Rp {{ number_format($program->goal_amount, 0, ',', '.') }}</span>
                    </div>
                    <div class="progress-track" style="height: 12px; margin: 12px 0 8px;">
                        <div class="progress-fill" data-percent="{{ $progress }}" style="height:100%;"></div>
                    </div>
                    <div style="text-align:right; font-size: 13.5px; color: var(--muted);">{{ $progress }}% terkumpul</div>
                </div>

                <div class="detail-wa-box">
                    <p><i class="fas fa-circle-info" style="color: var(--teal-500);"></i> Untuk berdonasi atau bertanya, silakan hubungi tim BWA melalui WhatsApp.</p>
                    <a href="https://wa.me/{{ $waNumber }}?text={{ urlencode($waMsg) }}" target="_blank" rel="noopener" class="btn btn-wa"
                       data-wa-log data-wa-source="program" data-wa-program="{{ $program->id }}">
                        <i class="fab fa-whatsapp"></i> Donasi via WhatsApp
                    </a>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection
