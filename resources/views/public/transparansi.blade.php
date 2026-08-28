@extends('layouts.public')

@section('title', 'Transparansi · Berbagi.or.id')
@section('meta_description', 'Transparansi Badan Wakaf Al Qur\'an (BWA): legalitas, total penghimpunan, dan laporan penyaluran dana wakaf & sedekah.')

@section('content')

<section class="relative overflow-hidden bg-gradient-to-br from-primary-700 via-primary-800 to-primary-950 py-16 text-white">
    <div class="pointer-events-none absolute -right-24 -top-24 h-96 w-96 rounded-full bg-primary-500/20 blur-3xl"></div>
    <div class="container relative">
        <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-1.5 text-sm font-medium text-primary-100 ring-1 ring-white/15">
            <i class="fas fa-file-invoice"></i> Akuntabilitas &amp; Kepercayaan
        </span>
        <h1 class="mt-6 max-w-2xl text-4xl font-extrabold leading-tight md:text-5xl">
            Transparansi untuk <em class="not-italic text-gold-300">Kepercayaan Anda</em>
        </h1>
        <p class="mt-5 max-w-xl text-base leading-relaxed text-primary-100/90">
            Setiap rupiah yang Anda amanahkan tercatat dan tersalurkan dengan amanah. Berikut ringkasan penghimpunan dan informasi lembaga.
        </p>
        <a href="{{ route('home') }}#program" class="btn btn-gold mt-8"><i class="fas fa-arrow-down"></i> Lihat Program</a>
    </div>
</section>

<main class="section">
    <div class="container">
        <div class="section-head" data-reveal>
            <div>
                <h2>Ringkasan Penghimpunan</h2>
                <p class="muted mt-1 text-sm">Angka diperbarui real-time dari pencatatan donasi resmi.</p>
            </div>
        </div>

        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4" data-reveal>
            <div class="rounded-2xl border border-black/5 bg-white p-6 shadow-card">
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary-100 text-primary-600"><i class="fas fa-hand-holding-dollar"></i></div>
                <p class="mt-4 text-2xl font-extrabold text-primary-900">Rp {{ number_format($totalCollected, 0, ',', '.') }}</p>
                <p class="mt-1 text-sm text-gray-500">Total Terkumpul</p>
            </div>
            <div class="rounded-2xl border border-black/5 bg-white p-6 shadow-card">
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-gold-100 text-gold-600"><i class="fas fa-bullseye"></i></div>
                <p class="mt-4 text-2xl font-extrabold text-primary-900">Rp {{ number_format($totalGoal, 0, ',', '.') }}</p>
                <p class="mt-1 text-sm text-gray-500">Target Nasional</p>
            </div>
            <div class="rounded-2xl border border-black/5 bg-white p-6 shadow-card">
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600"><i class="fas fa-users"></i></div>
                <p class="mt-4 text-2xl font-extrabold text-primary-900">{{ number_format($totalDonations) }}</p>
                <p class="mt-1 text-sm text-gray-500">Catatan Donasi</p>
            </div>
            <div class="rounded-2xl border border-black/5 bg-white p-6 shadow-card">
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary-100 text-primary-600"><i class="fas fa-percent"></i></div>
                <p class="mt-4 text-2xl font-extrabold text-primary-900">
                    {{ $totalGoal > 0 ? min(100, round(($totalCollected / $totalGoal) * 100, 1)) : 0 }}%
                </p>
                <p class="mt-1 text-sm text-gray-500">Pencapaian Target</p>
            </div>
        </div>

        <div class="mt-10 grid gap-8 lg:grid-cols-5" data-reveal>
            <div class="rounded-2xl border border-black/5 bg-white p-6 shadow-card lg:col-span-3">
                <h3 class="mb-6 text-lg font-bold text-primary-900">Distribusi per Program</h3>
                <div class="space-y-5">
                    @forelse($perProgram as $item)
                        <div>
                            <div class="mb-1.5 flex items-center justify-between gap-2 text-sm">
                                <span class="font-medium text-primary-900">{{ $item['name'] }}</span>
                                <span class="shrink-0 text-gray-500">
                                    <strong class="text-primary-700">Rp {{ number_format($item['collected'], 0, ',', '.') }}</strong>
                                    / Rp {{ number_format($item['goal'], 0, ',', '.') }}
                                </span>
                            </div>
                            <div class="progress-track">
                                <div class="progress-fill" data-percent="{{ $item['progress'] }}" style="width:0;"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">Belum ada data program.</p>
                    @endforelse
                </div>
            </div>

            <div class="lg:col-span-2">
                <div class="rounded-2xl border border-black/5 bg-primary-50/60 p-6">
                    <h3 class="mb-4 text-lg font-bold text-primary-900">Legalitas Lembaga</h3>
                    <ul class="space-y-4 text-sm">
                        <li class="flex items-start gap-3">
                            <span class="mt-0.5 grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-primary-100 text-primary-600"><i class="fas fa-scale-balanced"></i></span>
                            <div>
                                <p class="font-semibold text-primary-900">Badan Hukum</p>
                                <p class="text-gray-500">Badan Wakaf Al Qur'an (BWA)</p>
                            </div>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="mt-0.5 grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-gold-100 text-gold-600"><i class="fas fa-file-contract"></i></span>
                            <div>
                                <p class="font-semibold text-primary-900">Terdaftar Resmi</p>
                                <p class="text-gray-500">Lembaga terdaftar dan berizin dalam penghimpunan &amp; penyaluran wakaf, infak, dan sedekah.</p>
                            </div>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="mt-0.5 grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-emerald-100 text-emerald-600"><i class="fas fa-file-invoice-dollar"></i></span>
                            <div>
                                <p class="font-semibold text-primary-900">Sistem Pencatatan</p>
                                <p class="text-gray-500">Seluruh donasi tercatat dan dapat ditelusuri melalui tim / agen resmi.</p>
                            </div>
                        </li>
                    </ul>
                    <a href="https://wa.me/{{ $waNumber }}" target="_blank" rel="noopener" class="btn btn-wa mt-6 w-full"
                       data-wa-log data-wa-source="transparansi">
                        <i class="fab fa-whatsapp"></i> Tanya Transparansi
                    </a>
                </div>
            </div>
        </div>

        <div id="laporan" class="mt-14 scroll-mt-24 rounded-3xl border border-black/5 bg-white p-8 shadow-card" data-reveal>
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold text-primary-900">Laporan Penyaluran</h2>
                    <p class="muted mt-1 text-sm">Laporan berkala penyaluran dana akan dipublikasikan di halaman ini.</p>
                </div>
                <a href="https://wa.me/{{ $waNumber }}?text={{ urlencode('Assalamualaikum, saya ingin meminta laporan penyaluran BWA.') }}" target="_blank" rel="noopener"
                   class="btn btn-outline" data-wa-log data-wa-source="transparansi">
                    <i class="fab fa-whatsapp"></i> Minta Laporan
                </a>
            </div>
            <div class="mt-6 grid gap-4 sm:grid-cols-3">
                <div class="rounded-2xl border border-dashed border-primary-200 bg-primary-50/40 p-5 text-center">
                    <i class="fas fa-file-pdf text-2xl text-primary-400"></i>
                    <p class="mt-2 text-sm font-semibold text-primary-900">Laporan Bulanan</p>
                    <p class="mt-1 text-xs text-gray-500">Segera hadir</p>
                </div>
                <div class="rounded-2xl border border-dashed border-primary-200 bg-primary-50/40 p-5 text-center">
                    <i class="fas fa-chart-pie text-2xl text-primary-400"></i>
                    <p class="mt-2 text-sm font-semibold text-primary-900">Rekap Tahunan</p>
                    <p class="mt-1 text-xs text-gray-500">Segera hadir</p>
                </div>
                <div class="rounded-2xl border border-dashed border-primary-200 bg-primary-50/40 p-5 text-center">
                    <i class="fas fa-images text-2xl text-primary-400"></i>
                    <p class="mt-2 text-sm font-semibold text-primary-900">Dokumentasi Kegiatan</p>
                    <p class="mt-1 text-xs text-gray-500">Segera hadir</p>
                </div>
            </div>
        </div>

        <div class="mt-14 rounded-3xl bg-gradient-to-br from-primary-700 to-primary-950 p-10 text-center text-white" data-reveal>
            <h2 class="text-2xl font-extrabold md:text-3xl">Yakin untuk berdonasi sekarang?</h2>
            <p class="mx-auto mt-3 max-w-xl text-sm text-primary-100/90">Gratis konsultasi. Tim kami siap membantu Anda menyalurkan wakaf, infak, dan sedekah dengan amanah.</p>
            <a href="https://wa.me/{{ $waNumber }}" target="_blank" rel="noopener" class="btn btn-wa mt-7 !px-8 !py-3.5 !text-base"
               data-wa-log data-wa-source="transparansi">
                <i class="fab fa-whatsapp"></i> Donasi via WhatsApp
            </a>
        </div>
    </div>
</main>
@endsection
