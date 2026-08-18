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
            <div class="metric-value">Rp <span data-vue-app="CountUp"><script type="application/json">{"value": {{ (int) $todayTotal }}}</script></span></div>
            <div class="metric-sub">{{ $totalDonationsToday }} transaksi hari ini</div>
        </div>
    </div>
    <div class="metric-card">
        <div class="metric-icon blue"><i class="fas fa-chart-line"></i></div>
        <div class="metric-info">
            <div class="metric-label">Total Bulan Ini</div>
            <div class="metric-value">Rp <span data-vue-app="CountUp"><script type="application/json">{"value": {{ (int) $monthTotal }}}</script></span></div>
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
            <div class="metric-value"><span data-vue-app="CountUp"><script type="application/json">{"value": {{ $totalPrograms }}}</script></span></div>
            <div class="metric-sub">{{ $totalAgents }} agen aktif</div>
        </div>
    </div>
</div>

<div class="layout-grid-admin">
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-bullseye"></i> Pencapaian Target Bulan Ini</h2>
        </div>
        <div class="flex flex-col items-center gap-4 py-4">
            <div data-vue-app="DonutChart">
                <script type="application/json">{"value": {{ $overallProgress }}, "size": 170, "stroke": 18}</script>
            </div>
            <div class="text-center">
                <p class="text-sm text-gray-500">Terkumpul bulan ini</p>
                <p class="text-xl font-bold text-primary-700">Rp {{ number_format($monthTotal, 0, ',', '.') }}</p>
                <p class="text-xs text-gray-400">dari target Rp {{ number_format($totalTarget, 0, ',', '.') }}</p>
            </div>
        </div>
    </div>

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
</div>

<div class="layout-grid-admin">
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
                <div style="font-size: 11px; color: #5e7472; margin-top: 4px;">
                    Rp {{ number_format($branch->collected, 0, ',', '.') }} / Rp {{ number_format($branch->target_amount, 0, ',', '.') }}
                </div>
            </div>
        @empty
            <p class="empty-state">Belum ada data.</p>
        @endforelse
    </div>

    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-chart-column"></i> Tren Donasi 7 Hari Terakhir</h2>
        </div>
        <div data-vue-app="BarChart">
            <script type="application/json">@json(['data' => array_map(fn($t) => ['label' => $t['date'], 'value' => (int) $t['total']], $trend)])</script>
        </div>
    </div>
</div>
@endsection
