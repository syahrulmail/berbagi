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
    @php
        $hasAdvancedFilter = request('from') || request('to') || in_array(request('sort'), ['donation'], true);
    @endphp
    <div class="mo-sticky-filter">
        <form method="GET" action="{{ route('mo.programs') }}" id="mo-program-form">
            <div class="mo-search-flex">
                <div class="mo-search">
                    <i class="fas fa-magnifying-glass"></i>
                    <input type="search" name="search" placeholder="Cari program..." value="{{ request('search') }}">
                </div>
                <button type="button" class="mo-filter-toggle {{ $hasAdvancedFilter ? 'has-filter' : '' }}" data-filter-toggle="mo-program-filters" aria-label="Filter periode dan urutan" aria-expanded="false">
                    <i class="fas fa-sliders"></i>
                </button>
            </div>

            <div id="mo-program-filters" hidden>
                <input type="hidden" name="sort" value="{{ request('sort') }}">
                <div style="display:flex;gap:10px;margin-top:10px;">
                    <div style="flex:1;">
                        <label for="program_from" style="display:block;font-size:11px;color:var(--mo-muted);margin-bottom:4px;">Dari tanggal</label>
                        <input type="date" id="program_from" name="from" class="mo-input" value="{{ request('from') }}" data-autosubmit>
                    </div>
                    <div style="flex:1;">
                        <label for="program_to" style="display:block;font-size:11px;color:var(--mo-muted);margin-bottom:4px;">Sampai tanggal</label>
                        <input type="date" id="program_to" name="to" class="mo-input" value="{{ request('to') }}" data-autosubmit>
                    </div>
                </div>

                <div class="mo-segmented" style="margin-top:10px;margin-bottom:6px;">
                    <button type="submit" name="sort" value="" class="mo-segmented-item {{ !in_array(request('sort'), ['donation'], true) ? 'active' : '' }}">
                        <i class="fas fa-clock"></i> Terbaru
                    </button>
                    <button type="submit" name="sort" value="donation" class="mo-segmented-item {{ in_array(request('sort'), ['donation'], true) ? 'active' : '' }}">
                        <i class="fas fa-arrow-down-wide-short"></i> Donasi Terbesar
                    </button>
                </div>

                @if(request('search') || $hasAdvancedFilter)
                    <div style="text-align:right;">
                        <a href="{{ route('mo.programs') }}" style="font-size:12px;color:var(--mo-muted);text-decoration:none;">
                            <i class="fas fa-rotate-left"></i> Reset filter
                        </a>
                    </div>
                @endif
            </div>
        </form>
    </div>

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
                    <div class="mo-progress-meta">
                        <span><strong>{{ $p->collected_formatted }}</strong> terkumpul</span>
                        <span>dari {{ $p->donation_count }} donasi</span>
                    </div>
                </div>
            </div>
        @empty
            <div class="mo-empty">
                <i class="fas fa-file-invoice-dollar"></i>
                <p>Belum ada program{{ request('search') ? ' sesuai pencarian' : ' aktif' }}.</p>
            </div>
        @endforelse
    </div>
</div>

<a href="{{ route('mo.program.create') }}" class="mo-fab" aria-label="Tambah Program">
    <i class="fas fa-plus"></i>
</a>
@endsection

@push('scripts')
<script>
    (function () {
        document.querySelectorAll('[data-autosubmit]').forEach(function (el) {
            el.addEventListener('change', function () {
                if (el.form) el.form.submit();
            });
        });
    })();
</script>
@endpush

@section('sheets')
@include('mobile.partials.program-donor-sheet')
@endsection
