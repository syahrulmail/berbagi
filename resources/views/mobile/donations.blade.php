@extends('mobile.layouts.app')

@section('title', 'Donasi')

@section('mobile-content')
<div class="mo-appbar">
    <div class="mo-appbar-row">
        <div style="flex:1;min-width:0;">
            <h1 class="mo-appbar-title"><i class="fas fa-hand-holding-dollar" style="color:var(--mo-primary);font-size:20px;"></i> Donasi</h1>
            <div class="mo-appbar-sub">{{ $donations->count() }} catatan donasi</div>
        </div>
        <button type="button" class="mo-icon-btn" id="mo-open-download" aria-label="Unduh Donasi">
            <i class="fas fa-download"></i>
        </button>
        <a href="{{ route('mo.more') }}" class="mo-icon-btn" aria-label="Menu">
            <i class="fas fa-bars"></i>
        </a>
    </div>
</div>

<div class="mo-content" style="padding-top:0;">
    @php
        $hasDateFilter = request('period') || request('from') || request('to');
    @endphp
    <form method="GET" action="{{ route('mo.donations') }}" id="mo-donasi-form">
        <div class="mo-donasi-sticky">
            <div class="mo-search-flex">
                <div class="mo-search">
                    <i class="fas fa-magnifying-glass"></i>
                    <input type="search" name="search" placeholder="Cari nama / no. WA / catatan / program..." value="{{ request('search') }}">
                </div>
                <button type="button" class="mo-filter-toggle {{ $hasDateFilter ? 'has-filter' : '' }}" id="mo-filter-toggle" aria-label="Filter tanggal" aria-expanded="false">
                    <i class="fas fa-sliders"></i>
                </button>
            </div>

            <div id="mo-advanced-filters" hidden>
                <div style="display:flex;gap:10px;margin-top:10px;">
                    <div style="flex:1;">
                        <label for="filter_from" style="display:block;font-size:11px;color:var(--mo-muted);margin-bottom:4px;">Dari tanggal</label>
                        <input type="date" id="filter_from" name="from" class="mo-input" value="{{ request('from') }}" data-autosubmit>
                    </div>
                    <div style="flex:1;">
                        <label for="filter_to" style="display:block;font-size:11px;color:var(--mo-muted);margin-bottom:4px;">Sampai tanggal</label>
                        <input type="date" id="filter_to" name="to" class="mo-input" value="{{ request('to') }}" data-autosubmit>
                    </div>
                </div>

                <div class="mo-segmented" style="margin-top:10px;margin-bottom:6px;">
                    <button type="submit" name="period" value="" class="mo-segmented-item {{ !request('period') ? 'active' : '' }}">Semua</button>
                    <button type="submit" name="period" value="today" class="mo-segmented-item {{ request('period') === 'today' ? 'active' : '' }}">Hari Ini</button>
                    <button type="submit" name="period" value="week" class="mo-segmented-item {{ request('period') === 'week' ? 'active' : '' }}">7 Hari</button>
                </div>

                @if(request('search') || $hasDateFilter)
                    <div style="text-align:right;">
                        <a href="{{ route('mo.donations') }}" style="font-size:12px;color:var(--mo-muted);text-decoration:none;">
                            <i class="fas fa-rotate-left"></i> Reset filter
                        </a>
                    </div>
                @endif
            </div>
        </div>
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
                    @if($d->note)
                        <div class="mo-row-note"><i class="fas fa-note-sticky"></i> {{ $d->note }}</div>
                    @endif
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

<div class="mo-sheet-backdrop" data-for="mo-download-sheet"></div>
<div class="mo-sheet" id="mo-download-sheet" aria-hidden="true">
    <div class="mo-sheet-handle"></div>
    <div class="mo-sheet-head">
        <h3 class="mo-sheet-title"><i class="fas fa-download" style="color:var(--mo-primary);margin-right:6px;"></i>Unduh Donasi</h3>
        <button type="button" class="mo-sheet-close" aria-label="Tutup"><i class="fas fa-xmark"></i></button>
    </div>
    <div class="mo-sheet-body">
        <form method="GET" id="mo-download-form">
            <div class="mo-field">
                <label>Periode</label>
                <div style="display:flex;gap:10px;">
                    <div style="flex:1;">
                        <input type="date" name="from" class="mo-input" value="{{ request('from') }}">
                    </div>
                    <div style="flex:1;">
                        <input type="date" name="to" class="mo-input" value="{{ request('to') }}">
                    </div>
                </div>
                <div class="mo-form-help">Kosongkan untuk semua tanggal.</div>
            </div>

            @if(isset($downloadBranches) && $downloadBranches->count())
                <div class="mo-field">
                    <label>Cabang</label>
                    @if($downloadBranches->count() > 1)
                        <div style="display:flex;flex-direction:column;gap:8px;background:#f6faf9;border-radius:12px;padding:12px 14px;">
                            @foreach($downloadBranches as $b)
                                <label style="display:flex;align-items:center;gap:9px;font-size:13px;">
                                    <input type="checkbox" name="branch_ids[]" value="{{ $b->id }}">
                                    {{ $b->name }}
                                </label>
                            @endforeach
                        </div>
                        <div class="mo-form-help">Tanpa pilih cabang = semua cabang yang dapat Anda akses.</div>
                    @else
                        @foreach($downloadBranches as $b)
                            <input type="hidden" name="branch_ids[]" value="{{ $b->id }}">
                            <div style="font-size:13px;font-weight:600;color:var(--mo-text);">{{ $b->name }}</div>
                        @endforeach
                    @endif
                </div>
            @endif

            <div style="display:flex;gap:10px;margin-top:6px;">
                <button type="submit" class="mo-btn mo-btn-primary" style="flex:1;" formaction="{{ route('donations.download') }}">
                    <i class="fas fa-file-excel"></i> Excel
                </button>
                <button type="submit" class="mo-btn mo-btn-primary" style="flex:1;" formaction="{{ route('donations.download-proof') }}">
                    <i class="fas fa-file-word"></i> Download BT
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        var opener = document.getElementById('mo-open-download');
        if (opener) opener.addEventListener('click', function () { window.MoApp.sheets.open('mo-download-sheet'); });

        var filterToggle = document.getElementById('mo-filter-toggle');
        var advanced = document.getElementById('mo-advanced-filters');
        if (filterToggle && advanced) {
            filterToggle.addEventListener('click', function () {
                var opening = advanced.hasAttribute('hidden');
                if (opening) {
                    advanced.removeAttribute('hidden');
                } else {
                    advanced.setAttribute('hidden', '');
                }
                filterToggle.classList.toggle('open', opening);
                filterToggle.setAttribute('aria-expanded', opening ? 'true' : 'false');
            });
        }

        document.querySelectorAll('[data-autosubmit]').forEach(function (el) {
            el.addEventListener('change', function () {
                if (el.form) el.form.submit();
            });
        });
    })();
</script>
@endpush
