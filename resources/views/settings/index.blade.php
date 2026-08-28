@extends('layouts.app')

@section('title', 'Pengaturan Sistem')

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
        <h1><i class="fas fa-gear"></i> Pengaturan Sistem</h1>
        <p class="subtitle">Konfigurasi target global dan otomasi.</p>
    </div>
</div>

<div class="card" style="max-width: 640px;">
    <form method="POST" action="{{ route('settings.update') }}">
        @csrf
        @method('PUT')
        <div class="form-group">
            <label for="global_target">Target Global BWA (Rp)</label>
            <input type="number" id="global_target" name="global_target" value="{{ old('global_target', $settings['global_target']) }}" min="0" step="0.01">
            <small style="color: var(--gray-500);">Total target fundraising secara keseluruhan.</small>
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

        <hr style="border: none; border-top: 1px solid var(--gray-200); margin: 20px 0;">

        <h3 style="font-size: 15px; margin-bottom: 14px;">WhatsApp Publik</h3>

        <div class="form-group">
            <label for="wa_public_number">Nomor WhatsApp BWA (untuk tombol WA di halaman publik)</label>
            <input type="text" id="wa_public_number" name="wa_public_number" value="{{ old('wa_public_number', $settings['wa_public_number']) }}" placeholder="6281234567890">
            <small style="color: var(--gray-500);">Format internasional tanpa + dan tanpa spasi.</small>
        </div>

        <div class="form-group">
            <label for="wa_public_template">Template WA Publik</label>
            <textarea id="wa_public_template" name="wa_public_template" rows="3">{{ old('wa_public_template', $settings['wa_public_template']) }}</textarea>
            <small style="color: var(--gray-500);">Placeholder <code>{program}</code> akan diganti nama program.</small>
        </div>

        <div class="form-group">
            <label for="wa_agent_template">Template WA Agen</label>
            <textarea id="wa_agent_template" name="wa_agent_template" rows="3">{{ old('wa_agent_template', $settings['wa_agent_template']) }}</textarea>
            <small style="color: var(--gray-500);">Placeholder <code>{agen}</code> (nama agen) dan <code>{program}</code> (nama program).</small>
        </div>

        <hr style="border: none; border-top: 1px solid var(--gray-200); margin: 20px 0;">

        <h3 style="font-size: 15px; margin-bottom: 14px;">Otomasi WhatsApp</h3>

        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="wa_reminder_enabled" value="1" {{ old('wa_reminder_enabled', $settings['wa_reminder_enabled']) == '1' ? 'checked' : '' }}>
                Aktifkan pengingat otomatis WhatsApp
            </label>
        </div>
        <div class="form-group">
            <label for="wa_reminder_hour">Jam Pengingat</label>
            <select id="wa_reminder_hour" name="wa_reminder_hour">
                @for($h = 0; $h <= 23; $h++)
                    <option value="{{ $h }}" {{ old('wa_reminder_hour', $settings['wa_reminder_hour']) == $h ? 'selected' : '' }}>
                        {{ sprintf('%02d:00', $h) }}
                    </option>
                @endfor
            </select>
            <small style="color: var(--gray-500);">Dijalankan oleh cron job melalui <code>php artisan schedule:run</code>.</small>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Pengaturan</button>
        </div>
    </form>
</div>

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
@endsection
