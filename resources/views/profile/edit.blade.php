@extends('layouts.app')

@section('title', 'Profil Saya')

@include('partials.photo-upload-styles')

@section('content')
<div class="page-header">
    <div>
        <h1><i class="fas fa-user-circle"></i> Profil Saya</h1>
        <p class="subtitle">Foto profil dan sambutan yang tampil di halaman publik Anda (berbagi.or.id/cs/{{ $user->slug }}).</p>
    </div>
</div>

<div class="card" style="max-width: 640px;">
    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

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
            <label for="intro">Teks Sambutan</label>
            <textarea id="intro" name="intro" rows="4" maxlength="500"
                      placeholder="Assalamualaikum, saya siap membantu Anda menyalurkan wakaf, infak, dan sedekah melalui program-program BWA. Insya Allah amanah dan tepat sasaran.">{{ old('intro', $profile['intro'] ?? '') }}</textarea>
            <small style="color: var(--gray-500);">Sambutan yang tampil di bawah nama Anda pada halaman publik. Kosongkan untuk memakai teks bawaan.</small>
            @error('intro')
                <small style="color: var(--danger);">{{ $message }}</small>
            @enderror
        </div>

        <div class="form-actions" style="margin-top: 20px;">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Profil</button>
        </div>
    </form>
</div>

@include('partials.photo-upload-script')
@endsection
