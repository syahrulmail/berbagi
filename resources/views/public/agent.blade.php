@extends('layouts.public')

@section('title', $agen->name . ' · CS Berbagi.or.id')
@section('meta_description', 'Hubungi ' . $agen->name . ' — mitra CS Badan Wakaf Al Qur\'an untuk program wakaf dan sedekah.')

@section('content')
@include('public.partials.funnel-styles')
@include('public.partials.hero-styles')
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
@endphp

<section class="relative overflow-hidden bg-gradient-to-br from-primary-700 via-primary-800 to-primary-950 py-16 text-center text-white">
    <div class="hero-mesh"></div>
    <div class="hero-blob-a"></div>
    <div class="hero-blob-b"></div>
    <div class="container relative">
        @if($agenPhoto)
        <img src="{{ $agenPhoto }}" alt="{{ $agen->name }}" class="agen-hero-photo mx-auto">
        @else
        <div class="agen-hero-avatar mx-auto">
            {{ strtoupper(mb_substr($agen->name, 0, 1)) }}
        </div>
        @endif
        <h1 class="hero-enter hero-enter-2 mt-5 text-3xl font-extrabold md:text-4xl">{{ $agen->name }}</h1>
        <p class="hero-enter hero-enter-3 mt-2 text-primary-100">CS Wakaf & Sedekah · Badan Wakaf Al Qur'an</p>
        <div class="hero-enter hero-enter-3 mt-5 flex flex-wrap justify-center gap-2">
            @if($agen->branch)<span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-4 py-1.5 text-sm ring-1 ring-white/15"><i class="fas fa-location-dot"></i> {{ $agen->branch->name }}</span>@endif
            <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-4 py-1.5 text-sm ring-1 ring-white/15"><i class="fas fa-at"></i> {{ $agen->username }}</span>
        </div>
        <div class="hero-enter hero-enter-4 mt-7 flex flex-wrap justify-center gap-3">
            <a href="https://wa.me/{{ $waNumber }}" target="_blank" rel="noopener" class="btn btn-wa"
               data-wa-log data-wa-source="agent" data-wa-agen="{{ $agen->id }}">
                <i class="fab fa-whatsapp"></i> Chat Saya
            </a>
        </div>
        <p class="hero-enter hero-enter-5 mx-auto mt-7 max-w-xl text-sm leading-relaxed text-primary-100/90">{{ $agenIntro }}</p>
    </div>
</section>

@include('public.partials.funnel-sections')

@include('public.partials.achievement-slider')

<main class="section">
    <div class="container">
        <div data-reveal>
            <div data-vue-app="ProgramExplorer">
                <script type="application/json">@json(['programs' => $programCards, 'tags' => $tags->pluck('name')->all(), 'sticky' => true])</script>
            </div>
        </div>
    </div>
</main>

@include('public.partials.testimonial-slider-script')
@endsection
