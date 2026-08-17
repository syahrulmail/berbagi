<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Berbagi.or.id · Platform Wakaf BWA</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>
        .public-nav {
            background: rgba(255,255,255,0.06);
            padding: 14px 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .public-nav .container {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .public-nav-brand {
            color: #fff;
            font-weight: 700;
            font-size: 18px;
        }
        .public-nav-brand i { color: var(--accent); margin-right: 8px; }
        .public-nav a.btn { color: #fff; border-color: rgba(255,255,255,0.4); }
        .public-header { padding: 60px 0 90px; }
        .program-meta { display: flex; justify-content: space-between; align-items: center; margin-top: 14px; }
    </style>
</head>
<body class="public-body">
<div class="public-header">
    <nav class="public-nav">
        <div class="container">
            <div class="public-nav-brand"><i class="fas fa-hands-helping"></i> Berbagi.or.id</div>
            <div>
                <a href="{{ route('login') }}" class="btn btn-sm"><i class="fas fa-right-to-bracket"></i> Masuk Admin</a>
            </div>
        </div>
    </nav>
    <div class="container">
        <h1>Wakaf untuk Ummat, <br>Dari Anda untuk Kebaikan</h1>
        <p>Badan Wakaf Al Quran (BWA) — mari bersama-sama menyebarkan kebaikan melalui program wakaf Al-Quran, mushaf, dan dukungan para da'i di seluruh Nusantara.</p>
    </div>
</div>

<main class="container public-programs">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:18px;">
        <h2 style="font-size:20px; color:var(--primary);">Program Wakaf Aktif</h2>
    </div>

    @forelse($programs as $program)
        @php
            $progress = $program->goal_amount > 0 ? min(100, round(((float) $program->total_collected / (float) $program->goal_amount) * 100, 1)) : 0;
        @endphp
        <div class="program-card">
            <div>
                @foreach($program->campaignTags as $tag)
                    <span class="tag-pill" style="background: {{ $tag->color }}">{{ $tag->name }}</span>
                @endforeach
            </div>
            <h3 style="margin-top:12px;">{{ $program->name }}</h3>
            <p style="color: var(--gray-500);">{{ $program->description }}</p>
            <div style="margin-top:14px;">
                <div style="display:flex; justify-content:space-between; font-size:13px; margin-bottom:6px;">
                    <span>Terkumpul <strong>Rp {{ number_format($program->total_collected, 0, ',', '.') }}</strong></span>
                    <span>Target Rp {{ number_format($program->goal_amount, 0, ',', '.') }}</span>
                </div>
                <div class="progress-track"><div class="progress-fill" style="width: {{ $progress }}%"></div></div>
            </div>
            <div class="program-meta">
                <span style="color: var(--gray-500); font-size:13px;">{{ $progress }}% terkumpul</span>
                <a href="{{ route('public.program', $program->slug) }}" class="btn btn-primary btn-sm">Lihat Detail</a>
            </div>
        </div>
    @empty
        <div class="card empty-state">
            <i class="fas fa-folder-open"></i>
            <p>Belum ada program wakaf yang aktif.</p>
        </div>
    @endforelse
</main>

<footer class="public-footer">
    <div class="container">
        &copy; {{ date('Y') }} Badan Wakaf Al Quran (BWA) · berbagi.or.id
    </div>
</footer>
</body>
</html>
