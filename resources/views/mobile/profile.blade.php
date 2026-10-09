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
            <div class="mo-appbar-sub">Identitas, keamanan &amp; halaman publik</div>
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

            <div class="mo-field" style="margin-top:14px;margin-bottom:0;text-align:left;">
                <label>Link Halaman Profil Publik</label>
                <a href="{{ route('public.agent', $user->slug) }}" target="_blank" class="mo-btn mo-btn-ghost" style="width:100%;justify-content:flex-start;overflow:hidden;">
                    <i class="fas fa-link"></i>
                    <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">berbagi.or.id/cs/{{ $user->slug }}</span>
                </a>
            </div>
        </div>

        <div class="mo-form-card">
            <h3 class="mo-form-card-title"><i class="fas fa-comment-dots"></i> Teks Sambutan</h3>
            <div class="mo-field" style="margin-bottom:0;">
                <textarea id="intro" name="intro" class="mo-textarea" rows="4" maxlength="500"
                          placeholder="Assalamualaikum, saya siap membantu Anda menyalurkan wakaf, infak, dan sedekah...">{{ old('intro', $profile['intro'] ?? '') }}</textarea>
                <small style="color:var(--mo-muted);">Tampil di bawah nama Anda pada halaman publik. Kosongkan untuk memakai teks bawaan.</small>
                @error('intro')
                    <small style="color:var(--mo-danger);display:block;">{{ $message }}</small>
                @enderror
            </div>
        </div>

        <div class="mo-form-card">
            <h3 class="mo-form-card-title"><i class="fas fa-id-card"></i> Identitas</h3>
            <div class="mo-field">
                <label for="name">Nama Lengkap <span class="req">*</span></label>
                <input type="text" id="name" name="name" class="mo-input" value="{{ old('name', $user->name) }}" required>
                @error('name')
                    <small style="color:var(--mo-danger);display:block;">{{ $message }}</small>
                @enderror
            </div>
            <div class="mo-field">
                <label for="username">Username <span class="req">*</span></label>
                <input type="text" id="username" name="username" class="mo-input" value="{{ old('username', $user->username) }}" required>
                @error('username')
                    <small style="color:var(--mo-danger);display:block;">{{ $message }}</small>
                @enderror
            </div>
            <div class="mo-field">
                <label for="email">Email <span class="req">*</span></label>
                <input type="email" id="email" name="email" class="mo-input" value="{{ old('email', $user->email) }}" required>
                @error('email')
                    <small style="color:var(--mo-danger);display:block;">{{ $message }}</small>
                @enderror
            </div>
            <div class="mo-field" style="margin-bottom:0;">
                <label for="phone">No. Handphone</label>
                <input type="text" id="phone" name="phone" class="mo-input" value="{{ old('phone', $user->phone) }}" placeholder="08xxxxxxxxxx">
                @error('phone')
                    <small style="color:var(--mo-danger);display:block;">{{ $message }}</small>
                @enderror
            </div>
        </div>

        <div class="mo-form-card">
            <h3 class="mo-form-card-title"><i class="fas fa-lock"></i> Keamanan</h3>
            <div class="mo-field">
                <label for="password">Password Baru</label>
                <input type="password" id="password" name="password" class="mo-input" minlength="8" placeholder="Kosongkan jika tidak diubah">
                @error('password')
                    <small style="color:var(--mo-danger);display:block;">{{ $message }}</small>
                @enderror
            </div>
            <div class="mo-field" style="margin-bottom:0;">
                <label for="password_confirmation">Konfirmasi Password Baru</label>
                <input type="password" id="password_confirmation" name="password_confirmation" class="mo-input" minlength="8">
            </div>
        </div>

        <div class="mo-form-card">
            <h3 class="mo-form-card-title"><i class="fas fa-plug"></i> Integrasi</h3>
            <div class="mo-field">
                <label for="api_ss">API SS</label>
                <input type="text" id="api_ss" name="api_ss" class="mo-input" value="{{ old('api_ss', $profile['api_ss'] ?? '') }}" autocomplete="off">
                @error('api_ss')
                    <small style="color:var(--mo-danger);display:block;">{{ $message }}</small>
                @enderror
            </div>
            <div class="mo-field" style="margin-bottom:0;">
                <label for="api_cc">API CC</label>
                <input type="text" id="api_cc" name="api_cc" class="mo-input" value="{{ old('api_cc', $profile['api_cc'] ?? '') }}" autocomplete="off">
                @error('api_cc')
                    <small style="color:var(--mo-danger);display:block;">{{ $message }}</small>
                @enderror
            </div>
        </div>

        <div class="mo-form-card">
            <h3 class="mo-form-card-title"><i class="fas fa-shield-halved"></i> Peran &amp; Status</h3>
            <div class="mo-readonly-row">
                <span class="k">Peran</span>
                <span class="v">{{ $user->roleLabel() }}</span>
            </div>
            <div class="mo-readonly-row">
                <span class="k">Cabang</span>
                <span class="v">{{ $user->branch->name ?? '-' }}</span>
            </div>
            <div class="mo-readonly-row">
                <span class="k">Status</span>
                <span class="v">{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</span>
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
