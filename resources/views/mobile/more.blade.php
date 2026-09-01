@extends('mobile.layouts.app')

@section('title', 'Lainnya')

@section('mobile-content')
<div class="mo-appbar">
    <div class="mo-appbar-row">
        <div style="flex:1;min-width:0;">
            <h1 class="mo-appbar-title"><i class="fas fa-ellipsis" style="color:var(--mo-primary);font-size:20px;"></i> Lainnya</h1>
            <div class="mo-appbar-sub">Akun &amp; pengaturan</div>
        </div>
    </div>
</div>

<div class="mo-content" style="padding-top:0;">
    {{-- Profil --}}
    <div class="mo-profile-card">
        <div class="mo-avatar">
            @if(!empty($profile['photo']) && $profile['photo'])
                <img src="{{ asset_photo_url($profile['photo']) }}" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
            @else
                {{ strtoupper(substr($user->name, 0, 1)) }}
            @endif
        </div>
        <div style="flex:1;min-width:0;">
            <h2 class="mo-profile-name">{{ $user->name }}</h2>
            <div class="mo-profile-role">
                <i class="fas fa-user-tag"></i> {{ $user->roleLabel() }}
                @if($user->branch)
                    · {{ $user->branch->name }}
                @endif
            </div>
        </div>
        <i class="fas fa-chevron-right" style="opacity:.6;"></i>
    </div>

    {{-- Menu utama --}}
    <div class="mo-menu">
        <a href="{{ route('mo.programs') }}" class="mo-menu-item">
            <i class="fas fa-file-invoice-dollar mi"></i>
            <div class="txt">Program Donasi</div>
            <i class="fas fa-chevron-right chev"></i>
        </a>
        <a href="{{ route('mo.contacts') }}" class="mo-menu-item">
            <i class="fas fa-address-book mi blue"></i>
            <div class="txt">Manajemen Kontak</div>
            <i class="fas fa-chevron-right chev"></i>
        </a>
        <a href="{{ route('mo.donation.create') }}" class="mo-menu-item">
            <i class="fas fa-hand-holding-dollar mi gold"></i>
            <div class="txt">Catat Donasi</div>
            <i class="fas fa-chevron-right chev"></i>
        </a>
    </div>

    @if($user->isAdmin())
        <div class="mo-section-title">Manajemen</div>
        <div class="mo-menu">
            <a href="{{ route('mo.branches') }}" class="mo-menu-item">
                <i class="fas fa-building mi"></i>
                <div class="txt">Cabang</div>
                <i class="fas fa-chevron-right chev"></i>
            </a>
            <a href="{{ route('mo.users') }}" class="mo-menu-item">
                <i class="fas fa-users mi blue"></i>
                <div class="txt">Pengguna</div>
                <i class="fas fa-chevron-right chev"></i>
            </a>
        </div>
    @endif

    <div class="mo-section-title">Akun</div>
    <div class="mo-menu">
        <a href="{{ route('profile.edit') }}" class="mo-menu-item">
            <i class="fas fa-user-pen mi"></i>
            <div class="txt">Profil Saya</div>
            <i class="fas fa-chevron-right chev"></i>
        </a>
        <a href="{{ route('home') }}" target="_blank" rel="noopener" class="mo-menu-item">
            <i class="fas fa-globe mi blue"></i>
            <div class="txt">Lihat Situs Publik</div>
            <i class="fas fa-external-link chev"></i>
        </a>
        <form method="POST" action="{{ route('logout') }}" style="margin:0;">
            @csrf
            <button type="submit" class="mo-menu-item" style="width:100%;border:none;background:none;font-family:inherit;text-align:left;">
                <i class="fas fa-right-from-bracket mi red"></i>
                <div class="txt" style="color:var(--mo-danger);">Keluar</div>
                <i class="fas fa-chevron-right chev"></i>
            </button>
        </form>
    </div>

    <div style="text-align:center;padding:10px 0 8px;">
        <img src="{{ asset('img/berbagi-logo.png') }}" alt="" style="height:24px;display:none;" onerror="this.style.display='none'">
        <div style="font-size:11px;color:#9db3b0;">Berbagi Mobile · v1.0</div>
        <div style="font-size:10.5px;color:#b5c6c3;margin-top:2px;">berbagi.or.id</div>
    </div>
</div>
@endsection
