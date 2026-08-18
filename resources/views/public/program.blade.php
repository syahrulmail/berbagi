@extends('layouts.public')

@section('title', $program->name . ' · Berbagi.or.id')
@section('meta_description', mb_substr(strip_tags($program->description ?? ''), 0, 155))

@section('content')

@php
    $progress = $program->goal_amount > 0 ? min(100, round(((float) $collected / (float) $program->goal_amount) * 100, 1)) : 0;
    $waMsg = str_replace('{program}', $program->name, $waTemplate ?: 'Assalamualaikum, saya ingin berdonasi untuk program {program}. Mohon info selanjutnya.');
@endphp

<main class="section">
    <div class="container">
        <nav class="mb-8 flex items-center gap-2 text-sm text-gray-500">
            <a href="{{ route('home') }}" class="font-medium text-primary-600 transition hover:text-primary-700"><i class="fas fa-arrow-left"></i> Semua Program</a>
            <span>/</span>
            <span class="truncate">{{ $program->name }}</span>
        </nav>

        <div class="grid gap-8 lg:grid-cols-2">
            <div class="overflow-hidden rounded-3xl border border-black/5 shadow-card" data-reveal>
                @if($program->image)
                    <img src="{{ $program->image_url }}" alt="{{ $program->name }}" class="aspect-[4/3] h-full w-full object-cover">
                @else
                    <div class="grid aspect-[4/3] h-full w-full place-items-center bg-gradient-to-br from-primary-100 to-primary-50 text-primary-400">
                        <i class="fas fa-book-quran" style="font-size:72px;"></i>
                    </div>
                @endif
            </div>

            <div data-reveal>
                <div class="mb-4 flex flex-wrap items-center gap-2">
                    @if($program->category)
                        <span class="cat-badge {{ $program->category }}">{{ $program->category }}</span>
                    @endif
                    @foreach($program->campaignTags as $tag)
                        <span class="tag-chip">{{ $tag->name }}</span>
                    @endforeach
                </div>
                <h1 class="text-3xl font-extrabold text-primary-900">{{ $program->name }}</h1>
                <p class="lead mt-4">{{ $program->description }}</p>

                <div class="mt-8 rounded-2xl border border-black/5 bg-primary-50/50 p-6">
                    <div class="mb-2 flex items-center justify-between text-sm">
                        <span class="text-gray-500">Terkumpul <strong class="text-primary-700">Rp {{ number_format($collected, 0, ',', '.') }}</strong></span>
                        <span class="text-gray-500">Target Rp {{ number_format($program->goal_amount, 0, ',', '.') }}</span>
                    </div>
                    <div class="progress-track" style="height:12px;">
                        <div class="progress-fill" data-percent="{{ $progress }}" style="width:0;height:100%;"></div>
                    </div>
                    <div class="mt-2 text-right text-sm font-semibold text-primary-700">{{ $progress }}% terkumpul</div>
                </div>

                <div class="mt-6 flex items-center gap-4 rounded-2xl border border-black/5 bg-white p-5 shadow-card">
                    <div class="grid h-12 w-12 flex-shrink-0 place-items-center rounded-xl bg-primary-100 text-primary-600">
                        <i class="fab fa-whatsapp text-xl"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm text-gray-600">Untuk berdonasi atau bertanya, silakan hubungi tim BWA melalui WhatsApp.</p>
                    </div>
                    <a href="https://wa.me/{{ $waNumber }}?text={{ urlencode($waMsg) }}" target="_blank" rel="noopener" class="btn btn-wa"
                       data-wa-log data-wa-source="program" data-wa-program="{{ $program->id }}">
                        <i class="fab fa-whatsapp"></i> Donasi
                    </a>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection
