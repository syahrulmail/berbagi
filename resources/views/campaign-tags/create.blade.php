@extends('layouts.app')

@section('title', 'Tambah Label Kampanye')

@section('content')
<div class="page-header">
    <div>
        <h1><i class="fas fa-tags"></i> Tambah Label Kampanye</h1>
        <p class="subtitle">Buat label baru untuk program wakaf.</p>
    </div>
    <a href="{{ route('campaign-tags.index') }}" class="btn"><i class="fas fa-arrow-left"></i> Kembali</a>
</div>

<div class="card" style="max-width: 560px;">
    <form method="POST" action="{{ route('campaign-tags.store') }}">
        @csrf
        <div class="form-group">
            <label for="name">Nama Label *</label>
            <input type="text" id="name" name="name" value="{{ old('name') }}" placeholder="Wakaf Al-Quran" required>
        </div>
        <div class="form-group">
            <label for="slug">Slug</label>
            <input type="text" id="slug" name="slug" value="{{ old('slug') }}" placeholder="Kosongkan untuk otomatis">
        </div>
        <div class="form-group">
            <label for="color">Warna *</label>
            <div style="display: flex; gap: 10px; align-items: center;">
                <input type="color" id="color" name="color" value="{{ old('color', '#2ECC71') }}" style="width: 60px; height: 42px; padding: 4px; cursor: pointer;">
                <span style="font-size: 13px; color: var(--gray-500);">Pilih warna untuk label</span>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
            <a href="{{ route('campaign-tags.index') }}" class="btn">Batal</a>
        </div>
    </form>
</div>
@endsection
