@extends('mobile.layouts.app')

@section('title', $editUser ? 'Edit Pengguna' : 'Tambah Pengguna')

@section('mobile-content')
<div class="mo-appbar">
    <div class="mo-appbar-row">
        <a href="{{ route('mo.users') }}" class="mo-appbar-back" aria-label="Kembali">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div style="flex:1;min-width:0;">
            <h1 class="mo-appbar-title"><i class="fas fa-users" style="color:var(--mo-primary);font-size:20px;"></i> {{ $editUser ? 'Edit Pengguna' : 'Tambah Pengguna' }}</h1>
            <div class="mo-appbar-sub">{{ $editUser ? 'Perbarui data pengguna' : 'Buat akun pengguna baru' }}</div>
        </div>
    </div>
</div>

<div class="mo-content" style="padding-top:0;">
    <form method="POST" action="{{ $editUser ? route('mo.user.update', $editUser->id) : route('mo.user.store') }}" class="mo-form" enctype="multipart/form-data">
        @csrf
        @if($editUser)
            @method('PUT')
        @endif

        <div class="mo-form-card">
            <h3 class="mo-form-card-title"><i class="fas fa-user"></i> Identitas</h3>

            <div class="mo-field">
                <label for="name">Nama Lengkap <span class="req">*</span></label>
                <input type="text" id="name" name="name" class="mo-input" value="{{ old('name', $editUser->name ?? '') }}" required placeholder="Nama lengkap pengguna">
            </div>

            <div class="mo-field">
                <label for="username">Username <span class="req">*</span></label>
                <input type="text" id="username" name="username" class="mo-input" value="{{ old('username', $editUser->username ?? '') }}" required placeholder="nama.pengguna">
            </div>

            <div class="mo-field">
                <label for="email">Email <span class="req">*</span></label>
                <input type="email" id="email" name="email" class="mo-input" value="{{ old('email', $editUser->email ?? '') }}" required placeholder="nama@email.com">
            </div>

            <div class="mo-field">
                <label for="phone">No. Handphone <span class="req">*</span></label>
                <input type="tel" id="phone" name="phone" class="mo-input" value="{{ old('phone', $editUser->phone ?? '') }}" required placeholder="628xxxxxxx">
            </div>
        </div>

        @php $photoUrl = asset_photo_url($profile['photo'] ?? ''); @endphp
        <div class="mo-form-card">
            <h3 class="mo-form-card-title"><i class="fas fa-camera"></i> Foto Profil</h3>

            <div style="text-align:center;">
                <div class="mo-avatar" style="width:88px;height:88px;font-size:34px;margin:2px auto 12px;">
                    <img id="moUserPhotoPreview" src="{{ $photoUrl }}" alt="Foto profil {{ $editUser->name ?? '' }}"
                         style="width:100%;height:100%;object-fit:cover;border-radius:50%;{{ $photoUrl === '' ? 'display:none;' : '' }}">
                    <span id="moUserPhotoPlaceholder" style="{{ $photoUrl !== '' ? 'display:none;' : '' }}">{{ strtoupper(substr($editUser->name ?? '?', 0, 1)) }}</span>
                </div>

                <input type="file" id="moUserPhoto" name="photo" accept="image/jpeg,image/png,image/webp" style="display:none;">
                <input type="hidden" name="existing_photo" value="{{ $profile['photo'] ?? '' }}">
                <input type="hidden" name="photo_remove" id="moUserPhotoRemove" value="0">

                <label for="moUserPhoto" class="mo-btn mo-btn-ghost" style="display:inline-flex;width:auto;">
                    <i class="fas fa-camera"></i> Pilih Foto
                </label>
                <button type="button" id="moUserPhotoDelete" class="mo-btn mo-btn-ghost" style="display:{{ $photoUrl !== '' ? 'inline-flex' : 'none' }};width:auto;">
                    <i class="fas fa-trash-can"></i> Hapus
                </button>

                <div style="font-size:11px;color:var(--mo-muted);margin-top:10px;">JPG/PNG/WebP maks. 2MB.</div>
                @error('photo')
                    <small style="color:var(--mo-danger);display:block;margin-top:4px;">{{ $message }}</small>
                @enderror
            </div>
        </div>

        <div class="mo-form-card">
            <h3 class="mo-form-card-title"><i class="fas fa-key"></i> Keamanan</h3>

            <div class="mo-field">
                <label for="password">{{ $editUser ? 'Password Baru' : 'Password' }} @if(!$editUser)<span class="req">*</span>@endif</label>
                <input type="password" id="password" name="password" class="mo-input" {{ $editUser ? '' : 'required' }} placeholder="{{ $editUser ? 'Kosongkan bila tidak diubah' : 'Minimal 8 karakter' }}" autocomplete="new-password">
            </div>

            <div class="mo-field" style="margin-bottom:0;">
                <label for="password_confirmation">{{ $editUser ? 'Konfirmasi Password Baru' : 'Konfirmasi Password' }} @if(!$editUser)<span class="req">*</span>@endif</label>
                <input type="password" id="password_confirmation" name="password_confirmation" class="mo-input" {{ $editUser ? '' : 'required' }} placeholder="Ulangi password" autocomplete="new-password">
            </div>
        </div>

        <div class="mo-form-card">
            <h3 class="mo-form-card-title"><i class="fas fa-user-tag"></i> Peran &amp; Status</h3>

            <div class="mo-field">
                <label for="role">Peran <span class="req">*</span></label>
                <select id="role" name="role" class="mo-select">
                    @if(auth()->user()->isAdmin())
                        <option value="admin" {{ old('role', $editUser->role ?? '') == 'admin' ? 'selected' : '' }}>Admin Super</option>
                        <option value="supervisor" {{ old('role', $editUser->role ?? '') == 'supervisor' ? 'selected' : '' }}>Supervisor</option>
                        <option value="agen" {{ old('role', $editUser->role ?? '') == 'agen' ? 'selected' : '' }}>Agen</option>
                        <option value="donatur" {{ old('role', $editUser->role ?? '') == 'donatur' ? 'selected' : '' }}>Donatur</option>
                    @else
                        <option value="agen" selected>Agen</option>
                    @endif
                </select>
            </div>

            <div class="mo-field">
                <label for="branch_id">Cabang</label>
                <select id="branch_id" name="branch_id" class="mo-select">
                    <option value="">— Pilih Cabang —</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ old('branch_id', $editUser->branch_id ?? (auth()->user()->isSupervisor() ? auth()->user()->branch_id : '')) == $branch->id ? 'selected' : '' }}>
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mo-switch" style="margin-bottom:0;">
                <div>
                    <div class="lbl">Aktif</div>
                    <div class="sub">Pengguna dapat login ke sistem</div>
                </div>
                <input type="checkbox" name="is_active" value="1" id="mo-is-active" {{ old('is_active', $editUser->is_active ?? true) ? 'checked' : '' }}>
                <label class="track" for="mo-is-active"></label>
            </div>
        </div>

        <div class="mo-form-footer">
            <a href="{{ route('mo.users') }}" class="mo-btn mo-btn-ghost">Batal</a>
            <button type="submit" class="mo-btn mo-btn-primary"><i class="fas fa-save"></i> {{ $editUser ? 'Simpan Perubahan' : 'Simpan Pengguna' }}</button>
        </div>
    </form>

    @if($editUser)
        <div class="mo-form-card" style="margin-top:2px;background:#fff8f7;box-shadow:none;border:1.5px solid #f6d8d4;">
            <h3 class="mo-form-card-title" style="color:var(--mo-danger);"><i class="fas fa-triangle-exclamation"></i> Zona Berbahaya</h3>
            <form method="POST" action="{{ route('mo.user.destroy', $editUser->id) }}" onsubmit="return confirm('Hapus pengguna ini? Tindakan tidak dapat dibatalkan.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="mo-btn mo-btn-danger mo-btn-block"><i class="fas fa-trash-can"></i> Hapus Pengguna</button>
            </form>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    (function () {
        var input = document.getElementById('moUserPhoto');
        if (!input) return;

        var preview = document.getElementById('moUserPhotoPreview');
        var placeholder = document.getElementById('moUserPhotoPlaceholder');
        var removeField = document.getElementById('moUserPhotoRemove');
        var deleteBtn = document.getElementById('moUserPhotoDelete');

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
