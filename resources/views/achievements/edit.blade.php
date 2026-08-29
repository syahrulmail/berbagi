@extends('layouts.app')

@section('title', 'Edit Pencapaian')

@section('content')
<div class="page-header">
    <div>
        <h1><i class="fas fa-medal"></i> Edit Pencapaian</h1>
        <p class="subtitle">Perbarui kartu pencapaian.</p>
    </div>
    <a href="{{ route('achievements.index') }}" class="btn"><i class="fas fa-arrow-left"></i> Kembali</a>
</div>

<div class="card" style="max-width: 640px;">
    <form method="POST" action="{{ route('achievements.update', $achievement) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="form-group">
            <label for="value">Data Pencapaian *</label>
            <input type="text" id="value" name="value" value="{{ old('value', $achievement->value) }}" required>
            <small style="color: var(--gray-500);">Angka/data yang ditampilkan mencolok pada kartu.</small>
        </div>
        <div class="form-group">
            <label for="label">Keterangan *</label>
            <input type="text" id="label" name="label" value="{{ old('label', $achievement->label) }}" required>
            <small style="color: var(--gray-500);">Keterangan singkat di bawah data pencapaian.</small>
        </div>
        <div class="form-group">
            <label for="icon">Ikon (Font Awesome)</label>
            <input type="text" id="icon" name="icon" value="{{ old('icon', $achievement->icon) }}" placeholder="Contoh: fa-hand-holding-heart">
            <small style="color: var(--gray-500);">Dipakai bila tidak mengunggah gambar. Klik salah satu:</small>
            <div style="display:flex; flex-wrap:wrap; gap:6px; margin-top:8px;">
                @php $suggestions = ['fa-hand-holding-heart','fa-book-quran','fa-users','fa-building-columns','fa-house-chimney','fa-truck-fast','fa-seedling','fa-graduation-cap','fa-heart','fa-trophy','fa-ribbon','fa-hands-praying']; @endphp
                @foreach($suggestions as $ic)
                    <button type="button" class="tag-pill icon-suggest" data-icon="{{ $ic }}" style="background: var(--accent); cursor:pointer; border:none; padding:5px 12px; font-size:12px;">
                        <i class="fas {{ $ic }}"></i> {{ $ic }}
                    </button>
                @endforeach
            </div>
        </div>
        <div class="form-group">
            <label for="image">Gambar / Icon</label>
            <input type="file" id="image" name="image" accept="image/*">
            @if($achievement->image)
                <div style="margin-top:8px;">
                    <img src="{{ $achievement->image_url }}" alt="" style="height:56px; border-radius:10px;">
                    <small style="color: var(--gray-500);"> Gambar saat ini. Unggah file baru untuk mengganti.</small>
                </div>
            @else
                <small style="color: var(--gray-500);">Maksimal 2MB. Jika diunggah, gambar dipakai sebagai ikon.</small>
            @endif
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="color">Warna Aksen</label>
                <input type="color" id="color" name="color" value="{{ old('color', $achievement->color ?: '#08A899') }}" style="width: 60px; height: 42px; padding: 4px; cursor: pointer;">
            </div>
            <div class="form-group">
                <label for="sort_order">Urutan</label>
                <input type="number" id="sort_order" name="sort_order" value="{{ old('sort_order', $achievement->sort_order) }}" min="0">
            </div>
        </div>
        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $achievement->is_active) ? 'checked' : '' }}> Aktif
            </label>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
            <a href="{{ route('achievements.index') }}" class="btn">Batal</a>
        </div>
    </form>
</div>

@push('scripts')
<script>
    (function () {
        document.querySelectorAll('.icon-suggest').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.getElementById('icon').value = btn.getAttribute('data-icon');
            });
        });
    })();
</script>
@endpush
@endsection
