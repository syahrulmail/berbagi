@extends('layouts.app')

@section('title', 'Dashboard Supervisor')

@section('content')
<div class="page-header">
    <div>
        <h1>Dashboard {{ auth()->user()->name }}</h1>
        <p class="subtitle">{{ $branch ? $branch->name . ' (' . $branch->city . ')' : 'Belum ada penugasan cabang' }}</p>
    </div>
</div>

<div class="metrics-grid">
    <div class="metric-card">
        <div class="metric-icon blue"><i class="fas fa-bullseye"></i></div>
        <div class="metric-info">
            <div class="metric-label">Target Cabang</div>
            <div class="metric-value">Rp {{ number_format($target, 0, ',', '.') }}</div>
            <div class="metric-sub">Target bulanan</div>
        </div>
    </div>
    <div class="metric-card">
        <div class="metric-icon green"><i class="fas fa-wallet"></i></div>
        <div class="metric-info">
            <div class="metric-label">Realisasi Bulan Ini</div>
            <div class="metric-value">Rp {{ number_format($collected, 0, ',', '.') }}</div>
            <div class="metric-sub">Total terkumpul</div>
        </div>
    </div>
    <div class="metric-card">
        <div class="metric-icon orange"><i class="fas fa-percent"></i></div>
        <div class="metric-info">
            <div class="metric-label">Pencapaian</div>
            <div class="metric-value">{{ $progress }}%</div>
            <div class="metric-sub">dari target</div>
        </div>
    </div>
    <div class="metric-card">
        <div class="metric-icon green"><i class="fab fa-whatsapp"></i></div>
        <div class="metric-info">
            <div class="metric-label">Follow-up WA Agen</div>
            <div class="metric-value">{{ $fuTotal }}</div>
            <div class="metric-sub">Klik WA di halaman publik</div>
        </div>
    </div>
    <div class="metric-card">
        <div class="metric-icon red"><i class="fas fa-users"></i></div>
        <div class="metric-info">
            <div class="metric-label">Jumlah Agen</div>
            <div class="metric-value">{{ $agents->count() }}</div>
            <div class="metric-sub">Di cabang Anda</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-tasks"></i> Progress Target Cabang</h2>
    </div>
    <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 14px;">
        <strong>{{ $branch ? $branch->name : '-' }}</strong>
        <span class="progress-percent">{{ $progress }}%</span>
    </div>
    <div class="progress-track" style="height: 14px;">
        <div class="progress-fill {{ $progress < 50 ? 'warning' : '' }} {{ $progress < 25 ? 'danger' : '' }}"
             style="width: {{ min(100, $progress) }}%"></div>
    </div>
    <div style="font-size: 12px; color: var(--gray-500); margin-top: 8px;">
        Rp {{ number_format($collected, 0, ',', '.') }} dari target Rp {{ number_format($target, 0, ',', '.') }}
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-ranking-star"></i> Performa Agen</h2>
    </div>
    @forelse($agents as $index => $agent)
        <div class="leaderboard-item">
            <div class="leaderboard-rank {{ $index < 3 ? 'top' : '' }}">{{ $index + 1 }}</div>
            <div class="leaderboard-name">{{ $agent->name }}</div>
            <div class="leaderboard-value">Rp {{ number_format($agent->collected, 0, ',', '.') }}</div>
        </div>
    @empty
        <p class="empty-state">Belum ada agen yang ditugaskan.</p>
    @endforelse
</div>
@endsection
