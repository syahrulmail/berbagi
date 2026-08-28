@php
    $photoUrl = $photoUrl ?? '';
    $existingPhoto = $existingPhoto ?? '';
@endphp
<div class="profile-photo-wrap">
    <img id="profilePhotoPreview" src="{{ $photoUrl }}" alt="Foto profil {{ $user->name }}"
         class="profile-photo-preview" style="{{ $photoUrl === '' ? 'display:none;' : '' }}">
    <div id="profilePhotoPlaceholder" class="profile-photo-placeholder"
         style="{{ $photoUrl !== '' ? 'display:none;' : '' }}">
        {{ strtoupper(mb_substr($user->name, 0, 1)) }}
    </div>
</div>
<input type="file" id="profilePhoto" name="photo" accept="image/jpeg,image/png,image/webp" class="t-file">
<input type="hidden" name="existing_photo" value="{{ $existingPhoto }}">
<label class="inline-check">
    <input type="checkbox" name="photo_remove" value="1"> Hapus foto profil
</label>
<small style="color: var(--gray-500); display:block; margin-top:4px;">JPG/PNG/WebP maks. 2MB.</small>
