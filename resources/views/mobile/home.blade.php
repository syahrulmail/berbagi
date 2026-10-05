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
    {{-- Hero ringkasan --}}
    <div class="mo-hero">
        <div class="mo-hero-label">Total Donasi Tercatat</div>
        <div class="mo-hero-amount">Rp {{ number_format((int) $totalRecorded, 0, ',', '.') }}</div>
        <div class="mo-hero-sub">{{ number_format($totalTransactions, 0, ',', '.') }} Transaksi dari {{ number_format($totalDonors, 0, ',', '.') }} Donatur (seluruh data tercatat)</div>

        <div class="mo-hero-divider"></div>

        <div class="mo-hero-label">Total Donasi Bulan Ini</div>
        <div class="mo-hero-amount">Rp {{ number_format((int) $monthTotal, 0, ',', '.') }}</div>
        <div class="mo-hero-sub">{{ number_format($monthDonations, 0, ',', '.') }} Transaksi dari {{ number_format($monthDonors, 0, ',', '.') }} Donatur (bulan ini)</div>
        <div class="mo-hero-sub" style="margin-top:6px;">
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
            <div class="mo-stat-icon gold"><i class="fas fa-hand-holding-dollar"></i></div>
            <div class="mo-stat-value">{{ number_format($donorsToday, 0, ',', '.') }}</div>
            <div class="mo-stat-label">Donatur (hari ini)</div>
        </div>
    </div>

    {{-- Tren bulan ini --}}
    <div class="mo-card">
        <div class="mo-card-head">
            <h2 class="mo-card-title"><i class="fas fa-chart-column"></i> Tren Bulan ini</h2>
            <a href="{{ route('mo.donations') }}" class="mo-card-link">Lihat Semua</a>
        </div>
        <div class="mo-trend-h">
            @foreach($trend as $t)
                <div class="mo-trend-h-row {{ $t['is_weekend'] ? 'is-weekend' : '' }}">
                    <div class="mo-trend-h-label">{{ $t['label'] }}</div>
                    <div class="mo-trend-h-track">
                        <div class="mo-trend-h-bar"
                             style="width: {{ $t['value'] > 0 ? max(4, round(($t['value'] / $trendMax) * 100)) : 0 }}%"
                             title="Rp {{ number_format($t['value'], 0, ',', '.') }}"></div>
                    </div>
                    <div class="mo-trend-h-value">{{ $t['value'] > 0 ? number_format($t['value'], 0, ',', '.') : '-' }}</div>
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

<a href="{{ route('mo.donations') }}" class="mo-fab" title="Catat Donasi" data-href="{{ route('mo.donation.create') }}" aria-label="Catat Donasi">
    <i class="fas fa-plus"></i>
</a>
@endsection

@section('sheets')
@include('mobile.partials.donation-sheet')
@endsection
