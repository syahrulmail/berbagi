@extends('layouts.public')

@section('title', $program->name . ' · Berbagi.or.id')
@section('meta_description', mb_substr(strip_tags($program->description ?? ''), 0, 155))

@push('styles')
<style>
    .share-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 38px;
        height: 38px;
        border-radius: 9999px;
        border: 1px solid #d2e2e0;
        color: #086e66;
        font-size: 15px;
        transition: all 0.15s ease;
    }
    .share-icon:hover {
        background: #086e66;
        border-color: #086e66;
        color: #fff;
    }
</style>
@endpush

@php
    $progress = $program->goal_amount > 0 ? min(100, round(((float) $collected / (float) $program->goal_amount) * 100, 1)) : 0;
    $isComplete = $program->goal_amount > 0 && (float) $collected >= (float) $program->goal_amount;
    $remaining = $isComplete ? null : max(0, (float) $program->goal_amount - (float) $collected);

    if ($agen) {
        $waMsg = str_replace(
            ['{agen}', '{program}'],
            [$agen->name, $program->name],
            $waTemplate ?: 'Assalamualaikum {agen}, saya ingin berdonasi untuk program {program} melalui Anda.'
        );
        $waSource = 'agent';
    } else {
        $waMsg = str_replace('{program}', $program->name, $waTemplate ?: 'Assalamualaikum, saya ingin berdonasi untuk program {program}. Mohon info selanjutnya.');
        $waSource = 'program';
    }

    $waUrl = 'https://wa.me/' . $waNumber . '?text=' . urlencode($waMsg);
    $shareUrl = route('public.program', $program->slug);
    $shareTitle = urlencode('Bantu wakaf & sedekah: ' . $program->name);
    $shareText = urlencode('Bantu wakaf & sedekah: ' . $program->name . ' — ' . $shareUrl);
    $ctaHelpName = $agen ? $agen->name : 'Tim BWA';
@endphp

@section('donasiBarTitle', $program->name)
@section('donasiBarSub', $isComplete ? 'Target tercapai · Terima kasih' : 'Progress ' . $progress . '% · ' . ($remaining !== null ? 'Dibutuhkan Rp ' . number_format($remaining, 0, ',', '.') : 'Target tercapai'))
@section('donasiBarUrl', $waUrl)
@section('donasiBarSource', $waSource)
@section('donasiBarProgram', $program->id)

@section('content')

<main class="section">
    <div class="container">
        <nav class="mb-8 flex flex-wrap items-center gap-2 text-sm text-gray-500">
            <a href="{{ route('home') }}" class="font-medium text-primary-600 transition hover:text-primary-700"><i class="fas fa-arrow-left"></i> Beranda</a>
            <span>/</span>
            @if($agen)
                <a href="{{ route('public.agent', $agen->slug) }}" class="font-medium text-primary-600 transition hover:text-primary-700">{{ $agen->name }}</a>
                <span>/</span>
            @endif
            <span class="truncate">{{ $program->name }}</span>
        </nav>

        @if($agen)
            <div class="mb-8 flex flex-wrap items-center gap-3 rounded-2xl border border-primary-100 bg-primary-50/60 p-4" data-reveal>
                @if(!empty($agenPhoto))
                <img src="{{ $agenPhoto }}" alt="{{ $agen->name }}" class="h-12 w-12 flex-shrink-0 rounded-xl object-cover">
                @else
                <div class="grid h-12 w-12 flex-shrink-0 place-items-center rounded-xl bg-gradient-to-br from-primary-500 to-primary-700 text-lg font-bold text-white">{{ strtoupper(mb_substr($agen->name, 0, 1)) }}</div>
                @endif
                <div class="flex-1">
                    <p class="text-sm text-gray-500">CS Badan Wakaf Al Quran</p>
                    <p class="font-semibold text-primary-900">{{ $agen->name }}</p>
                </div>
                <a href="{{ route('public.agent', $agen->slug) }}" class="btn btn-outline btn-sm">Lihat Program Lainnya</a>
            </div>
        @endif

        <div class="grid items-start gap-8 lg:grid-cols-2">
            <div class="min-w-0">
                <div class="relative overflow-hidden rounded-3xl border border-black/5 shadow-card" data-reveal>
                    @if($program->image)
                        <img src="{{ $program->image_url }}" alt="{{ $program->name }}" class="aspect-[4/3] w-full object-cover">
                    @else
                        <div class="grid aspect-[4/3] w-full place-items-center bg-gradient-to-br from-primary-100 to-primary-50 text-primary-400">
                            <i class="fas fa-book-quran" style="font-size:72px;"></i>
                        </div>
                    @endif
                    @if($program->category)
                        <span class="cat-badge {{ $program->category }} absolute left-4 top-4 !text-[12px] !px-3 !py-1.5">{{ $program->category }}</span>
                    @endif
                </div>

                <div class="mt-6 lg:hidden" data-reveal>
                    <div class="rounded-2xl border border-black/5 bg-primary-50/50 p-5">
                        <div class="mb-2 flex items-center justify-between text-sm">
                            <span class="text-gray-500">Terkumpul <strong class="text-primary-700">Rp {{ number_format($collected, 0, ',', '.') }}</strong></span>
                            <span class="text-gray-500">Target Rp {{ number_format($program->goal_amount, 0, ',', '.') }}</span>
                        </div>
                        <div class="progress-track" style="height:12px;">
                            <div class="progress-fill" data-percent="{{ $progress }}" style="width:0;height:100%;"></div>
                        </div>
                        <div class="mt-1.5 flex items-center justify-between text-xs">
                            <span class="text-gray-500">
                                @if($isComplete)
                                    <span class="font-semibold text-emerald-600"><i class="fas fa-check-circle"></i> Target tercapai</span>
                                @else
                                    Dibutuhkan <strong class="text-primary-700">Rp {{ number_format($remaining, 0, ',', '.') }}</strong>
                                @endif
                            </span>
                            <span class="font-semibold text-primary-700">{{ $progress }}%</span>
                        </div>
                        <a href="{{ $waUrl }}" target="_blank" rel="noopener" class="btn btn-wa mt-4 w-full"
                           data-wa-log data-wa-source="{{ $waSource }}" data-wa-program="{{ $program->id }}" @if($agen) data-wa-agen="{{ $agen->id }}" @endif>
                            <i class="fab fa-whatsapp"></i> Berbagi sekarang
                        </a>
                    </div>
                </div>

                <div class="mt-8" data-reveal>
                    <div class="mb-3 flex flex-wrap items-center gap-2">
                        @if($program->category)
                            <span class="cat-badge {{ $program->category }} hidden lg:inline-block">{{ $program->category }}</span>
                        @endif
                        @foreach($program->campaignTags as $tag)
                            <span class="tag-chip">{{ $tag->name }}</span>
                        @endforeach
                    </div>
                    <h1 class="text-3xl font-extrabold text-primary-900 md:text-4xl">{{ $program->name }}</h1>
                    <p class="lead mt-4">{{ $program->description }}</p>
                </div>

                <section class="mt-12" data-reveal>
                    <h2 class="text-xl font-bold text-primary-900">Tentang Program</h2>
                    <div class="mt-4 space-y-4 leading-relaxed text-gray-600">
                        <p>{{ $program->description }}</p>
                        <p>Seluruh dana yang terhimpun dicatat resmi dan disalurkan melalui jaringan agen serta mitra Badan Wakaf Al Qur'an (BWA).</p>
                    </div>
                </section>

                @if(count($relatedCards))
                    <section class="mt-14" data-reveal>
                        <div class="section-head">
                            <div>
                                <h2>Program Serupa</h2>
                                <p class="muted mt-1 text-sm">Mungkin Anda juga tertarik mendukung program berikut.</p>
                            </div>
                        </div>
                        <div class="grid gap-5 sm:grid-cols-2">
                            @foreach($relatedCards as $p)
                                @include('public.partials.program-card', ['p' => $p])
                            @endforeach
                        </div>
                    </section>
                @endif

                <section class="mt-14 rounded-3xl bg-gradient-to-br from-primary-700 to-primary-950 p-8 text-center text-white md:p-10" data-reveal>
                    <h2 class="text-2xl font-extrabold md:text-3xl">Wujudkan kebaikan, mulai dari {{ $program->name }}</h2>
                    <p class="mx-auto mt-3 max-w-xl text-sm text-primary-100/90">Setiap rupiah sangat berarti. {{ $ctaHelpName }} siap membantu sepenuh hati.</p>
                    <a href="{{ $waUrl }}" target="_blank" rel="noopener" class="btn btn-wa mt-7 !px-8 !py-3.5 !text-base"
                       data-wa-log data-wa-source="{{ $waSource }}" data-wa-program="{{ $program->id }}" @if($agen) data-wa-agen="{{ $agen->id }}" @endif>
                        <i class="fab fa-whatsapp"></i> Berbagi Sekarang
                    </a>
                </section>
            </div>

            <aside class="hidden lg:block" data-reveal>
                <div class="rounded-3xl border border-black/5 bg-white p-6 shadow-card lg:sticky lg:top-24">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-bolt text-gold-500"></i>
                        <h2 class="text-sm font-bold uppercase tracking-wide text-primary-900">Donasi Cepat</h2>
                    </div>

                    <div class="mt-5">
                        <div class="mb-1.5 flex items-baseline justify-between gap-2 text-sm">
                            <span class="text-gray-500">Terkumpul</span>
                            <strong class="text-primary-700">Rp {{ number_format($collected, 0, ',', '.') }}</strong>
                        </div>
                        <div class="progress-track" style="height:12px;">
                            <div class="progress-fill" data-percent="{{ $progress }}" style="width:0;height:100%;"></div>
                        </div>
                        <div class="mt-1.5 flex items-center justify-between gap-2 text-xs text-gray-500">
                            <span>Target Rp {{ number_format($program->goal_amount, 0, ',', '.') }}</span>
                            <span class="font-semibold text-primary-700">{{ $progress }}%</span>
                        </div>
                        <div class="mt-3 rounded-xl bg-primary-50 px-4 py-3 text-xs text-gray-600">
                            @if($isComplete)
                                <span class="font-semibold text-emerald-600"><i class="fas fa-check-circle"></i> Target program tercapai. Terima kasih atas dukungan Anda.</span>
                            @else
                                Dibutuhkan <strong class="text-primary-700">Rp {{ number_format($remaining, 0, ',', '.') }}</strong> untuk mencapai target.
                            @endif
                        </div>
                    </div>

                    <a href="{{ $waUrl }}" target="_blank" rel="noopener" class="btn btn-wa mt-5 w-full !py-3.5"
                       data-wa-log data-wa-source="{{ $waSource }}" data-wa-program="{{ $program->id }}" @if($agen) data-wa-agen="{{ $agen->id }}" @endif>
                        <i class="fab fa-whatsapp"></i> Berbagi sekarang
                    </a>
                    <p class="mt-2 text-center text-xs text-gray-400">Mari berbagi, CS BWA siap melayani sepenuh hati</p>

                    <div class="mt-5 flex gap-2">
                        <a href="https://api.whatsapp.com/send?text={{ $shareText }}" target="_blank" rel="noopener" class="btn btn-outline btn-sm flex-1"><i class="fab fa-whatsapp"></i> Bagikan</a>
                        <button type="button" class="btn btn-outline btn-sm flex-1" data-copy-link><i class="fas fa-link"></i> Salin Link</button>
                    </div>

                    <div class="mt-4 flex items-center justify-center gap-3">
                        <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($shareUrl) }}" target="_blank" rel="noopener" class="share-icon" aria-label="Bagikan ke Facebook" title="Bagikan ke Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="https://twitter.com/intent/tweet?url={{ urlencode($shareUrl) }}&text={{ $shareTitle }}" target="_blank" rel="noopener" class="share-icon" aria-label="Bagikan ke X" title="Bagikan ke X"><i class="fab fa-x-twitter"></i></a>
                        <a href="https://api.whatsapp.com/send?text={{ $shareText }}" target="_blank" rel="noopener" class="share-icon" aria-label="Bagikan ke WhatsApp" title="Bagikan ke WhatsApp"><i class="fab fa-whatsapp"></i></a>
                        <a href="https://t.me/share/url?url={{ urlencode($shareUrl) }}&text={{ $shareTitle }}" target="_blank" rel="noopener" class="share-icon" aria-label="Bagikan ke Telegram" title="Bagikan ke Telegram"><i class="fab fa-telegram"></i></a>
                        <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode($shareUrl) }}" target="_blank" rel="noopener" class="share-icon" aria-label="Bagikan ke LinkedIn" title="Bagikan ke LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                    </div>

                    <div class="mt-5 border-t border-black/5 pt-4 text-xs text-gray-500">
                        <p>Butuh bantuan? Hubungi</p>
                        <a href="https://wa.me/{{ $waNumber }}" target="_blank" rel="noopener" class="mt-1 inline-flex items-center gap-1.5 font-semibold text-primary-600 hover:text-primary-700"
                           data-wa-log data-wa-source="{{ $waSource }}" data-wa-program="{{ $program->id }}" @if($agen) data-wa-agen="{{ $agen->id }}" @endif>
                            <i class="fab fa-whatsapp"></i> {{ $waNumber }}
                        </a>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</main>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';
    var btn = document.querySelector('[data-copy-link]');
    if (!btn) return;
    btn.addEventListener('click', function () {
        var url = window.location.href;
        function done() {
            if (window.BerbagiToast) window.BerbagiToast('Link berhasil disalin.');
        }
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(url).then(done, done);
        } else {
            var ta = document.createElement('textarea');
            ta.value = url;
            ta.style.position = 'fixed';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.select();
            try { document.execCommand('copy'); } catch (e) {}
            document.body.removeChild(ta);
            done();
        }
    });
})();
</script>
@endpush
