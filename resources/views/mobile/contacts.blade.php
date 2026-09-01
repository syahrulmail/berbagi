@extends('mobile.layouts.app')

@section('title', 'Kontak')

@section('mobile-content')
<div class="mo-appbar">
    <div class="mo-appbar-row">
        <div style="flex:1;min-width:0;">
            <h1 class="mo-appbar-title"><i class="fas fa-address-book" style="color:var(--mo-primary);font-size:20px;"></i> Kontak</h1>
            <div class="mo-appbar-sub">{{ $statusCounts['all'] }} kontak terhubung</div>
        </div>
        <a href="{{ route('mo.more') }}" class="mo-icon-btn" aria-label="Menu">
            <i class="fas fa-bars"></i>
        </a>
    </div>
</div>

<div class="mo-content" style="padding-top:0;">
    <form method="GET" action="{{ route('mo.contacts') }}" style="margin-bottom:14px;">
        <div class="mo-search">
            <i class="fas fa-magnifying-glass"></i>
            <input type="search" name="search" placeholder="Cari nama / nomor WA..." value="{{ request('search') }}">
        </div>

        <div class="mo-segmented">
            <button type="submit" name="status" value="" class="mo-segmented-item {{ !request('status') ? 'active' : '' }}">
                Semua ({{ $statusCounts['all'] }})
            </button>
            <button type="submit" name="status" value="donated" class="mo-segmented-item {{ request('status') === 'donated' ? 'active' : '' }}">
                Donatur ({{ $statusCounts['donated'] }})
            </button>
            <button type="submit" name="status" value="prospect" class="mo-segmented-item {{ request('status') === 'prospect' ? 'active' : '' }}">
                Prospek ({{ $statusCounts['prospect'] }})
            </button>
        </div>
    </form>

    <div class="mo-list">
        @forelse($contacts as $c)
            @php
                $statusCls = ['prospect' => 'gray', 'contacted' => 'blue', 'donated' => 'green', 'churned' => 'red'][$c->status] ?? 'gray';
                $rowIcon = $c->status === 'donated' ? '' : ($c->status === 'prospect' ? 'gold' : 'blue');
            @endphp
            <div class="mo-row" data-contact-detail="{{ $c->id }}">
                <div class="mo-row-icon {{ $rowIcon }}">{{ strtoupper(substr($c->name, 0, 1)) }}</div>
                <div class="mo-row-body">
                    <div class="mo-row-title">{{ $c->name }}</div>
                    <div class="mo-row-sub">{{ $c->phone }}</div>
                    <div style="margin-top:5px;">
                        <span class="mo-badge {{ $statusCls }}">{{ $c->statusLabel() }}</span>
                    </div>
                </div>
                <div class="mo-row-end">
                    <i class="fas fa-chevron-right chev"></i>
                </div>
            </div>
        @empty
            <div class="mo-empty">
                <i class="fas fa-address-book"></i>
                <p>Belum ada kontak{{ request('search') ? ' sesuai pencarian' : '' }}.</p>
            </div>
        @endforelse
    </div>
</div>

<a href="{{ route('mo.contact.create') }}" class="mo-fab" aria-label="Tambah Kontak">
    <i class="fas fa-plus"></i>
</a>
@endsection

@section('sheets')
@include('mobile.partials.contact-sheet')
@endsection
