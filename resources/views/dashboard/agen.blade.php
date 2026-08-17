@extends('layouts.app')

@section('title', 'Dashboard Agen')

@section('content')
<div class="page-header">
    <div>
        <h1>Halo, {{ auth()->user()->name }}</h1>
        <p class="subtitle">Pantau pencapaian fundraising Anda.</p>
    </div>
</div>

<div class="metrics-grid">
    <div class="metric-card">
        <div class="metric-icon green"><i class="fas fa-wallet"></i></div>
        <div class="metric-info">
            <div class="metric-label">Total Donasi Bulan Ini</div>
            <div class="metric-value">Rp {{ number_format($collected, 0, ',', '.') }}</div>
            <div class="metric-sub">Donasi berhasil dikumpulkan</div>
        </div>
    </div>
    <div class="metric-card">
        <div class="metric-icon blue"><i class="fas fa-address-book"></i></div>
        <div class="metric-info">
            <div class="metric-label">Total Kontak</div>
            <div class="metric-value">{{ $totalContacts }}</div>
            <div class="metric-sub">Kontak yang Anda kelola</div>
        </div>
    </div>
    <div class="metric-card">
        <div class="metric-icon orange"><i class="fas fa-check-circle"></i></div>
        <div class="metric-info">
            <div class="metric-label">Kontak Donated</div>
            <div class="metric-value">{{ $donatedContacts }}</div>
            <div class="metric-sub">Kontak yang sudah berdonasi</div>
        </div>
    </div>
    <div class="metric-card">
        <div class="metric-icon red"><i class="fas fa-plus-circle"></i></div>
        <div class="metric-info">
            <div class="metric-label">Aksi Cepat</div>
            <div class="metric-sub" style="margin-top:6px;">
                <a href="{{ route('donations.create') }}" class="btn btn-accent btn-sm">Catat Donasi</a>
                <a href="{{ route('contacts.create') }}" class="btn btn-sm" style="margin-top:6px;">Tambah Kontak</a>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-clock-rotate-left"></i> Donasi Terbaru</h2>
        <a href="{{ route('donations.index') }}" class="btn btn-sm">Lihat Semua</a>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Program</th>
                    <th>Cabang</th>
                    <th>Nominal</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentDonations as $donation)
                    <tr>
                        <td>{{ $donation->donation_date->format('d M Y') }}</td>
                        <td>{{ $donation->program->name ?? '-' }}</td>
                        <td>{{ $donation->branch->name ?? '-' }}</td>
                        <td><strong>Rp {{ number_format($donation->amount, 0, ',', '.') }}</strong></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="empty-state">Belum ada donasi yang dicatat.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
