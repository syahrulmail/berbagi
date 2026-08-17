@extends('layouts.app')

@section('title', 'Edit Label Kampanye')

@section('content')
<div class="page-header">
    <div>
        <h1><i class="fas fa-tags"></i> Edit Label Kampanye</h1>
        <p class="subtitle">{{ $campaignTag->name }}</p>
    </div>
    <a href="{{ route('campaign-tags.index') }}" class="btn"><i class="fas fa-arrow-left"></i> Kembali</a>
</div>

<div class="card" style="max-width: 560px;">
    <form method="POST" action="{{ route('campaign-tags.update', $campaignTag) }}">
        @csrf
        @method('PUT')
        <div class="form-group">
            <label for="name">Nama Label *</label>
            <input type="text" id="name" name="name" value="{{ old('name', $campaignTag->name) }}" required>
        </div>
        <div class="form-group">
            <label for="slug">Slug</label>
            <input type="text" id="slug" name="slug" value="{{ old('slug', $campaignTag->slug) }}">
        </div>
        <div class="form-group">
            <label for="color">Warna *</label>
            <div style="display: flex; gap: 10px; align-items: center;">
                <input type="color" id="color" name="color" value="{{ old('color', $campaignTag->color) }}" style="width: 60px; height: 42px; padding: 4px; cursor: pointer;">
                <span style="font-size: 13px; color: var(--gray-500);">Pilih warna untuk label</span>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Perubahan</button>
            <a href="{{ route('campaign-tags.index') }}" class="btn">Batal</a>
        </div>
    </form>
</div>
@endsection
