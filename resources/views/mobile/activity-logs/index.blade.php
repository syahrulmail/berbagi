@extends('mobile.layouts.app')

@section('title', 'Log Aktivitas')

@section('mobile-content')
<div class="mo-appbar">
    <div class="mo-appbar-row">
        <a href="{{ route('mo.more') }}" class="mo-appbar-back" aria-label="Kembali"><i class="fas fa-arrow-left"></i></a>
        <div style="flex:1;min-width:0;">
            <h1 class="mo-appbar-title"><i class="fas fa-clock-rotate-left" style="color:var(--mo-primary);font-size:19px;"></i> Log Aktivitas</h1>
            <div class="mo-appbar-sub">{{ $logs->total() }} catatan</div>
        </div>
    </div>
</div>

<div class="mo-content" style="padding-top:0;">
    <form method="GET" action="{{ route('mo.activity-logs') }}" style="margin-bottom:14px;">
        <div class="mo-search">
            <i class="fas fa-magnifying-glass"></i>
            <input type="search" name="search" placeholder="Cari aksi / keterangan..." value="{{ request('search') }}">
        </div>
    </form>

    <div class="mo-list">
        @forelse($logs as $log)
            <div class="mo-row" style="align-items:flex-start;">
                <div class="mo-row-icon blue"><i class="fas fa-clipboard-list"></i></div>
                <div class="mo-row-body">
                    <div class="mo-row-title">{{ $log->user->name ?? 'Sistem' }}</div>
                    <div class="mo-row-sub">
                        <span class="mo-badge teal" style="font-family:monospace;">{{ $log->action }}</span>
                    </div>
                    @if($log->description)
                        <div style="font-size:12.5px;color:var(--mo-muted);margin-top:5px;line-height:1.4;">{{ $log->description }}</div>
                    @endif
                    <div style="font-size:11px;color:#9db3b0;margin-top:6px;">
                        <i class="fas fa-clock"></i> {{ $log->created_at ? $log->created_at->format('d M Y H:i') : '' }}
                        @if($log->ip_address) · {{ $log->ip_address }} @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="mo-empty">
                <i class="fas fa-clock-rotate-left"></i>
                <p>Belum ada log aktivitas.</p>
            </div>
        @endforelse
    </div>

    @include('mobile.partials.pager', ['paginator' => $logs])
</div>
@endsection
