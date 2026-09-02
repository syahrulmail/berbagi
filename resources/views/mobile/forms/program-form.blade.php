@extends('mobile.layouts.app')

@section('title', $program ? 'Edit Program' : 'Tambah Program')

@section('mobile-content')
<div class="mo-appbar">
    <div class="mo-appbar-row">
        <a href="{{ route('mo.programs') }}" class="mo-appbar-back" aria-label="Kembali">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div style="flex:1;min-width:0;">
            <h1 class="mo-appbar-title"><i class="fas fa-file-invoice-dollar" style="color:var(--mo-primary);font-size:20px;"></i> {{ $program ? 'Edit Program' : 'Tambah Program' }}</h1>
            <div class="mo-appbar-sub">{{ $program ? 'Perbarui program wakaf' : 'Buat program wakaf baru' }}</div>
        </div>
    </div>
</div>

<div class="mo-content" style="padding-top:0;">
    @if($errors->any())
        <div class="mo-validation-summary">
            <strong><i class="fas fa-circle-exclamation"></i> Periksa kembali isian:</strong>
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ $program ? route('mo.program.update', $program->id) : route('mo.program.store') }}" enctype="multipart/form-data" class="mo-form">
        @csrf
        @if($program)
            @method('PUT')
        @endif

        <div class="mo-form-card">
            <h3 class="mo-form-card-title"><i class="fas fa-file-invoice-dollar"></i> Detail Program</h3>

            <div class="mo-field">
                <label for="name">Nama Program <span class="req">*</span></label>
                <input type="text" id="name" name="name" class="mo-input" value="{{ old('name', $program->name ?? '') }}" required placeholder="Contoh: Wakaf Al-Qur'an">
            </div>

            <div class="mo-field">
                <label for="slug">Slug</label>
                <input type="text" id="slug" name="slug" class="mo-input" value="{{ old('slug', $program->slug ?? '') }}" placeholder="Kosongkan untuk otomatis">
                <div class="mo-form-help">Digunakan pada link halaman publik program.</div>
            </div>

            <div class="mo-field">
                <label for="program_category">Kategori Program <span class="req">*</span></label>
                <select id="program_category" name="program_category" class="mo-select" required>
                    <option value="">— Pilih Kategori —</option>
                    @foreach(\App\Models\Program::CATEGORIES as $code => $label)
                        <option value="{{ $code }}" {{ old('program_category', $program->program_category ?? '') == $code ? 'selected' : '' }}>
                            {{ $label }} ({{ $code }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mo-field">
                <label for="category">Jenis Program</label>
                <select id="category" name="category" class="mo-select">
                    <option value="">— Pilih Jenis —</option>
                    <option value="penggalangan" {{ old('category', $program->category ?? '') == 'penggalangan' ? 'selected' : '' }}>Penggalangan</option>
                    <option value="penyaluran" {{ old('category', $program->category ?? '') == 'penyaluran' ? 'selected' : '' }}>Penyaluran</option>
                </select>
            </div>

            <div class="mo-field" style="margin-bottom:0;">
                <label for="description">Deskripsi</label>
                <textarea id="description" name="description" class="mo-textarea" rows="4" placeholder="Deskripsi singkat program...">{{ old('description', $program->description ?? '') }}</textarea>
            </div>
        </div>

        <div class="mo-form-card">
            <h3 class="mo-form-card-title"><i class="fas fa-image"></i> Gambar &amp; Video</h3>

            <div class="mo-field">
                <label>Sampul Program</label>
                <div class="mo-file-input">
                    <i class="fas fa-image"></i>
                    Pilih foto sampul program
                    <input type="file" name="cover_image" accept="image/jpeg,image/jpg,image/png,image/webp" data-cover-input>
                </div>
                @php
                    $coverUrl = '';
                    if ($program) {
                        $items = $program->media_items;
                        $coverUrl = count($items) && $items[0]['order'] === 0 ? $items[0]['url'] : '';
                    }
                @endphp
                @if($coverUrl)
                    <img src="{{ $coverUrl }}" alt="Sampul" class="mo-thumb" data-cover-preview style="display:block;">
                    <label style="margin-top:8px;display:flex;align-items:center;gap:5px;font-size:12px;color:var(--mo-danger);">
                        <input type="checkbox" name="remove_cover" value="1"> Hapus sampul saat ini
                    </label>
                @else
                    <img src="" alt="" class="mo-thumb" data-cover-preview>
                @endif
                <div class="mo-form-help">JPG, PNG, WebP. Maks 4MB.</div>
            </div>

            <div class="mo-field" style="margin-bottom:0;">
                <label for="video_url">Link Video (YouTube)</label>
                <input type="url" id="video_url" name="video_url" class="mo-input" value="{{ old('video_url', $program->video_url ?? '') }}" placeholder="https://youtube.com/watch?v=...">
            </div>
        </div>

        <div class="mo-form-card">
            <h3 class="mo-form-card-title"><i class="fas fa-chart-line"></i> Target &amp; Status</h3>

            <div class="mo-field">
                <label for="goal_amount">Goal Keuangan (Rp) <span class="req">*</span></label>
                <input type="number" id="goal_amount" name="goal_amount" class="mo-input" value="{{ old('goal_amount', $program->goal_amount ?? '') }}" min="0" step="0.01" required placeholder="0">
            </div>

            <div class="mo-field">
                <label for="terkumpul_publik">Terkumpul Publik (Rp)</label>
                <input type="number" id="terkumpul_publik" name="terkumpul_publik" class="mo-input" value="{{ old('terkumpul_publik', $program->terkumpul_publik ?? 0) }}" min="0" step="0.01" placeholder="0">
                <div class="mo-form-help">Angka ini yang tampil sebagai progress publik. Donasi riil tidak menambah angka ini.</div>
            </div>

            <div class="mo-switch">
                <div>
                    <div class="lbl">Tampilkan Goal</div>
                    <div class="sub">Target &amp; progress tampil di halaman publik</div>
                </div>
                <input type="checkbox" name="show_goal" value="1" id="mo-show-goal" {{ old('show_goal', $program->show_goal ?? true) ? 'checked' : '' }}>
                <label class="track" for="mo-show-goal"></label>
            </div>

            <div class="mo-switch">
                <div>
                    <div class="lbl">Aktif</div>
                    <div class="sub">Program aktif tampil di aplikasi &amp; situs publik</div>
                </div>
                <input type="checkbox" name="is_active" value="1" id="mo-is-active" {{ old('is_active', $program->is_active ?? true) ? 'checked' : '' }}>
                <label class="track" for="mo-is-active"></label>
            </div>

            <div class="mo-field">
                <label for="suka"><i class="fas fa-heart" style="color:#e0245e;"></i> Suka</label>
                <input type="number" id="suka" name="suka" class="mo-input" value="{{ old('suka', $program->suka ?? 0) }}" min="0" step="1" placeholder="0">
                <div class="mo-form-help">Jumlah suka awal. Klik ❤ riil pengunjung di situs publik ditambahkan otomatis.</div>
            </div>

            <div class="mo-field" style="margin-bottom:0;">
                <label for="klik"><i class="fas fa-arrow-pointer"></i> Klik</label>
                <input type="number" id="klik" name="klik" class="mo-input" value="{{ old('klik', $program->klik ?? 0) }}" min="0" step="1" placeholder="0">
                <div class="mo-form-help">Jumlah klik awal. Pembukaan detail program oleh pengunjung ditambahkan otomatis.</div>
            </div>
        </div>

        <div class="mo-form-footer">
            <a href="{{ route('mo.programs') }}" class="mo-btn mo-btn-ghost">Batal</a>
            <button type="submit" class="mo-btn mo-btn-primary"><i class="fas fa-save"></i> {{ $program ? 'Simpan Perubahan' : 'Simpan Program' }}</button>
        </div>
    </form>

    @if($program)
        <div class="mo-form-card" style="margin-top:2px;background:#fff8f7;box-shadow:none;border:1.5px solid #f6d8d4;">
            <h3 class="mo-form-card-title" style="color:var(--mo-danger);"><i class="fas fa-triangle-exclamation"></i> Zona Berbahaya</h3>
            <form method="POST" action="{{ route('mo.program.destroy', $program->id) }}" onsubmit="return confirm('Hapus program ini? Tindakan tidak dapat dibatalkan.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="mo-btn mo-btn-danger mo-btn-block"><i class="fas fa-trash-can"></i> Hapus Program</button>
            </form>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    (function () {
        'use strict';
        var input = document.querySelector('[data-cover-input]');
        var preview = document.querySelector('[data-cover-preview]');
        if (input && preview) {
            input.addEventListener('change', function () {
                var file = input.files[0];
                if (!file) return;
                var reader = new FileReader();
                reader.onload = function (e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            });
        }
    })();
</script>
@endpush
