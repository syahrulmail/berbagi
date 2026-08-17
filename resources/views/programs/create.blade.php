@extends('layouts.app')

@section('title', 'Tambah Program')

@section('content')
<div class="page-header">
    <div>
        <h1><i class="fas fa-file-invoice-dollar"></i> Tambah Program</h1>
        <p class="subtitle">Buat program wakaf baru.</p>
    </div>
    <a href="{{ route('programs.index') }}" class="btn"><i class="fas fa-arrow-left"></i> Kembali</a>
</div>

<div class="card" style="max-width: 640px;">
    <form method="POST" action="{{ route('programs.store') }}">
        @csrf
        <div class="form-group">
            <label for="name">Nama Program *</label>
            <input type="text" id="name" name="name" value="{{ old('name') }}" required>
        </div>
        <div class="form-group">
            <label for="slug">Slug</label>
            <input type="text" id="slug" name="slug" value="{{ old('slug') }}" placeholder="Kosongkan untuk otomatis">
        </div>
        <div class="form-group">
            <label for="category">Kategori</label>
            <select id="category" name="category">
                <option value="">— Pilih Kategori —</option>
                <option value="penggalangan" {{ old('category') == 'penggalangan' ? 'selected' : '' }}>Penggalangan</option>
                <option value="penyaluran" {{ old('category') == 'penyaluran' ? 'selected' : '' }}>Penyaluran</option>
            </select>
            <small style="color: var(--gray-500);">Digunakan untuk filter di halaman publik.</small>
        </div>
        <div class="form-group">
            <label for="image">Link Gambar / Foto Utama</label>
            <input type="url" id="image" name="image" value="{{ old('image') }}" placeholder="https://...">
        </div>
        <div class="form-group">
            <label for="description">Deskripsi</label>
            <textarea id="description" name="description" rows="4" placeholder="Deskripsi program...">{{ old('description') }}</textarea>
        </div>
        <div class="form-group">
            <label for="goal_amount">Goal Keuangan (Rp) *</label>
            <input type="number" id="goal_amount" name="goal_amount" value="{{ old('goal_amount') }}" min="0" step="0.01" required>
        </div>
        <div class="form-group">
            <label>Campaign Tags</label>
            @foreach($tags as $tag)
                <label class="checkbox-label" style="margin-bottom: 6px;">
                    <input type="checkbox" name="tags[]" value="{{ $tag->id }}"
                           {{ in_array($tag->id, old('tags', [])) ? 'checked' : '' }}>
                    <span class="tag-pill" style="background: {{ $tag->color }}">{{ $tag->name }}</span>
                </label>
            @endforeach
        </div>
        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}> Aktif
            </label>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
            <a href="{{ route('programs.index') }}" class="btn">Batal</a>
        </div>
    </form>
</div>
@endsection
