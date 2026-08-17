@extends('layouts.app')

@section('title', 'Edit Banner')

@section('content')
<div class="page-header">
    <div>
        <h1><i class="fas fa-images"></i> Edit Banner / Label</h1>
        <p class="subtitle">{{ $banner->title }}</p>
    </div>
    <a href="{{ route('banners.index') }}" class="btn"><i class="fas fa-arrow-left"></i> Kembali</a>
</div>

<div class="card" style="max-width: 640px;">
    <form method="POST" action="{{ route('banners.update', $banner) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="form-group">
            <label for="title">Judul *</label>
            <input type="text" id="title" name="title" value="{{ old('title', $banner->title) }}" required>
        </div>
        <div class="form-group">
            <label for="type">Tipe *</label>
            <select id="type" name="type">
                <option value="banner" {{ old('type', $banner->type) == 'banner' ? 'selected' : '' }}>Banner</option>
                <option value="label" {{ old('type', $banner->type) == 'label' ? 'selected' : '' }}>Label</option>
            </select>
        </div>
        <div class="form-group">
            <label for="image">Gambar Banner</label>
            @if($banner->image)
                <div style="margin-bottom: 8px;">
                    <img src="{{ asset('storage/' . $banner->image) }}" alt="{{ $banner->title }}" style="height: 80px; border-radius: 8px;">
                </div>
            @endif
            <input type="file" id="image" name="image" accept="image/*">
            <small style="color: var(--gray-500);">Kosongkan jika tidak mengganti gambar.</small>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="url">URL Tujuan</label>
                <input type="url" id="url" name="url" value="{{ old('url', $banner->url) }}" placeholder="https://...">
            </div>
            <div class="form-group">
                <label for="label_color">Warna Label</label>
                <input type="color" id="label_color" name="label_color" value="{{ old('label_color', $banner->label_color ?? '#086E66') }}" style="width: 60px; height: 42px; padding: 4px; cursor: pointer;">
            </div>
        </div>
        <div class="form-group">
            <label for="sort_order">Urutan</label>
            <input type="number" id="sort_order" name="sort_order" value="{{ old('sort_order', $banner->sort_order) }}" min="0">
        </div>
        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $banner->is_active) ? 'checked' : '' }}> Aktif
            </label>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Perubahan</button>
            <a href="{{ route('banners.index') }}" class="btn">Batal</a>
        </div>
    </form>
</div>
@endsection
