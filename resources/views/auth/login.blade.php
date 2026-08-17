<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Berbagi') }} · Masuk</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="auth-body">
<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-brand">
            <i class="fas fa-hands-helping auth-brand-icon"></i>
            <h1>Berbagi.or.id</h1>
            <p>Sistem Manajemen Fundraising BWA</p>
        </div>

        @if($errors->any())
            <div class="alert alert-error">
                <i class="fas fa-circle-exclamation"></i> {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="auth-form">
            @csrf
            <div class="form-group">
                <label for="login">Email atau Username</label>
                <div class="input-icon">
                    <i class="fas fa-user"></i>
                    <input type="text" id="login" name="login" value="{{ old('login') }}" required autofocus>
                </div>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-icon">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" required>
                </div>
            </div>
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="remember" value="1"> Ingat saya
                </label>
            </div>
            <button type="submit" class="btn btn-primary btn-block">
                <i class="fas fa-right-to-bracket"></i> Masuk
            </button>
        </form>

        <div class="auth-footer">
            <a href="{{ route('home') }}"><i class="fas fa-globe"></i> Lihat situs publik</a>
        </div>
    </div>
</div>
</body>
</html>
