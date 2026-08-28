@extends('layouts.app')

@section('title', 'Profil Saya')

@push('styles')
<style>
    .t-file {
        font-size: 12px;
        width: 100%;
        margin-top: 8px;
    }
    .profile-photo-wrap {
        display: flex;
        align-items: center;
        gap: 14px;
        margin-bottom: 4px;
    }
    .profile-photo-preview {
        width: 96px;
        height: 96px;
        object-fit: cover;
        border-radius: 14px;
        border: 1px solid var(--gray-300);
        background: #fff;
    }
    .profile-photo-placeholder {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 96px;
        height: 96px;
        border-radius: 14px;
        background: linear-gradient(135deg, #08a899, #086e66);
        color: #fff;
        font-size: 34px;
        font-weight: 700;
        border: 1px solid var(--gray-300);
    }
    .inline-check {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 13px;
        color: var(--gray-700);
        cursor: pointer;
        margin-top: 8px;
    }
</style>
@endpush

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
            <div class="profile-photo-wrap">
                @php
                    $photoUrl = asset_photo_url($profile['photo'] ?? '');
                @endphp
                <img id="profilePhotoPreview" src="{{ $photoUrl }}"
                     alt="Foto profil {{ $user->name }}"
                     class="profile-photo-preview" style="{{ $photoUrl === '' ? 'display:none;' : '' }}">
                <div id="profilePhotoPlaceholder" class="profile-photo-placeholder"
                     style="{{ $photoUrl !== '' ? 'display:none;' : '' }}">
                    {{ strtoupper(mb_substr($user->name, 0, 1)) }}
                </div>
            </div>
            <input type="file" id="profilePhoto" name="photo" accept="image/jpeg,image/png,image/webp" class="t-file">
            <input type="hidden" name="existing_photo" value="{{ $profile['photo'] ?? '' }}">
            <label class="inline-check">
                <input type="checkbox" name="photo_remove" value="1"> Hapus foto profil
            </label>
            <small style="color: var(--gray-500); display:block; margin-top:4px;">JPG/PNG/WebP maks. 2MB.</small>
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

@push('scripts')
<script>
(function () {
    var fileInput = document.getElementById('profilePhoto');
    var preview = document.getElementById('profilePhotoPreview');
    var placeholder = document.getElementById('profilePhotoPlaceholder');

    if (!fileInput) return;

    fileInput.addEventListener('change', function () {
        var file = fileInput.files && fileInput.files[0];
        if (!file) return;

        var reader = new FileReader();
        reader.onload = function (e) {
            preview.src = e.target.result;
            preview.style.display = '';
            placeholder.style.display = 'none';
        };
        reader.readAsDataURL(file);
    });

    var removeCheck = document.querySelector('input[name="photo_remove"]');
    if (removeCheck) {
        removeCheck.addEventListener('change', function () {
            if (removeCheck.checked) {
                preview.style.display = 'none';
                placeholder.style.display = '';
                fileInput.value = '';
            } else {
                preview.style.display = preview.src ? '' : 'none';
                placeholder.style.display = preview.src ? 'none' : '';
            }
        });
    }
})();
</script>
@endpush
@endsection
