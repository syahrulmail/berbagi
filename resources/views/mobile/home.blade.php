@extends('mobile.layouts.app')

@section('title', 'Beranda')

@section('mobile-content')
<div class="mo-appbar">
    <div class="mo-appbar-row">
        <div class="mo-avatar">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
        <div style="flex:1;min-width:0;">
            <div style="font-size:12px;color:var(--mo-muted);">{{ $greeting }},</div>
            <h1 class="mo-appbar-title" style="font-size:18px;">{{ $user->name }}</h1>
        </div>
        <a href="{{ route('mo.more') }}" class="mo-icon-btn" aria-label="Menu">
            <i class="fas fa-bars"></i>
        </a>
    </div>
</div>

<div class="mo-content" style="padding-top:0;">
    {{-- Hero ringkasan --}}
    <div class="mo-hero">
        <form method="GET" action="{{ route('mo.dashboard') }}" id="mo-dash-form">
            @if($isAdmin)
                <div class="mo-hero-filter">
                    <button type="button" class="mo-hero-filter-toggle {{ !empty($selectedBranches) ? 'open' : '' }}"
                            data-filter-toggle="mo-branch-panel"
                            aria-expanded="{{ !empty($selectedBranches) ? 'true' : 'false' }}" aria-label="Pilih cabang">
                        <i class="fas fa-code-branch"></i>
                        <span>{{ $branchSummary }}</span>
                        <i class="fas fa-chevron-down chev"></i>
                    </button>
                    <div class="mo-hero-filter-panel" id="mo-branch-panel" @if(empty($selectedBranches)) hidden @endif>
                        <label class="mo-hero-check">
                            <input type="checkbox" id="mo-branch-all" {{ empty($selectedBranches) ? 'checked' : '' }}>
                            <span>Semua Cabang</span>
                        </label>
                        @foreach($branches as $branch)
                            <label class="mo-hero-check">
                                <input type="checkbox" name="branches[]" value="{{ $branch->id }}"
                                    {{ in_array($branch->id, $selectedBranches, true) ? 'checked' : '' }}>
                                <span>{{ $branch->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="mo-hero-filter-static">
                    <i class="fas fa-code-branch"></i>
                    <span>{{ $branchSummary }}</span>
                    <i class="fas fa-lock lock"></i>
                </div>
            @endif

            <div class="mo-hero-period">
                <input type="date" name="from" class="mo-hero-date" value="{{ $from }}" aria-label="Dari tanggal" onchange="this.form.submit()">
                <span class="mo-hero-period-sep">–</span>
                <input type="date" name="to" class="mo-hero-date" value="{{ $to }}" aria-label="Sampai tanggal" onchange="this.form.submit()">
            </div>
        </form>

        <div class="mo-hero-label">Total Donasi Tercatat</div>
        <div class="mo-hero-amount mo-hero-amount--sm">Rp {{ number_format((int) $totalRecorded, 0, ',', '.') }}</div>
        <div class="mo-hero-sub">{{ number_format($totalTransactions, 0, ',', '.') }} Transaksi dari {{ number_format($totalDonors, 0, ',', '.') }} Donatur (seluruh data tercatat)</div>

        <div class="mo-hero-divider"></div>

        <div class="mo-hero-label">Total Donasi Periode</div>
        <div class="mo-hero-amount">Rp {{ number_format((int) $periodTotal, 0, ',', '.') }}</div>
        <div class="mo-hero-sub">{{ number_format($periodTransactions, 0, ',', '.') }} Transaksi dari {{ number_format($periodDonors, 0, ',', '.') }} Donatur ({{ $periodLabel }})</div>
        <div class="mo-hero-sub" style="margin-top:6px;">
            @if($growthPercent >= 0)
                <i class="fas fa-arrow-trend-up"></i> Naik {{ abs($growthPercent) }}%
            @else
                <i class="fas fa-arrow-trend-down"></i> Turun {{ abs($growthPercent) }}%
            @endif
            vs periode sebelumnya
        </div>
        @if($totalTarget > 0)
            <div class="mo-hero-progress">
                <div class="mo-hero-progress-fill" style="width: {{ min(100, $overallProgress) }}%"></div>
            </div>
            <div class="mo-hero-sub" style="margin-top:7px;">
                {{ $overallProgress }}% dari target Rp {{ number_format((int) $totalTarget, 0, ',', '.') }}
            </div>
        @endif
    </div>

    {{-- Total donasi hari ini --}}
    <div class="mo-card mo-today-card">
        <div class="mo-today-label"><i class="fas fa-wallet"></i> Total Donasi Hari Ini</div>
        <div class="mo-today-amount">Rp {{ number_format((int) $todayTotal, 0, ',', '.') }}</div>
        <div class="mo-today-sub">{{ number_format($todayTransactions, 0, ',', '.') }} Transaksi dari {{ number_format($donorsToday, 0, ',', '.') }} Donatur (Hari ini)</div>
    </div>

    {{-- Tren periode --}}
    <div class="mo-card">
        <div class="mo-card-head mo-card-head--collapse">
            <h2 class="mo-card-title"><i class="fas fa-chart-column"></i> Tren Periode</h2>
            <div class="mo-card-head-actions">
                <a href="{{ route('mo.donations') }}" class="mo-card-link">Semua</a>
                <button type="button" class="mo-collapse-btn" data-filter-toggle="mo-trend-body" aria-expanded="false" aria-label="Tampilkan tren">
                    <i class="fas fa-chevron-down mo-collapse-chevron"></i>
                </button>
            </div>
        </div>
        <div id="mo-trend-body" class="mo-collapse-body" hidden>
            <div class="mo-trend-h">
                @foreach($trend as $t)
                    <div class="mo-trend-h-row {{ $t['is_weekend'] ? 'is-weekend' : '' }}">
                        <div class="mo-trend-h-label">{{ $t['label'] }}</div>
                        <div class="mo-trend-h-track">
                            <div class="mo-trend-h-bar"
                                 style="width: {{ $t['value'] > 0 ? max(4, round(($t['value'] / $trendMax) * 100)) : 0 }}%"
                                 title="Rp {{ number_format($t['value'], 0, ',', '.') }}"></div>
                        </div>
                        <div class="mo-trend-h-value">{{ $t['value'] > 0 ? number_format($t['value'], 0, ',', '.') : '-' }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Donasi terbaru --}}
    <div class="mo-card mo-card--list">
        <div class="mo-card-head mo-card-head--collapse">
            <h2 class="mo-card-title"><i class="fas fa-clock-rotate-left"></i> Donasi Terbaru</h2>
            <div class="mo-card-head-actions">
                <a href="{{ route('mo.donations') }}" class="mo-card-link">Semua</a>
                <button type="button" class="mo-collapse-btn" data-filter-toggle="mo-recent-body" aria-expanded="false" aria-label="Tampilkan donasi terbaru">
                    <i class="fas fa-chevron-down mo-collapse-chevron"></i>
                </button>
            </div>
        </div>
        <div id="mo-recent-body" class="mo-collapse-body mo-list" hidden>
            @forelse($recentDonations as $d)
                <div class="mo-row" data-donation-detail="{{ $d->id }}">
                    <div class="mo-row-icon"><i class="fas fa-hand-holding-dollar"></i></div>
                    <div class="mo-row-body">
                        <div class="mo-row-title">{{ $d->contact->name ?? ($d->donor_info ?: 'Donatur') }}</div>
                        <div class="mo-row-sub">{{ $d->program_label }}</div>
                    </div>
                    <div class="mo-row-end">
                        <div class="amount">{{ $d->amount_formatted }}</div>
                        <div class="date">{{ $d->date_formatted }}</div>
                    </div>
                </div>
            @empty
                <div class="mo-empty">
                    <i class="fas fa-hand-holding-dollar"></i>
                    <p>Belum ada donasi tercatat.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>

<a href="{{ route('mo.donations') }}" class="mo-fab" title="Catat Donasi" data-href="{{ route('mo.donation.create') }}" aria-label="Catat Donasi">
    <i class="fas fa-plus"></i>
</a>
@endsection

@section('sheets')
@include('mobile.partials.donation-sheet')
@endsection

@if($isAdmin)
@push('scripts')
<script>
    (function () {
        var form = document.getElementById('mo-dash-form');
        if (!form) return;

        var all = document.getElementById('mo-branch-all');
        var boxes = Array.prototype.slice.call(form.querySelectorAll('input[name="branches[]"]'));

        function submit() { form.submit(); }

        if (all) {
            all.addEventListener('change', function () {
                if (all.checked) {
                    boxes.forEach(function (b) { b.checked = false; });
                }
                submit();
            });
        }

        boxes.forEach(function (b) {
            b.addEventListener('change', function () {
                if (b.checked && all) all.checked = false;
                submit();
            });
        });
    })();
</script>
@endpush
@endif
