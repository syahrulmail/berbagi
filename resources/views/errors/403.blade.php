<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 · Tidak Diizinkan</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>
        .error-page { min-height: 100vh; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); }
        .error-box { background: #fff; border-radius: 16px; padding: 48px; text-align: center; max-width: 440px; box-shadow: 0 24px 64px rgba(0,0,0,0.3); }
        .error-box h1 { font-size: 56px; color: var(--primary); }
        .error-box p { color: var(--gray-500); margin: 12px 0 24px; }
    </style>
</head>
<body>
<div class="error-page">
    <div class="error-box">
        <h1>403</h1>
        <p>Maaf, Anda tidak memiliki izin untuk mengakses halaman ini.</p>
        <a href="{{ route('dashboard') }}" class="btn btn-primary"><i class="fas fa-arrow-left"></i> Kembali ke Dashboard</a>
    </div>
</div>
</body>
</html>
