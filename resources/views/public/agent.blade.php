@extends('layouts.public')

@php
    $shareUrl = preg_replace('#^http://#i', 'https://', route('public.agent', $agen->slug));
    $ogDesc = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $agenIntro)));
    $ogImage = $agenPhoto !== '' ? preg_replace('#^http://#i', 'https://', $agenPhoto) : '';
    $shareMessage = ($ogDesc !== '' ? $ogDesc . "\n\n" : '') . $shareUrl;
    $shareText = urlencode($shareMessage);
    $shareTitle = urlencode($ogDesc !== '' ? $ogDesc : $agen->name);
    $waNumber = preg_replace('/\D/', '', $agen->phone ?: '');
    $waNumber = $waNumber !== '' ? $waNumber : preg_replace('/\D/', '', $waFallback);
    $agentWaMsg = str_replace(
        ['{agen}', '{program}'],
        [$agen->name, 'wakaf & sedekah BWA'],
        $waTemplate ?: 'Assalamualaikum {agen}, saya ingin berdonasi melalui Anda.'
    );
    $agentBarWaUrl = 'https://wa.me/' . $waNumber . '?text=' . urlencode($agentWaMsg);
@endphp

@section('title', $agen->name . ' · CS Berbagi.or.id')
@section('meta_description', $ogDesc)
@section('og_title', $agen->name . ' · CS Berbagi.or.id')
@section('og_description', $ogDesc)
@section('og_url', $shareUrl)
@section('og_image', $ogImage)
@section('donasiBarTitle', $agen->name)
@section('donasiBarSub', 'CS Badan Wakaf Al Qur\'an siap membantu Anda')
@section('donasiBarUrl', $agentBarWaUrl)
@section('donasiBarSource', 'agent')

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
    .agen-share-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 38px;
        height: 38px;
        border-radius: 9999px;
        border: 1px solid rgba(255, 255, 255, .28);
        color: #fff;
        font-size: 15px;
        transition: all 0.15s ease;
    }
    .agen-share-icon:hover {
        background: #fff;
        border-color: #fff;
        color: #086e66;
    }
</style>
@endpush

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
                <i class="fab fa-whatsapp"></i> Chat {{ $agen->name }}
            </a>
            <a href="https://api.whatsapp.com/send?text={{ $shareText }}" target="_blank" rel="noopener" class="btn btn-light">
                <i class="fas fa-share-nodes"></i> Share {{ $agen->name }}
            </a>
        </div>
        <div class="hero-enter hero-enter-4 mt-4 flex items-center justify-center gap-3">
            <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($shareUrl) }}" target="_blank" rel="noopener" class="agen-share-icon" aria-label="Bagikan ke Facebook" title="Bagikan ke Facebook"><i class="fab fa-facebook-f"></i></a>
            <a href="https://twitter.com/intent/tweet?url={{ urlencode($shareUrl) }}&text={{ $shareTitle }}" target="_blank" rel="noopener" class="agen-share-icon" aria-label="Bagikan ke X" title="Bagikan ke X"><i class="fab fa-x-twitter"></i></a>
            <a href="https://t.me/share/url?url={{ urlencode($shareUrl) }}&text={{ $shareTitle }}" target="_blank" rel="noopener" class="agen-share-icon" aria-label="Bagikan ke Telegram" title="Bagikan ke Telegram"><i class="fab fa-telegram"></i></a>
            <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode($shareUrl) }}" target="_blank" rel="noopener" class="agen-share-icon" aria-label="Bagikan ke LinkedIn" title="Bagikan ke LinkedIn"><i class="fab fa-linkedin-in"></i></a>
            <a href="{{ $shareUrl }}" class="agen-share-icon" data-share-copy="Instagram" aria-label="Bagikan ke Instagram" title="Bagikan ke Instagram"><i class="fab fa-instagram"></i></a>
            <a href="{{ $shareUrl }}" class="agen-share-icon" data-share-copy="TikTok" aria-label="Bagikan ke TikTok" title="Bagikan ke TikTok"><i class="fab fa-tiktok"></i></a>
            <a href="{{ $shareUrl }}" class="agen-share-icon" data-share-copy="Threads" aria-label="Bagikan ke Threads" title="Bagikan ke Threads"><i class="fab fa-threads"></i></a>
            <a href="{{ $shareUrl }}" class="agen-share-icon" data-share-copy="Discord" aria-label="Bagikan ke Discord" title="Bagikan ke Discord"><i class="fab fa-discord"></i></a>
        </div>
        <p class="hero-enter hero-enter-5 mx-auto mt-7 max-w-xl text-sm leading-relaxed text-primary-100/90">{{ $agenIntro }}</p>
    </div>
</section>

@include('public.partials.funnel-sections')

@include('public.partials.achievement-slider')

@include('public.partials.instagram-posts')

<main class="section">
    <div class="container">
        <div data-vue-app="ProgramExplorer">
            <script type="application/json">@json(['programs' => $programCards, 'tags' => $tags->pluck('name')->all(), 'sticky' => true])</script>
        </div>
    </div>
</main>

@include('public.partials.testimonial-slider-script')
@endsection

@push('scripts')
<script>
(function () {
    'use strict';
    var shareUrl = @json($shareUrl);
    function copyUrl(done) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(shareUrl).then(done, done);
        } else {
            var ta = document.createElement('textarea');
            ta.value = shareUrl;
            ta.style.position = 'fixed';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.select();
            try { document.execCommand('copy'); } catch (e) {}
            document.body.removeChild(ta);
            done();
        }
    }
    document.querySelectorAll('[data-share-copy]').forEach(function (icon) {
        icon.addEventListener('click', function (e) {
            e.preventDefault();
            var platform = icon.getAttribute('data-share-copy');
            copyUrl(function () {
                if (window.BerbagiToast) window.BerbagiToast('Link disalin. Tempel di ' + platform + '.');
            });
        });
    });
})();
</script>
@endpush
