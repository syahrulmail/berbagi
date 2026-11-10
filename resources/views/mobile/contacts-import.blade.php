@extends('mobile.layouts.app')

@section('title', 'Impor Kontak')

@section('mobile-content')
<div class="mo-appbar">
    <div class="mo-appbar-row">
        <a href="{{ route('mo.contacts') }}" class="mo-appbar-back" aria-label="Kembali"><i class="fas fa-arrow-left"></i></a>
        <div style="flex:1;min-width:0;">
            <h1 class="mo-appbar-title"><i class="fas fa-file-import" style="color:var(--mo-primary);font-size:19px;"></i> Impor Kontak</h1>
            <div class="mo-appbar-sub">Tambah kontak secara massal</div>
        </div>
    </div>
</div>

<div class="mo-content" style="padding-top:0;">
    <div class="mo-card mo-card--flat" style="font-size:12.5px;color:var(--mo-muted);line-height:1.55;">
        <strong style="color:var(--mo-text);"><i class="fas fa-circle-info"></i> Format data</strong><br>
        <div style="margin-top:6px;"><strong style="color:var(--mo-text);">Urutan kolom:</strong> <b>Cabang, Agen, Nama, No. WhatsApp, Status, Catatan</b> (boleh pakai baris judul).</div>
        <div><strong style="color:var(--mo-text);">Status:</strong> <b>Prospek, Simpan, Wakif, atau Stop</b>.</div>
        <div><strong style="color:var(--mo-text);">Nomor WA:</strong> 8-15 digit, boleh diawali <b>62</b>, <b>+62</b>, kode negara lain, atau 0/8 yang otomatis diubah ke 62 (contoh 62812xxxxxxx atau +60123456789).</div>
    </div>

    <div class="mo-form-card">
        <h3 class="mo-form-card-title"><i class="fas fa-download"></i> Contoh Format</h3>
        <p style="margin:0 0 10px;font-size:12.5px;color:var(--mo-muted);line-height:1.55;">
            Unduh template, isi datanya, lalu unggah kembali pada form di bawah.
        </p>
        <div class="mo-template-actions">
            <a href="{{ asset('downloads/template-import-kontak.xlsx') }}" class="mo-template-btn" download>
                <i class="fas fa-file-excel"></i> .xlsx
            </a>
            <a href="{{ asset('downloads/template-import-kontak.xls') }}" class="mo-template-btn" download>
                <i class="fas fa-file-excel"></i> .xls
            </a>
            <a href="{{ asset('downloads/template-import-kontak.csv') }}" class="mo-template-btn" download>
                <i class="fas fa-file-csv"></i> .csv
            </a>
        </div>
    </div>

    <div class="mo-form-card">
        <h3 class="mo-form-card-title"><i class="fas fa-file-arrow-up"></i> Unggah File</h3>
        <form method="POST" action="{{ route('mo.contact.import') }}" enctype="multipart/form-data">
            @csrf
            <div class="mo-file-input">
                <i class="fas fa-file-excel"></i>
                <span data-file-label>Pilih file .xls, .xlsx, .csv, atau .txt</span>
                <input type="file" name="import_file" accept=".xls,.xlsx,.csv,.txt" data-import-file required>
            </div>
            <button type="submit" class="mo-btn mo-btn-primary mo-btn-block" style="margin-top:12px;">
                <i class="fas fa-upload"></i> Impor File
            </button>
        </form>
    </div>

    <div class="mo-form-card">
        <h3 class="mo-form-card-title"><i class="fas fa-paste"></i> Tempel Data</h3>
        <form method="POST" action="{{ route('mo.contact.paste') }}">
            @csrf
            <div class="mo-field" style="margin-bottom:10px;">
                <label>Satu baris satu kontak <span class="req">*</span></label>
                <textarea name="paste_lines" class="mo-textarea" rows="7" placeholder="Cabang, Agen, Nama, No. WhatsApp, Status, Catatan" required>{{ old('paste_lines') }}</textarea>
            </div>
            <button type="submit" class="mo-btn mo-btn-primary mo-btn-block">
                <i class="fas fa-paste"></i> Proses Tempelan
            </button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        var input = document.querySelector('[data-import-file]');
        var label = document.querySelector('[data-file-label]');
        if (!input || !label) return;

        var defaultText = label.textContent;

        input.addEventListener('change', function () {
            var file = input.files && input.files[0];
            if (!file) {
                label.textContent = defaultText;
                return;
            }
            var size = file.size >= 1048576
                ? (file.size / 1048576).toFixed(1) + ' MB'
                : Math.max(1, Math.round(file.size / 1024)) + ' KB';
            label.textContent = file.name + ' (' + size + ')';
            label.style.color = 'var(--mo-text)';
            label.style.fontWeight = '600';
        });
    })();
</script>
@endpush
