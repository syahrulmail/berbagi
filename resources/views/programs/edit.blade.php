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

<div class="card" style="max-width: 720px;">
    <form method="POST" action="{{ route('programs.update', $program) }}" enctype="multipart/form-data">
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
            <label for="program_category">Kategori Program *</label>
            <select id="program_category" name="program_category" required>
                <option value="">— Pilih Kategori Program —</option>
                @foreach(\App\Models\Program::CATEGORIES as $code => $label)
                    <option value="{{ $code }}" {{ old('program_category', $program->program_category) == $code ? 'selected' : '' }}>
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
                <option value="penggalangan" {{ old('category', $program->category) == 'penggalangan' ? 'selected' : '' }}>Penggalangan</option>
                <option value="penyaluran" {{ old('category', $program->category) == 'penyaluran' ? 'selected' : '' }}>Penyaluran</option>
            </select>
        </div>
        @include('partials.program-media', [
            'mediaItems' => $program->media_items,
            'videoUrl' => old('video_url', $program->video_url),
        ])
        @include('partials.rich-editor', ['editorValue' => old('description', $program->description)])
        <div class="form-group">
            <label for="goal_amount">Goal Keuangan (Rp) *</label>
            <input type="number" id="goal_amount" name="goal_amount" value="{{ old('goal_amount', $program->goal_amount) }}" min="0" step="0.01" required>
        </div>
        <div class="form-group">
            <label for="terkumpul_publik">Terkumpul Publik (Rp)</label>
            <input type="number" id="terkumpul_publik" name="terkumpul_publik" value="{{ old('terkumpul_publik', $program->terkumpul_publik) }}" min="0" step="0.01">
            <small style="color: var(--gray-500); display:block; margin-top:4px;">Angka ini yang ditampilkan sebagai progress publik. Donasi riil TIDAK menambah angka ini.</small>
        </div>
        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="show_goal" value="1" {{ old('show_goal', $program->show_goal) ? 'checked' : '' }}>
                Tampilkan Goal di halaman publik
            </label>
            <small style="color: var(--gray-500); display:block; margin-top:4px;">Jika dicentang, target &amp; progress donasi ditampilkan di halaman publik.</small>
        </div>
        @include('partials.tag-input', [
            'defaultTags' => $defaultTags,
            'extraTags' => $extraTags,
            'defaultTagValue' => $defaultTagValue,
            'tagNamesValue' => $tagNamesValue,
        ])
        <div class="form-group">
            <label for="suka">Suka (klik ❤ oleh pengunjung)</label>
            <input type="number" id="suka" name="suka" value="{{ old('suka', $program->suka) }}" min="0" step="1">
            <small style="color: var(--gray-500); display:block; margin-top:4px;">Jumlah suka awal. Klik ❤ riil dari pengunjung akan ditambahkan otomatis.</small>
        </div>
        <div class="form-group">
            <label for="klik">Klik (klik Detail oleh pengunjung)</label>
            <input type="number" id="klik" name="klik" value="{{ old('klik', $program->klik) }}" min="0" step="1">
            <small style="color: var(--gray-500); display:block; margin-top:4px;">Jumlah klik awal. Setiap pembukaan detail program oleh pengunjung akan menambah angka ini.</small>
        </div>
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
