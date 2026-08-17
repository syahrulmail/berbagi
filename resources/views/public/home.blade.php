@extends('layouts.public')

@section('title', 'Berbagi.or.id · Badan Wakaf Al Qur\'an')

@section('content')

<section class="hero">
    <div class="container hero-grid">
        <div>
            <span class="hero-eyebrow"><i class="fas fa-book-quran"></i> Wakaf Al Qur'an & Kemanusiaan</span>
            <h1>Wakaf untuk Ummat,<br>Dari Anda untuk <em>Kebaikan</em></h1>
            <p class="hero-sub">Badan Wakaf Al Qur'an (BWA) hadir menghimpun dan menyalurkan wakaf, infak, dan sedekah untuk program Al-Qur'an serta kemanusiaan di seluruh Nusantara.</p>
            <div class="hero-cta">
                <a href="#program" class="btn btn-primary"><i class="fas fa-arrow-down"></i> Lihat Program</a>
                <a href="https://wa.me/{{ $waNumber }}" target="_blank" rel="noopener" class="btn btn-light"><i class="fab fa-whatsapp"></i> Hubungi Kami</a>
            </div>
            <div class="hero-stats">
                <div class="hero-stat"><b>{{ $programs->count() }}</b><span>Program Aktif</span></div>
                <div class="hero-stat"><b>Rp {{ number_format($totalCollected, 0, ',', '.') }}</b><span>Total Terkumpul</span></div>
                <div class="hero-stat"><b>{{ $totalAgents }}</b><span>Mitra Agen</span></div>
            </div>
        </div>
        <div>
            <div class="banner-shell">
                @if($banners->count())
                <div class="slider" id="heroSlider">
                    <div class="slider-track" id="heroTrack">
                        @foreach($banners as $banner)
                        <div class="slider-slide">
                            @if($banner->url)<a href="{{ $banner->url }}" target="_blank" rel="noopener">@endif
                                <img src="{{ $banner->image_url }}" alt="{{ $banner->title }}">
                                <div class="slider-caption">
                                    <h3>{{ $banner->title }}</h3>
                                </div>
                            @if($banner->url)</a>@endif
                        </div>
                        @endforeach
                    </div>
                    <div class="slider-dots" id="heroDots"></div>
                </div>
                @else
                <div class="slider">
                    <div class="slider-slide">
                        <div style="width:100%;height:100%;display:grid;place-items:center;background:linear-gradient(135deg,var(--teal-700),var(--teal-500));color:#fff;font-size:56px;"><i class="fas fa-hand-holding-heart"></i></div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</section>

<main class="section" id="program">
    <div class="container">
        <div class="section-head">
            <div>
                <h2>Program Aktif</h2>
                <p class="muted">Pilih program wakaf yang ingin Anda dukung.</p>
            </div>
        </div>

        <div class="filter-bar">
            <div class="search-box">
                <i class="fas fa-magnifying-glass"></i>
                <input type="search" id="programSearch" placeholder="Cari program..." autocomplete="off">
            </div>
            <div class="filter-pills" id="filterPills">
                <button class="pill active" data-filter="semua">Semua</button>
                <button class="pill" data-filter="penggalangan" data-cat="penggalangan"><i class="fas fa-hand-holding-dollar"></i> Penggalangan</button>
                <button class="pill" data-filter="penyaluran" data-cat="penyaluran"><i class="fas fa-box-open"></i> Penyaluran</button>
                @foreach($tags as $tag)
                    <button class="pill" data-filter="tag:{{ strtolower($tag->name) }}">{{ $tag->name }}</button>
                @endforeach
            </div>
        </div>

        <div class="program-grid" id="programGrid">
            @forelse($programs as $program)
                @php
                    $collected = (float) ($program->total_collected ?? 0);
                    $goal = (float) $program->goal_amount;
                    $progress = $goal > 0 ? min(100, round(($collected / $goal) * 100, 1)) : 0;
                    $cat = $program->category ?? 'penggalangan';
                    $tags = $program->campaignTags->pluck('name')->map(fn($n) => strtolower($n))->join(',');
                    $waMsg = str_replace('{program}', $program->name, $waTemplate ?: 'Assalamualaikum, saya ingin berdonasi untuk program {program}');
                @endphp
                <article class="program-card" data-category="{{ $cat }}" data-tags="{{ $tags }}" data-query="{{ strtolower($program->name . ' ' . $program->description . ' ' . $program->campaignTags->pluck('name')->join(' ')) }}">
                    <a href="{{ route('public.program', $program->slug) }}" class="pc-media">
                        @if($program->image)
                            <img src="{{ $program->image_url }}" alt="{{ $program->name }}" loading="lazy">
                        @else
                            <div style="width:100%;height:100%;display:grid;place-items:center;background:linear-gradient(135deg,var(--teal-100),var(--teal-50));color:var(--teal-400);font-size:40px;"><i class="fas fa-book-quran"></i></div>
                        @endif
                        <span class="cat-badge {{ $cat }}">{{ $cat }}</span>
                    </a>
                    <div class="pc-body">
                        <div class="pc-tags">
                            @foreach($program->campaignTags as $tag)
                                <span class="tag-chip {{ $loop->first ? 'gold' : '' }}">{{ $tag->name }}</span>
                            @endforeach
                        </div>
                        <h3 class="pc-title"><a href="{{ route('public.program', $program->slug) }}">{{ $program->name }}</a></h3>
                        <p class="pc-desc">{{ $program->description }}</p>
                        <div class="progress">
                            <div class="progress-head">
                                <span>Terkumpul <strong>Rp {{ number_format($collected, 0, ',', '.') }}</strong></span>
                                <span>Target Rp {{ number_format($goal, 0, ',', '.') }}</span>
                            </div>
                            <div class="progress-track">
                                <div class="progress-fill" data-percent="{{ $progress }}"></div>
                            </div>
                        </div>
                        <div class="pc-foot">
                            <a href="{{ route('public.program', $program->slug) }}" class="btn btn-outline btn-sm">Detail</a>
                            <a href="https://wa.me/{{ $waNumber }}?text={{ urlencode($waMsg) }}" target="_blank" rel="noopener" class="btn btn-ghost-wa" aria-label="Donasi via WhatsApp"
                               data-wa-log data-wa-source="home" data-wa-program="{{ $program->id }}">
                                <i class="fab fa-whatsapp"></i>
                            </a>
                        </div>
                    </div>
                </article>
            @empty
                <div class="empty-state" style="grid-column:1/-1;">
                    <i class="fas fa-folder-open"></i>
                    <p>Belum ada program wakaf yang aktif.</p>
                </div>
            @endforelse
        </div>
    </div>
</main>
@endsection

@push('scripts')
<script>
(function () {
    var grid = document.getElementById('programGrid');
    var cards = Array.prototype.slice.call(grid.querySelectorAll('.program-card'));
    var pills = Array.prototype.slice.call(document.querySelectorAll('#filterPills .pill'));
    var search = document.getElementById('programSearch');

    var activeFilter = 'semua';

    function apply() {
        var q = (search.value || '').toLowerCase().trim();
        var visible = 0;
        cards.forEach(function (card) {
            var show = true;

            if (activeFilter !== 'semua') {
                if (activeFilter === 'penggalangan' || activeFilter === 'penyaluran') {
                    if (card.dataset.category !== activeFilter) show = false;
                } else if (activeFilter.indexOf('tag:') === 0) {
                    var t = activeFilter.slice(4);
                    var tags = (card.dataset.tags || '').split(',');
                    if (tags.indexOf(t) === -1) show = false;
                }
            }

            if (show && q && card.dataset.query.indexOf(q) === -1) show = false;

            card.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        if (visible === 0) {
            var empty = document.getElementById('gridEmpty');
            if (!empty) {
                empty = document.createElement('div');
                empty.id = 'gridEmpty';
                empty.className = 'empty-state';
                empty.style.gridColumn = '1/-1';
                empty.innerHTML = '<i class="fas fa-magnifying-glass"></i><p>Tidak ada program yang cocok.</p>';
                grid.appendChild(empty);
            }
            empty.style.display = '';
        } else {
            var ex = document.getElementById('gridEmpty');
            if (ex) ex.style.display = 'none';
        }
    }

    pills.forEach(function (pill) {
        pill.addEventListener('click', function () {
            pills.forEach(function (p) { p.classList.remove('active'); });
            pill.classList.add('active');
            activeFilter = pill.dataset.filter;
            apply();
        });
    });

    search.addEventListener('input', apply);
})();

(function () {
    var slider = document.getElementById('heroSlider');
    if (!slider) return;
    var track = document.getElementById('heroTrack');
    var dotsWrap = document.getElementById('heroDots');
    var slides = track.children.length;
    if (slides <= 1) return;

    var cur = 0;
    for (var i = 0; i < slides; i++) {
        var d = document.createElement('button');
        d.className = 'slider-dot' + (i === 0 ? ' active' : '');
        d.setAttribute('aria-label', 'Slide ' + (i + 1));
        d.addEventListener('click', (function (idx) { return function () { go(idx); restart(); }; })(i));
        dotsWrap.appendChild(d);
    }
    var dots = Array.prototype.slice.call(dotsWrap.children);

    function go(i) {
        cur = (i + slides) % slides;
        track.style.transform = 'translateX(-' + (cur * 100) + '%)';
        dots.forEach(function (d, x) { d.classList.toggle('active', x === cur); });
    }

    var timer = setInterval(function () { go(cur + 1); }, 5000);
    function restart() { clearInterval(timer); timer = setInterval(function () { go(cur + 1); }, 5000); }

    var startX = null;
    slider.addEventListener('touchstart', function (e) { startX = e.touches[0].clientX; }, { passive: true });
    slider.addEventListener('touchend', function (e) {
        if (startX === null) return;
        var dx = e.changedTouches[0].clientX - startX;
        if (Math.abs(dx) > 45) { go(cur + (dx < 0 ? 1 : -1)); restart(); }
        startX = null;
    }, { passive: true });
})();
</script>
@endpush
