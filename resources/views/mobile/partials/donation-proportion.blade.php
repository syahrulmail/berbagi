@php
    $items = $donationProportion['items'] ?? [];
    $total = (float) ($donationProportion['total'] ?? 0);
    $palette = ['#2563eb', '#16a34a', '#f59e0b', '#ef4444', '#8b5cf6', '#0891b2', '#db2777', '#64748b'];

    $stops = [];
    $cursor = 0;
    foreach ($items as $index => $item) {
        $ratio = $total > 0 ? ((float) $item['total'] / $total) * 100 : 0;
        $start = $cursor;
        $cursor += $ratio;
        $stops[] = $palette[$index % count($palette)] . ' ' . round($start, 2) . '% ' . round($cursor, 2) . '%';
    }
    $gradient = $stops
        ? 'conic-gradient(' . implode(', ', $stops) . ')'
        : 'conic-gradient(#e2e8f0 0% 100%)';
@endphp

@push('styles')
<style>
    .mo-proporsi-wrap { display: flex; flex-direction: column; align-items: center; gap: 14px; }
    .mo-proporsi-donut {
        position: relative; width: 132px; height: 132px; border-radius: 50%;
        background: {{ $gradient }}; flex: 0 0 auto;
    }
    .mo-proporsi-donut::after {
        content: ''; position: absolute; inset: 22px; border-radius: 50%;
        background: #fff;
    }
    .mo-proporsi-center {
        position: absolute; inset: 22px; display: flex; flex-direction: column;
        align-items: center; justify-content: center; text-align: center; z-index: 1;
    }
    .mo-proporsi-center-label { font-size: 10px; color: var(--mo-muted); text-transform: uppercase; letter-spacing: .4px; }
    .mo-proporsi-center-value { font-size: 12px; font-weight: 700; line-height: 1.25; margin-top: 2px; }
    .mo-proporsi-legend { width: 100%; }
    .mo-proporsi-item { display: flex; align-items: center; gap: 8px; padding: 4px 0; }
    .mo-proporsi-dot { width: 10px; height: 10px; border-radius: 50%; flex: 0 0 auto; }
    .mo-proporsi-name {
        flex: 1; min-width: 0; font-size: 12px; white-space: nowrap;
        overflow: hidden; text-overflow: ellipsis;
    }
    .mo-proporsi-value { font-size: 12px; font-weight: 700; text-align: right; flex: 0 0 auto; }
    .mo-proporsi-amount { font-size: 10px; color: var(--mo-muted); font-weight: 500; display: block; }
    .mo-proporsi-empty { padding: 8px 0; font-size: 12px; color: var(--mo-muted); }
</style>
@endpush

<div class="mo-card">
    <div class="mo-card-head mo-card-head--collapse">
        <h2 class="mo-card-title"><i class="fas fa-chart-pie"></i> Proporsi Donasi</h2>
        <div class="mo-card-head-actions">
            <span class="mo-card-link">{{ $donationProportion['label'] ?? 'Per Cabang' }}</span>
            <button type="button" class="mo-collapse-btn" data-filter-toggle="mo-proporsi-body"
                    aria-expanded="false" aria-label="Tampilkan proporsi donasi">
                <i class="fas fa-chevron-down mo-collapse-chevron"></i>
            </button>
        </div>
    </div>
    <div id="mo-proporsi-body" class="mo-collapse-body" hidden>
        @if(empty($items))
            <div class="mo-proporsi-empty">Belum ada donasi pada periode ini.</div>
        @else
            <div class="mo-proporsi-wrap">
                <div class="mo-proporsi-donut" role="img" aria-label="Proporsi donasi {{ $donationProportion['label'] ?? '' }}">
                    <div class="mo-proporsi-center">
                        <span class="mo-proporsi-center-label">Total</span>
                        <span class="mo-proporsi-center-value">{{ $donationProportion['total_formatted'] }}</span>
                    </div>
                </div>
                <div class="mo-proporsi-legend">
                    @foreach($items as $index => $item)
                        <div class="mo-proporsi-item">
                            <span class="mo-proporsi-dot" style="background: {{ $palette[$index % count($palette)] }};"></span>
                            <span class="mo-proporsi-name" title="{{ $item['name'] }}">{{ $item['name'] }}</span>
                            <span class="mo-proporsi-value">
                                {{ rtrim(rtrim(number_format((float) $item['percent'], 1, ',', '.'), '0'), ',') }}%
                                <span class="mo-proporsi-amount">{{ $item['total_formatted'] }}</span>
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
