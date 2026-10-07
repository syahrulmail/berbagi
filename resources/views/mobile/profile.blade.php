@extends('mobile.layouts.app')

@section('title', 'Profil Saya')

@section('mobile-content')
<div class="mo-appbar">
    <div class="mo-appbar-row">
        <a href="{{ route('mo.more') }}" class="mo-appbar-back" aria-label="Kembali">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div style="flex:1;min-width:0;">
            <h1 class="mo-appbar-title"><i class="fas fa-user-circle" style="color:var(--mo-primary);font-size:20px;"></i> Profil Saya</h1>
            <div class="mo-appbar-sub">Foto &amp; sambutan halaman publik</div>
        </div>
    </div>
</div>

<div class="mo-content" style="padding-top:0;">
    @php $photoUrl = asset_photo_url($profile['photo'] ?? ''); @endphp
    <form method="POST" action="{{ route('mo.profile.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="mo-form-card" style="text-align:center;">
            <div class="mo-avatar" style="width:96px;height:96px;font-size:36px;margin:2px auto 14px;">
                <img id="moProfilePreview" src="{{ $photoUrl }}" alt="Foto profil {{ $user->name }}"
                     style="width:100%;height:100%;object-fit:cover;border-radius:50%;{{ $photoUrl === '' ? 'display:none;' : '' }}">
                <span id="moProfilePlaceholder" style="{{ $photoUrl !== '' ? 'display:none;' : '' }}">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
            </div>

            <input type="file" id="moProfilePhoto" name="photo" accept="image/jpeg,image/png,image/webp" style="display:none;">
            <input type="hidden" name="existing_photo" value="{{ $profile['photo'] ?? '' }}">
            <input type="hidden" name="photo_remove" id="moProfilePhotoRemove" value="0">

            <label for="moProfilePhoto" class="mo-btn mo-btn-ghost" style="display:inline-flex;width:auto;">
                <i class="fas fa-camera"></i> Pilih Foto
            </label>
            <button type="button" id="moProfilePhotoDelete" class="mo-btn mo-btn-ghost" style="display:{{ $photoUrl !== '' ? 'inline-flex' : 'none' }};width:auto;">
                <i class="fas fa-trash-can"></i> Hapus
            </button>

            <div style="font-size:11px;color:var(--mo-muted);margin-top:10px;">JPG/PNG/WebP maks. 2MB.</div>
            @error('photo')
                <small style="color:var(--mo-danger);display:block;margin-top:4px;">{{ $message }}</small>
            @enderror
        </div>

        <div class="mo-form-card">
            <div class="mo-field" style="margin-bottom:0;">
                <label for="intro">Teks Sambutan</label>
                <textarea id="intro" name="intro" class="mo-textarea" rows="4" maxlength="500"
                          placeholder="Assalamualaikum, saya siap membantu Anda menyalurkan wakaf, infak, dan sedekah...">{{ old('intro', $profile['intro'] ?? '') }}</textarea>
                <small style="color:var(--mo-muted);">Tampil di bawah nama Anda pada halaman publik. Kosongkan untuk memakai teks bawaan.</small>
                @error('intro')
                    <small style="color:var(--mo-danger);display:block;">{{ $message }}</small>
                @enderror
            </div>
        </div>

        <div class="mo-form-footer mo-form-footer--static">
            <a href="{{ route('mo.more') }}" class="mo-btn mo-btn-ghost">Batal</a>
            <button type="submit" class="mo-btn mo-btn-primary"><i class="fas fa-save"></i> Simpan</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        var input = document.getElementById('moProfilePhoto');
        if (!input) return;

        var preview = document.getElementById('moProfilePreview');
        var placeholder = document.getElementById('moProfilePlaceholder');
        var removeField = document.getElementById('moProfilePhotoRemove');
        var deleteBtn = document.getElementById('moProfilePhotoDelete');

        input.addEventListener('change', function () {
            var file = input.files && input.files[0];
            if (!file) return;

            var reader = new FileReader();
            reader.onload = function (e) {
                preview.src = e.target.result;
                preview.style.display = '';
                placeholder.style.display = 'none';
                if (removeField) removeField.value = '0';
                if (deleteBtn) deleteBtn.style.display = 'inline-flex';
            };
            reader.readAsDataURL(file);
        });

        if (deleteBtn) {
            deleteBtn.addEventListener('click', function () {
                input.value = '';
                preview.removeAttribute('src');
                preview.style.display = 'none';
                placeholder.style.display = '';
                if (removeField) removeField.value = '1';
                deleteBtn.style.display = 'none';
            });
        }
    })();
</script>
@endpush
