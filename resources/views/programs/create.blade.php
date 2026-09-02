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

<div class="card" style="max-width: 720px;">
    <form method="POST" action="{{ route('programs.store') }}" enctype="multipart/form-data">
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
            <label for="program_category">Kategori Program *</label>
            <select id="program_category" name="program_category" required>
                <option value="">— Pilih Kategori Program —</option>
                @foreach(\App\Models\Program::CATEGORIES as $code => $label)
                    <option value="{{ $code }}" {{ old('program_category') == $code ? 'selected' : '' }}>
                        {{ $label }} ({{ $code }})
                    </option>
                @endforeach
            </select>
            <small style="color: var(--gray-500);">Setiap program wajib memiliki 1 Kategori Program.</small>
        </div>
        <div class="form-group">
            <label for="category">Jenis Program</label>
            <select id="category" name="category">
                <option value="">— Pilih Jenis Program —</option>
                <option value="penggalangan" {{ old('category') == 'penggalangan' ? 'selected' : '' }}>Penggalangan</option>
                <option value="penyaluran" {{ old('category') == 'penyaluran' ? 'selected' : '' }}>Penyaluran</option>
            </select>
            <small style="color: var(--gray-500);">Digunakan untuk filter di halaman publik.</small>
        </div>
        @include('partials.program-media', ['mediaItems' => [], 'videoUrl' => old('video_url', '')])
        @include('partials.rich-editor', ['editorValue' => old('description', '')])
        <div class="form-group">
            <label for="goal_amount">Goal Keuangan (Rp) *</label>
            <input type="number" id="goal_amount" name="goal_amount" value="{{ old('goal_amount') }}" min="0" step="0.01" required>
        </div>
        <div class="form-group">
            <label for="terkumpul_publik">Terkumpul Publik (Rp)</label>
            <input type="number" id="terkumpul_publik" name="terkumpul_publik" value="{{ old('terkumpul_publik', 0) }}" min="0" step="0.01">
            <small style="color: var(--gray-500); display:block; margin-top:4px;">Angka ini yang ditampilkan sebagai progress publik. Donasi riil TIDAK menambah angka ini.</small>
        </div>
        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="show_goal" value="1" {{ old('show_goal', true) ? 'checked' : '' }}>
                Tampilkan Goal di halaman publik
            </label>
            <small style="color: var(--gray-500); display:block; margin-top:4px;">Jika dicentang, target &amp; progress ditampilkan di halaman publik.</small>
        </div>
        @include('partials.tag-input', [
            'defaultTags' => $defaultTags,
            'extraTags' => $extraTags,
            'defaultTagValue' => $defaultTagValue,
            'tagNamesValue' => $tagNamesValue,
        ])
        <div class="form-group">
            <label for="suka">Suka (klik ❤ oleh pengunjung)</label>
            <input type="number" id="suka" name="suka" value="{{ old('suka', 0) }}" min="0" step="1">
            <small style="color: var(--gray-500); display:block; margin-top:4px;">Jumlah suka awal. Klik ❤ riil dari pengunjung akan ditambahkan otomatis.</small>
        </div>
        <div class="form-group">
            <label for="klik">Klik (klik Detail oleh pengunjung)</label>
            <input type="number" id="klik" name="klik" value="{{ old('klik', 0) }}" min="0" step="1">
            <small style="color: var(--gray-500); display:block; margin-top:4px;">Jumlah klik awal. Setiap pembukaan detail program oleh pengunjung akan menambah angka ini.</small>
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
