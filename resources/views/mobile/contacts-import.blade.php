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
        Urutan kolom: <b>Cabang, Agen, Nama, No. WhatsApp, Status, Catatan</b> (boleh pakai baris judul).
        Status: Prospek, Simpan, Wakif, atau Stop. Nomor WA: 10-15 digit (contoh 62812xxxxxxx).
    </div>

    <div class="mo-form-card">
        <h3 class="mo-form-card-title"><i class="fas fa-file-arrow-up"></i> Unggah File</h3>
        <form method="POST" action="{{ route('mo.contact.import') }}" enctype="multipart/form-data">
            @csrf
            <div class="mo-file-input">
                <i class="fas fa-file-excel"></i>
                Pilih file .xls, .xlsx, .csv, atau .txt
                <input type="file" name="import_file" accept=".xls,.xlsx,.csv,.txt" required>
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
