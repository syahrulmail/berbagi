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
    <a href="{{ route('mo.profile') }}" class="mo-profile-card" style="text-decoration:none;color:#fff;" aria-label="Buka halaman profil">
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
            @php
                $ssStatus = $apiStatus['ss'] ?? 'empty';
                $ccStatus = $apiStatus['cc'] ?? 'empty';
                $dotColor = ['ok' => '#22c55e', 'fail' => '#ef4444', 'empty' => '#cbd5e1'];
                $dotGlow = [
                    'ok' => '0 0 0 2px rgba(34,197,94,.25), 0 0 5px rgba(34,197,94,.85)',
                    'fail' => '0 0 0 2px rgba(239,68,68,.25), 0 0 5px rgba(239,68,68,.85)',
                    'empty' => 'none',
                ];
                $dotLabel = ['ok' => 'Terkoneksi', 'fail' => 'Tidak terkoneksi', 'empty' => 'Belum diisi'];
            @endphp
            <div style="display:inline-flex;align-items:center;gap:6px;margin-top:6px;"
                 title="API SS: {{ $dotLabel[$ssStatus] ?? '' }} / API CC: {{ $dotLabel[$ccStatus] ?? '' }}">
                <span style="width:10px;height:10px;border-radius:50%;display:inline-block;background:{{ $dotColor[$ssStatus] ?? '#cbd5e1' }};box-shadow:{{ $dotGlow[$ssStatus] ?? 'none' }};"></span>
                <span style="width:10px;height:10px;border-radius:50%;display:inline-block;background:{{ $dotColor[$ccStatus] ?? '#cbd5e1' }};box-shadow:{{ $dotGlow[$ccStatus] ?? 'none' }};"></span>
            </div>
        </div>
        <i class="fas fa-chevron-right" style="opacity:.6;"></i>
    </a>

    {{-- Halaman profil publik (paling atas) --}}
    @php
        $publicProfileUrl = ($user->isAgen() || $user->isSupervisor()) && $user->slug
            ? route('public.agent', ['slug' => $user->slug])
            : route('home');
    @endphp
    <div class="mo-menu">
        <a href="{{ $publicProfileUrl }}" target="_blank" rel="noopener" class="mo-menu-item">
            <i class="fas fa-globe mi blue"></i>
            <div class="txt">Lihat Halaman Profil Publik</div>
            <i class="fas fa-external-link chev"></i>
        </a>
    </div>

    <div class="mo-section-title">Komunikasi</div>
    <div class="mo-menu">
        <a href="{{ route('mo.whatsapp') }}" class="mo-menu-item">
            <i class="fab fa-whatsapp mi" style="background:#e5f8ec;color:#25d366;"></i>
            <div class="txt">Follow-up WA</div>
            <i class="fas fa-chevron-right chev"></i>
        </a>
        <a href="{{ route('mo.followups') }}" class="mo-menu-item">
            <i class="fas fa-comments mi blue"></i>
            <div class="txt">Traffic</div>
            <i class="fas fa-chevron-right chev"></i>
        </a>
    </div>

    @if($user->isAdmin() || $user->isSupervisor())
        <div class="mo-section-title">Manajemen</div>
        <div class="mo-menu">
            @if($user->isAdmin())
                <a href="{{ route('mo.branches') }}" class="mo-menu-item">
                    <i class="fas fa-building mi"></i>
                    <div class="txt">Cabang</div>
                    <i class="fas fa-chevron-right chev"></i>
                </a>
            @endif
            <a href="{{ route('mo.users') }}" class="mo-menu-item">
                <i class="fas fa-users mi blue"></i>
                <div class="txt">Pengguna</div>
                <i class="fas fa-chevron-right chev"></i>
            </a>
            @if($user->isAdmin())
                <a href="{{ route('mo.campaign-tags') }}" class="mo-menu-item">
                    <i class="fas fa-tags mi gold"></i>
                    <div class="txt">Label Kampanye</div>
                    <i class="fas fa-chevron-right chev"></i>
                </a>
                <a href="{{ route('mo.achievements') }}" class="mo-menu-item">
                    <i class="fas fa-trophy mi gold"></i>
                    <div class="txt">Pencapaian</div>
                    <i class="fas fa-chevron-right chev"></i>
                </a>
            @endif
        </div>
    @endif

    @if($user->isAdmin() || $user->isSupervisor())
        <div class="mo-section-title">Konten &amp; Monitoring</div>
        <div class="mo-menu">
            @if($user->isAdmin())
                <a href="{{ route('mo.banners') }}" class="mo-menu-item">
                    <i class="fas fa-images mi"></i>
                    <div class="txt">Banner &amp; Label</div>
                    <i class="fas fa-chevron-right chev"></i>
                </a>
            @endif
            <a href="{{ route('mo.activity-logs') }}" class="mo-menu-item">
                <i class="fas fa-clock-rotate-left mi blue"></i>
                <div class="txt">Log Aktivitas</div>
                <i class="fas fa-chevron-right chev"></i>
            </a>
        </div>
    @endif

    <div class="mo-section-title">Akun</div>
    <div class="mo-menu">
        <form method="POST" action="{{ route('logout', ['next' => 'mo']) }}" style="margin:0;">
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
