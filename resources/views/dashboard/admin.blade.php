@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="page-header">
    <div>
        <h1>Selamat Datang, {{ auth()->user()->name }}</h1>
        <p class="subtitle">Ringkasan performa fundraising BWA hari ini.</p>
    </div>
</div>

<div class="metrics-grid">
    <div class="metric-card">
        <div class="metric-icon green"><i class="fas fa-wallet"></i></div>
        <div class="metric-info">
            <div class="metric-label">Wakaf Hari Ini</div>
            <div class="metric-value">Rp {{ number_format($todayTotal, 0, ',', '.') }}</div>
            <div class="metric-sub">{{ $totalDonationsToday }} transaksi hari ini</div>
        </div>
    </div>
    <div class="metric-card">
        <div class="metric-icon blue"><i class="fas fa-chart-line"></i></div>
        <div class="metric-info">
            <div class="metric-label">Total Bulan Ini</div>
            <div class="metric-value">Rp {{ number_format($monthTotal, 0, ',', '.') }}</div>
            <div class="metric-sub {{ $growthPercent >= 0 ? 'positive' : 'negative' }}">
                <i class="fas fa-arrow-{{ $growthPercent >= 0 ? 'up' : 'down' }}"></i> {{ abs($growthPercent) }}% vs bulan lalu
            </div>
        </div>
    </div>
    <div class="metric-card">
        <div class="metric-icon orange"><i class="fas fa-bullseye"></i></div>
        <div class="metric-info">
            <div class="metric-label">Target Tercapai (Bulan Ini)</div>
            <div class="metric-value">{{ $overallProgress }}%</div>
            <div class="metric-sub">dari Rp {{ number_format($totalTarget, 0, ',', '.') }}</div>
        </div>
    </div>
    <div class="metric-card">
        <div class="metric-icon red"><i class="fas fa-hand-holding-heart"></i></div>
        <div class="metric-info">
            <div class="metric-label">Program Aktif</div>
            <div class="metric-value">{{ $totalPrograms }}</div>
            <div class="metric-sub">{{ $totalAgents }} agen aktif</div>
        </div>
    </div>
</div>

<div class="layout-grid-admin">
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-trophy"></i> Top Performers (Cabang)</h2>
        </div>
        @forelse($topPerformers as $rank => $branch)
            <div class="leaderboard-item">
                <div class="leaderboard-rank {{ $rank < 3 ? 'top' : '' }}">{{ $rank + 1 }}</div>
                <div class="leaderboard-name">{{ $branch->name }}</div>
                <div class="leaderboard-value">{{ $branch->progress }}%</div>
            </div>
        @empty
            <p class="empty-state">Belum ada data donasi.</p>
        @endforelse
    </div>

    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-tasks"></i> Progress per Cabang</h2>
        </div>
        @forelse($branches->take(8) as $branch)
            <div style="margin-bottom: 16px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 6px; font-size: 13px;">
                    <span>{{ $branch->name }}</span>
                    <span class="progress-percent">{{ $branch->progress }}%</span>
                </div>
                <div class="progress-track">
                    <div class="progress-fill {{ $branch->progress < 50 ? 'warning' : '' }} {{ $branch->progress < 25 ? 'danger' : '' }}"
                         style="width: {{ min(100, $branch->progress) }}%"></div>
                </div>
                <div style="font-size: 11px; color: var(--gray-500); margin-top: 4px;">
                    Rp {{ number_format($branch->collected, 0, ',', '.') }} / Rp {{ number_format($branch->target_amount, 0, ',', '.') }}
                </div>
            </div>
        @empty
            <p class="empty-state">Belum ada data.</p>
        @endforelse
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-chart-column"></i> Tren Donasi 7 Hari Terakhir</h2>
    </div>
    <div class="trend-bars">
        @foreach($trend as $item)
            <div class="trend-col">
                <div class="trend-bar-wrap">
                    @php
                        $maxTrend = max(array_column($trend, 'total')) ?: 1;
                        $height = max(4, round(($item['total'] / $maxTrend) * 120));
                    @endphp
                    <div class="trend-bar" style="height: {{ $height }}px;" title="Rp {{ number_format($item['total'], 0, ',', '.') }}"></div>
                </div>
                <div class="trend-label">{{ $item['date'] }}</div>
            </div>
        @endforeach
    </div>
</div>

<style>
.layout-grid-admin {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}
@media (max-width: 900px) {
    .layout-grid-admin { grid-template-columns: 1fr; }
}
.trend-bars {
    display: flex;
    align-items: flex-end;
    gap: 18px;
    height: 160px;
    padding: 10px 0;
}
.trend-col {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
}
.trend-bar-wrap {
    flex: 1;
    display: flex;
    align-items: flex-end;
    width: 100%;
}
.trend-bar {
    width: 100%;
    background: linear-gradient(180deg, var(--primary) 0%, #4A7CBB 100%);
    border-radius: 6px 6px 0 0;
    min-height: 4px;
    transition: height 0.5s ease;
}
.trend-label {
    font-size: 11px;
    color: var(--gray-500);
}
</style>
@endsection
