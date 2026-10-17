@extends('layouts.app')

@section('title', 'Target & Informasi')

@push('styles')
<style>
    .rich-editor-toolbar {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-bottom: 8px;
    }
    .rich-editor {
        min-height: 110px;
        border: 1px solid #d2e2e0;
        border-radius: 10px;
        background: #fff;
        padding: 12px 14px;
        font-size: 14px;
        line-height: 1.7;
    }
    .rich-editor:focus {
        outline: none;
        border-color: #086e66;
        box-shadow: 0 0 0 3px rgba(8, 110, 102, 0.12);
    }
    .rich-editor blockquote {
        border-left: 3px solid #d4911e;
        margin: 8px 0;
        padding-left: 12px;
        color: #086e66;
        font-style: italic;
    }
</style>
@endpush

@section('content')
<div class="page-header">
    <div>
        <h1><i class="fas fa-bullseye"></i> Target &amp; Informasi</h1>
        <p class="subtitle">Target global dan informasi utama halaman publik.</p>
    </div>
</div>

<div class="card" style="max-width: 640px;">
    <form method="POST" action="{{ route('settings.target.update') }}">
        @csrf
        @method('PUT')

        <div class="form-group">
            <label for="global_target_bulanan">Target Global BWA Bulanan (Rp)</label>
            <input type="text" id="global_target_bulanan" value="Rp {{ number_format($totalGlobalTarget, 0, ',', '.') }}" readonly>
            <small style="color: var(--gray-500);">Otomatis dihitung dari total "Target Donasi (Rp)" seluruh cabang aktif. Ubah di menu Cabang → Tambah/Edit Cabang.</small>
        </div>

        <div class="form-group">
            <label for="trustbar_text">Teks Bar Atas (Trustbar)</label>
            <input type="text" id="trustbar_text" name="trustbar_text" value="{{ old('trustbar_text', $settings['trustbar_text']) }}" maxlength="160">
            <small style="color: var(--gray-500);">Teks yang tampil pada baris teratas seluruh halaman publik, mis. "Badan Wakaf Al Qur'an · Terdaftar &amp; Berizin".</small>
        </div>

        <div class="form-group">
            <label for="home_quote">Quote di Bawah Hero</label>
            <div class="rich-editor-toolbar">
                <button type="button" class="btn btn-outline btn-sm" data-cmd="bold"><b>B</b></button>
                <button type="button" class="btn btn-outline btn-sm" data-cmd="italic"><i>I</i></button>
                <button type="button" class="btn btn-outline btn-sm" data-cmd="underline"><u>U</u></button>
                <button type="button" class="btn btn-outline btn-sm" data-cmd="formatBlock" data-val="blockquote">Quote</button>
                <button type="button" class="btn btn-outline btn-sm" data-cmd="removeFormat">Hapus Format</button>
            </div>
            <div id="homeQuoteEditor" class="rich-editor" contenteditable="true"></div>
            <input type="hidden" id="home_quote" name="home_quote" value="{{ old('home_quote', $settings['home_quote']) }}">
            <small style="color: var(--gray-500);">Kutipan yang tampil tepat di bawah hero halaman utama. Gunakan toolbar untuk tebal, miring, garis bawah, atau format kutipan.</small>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var editor = document.getElementById('homeQuoteEditor');
    var input = document.getElementById('home_quote');
    if (!editor || !input) return;

    editor.innerHTML = input.value;

    document.querySelectorAll('.rich-editor-toolbar [data-cmd]').forEach(function (btn) {
        btn.addEventListener('mousedown', function (e) { e.preventDefault(); });
        btn.addEventListener('click', function () {
            editor.focus();
            var cmd = btn.getAttribute('data-cmd');
            if (cmd === 'formatBlock') {
                document.execCommand('formatBlock', false, btn.getAttribute('data-val') || 'blockquote');
            } else {
                document.execCommand(cmd, false, null);
            }
        });
    });

    editor.closest('form').addEventListener('submit', function () {
        input.value = editor.innerHTML;
    });
})();
</script>
@endpush
