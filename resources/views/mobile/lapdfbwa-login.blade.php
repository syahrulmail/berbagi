@extends('mobile.layouts.public')

@section('title', 'Laporan DFBWA')

@section('mobile-content')
<div style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;">
    <form method="POST" action="{{ route('lapdfbwa.auth') }}"
          style="width:100%;max-width:360px;background:#fff;border-radius:20px;padding:28px 22px;box-shadow:0 12px 40px rgba(0,0,0,.08);">
        @csrf
        <div style="text-align:center;margin-bottom:20px;">
            <div style="width:60px;height:60px;margin:0 auto 14px;border-radius:50%;background:#eefaf8;color:var(--mo-primary);display:flex;align-items:center;justify-content:center;font-size:24px;">
                <i class="fas fa-lock"></i>
            </div>
            <h1 style="font-size:19px;font-weight:800;color:var(--mo-text);margin:0 0 6px;">Laporan DFBWA</h1>
            <p style="font-size:12.5px;color:var(--mo-muted);margin:0;">Masukkan password untuk melihat laporan.</p>
        </div>

        @if(session('error'))
            <div style="background:#fdeeec;color:#b91c1c;font-size:12.5px;padding:10px 12px;border-radius:12px;margin-bottom:14px;">
                <i class="fas fa-circle-exclamation"></i> {{ session('error') }}
            </div>
        @endif

        @unless($passwordSet)
            <div style="background:#fff7ed;color:#9a3412;font-size:12.5px;padding:10px 12px;border-radius:12px;margin-bottom:14px;">
                <i class="fas fa-triangle-exclamation"></i> Password laporan belum diatur oleh admin.
            </div>
        @endunless

        <div class="mo-field">
            <label for="lapdfbwa_password">Password</label>
            <input type="password" id="lapdfbwa_password" name="password" class="mo-input" placeholder="Masukkan password" required autofocus>
        </div>

        <button type="submit" class="mo-btn mo-btn-primary" style="width:100%;margin-top:6px;">
            <i class="fas fa-arrow-right-to-bracket"></i> Masuk
        </button>
    </form>
</div>
@endsection
