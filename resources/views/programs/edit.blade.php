@extends('layouts.app')

@section('title', 'Edit Program')

@section('content')
<div class="page-header">
    <div>
        <h1><i class="fas fa-file-invoice-dollar"></i> Edit Program</h1>
        <p class="subtitle">{{ $program->name }}</p>
    </div>
    <a href="{{ route('programs.index') }}" class="btn"><i class="fas fa-arrow-left"></i> Kembali</a>
</div>

<div class="card" style="max-width: 640px;">
    <form method="POST" action="{{ route('programs.update', $program) }}">
        @csrf
        @method('PUT')
        <div class="form-group">
            <label for="name">Nama Program *</label>
            <input type="text" id="name" name="name" value="{{ old('name', $program->name) }}" required>
        </div>
        <div class="form-group">
            <label for="slug">Slug</label>
            <input type="text" id="slug" name="slug" value="{{ old('slug', $program->slug) }}">
        </div>
        <div class="form-group">
            <label for="category">Kategori</label>
            <select id="category" name="category">
                <option value="">— Pilih Kategori —</option>
                <option value="penggalangan" {{ old('category', $program->category) == 'penggalangan' ? 'selected' : '' }}>Penggalangan</option>
                <option value="penyaluran" {{ old('category', $program->category) == 'penyaluran' ? 'selected' : '' }}>Penyaluran</option>
            </select>
        </div>
        <div class="form-group">
            <label for="image">Link Gambar / Foto Utama</label>
            <input type="url" id="image" name="image" value="{{ old('image', $program->image) }}" placeholder="https://...">
        </div>
        <div class="form-group">
            <label for="description">Deskripsi</label>
            <textarea id="description" name="description" rows="4">{{ old('description', $program->description) }}</textarea>
        </div>
        <div class="form-group">
            <label for="goal_amount">Goal Keuangan (Rp) *</label>
            <input type="number" id="goal_amount" name="goal_amount" value="{{ old('goal_amount', $program->goal_amount) }}" min="0" step="0.01" required>
        </div>
        @include('partials.tag-input', [
            'tagNamesValue' => old('tag_names', $program->campaignTags->pluck('name')->implode(', ')),
            'tags' => $tags,
        ])
        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $program->is_active) ? 'checked' : '' }}> Aktif
            </label>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Perubahan</button>
            <a href="{{ route('programs.index') }}" class="btn">Batal</a>
        </div>
    </form>
</div>
@endsection
