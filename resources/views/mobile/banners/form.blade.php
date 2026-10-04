@extends('mobile.layouts.app')

@section('title', $banner ? 'Edit Banner' : 'Tambah Banner')

@section('mobile-content')
<div class="mo-appbar">
    <div class="mo-appbar-row">
        <a href="{{ route('mo.banners') }}" class="mo-appbar-back" aria-label="Kembali"><i class="fas fa-arrow-left"></i></a>
        <div style="flex:1;min-width:0;">
            <h1 class="mo-appbar-title"><i class="fas fa-images" style="color:var(--mo-primary);font-size:19px;"></i> {{ $banner ? 'Edit Banner' : 'Tambah Banner' }}</h1>
            <div class="mo-appbar-sub">Banner &amp; label halaman publik</div>
        </div>
    </div>
</div>

<div class="mo-content" style="padding-top:0;">
    <form method="POST" action="{{ $banner ? route('mo.banners.update', $banner->id) : route('mo.banners.store') }}" enctype="multipart/form-data" class="mo-form">
        @csrf
        @if($banner)
            @method('PUT')
        @endif

        <div class="mo-form-card">
            <h3 class="mo-form-card-title"><i class="fas fa-image"></i> Detail Banner</h3>

            <div class="mo-field">
                <label for="title">Judul <span class="req">*</span></label>
                <input type="text" id="title" name="title" class="mo-input" value="{{ old('title', $banner->title ?? '') }}" required placeholder="Contoh: Program Ramadhan">
            </div>

            <div class="mo-field">
                <label for="type">Tipe <span class="req">*</span></label>
                <select id="type" name="type" class="mo-select" required>
                    <option value="banner" {{ old('type', $banner->type ?? 'banner') === 'banner' ? 'selected' : '' }}>Banner</option>
                    <option value="label" {{ old('type', $banner->type ?? '') === 'label' ? 'selected' : '' }}>Label</option>
                </select>
            </div>

            <div class="mo-field">
                <label>Gambar</label>
                <div class="mo-file-input">
                    <i class="fas fa-image"></i>
                    Pilih gambar banner
                    <input type="file" name="image" accept="image/jpeg,image/jpg,image/png,image/webp" data-banner-input>
                </div>
                @if($banner && $banner->image_url)
                    <img src="{{ $banner->image_url }}" alt="" class="mo-thumb" data-banner-preview style="display:block;">
                @else
                    <img src="" alt="" class="mo-thumb" data-banner-preview>
                @endif
                <div class="mo-form-help">JPG, PNG, WebP. Maks 5MB.</div>
            </div>

            <div class="mo-field">
                <label for="url">Link URL</label>
                <input type="url" id="url" name="url" class="mo-input" value="{{ old('url', $banner->url ?? '') }}" placeholder="https://...">
            </div>

            <div class="mo-field">
                <label for="label_color">Warna Label</label>
                <input type="color" id="label_color" name="label_color" class="mo-input" value="{{ old('label_color', $banner->label_color ?? '#086e66') }}" style="height:48px;padding:4px;">
            </div>

            <div class="mo-field">
                <label for="sort_order">Urutan</label>
                <input type="number" id="sort_order" name="sort_order" class="mo-input" value="{{ old('sort_order', $banner->sort_order ?? 0) }}" min="0" step="1">
            </div>

            <div class="mo-switch">
                <div>
                    <div class="lbl">Aktif</div>
                    <div class="sub">Tampil di halaman publik</div>
                </div>
                <input type="checkbox" name="is_active" value="1" id="mo-banner-active" {{ old('is_active', $banner->is_active ?? true) ? 'checked' : '' }}>
                <label class="track" for="mo-banner-active"></label>
            </div>
        </div>

        <div class="mo-form-footer">
            <a href="{{ route('mo.banners') }}" class="mo-btn mo-btn-ghost">Batal</a>
            <button type="submit" class="mo-btn mo-btn-primary"><i class="fas fa-save"></i> Simpan</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        var input = document.querySelector('[data-banner-input]');
        var preview = document.querySelector('[data-banner-preview]');
        if (input && preview) {
            input.addEventListener('change', function () {
                var file = input.files[0];
                if (!file) return;
                var reader = new FileReader();
                reader.onload = function (e) { preview.src = e.target.result; preview.style.display = 'block'; };
                reader.readAsDataURL(file);
            });
        }
    })();
</script>
@endpush
