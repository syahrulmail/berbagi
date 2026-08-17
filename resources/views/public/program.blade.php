<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $program->name }} · Berbagi.or.id</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>
        .public-nav { background: var(--primary); padding: 14px 0; }
        .public-nav .container { display: flex; align-items: center; justify-content: space-between; }
        .public-nav-brand { color: #fff; font-weight: 700; font-size: 18px; }
        .public-nav-brand i { color: var(--accent); margin-right: 8px; }
        .public-nav a.btn { color: #fff; border-color: rgba(255,255,255,0.4); }
        .program-detail { padding: 40px 0 60px; }
        .detail-progress { margin: 24px 0; }
    </style>
</head>
<body class="public-body">
<nav class="public-nav">
    <div class="container">
        <div class="public-nav-brand"><i class="fas fa-hands-helping"></i> Berbagi.or.id</div>
        <div>
            <a href="{{ route('home') }}" class="btn btn-sm"><i class="fas fa-arrow-left"></i> Kembali</a>
        </div>
    </div>
</nav>

<main class="container program-detail">
    @php
        $progress = $program->goal_amount > 0 ? min(100, round(((float) $collected / (float) $program->goal_amount) * 100, 1)) : 0;
    @endphp

    <div class="card">
        <div>
            @foreach($program->campaignTags as $tag)
                <span class="tag-pill" style="background: {{ $tag->color }}">{{ $tag->name }}</span>
            @endforeach
        </div>
        <h1 style="font-size:26px; color:var(--primary); margin:14px 0 10px;">{{ $program->name }}</h1>
        <p style="color: var(--gray-500); line-height:1.8;">{{ $program->description }}</p>

        <div class="detail-progress">
            <div style="display:flex; justify-content:space-between; font-size:14px; margin-bottom:8px;">
                <span>Terkumpul <strong style="color:var(--accent-dark);">Rp {{ number_format($collected, 0, ',', '.') }}</strong></span>
                <span>Target Rp {{ number_format($program->goal_amount, 0, ',', '.') }}</span>
            </div>
            <div class="progress-track" style="height:14px;">
                <div class="progress-fill" style="width: {{ $progress }}%"></div>
            </div>
            <div style="text-align:right; font-size:13px; color:var(--gray-500); margin-top:6px;">{{ $progress }}% terkumpul</div>
        </div>

        <div class="card" style="background:var(--gray-100); border-style:dashed;">
            <div style="text-align:center;">
                <p style="margin-bottom:10px; color:var(--gray-700);"><i class="fas fa-circle-info"></i> Untuk berdonasi, silakan hubungi tim BWA melalui WhatsApp.</p>
                <a href="https://wa.me/6281234567890?text={{ urlencode('Saya ingin berdonasi untuk program: ' . $program->name) }}"
                   target="_blank" class="btn btn-accent">
                    <i class="fab fa-whatsapp"></i> Hubungi via WhatsApp
                </a>
            </div>
        </div>
    </div>
</main>

<footer class="public-footer">
    <div class="container">
        &copy; {{ date('Y') }} Badan Wakaf Al Quran (BWA) · berbagi.or.id
    </div>
</footer>
</body>
</html>
