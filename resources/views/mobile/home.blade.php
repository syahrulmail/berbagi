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
    {{-- Filter sticky: cabang + periode --}}
    <form method="GET" action="{{ route('mo.dashboard') }}" id="mo-dash-form" class="mo-dash-filters">
        @if($isAdmin)
            <div class="mo-hero-filter">
                <button type="button" class="mo-hero-filter-toggle"
                        data-filter-toggle="mo-branch-panel"
                        aria-expanded="false" aria-label="Pilih cabang">
                    <i class="fas fa-code-branch"></i>
                    <span>{{ $branchSummary }}</span>
                    <i class="fas fa-chevron-down chev"></i>
                </button>
                <div class="mo-hero-filter-panel" id="mo-branch-panel" hidden>
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

        <div class="mo-hero-filter">
            <button type="button" class="mo-hero-filter-toggle"
                    data-filter-toggle="mo-period-panel"
                    aria-expanded="false" aria-label="Pilih periode">
                <i class="fas fa-calendar-days"></i>
                <span>{{ $periodLabel }}</span>
                <i class="fas fa-chevron-down chev"></i>
            </button>
            <div class="mo-period-panel" id="mo-period-panel" hidden>
                <div class="mo-hero-period">
                    <span class="mo-date-field">
                        <input type="date" name="from" class="mo-hero-date" value="{{ $from }}" aria-label="Dari tanggal" onchange="this.form.submit()">
                        <i class="fas fa-calendar-days" aria-hidden="true"></i>
                    </span>
                    <span class="mo-hero-period-sep">–</span>
                    <span class="mo-date-field">
                        <input type="date" name="to" class="mo-hero-date" value="{{ $to }}" aria-label="Sampai tanggal" onchange="this.form.submit()">
                        <i class="fas fa-calendar-days" aria-hidden="true"></i>
                    </span>
                </div>
                @php
                    $branchQuery = !empty($selectedBranches) ? ['branches' => $selectedBranches] : [];
                    $periodTabs = [
                        ['key' => 'all', 'label' => 'Semua'],
                        ['key' => '7d', 'label' => '7 Hari'],
                        ['key' => 'month', 'label' => 'Bulan ini'],
                        ['key' => 'year', 'label' => 'Tahun ini'],
                    ];
                @endphp
                <div class="mo-period-tabs">
                    @foreach($periodTabs as $tab)
                        <a href="{{ route('mo.dashboard', array_merge(['range' => $tab['key']], $branchQuery)) }}"
                           class="mo-period-tab {{ $activeRange === $tab['key'] ? 'active' : '' }}">{{ $tab['label'] }}</a>
                    @endforeach
                </div>
            </div>
        </div>
    </form>

    {{-- Hero ringkasan --}}
    <div class="mo-hero">
        <div class="mo-hero-label">Total Donasi Tercatat</div>
        <div class="mo-hero-note">(Seluruh data tercatat)</div>
        <div class="mo-hero-amount mo-hero-amount--sm">Rp {{ number_format((int) $totalRecorded, 0, ',', '.') }}</div>
        <div class="mo-hero-sub">{{ number_format($totalTransactions, 0, ',', '.') }} Transaksi dari {{ number_format($totalDonors, 0, ',', '.') }} Donatur</div>

        <div class="mo-hero-divider"></div>

        <div class="mo-hero-label">Total Donasi Periode</div>
        <div class="mo-hero-note">({{ $periodLabel }})</div>
        <div class="mo-hero-amount">Rp {{ number_format((int) $periodTotal, 0, ',', '.') }}</div>
        <div class="mo-hero-sub">{{ number_format($periodTransactions, 0, ',', '.') }} Transaksi dari {{ number_format($periodDonors, 0, ',', '.') }} Donatur</div>
        @if(! $periodIsAll)
            <div class="mo-hero-sub" style="margin-top:6px;">
                @if($growthPercent >= 0)
                    <i class="fas fa-arrow-trend-up"></i> Naik {{ abs($growthPercent) }}%
                @else
                    <i class="fas fa-arrow-trend-down"></i> Turun {{ abs($growthPercent) }}%
                @endif
                vs periode sebelumnya
            </div>
        @endif
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
        <div class="mo-today-label"><i class="fas fa-wallet"></i> Total Donasi Hari Ini {{ $branchSummary }}</div>
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

    {{-- Tertinggi - Kontak --}}
    <div class="mo-card mo-card--list">
        <div class="mo-card-head mo-card-head--collapse">
            <h2 class="mo-card-title"><i class="fas fa-trophy"></i> Tertinggi - Kontak</h2>
            <div class="mo-card-head-actions">
                <a href="{{ route('mo.contacts') }}" class="mo-card-link">Semua</a>
                <button type="button" class="mo-collapse-btn" data-filter-toggle="mo-top-contact-body" aria-expanded="false" aria-label="Tampilkan kontak tertinggi">
                    <i class="fas fa-chevron-down mo-collapse-chevron"></i>
                </button>
            </div>
        </div>
        <div id="mo-top-contact-body" class="mo-collapse-body mo-list" hidden>
            @forelse($topContacts as $c)
                <div class="mo-row" data-contact-detail="{{ $c->contact_id }}">
                    <div class="mo-row-icon gold">{{ $c->initial }}</div>
                    <div class="mo-row-body">
                        <div class="mo-row-title">{{ $c->name }}</div>
                        <div class="mo-row-sub">{{ $c->phone }}</div>
                    </div>
                    <div class="mo-row-end">
                        <div class="amount">{{ $c->total_formatted }}</div>
                        <div class="date">{{ number_format($c->transactions, 0, ',', '.') }}x donasi</div>
                    </div>
                </div>
            @empty
                <div class="mo-empty">
                    <i class="fas fa-users"></i>
                    <p>Belum ada donasi pada periode ini.</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Tertinggi - Program --}}
    <div class="mo-card mo-card--list">
        <div class="mo-card-head mo-card-head--collapse">
            <h2 class="mo-card-title"><i class="fas fa-trophy"></i> Tertinggi - Program</h2>
            <div class="mo-card-head-actions">
                <a href="{{ route('mo.programs') }}" class="mo-card-link">Semua</a>
                <button type="button" class="mo-collapse-btn" data-filter-toggle="mo-top-program-body" aria-expanded="false" aria-label="Tampilkan program tertinggi">
                    <i class="fas fa-chevron-down mo-collapse-chevron"></i>
                </button>
            </div>
        </div>
        <div id="mo-top-program-body" class="mo-collapse-body mo-list" hidden>
            @forelse($topPrograms as $p)
                <div class="mo-row">
                    <div class="mo-row-icon"><i class="fas fa-bullseye"></i></div>
                    <div class="mo-row-body">
                        <div class="mo-row-title">{{ $p->name }}</div>
                        <div class="mo-row-sub">{{ $p->category_label ?: '-' }}</div>
                    </div>
                    <div class="mo-row-end">
                        <div class="amount">{{ $p->total_formatted }}</div>
                        <div class="date">{{ number_format($p->transactions, 0, ',', '.') }}x donasi</div>
                    </div>
                </div>
            @empty
                <div class="mo-empty">
                    <i class="fas fa-bullseye"></i>
                    <p>Belum ada donasi pada periode ini.</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Tertinggi - Cabang --}}
    <div class="mo-card mo-card--list">
        <div class="mo-card-head mo-card-head--collapse">
            <h2 class="mo-card-title"><i class="fas fa-trophy"></i> Tertinggi - Cabang</h2>
            <div class="mo-card-head-actions">
                <button type="button" class="mo-collapse-btn" data-filter-toggle="mo-top-branch-body" aria-expanded="false" aria-label="Tampilkan cabang tertinggi">
                    <i class="fas fa-chevron-down mo-collapse-chevron"></i>
                </button>
            </div>
        </div>
        <div id="mo-top-branch-body" class="mo-collapse-body mo-list" hidden>
            @forelse($topBranches as $b)
                <div class="mo-row">
                    <div class="mo-row-icon blue"><i class="fas fa-code-branch"></i></div>
                    <div class="mo-row-body">
                        <div class="mo-row-title">{{ $b->name }}</div>
                        <div class="mo-row-sub">{{ number_format($b->transactions, 0, ',', '.') }}x donasi</div>
                    </div>
                    <div class="mo-row-end">
                        <div class="amount">{{ $b->total_formatted }}</div>
                    </div>
                </div>
            @empty
                <div class="mo-empty">
                    <i class="fas fa-code-branch"></i>
                    <p>Belum ada donasi pada periode ini.</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Tertinggi - Agent --}}
    <div class="mo-card mo-card--list">
        <div class="mo-card-head mo-card-head--collapse">
            <h2 class="mo-card-title"><i class="fas fa-trophy"></i> Tertinggi - Agent</h2>
            <div class="mo-card-head-actions">
                <button type="button" class="mo-collapse-btn" data-filter-toggle="mo-top-agent-body" aria-expanded="false" aria-label="Tampilkan agen tertinggi">
                    <i class="fas fa-chevron-down mo-collapse-chevron"></i>
                </button>
            </div>
        </div>
        <div id="mo-top-agent-body" class="mo-collapse-body mo-list" hidden>
            @forelse($topAgents as $a)
                <div class="mo-row">
                    <div class="mo-row-icon gold">{{ $a->initial }}</div>
                    <div class="mo-row-body">
                        <div class="mo-row-title">{{ $a->name }}</div>
                        <div class="mo-row-sub">{{ $a->branch_name ?: '-' }}</div>
                    </div>
                    <div class="mo-row-end">
                        <div class="amount">{{ $a->total_formatted }}</div>
                        <div class="date">{{ number_format($a->transactions, 0, ',', '.') }}x donasi</div>
                    </div>
                </div>
            @empty
                <div class="mo-empty">
                    <i class="fas fa-user-tie"></i>
                    <p>Belum ada donasi pada periode ini.</p>
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
@include('mobile.partials.contact-sheet')
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
