@extends('mobile.layouts.app')

@section('title', 'Beranda')

@section('mobile-content')
<div class="mo-appbar">
    <div class="mo-appbar-row">
        <div class="mo-avatar">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
        <div style="flex:1;min-width:0;">
            <div style="font-size:12px;color:var(--mo-muted);">{{ $greeting }},</div>
            <h1 class="mo-appbar-title" style="font-size:18px;">{{ $user->name }}</h1>
        </div>
        <a href="{{ route('mo.more') }}" class="mo-icon-btn" aria-label="Menu">
            <i class="fas fa-bars"></i>
        </a>
    </div>
</div>

<div class="mo-content" style="padding-top:0;">
    @if(session('success'))
        <div class="mo-card mo-flash" style="background:#e5f7ec;color:#1f8a4c;box-shadow:none;padding:13px 15px;">
            <i class="fas fa-circle-check"></i> {{ session('success') }}
        </div>
    @endif

    {{-- Hero ringkasan --}}
    <div class="mo-hero">
        <div class="mo-hero-label">Total Donasi Bulan Ini</div>
        <div class="mo-hero-amount">Rp {{ number_format((int) $monthTotal, 0, ',', '.') }}</div>
        <div class="mo-hero-sub">
            @if($growthPercent >= 0)
                <i class="fas fa-arrow-trend-up"></i> Naik {{ abs($growthPercent) }}%
            @else
                <i class="fas fa-arrow-trend-down"></i> Turun {{ abs($growthPercent) }}%
            @endif
            vs bulan lalu
        </div>
        @if($totalTarget > 0)
            <div class="mo-hero-progress">
                <div class="mo-hero-progress-fill" style="width: {{ min(100, $overallProgress) }}%"></div>
            </div>
            <div class="mo-hero-sub" style="margin-top:7px;">
                {{ $overallProgress }}% dari target Rp {{ number_format((int) $totalTarget, 0, ',', '.') }}
            </div>
        @endif
    </div>

    {{-- Statistik ringkas --}}
    <div class="mo-stats">
        <div class="mo-stat">
            <div class="mo-stat-icon green"><i class="fas fa-wallet"></i></div>
            <div class="mo-stat-value">Rp {{ number_format((int) $todayTotal, 0, ',', '.') }}</div>
            <div class="mo-stat-label">Hari Ini</div>
        </div>
        <div class="mo-stat">
            <div class="mo-stat-icon blue"><i class="fas fa-file-invoice-dollar"></i></div>
            <div class="mo-stat-value">{{ $monthDonations }}</div>
            <div class="mo-stat-label">Transaksi</div>
        </div>
        <div class="mo-stat">
            <div class="mo-stat-icon gold"><i class="fas fa-address-book"></i></div>
            <div class="mo-stat-value">{{ $totalContacts }}</div>
            <div class="mo-stat-label">Kontak</div>
        </div>
    </div>

    {{-- Tren 7 hari --}}
    <div class="mo-card">
        <div class="mo-card-head">
            <h2 class="mo-card-title"><i class="fas fa-chart-column"></i> Tren 7 Hari</h2>
            <a href="{{ route('mo.donations') }}" class="mo-card-link">Lihat Semua</a>
        </div>
        <div class="mo-trend">
            @foreach($trend as $t)
                <div class="mo-trend-col">
                    <div class="mo-trend-bar-wrap">
                        <div class="mo-trend-bar" style="height: {{ max(6, round(($t['value'] / $trendMax) * 100)) }}%"
                             title="Rp {{ number_format($t['value'], 0, ',', '.') }}"></div>
                    </div>
                    <div class="mo-trend-label">{{ $t['label'] }}</div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Donasi terbaru --}}
    <div class="mo-card-head" style="margin:4px 2px 10px;">
        <h2 class="mo-card-title"><i class="fas fa-clock-rotate-left"></i> Donasi Terbaru</h2>
        <a href="{{ route('mo.donations') }}" class="mo-card-link">Semua</a>
    </div>

    @forelse($recentDonations as $d)
        <div class="mo-row" data-donation-detail="{{ $d->id }}">
            <div class="mo-row-icon"><i class="fas fa-hand-holding-dollar"></i></div>
            <div class="mo-row-body">
                <div class="mo-row-title">{{ $d->contact->name ?? ($d->donor_info ?: 'Donatur') }}</div>
                <div class="mo-row-sub">{{ $d->program_label }}</div>
            </div>
            <div class="mo-row-end">
                <div class="amount">{{ $d->amount_formatted }}</div>
                <div class="date">{{ $d->date_formatted }}</div>
            </div>
        </div>
    @empty
        <div class="mo-card mo-empty" style="box-shadow:none;background:transparent;">
            <i class="fas fa-hand-holding-dollar"></i>
            <p>Belum ada donasi tercatat.</p>
        </div>
    @endforelse
</div>

<a href="{{ route('mo.donations') }}" class="mo-fab" title="Catat Donasi" data-href="{{ route('donations.create') }}" aria-label="Catat Donasi">
    <i class="fas fa-plus"></i>
</a>
@endsection

@section('sheets')
@include('mobile.partials.donation-sheet')
@endsection
