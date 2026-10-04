@extends('mobile.layouts.app')

@section('title', 'Program')

@section('mobile-content')
<div class="mo-appbar">
    <div class="mo-appbar-row">
        <div style="flex:1;min-width:0;">
            <h1 class="mo-appbar-title"><i class="fas fa-file-invoice-dollar" style="color:var(--mo-primary);font-size:20px;"></i> Program</h1>
            <div class="mo-appbar-sub">{{ $programs->count() }} program aktif</div>
        </div>
        <a href="{{ route('mo.more') }}" class="mo-icon-btn" aria-label="Menu">
            <i class="fas fa-bars"></i>
        </a>
    </div>
</div>

<div class="mo-content" style="padding-top:0;">
    <div class="mo-program-grid">
        @forelse($programs as $p)
            <div class="mo-program-card" data-program-slug="{{ $p->slug }}">
                <div class="mo-program-actions" style="position:absolute;top:10px;right:10px;display:flex;gap:6px;z-index:3;">
                    <button type="button" class="mo-program-donors" data-program-donors="{{ route('mo.program.donors', $p->id) }}" aria-label="Lihat donatur" style="width:34px;height:34px;border:none;border-radius:11px;background:rgba(0,0,0,.35);color:#fff;display:grid;place-items:center;">
                        <i class="fas fa-users"></i>
                    </button>
                    <button type="button" class="mo-program-share" data-program-share="{{ url('/program/' . $p->slug) }}" data-share-title="{{ $p->name }}" aria-label="Bagikan program" style="width:34px;height:34px;border:none;border-radius:11px;background:rgba(0,0,0,.35);color:#fff;display:grid;place-items:center;">
                        <i class="fas fa-share-nodes"></i>
                    </button>
                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('mo.program.edit', $p->id) }}" class="mo-program-edit-btn" aria-label="Edit program" onclick="event.stopPropagation();" style="width:34px;height:34px;border:none;border-radius:11px;background:rgba(0,0,0,.35);color:#fff;display:grid;place-items:center;">
                            <i class="fas fa-pen"></i>
                        </a>
                    @endif
                </div>
                <div class="mo-program-cover">
                    @if($p->image_url)
                        <img src="{{ $p->image_url }}" alt="{{ $p->name }}" loading="lazy">
                    @else
                        <div style="width:100%;height:100%;display:grid;place-items:center;color:rgba(255,255,255,0.85);font-size:26px;">
                            <i class="fas fa-hand-holding-heart"></i>
                        </div>
                    @endif
                    @if($p->category_label)
                        <span class="mo-badge"><i class="fas fa-tag"></i> {{ $p->category_label }}</span>
                    @endif
                </div>
                <div class="mo-program-body">
                    <h3 class="mo-program-name">{{ $p->name }}</h3>
                    <div class="mo-progress-track">
                        <div class="mo-progress-fill" style="width: {{ min(100, $p->progress) }}%"></div>
                    </div>
                    <div class="mo-progress-meta">
                        <span><strong>{{ $p->collected_formatted }}</strong> terkumpul</span>
                        @if($p->goal > 0)
                            <span>Target {{ $p->goal_formatted }} · {{ $p->progress }}%</span>
                        @else
                            <span>{{ $p->progress }}%</span>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="mo-empty">
                <i class="fas fa-file-invoice-dollar"></i>
                <p>Belum ada program aktif.</p>
            </div>
        @endforelse
    </div>
</div>

<a href="{{ route('mo.program.create') }}" class="mo-fab" aria-label="Tambah Program">
    <i class="fas fa-plus"></i>
</a>
@endsection

@section('sheets')
@include('mobile.partials.program-donor-sheet')
@endsection
