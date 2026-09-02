<!DOCTYPE html>
<html lang="id" class="mobile-app">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow, noarchive, nosnippet">
    <meta name="theme-color" content="#086e66">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>Masuk · Berbagi Mobile</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fira+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="{{ assetv('css/mobile.css') }}">
</head>
<body class="mo-login-body">
    <div class="mo-login-card">
        <div class="mo-login-brand">
            <div class="mo-login-logo">BWA</div>
            <h1>Berbagi Mobile</h1>
            <p>Badan Wakaf Al Qur'an — Masuk ke akun Anda</p>
        </div>

        @if($errors->any())
            <div class="mo-login-alert">
                <i class="fas fa-circle-exclamation"></i> {{ $errors->first() }}
            </div>
        @endif

        @if(session('status'))
            <div class="mo-login-alert" style="background:#e5f7ec;color:#1f8a4c;">
                <i class="fas fa-circle-check"></i> {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('mo.login') }}" autocomplete="on">
            @csrf
            <div class="mo-login-field">
                <label for="login">Email atau Username</label>
                <div class="mo-login-input-wrap">
                    <i class="fas fa-user"></i>
                    <input type="text" id="login" name="login" value="{{ old('login') }}" placeholder="admin" required autofocus>
                </div>
            </div>
            <div class="mo-login-field">
                <label for="password">Password</label>
                <div class="mo-login-input-wrap">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" placeholder="••••••••" required>
                </div>
            </div>
            <label class="mo-login-remember">
                <input type="checkbox" name="remember" value="1">
                <span>Ingat saya</span>
            </label>
            <button type="submit" class="mo-login-btn">
                <i class="fas fa-right-to-bracket"></i> Masuk
            </button>
        </form>
    </div>

    <div class="mo-login-footer">
        <a href="{{ route('home') }}"><i class="fas fa-globe"></i> Lihat situs publik</a>
    </div>
</body>
</html>
