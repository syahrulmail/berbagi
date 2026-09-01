@php
    $editorName = $editorName ?? 'description';
    $editorId = $editorId ?? 'richEditor';
    $editorValue = $editorValue ?? '';
    $editorLabel = $editorLabel ?? 'Deskripsi';
    $editorPlaceholder = $editorPlaceholder ?? 'Tulis deskripsi program...';
    $editorTextareaId = $editorId . 'Textarea';
@endphp

@push('styles')
<style>
    .rich-editor-toolbar {
        display: flex;
        flex-wrap: wrap;
        gap: 2px;
        padding: 6px 8px;
        border: 1px solid var(--gray-300);
        border-bottom: none;
        border-radius: 10px 10px 0 0;
        background: #fafcfc;
    }
    .rich-editor-toolbar .re-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 30px;
        height: 30px;
        border: none;
        background: transparent;
        color: var(--gray-700);
        border-radius: 6px;
        cursor: pointer;
        font-size: 13px;
        transition: background .15s, color .15s;
    }
    .rich-editor-toolbar .re-btn:hover {
        background: #e8f4f2;
        color: var(--primary);
    }
    .rich-editor-toolbar .re-sep {
        width: 1px;
        margin: 4px 6px;
        background: var(--gray-300);
    }
    .rich-editor-box {
        min-height: 160px;
        padding: 12px 14px;
        border: 1px solid var(--gray-300);
        border-radius: 0 0 10px 10px;
        background: #fff;
        font-size: 14px;
        line-height: 1.6;
        color: var(--gray-900);
        outline: none;
    }
    .rich-editor-box:focus {
        border-color: var(--primary-500);
        box-shadow: 0 0 0 3px rgba(8, 168, 153, 0.15);
    }
    .rich-editor-box h2, .rich-editor-box h3 {
        margin: 10px 0 6px;
        color: var(--gray-900);
    }
    .rich-editor-box h2 { font-size: 18px; }
    .rich-editor-box h3 { font-size: 16px; }
    .rich-editor-box p { margin: 6px 0; }
    .rich-editor-box ul, .rich-editor-box ol { margin: 6px 0; padding-left: 24px; }
    .rich-editor-box blockquote {
        margin: 8px 0;
        padding: 8px 14px;
        border-left: 4px solid var(--primary-400);
        background: #f0faf8;
        color: var(--gray-700);
        border-radius: 0 8px 8px 0;
    }
    .rich-editor-box a { color: var(--primary); text-decoration: underline; }
    .rich-editor-box img { max-width: 100%; height: auto; border-radius: 8px; margin: 6px 0; }
    .rich-editor-toolbar .re-select {
        height: 30px;
        padding: 0 6px;
        border: 1px solid var(--gray-300);
        border-radius: 6px;
        background: #fff;
        color: var(--gray-700);
        font-size: 12px;
        cursor: pointer;
    }
    .rich-editor-toolbar .re-select:focus {
        outline: 2px solid rgba(8, 168, 153, 0.4);
        outline-offset: 1px;
    }
    .rich-editor-toolbar .re-swatch {
        width: 18px;
        height: 18px;
        margin: 6px 2px;
        border-radius: 50%;
        border: 2px solid #fff;
        box-shadow: 0 0 0 1px rgba(2, 35, 33, 0.18);
        padding: 0;
    }
    .rich-editor-toolbar .re-swatch:hover {
        transform: scale(1.15);
        background: inherit;
        color: inherit;
    }
    .rich-editor-toolbar .re-color-label {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 30px;
        height: 30px;
        border-radius: 6px;
        color: var(--gray-700);
        cursor: pointer;
    }
    .rich-editor-toolbar .re-color-label:hover {
        background: #e8f4f2;
        color: var(--primary);
    }
    .rich-editor-toolbar .re-color-label input {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        opacity: 0;
        cursor: pointer;
    }
    .rich-editor-toolbar .re-img-panel {
        flex-basis: 100%;
        display: none;
        flex-wrap: wrap;
        align-items: center;
        gap: 8px;
        padding: 10px 2px 2px;
        margin-top: 6px;
        border-top: 1px solid var(--gray-300);
    }
    .rich-editor-toolbar .re-img-panel.open { display: flex; }
    .rich-editor-toolbar .re-img-panel input[type="text"],
    .rich-editor-toolbar .re-img-panel input[type="file"] {
        border: 1px solid var(--gray-300);
        border-radius: 8px;
        padding: 7px 10px;
        font-size: 12px;
        background: #fff;
        color: var(--gray-900);
    }
    .rich-editor-toolbar .re-img-panel input[type="text"] { flex: 1 1 220px; min-width: 0; }
    .rich-editor-toolbar .re-img-panel input[type="text"]:focus {
        border-color: var(--primary-500);
        outline: none;
    }
    .rich-editor-toolbar .re-img-panel .re-img-file { max-width: 220px; }
    .rich-editor-toolbar .re-img-status { font-size: 12px; color: var(--gray-500); }
    .rich-editor-toolbar .re-img-status.error { color: var(--danger); }
</style>
@endpush

<div class="form-group">
    <label for="{{ $editorId }}">{{ $editorLabel }}</label>
    <div class="rich-editor-toolbar" data-richtoolbar>
        <button type="button" class="re-btn" data-cmd="bold" title="Tebal"><i class="fas fa-bold"></i></button>
        <button type="button" class="re-btn" data-cmd="italic" title="Miring"><i class="fas fa-italic"></i></button>
        <button type="button" class="re-btn" data-cmd="underline" title="Garis bawah"><i class="fas fa-underline"></i></button>
        <button type="button" class="re-btn" data-cmd="strikeThrough" title="Coret"><i class="fas fa-strikethrough"></i></button>
        <span class="re-sep"></span>
        <button type="button" class="re-btn" data-cmd="formatBlock" data-value="H2" title="Judul"><strong>H2</strong></button>
        <button type="button" class="re-btn" data-cmd="formatBlock" data-value="H3" title="Subjudul"><strong>H3</strong></button>
        <button type="button" class="re-btn" data-cmd="formatBlock" data-value="P" title="Paragraf"><i class="fas fa-paragraph"></i></button>
        <span class="re-sep"></span>
        <button type="button" class="re-btn" data-cmd="insertUnorderedList" title="Daftar poin"><i class="fas fa-list-ul"></i></button>
        <button type="button" class="re-btn" data-cmd="insertOrderedList" title="Daftar angka"><i class="fas fa-list-ol"></i></button>
        <button type="button" class="re-btn" data-cmd="formatBlock" data-value="BLOCKQUOTE" title="Kutipan"><i class="fas fa-quote-right"></i></button>
        <span class="re-sep"></span>
        <button type="button" class="re-btn" data-cmd="createLink" title="Tautan"><i class="fas fa-link"></i></button>
        <button type="button" class="re-btn" data-cmd="unlink" title="Hapus tautan"><i class="fas fa-unlink"></i></button>
        <span class="re-sep"></span>
        <select class="re-select" data-re-fontsize title="Ukuran font">
            <option value="">Ukuran</option>
            <option value="12px">12</option>
            <option value="14px">14</option>
            <option value="16px">16</option>
            <option value="18px">18</option>
            <option value="24px">24</option>
            <option value="32px">32</option>
            <option value="48px">48</option>
        </select>
        <span class="re-sep"></span>
        <button type="button" class="re-btn re-swatch" data-re-color="#b91c1c" title="Warna merah" style="background:#b91c1c"></button>
        <button type="button" class="re-btn re-swatch" data-re-color="#1d4ed8" title="Warna biru" style="background:#1d4ed8"></button>
        <button type="button" class="re-btn re-swatch" data-re-color="#086e66" title="Warna hijau" style="background:#086e66"></button>
        <button type="button" class="re-btn re-swatch" data-re-color="#935516" title="Warna emas" style="background:#935516"></button>
        <button type="button" class="re-btn re-swatch" data-re-color="#111827" title="Warna hitam" style="background:#111827"></button>
        <label class="re-color-label" title="Warna kustom"><input type="color" class="re-color-input" value="#086e66"><i class="fas fa-fill-drip"></i></label>
        <span class="re-sep"></span>
        <button type="button" class="re-btn" data-cmd="insertImage" title="Sisipkan gambar"><i class="fas fa-image"></i></button>
        <button type="button" class="re-btn" data-cmd="removeFormat" title="Hapus format"><i class="fas fa-eraser"></i></button>
        <div class="re-img-panel" data-re-imgpanel>
            <input type="text" class="re-img-url" data-re-imgurl placeholder="Tempel URL gambar (https://...)">
            <button type="button" class="btn btn-sm" data-re-imgurlbtn>Sisipkan URL</button>
            <input type="file" class="re-img-file" data-re-imgfile accept="image/*" title="Unggah gambar dari komputer">
            <span class="re-img-status" data-re-imgstatus></span>
            <button type="button" class="btn btn-sm btn-outline" data-re-imgcancel>Tutup</button>
        </div>
    </div>
    <div id="{{ $editorId }}" class="rich-editor-box" contenteditable="true" data-placeholder="{{ $editorPlaceholder }}">{!! $editorValue !!}</div>
    <textarea name="{{ $editorName }}" id="{{ $editorTextareaId }}" style="display:none;">{{ $editorValue }}</textarea>
    <small style="color: var(--gray-500); display:block; margin-top:4px;">Dukungan format teks kaya: tebal, miring, judul, daftar, kutipan, tautan, ukuran font, warna teks, dan gambar.</small>
</div>

@push('scripts')
<script>
(function () {
    'use strict';
    var editor = document.getElementById('{{ $editorId }}');
    var textarea = document.getElementById('{{ $editorTextareaId }}');
    if (!editor || !textarea) return;

    function sync() {
        textarea.value = editor.innerHTML;
    }

    editor.addEventListener('input', sync);

    var form = editor.closest('form');
    if (form) {
        form.addEventListener('submit', sync);
    }

    var savedRange = null;
    function saveSelection() {
        var sel = window.getSelection();
        if (sel && sel.rangeCount > 0 && editor.contains(sel.anchorNode)) {
            savedRange = sel.getRangeAt(0).cloneRange();
        }
    }
    editor.addEventListener('keyup', saveSelection);
    editor.addEventListener('mouseup', saveSelection);
    editor.addEventListener('blur', saveSelection);

    function restoreSelection() {
        editor.focus();
        if (savedRange) {
            var sel = window.getSelection();
            if (sel) {
                sel.removeAllRanges();
                sel.addRange(savedRange);
            }
        }
    }

    var PX_MAP = { 1: '12px', 2: '14px', 3: '16px', 4: '18px', 5: '24px', 6: '32px', 7: '48px' };
    function normalizeFontTags() {
        var html = editor.innerHTML;
        html = html.replace(/<font\b([^>]*)>/gi, function (m, attrs) {
            var size = (attrs.match(/\bsize="?(\d)"?/i) || [])[1];
            var color = (attrs.match(/\bcolor="?([^"\s>]+)"?/i) || [])[1];
            var style = '';
            if (size && PX_MAP[size]) style += 'font-size:' + PX_MAP[size] + ';';
            if (color) style += 'color:' + color + ';';
            return style ? '<span style="' + style + '">' : '<span>';
        });
        html = html.replace(/<\/font>/gi, '</span>');
        editor.innerHTML = html;
    }

    var toolbar = editor.parentElement.querySelector('[data-richtoolbar]');
    if (toolbar) {
        toolbar.addEventListener('mousedown', function (e) {
            var btn = e.target.closest('[data-cmd]');
            if (!btn) return;
            e.preventDefault();
            var cmd = btn.getAttribute('data-cmd');
            var val = btn.getAttribute('data-value') || null;

            if (cmd === 'insertImage') {
                showImgPanel();
                return;
            }
            if (cmd === 'createLink') {
                var url = window.prompt('Masukkan URL tautan:', 'https://');
                if (!url || !url.trim()) return;
                val = url.trim();
            }

            editor.focus();
            document.execCommand(cmd, false, val);
            sync();
        });

        var fontSelect = toolbar.querySelector('[data-re-fontsize]');
        if (fontSelect) {
            fontSelect.addEventListener('change', function () {
                var px = fontSelect.value;
                if (!px) return;
                restoreSelection();
                var htmlNum = '';
                for (var k in PX_MAP) {
                    if (PX_MAP[k] === px) { htmlNum = k; break; }
                }
                document.execCommand('fontSize', false, htmlNum);
                normalizeFontTags();
                sync();
                fontSelect.value = '';
            });
        }

        var swatches = toolbar.querySelectorAll('[data-re-color]');
        swatches.forEach(function (sw) {
            sw.addEventListener('mousedown', function (e) { e.preventDefault(); });
            sw.addEventListener('click', function () {
                restoreSelection();
                document.execCommand('foreColor', false, sw.getAttribute('data-re-color'));
                normalizeFontTags();
                sync();
            });
        });

        var colorInput = toolbar.querySelector('.re-color-input');
        if (colorInput) {
            colorInput.addEventListener('change', function () {
                restoreSelection();
                document.execCommand('foreColor', false, colorInput.value);
                normalizeFontTags();
                sync();
            });
        }

        var imgPanel = toolbar.querySelector('[data-re-imgpanel]');
        var imgUrlInput = toolbar.querySelector('[data-re-imgurl]');
        var imgUrlBtn = toolbar.querySelector('[data-re-imgurlbtn]');
        var imgFile = toolbar.querySelector('[data-re-imgfile]');
        var imgCancel = toolbar.querySelector('[data-re-imgcancel]');
        var imgStatus = toolbar.querySelector('[data-re-imgstatus]');
        var csrfMeta = document.querySelector('meta[name="csrf-token"]');
        var csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';
        var uploadUrl = '{{ route('uploads.rich-image') }}';

        function setImgStatus(msg, isError) {
            if (!imgStatus) return;
            imgStatus.textContent = msg || '';
            imgStatus.classList.toggle('error', !!isError);
        }
        function showImgPanel() {
            if (!imgPanel) return;
            var open = !imgPanel.classList.contains('open');
            imgPanel.classList.toggle('open', open);
            setImgStatus('');
            if (open && imgUrlInput) imgUrlInput.focus();
        }
        function closeImgPanel() {
            if (imgPanel) imgPanel.classList.remove('open');
            if (imgUrlInput) imgUrlInput.value = '';
            if (imgFile) imgFile.value = '';
            setImgStatus('');
        }
        function insertImage(url) {
            restoreSelection();
            document.execCommand('insertImage', false, url);
            sync();
            closeImgPanel();
        }
        if (imgUrlBtn && imgUrlInput) {
            imgUrlBtn.addEventListener('click', function () {
                var url = imgUrlInput.value.trim();
                if (!url) {
                    setImgStatus('Masukkan URL gambar terlebih dahulu.', true);
                    return;
                }
                insertImage(url);
            });
        }
        if (imgFile) {
            imgFile.addEventListener('change', function () {
                var file = imgFile.files && imgFile.files[0];
                if (!file) return;
                setImgStatus('Mengunggah gambar...');
                var fd = new FormData();
                fd.append('image', file);
                fetch(uploadUrl, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    body: fd
                })
                .then(function (r) {
                    return r.json().then(function (j) { return { ok: r.ok, json: j }; });
                })
                .then(function (res) {
                    if (res.ok && res.json && res.json.url) {
                        insertImage(res.json.url);
                    } else {
                        setImgStatus((res.json && res.json.error) || 'Gagal mengunggah gambar.', true);
                        imgFile.value = '';
                    }
                })
                .catch(function () {
                    setImgStatus('Gagal mengunggah gambar.', true);
                    imgFile.value = '';
                });
            });
        }
        if (imgCancel) {
            imgCancel.addEventListener('click', closeImgPanel);
        }
    }

    if (editor.innerText.trim() === '') {
        editor.innerHTML = '';
    }
})();
</script>
@endpush
