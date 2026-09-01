@extends('mobile.layouts.app')

@section('title', 'Cabang')

@section('mobile-content')
<div class="mo-appbar">
    <div class="mo-appbar-row">
        <a href="{{ route('mo.more') }}" class="mo-appbar-back" aria-label="Kembali">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div style="flex:1;min-width:0;">
            <h1 class="mo-appbar-title">Cabang</h1>
            <div class="mo-appbar-sub">{{ $branches->count() }} cabang aktif</div>
        </div>
    </div>
</div>

<div class="mo-content" style="padding-top:0;">
    @forelse($branches as $b)
        <a href="{{ route('mo.branch.edit', $b['id']) }}" style="text-decoration:none;color:inherit;display:block;">
            <div class="mo-card mo-card--flat">
                <div class="mo-card-head" style="margin-bottom:10px;">
                    <div style="display:flex;align-items:center;gap:11px;">
                        <div class="mo-row-icon"><i class="fas fa-building"></i></div>
                        <div>
                            <div style="font-weight:700;font-size:14px;color:var(--mo-text);">{{ $b['name'] }}</div>
                            <div style="font-size:11.5px;color:var(--mo-muted);">{{ $b['city'] ?: '—' }}</div>
                        </div>
                    </div>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <span class="mo-badge {{ $b['progress'] < 25 ? 'red' : ($b['progress'] < 50 ? 'orange' : 'green') }}">{{ $b['progress'] }}%</span>
                        <i class="fas fa-pen" style="color:#c3d6d3;font-size:12px;"></i>
                    </div>
                </div>
                <div class="mo-progress-track">
                    <div class="mo-progress-fill" style="width: {{ min(100, $b['progress']) }}%"></div>
                </div>
                <div class="mo-progress-meta">
                    <span><strong>Rp {{ number_format($b['collected'], 0, ',', '.') }}</strong> terkumpul</span>
                    <span>Target Rp {{ number_format($b['target'], 0, ',', '.') }}</span>
                </div>
            </div>
        </a>
    @empty
        <div class="mo-empty">
            <i class="fas fa-building"></i>
            <p>Belum ada cabang aktif.</p>
        </div>
    @endforelse
</div>

<a href="{{ route('mo.branch.create') }}" class="mo-fab" aria-label="Tambah Cabang">
    <i class="fas fa-plus"></i>
</a>
@endsection
