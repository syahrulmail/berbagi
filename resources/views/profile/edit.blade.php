@extends('layouts.app')

@section('title', 'Profil Saya')

@include('partials.photo-upload-styles')

@section('content')
<div class="page-header">
    <div>
        <h1><i class="fas fa-user-circle"></i> Profil Saya</h1>
        <p class="subtitle">Kelola identitas, keamanan, foto, dan sambutan halaman publik Anda.</p>
    </div>
</div>

<form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <div class="card" style="max-width: 720px;">
        <div class="form-group">
            <label>Foto Profil</label>
            @php
                $photoUrl = asset_photo_url($profile['photo'] ?? '');
                $existingPhoto = $profile['photo'] ?? '';
            @endphp
            @include('partials.photo-upload')
            @error('photo')
                <small style="color: var(--danger);">{{ $message }}</small>
            @enderror
        </div>

        <div class="form-group">
            <label>Link Halaman Profil Publik</label>
            <div class="input-group">
                <input type="text" value="{{ url('/cs/' . $user->slug) }}" readonly style="flex:1;background:var(--gray-100);">
                <a href="{{ route('public.agent', $user->slug) }}" target="_blank" class="btn btn-sm"><i class="fas fa-external-link"></i> Buka</a>
            </div>
            <small style="color: var(--gray-500);">Halaman publik Anda: <code>berbagi.or.id/cs/{{ $user->slug }}</code></small>
        </div>

        <div class="form-group" style="margin-bottom: 0;">
            <label for="intro">Teks Sambutan</label>
            <textarea id="intro" name="intro" rows="4" maxlength="500"
                      placeholder="Assalamualaikum, saya siap membantu Anda menyalurkan wakaf, infak, dan sedekah melalui program-program BWA. Insya Allah amanah dan tepat sasaran.">{{ old('intro', $profile['intro'] ?? '') }}</textarea>
            <small style="color: var(--gray-500);">Sambutan yang tampil di bawah nama Anda pada halaman publik. Kosongkan untuk memakai teks bawaan.</small>
            @error('intro')
                <small style="color: var(--danger);">{{ $message }}</small>
            @enderror
        </div>
    </div>

    <div class="card" style="max-width: 720px;">
        <h2 class="card-title"><i class="fas fa-id-card" style="color: var(--primary);"></i> Identitas</h2>
        <div class="form-group">
            <label for="name">Nama Lengkap *</label>
            <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required>
            @error('name')
                <small style="color: var(--danger);">{{ $message }}</small>
            @enderror
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="username">Username *</label>
                <input type="text" id="username" name="username" value="{{ old('username', $user->username) }}" required>
                @error('username')
                    <small style="color: var(--danger);">{{ $message }}</small>
                @enderror
            </div>
            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required>
                @error('email')
                    <small style="color: var(--danger);">{{ $message }}</small>
                @enderror
            </div>
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <label for="phone">No. Handphone</label>
            <input type="text" id="phone" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="08xxxxxxxxxx">
            @error('phone')
                <small style="color: var(--danger);">{{ $message }}</small>
            @enderror
        </div>
    </div>

    <div class="card" style="max-width: 720px;">
        <h2 class="card-title"><i class="fas fa-lock" style="color: var(--primary);"></i> Keamanan</h2>
        <div class="form-row">
            <div class="form-group">
                <label for="password">Password Baru</label>
                <input type="password" id="password" name="password" minlength="8" placeholder="Kosongkan jika tidak diubah">
                @error('password')
                    <small style="color: var(--danger);">{{ $message }}</small>
                @enderror
            </div>
            <div class="form-group">
                <label for="password_confirmation">Konfirmasi Password Baru</label>
                <input type="password" id="password_confirmation" name="password_confirmation" minlength="8">
            </div>
        </div>
        <small style="color: var(--gray-500);">Biarkan kosong jika tidak ingin mengubah password.</small>
    </div>

    <div class="card" style="max-width: 720px;">
        <h2 class="card-title"><i class="fas fa-plug" style="color: var(--primary);"></i> Integrasi</h2>
        <div class="form-group">
            <label for="api_ss">API SS</label>
            <input type="text" id="api_ss" name="api_ss" value="{{ old('api_ss', $profile['api_ss'] ?? '') }}" autocomplete="off">
            @error('api_ss')
                <small style="color: var(--danger);">{{ $message }}</small>
            @enderror
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <label for="api_cc">API CC</label>
            <input type="text" id="api_cc" name="api_cc" value="{{ old('api_cc', $profile['api_cc'] ?? '') }}" autocomplete="off">
            @error('api_cc')
                <small style="color: var(--danger);">{{ $message }}</small>
            @enderror
        </div>
    </div>

    <div class="card" style="max-width: 720px;">
        <h2 class="card-title"><i class="fas fa-shield-halved" style="color: var(--primary);"></i> Peran &amp; Status</h2>
        <div class="form-row">
            <div class="form-group">
                <label>Peran</label>
                <div style="font-weight: 600;">{{ $user->roleLabel() }}</div>
            </div>
            <div class="form-group">
                <label>Cabang</label>
                <div style="font-weight: 600;">{{ $user->branch->name ?? '-' }}</div>
            </div>
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <label>Status</label>
            <div style="font-weight: 600;">{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</div>
        </div>
    </div>

    <div class="form-actions" style="max-width: 720px;">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
    </div>
</form>

@include('partials.photo-upload-script')
@endsection
