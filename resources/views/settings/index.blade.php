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
    .testimonial-row {
        display: flex;
        gap: 12px;
        align-items: flex-start;
        padding: 12px;
        border: 1px solid var(--gray-200);
        border-radius: 12px;
        background: #fafcfc;
        margin-bottom: 10px;
    }
    .testimonial-row-photo {
        flex: 0 0 96px;
    }
    .testimonial-row-photo img {
        width: 96px;
        height: 96px;
        object-fit: contain;
        display: block;
        border: 1px dashed var(--gray-300);
        border-radius: 8px;
        background: #fff;
    }
    .testimonial-row-photo img[src=""] {
        display: none;
    }
    .testimonial-row-photo .t-file {
        font-size: 11px;
        width: 100%;
    }
    .testimonial-row-fields {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .testimonial-row-fields textarea {
        width: 100%;
    }
    .testimonial-row-fields input {
        width: 100%;
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
    <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data">
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

        <div class="form-group">
            <label>Testimoni Tokoh <small style="color: var(--gray-500); font-weight: 400;">— auto slideshow di bawah quote, tanpa judul. Foto tampil tanpa frame (unggah foto dengan latar transparan).</small></label>

            <div id="testimonials-wrap">
                @foreach($settings['home_testimonials'] as $index => $testimonial)
                <div class="testimonial-row">
                    <div class="testimonial-row-photo">
                        <img class="t-preview" src="{{ $testimonial['photo'] ? asset_photo_url($testimonial['photo']) : '' }}" alt="Foto tokoh">
                        <input type="file" class="t-file" name="testimonials[{{ $index }}][photo]" accept="image/png,image/jpeg,image/webp">
                        <input type="hidden" class="t-existing" name="testimonials[{{ $index }}][existing_photo]" value="{{ $testimonial['photo'] }}">
                        <input type="hidden" class="t-remove-flag" name="testimonials[{{ $index }}][photo_remove]" value="0">
                        <button type="button" class="t-rm-photo btn btn-outline btn-sm" style="width: 100%; margin-top: 6px;">Hapus Foto</button>
                    </div>
                    <div class="testimonial-row-fields">
                        <textarea class="t-text" name="testimonials[{{ $index }}][text]" rows="2" placeholder="Teks testimoni">{{ $testimonial['text'] }}</textarea>
                        <input type="text" class="t-name" name="testimonials[{{ $index }}][name]" placeholder="Nama tokoh" value="{{ $testimonial['name'] }}">
                    </div>
                    <button type="button" class="t-rm-row btn btn-outline btn-sm" style="align-self: flex-start;">Hapus</button>
                </div>
                @endforeach
            </div>

            <button type="button" id="add-testimonial" class="btn btn-outline btn-sm"><i class="fas fa-plus"></i> Tambah Testimoni</button>
        </div>

        <div class="form-group">
            <label>Logo Mitra (Perusahaan/Lembaga) <small style="color: var(--gray-500); font-weight: 400;">— running nonstop di bawah testimoni, tanpa judul. Hanya gambar logo (gunakan PNG dengan latar transparan agar rapi).</small></label>

            <div id="logos-wrap">
                @foreach($settings['home_partner_logos'] as $index => $logo)
                <div class="testimonial-row">
                    <div class="testimonial-row-photo">
                        <img class="l-preview" src="{{ asset_photo_url($logo) }}" alt="Logo mitra">
                        <input type="file" class="l-file" name="logos[{{ $index }}][photo]" accept="image/png,image/jpeg,image/webp">
                        <input type="hidden" class="l-existing" name="logos[{{ $index }}][existing_photo]" value="{{ $logo }}">
                        <input type="hidden" class="l-remove-flag" name="logos[{{ $index }}][photo_remove]" value="0">
                        <button type="button" class="l-rm-photo btn btn-outline btn-sm" style="width: 100%; margin-top: 6px;">Hapus Logo</button>
                    </div>
                    <button type="button" class="l-rm-row btn btn-outline btn-sm" style="align-self: flex-start;">Hapus</button>
                </div>
                @endforeach
            </div>

            <button type="button" id="add-logo" class="btn btn-outline btn-sm"><i class="fas fa-plus"></i> Tambah Logo</button>
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

(function () {
    var wrap = document.getElementById('testimonials-wrap');
    var addBtn = document.getElementById('add-testimonial');
    if (!wrap || !addBtn) return;

    var index = wrap.querySelectorAll('.testimonial-row').length;

    function bindRow(row) {
        var file = row.querySelector('.t-file');
        var preview = row.querySelector('.t-preview');
        var removeFlag = row.querySelector('.t-remove-flag');

        if (file && preview) {
            file.addEventListener('change', function () {
                var f = file.files && file.files[0];
                if (!f) return;
                var reader = new FileReader();
                reader.onload = function (e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(f);
            });
        }

        var rmPhoto = row.querySelector('.t-rm-photo');
        if (rmPhoto) {
            rmPhoto.addEventListener('click', function () {
                preview.src = '';
                preview.style.display = 'none';
                if (file) file.value = '';
                if (removeFlag) removeFlag.value = '1';
            });
        }

        var rmRow = row.querySelector('.t-rm-row');
        if (rmRow) {
            rmRow.addEventListener('click', function () {
                row.remove();
            });
        }
    }

    wrap.querySelectorAll('.testimonial-row').forEach(function (row) {
        var preview = row.querySelector('.t-preview');
        if (preview && !preview.getAttribute('src')) {
            preview.style.display = 'none';
        }
        bindRow(row);
    });

    addBtn.addEventListener('click', function () {
        var row = document.createElement('div');
        row.className = 'testimonial-row';
        row.innerHTML =
            '<div class="testimonial-row-photo">' +
                '<img class="t-preview" src="" alt="Foto tokoh" style="display:none;">' +
                '<input type="file" class="t-file" name="testimonials[' + index + '][photo]" accept="image/png,image/jpeg,image/webp">' +
                '<input type="hidden" class="t-existing" name="testimonials[' + index + '][existing_photo]" value="">' +
                '<input type="hidden" class="t-remove-flag" name="testimonials[' + index + '][photo_remove]" value="0">' +
                '<button type="button" class="t-rm-photo btn btn-outline btn-sm" style="width: 100%; margin-top: 6px;">Hapus Foto</button>' +
            '</div>' +
            '<div class="testimonial-row-fields">' +
                '<textarea class="t-text" name="testimonials[' + index + '][text]" rows="2" placeholder="Teks testimoni"></textarea>' +
                '<input type="text" class="t-name" name="testimonials[' + index + '][name]" placeholder="Nama tokoh">' +
            '</div>' +
            '<button type="button" class="t-rm-row btn btn-outline btn-sm" style="align-self: flex-start;">Hapus</button>';
        wrap.appendChild(row);
        bindRow(row);
        index++;
    });
})();

(function () {
    var wrap = document.getElementById('logos-wrap');
    var addBtn = document.getElementById('add-logo');
    if (!wrap || !addBtn) return;

    var index = wrap.querySelectorAll('.testimonial-row').length;

    function bindLogoRow(row) {
        var file = row.querySelector('.l-file');
        var preview = row.querySelector('.l-preview');
        var removeFlag = row.querySelector('.l-remove-flag');

        if (file && preview) {
            file.addEventListener('change', function () {
                var f = file.files && file.files[0];
                if (!f) return;
                var reader = new FileReader();
                reader.onload = function (e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(f);
            });
        }

        var rmPhoto = row.querySelector('.l-rm-photo');
        if (rmPhoto) {
            rmPhoto.addEventListener('click', function () {
                preview.src = '';
                preview.style.display = 'none';
                if (file) file.value = '';
                if (removeFlag) removeFlag.value = '1';
            });
        }

        var rmRow = row.querySelector('.l-rm-row');
        if (rmRow) {
            rmRow.addEventListener('click', function () {
                row.remove();
            });
        }
    }

    wrap.querySelectorAll('.testimonial-row').forEach(function (row) {
        var preview = row.querySelector('.l-preview');
        if (preview && !preview.getAttribute('src')) {
            preview.style.display = 'none';
        }
        bindLogoRow(row);
    });

    addBtn.addEventListener('click', function () {
        var row = document.createElement('div');
        row.className = 'testimonial-row';
        row.innerHTML =
            '<div class="testimonial-row-photo">' +
                '<img class="l-preview" src="" alt="Logo mitra" style="display:none;">' +
                '<input type="file" class="l-file" name="logos[' + index + '][photo]" accept="image/png,image/jpeg,image/webp">' +
                '<input type="hidden" class="l-existing" name="logos[' + index + '][existing_photo]" value="">' +
                '<input type="hidden" class="l-remove-flag" name="logos[' + index + '][photo_remove]" value="0">' +
                '<button type="button" class="l-rm-photo btn btn-outline btn-sm" style="width: 100%; margin-top: 6px;">Hapus Logo</button>' +
            '</div>' +
            '<button type="button" class="l-rm-row btn btn-outline btn-sm" style="align-self: flex-start;">Hapus</button>';
        wrap.appendChild(row);
        bindLogoRow(row);
        index++;
    });
})();
</script>
@endpush
@endsection
