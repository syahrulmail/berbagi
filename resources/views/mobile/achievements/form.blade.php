@extends('mobile.layouts.app')

@section('title', $achievement ? 'Edit Pencapaian' : 'Tambah Pencapaian')

@section('mobile-content')
<div class="mo-appbar">
    <div class="mo-appbar-row">
        <a href="{{ route('mo.achievements') }}" class="mo-appbar-back" aria-label="Kembali"><i class="fas fa-arrow-left"></i></a>
        <div style="flex:1;min-width:0;">
            <h1 class="mo-appbar-title"><i class="fas fa-trophy" style="color:var(--mo-gold);font-size:19px;"></i> {{ $achievement ? 'Edit Pencapaian' : 'Tambah Pencapaian' }}</h1>
            <div class="mo-appbar-sub">Statistik di halaman publik</div>
        </div>
    </div>
</div>

<div class="mo-content" style="padding-top:0;">
    <form method="POST" action="{{ $achievement ? route('mo.achievements.update', $achievement->id) : route('mo.achievements.store') }}" enctype="multipart/form-data" class="mo-form">
        @csrf
        @if($achievement)
            @method('PUT')
        @endif

        <div class="mo-form-card">
            <h3 class="mo-form-card-title"><i class="fas fa-medal"></i> Detail Pencapaian</h3>

            <div class="mo-field">
                <label for="value">Nilai <span class="req">*</span></label>
                <input type="text" id="value" name="value" class="mo-input" value="{{ old('value', $achievement->value ?? '') }}" required placeholder="Contoh: 1.250">
            </div>

            <div class="mo-field">
                <label for="label">Label <span class="req">*</span></label>
                <input type="text" id="label" name="label" class="mo-input" value="{{ old('label', $achievement->label ?? '') }}" required placeholder="Contoh: Donatur Aktif">
            </div>

            <div class="mo-field">
                <label for="icon">Ikon (Font Awesome)</label>
                <input type="text" id="icon" name="icon" class="mo-input" value="{{ old('icon', $achievement->icon ?? '') }}" placeholder="fa-hand-holding-heart">
                <div class="mo-form-help">Nama kelas Font Awesome tanpa awalan "fa-". Contoh: fa-users.</div>
            </div>

            <div class="mo-field">
                <label for="color">Warna</label>
                <input type="color" id="color" name="color" class="mo-input" value="{{ old('color', $achievement->color ?? '#d4911e') }}" style="height:48px;padding:4px;">
            </div>

            <div class="mo-field">
                <label>Gambar</label>
                <div class="mo-file-input">
                    <i class="fas fa-image"></i>
                    Pilih gambar (opsional)
                    <input type="file" name="image" accept="image/jpeg,image/jpg,image/png,image/webp" data-ach-input>
                </div>
                @if($achievement && $achievement->image_url)
                    <img src="{{ $achievement->image_url }}" alt="" class="mo-thumb" data-ach-preview style="display:block;">
                @else
                    <img src="" alt="" class="mo-thumb" data-ach-preview>
                @endif
                <div class="mo-form-help">Jika diisi, gambar menggantikan ikon. Maks 2MB.</div>
            </div>

            <div class="mo-field">
                <label for="sort_order">Urutan</label>
                <input type="number" id="sort_order" name="sort_order" class="mo-input" value="{{ old('sort_order', $achievement->sort_order ?? 0) }}" min="0" step="1">
            </div>

            <div class="mo-switch">
                <div>
                    <div class="lbl">Aktif</div>
                    <div class="sub">Tampil di halaman publik</div>
                </div>
                <input type="checkbox" name="is_active" value="1" id="mo-ach-active" {{ old('is_active', $achievement->is_active ?? true) ? 'checked' : '' }}>
                <label class="track" for="mo-ach-active"></label>
            </div>
        </div>

        <div class="mo-form-footer">
            <a href="{{ route('mo.achievements') }}" class="mo-btn mo-btn-ghost">Batal</a>
            <button type="submit" class="mo-btn mo-btn-primary"><i class="fas fa-save"></i> Simpan</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        var input = document.querySelector('[data-ach-input]');
        var preview = document.querySelector('[data-ach-preview]');
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
