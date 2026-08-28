@extends('layouts.public')

@section('title', 'Cara Berdonasi · Berbagi.or.id')
@section('meta_description', 'Cara berdonasi wakaf, infak, dan sedekah di Badan Wakaf Al Qur\'an (BWA) — pilih program, chat WhatsApp, transfer & konfirmasi.')

@section('content')

<section class="relative overflow-hidden bg-gradient-to-br from-primary-700 via-primary-800 to-primary-950 py-16 text-white">
    <div class="pointer-events-none absolute -right-24 -top-24 h-96 w-96 rounded-full bg-primary-500/20 blur-3xl"></div>
    <div class="pointer-events-none absolute -bottom-32 -left-24 h-96 w-96 rounded-full bg-gold-500/10 blur-3xl"></div>
    <div class="container relative">
        <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-1.5 text-sm font-medium text-primary-100 ring-1 ring-white/15">
            <i class="fas fa-circle-question"></i> Panduan Donasi
        </span>
        <h1 class="mt-6 max-w-2xl text-4xl font-extrabold leading-tight md:text-5xl">
            Berdonasi dalam <em class="not-italic text-gold-300">3 Langkah Mudah</em>
        </h1>
        <p class="mt-5 max-w-xl text-base leading-relaxed text-primary-100/90">
            Tanpa repot dan tanpa kewajiban. Pilih program, hubungi kami via WhatsApp, dan tim BWA membantu Anda menyalurkan dengan amanah.
        </p>
    </div>
</section>

<main class="section">
    <div class="container">

        <div class="grid gap-6 md:grid-cols-3" data-reveal>
            <div class="relative rounded-3xl border border-black/5 bg-white p-7 shadow-card">
                <span class="grid h-12 w-12 place-items-center rounded-2xl bg-primary-100 text-xl font-extrabold text-primary-600">1</span>
                <h3 class="mt-5 text-lg font-bold text-primary-900">Pilih Program</h3>
                <p class="mt-2 text-sm leading-relaxed text-gray-600">Tentukan program wakaf atau sedekah yang ingin Anda dukung dari daftar program aktif.</p>
                <a href="{{ route('home') }}#program" class="mt-4 inline-flex items-center gap-1.5 text-sm font-semibold text-primary-600 transition hover:text-primary-700">Lihat Program <i class="fas fa-arrow-right"></i></a>
            </div>
            <div class="relative rounded-3xl border border-black/5 bg-white p-7 shadow-card">
                <span class="grid h-12 w-12 place-items-center rounded-2xl bg-gold-100 text-xl font-extrabold text-gold-600">2</span>
                <h3 class="mt-5 text-lg font-bold text-primary-900">Chat WhatsApp</h3>
                <p class="mt-2 text-sm leading-relaxed text-gray-600">Chat tim BWA dengan pesan otomatis terisi. Tanyakan apa saja — gratis, tanpa kewajiban donasi.</p>
                <a href="https://wa.me/{{ $waNumber }}" target="_blank" rel="noopener" class="mt-4 inline-flex items-center gap-1.5 text-sm font-semibold text-primary-600 transition hover:text-primary-700" data-wa-log data-wa-source="cara-donasi"><i class="fab fa-whatsapp"></i> Chat Sekarang</a>
            </div>
            <div class="relative rounded-3xl border border-black/5 bg-white p-7 shadow-card">
                <span class="grid h-12 w-12 place-items-center rounded-2xl bg-emerald-100 text-xl font-extrabold text-emerald-600">3</span>
                <h3 class="mt-5 text-lg font-bold text-primary-900">Transfer &amp; Konfirmasi</h3>
                <p class="mt-2 text-sm leading-relaxed text-gray-600">Transfer ke rekening resmi yang tim kami berikan, lalu konfirmasi di WhatsApp. Donasi Anda tercatat resmi.</p>
            </div>
        </div>

        <div class="mt-14 grid gap-8 lg:grid-cols-2" data-reveal>
            <div class="rounded-3xl border border-black/5 bg-white p-7 shadow-card">
                <div class="flex items-center gap-3">
                    <span class="grid h-11 w-11 place-items-center rounded-xl bg-primary-100 text-primary-600"><i class="fab fa-whatsapp text-lg"></i></span>
                    <div>
                        <h2 class="text-lg font-bold text-primary-900">Donasi via WhatsApp</h2>
                        <p class="text-xs text-gray-500">Paling cepat &amp; paling mudah</p>
                    </div>
                </div>
                <p class="mt-4 text-sm leading-relaxed text-gray-600">Klik tombol di bawah, pesan akan terisi otomatis. Tim kami membalas dalam 1×24 jam dan memandu Anda sampai donasi tercatat.</p>
                <a href="https://wa.me/{{ $waNumber }}" target="_blank" rel="noopener" class="btn btn-wa mt-5 w-full !py-3.5"
                   data-wa-log data-wa-source="cara-donasi">
                    <i class="fab fa-whatsapp"></i> {{ $waNumber }}
                </a>
                <p class="mt-3 text-center text-xs text-gray-400">Gratis konsultasi · Tanpa kewajiban</p>
            </div>

            <div class="rounded-3xl border border-black/5 bg-white p-7 shadow-card">
                <div class="flex items-center gap-3">
                    <span class="grid h-11 w-11 place-items-center rounded-xl bg-gold-100 text-gold-600"><i class="fas fa-building-columns text-lg"></i></span>
                    <div>
                        <h2 class="text-lg font-bold text-primary-900">Transfer Bank</h2>
                        <p class="text-xs text-gray-500">Rekening resmi atas nama BWA</p>
                    </div>
                </div>
                <p class="mt-4 text-sm leading-relaxed text-gray-600">Untuk keamanan, nomor rekening resmi diberikan langsung oleh tim kami melalui WhatsApp beserta panduan transfer dan tata cara konfirmasi.</p>
                <a href="https://wa.me/{{ $waNumber }}?text={{ urlencode('Assalamualaikum, saya ingin mengetahui rekening resmi untuk berdonasi di BWA.') }}" target="_blank" rel="noopener"
                   class="btn btn-outline mt-5 w-full !py-3.5" data-wa-log data-wa-source="cara-donasi">
                    <i class="fab fa-whatsapp"></i> Minta Rekening Resmi
                </a>
                <p class="mt-3 text-center text-xs text-gray-400">Hati-hati penipuan mengatasnamakan BWA</p>
            </div>
        </div>

        <div class="mt-14 flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-primary-100 bg-primary-50/60 p-6" data-reveal>
            <div class="flex items-start gap-3">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-primary-100 text-primary-600"><i class="fas fa-scale-balanced"></i></span>
                <div>
                    <p class="font-semibold text-primary-900">Badan Wakaf Al Qur'an — lembaga terdaftar &amp; berizin</p>
                    <p class="text-sm text-gray-600">Seluruh penghimpunan dan penyaluran dicatat resmi. Lihat ringkasan dan laporan di halaman transparansi.</p>
                </div>
            </div>
            <a href="{{ route('public.transparansi') }}" class="btn btn-outline">Lihat Transparansi</a>
        </div>

        <div class="mt-14" data-reveal>
            <div class="section-head">
                <div>
                    <h2>Pertanyaan Umum</h2>
                    <p class="muted mt-1 text-sm">Hal-hal yang sering ditanyakan sebelum berdonasi.</p>
                </div>
            </div>
            <div class="mx-auto max-w-3xl space-y-3">
                <details class="group rounded-2xl border border-black/5 bg-white p-5 shadow-card">
                    <summary class="flex cursor-pointer items-center justify-between gap-3 font-semibold text-primary-900">
                        Apakah BWA lembaga resmi?
                        <i class="fas fa-chevron-down text-sm text-gray-400 transition group-open:rotate-180"></i>
                    </summary>
                    <p class="mt-3 text-sm leading-relaxed text-gray-600">Badan Wakaf Al Qur'an (BWA) adalah lembaga terdaftar dan berizin dalam penghimpunan serta penyaluran wakaf, infak, dan sedekah. Detail legalitas tersedia di halaman transparansi.</p>
                </details>
                <details class="group rounded-2xl border border-black/5 bg-white p-5 shadow-card">
                    <summary class="flex cursor-pointer items-center justify-between gap-3 font-semibold text-primary-900">
                        Apakah aman berdonasi di sini?
                        <i class="fas fa-chevron-down text-sm text-gray-400 transition group-open:rotate-180"></i>
                    </summary>
                    <p class="mt-3 text-sm leading-relaxed text-gray-600">Seluruh donasi dicatat resmi dan dapat ditelusuri. Nomor rekening diberikan langsung oleh tim kami melalui WhatsApp — jangan percaya nomor yang tidak terkonfirmasi.</p>
                </details>
                <details class="group rounded-2xl border border-black/5 bg-white p-5 shadow-card">
                    <summary class="flex cursor-pointer items-center justify-between gap-3 font-semibold text-primary-900">
                        Bagaimana cara transfer dan konfirmasinya?
                        <i class="fas fa-chevron-down text-sm text-gray-400 transition group-open:rotate-180"></i>
                    </summary>
                    <p class="mt-3 text-sm leading-relaxed text-gray-600">Chat kami via WhatsApp untuk mendapatkan rekening resmi. Setelah transfer, kirim bukti transfer di chat yang sama — tim kami akan mengonfirmasi pencatatan donasi Anda.</p>
                </details>
                <details class="group rounded-2xl border border-black/5 bg-white p-5 shadow-card">
                    <summary class="flex cursor-pointer items-center justify-between gap-3 font-semibold text-primary-900">
                        Apakah saya bisa memilih langsung penerima manfaat?
                        <i class="fas fa-chevron-down text-sm text-gray-400 transition group-open:rotate-180"></i>
                    </summary>
                    <p class="mt-3 text-sm leading-relaxed text-gray-600">Ya. Anda dapat memilih program yang ingin didukung, dan tim kami akan menjelaskan penyalurannya beserta laporan yang tersedia.</p>
                </details>
            </div>
        </div>

        @if(count($recentDonors))
            <div class="mt-14 rounded-3xl border border-black/5 bg-white p-7 shadow-card" data-reveal>
                <h2 class="text-lg font-bold text-primary-900">Donatur Terbaru</h2>
                <p class="muted mt-1 text-sm">Sebagian dari mereka yang telah berdonasi.</p>
                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                    @foreach($recentDonors as $donor)
                        <div class="flex items-center gap-3 rounded-2xl bg-primary-50/50 px-4 py-3">
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
        @endif

        <div class="mt-14 rounded-3xl bg-gradient-to-br from-primary-700 to-primary-950 p-10 text-center text-white" data-reveal>
            <h2 class="text-2xl font-extrabold md:text-3xl">Siap berbuat kebaikan?</h2>
            <p class="mx-auto mt-3 max-w-xl text-sm text-primary-100/90">Pilih program wakaf atau hubungi kami langsung — tim BWA siap membantu Anda.</p>
            <div class="mt-7 flex flex-wrap justify-center gap-3">
                <a href="{{ route('home') }}#program" class="btn btn-gold"><i class="fas fa-arrow-down"></i> Lihat Program</a>
                <a href="https://wa.me/{{ $waNumber }}" target="_blank" rel="noopener" class="btn btn-wa"
                   data-wa-log data-wa-source="cara-donasi">
                    <i class="fab fa-whatsapp"></i> Chat WhatsApp
                </a>
            </div>
        </div>
    </div>
</main>
@endsection
