@extends('mobile.layouts.app')

@section('title', 'Donasi')

@section('mobile-content')
<div class="mo-appbar">
    <div class="mo-appbar-row">
        <div style="flex:1;min-width:0;">
            <h1 class="mo-appbar-title"><i class="fas fa-hand-holding-dollar" style="color:var(--mo-primary);font-size:20px;"></i> Donasi</h1>
            <div class="mo-appbar-sub">{{ $donations->count() }} catatan donasi</div>
        </div>
        <a href="{{ route('mo.more') }}" class="mo-icon-btn" aria-label="Menu">
            <i class="fas fa-bars"></i>
        </a>
    </div>
</div>

<div class="mo-content" style="padding-top:0;">
    <form method="GET" action="{{ route('mo.donations') }}" style="margin-bottom:14px;">
        <div class="mo-search">
            <i class="fas fa-magnifying-glass"></i>
            <input type="search" name="search" placeholder="Cari donatur / program..." value="{{ request('search') }}">
        </div>

        <div class="mo-segmented">
            <button type="submit" name="period" value="" class="mo-segmented-item {{ !request('period') ? 'active' : '' }}">Semua</button>
            <button type="submit" name="period" value="today" class="mo-segmented-item {{ request('period') === 'today' ? 'active' : '' }}">Hari Ini</button>
            <button type="submit" name="period" value="week" class="mo-segmented-item {{ request('period') === 'week' ? 'active' : '' }}">7 Hari</button>
        </div>
        @if(request('search') || request('period'))
            <div style="text-align:right;margin-top:-8px;margin-bottom:8px;">
                <a href="{{ route('mo.donations') }}" style="font-size:12px;color:var(--mo-muted);text-decoration:none;">
                    <i class="fas fa-rotate-left"></i> Reset filter
                </a>
            </div>
        @endif
    </form>

    @if(request('search'))
        <div style="font-size:12.5px;color:var(--mo-muted);margin:-6px 4px 12px;">
            Hasil pencarian: <strong>{{ $donations->count() }}</strong> donasi
        </div>
    @endif

    <div class="mo-list">
        @forelse($donations as $d)
            <div class="mo-row" data-donation-detail="{{ $d->id }}">
                <div class="mo-row-icon {{ $loop->first ? '' : 'blue' }}">
                    <i class="fas fa-hand-holding-dollar"></i>
                </div>
                <div class="mo-row-body">
                    <div class="mo-row-title">{{ $d->contact->name ?? ($d->donor_info ?: 'Donatur') }}</div>
                    <div class="mo-row-sub">
                        {{ $d->branch->name ?? '-' }} · {{ $d->agen->name ?? '-' }}
                    </div>
                    <div class="mo-row-sub" style="margin-top:4px;">
                        <span class="mo-badge teal">{{ $d->payment_method_label ?? $d->payment_method }}</span>
                    </div>
                </div>
                <div class="mo-row-end">
                    <div class="amount">{{ $d->amount_formatted }}</div>
                    <div class="date">{{ $d->date_formatted }}</div>
                </div>
            </div>
        @empty
            <div class="mo-empty">
                <i class="fas fa-hand-holding-dollar"></i>
                <p>Belum ada donasi{{ request('search') ? ' sesuai pencarian' : '' }}.</p>
            </div>
        @endforelse
    </div>
</div>

<a href="{{ route('mo.donation.create') }}" class="mo-fab" aria-label="Catat Donasi">
    <i class="fas fa-plus"></i>
</a>
@endsection

@section('sheets')
@include('mobile.partials.donation-sheet')
@endsection
