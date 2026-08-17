@extends('layouts.app')

@section('title', 'Tambah Banner')

@section('content')
<div class="page-header">
    <div>
        <h1><i class="fas fa-images"></i> Tambah Banner / Label</h1>
        <p class="subtitle">Unggah banner promosi atau label baru.</p>
    </div>
    <a href="{{ route('banners.index') }}" class="btn"><i class="fas fa-arrow-left"></i> Kembali</a>
</div>

<div class="card" style="max-width: 640px;">
    <form method="POST" action="{{ route('banners.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="form-group">
            <label for="title">Judul *</label>
            <input type="text" id="title" name="title" value="{{ old('title') }}" required>
        </div>
        <div class="form-group">
            <label for="type">Tipe *</label>
            <select id="type" name="type">
                <option value="banner" {{ old('type') == 'banner' ? 'selected' : '' }}>Banner</option>
                <option value="label" {{ old('type') == 'label' ? 'selected' : '' }}>Label</option>
            </select>
        </div>
        <div class="form-group">
            <label for="image">Gambar Banner</label>
            <input type="file" id="image" name="image" accept="image/*">
            <small style="color: var(--gray-500);">Maksimal 5MB. Akan diproses dengan Imagick.</small>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="url">URL Tujuan</label>
                <input type="url" id="url" name="url" value="{{ old('url') }}" placeholder="https://...">
            </div>
            <div class="form-group">
                <label for="label_color">Warna Label</label>
                <input type="color" id="label_color" name="label_color" value="{{ old('label_color', '#086E66') }}" style="width: 60px; height: 42px; padding: 4px; cursor: pointer;">
            </div>
        </div>
        <div class="form-group">
            <label for="sort_order">Urutan</label>
            <input type="number" id="sort_order" name="sort_order" value="{{ old('sort_order', 0) }}" min="0">
        </div>
        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}> Aktif
            </label>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
            <a href="{{ route('banners.index') }}" class="btn">Batal</a>
        </div>
    </form>
</div>
@endsection
